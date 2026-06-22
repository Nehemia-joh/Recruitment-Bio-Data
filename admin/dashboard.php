<?php
// admin/dashboard.php
session_start();
require_once '../config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get statistics
$stats = [];
$stats['total_employees'] = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$stats['total_male'] = $pdo->query("SELECT COUNT(*) FROM employees WHERE gender = 'MALE'")->fetchColumn();
$stats['total_female'] = $pdo->query("SELECT COUNT(*) FROM employees WHERE gender = 'FEMALE'")->fetchColumn();
$stats['recent_hires'] = $pdo->query("SELECT COUNT(*) FROM employees WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// Get recent employees
$recentStmt = $pdo->query("
    SELECT employee_id, surname, first_name, position, created_at 
    FROM employees 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentEmployees = $recentStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            font-family: 'Merriweather Sans', sans-serif;
        }
        body {
            background-color: #ECECEC;
        }
        .sidebar {
            background: linear-gradient(180deg, #002368 0%, #001845 100%);
        }
        .stat-card {
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar w-64 min-h-screen text-white fixed">
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-6">Silverleaf</h2>
                <p class="text-sm opacity-75 mb-6">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                
                <nav class="space-y-2">
                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-lg">
                        <i class="fas fa-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="employees.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                        <i class="fas fa-users"></i>
                        <span>Employees</span>
                    </a>
                    <?php if (hasRole('admin')): ?>
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                        <i class="fas fa-user-cog"></i>
                        <span>Users</span>
                    </a>
                    <?php endif; ?>
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <hr class="border-white border-opacity-20 my-4">
                    <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-10 rounded-lg transition text-red-300">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 ml-64 p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-[#002368]">Dashboard</h1>
                <p class="text-gray-600">Welcome back! Here's what's happening with your employees.</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#FFC952]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Employees</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $stats['total_employees']; ?></p>
                        </div>
                        <div class="bg-[#80BFEC] bg-opacity-20 p-3 rounded-full">
                            <i class="fas fa-users text-2xl text-[#002368]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#002368]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Male Employees</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $stats['total_male']; ?></p>
                        </div>
                        <div class="bg-[#FFC952] bg-opacity-20 p-3 rounded-full">
                            <i class="fas fa-male text-2xl text-[#002368]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#80BFEC]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Female Employees</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $stats['total_female']; ?></p>
                        </div>
                        <div class="bg-[#002368] bg-opacity-10 p-3 rounded-full">
                            <i class="fas fa-female text-2xl text-[#002368]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-[#FFC952]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Recent Hires (30d)</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $stats['recent_hires']; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-user-plus text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Employees -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-[#002368] mb-4">Recent Employees</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-3 px-4">ID</th>
                                <th class="text-left py-3 px-4">Name</th>
                                <th class="text-left py-3 px-4">Position</th>
                                <th class="text-left py-3 px-4">Joined</th>
                                <th class="text-left py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEmployees as $emp): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">#<?php echo $emp['employee_id']; ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($emp['surname'] . ' ' . $emp['first_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($emp['position']); ?></td>
                                <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($emp['created_at'])); ?></td>
                                <td class="py-3 px-4">
                                    <a href="view_employee.php?id=<?php echo $emp['employee_id']; ?>" 
                                       class="text-[#002368] hover:text-[#FFC952] transition mr-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (hasRole('admin') || hasRole('hr_manager')): ?>
                                    <a href="edit_employee.php?id=<?php echo $emp['employee_id']; ?>" 
                                       class="text-green-600 hover:text-green-800 transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>