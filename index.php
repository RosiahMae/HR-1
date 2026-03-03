<?php
// Employee Portal - PHP Version
session_start();

// Check if user is logged in
$loggedInUser = isset($_SESSION['employee_email']) ? $_SESSION['employee_email'] : null;
$loggedInUserData = isset($_SESSION['employee_data']) ? $_SESSION['employee_data'] : null;

// Handle AJAX API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    // Handle employee registration
    if ($action === 'register') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
            exit;
        }
        
        // Simulate registration (store in session for demo)
        $_SESSION['employee_email'] = $email;
        $_SESSION['employee_data'] = [
            'email' => $email,
            'full_name' => 'New Employee',
            'id' => uniqid()
        ];
        
        echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
        exit;
    }
    
    // Handle employee login
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Basic validation (in production, query from database)
        if (!empty($email) && !empty($password)) {
            $_SESSION['employee_email'] = $email;
            $_SESSION['employee_data'] = [
                'email' => $email,
                'full_name' => 'Employee ' . ucfirst(explode('@', $email)[0]),
                'id' => uniqid()
            ];
            
            echo json_encode(['status' => 'success', 'data' => $_SESSION['employee_data']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
        }
        exit;
    }
    
    // Handle job application submission
    if ($action === 'submit_application') {
        $fullName = $_POST['fullName'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $jobId = $_POST['job_id'] ?? '';
        
        // In production, save to database
        // For now, just simulate success
        echo json_encode(['status' => 'success', 'message' => 'Application submitted successfully']);
        exit;
    }
}

// Handle GET requests for job data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    
    if ($action === 'get_jobs') {
        $jobs = [
            [
                'id' => 1,
                'title' => 'Senior Barista',
                'department' => 'Beverage & Service',
                'location' => 'Quezon City',
                'type' => 'Full-time',
                'salary' => '₱18,000 - ₱25,000',
                'description' => 'Looking for an experienced barista with a passion for coffee...'
            ],
            [
                'id' => 2,
                'title' => 'Pastry Chef / Baker',
                'department' => 'Kitchen',
                'location' => 'Quezon City',
                'type' => 'Full-time',
                'salary' => '₱20,000 - ₱30,000',
                'description' => 'Join our kitchen team to create the finest baked goods...'
            ],
            [
                'id' => 3,
                'title' => 'Store Cashier',
                'department' => 'Front of House',
                'location' => 'Quezon City',
                'type' => 'Part-time',
                'salary' => '₱12,000 - ₱15,000',
                'description' => 'Friendly cashier needed for our bustling cafe...'
            ]
        ];
        
        echo json_encode(['status' => 'success', 'data' => $jobs]);
        exit;
    }
    
    if ($action === 'get_employee_data') {
        if ($loggedInUserData) {
            echo json_encode(['status' => 'success', 'data' => $loggedInUserData]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
        }
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gween's Bake n Brew - Careers & Employee Portal</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #faf7f2;
            color: #4a332a;
        }
        h1, h2, h3, h4, .font-serif {
            font-family: 'Playfair Display', serif;
        }
        
        .view-section { display: none; animation: fadeIn 0.5s ease-in-out; }
        .view-section.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="selection:bg-[#f5e6de] selection:text-[#4a332a]">

    <!-- Navbar -->
    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-2 cursor-pointer" onclick="switchView('careers')">
                <div class="bg-[#5c4033] p-2 rounded-full text-white">
                    <i data-lucide="coffee" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-[#4a332a] leading-tight font-serif">Gween's Bake n Brew</h1>
                    <p class="text-xs text-[#8b6b5d] font-medium tracking-wider uppercase">Team Portal</p>
                </div>
            </div>
            
            <div class="flex gap-4 items-center">
                <button onclick="switchView('careers')" class="font-medium text-[#5c4033] hover:text-[#5c4033] transition-colors">
                    Open Roles
                </button>
                
                <div id="nav-auth-container" class="flex gap-4 items-center">
                    <button id="nav-dashboard-btn" onclick="handleWorkspaceClick()" class="flex items-center gap-2 font-medium px-4 py-2 rounded-full bg-[#fdf8f5] text-[#5c4033] hover:bg-[#f5e6de] transition-colors">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span id="nav-workspace-text">My Workspace Login</span>
                    </button>
                    <button id="nav-logout-btn" onclick="logout()" class="hidden text-sm font-medium text-red-500 hover:text-red-700 transition-colors">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="min-h-[80vh]">
        <!-- 1. CAREERS LISTING VIEW -->
        <section id="careers-view" class="view-section active">
            <div class="bg-gradient-to-br from-[#fdf8f5] to-[#f5e6de] py-20 px-4 text-center rounded-b-[3rem] shadow-sm mb-12">
                <div class="max-w-3xl mx-auto">
                    <h2 class="text-4xl md:text-5xl font-serif font-bold text-[#4a332a] mb-6">Join Our Brew-tiful Team</h2>
                    <p class="text-lg text-[#6b4c3a] mb-8 leading-relaxed">
                        We're always looking for passionate individuals who share our love for artisanal coffee and exquisite baked goods.
                    </p>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-4 pb-20">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h3 class="text-2xl font-bold text-[#4a332a]">Open Positions</h3>
                    </div>
                    <div id="job-count" class="text-sm font-medium text-[#8b6b5d] bg-[#fdf8f5] px-4 py-2 rounded-full">
                        Loading...
                    </div>
                </div>
                <div id="job-listings-container" class="grid gap-4">
                    <!-- Jobs will be injected here -->
                </div>
            </div>
        </section>

        <!-- 2. JOB DETAILS VIEW -->
        <section id="job-details-view" class="view-section max-w-4xl mx-auto px-4 py-12">
            <button onclick="switchView('careers')" class="flex items-center gap-2 text-gray-500 hover:text-[#5c4033] mb-8 font-medium transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> Back to all jobs
            </button>

            <div class="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-[#f0e4dd]">
                <div class="border-b border-gray-100 pb-8 mb-8">
                    <h2 id="detail-title" class="text-3xl md:text-4xl font-serif font-bold text-[#4a332a] mb-4">Job Title</h2>
                    <div class="flex flex-wrap gap-6 text-gray-600">
                        <div class="flex items-center gap-2"><i data-lucide="briefcase" class="w-5 h-5 text-[#8b6b5d]"></i> <span id="detail-dept">Dept</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="map-pin" class="w-5 h-5 text-[#8b6b5d]"></i> <span id="detail-loc">Location</span></div>
                        <div class="flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-[#8b6b5d]"></i> <span id="detail-type">Type</span></div>
                    </div>
                </div>

                <div class="prose max-w-none text-gray-600 mb-12">
                    <h3 class="text-xl font-bold text-[#4a332a] mb-4">About the Role</h3>
                    <p id="detail-desc" class="mb-8 leading-relaxed whitespace-pre-line">Description goes here...</p>

                    <h3 class="text-xl font-bold text-[#4a332a] mb-4">Salary Range</h3>
                    <p id="detail-salary" class="font-medium text-[#6b4c3a] bg-[#fdf8f5] inline-block px-4 py-2 rounded-lg">₱0 - ₱0</p>
                </div>

                <button onclick="switchView('apply')" class="w-full md:w-auto bg-[#5c4033] text-white px-12 py-4 rounded-xl font-bold text-lg hover:bg-[#4a332a] transition-colors shadow-md">
                    Apply for this Position
                </button>
            </div>
        </section>

        <!-- 3. APPLY FORM VIEW -->
        <section id="apply-view" class="view-section max-w-3xl mx-auto px-4 py-12">
            <button onclick="switchView('job-details')" class="flex items-center gap-2 text-gray-500 hover:text-[#5c4033] mb-8 font-medium transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> Back to job details
            </button>

            <div id="success-message" class="hidden bg-white rounded-3xl p-12 shadow-sm border border-[#f0e4dd] text-center">
                <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="check-circle-2" class="w-12 h-12"></i>
                </div>
                <h2 class="text-3xl font-serif font-bold text-[#4a332a] mb-4">Application Submitted!</h2>
                <p class="text-gray-600 mb-8 text-lg">Thank you for applying. Our HR team will review your application shortly.</p>
                <button onclick="switchView('careers')" class="text-[#5c4033] font-bold hover:underline">Back to Open Roles</button>
            </div>

            <div id="form-container" class="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-[#f0e4dd]">
                <div class="mb-10">
                    <h2 class="text-3xl font-serif font-bold text-[#4a332a] mb-2">Submit Application</h2>
                    <p class="text-gray-500">Applying for: <strong id="apply-job-title">Job Title</strong></p>
                </div>

                <form id="application-form" onsubmit="submitApplication(event)" class="space-y-6">
                    <input type="hidden" id="job_id" name="job_id">
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-700">Full Name *</label>
                            <input type="text" name="fullName" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#8b6b5d] focus:ring-2 outline-none transition-all" placeholder="Juan Dela Cruz">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-700">Email Address *</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#8b6b5d] focus:ring-2 outline-none transition-all" placeholder="juan@example.com">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-700">Phone Number *</label>
                        <input type="tel" name="phone" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#8b6b5d] focus:ring-2 outline-none transition-all" placeholder="0912 345 6789">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-700">Resume / CV (PDF/DOCX) *</label>
                        <input type="file" name="resume" required class="w-full text-sm text-gray-500">
                    </div>

                    <div id="form-error" class="hidden bg-red-50 text-red-600 p-4 rounded-xl text-sm font-medium border border-red-200"></div>

                    <div class="pt-6 border-t border-gray-100">
                        <button type="submit" id="submit-btn" class="w-full bg-[#5c4033] text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-[#4a332a] transition-colors">
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- 4. LOGIN VIEW -->
        <section id="login-view" class="view-section max-w-md mx-auto px-4 py-20">
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-[#f0e4dd]">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-[#fdf8f5] text-[#5c4033] rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="lock" class="w-8 h-8"></i>
                    </div>
                    <h2 class="text-2xl font-serif font-bold text-[#4a332a]">Employee Login</h2>
                    <p class="text-gray-500 text-sm mt-1">Access your workspace.</p>
                </div>

                <div id="login-error" class="hidden bg-red-50 text-red-600 p-3 rounded-lg text-sm font-medium border border-red-200 mb-6 text-center"></div>

                <form id="login-form" onsubmit="handleLogin(event)" class="space-y-5">
                    <div>
                        <label class="text-sm font-bold text-gray-700 block mb-1">Email Address</label>
                        <input type="email" id="login-email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#8b6b5d] focus:ring-2 outline-none transition-all">
                    </div>
                    <div>
                        <label class="text-sm font-bold text-gray-700 block mb-1">Password</label>
                        <input type="password" id="login-password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#8b6b5d] focus:ring-2 outline-none transition-all">
                    </div>
                    
                    <button type="submit" class="w-full bg-[#5c4033] text-white px-8 py-3.5 rounded-xl font-bold hover:bg-[#4a332a] transition-colors">
                        Sign In
                    </button>
                </form>
            </div>
        </section>

        <!-- 5. DASHBOARD -->
        <section id="dashboard-view" class="view-section max-w-6xl mx-auto px-4 py-12">
            <div class="bg-gradient-to-r from-[#5c4033] to-[#8b6b5d] rounded-3xl p-8 md:p-10 shadow-lg mb-8 text-white flex items-center gap-6">
                <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center border border-white/30">
                    <i data-lucide="user" class="w-10 h-10"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-serif font-bold mb-1" id="dash-welcome-name">Welcome!</h2>
                    <p class="text-[#f5e6de]">Employee Dashboard</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-[#f0e4dd]">
                    <h3 class="text-lg font-bold text-[#4a332a] mb-4 font-serif">Profile</h3>
                    <div class="bg-[#fdf8f5] p-4 rounded-xl border border-[#e8dcd5]">
                        <h4 class="font-bold text-[#4a332a]" id="dash-name">Employee</h4>
                        <p class="text-sm text-gray-500">Email: <span id="dash-email">email@example.com</span></p>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-sm border border-[#f0e4dd]">
                    <h3 class="text-xl font-bold text-[#4a332a] font-serif mb-4">Welcome!</h3>
                    <p class="text-gray-600">You're logged in to your employee portal. Explore open positions or manage your profile.</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="mt-20 py-8 text-center text-sm text-gray-400 border-t border-gray-200/50">
        <p>&copy; 2026 Gween's Bake n Brew - Human Resources.</p>
    </footer>

    <script>
        let loggedInUser = <?php echo $loggedInUser ? json_encode($loggedInUserData) : 'null'; ?>;
        let JOB_LISTINGS = [];
        let currentJob = null;

        document.addEventListener('DOMContentLoaded', () => {
            fetchJobsFromDatabase();
            lucide.createIcons();
        });

        function switchView(viewId) {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            document.getElementById(`${viewId}-view`).classList.add('active');
            window.scrollTo(0, 0);
        }

        function handleWorkspaceClick() {
            if (loggedInUser) {
                switchView('dashboard');
            } else {
                switchView('login');
            }
        }

        function handleLogin(e) {
            e.preventDefault();
            const errorBox = document.getElementById('login-error');
            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);

            fetch('<?php echo basename(__FILE__); ?>', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        loggedInUser = data.data;
                        updateUIAfterLogin();
                        switchView('dashboard');
                    } else {
                        errorBox.innerText = data.message;
                        errorBox.classList.remove('hidden');
                    }
                });
        }

        function updateUIAfterLogin() {
            const dashBtn = document.getElementById('nav-dashboard-btn');
            dashBtn.classList.add('bg-[#5c4033]', 'text-white');
            dashBtn.classList.remove('bg-[#fdf8f5]', 'text-[#5c4033]');
            document.getElementById('nav-workspace-text').innerText = "My Workspace";
            document.getElementById('nav-logout-btn').classList.remove('hidden');
            
            document.getElementById('dash-welcome-name').innerText = `Welcome, ${loggedInUser.full_name.split(' ')[0]}!`;
            document.getElementById('dash-name').innerText = loggedInUser.full_name;
            document.getElementById('dash-email').innerText = loggedInUser.email;
        }

        function logout() {
            loggedInUser = null;
            const dashBtn = document.getElementById('nav-dashboard-btn');
            dashBtn.classList.remove('bg-[#5c4033]', 'text-white');
            dashBtn.classList.add('bg-[#fdf8f5]', 'text-[#5c4033]');
            document.getElementById('nav-workspace-text').innerText = "My Workspace Login";
            document.getElementById('nav-logout-btn').classList.add('hidden');
            switchView('careers');
        }

        function fetchJobsFromDatabase() {
            fetch('<?php echo basename(__FILE__); ?>?action=get_jobs')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        JOB_LISTINGS = data.data;
                        document.getElementById('job-count').innerText = `${JOB_LISTINGS.length} Roles Available`;
                        renderJobs();
                    }
                })
                .catch(() => {
                    document.getElementById('job-count').innerText = '3 Roles Available';
                    JOB_LISTINGS = [
                        { id: 1, title: 'Senior Barista', department: 'Beverage & Service', location: 'Quezon City', type: 'Full-time', salary: '₱18,000 - ₱25,000' },
                        { id: 2, title: 'Pastry Chef / Baker', department: 'Kitchen', location: 'Quezon City', type: 'Full-time', salary: '₱20,000 - ₱30,000' },
                        { id: 3, title: 'Store Cashier', department: 'Front of House', location: 'Quezon City', type: 'Part-time', salary: '₱12,000 - ₱15,000' }
                    ];
                    renderJobs();
                });
        }

        function renderJobs() {
            const container = document.getElementById('job-listings-container');
            container.innerHTML = JOB_LISTINGS.map(job => `
                <div class="bg-white border border-[#f0e4dd] rounded-2xl p-6 hover:shadow-md transition-all cursor-pointer" onclick="openJobDetails(${job.id})">
                    <h4 class="text-xl font-bold text-[#4a332a] mb-3">${job.title}</h4>
                    <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                        <div class="flex items-center gap-1.5"><i data-lucide="briefcase" class="w-4 h-4"></i> ${job.department}</div>
                        <div class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-4 h-4"></i> ${job.location}</div>
                        <div class="flex items-center gap-1.5"><i data-lucide="clock" class="w-4 h-4"></i> ${job.type}</div>
                    </div>
                    <button class="mt-4 flex items-center gap-2 bg-[#fdf8f5] text-[#5c4033] px-6 py-2 rounded-xl font-medium hover:bg-[#5c4033] hover:text-white transition-colors">
                        View Details <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            `).join('');
            lucide.createIcons();
        }

        function openJobDetails(id) {
            currentJob = JOB_LISTINGS.find(j => j.id == id);
            if (!currentJob) return;

            document.getElementById('detail-title').innerText = currentJob.title;
            document.getElementById('detail-dept').innerText = currentJob.department;
            document.getElementById('detail-loc').innerText = currentJob.location;
            document.getElementById('detail-type').innerText = currentJob.type;
            document.getElementById('detail-salary').innerText = currentJob.salary;
            document.getElementById('apply-job-title').innerText = currentJob.title;
            document.getElementById('job_id').value = currentJob.id;
            
            switchView('job-details');
            lucide.createIcons();
        }

        function submitApplication(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            btn.innerText = "Submitting...";
            btn.disabled = true;

            const formData = new FormData(e.target);
            formData.append('action', 'submit_application');

            fetch('<?php echo basename(__FILE__); ?>', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('form-container').classList.add('hidden');
                        document.getElementById('success-message').classList.remove('hidden');
                    }
                })
                .catch(() => {
                    document.getElementById('form-container').classList.add('hidden');
                    document.getElementById('success-message').classList.remove('hidden');
                });
        }
    </script>
</body>
</html>
