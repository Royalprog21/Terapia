<?php
session_start();
include '../config/db.php'; // ensures $conn is defined

// Enable debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $patient_id = $_SESSION['user_id'] ?? null;
        $first_name = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $appointment_date = trim($_POST['appointment_date'] ?? '');
        $appointment_time = trim($_POST['appointment_time'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        $comments = trim($_POST['comments'] ?? '');
        $testimonial_id = null; // optional

        // Required fields validation
        $required = [
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
        foreach ($required as $field => $label) {
            if (empty($$field)) $errors[] = "$label is required";
        }
        if ($errors) throw new Exception(implode(", ", $errors));

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid email address");

        // Date validation
        if ($appointment_date < date('Y-m-d')) throw new Exception("Appointment date cannot be in the past");

        // Check if slot already booked
        $stmt_check = $conn->prepare("SELECT id FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status!='cancelled'");
        $stmt_check->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        if ($res_check->num_rows > 0) throw new Exception("Selected time slot is already booked. Choose another.");
        $stmt_check->close();

        // Insert appointment
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, testimonial_id, first_name, email, phone, gender, department, comments, created_at) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iississssss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $testimonial_id, $first_name, $email, $phone, $gender, $department, $comments);
        if (!$stmt->execute()) throw new Exception("Failed to save appointment: ".$stmt->error);

        $appointment_id = $stmt->insert_id;
        $stmt->close();

        // Get doctor name
        $doctor_name = "the doctor";
        $stmt_doc = $conn->prepare("SELECT name FROM users WHERE id=?");
        $stmt_doc->bind_param("i",$doctor_id);
        $stmt_doc->execute();
        $res_doc = $stmt_doc->get_result();
        if($res_doc->num_rows>0){
            $doctor_name = $res_doc->fetch_assoc()['name'];
        }
        $stmt_doc->close();

        // Format date/time
        $formatted_date = date('F j, Y', strtotime($appointment_date));
        $formatted_time = date('g:i A', strtotime($appointment_time));
        $reference_number = "APPT".str_pad($appointment_id,4,"0",STR_PAD_LEFT);

        // Success message
        echo "✅ Appointment booked successfully!<br><br>
              <strong>Appointment Details:</strong><br>
              • <strong>Reference #:</strong> $reference_number<br>
              • <strong>Date:</strong> $formatted_date<br>
              • <strong>Time:</strong> $formatted_time<br>
              • <strong>Doctor:</strong> $doctor_name<br>
              • <strong>Department:</strong> $department<br>
              • <strong>Patient:</strong> $first_name<br>";

        if($comments) echo "• <strong>Notes:</strong> ".htmlspecialchars($comments)."<br>";
        echo "<br><small>We will contact you at <strong>$phone</strong> to confirm your appointment. Keep your reference number for future communication.</small>";

        $conn->close();

    } catch (Exception $e) {
        echo "❌ ".$e->getMessage();
    }
} else {
    echo "❌ Invalid request method.";
}
?>
