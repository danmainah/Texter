<?php
require_once 'auth.php';
// Access user session info
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Texter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e9fbe5; }
        .navbar { background: #b2e2b2; }
        .nav-link.active, .nav-tabs .nav-link.active { background: #28a745 !important; color: #fff !important; }
        .nav-link { color: #218838 !important; }
        .nav-link:hover { color: #145c1a !important; }
        .tab-content { background: #fff; border-radius: 0 0 8px 8px; box-shadow: 0 2px 8px rgba(0,128,0,0.06); padding: 32px; min-height: 300px; }
        .user-info { color: #218838; }
        .logout-link { color: #d00; margin-left: 16px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#" style="color:#218838;font-weight:bold;">Texter</a>
    <div class="d-flex align-items-center ms-auto">
      <span class="user-info me-3">Welcome, <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)</span>
      <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>
<div class="container">
    <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" type="button" role="tab">Contacts</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages" type="button" role="tab">Messages</button>
      </li>
      <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">User Management</button>
      </li>
      <?php endif; ?>
    </ul>
    <div class="tab-content" id="dashboardTabsContent">
      <div class="tab-pane fade show active" id="contacts" role="tabpanel">
        <h3>Contacts</h3>
        <p>Manage your contacts here. (Coming soon)</p>
      </div>
      <div class="tab-pane fade" id="messages" role="tabpanel">
        <h3>Messages</h3>
        <p>View, send, and schedule messages. (Coming soon)</p>
      </div>
      <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
      <div class="tab-pane fade" id="users" role="tabpanel">
        <h3>User Management</h3>
        <p>Manage users and roles. (Coming soon)</p>
      </div>
      <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
