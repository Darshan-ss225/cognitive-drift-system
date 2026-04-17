<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$subjects = $pdo->query("
    SELECT id, full_name, subject_code
    FROM subjects
    WHERE status = 'active'
    ORDER BY full_name ASC
")->fetchAll();

$questions = $pdo->query("
    SELECT id, question_text, option_a, option_b, option_c, option_d, correct_option
    FROM questions
    ORDER BY id ASC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="subject-page">
    <div class="subject-shell">
        <div class="subject-topbar">
            <h1>Subject Cognitive Session</h1>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-secondary">Admin Login</a>
        </div>

        <?php require_once __DIR__ . '/includes/alerts_helper.php'; ?>

        <form id="subjectSessionForm" class="subject-form" method="POST" action="<?= BASE_URL ?>/save_subject_session.php">
            <div class="subject-grid">
                <div class="panel glass-panel">
                    <h2>Subject Selection</h2>

                    <div class="form-group">
                        <label for="subject_id">Choose Subject</label>
                        <select name="subject_id" id="subject_id" required>
                            <option value="">Select subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= e($subject['id']) ?>">
                                    <?= e($subject['full_name']) ?> (<?= e($subject['subject_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="text_sample">Text Sample</label>
                        <textarea
                            name="text_sample"
                            id="text_sample"
                            rows="6"
                            placeholder="Write how you feel, how you approached the questions, or your current state..."
                            required
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="confidence_score">Confidence Score (0 - 100)</label>
                        <input type="number" name="confidence_score" id="confidence_score" min="0" max="100" step="0.01" required>
                    </div>
                </div>

                <div class="panel glass-panel reaction-panel">
                    <h2>Reaction Time Test</h2>

                    <div class="reaction-stage">
                        <p id="reactionInstruction" class="reaction-instruction">
                            Click start. Wait for the circle to turn green. Click it as quickly as possible.
                        </p>

                        <div id="reactionCircle" class="reaction-circle"></div>

                        <div class="reaction-actions">
                            <button type="button" id="startReactionBtn" class="btn">Start Reaction Test</button>
                            <button type="button" id="resetReactionBtn" class="btn btn-secondary">Reset</button>
                        </div>

                        <div class="reaction-stats">
                            <div class="stat-card">
                                <h3>Attempts</h3>
                                <p id="attemptCount">0</p>
                            </div>
                            <div class="stat-card">
                                <h3>Last Reaction (ms)</h3>
                                <p id="lastReaction">--</p>
                            </div>
                            <div class="stat-card">
                                <h3>Average Reaction (ms)</h3>
                                <p id="avgReaction">--</p>
                            </div>
                        </div>

                        <p class="reaction-note">You need at least 3 valid attempts.</p>
                        <input type="hidden" name="reaction_avg" id="reaction_avg" value="">
                    </div>
                </div>
            </div>

            <div class="panel glass-panel">
                <h2>Quiz Section</h2>

                <?php if ($questions): ?>
                    <div class="quiz-list">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="quiz-card">
                                <h3>Question <?= $index + 1 ?></h3>
                                <p class="quiz-question"><?= e($question['question_text']) ?></p>

                                <input type="hidden" name="responses[<?= $index ?>][question_id]" value="<?= e($question['id']) ?>">
                                <input type="hidden" name="responses[<?= $index ?>][correct_option]" value="<?= e($question['correct_option']) ?>">

                                <div class="quiz-options">
                                    <label><input type="radio" name="responses[<?= $index ?>][selected_option]" value="A" required> <?= e($question['option_a']) ?></label>
                                    <label><input type="radio" name="responses[<?= $index ?>][selected_option]" value="B" required> <?= e($question['option_b']) ?></label>
                                    <label><input type="radio" name="responses[<?= $index ?>][selected_option]" value="C" required> <?= e($question['option_c']) ?></label>
                                    <label><input type="radio" name="responses[<?= $index ?>][selected_option]" value="D" required> <?= e($question['option_d']) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No questions found. Add records to the questions table.</p>
                <?php endif; ?>

                <div class="subject-submit-wrap">
                    <button type="submit" class="btn btn-submit-session">Submit Session</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const circle = document.getElementById('reactionCircle');
    const instruction = document.getElementById('reactionInstruction');
    const startBtn = document.getElementById('startReactionBtn');
    const resetBtn = document.getElementById('resetReactionBtn');
    const attemptCount = document.getElementById('attemptCount');
    const lastReaction = document.getElementById('lastReaction');
    const avgReaction = document.getElementById('avgReaction');
    const reactionAvgInput = document.getElementById('reaction_avg');
    const form = document.getElementById('subjectSessionForm');

    let waiting = false;
    let ready = false;
    let timeoutId = null;
    let startTime = 0;
    let attempts = [];

    function updateStats() {
        attemptCount.textContent = attempts.length;
        lastReaction.textContent = attempts.length ? attempts[attempts.length - 1] : '--';

        if (attempts.length) {
            const avg = Math.round(attempts.reduce((a, b) => a + b, 0) / attempts.length);
            avgReaction.textContent = avg;
            reactionAvgInput.value = avg;
        } else {
            avgReaction.textContent = '--';
            reactionAvgInput.value = '';
        }
    }

    function resetReactionState() {
        waiting = false;
        ready = false;
        startTime = 0;

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        circle.className = 'reaction-circle';
        instruction.textContent = 'Click start. Wait for the circle to turn green. Click it as quickly as possible.';
        startBtn.disabled = false;
    }

    startBtn.addEventListener('click', () => {
        if (waiting || ready) return;

        waiting = true;
        startBtn.disabled = true;

        circle.className = 'reaction-circle waiting';
        instruction.textContent = 'Wait... do not click until the circle turns green.';

        const delay = Math.floor(Math.random() * 2500) + 1500;

        timeoutId = setTimeout(() => {
            waiting = false;
            ready = true;
            startTime = performance.now();

            circle.className = 'reaction-circle ready';
            instruction.textContent = 'Now click the green circle!';
        }, delay);
    });

    circle.addEventListener('click', () => {
        if (waiting) {
            if (timeoutId) clearTimeout(timeoutId);
            waiting = false;
            startBtn.disabled = false;
            circle.className = 'reaction-circle too-soon';
            instruction.textContent = 'Too soon! Wait for green before clicking.';
            return;
        }

        if (!ready) return;

        const endTime = performance.now();
        const reaction = Math.round(endTime - startTime);
        attempts.push(reaction);

        ready = false;
        circle.className = 'reaction-circle success';
        instruction.textContent = `Good! Your reaction time was ${reaction} ms.`;

        updateStats();
        startBtn.disabled = false;
    });

    resetBtn.addEventListener('click', () => {
        attempts = [];
        updateStats();
        resetReactionState();
    });

    form.addEventListener('submit', (e) => {
        if (attempts.length < 3) {
            e.preventDefault();
            alert('Please complete at least 3 valid reaction attempts before submitting.');
            return;
        }

        if (!reactionAvgInput.value) {
            e.preventDefault();
            alert('Reaction average is missing.');
        }
    });

    updateStats();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>