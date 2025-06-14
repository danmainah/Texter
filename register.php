<?php
// Admin registration utility (one-time use, then delete for security)
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name && $email && $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param('sss', $name, $email, $hashed);
        if ($stmt->execute()) {
            echo "Admin registered. Please delete this file now.";
        } else {
            echo "Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "All fields required.";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Admin</title>
</head>
<body>
<h2>Register Admin</h2>
<form method="post">
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>
<p><strong>Note:</strong> Delete this file after registering your admin for security.</p>
</body>
</html>
