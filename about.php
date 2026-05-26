<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About - ELMS</title>
  <link rel="stylesheet" href="dashboard_ELMS_style.css">
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">
  <div class="logo">
    <img src="images/ELMS-logo.png">
  </div>

  <nav>
    <a href="dashboard_ELMS.php">Home</a>
    <a href="courses.php">Courses</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
  </nav>

  <button class="login-btn">Login</button>
</header>

<!-- HERO -->
<div class="hero">
  <div class="slide active" style="background-image:url('https://images.unsplash.com/photo-1523240795612-9a054b0db644');"></div>
  <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1501504905252-473c47e087f8');"></div>

  <div class="overlay"></div>

  <div class="content">
    <h1>About ELMS</h1>
    <p>Learn more about our platform</p>
  </div>
</div>

<!-- ABOUT CONTENT -->
<section class="courses">
  <h2>Who We Are</h2>
  <p>
    ELMS is an advanced E-Learning Management System designed for students and lecturers 
    to access education anytime, anywhere.
  </p>
</section>

<!-- SCRIPT -->
<script>
let slides = document.querySelectorAll(".slide");
let index = 0;

setInterval(() => {
  slides.forEach(s => s.classList.remove("active"));
  slides[index].classList.add("active");
  index = (index + 1) % slides.length;
}, 3000);
</script>

</body>
</html>