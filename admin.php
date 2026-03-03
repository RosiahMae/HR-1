<?php
// Admin Portal - PHP Version
session_start();

// Check if user is already logged in
$isLoggedIn = isset($_SESSION['admin_email']);

// Handle AJAX API requests if needed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    // Handle registration
    if ($action === 'register') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Basic validation (in production, use proper backend validation)
        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
            exit;
        }
        
        // Simulate registration (in production, save to database)
        $_SESSION['admin_email'] = $email;
        echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
        exit;
    }
    
    // Handle login
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Hardcoded test credentials (in production, query database)
        if ($email === 'john.rey@gweens.com' && $password === 'Admin@123!') {
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_name'] = 'John Rey Baria';
            $_SESSION['admin_role'] = 'HR Team Leader';
            echo json_encode(['status' => 'success', 'message' => 'Login successful']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        }
        exit;
    }
}

// Handle AJAX GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['action'];
    
    // Return applicants data
    if ($action === 'get_applicants') {
        $applicants = [
            ['id' => 'APP-001', 'name' => 'Maria Santos', 'job' => 'Senior Barista', 'date' => '2026-03-01', 'status' => 'Screening'],
            ['id' => 'APP-002', 'name' => 'Mark Repardas', 'job' => 'Pastry Chef / Baker', 'date' => '2026-02-28', 'status' => 'Interview']
        ];
        echo json_encode(['status' => 'success', 'data' => $applicants]);
        exit;
    }
    
    // Return jobs data
    if ($action === 'get_jobs') {
        $jobs = [
            ['id' => 1, 'title' => 'Senior Barista', 'department' => 'Beverage & Service', 'type' => 'Full-time', 'status' => 'Active'],
            ['id' => 2, 'title' => 'Pastry Chef / Baker', 'department' => 'Kitchen', 'type' => 'Full-time', 'status' => 'Active']
        ];
        echo json_encode(['status' => 'success', 'data' => $jobs]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Admin - Gween's Bake n Brew</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f0ea; 
            color: #4a332a;
        }
        h1, h2, h3, .font-serif {
            font-family: 'Playfair Display', serif;
        }

        .admin-view:not(.active) { display: none !important; }
        .admin-view.active { animation: fadeIn 0.3s ease-in-out; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .kanban-col::-webkit-scrollbar { width: 6px; }
        .kanban-col::-webkit-scrollbar-track { background: transparent; }
        .kanban-col::-webkit-scrollbar-thumb { background-color: #d1c4bc; border-radius: 10px; }
        
        .kanban-card.dragging { opacity: 0.5; transform: scale(0.95); }
        .kanban-col.drag-over { background-color: #f5e6de; border-color: #cda896; border-style: dashed; }
    </style>
</head>
<body class="selection:bg-[#f5e6de] selection:text-[#4a332a]">

    <!-- AUTHENTICATION LAYOUT -->
    <div id="auth-layout" class="<?php echo $isLoggedIn ? 'hidden' : ''; ?> min-h-screen flex items-center justify-center p-4">
        
        <!-- LOGIN VIEW -->
        <div id="admin-login-view" class="bg-white rounded-3xl p-8 md:p-10 shadow-lg border border-[#e8dcd5] w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-[#5c4033] text-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <i data-lucide="coffee" class="w-8 h-8"></i>
                </div>
                <h2 class="text-2xl font-serif font-bold text-[#4a332a]">HR Admin Portal</h2>
                <p class="text-gray-500 text-sm mt-1">Authorized personnel only.</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 text-blue-700 p-3 rounded-xl text-xs mb-6 text-center">
                <p class="font-bold mb-1">Test Credentials</p>
                <p>Email: <span class="font-mono">john.rey@gweens.com</span></p>
                <p>Password: <span class="font-mono">Admin@123!</span></p>
            </div>

            <div id="admin-login-error" class="hidden bg-red-50 text-red-600 p-3 rounded-lg text-sm font-medium border border-red-200 mb-6 text-center"></div>

            <form id="admin-login-form" onsubmit="handleAdminLogin(event)" class="space-y-5">
                <div>
                    <label class="text-sm font-bold text-gray-700 block mb-1">Email Address</label>
                    <input type="email" id="login-email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#8b6b5d] focus:ring-2 outline-none transition-all" placeholder="name@gweens.com">
                </div>
                <div>
                    <label class="text-sm font-bold text-gray-700 block mb-1">Password</label>
                    <input type="password" id="login-password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#8b6b5d] focus:ring-2 outline-none transition-all">
                </div>
                
                <button type="submit" class="w-full bg-[#5c4033] text-white px-8 py-3.5 rounded-xl font-bold hover:bg-[#4a332a] transition-colors mt-2 shadow-sm">
                    Sign In to Dashboard
                </button>
            </form>
        </div>
    </div>

    <!-- MAIN APP LAYOUT -->
    <div id="app-layout" class="<?php echo !$isLoggedIn ? 'hidden' : ''; ?> flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside class="w-64 bg-white border-r border-[#e8dcd5] flex flex-col hidden md:flex z-20 shadow-sm">
            <div class="p-6 border-b border-[#e8dcd5] flex items-center gap-3">
                <div class="bg-[#5c4033] p-2 rounded-xl text-white shadow-sm">
                    <i data-lucide="coffee" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-[#4a332a] leading-tight font-serif">HR Admin</h1>
                    <p class="text-[10px] text-[#8b6b5d] font-bold tracking-wider uppercase">Talent Acquisition</p>
                </div>
            </div>

            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-4">Modules</p>
                
                <button onclick="switchAdminView('dashboard')" id="nav-dashboard" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-[#5c4033] bg-[#fdf8f5] font-medium transition-colors">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </button>
                
                <button onclick="switchAdminView('pipeline')" id="nav-pipeline" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-500 hover:text-[#5c4033] hover:bg-[#fdf8f5] font-medium transition-colors">
                    <i data-lucide="kanban-square" class="w-5 h-5"></i> Pipeline
                </button>
                
                <button onclick="switchAdminView('jobs')" id="nav-jobs" class="nav-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-500 hover:text-[#5c4033] hover:bg-[#fdf8f5] font-medium transition-colors">
                    <i data-lucide="briefcase" class="w-5 h-5"></i> Jobs
                </button>
            </nav>

            <div class="p-4 border-t border-[#e8dcd5]">
                <div class="flex items-center gap-3 bg-[#fdf8f5] p-3 rounded-xl border border-[#f0e4dd]">
                    <div class="w-10 h-10 rounded-full bg-[#5c4033] text-white flex items-center justify-center font-bold">
                        <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 2)); ?>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $_SESSION['admin_role'] ?? 'HR'; ?></p>
                    </div>
                    <button onclick="adminLogout()" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 flex flex-col h-screen overflow-hidden">
            
            <!-- TOP HEADER -->
            <header class="h-20 bg-white border-b border-[#e8dcd5] flex items-center justify-between px-8 z-10 shadow-sm">
                <div class="flex items-center bg-[#f3f0ea] px-4 py-2 rounded-xl w-96 border border-transparent focus-within:border-[#cda896] focus-within:bg-white transition-all">
                    <i data-lucide="search" class="w-4 h-4 text-gray-400 mr-2"></i>
                    <input type="text" placeholder="Search applicants, jobs..." class="bg-transparent border-none outline-none w-full text-sm placeholder-gray-400">
                </div>
                
                <div class="flex items-center gap-4">
                    <button class="relative p-2 text-gray-400 hover:text-[#5c4033] transition-colors">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
            </header>

            <!-- SCROLLABLE VIEWS -->
            <div class="flex-1 overflow-y-auto p-8">

                <!-- DASHBOARD -->
                <section id="dashboard-view" class="admin-view active max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-2xl font-serif font-bold text-[#4a332a]">Welcome back, <?php echo $_SESSION['admin_name'] ? explode(' ', $_SESSION['admin_name'])[0] : 'Admin'; ?>!</h2>
                        <p class="text-gray-500">HR Dashboard Overview</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-2xl border border-[#e8dcd5] shadow-sm flex items-center gap-4">
                            <div class="bg-blue-50 text-blue-600 p-4 rounded-xl"><i data-lucide="users" class="w-6 h-6"></i></div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Applicants</p>
                                <h3 class="text-2xl font-bold text-[#4a332a]">0</h3>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-[#e8dcd5] shadow-sm flex items-center gap-4">
                            <div class="bg-amber-50 text-amber-600 p-4 rounded-xl"><i data-lucide="clock" class="w-6 h-6"></i></div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Under Review</p>
                                <h3 class="text-2xl font-bold text-[#4a332a]">0</h3>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-[#e8dcd5] shadow-sm flex items-center gap-4">
                            <div class="bg-purple-50 text-purple-600 p-4 rounded-xl"><i data-lucide="calendar" class="w-6 h-6"></i></div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Interviews Today</p>
                                <h3 class="text-2xl font-bold text-[#4a332a]">0</h3>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-2xl border border-[#e8dcd5] shadow-sm flex items-center gap-4">
                            <div class="bg-green-50 text-green-600 p-4 rounded-xl"><i data-lucide="check-circle" class="w-6 h-6"></i></div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Hired This Month</p>
                                <h3 class="text-2xl font-bold text-[#4a332a]">0</h3>
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-500 text-center py-8">Dashboard content loaded. Connect to database for live data.</p>
                </section>

                <!-- PIPELINE -->
                <section id="pipeline-view" class="admin-view h-full flex flex-col">
                    <div class="mb-6">
                        <h2 class="text-2xl font-serif font-bold text-[#4a332a]">Recruitment Pipeline</h2>
                        <p class="text-gray-500">Manage applicant status</p>
                    </div>
                    <p class="text-gray-500">Pipeline content will load from database</p>
                </section>

                <!-- JOBS -->
                <section id="jobs-view" class="admin-view max-w-6xl mx-auto">
                    <div class="mb-8">
                        <h2 class="text-2xl font-serif font-bold text-[#4a332a]">Job Postings</h2>
                        <p class="text-gray-500">Manage active openings</p>
                    </div>
                    <p class="text-gray-500">Job postings will load from database</p>
                </section>

            </div>
        </main>
    </div>

    <script>
        let loggedInAdmin = null;
        
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });

        function handleAdminLogin(e) {
            e.preventDefault();
            const errorBox = document.getElementById('admin-login-error');
            errorBox.classList.add('hidden');

            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;

            // Test with hardcoded credentials
            if (email === 'john.rey@gweens.com' && password === 'Admin@123!') {
                loggedInAdmin = { email, name: 'John Rey Baria', role: 'HR Team Leader' };
                document.getElementById('auth-layout').classList.add('hidden');
                document.getElementById('app-layout').classList.remove('hidden');
                document.getElementById('admin-login-form').reset();
            } else {
                errorBox.innerText = 'Invalid email or password';
                errorBox.classList.remove('hidden');
            }
        }

        function adminLogout() {
            loggedInAdmin = null;
            document.getElementById('app-layout').classList.add('hidden');
            document.getElementById('auth-layout').classList.remove('hidden');
        }

        function switchAdminView(viewId) {
            document.querySelectorAll('.admin-view').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('bg-[#fdf8f5]', 'text-[#5c4033]');
                btn.classList.add('text-gray-500');
            });

            document.getElementById(`${viewId}-view`).classList.add('active');
            const activeBtn = document.getElementById(`nav-${viewId}`);
            if (activeBtn) {
                activeBtn.classList.remove('text-gray-500');
                activeBtn.classList.add('bg-[#fdf8f5]', 'text-[#5c4033]');
            }
        }
    </script>
</body>
</html>
