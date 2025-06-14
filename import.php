<?php
require_once 'auth.php';
require_once 'config.php';
include_once 'import_config.php';

// Handle update of Google Sheet link
if (isset($_POST['update_link']) && !empty($_POST['sheet_url'])) {
    $new_url = trim($_POST['sheet_url']);
    if (strpos($new_url, '/edit') !== false) {
        $new_url = preg_replace('/\/edit.*/', '/export?format=csv', $new_url);
    }
    $safe_url = str_replace(["\"", "\n", "\r"], ["\\\"", "", ""], $new_url);
    $config_code = "<?php\n// Config for Google Sheet link\n$google_sheet_url = \"$safe_url\";\n";
    file_put_contents('import_config.php', $config_code);
    $google_sheet_url = $new_url;
    $msg = "Google Sheet link updated.";
}

// Fetch CSV data
$csv_data = [];
$csv_error = '';
if ($google_sheet_url) {
    $csv = @file_get_contents($google_sheet_url);
    if ($csv !== false) {
        $rows = array_map('str_getcsv', explode("\n", $csv));
        $header = array_map('trim', $rows[0]);
        for ($i = 1; $i < count($rows); $i++) {
            if (count($rows[$i]) < 2) continue;
            if (count($rows[$i]) !== count($header)) continue;
            $csv_data[] = array_combine($header, $rows[$i]);
        }
    } else {
        $csv_error = "Could not fetch data from Google Sheet. Please check the link.";
    }
}
// Handle import
if (isset($_POST['import_contacts']) && !empty($csv_data)) {
    $imported = 0;
    foreach ($csv_data as $contact) {
        $name = $conn->real_escape_string($contact['name'] ?? '');
        $phone = $conn->real_escape_string($contact['phone'] ?? '');
        $email = $conn->real_escape_string($contact['email'] ?? '');
        if ($name && $phone) {
            $conn->query("INSERT INTO contacts (name, phone, email, created_by) VALUES ('{$name}', '{$phone}', '{$email}', '" . intval($_SESSION['user_id']) . "')");
            $imported++;
        }
    }
    $msg = "$imported contacts imported.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Contacts from Google Sheets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e9fbe5; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,128,0,0.06); padding: 32px; }
        h2 { color: #218838; }
        .form-label { color: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h2>Import Contacts from Google Sheets</h2>
    <?php if (!empty($msg)): ?>
        <div class="alert alert-success"><?php echo $msg; ?></div>
    <?php endif; ?>
    <form method="post" class="mb-4">
        <label class="form-label">Google Sheet Link</label>
        <div class="input-group mb-2">
            <input type="url" name="sheet_url" class="form-control" value="<?php echo htmlspecialchars(str_replace('/export?format=csv','/edit?usp=sharing',$google_sheet_url)); ?>" required>
            <button type="submit" name="update_link" class="btn btn-success">Update Link</button>
        </div>
        <div class="form-text">Paste your Google Sheet link here (edit or view link).</div>
    </form>
    <hr>
    <h5>Preview Data</h5>
    <?php if ($csv_error): ?>
        <div class="alert alert-danger"><?php echo $csv_error; ?></div>
    <?php elseif (!empty($csv_data)): ?>
        <form method="post">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <?php foreach ($header as $col): ?>
                            <th><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($csv_data as $row): ?>
                        <tr>
                            <?php foreach ($header as $col): ?>
                                <td><?php echo htmlspecialchars($row[$col] ?? ''); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="import_contacts" class="btn btn-success">Import All Contacts</button>
        </form>
    <?php else: ?>
        <div class="alert alert-info">No data found or sheet is empty.</div>
    <?php endif; ?>
    <hr>
    <h5 class="mt-5">Add New Contact</h5>
    <?php
    // Handle add new contact
    if (isset($_POST['add_contact'])) {
        $new_name = trim($_POST['new_name'] ?? '');
        $new_phone = trim($_POST['new_phone'] ?? '');
        $new_email = trim($_POST['new_email'] ?? '');
        if ($new_name && $new_phone) {
            $stmt = $conn->prepare("INSERT INTO contacts (name, phone, email, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $new_name, $new_phone, $new_email, $_SESSION['user_id']);
            if ($stmt->execute()) {
                echo '<div class="alert alert-success">Contact added.</div>';
            } else {
                echo '<div class="alert alert-danger">Error adding contact.</div>';
            }
            $stmt->close();
        } else {
            echo '<div class="alert alert-danger">Name and phone are required.</div>';
        }
    }
    ?>
    <form class="row g-3 mb-4" method="post">
        <div class="col-md-4">
            <input type="text" name="new_name" class="form-control" placeholder="Name" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="new_phone" class="form-control" placeholder="Phone" required>
        </div>
        <div class="col-md-3">
            <input type="email" name="new_email" class="form-control" placeholder="Email">
        </div>
        <div class="col-md-2">
            <button type="submit" name="add_contact" class="btn btn-success w-100">Add Contact</button>
        </div>
    </form>
    <h5 class="mt-5">All Contacts</h5>
    <?php
    // Handle delete action
    if (isset($_POST['delete_contact']) && isset($_POST['contact_id'])) {
        $id = intval($_POST['contact_id']);
        $conn->query("DELETE FROM contacts WHERE id = $id");
        echo '<div class="alert alert-success">Contact deleted.</div>';
    }
    // Handle update action
    if (isset($_POST['edit_contact']) && isset($_POST['contact_id'])) {
        $id = intval($_POST['contact_id']);
        $name = $conn->real_escape_string($_POST['edit_name'] ?? '');
        $phone = $conn->real_escape_string($_POST['edit_phone'] ?? '');
        $email = $conn->real_escape_string($_POST['edit_email'] ?? '');
        $conn->query("UPDATE contacts SET name='$name', phone='$phone', email='$email' WHERE id=$id");
        echo '<div class="alert alert-success">Contact updated.</div>';
    }
    // Search/filter
    $filter = '';
    if (!empty($_GET['search'])) {
        $q = $conn->real_escape_string($_GET['search']);
        $filter = "WHERE name LIKE '%$q%' OR phone LIKE '%$q%' OR email LIKE '%$q%'";
    }
    $contacts = $conn->query("SELECT * FROM contacts $filter ORDER BY id DESC");
    ?>
    <form class="mb-3" method="get">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by name, phone, or email" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button class="btn btn-outline-success" type="submit">Search</button>
        </div>
    </form>
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($c = $contacts->fetch_assoc()): ?>
            <tr>
                <form method="post">
                <input type="hidden" name="contact_id" value="<?php echo $c['id']; ?>">
                <?php if (isset($_POST['edit']) && $_POST['contact_id'] == $c['id']): ?>
                    <td><input type="text" name="edit_name" value="<?php echo htmlspecialchars($c['name']); ?>" class="form-control" required></td>
                    <td><input type="text" name="edit_phone" value="<?php echo htmlspecialchars($c['phone']); ?>" class="form-control" required></td>
                    <td><input type="email" name="edit_email" value="<?php echo htmlspecialchars($c['email']); ?>" class="form-control"></td>
                    <td>
                        <button type="submit" name="edit_contact" class="btn btn-success btn-sm">Save</button>
                        <a href="import.php" class="btn btn-secondary btn-sm">Cancel</a>
                    </td>
                <?php else: ?>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo htmlspecialchars($c['phone']); ?></td>
                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                    <td>
                        <button type="submit" name="edit" class="btn btn-outline-primary btn-sm">Edit</button>
                        <button type="submit" name="delete_contact" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this contact?')">Delete</button>
                    </td>
                <?php endif; ?>
                </form>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
