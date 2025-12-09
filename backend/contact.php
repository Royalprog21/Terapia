<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration - UPDATE THESE WITH YOUR ACTUAL DATABASE CREDENTIALS
$host = 'localhost';
$dbname = 'terapia'; // Your database name
$username = 'root';   // Your database username
$password = '';       // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }
    
    $name = filter_var($input['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $message = filter_var($input['message'] ?? '', FILTER_SANITIZE_STRING);
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
    
    try {
        // Check if messages table exists, create if not
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'messages'")->rowCount();
        if ($tableCheck === 0) {
            // Create messages table
            $createTableSQL = "CREATE TABLE messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_read TINYINT DEFAULT 0
            )";
            $pdo->exec($createTableSQL);
        }
        
        // Save to database
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);
        
        // Send email notification (optional - configure with your SMTP settings)
        $to = "info@terapia.com";
        $email_subject = "New Contact Form Submission from $name";
        $email_body = "
            You have received a new message from your website contact form.\n\n
            Name: $name\n
            Email: $email\n\n
            Message:\n$message\n
        ";
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        // Uncomment to enable email sending (requires proper SMTP configuration)
        // mail($to, $email_subject, $email_body, $headers);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Message sent successfully! We will get back to you soon.'
        ]);
        
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save message. Please try again later.'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>