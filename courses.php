<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Courses - ELMS</title>
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
  <div class="slide active" style="background-image:url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f');"></div>
  <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1524178232363-1fb2b075b655');"></div>

  <div class="overlay"></div>

  <div class="content">
    <h1>Our Courses</h1>
    <p>Choose your career path</p>
  </div>
</div>

<!-- COURSES -->
<section class="courses">
  <h2>Available Courses</h2>

  <div class="course-grid">
    <div class="card"><h3>IT</h3><button>View</button></div>
    <div class="card"><h3>Accountancy</h3><button>View</button></div>
    <div class="card"><h3>Marketing</h3><button>View</button></div>
    <div class="card"><h3>HR</h3><button>View</button></div>
    <div class="card"><h3>Business Administration</h3><button>View</button></div>
    <div class="card"><h3>Tax</h3><button>View</button></div>
    <div class="card"><h3>Finance</h3><button>View</button></div>
  </div>
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