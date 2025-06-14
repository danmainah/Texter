<?php
require_once 'auth.php';
require_once 'config.php';
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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Contacts</h3>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import Contacts</button>
        </div>
        <!-- Show alert message (from import or actions) -->
        <?php
        if (!empty($_SESSION['contacts_alert'])) {
            echo '<div class="alert alert-success">' . $_SESSION['contacts_alert'] . '</div>';
            unset($_SESSION['contacts_alert']);
        }
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
        <form class="mb-3" method="get">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by name, phone, or email" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </div>
        </form>
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $rownum = 1; while ($c = $contacts->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $rownum++; ?></td>
                    <form method="post">
                    <input type="hidden" name="contact_id" value="<?php echo $c['id']; ?>">
                    <?php if (isset($_POST['edit']) && $_POST['contact_id'] == $c['id']): ?>
                        <td><input type="text" name="edit_name" value="<?php echo htmlspecialchars($c['name']); ?>" class="form-control" required></td>
                        <td><input type="text" name="edit_phone" value="<?php echo htmlspecialchars($c['phone']); ?>" class="form-control" required></td>
                        <td><input type="text" name="edit_location" value="<?php echo htmlspecialchars($c['location']); ?>" class="form-control"></td>
                        <td>
                            <button type="submit" name="edit_contact" class="btn btn-success btn-sm">Save</button>
                            <a href="dashboard.php" class="btn btn-secondary btn-sm">Cancel</a>
                        </td>
                    <?php else: ?>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo htmlspecialchars($c['phone']); ?></td>
                        <td><?php echo htmlspecialchars($c['location']); ?></td>
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
        <!-- Import Contacts Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Contacts from Google Sheets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
<?php
require_once 'import_config.php';
// Handle update link and import within modal
if (isset($_POST['update_link']) && !empty($_POST['sheet_url'])) {
    $new_url = trim($_POST['sheet_url']);
    if (strpos($new_url, '/edit') !== false) {
        $new_url = preg_replace('/\/edit.*/', '/export?format=csv', $new_url);
    }
    $safe_url = str_replace(["\"", "\n", "\r"], ["\\\"", "", ""], $new_url);
    $config_code = "<?php\n// Config for Google Sheet link\n$google_sheet_url = \"$safe_url\";\n";
    file_put_contents('import_config.php', $config_code);
    $google_sheet_url = $new_url;
    $_SESSION['contacts_alert'] = "Google Sheet link updated.";
    echo "<script>window.location.href='dashboard.php#contacts';</script>";
    exit;
}
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
if (isset($_POST['import_contacts']) && !empty($csv_data)) {
    require_once 'config.php';
    $imported = 0;
    // Map columns flexibly
    foreach ($csv_data as $contact) {
        $name = '';
        $phone = '';
        $location = '';
        // Try common variations
        foreach ($contact as $key => $val) {
            $k = strtolower(trim($key));
            if (in_array($k, ['name', 'fullname'])) $name = $val;
            if (in_array($k, ['phone', 'contact', 'mobile', 'phonenumber'])) $phone = $val;
            if (in_array($k, ['location', 'place', 'address', 'area'])) $location = $val;
        }
        $name = $conn->real_escape_string($name);
        $phone = $conn->real_escape_string($phone);
        $location = $conn->real_escape_string($location);
        // Prevent duplicates (name + phone)
        $exists = $conn->query("SELECT id FROM contacts WHERE name='$name' AND phone='$phone' LIMIT 1");
        if ($name && $phone && $exists->num_rows == 0) {
            $conn->query("INSERT INTO contacts (name, phone, location, created_by) VALUES ('{$name}', '{$phone}', '{$location}', '" . intval($_SESSION['user_id']) . "')");
            $imported++;
        }
    }
    $_SESSION['contacts_alert'] = "$imported contacts imported.";
    echo "<script>window.location.href='dashboard.php#contacts';</script>";
    exit;
}
?>
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
                        <button type="submit" name="import_contacts" class="btn btn-primary">Import Using PHP</button>
                        <button type="button" class="btn btn-success" id="importBtn">Import Using AJAX</button>
                        <div id="importLoading" style="display:none;text-align:center;margin-top:10px;">
                          <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
                          <div id="importProgressText">Importing, please wait...</div>
                          <div class="progress mt-2" style="height:20px;">
                            <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%">0%</div>
                          </div>
                        </div>
                        <div id="importResult"></div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">No data found or sheet is empty.</div>
                <?php endif; ?>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="tab-pane fade" id="messages" role="tabpanel">
        <h3>Send Messages</h3>
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Select Contacts</h5>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <input type="text" id="contactSearch" class="form-control" placeholder="Search contacts...">
                </div>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                  <table class="table table-hover table-sm">
                    <thead>
                      <tr>
                        <th><input type="checkbox" id="selectAllContacts"> All</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                      </tr>
                    </thead>
                    <tbody id="contactsList">
                      <?php
                      $contacts_query = $conn->query("SELECT * FROM contacts WHERE created_by = " . intval($_SESSION['user_id']) . " ORDER BY name ASC");
                      while ($contact = $contacts_query->fetch_assoc()): 
                      ?>
                      <tr>
                        <td><input type="checkbox" class="contact-select" data-name="<?php echo htmlspecialchars($contact['name']); ?>" data-phone="<?php echo htmlspecialchars($contact['phone']); ?>" data-location="<?php echo htmlspecialchars($contact['location']); ?>"></td>
                        <td><?php echo htmlspecialchars($contact['name']); ?></td>
                        <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                        <td><?php echo htmlspecialchars($contact['location']); ?></td>
                      </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
                <div class="mt-2">
                  <span id="selectedCount" class="badge bg-success">0 selected</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card">
              <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Compose Message</h5>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Message Type</label>
                  <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="msgType" id="whatsappType" value="whatsapp" checked>
                    <label class="btn btn-outline-success" for="whatsappType">WhatsApp</label>
                    
                    <input type="radio" class="btn-check" name="msgType" id="smsType" value="sms">
                    <label class="btn btn-outline-success" for="smsType">SMS</label>
                    
                    <input type="radio" class="btn-check" name="msgType" id="emailType" value="email">
                    <label class="btn btn-outline-success" for="emailType">Email</label>
                  </div>
                </div>
                
                <div class="mb-3" id="emailSubjectField" style="display:none;">
                  <label class="form-label">Email Subject</label>
                  <input type="text" id="emailSubject" class="form-control" placeholder="Enter email subject...">
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Message Content</label>
                  <textarea id="messageContent" class="form-control" rows="5" placeholder="Type your message here..."></textarea>
                </div>
                
                <div class="d-grid gap-2">
                  <button id="generateLinks" class="btn btn-success">Generate Message Links</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mb-4" id="messageLinksCard" style="display:none;">
          <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">Message Links</h5>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i> Click on the links below to send messages to the selected contacts.
            </div>
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="messageLinks">
                  <!-- Message links will be generated here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Message sending functionality
  const contactSearch = document.getElementById('contactSearch');
  const selectAllContacts = document.getElementById('selectAllContacts');
  const contactCheckboxes = document.querySelectorAll('.contact-select');
  const selectedCountEl = document.getElementById('selectedCount');
  const generateLinksBtn = document.getElementById('generateLinks');
  const messageLinksCard = document.getElementById('messageLinksCard');
  const messageLinksTable = document.getElementById('messageLinks');
  const messageContent = document.getElementById('messageContent');
  const emailSubject = document.getElementById('emailSubject');
  const emailSubjectField = document.getElementById('emailSubjectField');
  const msgTypeRadios = document.querySelectorAll('input[name="msgType"]');
  
  // Show/hide email subject field based on message type
  msgTypeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      if (this.value === 'email') {
        emailSubjectField.style.display = 'block';
      } else {
        emailSubjectField.style.display = 'none';
      }
    });
  });
  
  // Search contacts
  if (contactSearch) {
    contactSearch.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = document.querySelectorAll('#contactsList tr');
      
      rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const phone = row.cells[2].textContent.toLowerCase();
        const location = row.cells[3].textContent.toLowerCase();
        
        if (name.includes(searchTerm) || phone.includes(searchTerm) || location.includes(searchTerm)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }
  
  // Select all contacts
  if (selectAllContacts) {
    selectAllContacts.addEventListener('change', function() {
      const isChecked = this.checked;
      const visibleCheckboxes = Array.from(contactCheckboxes).filter(cb => {
        return cb.closest('tr').style.display !== 'none';
      });
      
      visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
      });
      
      updateSelectedCount();
    });
  }
  
  // Individual contact selection
  contactCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
  });
  
  // Update selected count
  function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.contact-select:checked').length;
    if (selectedCountEl) {
      selectedCountEl.textContent = `${selectedCount} selected`;
    }
  }
  
  // Generate message links
  if (generateLinksBtn) {
    generateLinksBtn.addEventListener('click', function() {
      const selectedContacts = document.querySelectorAll('.contact-select:checked');
      const msgType = document.querySelector('input[name="msgType"]:checked').value;
      const msg = encodeURIComponent(messageContent.value.trim());
      
      if (selectedContacts.length === 0) {
        alert('Please select at least one contact.');
        return;
      }
      
      if (!msg) {
        alert('Please enter a message.');
        return;
      }
      
      // Clear previous links
      if (messageLinksTable) {
        messageLinksTable.innerHTML = '';
      }
      
      // Generate links for each selected contact
      selectedContacts.forEach(checkbox => {
        const name = checkbox.dataset.name;
        const phone = checkbox.dataset.phone;
        const location = checkbox.dataset.location;
        let linkHtml = '';
        
        if (msgType === 'whatsapp') {
          // Format phone for WhatsApp (remove non-digits except leading +)
          let formattedPhone = phone;
          if (formattedPhone.startsWith('+')) {
            formattedPhone = '+' + formattedPhone.substring(1).replace(/\D/g, '');
          } else {
            formattedPhone = formattedPhone.replace(/\D/g, '');
          }
          linkHtml = `<a href="https://wa.me/${formattedPhone}?text=${msg}" target="_blank" class="btn btn-success btn-sm">WhatsApp</a>`;
        } else if (msgType === 'sms') {
          linkHtml = `<a href="sms:${phone}?body=${msg}" class="btn btn-primary btn-sm">SMS</a>`;
        } else if (msgType === 'email') {
          const subject = encodeURIComponent(emailSubject.value.trim() || 'Message from Texter');
          linkHtml = `<a href="mailto:?subject=${subject}&body=${msg}" class="btn btn-info btn-sm">Email</a>`;
        }
        
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${name}</td>
          <td>${phone}</td>
          <td>${location}</td>
          <td>${linkHtml}</td>
        `;
        
        messageLinksTable.appendChild(row);
      });
      
      // Show the links card
      if (messageLinksCard) {
        messageLinksCard.style.display = 'block';
      }
    });
  }
  
  // Import contacts functionality
  const importBtn = document.getElementById('importBtn');
  if (importBtn) {
    importBtn.addEventListener('click', function() {
      importBtn.disabled = true;
      const loadingEl = document.getElementById('importLoading');
      const resultEl = document.getElementById('importResult');
      
      if (loadingEl) loadingEl.style.display = 'block';
      if (resultEl) resultEl.innerHTML = '';

      // Parse table rows into contact objects with robust header mapping
      const table = document.querySelector('#importModal table');
      if (!table) {
        if (resultEl) resultEl.innerHTML = '<div class="alert alert-danger">No preview table found.</div>';
        if (loadingEl) loadingEl.style.display = 'none';
        importBtn.disabled = false;
        return;
      }
      
      const headerMap = Array.from(table.querySelectorAll('thead th')).map(th => {
        let key = th.textContent.trim().toLowerCase();
        if (["full name", "fullname", "name"].includes(key)) return "name";
        if (["contact", "mobile", "phonenumber", "phone number", "phone"].includes(key)) return "phone";
        if (["location", "place", "address", "area"].includes(key)) return "location";
        return key;
      });
      
      const contacts = Array.from(table.querySelectorAll('tbody tr')).map(tr => {
        const obj = {};
        Array.from(tr.children).forEach((td, i) => {
          obj[headerMap[i]] = td.textContent.trim();
        });
        return { name: obj.name || '', phone: obj.phone || '', location: obj.location || '' };
      }).filter(c => c.name && c.phone); // Only valid rows

      if (!contacts.length) {
        if (resultEl) resultEl.innerHTML = '<div class="alert alert-danger">No valid contacts found to import. Make sure your sheet has Name and Phone columns and at least one row.</div>';
        if (loadingEl) loadingEl.style.display = 'none';
        importBtn.disabled = false;
        return;
      }

      // Batch upload
      const batchSize = 20;
      let imported = 0;
      let batch = 0;
      
      function uploadBatch() {
        const start = batch * batchSize;
        const end = Math.min(start + batchSize, contacts.length);
        const batchContacts = contacts.slice(start, end);
        
        if (batchContacts.length === 0) {
          if (loadingEl) loadingEl.style.display = 'none';
          importBtn.disabled = false;
          if (resultEl) resultEl.innerHTML = `<div class='alert alert-success'>All contacts imported. (${imported} new)</div>`;
          setTimeout(() => window.location.reload(), 1200);
          return;
        }
        
        const progressText = document.getElementById('importProgressText');
        if (progressText) progressText.textContent = `Importing batch ${batch+1} of ${Math.ceil(contacts.length/batchSize)}...`;
        
        console.log('Sending batch:', JSON.stringify(batchContacts));
        fetch('import_ajax.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(batchContacts)
        })
        .then(resp => {
          if (!resp.ok) {
            throw new Error(`HTTP error! Status: ${resp.status}`);
          }
          return resp.text().then(text => {
            try {
              // Try to parse as JSON
              return JSON.parse(text);
            } catch (e) {
              // If parsing fails, show the raw response
              console.error('Failed to parse JSON:', text);
              throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
            }
          });
        })
        .then(data => {
          console.log('Response data:', data);
          imported += (data.status === 'success') ? (parseInt(data.message) || batchContacts.length) : 0;
          batch++;
          const percent = Math.round((end/contacts.length)*100);
          const bar = document.getElementById('importProgressBar');
          if (bar) {
            bar.style.width = percent+'%';
            bar.textContent = percent+'%';
          }
          
          if (data.status !== 'success') {
            if (resultEl) resultEl.innerHTML = `<div class='alert alert-danger'>${data.message}</div>`;
            if (loadingEl) loadingEl.style.display = 'none';
            importBtn.disabled = false;
            return;
          }
          
          setTimeout(uploadBatch, 300); // Small delay between batches
        })
        .catch(error => {
          console.error('Import error:', error);
          if (resultEl) resultEl.innerHTML = `<div class="alert alert-danger">Import failed: ${error.message}</div>`;
          if (loadingEl) loadingEl.style.display = 'none';
          importBtn.disabled = false;
        });
      }
      
      uploadBatch();
    });
  }
});
</script>
</body>
</html>
