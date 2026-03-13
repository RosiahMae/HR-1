<?php

require_once 'db.php';

try {
    // Insert a sample job
    $jobSql = "INSERT INTO jobs (title, department, location, type, salary, description, status, submitted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $jobStmt = $pdo->prepare($jobSql);
    $jobStmt->execute([
        'Senior Barista',
        'Beverage & Service',
        'Quezon City',
        'Full-time',
        '₱25,000 - ₱35,000',
        'We are looking for an experienced barista to join our team. Responsibilities include preparing specialty coffee drinks, maintaining equipment, and providing excellent customer service.',
        'Active',
        'Operations Manager'
    ]);

    $jobId = $pdo->lastInsertId();
    echo "✅ Created job: Senior Barista (ID: $jobId)\n";

    // Insert first applicant
    $app1Sql = "INSERT INTO applicants (job_id, full_name, email, phone, cover_letter, status, requesting_department) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $app1Stmt = $pdo->prepare($app1Sql);
    $app1Stmt->execute([
        $jobId,
        'Maria Santos',
        'maria.santos@email.com',
        '0917-123-4567',
        'I have 3 years of experience working in a busy cafe environment. I am passionate about coffee and have extensive knowledge of specialty drinks and latte art. I am confident I can contribute to your team immediately.',
        'Screening',
        'Beverage & Service'
    ]);

    $app1Id = $pdo->lastInsertId();
    echo "✅ Created applicant: Maria Santos (ID: $app1Id) - Status: Screening\n";

    // Insert second applicant for the same job
    $app2Sql = "INSERT INTO applicants (job_id, full_name, email, phone, cover_letter, status, requesting_department) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $app2Stmt = $pdo->prepare($app2Sql);
    $app2Stmt->execute([
        $jobId,
        'Juan dela Cruz',
        'juan.delacruz@email.com',
        '0918-987-6543',
        'I am a dedicated professional with 2 years of barista experience. I excel at customer service and have a strong work ethic. I would love the opportunity to join your team.',
        'Interview',
        'Beverage & Service'
    ]);

    $app2Id = $pdo->lastInsertId();
    echo "✅ Created applicant: Juan dela Cruz (ID: $app2Id) - Status: Interview\n";

    // Insert another job
    $job2Sql = "INSERT INTO jobs (title, department, location, type, salary, description, status, submitted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $job2Stmt = $pdo->prepare($job2Sql);
    $job2Stmt->execute([
        'Store Cashier',
        'Front of House',
        'Quezon City',
        'Part-time',
        '₱15,000 - ₱20,000',
        'We need a reliable cashier for our front counter. Responsibilities include handling transactions, maintaining cash register, and assisting customers.',
        'Active',
        'Store Manager'
    ]);

    $job2Id = $pdo->lastInsertId();
    echo "✅ Created job: Store Cashier (ID: $job2Id)\n";

    // Insert third applicant for the second job
    $app3Sql = "INSERT INTO applicants (job_id, full_name, email, phone, cover_letter, status, requesting_department) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $app3Stmt = $pdo->prepare($app3Sql);
    $app3Stmt->execute([
        $job2Id,
        'Ana Reyes',
        'ana.reyes@email.com',
        '0919-555-1234',
        'I have excellent math skills and customer service experience. I am detail-oriented and reliable. I would be happy to contribute to your store operations.',
        'Hired',
        'Front of House'
    ]);

    $app3Id = $pdo->lastInsertId();
    echo "✅ Created applicant: Ana Reyes (ID: $app3Id) - Status: Hired\n";

    // Set up onboarding tasks for the hired applicant
    $onboardingTasks = [
        'Signed Employment Contract',
        'Provide Staff Uniform',
        'Health & Safety Orientation',
        'POS System Training'
    ];

    $taskSql = "INSERT INTO onboarding_tasks (applicant_id, task_name, is_completed) VALUES (?, ?, ?)";
    $taskStmt = $pdo->prepare($taskSql);

    foreach ($onboardingTasks as $index => $task) {
        $isCompleted = $index < 2; // First two tasks completed
        $taskStmt->execute([$app3Id, $task, $isCompleted]);
    }

    echo "✅ Created onboarding tasks for Ana Reyes\n";

    // Set up performance evaluation for the hired applicant
    $perfSql = "INSERT INTO performance_evaluations (applicant_id, overall_score) VALUES (?, ?)";
    $perfStmt = $pdo->prepare($perfSql);
    $perfStmt->execute([$app3Id, 85]);

    $perfId = $pdo->lastInsertId();

    // Add performance skills
    $skills = [
        ['POS Operations', 1],
        ['Customer Greeting Standard', 1],
        ['End of Day Tills', 0]
    ];

    $skillSql = "INSERT INTO performance_skills (evaluation_id, skill_name, rating) VALUES (?, ?, ?)";
    $skillStmt = $pdo->prepare($skillSql);

    foreach ($skills as $skill) {
        $skillStmt->execute([$perfId, $skill[0], $skill[1]]);
    }

    echo "✅ Created performance evaluation for Ana Reyes\n";

    // Add a recognition post
    $recognitionSql = "INSERT INTO recognition_feed (type, author_name, target_applicant_id, target_name, role, message, icon) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $recognitionStmt = $pdo->prepare($recognitionSql);
    $recognitionStmt->execute([
        'welcome',
        'HR Team',
        $app3Id,
        'Ana Reyes',
        'Store Cashier',
        'Welcome to the Gween\'s Bake n Brew family! We are excited to see you shine at the front counter.',
        'hand-metal'
    ]);

    echo "✅ Created welcome recognition post for Ana Reyes\n";

    echo "\n🎉 Successfully added real data to the database!\n";
    echo "📊 Summary:\n";
    echo "   • 2 Active Jobs\n";
    echo "   • 3 Applicants (Screening, Interview, Hired)\n";
    echo "   • Onboarding tasks and performance data for hired applicant\n";
    echo "   • Recognition feed activity\n";
    echo "\n🔄 Refresh your admin panel to see the real data!\n";

} catch (PDOException $e) {
    echo "❌ Error adding data: " . $e->getMessage() . "\n";
}

?>