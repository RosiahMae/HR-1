<?php

header('Content-Type: application/json');
require_once 'db.php'; 


$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);


$action = $_GET['action'] ?? $_POST['action'] ?? $data['action'] ?? '';

function sendEmail($applicant_id, $subject, $message) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT email, full_name FROM applicants WHERE id = ?");
    $stmt->execute([$applicant_id]);
    $applicant = $stmt->fetch();
    if ($applicant) {
        $to = $applicant['email'];
        $headers = "From: hr@gweens.com\r\n";
        mail($to, $subject, $message, $headers);
    }
}

function setupOnboardingAndPerformance($applicant_id) {
    global $pdo;

    // First verify the applicant exists
    $checkApplicant = $pdo->prepare("SELECT COUNT(*) FROM applicants WHERE id = ?");
    $checkApplicant->execute([$applicant_id]);

    if ($checkApplicant->fetchColumn() == 0) {
        // Applicant doesn't exist, skip setup
        error_log("Warning: Attempted to set up onboarding for non-existent applicant ID: $applicant_id");
        return;
    }

    // 1. Check if onboarding tasks already exist to prevent duplicates
    $checkOnboarding = $pdo->prepare("SELECT COUNT(*) FROM onboarding_tasks WHERE applicant_id = ?");
    $checkOnboarding->execute([$applicant_id]);

    if ($checkOnboarding->fetchColumn() == 0) {
        // Insert default checklist
        $tasks = [
            'Signed Employment Contract',
            'Received Staff Uniform',
            'Health & Safety Orientation',
            'Role Specific Training'
        ];
        $insertTask = $pdo->prepare("INSERT INTO onboarding_tasks (applicant_id, task_name) VALUES (?, ?)");
        foreach ($tasks as $task) {
            $insertTask->execute([$applicant_id, $task]);
        }
    }

    // 2. Check and generate Performance Evaluation record
    $checkPerf = $pdo->prepare("SELECT COUNT(*) FROM performance_evaluations WHERE applicant_id = ?");
    $checkPerf->execute([$applicant_id]);

    if ($checkPerf->fetchColumn() == 0) {
        $insertPerf = $pdo->prepare("INSERT INTO performance_evaluations (applicant_id) VALUES (?)");
        $insertPerf->execute([$applicant_id]);
        $eval_id = $pdo->lastInsertId();

        // Add some default skills to track
        $skills = ['Initial Workflow Grasp', 'Team Communication', 'Customer Service Standard'];
        $insertSkill = $pdo->prepare("INSERT INTO performance_skills (evaluation_id, skill_name) VALUES (?, ?)");
        foreach ($skills as $skill) {
            $insertSkill->execute([$eval_id, $skill]);
        }
    }
    
    // 3. Generate a Social Recognition Welcome Post
    $getApp = $pdo->prepare("SELECT a.full_name, j.title FROM applicants a JOIN jobs j ON a.job_id = j.id WHERE a.id = ?");
    $getApp->execute([$applicant_id]);
    $appData = $getApp->fetch(PDO::FETCH_ASSOC);

    if ($appData) {
        $welcomeMsg = "Let's all give a warm welcome to " . $appData['full_name'] . ", our newest " . $appData['title'] . "!";
        $insertPost = $pdo->prepare("INSERT INTO recognition_feed (type, author_name, target_applicant_id, target_name, role, message, icon) VALUES ('welcome', 'System', ?, ?, ?, ?, 'party-popper')");
        $insertPost->execute([$applicant_id, $appData['full_name'], $appData['title'], $welcomeMsg]);
    }
}

switch ($action) {
    
    // 1. Fetch all applicants (for Dashboard, Pipeline, and Directory)
    case 'get_applicants':
        try {
            // Join applicants with their respective jobs
            $sql = "SELECT a.id, a.full_name as name, j.title as job, a.applied_at as date, a.status, a.email, a.phone, a.cover_letter as cover, a.resume_path
                    FROM applicants a 
                    JOIN jobs j ON a.job_id = j.id 
                    ORDER BY a.applied_at DESC";
            $stmt = $pdo->query($sql);
            $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enrich data with Onboarding and Performance info for the frontend
            foreach ($applicants as &$app) {
                // 1. Fetch Onboarding
                $stmtTask = $pdo->prepare("SELECT id, task_name as task, is_completed as completed FROM onboarding_tasks WHERE applicant_id = ?");
                $stmtTask->execute([$app['id']]);
                $tasks = $stmtTask->fetchAll(PDO::FETCH_ASSOC);
                
                // Convert 1/0 to boolean for JS
                foreach($tasks as &$t) $t['completed'] = (bool)$t['completed'];
                $app['onboarding'] = $tasks;

                // 2. Fetch Performance
                $stmtPerf = $pdo->prepare("SELECT id, overall_score as score FROM performance_evaluations WHERE applicant_id = ?");
                $stmtPerf->execute([$app['id']]);
                $perf = $stmtPerf->fetch(PDO::FETCH_ASSOC);

                if ($perf) {
                    $app['performance'] = [
                        'score' => $perf['score'] ?? 0,
                        'day30' => 'Pending', // Simplified for demo
                        'day60' => 'Pending',
                        'day90' => 'Pending'
                    ];
                    
                    $stmtSkills = $pdo->prepare("SELECT skill_name as name, rating FROM performance_skills WHERE evaluation_id = ?");
                    $stmtSkills->execute([$perf['id']]);
                    $skills = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Map rating to boolean passed for demo
                    $mappedSkills = [];
                    foreach($skills as $s) {
                        $mappedSkills[] = ['name' => $s['name'], 'passed' => $s['rating'] > 0];
                    }
                    $app['performance']['skills'] = $mappedSkills;
                } else {
                    $app['performance'] = null;
                }
            }
            
            echo json_encode(["status" => "success", "data" => $applicants]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 2. Update Applicant Status (Triggered by Drag & Drop on Kanban)
    case 'update_status':
        try {
            $applicant_id = $data['id'] ?? null;
            $new_status = $data['status'] ?? null;

            if (!$applicant_id || !$new_status) {
                echo json_encode(["status" => "error", "message" => "Missing applicant ID or new status."]);
                exit;
            }

            // Verify applicant exists before updating
            $checkApplicant = $pdo->prepare("SELECT COUNT(*) FROM applicants WHERE id = ?");
            $checkApplicant->execute([$applicant_id]);

            if ($checkApplicant->fetchColumn() == 0) {
                echo json_encode(["status" => "error", "message" => "Applicant not found."]);
                exit;
            }

            // Update the status
            $stmt = $pdo->prepare("UPDATE applicants SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $applicant_id]);

            // Send email notification based on status
            if ($new_status === 'Interview') {
                sendEmail($applicant_id, 'Interview Scheduled', 'Your interview has been scheduled. Please check your email for details.');
            } elseif ($new_status === 'Rejected') {
                sendEmail($applicant_id, 'Application Update', 'We regret to inform you that your application has been rejected.');
            } elseif ($new_status === 'Hired') {
                sendEmail($applicant_id, 'Congratulations!', 'You have been hired. Welcome to the team!');
                // Set up onboarding and performance only after final approval
                setupOnboardingAndPerformance($applicant_id);
            }

            echo json_encode(["status" => "success", "message" => "Status updated to $new_status."]);

        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 3. Update an Onboarding Task Checkbox
    case 'update_onboarding_task':
        try {
            $task_id = $data['task_id'] ?? null;
            $is_completed = $data['is_completed'] ?? null; // true or false boolean

            if (!$task_id) {
                echo json_encode(["status" => "error", "message" => "Missing task ID."]);
                exit;
            }

            $completed_val = $is_completed ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE onboarding_tasks SET is_completed = ? WHERE id = ?");
            $stmt->execute([$completed_val, $task_id]);

            echo json_encode(["status" => "success", "message" => "Task updated."]);

        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 4. Fetch Jobs (for Admin Management)
    case 'get_jobs':
        try {
            $stmt = $pdo->query("SELECT * FROM jobs ORDER BY created_at DESC");
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["status" => "success", "data" => $jobs]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 5. Submit Job (for departments to submit job postings)
    case 'submit_job':
        try {
            $title = $data['title'] ?? '';
            $department = $data['department'] ?? '';
            $location = $data['location'] ?? 'Quezon City';
            $type = $data['type'] ?? 'Full-time';
            $salary = $data['salary'] ?? '';
            $description = $data['description'] ?? '';
            $submitted_by = $data['submitted_by'] ?? 'Unknown';

            $sql = "INSERT INTO jobs (title, department, location, type, salary, description, status, submitted_by) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending Approval', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $department, $location, $type, $salary, $description, $submitted_by]);

            echo json_encode(["status" => "success", "message" => "Job submitted for approval."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 6. Approve Job
    case 'approve_job':
        try {
            $job_id = $data['job_id'] ?? null;
            if (!$job_id) {
                echo json_encode(["status" => "error", "message" => "Job ID required."]);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'Approved' WHERE id = ?");
            $stmt->execute([$job_id]);
            echo json_encode(["status" => "success", "message" => "Job approved."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 7. Reject Job
    case 'reject_job':
        try {
            $job_id = $data['job_id'] ?? null;
            if (!$job_id) {
                echo json_encode(["status" => "error", "message" => "Job ID required."]);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'Rejected' WHERE id = ?");
            $stmt->execute([$job_id]);
            echo json_encode(["status" => "success", "message" => "Job rejected."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 6. Update Performance Evaluation
    case 'update_performance':
        try {
            $applicant_id = $data['applicant_id'] ?? null;
            $score = $data['score'] ?? 0;
            $skills = $data['skills'] ?? []; // Array of {name: "Skill", passed: true/false}

            if (!$applicant_id) {
                echo json_encode(["status" => "error", "message" => "Applicant ID required."]);
                exit;
            }

            // 1. Update Overall Score
            // First find the evaluation ID
            $stmtEval = $pdo->prepare("SELECT id FROM performance_evaluations WHERE applicant_id = ?");
            $stmtEval->execute([$applicant_id]);
            $evalId = $stmtEval->fetchColumn();

            if (!$evalId) {
                // Create if not exists (though it should exist from 'Hired' logic)
                $stmtIns = $pdo->prepare("INSERT INTO performance_evaluations (applicant_id, overall_score) VALUES (?, ?)");
                $stmtIns->execute([$applicant_id, $score]);
                $evalId = $pdo->lastInsertId();
            } else {
                $stmtUpd = $pdo->prepare("UPDATE performance_evaluations SET overall_score = ? WHERE id = ?");
                $stmtUpd->execute([$score, $evalId]);
            }

            // 2. Update Skills
            // We will update existing skills based on name
            $stmtSkillUpd = $pdo->prepare("UPDATE performance_skills SET rating = ? WHERE evaluation_id = ? AND skill_name = ?");
            
            foreach ($skills as $skill) {
                $rating = $skill['passed'] ? 1 : 0;
                $stmtSkillUpd->execute([$rating, $evalId, $skill['name']]);
            }

            echo json_encode(["status" => "success", "message" => "Performance evaluation updated."]);

        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 7. Add Kudos (Recognition)
    case 'add_kudos':
        try {
            $target_id = $data['target_id'] ?? null;
            $author = $data['author'] ?? 'HR Admin';
            $message = $data['message'] ?? '';
            $badge = $data['badge'] ?? 'Star Player';
            $icon = $data['icon'] ?? 'star';

            if (!$target_id || !$message) {
                echo json_encode(["status" => "error", "message" => "Target employee and message required."]);
                exit;
            }

            // Get target name
            $stmtName = $pdo->prepare("SELECT full_name FROM applicants WHERE id = ?");
            $stmtName->execute([$target_id]);
            $target_name = $stmtName->fetchColumn();

            $sql = "INSERT INTO recognition_feed (type, author_name, target_applicant_id, target_name, badge, message, icon) 
                    VALUES ('kudos', ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$author, $target_id, $target_name, $badge, $message, $icon]);

            echo json_encode(["status" => "success", "message" => "Kudos sent successfully!"]);

        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 8. Get Recognition Feed
    case 'get_recognition':
        try {
            $stmt = $pdo->prepare("SELECT * FROM recognition_feed ORDER BY created_at DESC LIMIT 20");
            $stmt->execute();
            $recognition_feed = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(["status" => "success", "data" => $recognition_feed]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 9. Get Analytics
    case 'get_analytics':
        try {
            $analytics = [];

            // Total applicants
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM applicants");
            $analytics['total_applicants'] = $stmt->fetchColumn();

            // Hired
            $stmt = $pdo->query("SELECT COUNT(*) as hired FROM applicants WHERE status = 'Hired'");
            $analytics['hired'] = $stmt->fetchColumn();

            // Rejected
            $stmt = $pdo->query("SELECT COUNT(*) as rejected FROM applicants WHERE status = 'Rejected'");
            $analytics['rejected'] = $stmt->fetchColumn();

            // Screening
            $stmt = $pdo->query("SELECT COUNT(*) as screening FROM applicants WHERE status = 'Screening'");
            $analytics['screening'] = $stmt->fetchColumn();

            // Interview
            $stmt = $pdo->query("SELECT COUNT(*) as interview FROM applicants WHERE status = 'Interview'");
            $analytics['interview'] = $stmt->fetchColumn();

            // Training
            $stmt = $pdo->query("SELECT COUNT(*) as training FROM applicants WHERE status = 'Training'");
            $analytics['training'] = $stmt->fetchColumn();

            // Performance Check
            $stmt = $pdo->query("SELECT COUNT(*) as performance_check FROM applicants WHERE status = 'Performance Check'");
            $analytics['performance_check'] = $stmt->fetchColumn();

            echo json_encode(["status" => "success", "data" => $analytics]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 10. Send to Training (HR2)
    case 'send_to_training':
        try {
            $applicant_id = $data['id'] ?? null;
            if (!$applicant_id) {
                echo json_encode(["status" => "error", "message" => "Applicant ID required."]);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE applicants SET status = 'Training' WHERE id = ?");
            $stmt->execute([$applicant_id]);
            sendEmail($applicant_id, 'Training Scheduled', 'You have been selected for training. Please await further instructions.');
            echo json_encode(["status" => "success", "message" => "Sent to training."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 11. Approve Performance
    case 'approve_performance':
        try {
            $applicant_id = $data['id'] ?? null;
            if (!$applicant_id) {
                echo json_encode(["status" => "error", "message" => "Applicant ID required."]);
                exit;
            }

            // Verify applicant exists before updating
            $checkApplicant = $pdo->prepare("SELECT COUNT(*) FROM applicants WHERE id = ?");
            $checkApplicant->execute([$applicant_id]);

            if ($checkApplicant->fetchColumn() == 0) {
                echo json_encode(["status" => "error", "message" => "Applicant not found."]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE applicants SET status = 'Hired' WHERE id = ?");
            $stmt->execute([$applicant_id]);
            sendEmail($applicant_id, 'Hired!', 'Congratulations! You have passed the performance check and are now hired.');
            setupOnboardingAndPerformance($applicant_id);
            echo json_encode(["status" => "success", "message" => "Performance approved, hired."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 12. Edit Applicant
    case 'edit_applicant':
        try {
            $applicant_id = $data['id'] ?? null;
            $full_name = $data['full_name'] ?? '';
            $email = $data['email'] ?? '';
            $phone = $data['phone'] ?? '';
            $cover_letter = $data['cover_letter'] ?? '';

            if (!$applicant_id || !$full_name || !$email) {
                echo json_encode(["status" => "error", "message" => "Applicant ID, name, and email are required."]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE applicants SET full_name = ?, email = ?, phone = ?, cover_letter = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $cover_letter, $applicant_id]);

            echo json_encode(["status" => "success", "message" => "Applicant updated successfully."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // 13. Delete Applicant
    case 'delete_applicant':
        try {
            $applicant_id = $data['id'] ?? null;

            if (!$applicant_id) {
                echo json_encode(["status" => "error", "message" => "Applicant ID is required."]);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM applicants WHERE id = ?");
            $stmt->execute([$applicant_id]);

            echo json_encode(["status" => "success", "message" => "Applicant deleted successfully."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid action specified."]);
        break;
}
?>