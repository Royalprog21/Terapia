<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Check if user is a doctor (case-insensitive check)
if (strtolower($_SESSION['role']) !== 'doctor') {
    header("Location: dashboard.php");
    exit();
}

// Get all appointments with patient information
$sql = "SELECT a.*, u.name as patient_name, u.email as patient_email 
        FROM appointments a 
        LEFT JOIN users u ON a.patient_id = u.id 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_appointments = count($appointments);
$pending_count = count(array_filter($appointments, fn($a) => $a['status'] === 'Pending'));
$completed_count = count(array_filter($appointments, fn($a) => $a['status'] === 'Completed'));
$today_count = count(array_filter($appointments, fn($a) => $a['appointment_date'] === date('Y-m-d')));

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Appointments - Doctor Dashboard</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet"/>
<style>
body {
    font-family: 'Open Sans', sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding-top: 70px;
}
.navbar { position: fixed; top: 0; width: 100%; z-index: 1000; }

/* HEADER */
.doctor-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 50px 20px 30px 20px;
    text-align: center;
}
.doctor-header h1 { font-size: 2rem; margin-bottom: 5px; }
.doctor-header p { font-size: 1rem; margin: 0; }

/* STATS CARDS */
.stats-container { margin: 20px 0 30px; }
.stat-box {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    transition: transform 0.3s;
}
.stat-box:hover { transform: translateY(-5px); }
.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 5px;
}
.stat-label { color: #666; font-size: 0.9rem; }

/* APPOINTMENTS TABLE */
.appointments-card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.table thead { background: #f8f9fa; }
.table td, .table th { font-size: 0.9rem; padding: 12px 8px; vertical-align: middle; }
.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    color: #fff;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
}
.status-Pending { background-color: #ff9800; }
.status-Completed { background-color: #4CAF50; }
.status-Confirmed { background-color: #2196F3; }
.status-Canceled { background-color: #f44336; }

.btn-back {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    color: white;
    padding: 12px 30px;
    font-size: 1rem;
    border-radius: 25px;
    transition: all 0.3s;
}
.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.patient-info { 
    font-size: 0.85rem;
    color: #666;
    margin-top: 3px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .doctor-header { padding: 40px 15px 20px 15px; }
    .doctor-header h1 { font-size: 1.5rem; }
    .stat-number { font-size: 2rem; }
    .table td, .table th { font-size: 0.8rem; padding: 8px 5px; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white px-4 px-lg-5 py-2 py-lg-0">
    <a href="index.html" class="navbar-brand p-0">
        <h1 class="text-primary m-0"><i class="fas fa-star-of-life me-2"></i>Terapia</h1>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="fa fa-bars"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto py-0">
            <a href="home.html" class="nav-item nav-link">Home</a>
            <a href="dashboard.php" class="nav-item nav-link">Dashboard</a>
            <a href="viewAppointment.php" class="nav-item nav-link active">Appointments</a>
            <a href="profile.php" class="nav-item nav-link">Profile</a>
        </div>
        <a href="backend/logout.php" class="btn btn-primary rounded-pill text-white py-1 px-3">Logout</a>
    </div>
</nav>

<!-- HEADER -->
<div class="doctor-header">
    <h1><i class="fas fa-calendar-check me-2"></i>My Appointments</h1>
    <p>Dr. <?= htmlspecialchars($_SESSION['name']); ?> - View and manage your scheduled appointments</p>
</div>

<!-- STATISTICS -->
<div class="container stats-container">
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number"><?= $total_appointments ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number"><?= $today_count ?></div>
                <div class="stat-label">Today</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number"><?= $pending_count ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-box">
                <div class="stat-number"><?= $completed_count ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>
</div>

<!-- APPOINTMENTS TABLE -->
<div class="container pb-5">
    <div class="appointments-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="fas fa-list me-2"></i>All Appointments</h4>
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <?php if (empty($appointments)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h5>No Appointments Found</h5>
                <p class="text-muted">You don't have any appointments scheduled yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Comments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($appointments as $index => $appt): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= date('M j, Y', strtotime($appt['appointment_date'])) ?></strong>
                                <?php if ($appt['appointment_date'] === date('Y-m-d')): ?>
                                    <span class="badge bg-info text-white ms-1">Today</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('g:i A', strtotime($appt['appointment_time'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($appt['patient_name']) ?></strong>
                                <div class="patient-info">
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($appt['patient_email']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($appt['department']) ?></td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($appt['status']) ?>">
                                    <?= htmlspecialchars($appt['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($appt['comments'])): ?>
                                    <span title="<?= htmlspecialchars($appt['comments']) ?>" data-bs-toggle="tooltip">
                                        <?= htmlspecialchars(substr($appt['comments'], 0, 30)) ?>
                                        <?= strlen($appt['comments']) > 30 ? '...' : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#detailsModal<?= $appt['id'] ?>"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal for each appointment -->
                        <div class="modal fade" id="detailsModal<?= $appt['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">
                                            <i class="fas fa-info-circle me-2"></i>Appointment Details
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Reference ID:</strong> APPT-<?= str_pad($appt['id'], 5, '0', STR_PAD_LEFT) ?></p>
                                        <hr>
                                        <p><strong>Patient Name:</strong> <?= htmlspecialchars($appt['patient_name']) ?></p>
                                        <p><strong>Patient Email:</strong> <?= htmlspecialchars($appt['patient_email']) ?></p>
                                        <p><strong>Date:</strong> <?= date('l, F j, Y', strtotime($appt['appointment_date'])) ?></p>
                                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($appt['appointment_time'])) ?></p>
                                        <p><strong>Department:</strong> <?= htmlspecialchars($appt['department']) ?></p>
                                        <p><strong>Status:</strong> 
                                            <span class="status-badge status-<?= htmlspecialchars($appt['status']) ?>">
                                                <?= htmlspecialchars($appt['status']) ?>
                                            </span>
                                        </p>
                                        <p><strong>Comments:</strong><br>
                                            <?= !empty($appt['comments']) ? nl2br(htmlspecialchars($appt['comments'])) : '<em class="text-muted">No comments</em>' ?>
                                        </p>
                                        <p><strong>Booked on:</strong> <?= date('M j, Y g:i A', strtotime($appt['created_at'])) ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
<script>
// Initialize Bootstrap tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>
</body>
</html>
