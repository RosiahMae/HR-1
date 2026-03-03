<?php
// api.php - Handles requests from the public Careers/Applicant Portal
header('Content-Type: application/json');
require_once 'db.php'; // Include our database connection

// Get the 'action' parameter from the URL or POST data
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    
    // 1. Fetch all active jobs to display on the careers page
    case 'get_jobs':
        try {
            $stmt = $pdo->query("SELECT * FROM jobs WHERE status = 'Active' ORDER BY created_at DESC");
            $jobs = $stmt->fetchAll();
            echo json_encode(["status" => "success", "data" => $jobs]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 2. Submit a new application form
    case 'submit_application':
        try {
            // Validate required fields
            if (empty($_POST['job_id']) || empty($_POST['fullName']) || empty($_POST['email']) || empty($_POST['phone'])) {
                echo json_encode(["status" => "error", "message" => "Missing required fields."]);
                exit;
            }

            // Sanitize inputs
            $job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
            $fullName = filter_input(INPUT_POST, 'fullName', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
            $coverLetter = filter_input(INPUT_POST, 'coverLetter', FILTER_SANITIZE_STRING);
            
            // Handle File Upload
            $resumePath = null;
            if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_' . basename($_FILES['resume']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
                    $resumePath = $targetPath;
                }
            }

            // Insert into the database
            $sql = "INSERT INTO applicants (job_id, full_name, email, phone, cover_letter, resume_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Screening')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$job_id, $fullName, $email, $phone, $coverLetter, $resumePath]);

            echo json_encode(["status" => "success", "message" => "Application submitted successfully!"]);

        } catch (PDOException $e) {
            // Handle duplicate email error specifically
            if ($e->getCode() == 23000) {
                echo json_encode(["status" => "error", "message" => "An application with this email already exists."]);
            } else {
                echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            }
        }
        break;

    // 3. Employee Registration (For Hired Applicants)
    case 'register':
        try {
            $email = $data['email'] ?? $_POST['email'] ?? '';
            $password = $data['password'] ?? $_POST['password'] ?? '';

            if (!$email || !$password) {
                echo json_encode(["status" => "error", "message" => "Email and password required."]);
                exit;
            }

            // Check if applicant exists and is Hired
            $stmt = $pdo->prepare("SELECT id, status FROM applicants WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || $user['status'] !== 'Hired') {
                echo json_encode(["status" => "error", "message" => "This email is not associated with a hired applicant."]);
                exit;
            }

            // Update password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE applicants SET password_hash = ? WHERE id = ?");
            $update->execute([$hash, $user['id']]);

            echo json_encode(["status" => "success", "message" => "Account created successfully."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 4. Employee Login
    case 'login':
        try {
            $email = $data['email'] ?? $_POST['email'] ?? '';
            $password = $data['password'] ?? $_POST['password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM applicants WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                // Remove password from response
                unset($user['password_hash']);
                echo json_encode(["status" => "success", "data" => $user]);
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
            }
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 5. Fetch Employee Workspace Data
    case 'get_employee_data':
        try {
            $applicant_id = $_GET['id'] ?? 0;

            // Get Tasks
            $stmtTasks = $pdo->prepare("SELECT id, task_name as task, is_completed as completed FROM onboarding_tasks WHERE applicant_id = ?");
            $stmtTasks->execute([$applicant_id]);
            $tasks = $stmtTasks->fetchAll();

            // Get Feed (Welcome posts or Kudos targeting this user)
            $stmtFeed = $pdo->prepare("SELECT * FROM recognition_feed WHERE target_applicant_id = ? OR type = 'welcome' ORDER BY created_at DESC");
            $stmtFeed->execute([$applicant_id]);
            $feed = $stmtFeed->fetchAll();

            echo json_encode(["status" => "success", "data" => ["tasks" => $tasks, "feed" => $feed]]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action specified."]);
        break;
}
?>