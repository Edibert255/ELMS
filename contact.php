<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact - ELMS</title>
  <link rel="stylesheet" href="dashboard_ELMS_style.css">
</head>

<body>

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
  <div class="slide active" style="background-image:url('https://images.unsplash.com/photo-1501504905252-473c47e087f8');"></div>
  <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1523240795612-9a054b0db644');"></div>

  <div class="overlay"></div>

  <div class="content">
    <h1>Contact Us</h1>
    <p>Get in touch with us</p>
  </div>
</div>

<!-- CONTACT -->
<section class="courses">
  <h2>Contact Information</h2>
  <p>Email: elms@gmail.com</p>
  <p>Phone: +255 700 000 000</p>
</section>

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