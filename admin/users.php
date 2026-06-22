<?php
// admin/users.php
session_start();
require_once '../config/database.php';
requireLogin();
requireRole('admin'); // Only admin can access user management

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                // Validate
                if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email']) || empty($_POST['full_name'])) {
                    throw new Exception('All fields are required');
                }
                
                // Check if username exists
                $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                $check->execute([$_POST['username']]);
                if ($check->fetch()) {
                    throw new Exception('Username already exists');
                }
                
                // Check if email exists
                $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                $check->execute([$_POST['email']]);
                if ($check->fetch()) {
                    throw new Exception('Email already exists');
                }
                
                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, email, full_name, role) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute([
                    $_POST['username'],
                    $hashedPassword,
                    $_POST['email'],
                    $_POST['full_name'],
                    $_POST['role']
                ]);
                
                $message = 'User created successfully';
                
            } elseif ($_POST['action'] === 'edit') {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET email = ?, full_name = ?, role = ?, is_active = ?
                    WHERE user_id = ?
                ");
                
                $stmt->execute([
                    $_POST['email'],
                    $_POST['full_name'],
                    $_POST['role'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['user_id']
                ]);
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $passStmt->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['user_id']]);
                }
                
                $message = 'User updated successfully';
                
            } elseif ($_POST['action'] === 'delete') {
                if ($_POST['user_id'] == $_SESSION['user_id']) {
                    throw new Exception('You cannot delete your own account');
                }
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);
                $message = 'User deleted successfully';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get all users
$users = $pdo->query("
    SELECT user_id, username, email, full_name, role, is_active, last_login, created_at 
    FROM users 
    ORDER BY created_at DESC
")->fetchAll();

// Get statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$hrCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'hr_manager'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Merriweather Sans', sans-serif; }
        body { background-color: #ECECEC; }
        .sidebar { background: linear-gradient(180deg, #002368 0%, #001845 100%); }
        .modal { transition: opacity 0.3s ease; }
        .modal.hidden { display: none; }
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
                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                        <i class="fas fa-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="employees.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-white hover:bg-opacity-10 rounded-lg transition">
                        <i class="fas fa-users"></i>
                        <span>Employees</span>
                    </a>
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-lg">
                        <i class="fas fa-user-cog"></i>
                        <span>Users</span>
                    </a>
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
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-[#002368]">User Management</h1>
                        <p class="text-gray-600">Manage system users and permissions</p>
                    </div>
                    <button onclick="openAddModal()" 
                            class="bg-[#002368] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition">
                        <i class="fas fa-plus mr-2"></i>Add New User
                    </button>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Users</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $totalUsers; ?></p>
                        </div>
                        <div class="bg-[#80BFEC] bg-opacity-20 p-3 rounded-full">
                            <i class="fas fa-users text-2xl text-[#002368]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Active Users</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo $activeUsers; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-check-circle text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Admins</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $adminCount; ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-crown text-2xl text-purple-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">HR Managers</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $hrCount; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-user-tie text-2xl text-blue-600"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-[#002368] text-white">
                            <tr>
                                <th class="text-left py-4 px-6">ID</th>
                                <th class="text-left py-4 px-6">Username</th>
                                <th class="text-left py-4 px-6">Full Name</th>
                                <th class="text-left py-4 px-6">Email</th>
                                <th class="text-left py-4 px-6">Role</th>
                                <th class="text-left py-4 px-6">Status</th>
                                <th class="text-left py-4 px-6">Last Login</th>
                                <th class="text-left py-4 px-6">Created</th>
                                <th class="text-left py-4 px-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-4 px-6">#<?php echo $user['user_id']; ?></td>
                                <td class="py-4 px-6 font-semibold"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-4 px-6">
                                    <span class="px-2 py-1 rounded-full text-xs 
                                        <?php 
                                        if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                                        elseif ($user['role'] === 'hr_manager') echo 'bg-blue-100 text-blue-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2 py-1 rounded-full text-xs <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                </td>
                                <td class="py-4 px-6"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="py-4 px-6">
                                    <div class="flex space-x-3">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                class="text-blue-600 hover:text-blue-800 transition" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button onclick="deleteUser(<?php echo $user['user_id']; ?>)" 
                                                class="text-red-600 hover:text-red-800 transition" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h2 class="text-2xl font-bold text-[#002368] mb-4">Add New User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Username *</label>
                        <input type="text" name="username" required 
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Full Name *</label>
                        <input type="text" name="full_name" required 
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Email *</label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Password *</label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Min 8 characters with letters and numbers</p>
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Role *</label>
                        <select name="role" required class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="viewer">Viewer</option>
                            <option value="hr_manager">HR Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeAddModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-[#002368] text-white rounded-lg hover:bg-[#FFC952] hover:text-[#002368] transition">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h2 class="text-2xl font-bold text-[#002368] mb-4">Edit User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Username</label>
                        <input type="text" id="edit_username" disabled 
                               class="w-full px-4 py-2 rounded-lg border-2 border-gray-300 bg-gray-100">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Full Name *</label>
                        <input type="text" name="full_name" id="edit_full_name" required 
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Email *</label>
                        <input type="email" name="email" id="edit_email" required 
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" 
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Role *</label>
                        <select name="role" id="edit_role" required class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="viewer">Viewer</option>
                            <option value="hr_manager">HR Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active" class="w-5 h-5 text-[#002368] border-2 border-[#80BFEC] rounded">
                        <label for="edit_is_active" class="ml-2 text-[#002368] font-semibold">Active Account</label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-[#002368] text-white rounded-lg hover:bg-[#FFC952] hover:text-[#002368] transition">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Form -->
    <form id="deleteForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete_user_id">
    </form>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }
        
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                document.getElementById('delete_user_id').value = userId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.add('hidden');
            }
        }
    </script>
</body>
</html>