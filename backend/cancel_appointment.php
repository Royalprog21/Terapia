<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Error: Please log in to cancel appointments";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($appointment_id === 0) {
        echo "Error: Invalid appointment ID";
        exit();
    }
    
    // Verify the appointment belongs to the user
    $check_sql = "SELECT id FROM appointments WHERE id = ? AND patient_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo "Error: Appointment not found or you don't have permission to cancel it";
        exit();
    }
    
    // Update appointment status to cancelled
    $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $appointment_id);
    
    if ($update_stmt->execute()) {
        echo "Appointment cancelled successfully";
    } else {
        echo "Error cancelling appointment: " . $update_stmt->error;
    }
    
    $update_stmt->close();
    $check_stmt->close();
    $conn->close();
} else {
    echo "Invalid request method";
}
?>