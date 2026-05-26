<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$student = [];
if ($role == 'student') {
    $sql_student = "SELECT s.*, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = '$user_id'";
    $result_student = mysqli_query($conn, $sql_student);
    if ($result_student && mysqli_num_rows($result_student) > 0) {
        $student = mysqli_fetch_assoc($result_student);
    }
}

$exams = [];
$sql = "SELECT * FROM exam_timetable ORDER BY exam_date ASC, start_time ASC";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $exams[] = $row;
    }
}

$photo = 'default.png';
$first_name = isset($student['first_name']) ? $student['first_name'] : 'Student';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Timetable - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .exam-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; overflow-x: auto; }
        .exam-table { width: 100%; border-collapse: collapse; }
        .exam-table th { background: #dc3545; color: white; padding: 12px; text-align: left; }
        .exam-table td { border: 1px solid #ddd; padding: 12px; }
        .exam-type { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; }
        .type-Midterm { background: #ffc107; color: #333; }
        .type-Final { background: #dc3545; color: white; }
        .type-Quiz { background: #17a2b8; color: white; }
        .type-Practical { background: #6f42c1; color: white; }
        .countdown { font-size: 12px; color: #dc3545; font-weight: bold; }
        .print-btn { background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; margin-bottom: 15px; }
        @media print { .sidebar, .top-header, .info-banner, .print-btn, nav, aside { display: none; } .content-wrapper { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">Exam Timetable</div>
            <div class="exam-container">
                <div style="text-align: right;"><button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print</button></div>
                <?php if(empty($exams)): ?><div style="text-align:center; padding:50px; color:#999;">No exams scheduled yet.</div>
                <?php else: ?>
                <table class="exam-table"><thead><tr><th>Course</th><th>Exam Date</th><th>Time</th><th>Venue</th><th>Type</th><th>Countdown</th></tr></thead>
                <tbody><?php foreach($exams as $exam): $diff = strtotime($exam['exam_date']) - time(); $days = floor($diff / (60*60*24)); ?>
                <tr><td><strong><?php echo $exam['course_name']; ?></strong></td><td><?php echo date('d M, Y', strtotime($exam['exam_date'])); ?></td><td><?php echo date('h:i A', strtotime($exam['start_time'])); ?> - <?php echo date('h:i A', strtotime($exam['end_time'])); ?></td><td><i class="fas fa-map-marker-alt"></i> <?php echo $exam['venue']; ?></td><td><span class="exam-type type-<?php echo $exam['exam_type']; ?>"><?php echo $exam['exam_type']; ?></span></td>
                <td><?php if($diff < 0) echo '<span style="color:#28a745;">Completed</span>'; elseif($days == 0) echo '<span class="countdown">Today!</span>'; else echo '<span class="countdown">'.$days.' days left</span>'; ?></td></tr>
                <?php endforeach; ?></tbody></table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>