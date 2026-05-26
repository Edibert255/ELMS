<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// pata student
$student = $conn->query("SELECT * FROM students WHERE user_id='$user_id'")->fetch_assoc();
$student_id = $student['id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>My Courses</title>
<link rel="stylesheet" href="students_dashboard_style.css">
</head>
<body>

<div class="container">

<aside class="sidebar">
<h2>ELMS</h2>
<ul>
<li><a href="student_dashboard.php">Dashboard</a></li>
<li class="active"><a href="my_courses.php">My Courses</a></li>
<li><a href="assignments.php">Assignments</a></li>
<li><a href="live_classes.php">Live Classes</a></li>
<li><a href="grades.php">Grades</a></li>
<li><a href="messages.php">Messages</a></li>
<li><a href="library.php">Library</a></li>
<li><a href="calendar.php">Calendar</a></li>
<li><a href="support.php">Help & Support</a></li>
</ul>
</aside>

<main class="main">
<h1>My Courses</h1>

<?php
$sql = "SELECT courses.course_name 
        FROM enrollments
        JOIN courses ON enrollments.course_id = courses.id
        WHERE enrollments.student_id='$student_id'";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()){
?>
<div class="card">
<h3><?php echo $row['course_name']; ?></h3>
<p>Ongoing course</p>
</div>
<?php } ?>

</main>

</div>

</body>
</html>