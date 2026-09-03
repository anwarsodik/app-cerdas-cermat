(() => {
    'use strict';

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) {
                event.preventDefault();
            }
        });
    });

    async function fetchJson(url, options = {}) {
        const response = await fetch(url, {
            cache: 'no-store',
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...(options.headers || {}),
            },
        });
        const data = await response.json().catch(() => ({ ok: false, message: 'Respons server tidak dapat dibaca.' }));
        if (!response.ok) {
            throw new Error(data.message || 'Permintaan gagal diproses.');
        }
        return data;
    }

    const consoleRoot = document.querySelector('.operator-console');
    if (consoleRoot) {
        const matchId = Number(consoleRoot.dataset.matchId);
        const csrf = consoleRoot.dataset.csrf;
        const feedback = document.querySelector('#action-feedback');
        let requestRunning = false;

        const actionConfig = {
            'open-question': ['/api/open-question.php', {}],
            correct: ['/api/judge-answer.php', { decision: 'correct' }],
            wrong: ['/api/judge-answer.php', { decision: 'wrong' }],
            'cancel-buzz': ['/api/judge-answer.php', { decision: 'cancel_buzz' }],
            'undo-score': ['/api/undo-score.php', {}],
            finish: ['/api/finish-match.php', {}],
        };

        function setFeedback(message, type = 'success') {
            feedback.textContent = message;
            feedback.className = `action-feedback is-${type}`;
        }

        async function runAction(action) {
            if (requestRunning || !actionConfig[action]) return;
            if (action === 'finish' && !window.confirm('Akhiri pertandingan dan kunci hasil saat ini?')) return;

            requestRunning = true;
            consoleRoot.classList.add('is-busy');
            const [url, payload] = actionConfig[action];
            try {
                const result = await fetchJson(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ match_id: matchId, ...payload }),
                });
                setFeedback(result.message);
                if (action === 'finish') {
                    window.setTimeout(() => window.location.reload(), 700);
                    return;
                }
                await refreshConsole();
            } catch (error) {
                setFeedback(error.message, 'error');
            } finally {
                requestRunning = false;
                consoleRoot.classList.remove('is-busy');
            }
        }

        document.querySelectorAll('[data-match-action]').forEach((button) => {
            button.addEventListener('click', () => runAction(button.dataset.matchAction));
        });

        document.querySelectorAll('[data-buzz-team]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (requestRunning) return;
                requestRunning = true;
                try {
                    const result = await fetchJson('/api/press-buzzer.php', {
                        method: 'POST',
                        body: JSON.stringify({ match_id: matchId, team_id: Number(button.dataset.buzzTeam) }),
                    });
                    setFeedback(result.message);
                    await refreshConsole();
                } catch (error) {
                    setFeedback(error.message, 'error');
                } finally {
                    requestRunning = false;
                }
            });
        });

        function renderHistory(history) {
            const list = document.querySelector('#history-list');
            list.replaceChildren();
            if (!history.length) {
                const item = document.createElement('li');
                item.className = 'history-empty';
                item.textContent = 'Belum ada perubahan nilai.';
                list.append(item);
                return;
            }
            history.forEach((entry) => {
                const item = document.createElement('li');
                if (entry.reversed_at) item.className = 'is-reversed';
                const name = document.createElement('strong');
                name.textContent = entry.team_name;
                const detail = document.createElement('span');
                detail.textContent = `${entry.reason}, skor ${entry.score_after}`;
                item.append(name, detail);
                list.append(item);
            });
        }

        function applyConsole(data) {
            const match = data.match;
            const stage = document.querySelector('#decision-stage');
            stage.dataset.status = match.status;
            document.querySelector('#match-status-label').textContent = match.status_label;
            document.querySelector('#question-number').textContent = match.current_question;

            const title = document.querySelector('#decision-title');
            const kicker = document.querySelector('#decision-kicker');
            const help = document.querySelector('#decision-help');
            const copy = {
                ready: ['Pertandingan siap', 'Buka soal saat moderator siap', 'Bel regu masih dikunci.'],
                question_open: ['Menunggu bel', 'Bel sudah dibuka', 'Tekan salah satu tombol regu untuk simulasi.'],
                buzzed: ['Hak jawab', `${match.buzzer_team_name} mendapat hak jawab`, 'Juri menentukan jawaban benar atau salah.'],
                judged: ['Penilaian tercatat', 'Lanjutkan ke soal berikutnya', 'Skor telah diperbarui dan tersimpan.'],
                finished: ['Selesai', 'Pertandingan telah berakhir', 'Hasil akhir tersimpan.'],
            }[match.status] || ['Status', match.status_label, ''];
            kicker.textContent = copy[0];
            title.textContent = copy[1];
            help.textContent = copy[2];

            const visibility = {
                'open-question': ['ready', 'judged'].includes(match.status),
                correct: match.status === 'buzzed',
                wrong: match.status === 'buzzed',
                'cancel-buzz': match.status === 'buzzed',
                'undo-score': match.status === 'judged',
                finish: match.status !== 'finished',
            };
            Object.entries(visibility).forEach(([action, visible]) => {
                const button = document.querySelector(`[data-match-action="${action}"]`);
                if (button) button.hidden = !visible;
            });

            data.teams.forEach((team) => {
                const row = document.querySelector(`[data-team-row="${team.id}"]`);
                if (!row) return;
                row.classList.toggle('is-answering', match.status === 'buzzed' && Number(match.buzzer_team_id) === Number(team.id));
                row.querySelector('.score-value').textContent = team.score;
                row.querySelector('.team-state').textContent = match.status === 'buzzed' && Number(match.buzzer_team_id) === Number(team.id)
                    ? 'Memegang hak jawab'
                    : match.status === 'question_open' ? 'Bel siap ditekan' : 'Menunggu soal';
                row.querySelector('[data-buzz-team]').disabled = match.status !== 'question_open';
            });
            renderHistory(data.history);
        }

        async function refreshConsole() {
            try {
                const result = await fetchJson(`/api/match-status.php?match_id=${matchId}`);
                applyConsole(result.data);
            } catch (error) {
                setFeedback('Status terbaru gagal dimuat. Coba muat ulang halaman.', 'error');
            }
        }

        refreshConsole();
        window.setInterval(refreshConsole, 1400);
    }

    const scoreboard = document.querySelector('[data-public-match]');
    if (scoreboard) {
        const matchId = Number(scoreboard.dataset.publicMatch);
        let timerId = null;
        let timerKey = '';

        function runAnswerTimer(match) {
            const timer = document.querySelector('#answer-timer');
            const key = `${match.current_question}:${match.buzzer_team_id || ''}:${match.status}`;
            if (key === timerKey) return;
            timerKey = key;
            window.clearInterval(timerId);

            if (match.status !== 'buzzed') {
                timer.textContent = '';
                return;
            }

            let remaining = Number(match.answer_seconds);
            timer.textContent = `${remaining} detik`;
            timerId = window.setInterval(() => {
                remaining = Math.max(0, remaining - 1);
                timer.textContent = remaining > 0 ? `${remaining} detik` : 'Waktu habis';
                if (remaining === 0) window.clearInterval(timerId);
            }, 1000);
        }

        function applyScoreboard(data) {
            const match = data.match;
            document.querySelector('#public-question').textContent = match.current_question;
            document.querySelector('#public-status-label').textContent = match.status_label;
            document.querySelector('#public-status').dataset.status = match.status;

            const publicTitle = {
                ready: 'Pertandingan segera dimulai',
                question_open: 'Bel dibuka, siapa yang paling cepat?',
                buzzed: `${match.buzzer_team_name} mendapat hak jawab`,
                judged: 'Penilaian tercatat',
                finished: 'Pertandingan selesai',
            }[match.status] || match.status_label;
            document.querySelector('#public-status-title').textContent = publicTitle;

            const grid = document.querySelector('#public-score-grid');
            grid.replaceChildren();
            data.teams.forEach((team) => {
                const card = document.createElement('article');
                card.className = 'public-team-card';
                const answering = match.status === 'buzzed' && Number(match.buzzer_team_id) === Number(team.id);
                if (answering) card.classList.add('is-answering');
                const name = document.createElement('h3');
                name.textContent = team.name;
                const score = document.createElement('strong');
                score.textContent = team.score;
                const state = document.createElement('span');
                state.textContent = answering ? 'Hak jawab' : 'Menunggu';
                card.append(name, score, state);
                grid.append(card);
            });
            runAnswerTimer(match);
            document.querySelector('#public-sync-status').textContent = 'Skor diperbarui';
        }

        async function refreshScoreboard() {
            try {
                const result = await fetchJson(`/api/match-status.php?match_id=${matchId}`);
                applyScoreboard(result.data);
            } catch (error) {
                document.querySelector('#public-sync-status').textContent = 'Gagal memuat skor, mencoba kembali';
            }
        }

        refreshScoreboard();
        window.setInterval(refreshScoreboard, 1200);
    }

    const teamBuzzer = document.querySelector('[data-team-buzzer]');
    if (teamBuzzer) {
        const matchId = Number(teamBuzzer.dataset.matchId);
        const teamId = Number(teamBuzzer.dataset.teamId);
        const accessCode = teamBuzzer.dataset.accessCode;
        const button = document.querySelector('#big-buzzer');
        const state = document.querySelector('#buzzer-state');
        const instruction = document.querySelector('#buzzer-instruction');
        const feedback = document.querySelector('#buzzer-feedback');
        let pressedForQuestion = null;

        function updateBuzzer(match) {
            const isOpen = match.status === 'question_open';
            const won = Number(match.buzzer_team_id) === teamId;
            if (match.current_question !== pressedForQuestion && isOpen) pressedForQuestion = null;
            button.disabled = !isOpen || pressedForQuestion === Number(match.current_question);
            button.classList.toggle('is-winner', won);

            if (won) {
                state.textContent = 'Bel diterima';
                instruction.textContent = 'Regu Anda mendapat hak jawab.';
            } else if (isOpen) {
                state.textContent = 'Bel siap';
                instruction.textContent = 'Tekan tombol secepatnya.';
            } else if (match.status === 'buzzed') {
                state.textContent = 'Bel dikunci';
                instruction.textContent = `${match.buzzer_team_name} mendapat hak jawab.`;
            } else {
                state.textContent = 'Bel belum dibuka';
                instruction.textContent = 'Tunggu sampai operator membuka soal.';
            }
        }

        async function refreshBuzzer() {
            try {
                const result = await fetchJson(`/api/match-status.php?match_id=${matchId}`);
                updateBuzzer(result.data.match);
            } catch (error) {
                button.disabled = true;
                state.textContent = 'Tidak terhubung';
                instruction.textContent = 'Muat ulang halaman untuk mencoba kembali.';
            }
        }

        button.addEventListener('click', async () => {
            button.disabled = true;
            feedback.textContent = 'Mengirim bel...';
            try {
                const result = await fetchJson('/api/press-buzzer.php', {
                    method: 'POST',
                    body: JSON.stringify({ match_id: matchId, team_id: teamId, access_code: accessCode }),
                });
                pressedForQuestion = Number((await fetchJson(`/api/match-status.php?match_id=${matchId}`)).data.match.current_question);
                feedback.textContent = result.message;
                if ('vibrate' in navigator) navigator.vibrate(120);
            } catch (error) {
                feedback.textContent = error.message;
            }
            await refreshBuzzer();
        });

        refreshBuzzer();
        window.setInterval(refreshBuzzer, 1100);
    }
})();
