<?php
require_once __DIR__ . '/../includes/auth.php';

$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$status    = trim($_GET['status'] ?? '');
$fromDate  = trim($_GET['from_date'] ?? '');
$toDate    = trim($_GET['to_date'] ?? '');

$sql = "
    SELECT
        s.id AS session_id,
        s.subject_id,
        s.session_date,
        s.reaction_avg,
        s.confidence_score,
        s.quiz_score,
        s.drift_score,
        s.drift_status,
        s.risk_level,
        s.analyzed_at,
        s.text_sample,
        sub.full_name,
        sub.subject_code
    FROM sessions s
    JOIN subjects sub ON s.subject_id = sub.id
    WHERE 1=1
";

$params = [];

if ($subjectId > 0) {
    $sql .= " AND s.subject_id = :subject_id";
    $params['subject_id'] = $subjectId;
}

if ($status !== '') {
    $sql .= " AND s.drift_status = :status";
    $params['status'] = $status;
}

if ($fromDate !== '') {
    $sql .= " AND DATE(s.session_date) >= :from_date";
    $params['from_date'] = $fromDate;
}

if ($toDate !== '') {
    $sql .= " AND DATE(s.session_date) <= :to_date";
    $params['to_date'] = $toDate;
}

$sql .= " ORDER BY s.session_date DESC, s.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* Summary */
$totalRows = count($rows);
$high = 0;
$moderate = 0;
$low = 0;
$pending = 0;
$avgDrift = 0;

if ($rows) {
    $sumDrift = 0;
    foreach ($rows as $row) {
        $sumDrift += (float)$row['drift_score'];

        if (($row['drift_status'] ?? '') === 'High Drift') {
            $high++;
        } elseif (($row['drift_status'] ?? '') === 'Moderate Drift') {
            $moderate++;
        } elseif (($row['drift_status'] ?? '') === 'Low Drift') {
            $low++;
        } else {
            $pending++;
        }
    }
    $avgDrift = round($sumDrift / $totalRows, 2);
}

$filename = 'drift_report_' . date('Ymd_His') . '.xls';

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #ffffff;
            background: #0f172a;
            text-align: center;
            padding: 14px;
        }
        .subtitle {
            font-size: 11pt;
            background: #dbeafe;
            color: #0f172a;
            padding: 8px;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #ffffff;
            background: #0369a1;
            padding: 8px;
        }
        .summary-label {
            font-weight: bold;
            background: #f8fafc;
        }
        .summary-value {
            background: #ffffff;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background: #0b5d7a;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border: 1px solid #cbd5e1;
        }
        td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            vertical-align: top;
        }
        .status-high {
            background: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
        }
        .status-moderate {
            background: #fef3c7;
            color: #b45309;
            font-weight: bold;
        }
        .status-low {
            background: #dcfce7;
            color: #15803d;
            font-weight: bold;
        }
        .status-pending {
            background: #e0e7ff;
            color: #3730a3;
            font-weight: bold;
        }
        .text-cell {
            white-space: normal;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
        .spacer td {
            border: none;
            height: 10px;
            background: #ffffff;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td colspan="12" class="title">Cognitive Drift Analysis Report</td>
    </tr>
    <tr>
        <td colspan="12" class="subtitle">
            Generated On: <?= htmlspecialchars(date('d-m-Y H:i:s')) ?>
        </td>
    </tr>

    <tr class="spacer"><td colspan="12"></td></tr>

    <tr>
        <td colspan="4" class="section-title">Summary</td>
        <td colspan="8"></td>
    </tr>
    <tr>
        <td class="summary-label">Total Records</td>
        <td class="summary-value center"><?= $totalRows ?></td>
        <td class="summary-label">Average Drift</td>
        <td class="summary-value center"><?= number_format((float)$avgDrift, 2) ?></td>
        <td class="summary-label">High Drift</td>
        <td class="summary-value center"><?= $high ?></td>
        <td class="summary-label">Moderate Drift</td>
        <td class="summary-value center"><?= $moderate ?></td>
        <td class="summary-label">Low Drift</td>
        <td class="summary-value center"><?= $low ?></td>
        <td class="summary-label">Pending</td>
        <td class="summary-value center"><?= $pending ?></td>
    </tr>

    <tr class="spacer"><td colspan="12"></td></tr>

    <tr>
        <td colspan="12" class="section-title">Session Records</td>
    </tr>

    <tr>
        <th>Session ID</th>
        <th>Subject Name</th>
        <th>Subject Code</th>
        <th>Session Date</th>
        <th>Reaction Avg</th>
        <th>Confidence</th>
        <th>Quiz</th>
        <th>Drift</th>
        <th>Drift Status</th>
        <th>Risk Level</th>
        <th>Analyzed At</th>
        <th>Text Sample / AI Notes</th>
    </tr>

    <?php if ($rows): ?>
        <?php foreach ($rows as $row): ?>
            <?php
                $statusClass = 'status-pending';
                if (($row['drift_status'] ?? '') === 'High Drift') {
                    $statusClass = 'status-high';
                } elseif (($row['drift_status'] ?? '') === 'Moderate Drift') {
                    $statusClass = 'status-moderate';
                } elseif (($row['drift_status'] ?? '') === 'Low Drift') {
                    $statusClass = 'status-low';
                }
            ?>
            <tr>
                <td class="center"><?= (int)$row['session_id'] ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td class="center"><?= htmlspecialchars($row['subject_code']) ?></td>
                <td><?= htmlspecialchars($row['session_date']) ?></td>
                <td class="right"><?= number_format((float)$row['reaction_avg'], 2) ?></td>
                <td class="right"><?= number_format((float)$row['confidence_score'], 2) ?></td>
                <td class="right"><?= number_format((float)$row['quiz_score'], 2) ?></td>
                <td class="right"><?= number_format((float)$row['drift_score'], 2) ?></td>
                <td class="<?= $statusClass ?> center"><?= htmlspecialchars($row['drift_status'] ?: 'Pending') ?></td>
                <td class="center"><?= htmlspecialchars($row['risk_level'] ?: 'N/A') ?></td>
                <td><?= htmlspecialchars($row['analyzed_at'] ?: 'Not analyzed') ?></td>
                <td class="text-cell"><?= htmlspecialchars($row['text_sample'] ?: '') ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="12" class="center">No data found for the selected filters.</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>