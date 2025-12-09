<?php
// Simple navigation that matches your site style
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 px-lg-5 py-3 py-lg-0">
    <a href="index.html" class="navbar-brand p-0">
        <h1 class="text-primary m-0"><i class="fas fa-star-of-life me-3"></i>Terapia</h1>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="fa fa-bars"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto py-0">
            <a href="home.html" class="nav-item nav-link">Home</a>
            <a href="about.html" class="nav-item nav-link">About</a>
            <a href="service.html" class="nav-item nav-link">Services</a>
            <a href="appointment.html" class="nav-item nav-link">Appointment</a>
            <a href="profile.php" class="nav-item nav-link active">My Profile</a>
            <a href="contact.html" class="nav-item nav-link">Contact</a>
        </div>
        <a href="backend/logout.php" class="btn btn-primary rounded-pill text-white py-2 px-4">Logout</a>
    </div>
</nav>