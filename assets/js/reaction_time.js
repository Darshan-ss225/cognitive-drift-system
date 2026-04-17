(function () {
    const startBtn = document.getElementById('startReactionBtn');
    const resetBtn = document.getElementById('resetReactionBtn');
    const stimulus = document.getElementById('reactionStimulus');
    const attemptsText = document.getElementById('reactionAttemptsText');
    const lastText = document.getElementById('reactionLastText');
    const averageText = document.getElementById('reactionAverageText');
    const statusText = document.getElementById('reactionStatusText');
    const avgInput = document.getElementById('reaction_avg');
    const attemptsJsonInput = document.getElementById('reaction_attempts_json');

    if (!startBtn || !stimulus) return;

    let attempts = [];
    let active = false;
    let startTimestamp = 0;
    let timeoutId = null;
    let waiting = false;

    function render() {
        attemptsText.textContent = attempts.length;
        lastText.textContent = attempts.length ? String(attempts[attempts.length - 1]) : '--';

        if (attempts.length) {
            const avg = attempts.reduce((sum, value) => sum + value, 0) / attempts.length;
            averageText.textContent = avg.toFixed(2);
            avgInput.value = avg.toFixed(2);
        } else {
            averageText.textContent = '--';
            avgInput.value = '';
        }

        attemptsJsonInput.value = JSON.stringify(attempts);

        if (attempts.length >= 3) {
            statusText.textContent = 'Reaction test complete. You can submit now.';
        } else {
            statusText.textContent = 'You need at least 3 valid attempts.';
        }
    }

    function clearState() {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = null;
        waiting = false;
        active = false;
        startTimestamp = 0;
        stimulus.classList.remove('active');
    }

    function startTest() {
        clearState();
        statusText.textContent = 'Wait for green...';
        waiting = true;

        const delay = Math.floor(Math.random() * 2500) + 1500;

        timeoutId = setTimeout(() => {
            stimulus.classList.add('active');
            active = true;
            waiting = false;
            startTimestamp = performance.now();
            statusText.textContent = 'Click now!';
        }, delay);
    }

    function resetTest() {
        clearState();
        attempts = [];
        render();
        statusText.textContent = 'You need at least 3 valid attempts.';
    }

    stimulus.addEventListener('click', function () {
        if (waiting) {
            clearState();
            statusText.textContent = 'Too early. Wait for green, then start again.';
            return;
        }

        if (!active) return;

        const reaction = Math.round(performance.now() - startTimestamp);
        attempts.push(reaction);
        clearState();
        render();
        statusText.textContent = 'Reaction captured. Start next attempt.';
    });

    startBtn.addEventListener('click', startTest);
    resetBtn.addEventListener('click', resetTest);

    render();
})();