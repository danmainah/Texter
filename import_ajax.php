<?php
// Prevent any output before JSON response
ob_start();

// Set headers first, before any potential output
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Disable error display but log errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Include required files
    require_once 'auth.php';
    require_once 'config.php';
    include_once 'import_config.php';
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Not authenticated. Please log in.");
    }
    
    $response = ["status" => "error", "message" => "Unknown error."];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get and validate JSON data
        $json_input = file_get_contents('php://input');
        $data = json_decode($json_input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON data: " . json_last_error_msg());
        }
        
        if (!is_array($data) || empty($data)) {
            throw new Exception("Invalid data format or empty data.");
        }
        
        $imported = 0;
        $errors = [];
        
        foreach ($data as $contact) {
            $name = $conn->real_escape_string($contact['name'] ?? '');
            $phone = $conn->real_escape_string($contact['phone'] ?? '');
            $location = $conn->real_escape_string($contact['location'] ?? '');
            
            if (empty($name) || empty($phone)) {
                continue; // Skip invalid contacts
            }
            
            // Process phone numbers that contain multiple numbers or are too long
            if (strlen($phone) > 15) {
                // If it contains a slash, take the first number
                if (strpos($phone, '/') !== false) {
                    $phone = trim(explode('/', $phone)[0]);
                } else {
                    // Otherwise truncate to 15 characters
                    $phone = substr($phone, 0, 15);
                }
            }
            
            // Clean up phone number - remove any non-numeric characters except + at the beginning
            if (substr($phone, 0, 1) === '+') {
                $phone = '+' . preg_replace('/[^0-9]/', '', substr($phone, 1));
            } else {
                $phone = preg_replace('/[^0-9]/', '', $phone);
            }
            
            // Skip if phone is now empty after cleaning
            if (empty($phone)) {
                continue;
            }
            
            try {
                $exists = $conn->query("SELECT id FROM contacts WHERE name='$name' AND phone='$phone' LIMIT 1");
                if ($exists->num_rows == 0) {
                    $result = $conn->query("INSERT INTO contacts (name, phone, location, created_by) VALUES ('{$name}', '{$phone}', '{$location}', '" . intval($_SESSION['user_id']) . "')");
                    if ($result) {
                        $imported++;
                    }
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            $response = ["status" => "warning", "message" => "$imported contacts imported. Some contacts had issues: " . implode("; ", array_slice($errors, 0, 3))];
        } else {
            $response = ["status" => "success", "message" => "$imported contacts imported in this batch."];
        }
    } else {
        throw new Exception("Invalid request method. Only POST is supported.");
    }
} catch (Exception $e) {
    $response = ["status" => "error", "message" => $e->getMessage()];
}

// Clear any output buffered so far
$output = ob_get_clean();

// Send JSON response
echo json_encode($response);

