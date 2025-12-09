<?php
require_once '../config/db.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name = trim($_POST['name'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $testimonial = trim($_POST['testimonial'] ?? '');
    $treatment_type = trim($_POST['treatment_type'] ?? '');
    $doctor_id = intval($_POST['doctor_id'] ?? 0);

    if(!$name || !$testimonial || $rating<=0){
        echo "❌ Please fill in all required fields and provide a rating";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO testimonials (name,rating,testimonial,treatment_type,doctor_id,status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sissi",$name,$rating,$testimonial,$treatment_type,$doctor_id);

    if($stmt->execute()){
        echo "✅ Testimonial submitted successfully!";
    }else{
        echo "❌ Error submitting testimonial: ".$stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
