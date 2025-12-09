<?php
session_start();
include("../config/db.php"); // connect to DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "❌ Email already registered. Please log in instead.";
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            // Optional: Auto-login after registration
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['role']    = $role;
            $_SESSION['name']    = $name;

            // Redirect to dashboard
            header("Location: /terapia-1.0.0/dashboard.php");
            exit();
        } else {
            echo "❌ Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $check->close();
}
?>