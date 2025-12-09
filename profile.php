<?php
session_start();
include("config/db.php");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user appointments
$sql = "SELECT a.*, u.name as doctor_name 
        FROM appointments a 
        LEFT JOIN users u ON a.doctor_id = u.id 
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// Get user info
$user_sql = "SELECT name, email, created_at FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Terapia</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
        .appointment-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
        }
        .status-pending { border-left-color: #ffc107; }
        .status-confirmed { border-left-color: #28a745; }
        .status-completed { border-left-color: #6c757d; }
        .status-cancelled { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <!-- Navigation (same as your other pages) -->
    <?php include('navigation.php'); ?>

    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4">Welcome, <?php echo htmlspecialchars($user_info['name']); ?>!</h1>
                    <p class="lead mb-0">Manage your appointments and profile information</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="appointment.html" class="btn btn-light btn-lg">
                        <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x text-white"></i>
                            </div>
                        </div>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_info['name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
                        <p><strong>Member since:</strong> <?php echo date('F Y', strtotime($user_info['created_at'])); ?></p>
                        <p><strong>Total Appointments:</strong> <?php echo count($appointments); ?></p>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="edit_profile.html" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </a>
                            <a href="testimonial.html" class="btn btn-outline-success">
                                <i class="fas fa-star me-2"></i>Write Testimonial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Appointments Section -->
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>My Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($appointments)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No Appointments Yet</h5>
                                <p class="text-muted">You haven't booked any appointments yet.</p>
                                <a href="appointment.html" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus me-2"></i>Book Your First Appointment
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Doctor</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($appointments as $appt): 
                                            $status_class = 'status-' . $appt['status'];
                                            $status_badge = [
                                                'pending' => 'warning',
                                                'confirmed' => 'success', 
                                                'completed' => 'secondary',
                                                'cancelled' => 'danger'
                                            ][$appt['status']];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></strong><br>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appt['department']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_badge; ?>">
                                                    <?php echo ucfirst($appt['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary view-details" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#appointmentModal"
                                                        data-appointment='<?php echo json_encode($appt); ?>'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($appt['status'] == 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-danger cancel-appointment" 
                                                            data-id="<?php echo $appt['id']; ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div class="modal fade" id="appointmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="appointmentDetails">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View appointment details
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const appointment = JSON.parse(this.getAttribute('data-appointment'));
                const modalBody = document.getElementById('appointmentDetails');
                
                modalBody.innerHTML = `
                    <p><strong>Reference:</strong> APPT${appointment.id.toString().padStart(4, '0')}</p>
                    <p><strong>Date:</strong> ${new Date(appointment.appointment_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    <p><strong>Time:</strong> ${new Date('1970-01-01T' + appointment.appointment_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</p>
                    <p><strong>Doctor:</strong> ${appointment.doctor_name}</p>
                    <p><strong>Department:</strong> ${appointment.department}</p>
                    <p><strong>Status:</strong> <span class="badge bg-${appointment.status === 'pending' ? 'warning' : appointment.status === 'confirmed' ? 'success' : 'secondary'}">${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</span></p>
                    ${appointment.comments ? `<p><strong>Notes:</strong> ${appointment.comments}</p>` : ''}
                    <p><strong>Booked on:</strong> ${new Date(appointment.created_at).toLocaleDateString()}</p>
                `;
            });
        });

        // Cancel appointment
        document.querySelectorAll('.cancel-appointment').forEach(button => {
            button.addEventListener('click', function() {
                const appointmentId = this.getAttribute('data-id');
                
                if (confirm('Are you sure you want to cancel this appointment?')) {
                    fetch('backend/cancel_appointment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'appointment_id=' + appointmentId
                    })
                    .then(response => response.text())
                    .then(result => {
                        alert(result);
                        if (result.includes('successfully')) {
                            location.reload();
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
                }
            });
        });
    </script>
</body>
</html>