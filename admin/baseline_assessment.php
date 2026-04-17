<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Baseline Assessment';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $baseline_reaction_avg = isset($_POST['baseline_reaction_avg']) ? (float)$_POST['baseline_reaction_avg'] : 0;
    $baseline_confidence_score = isset($_POST['baseline_confidence_score']) ? (float)$_POST['baseline_confidence_score'] : 0;
    $baseline_quiz_score = isset($_POST['baseline_quiz_score']) ? (float)$_POST['baseline_quiz_score'] : 0;
    $baseline_text_summary = trim($_POST['baseline_text_summary'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($subject_id <= 0) {
        set_flash('error', 'Please select a subject.');
        redirect('admin/baseline_assessment.php');
    }

    $insert = $pdo->prepare("
        INSERT INTO baseline_assessments (
            subject_id,
            baseline_reaction_avg,
            baseline_confidence_score,
            baseline_quiz_score,
            baseline_text_summary,
            notes,
            created_at
        ) VALUES (
            :subject_id,
            :baseline_reaction_avg,
            :baseline_confidence_score,
            :baseline_quiz_score,
            :baseline_text_summary,
            :notes,
            NOW()
        )
    ");
    $insert->execute([
        'subject_id' => $subject_id,
        'baseline_reaction_avg' => $baseline_reaction_avg,
        'baseline_confidence_score' => $baseline_confidence_score,
        'baseline_quiz_score' => $baseline_quiz_score,
        'baseline_text_summary' => $baseline_text_summary,
        'notes' => $notes
    ]);

    $update = $pdo->prepare("
        UPDATE subjects
        SET baseline_score = :baseline_score,
            baseline_status = 'Completed'
        WHERE id = :id
    ");
    $update->execute([
        'baseline_score' => $baseline_quiz_score,
        'id' => $subject_id
    ]);

    set_flash('success', 'Baseline assessment saved successfully.');
    redirect('admin/baseline_assessment.php');
}

$subjects = $pdo->query("
    SELECT id, full_name, subject_code, baseline_status
    FROM subjects
    ORDER BY full_name ASC
")->fetchAll();

$recentAssessments = $pdo->query("
    SELECT b.*, s.full_name, s.subject_code
    FROM baseline_assessments b
    JOIN subjects s ON b.subject_id = s.id
    ORDER BY b.id DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="content">
        <div class="topbar">
            <h1>Baseline Assessment</h1>
        </div>

        <?php require_once __DIR__ . '/../includes/alerts_helper.php'; ?>

        <div class="profile-grid">
            <div class="panel form-panel">
                <h2>Create Baseline</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id" required>
                            <option value="">Select subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= e($subject['id']) ?>">
                                    <?= e($subject['full_name']) ?> (<?= e($subject['subject_code']) ?>) - <?= e($subject['baseline_status'] ?: 'Pending') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Baseline Reaction Average</label>
                        <input type="number" step="0.01" name="baseline_reaction_avg" required>
                    </div>

                    <div class="form-group">
                        <label>Baseline Confidence Score</label>
                        <input type="number" step="0.01" name="baseline_confidence_score" required>
                    </div>

                    <div class="form-group">
                        <label>Baseline Quiz Score</label>
                        <input type="number" step="0.01" name="baseline_quiz_score" required>
                    </div>

                    <div class="form-group">
                        <label>Baseline Text Summary</label>
                        <textarea name="baseline_text_summary" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" rows="4"></textarea>
                    </div>

                    <button type="submit" class="btn">Save Baseline</button>
                </form>
            </div>

            <div class="panel">
                <h2>Recent Baselines</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Reaction</th>
                                <th>Confidence</th>
                                <th>Quiz</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentAssessments): ?>
                                <?php foreach ($recentAssessments as $row): ?>
                                    <tr>
                                        <td><?= e($row['full_name']) ?> (<?= e($row['subject_code']) ?>)</td>
                                        <td><?= number_format((float)$row['baseline_reaction_avg'], 2) ?></td>
                                        <td><?= number_format((float)$row['baseline_confidence_score'], 2) ?></td>
                                        <td><?= number_format((float)$row['baseline_quiz_score'], 2) ?></td>
                                        <td><?= e($row['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No baseline assessments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>