<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// FETCH STUDENT DATA
$student = [];
if ($role == 'student') {
    $sql_student = "SELECT s.*, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = '$user_id'";
    $result_student = mysqli_query($conn, $sql_student);
    if ($result_student && mysqli_num_rows($result_student) > 0) {
        $student = mysqli_fetch_assoc($result_student);
    }
}

// FETCH CLASS TIMETABLE
$timetable = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

foreach ($days as $day) {
    $sql = "SELECT * FROM class_timetable WHERE day_of_week = '$day' ORDER BY start_time ASC";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $timetable[$day][] = $row;
        }
    } else {
        $timetable[$day] = [];
    }
}

$time_slots = [
    '07:00:00' => '07:00 - 09:00',
    '09:00:00' => '09:00 - 11:00',
    '11:00:00' => '11:00 - 13:00',
    '13:00:00' => '13:00 - 15:00',
    '15:00:00' => '15:00 - 17:00'
];

$photo = 'default.png';
$first_name = isset($student['first_name']) ? $student['first_name'] : 'Student';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable - CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .timetable-container { background: white; border-radius: 12px; padding: 20px; margin-top: 20px; overflow-x: auto; }
        .timetable-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .timetable-table th { background: #0056b3; color: white; padding: 12px; text-align: center; }
        .timetable-table td { border: 1px solid #ddd; padding: 10px; vertical-align: top; background: #fafafa; }
        .time-column { background: #e9ecef; font-weight: bold; text-align: center; width: 100px; }
        .class-item { background: white; padding: 6px 8px; margin-bottom: 6px; border-radius: 4px; border-left: 3px solid #28a745; }
        .class-course { font-weight: bold; color: #0056b3; font-size: 13px; }
        .class-lecturer { font-size: 11px; color: #666; }
        .class-venue { font-size: 10px; color: #888; }
        .no-class { text-align: center; color: #bbb; padding: 10px; font-size: 12px; }
        .print-btn { background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; margin-bottom: 15px; }
        @media print { .sidebar, .top-header, .info-banner, .print-btn, nav, aside { display: none; } .content-wrapper { margin-left: 0; } }
    </style>
</head>
<body>
    <!-- Include your student sidebar here -->
    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | Today: <?php echo date("d M, Y"); ?></p></div></header>
        <main>
            <div class="panel-title">Class Timetable</div>
            <div class="timetable-container">
                <div style="text-align: right;"><button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print</button></div>
                <table class="timetable-table"><thead><tr><th>TIME</th><th>MONDAY</th><th>TUESDAY</th><th>WEDNESDAY</th><th>THURSDAY</th><th>FRIDAY</th><th>SATURDAY</th></tr></thead>
                <tbody><?php foreach($time_slots as $slot_time => $slot_label): ?>
                <tr><td class="time-column"><?php echo $slot_label; ?></td>
                <?php foreach($days as $day): ?>
                <td><?php $found=false; if(isset($timetable[$day])){foreach($timetable[$day] as $class){$class_start=strtotime($class['start_time']);$slot_start=strtotime($slot_time);if($class_start>=$slot_start && $class_start<$slot_start+(2*3600)){echo '<div class="class-item"><div class="class-course">'.$class['course_name'].'</div><div class="class-lecturer"><i class="fas fa-chalkboard-teacher"></i> '.$class['lecturer_name'].'</div><div class="class-venue"><i class="fas fa-map-marker-alt"></i> '.$class['venue'].'</div></div>';$found=true;}}}if(!$found)echo '<div class="no-class">—</div>';?></td>
                <?php endforeach; ?></tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>