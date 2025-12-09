<?php
session_start();
include("../config/db.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Get form data
        $patient_id = $_SESSION['user_id'] ?? null;
        $first_name = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $appointment_date = trim($_POST['appointment_date'] ?? '');
        $appointment_time = trim($_POST['appointment_time'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $doctor_id = trim($_POST['doctor_id'] ?? '');
        $comments = trim($_POST['comments'] ?? '');
        $testimonial_id = null; // optional field

        // Required fields validation
        $required_fields = [
            'first_name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'gender' => 'Gender',
            'appointment_date' => 'Date',
            'appointment_time' => 'Time',
            'department' => 'Department',
            'doctor_id' => 'Doctor'
        ];

        $errors = [];
        foreach ($required_fields as $field => $label) {
            if (empty($$field)) {
                $errors[] = "$label is required";
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address");
        }

        // Date validation (cannot be in past)
        if ($appointment_date < date('Y-m-d')) {
            throw new Exception("Appointment date cannot be in the past");
        }

        // Doctor ID numeric check
        if (!is_numeric($doctor_id)) {
            throw new Exception("Invalid doctor selection");
        }

        // Check if slot already booked
        $check_sql = "SELECT id FROM appointments 
                      WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            throw new Exception("Selected time slot is already booked. Choose another.");
        }
        $stmt_check->close();

        // ✅ Insert appointment with corrected query
        $sql = "INSERT INTO appointments 
            (patient_id, doctor_id, appointment_date, appointment_time, status, testimonial_id, first_name, email, phone, gender, department, comments, created_at) 
            VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iississssss", 
            $patient_id, 
            $doctor_id, 
            $appointment_date, 
            $appointment_time, 
            $testimonial_id, 
            $first_name, 
            $email, 
            $phone, 
            $gender, 
            $department, 
            $comments
        );

        if ($stmt->execute()) {
            $appointment_id = $stmt->insert_id;

            // Get doctor name
            $doctor_name = "the doctor";
            $doctor_sql = "SELECT name FROM users WHERE id = ?";
            $stmt_doc = $conn->prepare($doctor_sql);
            $stmt_doc->bind_param("i", $doctor_id);
            $stmt_doc->execute();
            $res_doc = $stmt_doc->get_result();
            if ($res_doc->num_rows > 0) {
                $doctor_data = $res_doc->fetch_assoc();
                $doctor_name = $doctor_data['name'];
            }
            $stmt_doc->close();

            // Format date/time
            $formatted_date = date('F j, Y', strtotime($appointment_date));
            $formatted_time = date('g:i A', strtotime($appointment_time));
            $reference_number = "APPT" . str_pad($appointment_id, 4, "0", STR_PAD_LEFT);

            echo "✅ Appointment booked successfully!<br><br>
                  <strong>Appointment Details:</strong><br>
                  • <strong>Reference #:</strong> $reference_number<br>
                  • <strong>Date:</strong> $formatted_date<br>
                  • <strong>Time:</strong> $formatted_time<br>
                  • <strong>Doctor:</strong> $doctor_name<br>
                  • <strong>Department:</strong> $department<br>
                  • <strong>Patient:</strong> $first_name<br>";

            if (!empty($comments)) {
                echo "• <strong>Notes:</strong> " . htmlspecialchars($comments) . "<br>";
            }

            echo "<br><small>We will contact you at <strong>$phone</strong> to confirm your appointment. Keep your reference number for future communication.</small>";
        } else {
            throw new Exception("Failed to save appointment: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo "❌ " . $e->getMessage();
    }
} else {
    echo "❌ Invalid request method.";
}
?>
