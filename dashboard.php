<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==================== QUICK STATS ====================
$total_appts = $conn->query("SELECT COUNT(*) FROM appointments WHERE patient_id = $user_id")->fetch_row()[0];
$pending_appts = $conn->query("SELECT COUNT(*) FROM appointments WHERE patient_id = $user_id AND status='Pending'")->fetch_row()[0];
$completed_appts = $conn->query("SELECT COUNT(*) FROM appointments WHERE patient_id = $user_id AND status='Completed'")->fetch_row()[0];
$reviews_avg = $conn->query("SELECT AVG(rating) FROM testimonials")->fetch_row()[0];
$reviews_avg = $reviews_avg ? number_format($reviews_avg,1) : 0;

// ==================== RECENT APPOINTMENTS ====================
$recent_appointments = [];
$sql = "SELECT id, appointment_date, appointment_time, doctor_id, department, status, comments
        FROM appointments
        WHERE patient_id = ?
        ORDER BY appointment_date DESC, appointment_time DESC
        LIMIT 5";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result){
        while($row = $result->fetch_assoc()){
            $recent_appointments[] = $row;
        }
    }
    $stmt->close();
}

// ==================== RECENT TESTIMONIALS ====================
$recent_testimonials = [];
$sql2 = "SELECT name, rating, testimonial, treatment_type, doctor_id, status, created_at
         FROM testimonials
         ORDER BY created_at DESC
         LIMIT 5";
$result2 = $conn->query($sql2);
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        $recent_testimonials[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard - Terapia</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet"/>
<style>
body {
    font-family: 'Open Sans', sans-serif;
    background:#f4f6f8;
    margin:0;
    padding-top: 70px;
}
.navbar { position: fixed; top:0; width:100%; z-index: 1000; }

/* SMALL DASHBOARD HEADER */
.dashboard-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 60px 20px 30px 20px;
    text-align: center;
    position: relative;
}
.dashboard-header h1 { font-size:2rem; margin-bottom:5px; }
.dashboard-header p { font-size:1rem; margin:0; }

/* QUICK STATS */
.quick-stats { margin: 20px 0 30px; }
.stat-card { background:#fff; border-radius:15px; padding:20px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.05); height:100%; transition:0.3s; }
.stat-card:hover { transform: translateY(-3px); }
.stat-number { font-size:2rem; font-weight:700; background: linear-gradient(135deg,#667eea,#764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom:5px; }

/* NAV CARDS */
.nav-card { background:#fff; border-radius:12px; padding:15px; box-shadow:0 4px 10px rgba(0,0,0,0.05); height:100%; margin-bottom:15px; }
.nav-card h4 { margin:10px 0; font-size:1.1rem; }
.nav-icon { font-size:30px; margin-bottom:5px; }
.nav-icon.icon-primary { color:#667eea; }
.nav-icon.icon-success { color:#28a745; }
.nav-icon.icon-warning { color:#ffc107; }
.nav-icon.icon-info { color:#17a2b8; }

/* RECENT APPOINTMENTS TABLE */
.table thead { background:#f1f3f6; font-size:0.9rem; }
.table td, .table th { font-size:0.85rem; padding:8px; }
.status { padding:3px 7px; border-radius:5px; color:#fff; font-weight:bold; font-size:0.75rem; }
.status-Pending { background-color: #ff9800; }
.status-Completed { background-color: #4CAF50; }
.status-Canceled { background-color: #f44336; }

/* TESTIMONIALS */
.testimonial-item { background:#fff; padding:10px; border-radius:6px; margin-bottom:10px; box-shadow:0 1px 6px rgba(0,0,0,0.05); font-size:0.9rem; }
.testimonial-item strong { color:#667eea; font-size:0.95rem; }
.testimonial-item em { color:#777; font-style:normal; font-size:0.85rem; }

/* QUICK ACTIONS */
.btn { font-size:0.85rem; padding:8px 10px; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .dashboard-header { padding:50px 15px 20px 15px; }
    .dashboard-header h1 { font-size:1.5rem; }
    .dashboard-header p { font-size:0.9rem; }
    .stat-number { font-size:1.5rem; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 px-lg-5 py-2 py-lg-0">
    <a href="index.html" class="navbar-brand p-0"><h1 class="text-primary m-0"><i class="fas fa-star-of-life me-2"></i>Terapia</h1></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"><span class="fa fa-bars"></span></button>
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto py-0">
            <a href="home.html" class="nav-item nav-link">Home</a>
            <a href="about.html" class="nav-item nav-link">About</a>
            <a href="service.html" class="nav-item nav-link">Services</a>
            <a href="appointment.html" class="nav-item nav-link">Appointment</a>
            <?php if (strtolower($_SESSION['role']) === 'doctor'): ?>
                <a href="viewAppointment.php" class="nav-item nav-link">View Appointments</a>
            <?php endif; ?>
            <a href="dashboard.php" class="nav-item nav-link active">Dashboard</a>
            <a href="contact.html" class="nav-item nav-link">Contact</a>
        </div>
        <a href="backend/logout.php" class="btn btn-primary rounded-pill text-white py-1 px-3">Logout</a>
    </div>
</nav>

<!-- DASHBOARD HEADER -->
<div class="dashboard-header">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['name']); ?>!</h1>
    <p>Manage your appointments and profile information</p>
</div>

<!-- QUICK STATS -->
<div class="container quick-stats">
    <div class="row g-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?= $total_appts ?></div><p>Total Appointments</p></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?= $pending_appts ?></div><p>Pending</p></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?= $completed_appts ?></div><p>Completed</p></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-number"><?= $reviews_avg ?></div><p>Avg Reviews</p></div></div>
    </div>
</div>

<div class="container py-3">

    <!-- NAV CARDS -->
    <div class="row g-3">
        <div class="col-md-6"><div class="nav-card text-center"><div class="nav-icon icon-primary"><i class="fas fa-calendar-check"></i></div><h4>Appointments</h4><p>Manage your sessions</p>
            <div class="row g-2 mt-2">
                <div class="col-6"><a href="appointment.html" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>New</a></div>
                <div class="col-6"><a href="profile.php" class="btn btn-outline-primary w-100"><i class="fas fa-list me-1"></i>View All</a></div>
            </div>
        </div></div>

        <div class="col-md-6"><div class="nav-card text-center"><div class="nav-icon icon-success"><i class="fas fa-user"></i></div><h4>My Profile</h4><p>Update your information</p>
            <div class="row g-2 mt-2">
                <div class="col-6"><a href="profile.php" class="btn btn-success w-100"><i class="fas fa-eye me-1"></i>View</a></div>
                <div class="col-6"><button class="btn btn-outline-success w-100" disabled><i class="fas fa-edit me-1"></i>Edit</button></div>
            </div>
        </div></div>
    </div>

    <!-- RECENT APPOINTMENTS -->
    <div class="card mt-3 p-3">
        <h5>Recent Appointments</h5>
        <?php if(!empty($recent_appointments)): ?>
        <table class="table table-striped mt-2 mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Doctor ID</th>
                    <th>Dept</th>
                    <th>Status</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($recent_appointments as $appt): ?>
                <tr>
                    <td><?= htmlspecialchars($appt['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($appt['appointment_time']) ?></td>
                    <td><?= htmlspecialchars($appt['doctor_id']) ?></td>
                    <td><?= htmlspecialchars($appt['department']) ?></td>
                    <td><span class="status status-<?= htmlspecialchars($appt['status']) ?>"><?= htmlspecialchars($appt['status']) ?></span></td>
                    <td><?= htmlspecialchars($appt['comments']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p>No recent appointments.</p><?php endif; ?>
    </div>

    <!-- RECENT TESTIMONIALS -->
    <div class="card mt-3 p-3">
        <h5>Recent Testimonials</h5>
        <?php if(!empty($recent_testimonials)): ?>
            <?php foreach($recent_testimonials as $test): ?>
            <div class="testimonial-item">
                <strong><?= htmlspecialchars($test['name']) ?></strong> (<?= htmlspecialchars($test['rating']) ?>/5)<br>
                <em><?= htmlspecialchars($test['treatment_type']) ?></em><br>
                <?= nl2br(htmlspecialchars($test['testimonial'])) ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?><p>No testimonials yet.</p><?php endif; ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card p-3">
                <h5>Quick Actions</h5>
                <div class="row g-2 mt-2">
                    <div class="col-md-3 col-6"><a href="appointment.html" class="btn btn-outline-primary w-100"><i class="fas fa-calendar-plus me-1"></i>New Appointment</a></div>
                    <div class="col-md-3 col-6"><a href="profile.php" class="btn btn-outline-success w-100"><i class="fas fa-history me-1"></i>History</a></div>
                    <div class="col-md-3 col-6"><a href="testimonial.html" class="btn btn-outline-warning w-100"><i class="fas fa-comment-medical me-1"></i>Write Review</a></div>
                    <div class="col-md-3 col-6"><a href="backend/logout.php" class="btn btn-outline-danger w-100"><i class="fas fa-sign-out-alt me-1"></i>Logout</a></div>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
