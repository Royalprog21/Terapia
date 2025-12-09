<?php
session_start();
include("../config/db.php"); // connect to DB

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Store user session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['name']    = $user['name'];

            // Redirect to dashboard
            header("Location: /terapia-1.0.0/home.html");
            exit();
        } else {
            echo "❌ Invalid password.";
        }
    } else {
        echo "❌ No account found with this email.";
    }

    $stmt->close();
}
?>