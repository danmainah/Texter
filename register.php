<?php
require_once 'config.php';
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name && $email && $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param('sss', $name, $email, $hashed);
        if ($stmt->execute()) {
            // Auto-login after registration
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'admin';
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "All fields required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #e9fbe5; }
        .register-box { max-width: 400px; margin: 80px auto; padding: 32px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,128,0,0.08); border: 1px solid #b2e2b2; }
        .register-box h2 { margin-bottom: 24px; color: #218838; }
        .register-box input { width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #b2e2b2; border-radius: 4px; }
        .register-box button { width: 100%; padding: 10px; background: #28a745; color: #fff; border: none; border-radius: 4px; font-size: 16px; }
        a { color: #218838; }
        a:hover { color: #145c1a; }
    </style>
</head>
<body>
<div class="register-box">
<h2>Register Admin</h2>
<?php if (isset($error) && $error): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
<form method="post">
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Register</button>
</form>
<p style="text-align:center; margin-top:16px;">
    <a href="login.php">Already have an account? Login</a>
</p>
</body>
</html>
