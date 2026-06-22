<?php
// admin/settings.php
session_start();
require_once '../config/database.php';
requireLogin();

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'profile') {
                // Update profile
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
                $stmt->execute([$_POST['full_name'], $_POST['email'], $_SESSION['user_id']]);
                
                // Update password if provided
                if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
                    // Verify current password
                    $user = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                    $user->execute([$_SESSION['user_id']]);
                    $current = $user->fetch();
                    
                    if (password_verify($_POST['current_password'], $current['password'])) {
                        if ($_POST['new_password'] === $_POST['confirm_password']) {
                            $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                            $passStmt->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
                            $message = 'Profile and password updated successfully';
                        } else {
                            throw new Exception('New passwords do not match');
                        }
                    } else {
                        throw new Exception('Current password is incorrect');
                    }
                } else {
                    $message = 'Profile updated successfully';
                }
                
                // Update session
                $_SESSION['full_name'] = $_POST['full_name'];
                
            } elseif ($_POST['action'] === 'system' && hasRole('admin')) {
                // Update system settings
                $settings = [
                    'company_name' => $_POST['company_name'],
                    'company_email' => $_POST['company_email'],
                    'company_phone' => $_POST['company_phone'],
                    'company_address' => $_POST['company_address'],
                    'timezone' => $_POST['timezone'],
                    'date_format' => $_POST['date_format'],
                    'session_timeout' => $_POST['session_timeout']
                ];
                
                // Save to file or database
                file_put_contents('../config/settings.json', json_encode($settings, JSON_PRETTY_PRINT));
                $message = 'System settings updated successfully';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current user data
$userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$currentUser = $userStmt->fetch();

// Get system settings
$systemSettings = [];
if (file_exists('../config/settings.json')) {
    $systemSettings = json_decode(file_get_contents('../config/settings.json'), true);
} else {
    $systemSettings = [
        'company_name' => 'Silverleaf Academy',
        'company_email' => 'info@silverleaf.ac.tz',
        'company_phone' => '+255 123 456 789',
        'company_address' => 'Dar es Salaam, Tanzania',
        'timezone' => 'Africa/Dar_es_Salaam',
        'date_format' => 'Y-m-d',
        'session_timeout' => '30'
    ];
}

// Get activity log
$activityLog = $pdo->query("
    SELECT u.username, u.full_name, u.last_login 
    FROM users u 
    WHERE u.last_login IS NOT NULL 
    ORDER BY u.last_login DESC 
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { font-family: 'Merriweather Sans', sans-serif; }
        body { background-color: #ECECEC; }
        .sidebar { background: linear-gradient(180deg, #002368 0%, #001845 100%); }
        .settings-tab { transition: all 0.3s ease; }
        .settings-tab.active { background-color: #002368; color: white; }
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
                    <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-lg">
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
                <h1 class="text-3xl font-bold text-[#002368]">Settings</h1>
                <p class="text-gray-600">Manage your account and system preferences</p>
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
            
            <!-- Settings Tabs -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-4 p-4">
                        <button onclick="showTab('profile')" id="tab-profile-btn" class="settings-tab px-4 py-2 rounded-lg font-semibold active">Profile Settings</button>
                        <?php if (hasRole('admin')): ?>
                        <button onclick="showTab('system')" id="tab-system-btn" class="settings-tab px-4 py-2 rounded-lg font-semibold">System Settings</button>
                        <button onclick="showTab('security')" id="tab-security-btn" class="settings-tab px-4 py-2 rounded-lg font-semibold">Security</button>
                        <button onclick="showTab('backup')" id="tab-backup-btn" class="settings-tab px-4 py-2 rounded-lg font-semibold">Backup</button>
                        <?php endif; ?>
                        <button onclick="showTab('activity')" id="tab-activity-btn" class="settings-tab px-4 py-2 rounded-lg font-semibold">Activity Log</button>
                    </nav>
                </div>
                
                <!-- Profile Settings Tab -->
                <div id="tab-profile" class="p-6">
                    <h2 class="text-xl font-bold text-[#002368] mb-6">Profile Settings</h2>
                    <form method="POST" class="max-w-2xl">
                        <input type="hidden" name="action" value="profile">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled 
                                       class="w-full px-4 py-2 rounded-lg border-2 border-gray-300 bg-gray-100">
                            </div>
                            
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required 
                                       class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required 
                                       class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <h3 class="text-lg font-bold text-[#002368] mb-4">Change Password</h3>
                                <p class="text-sm text-gray-500 mb-4">Leave blank to keep current password</p>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[#002368] font-semibold mb-2">Current Password</label>
                                        <input type="password" name="current_password" 
                                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-[#002368] font-semibold mb-2">New Password</label>
                                        <input type="password" name="new_password" 
                                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-[#002368] font-semibold mb-2">Confirm New Password</label>
                                        <input type="password" name="confirm_password" 
                                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" 
                                        class="bg-[#002368] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition">
                                    Update Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- System Settings Tab (Admin only) -->
                <?php if (hasRole('admin')): ?>
                <div id="tab-system" class="p-6 hidden">
                    <h2 class="text-xl font-bold text-[#002368] mb-6">System Settings</h2>
                    <form method="POST" class="max-w-2xl">
                        <input type="hidden" name="action" value="system">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Company Name</label>
                                <input type="text" name="company_name" value="<?php echo htmlspecialchars($systemSettings['company_name']); ?>" 
                                       class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Company Email</label>
                                <input type="email" name="company_email" value="<?php echo htmlspecialchars($systemSettings['company_email']); ?>" 
                                       class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Company Phone</label>
                                <input type="text" name="company_phone" value="<?php echo htmlspecialchars($systemSettings['company_phone']); ?>" 
                                       class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Company Address</label>
                                <textarea name="company_address" rows="3" 
                                          class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none"><?php echo htmlspecialchars($systemSettings['company_address']); ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[#002368] font-semibold mb-2">Timezone</label>
                                    <select name="timezone" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                                        <option value="Africa/Dar_es_Salaam" <?php echo $systemSettings['timezone'] == 'Africa/Dar_es_Salaam' ? 'selected' : ''; ?>>Dar es Salaam</option>
                                        <option value="Africa/Nairobi" <?php echo $systemSettings['timezone'] == 'Africa/Nairobi' ? 'selected' : ''; ?>>Nairobi</option>
                                        <option value="Africa/Kampala" <?php echo $systemSettings['timezone'] == 'Africa/Kampala' ? 'selected' : ''; ?>>Kampala</option>
                                        <option value="UTC" <?php echo $systemSettings['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-[#002368] font-semibold mb-2">Date Format</label>
                                    <select name="date_format" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                                        <option value="Y-m-d" <?php echo $systemSettings['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="d/m/Y" <?php echo $systemSettings['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                        <option value="m/d/Y" <?php echo $systemSettings['date_format'] == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-[#002368] font-semibold mb-2">Session Timeout (minutes)</label>
                                <input type="number" name="session_timeout" value="<?php echo $systemSettings['session_timeout']; ?>" min="5" max="480"
                                       class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" 
                                        class="bg-[#002368] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition">
                                    Save System Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Security Tab -->
                <div id="tab-security" class="p-6 hidden">
                    <h2 class="text-xl font-bold text-[#002368] mb-6">Security Settings</h2>
                    
                    <div class="space-y-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-[#002368] mb-2">Two-Factor Authentication</h3>
                            <p class="text-sm text-gray-600 mb-3">Enhance your account security with 2FA</p>
                            <button class="bg-[#002368] text-white px-4 py-2 rounded-lg hover:bg-[#FFC952] hover:text-[#002368] transition">
                                Enable 2FA
                            </button>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-[#002368] mb-2">Login History</h3>
                            <p class="text-sm text-gray-600 mb-3">Review recent login attempts</p>
                            <a href="login_history.php" class="text-[#002368] hover:text-[#FFC952] transition">
                                View Login History <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-[#002368] mb-2">Session Management</h3>
                            <p class="text-sm text-gray-600 mb-3">Manage active sessions</p>
                            <button class="text-red-600 hover:text-red-800 transition" onclick="terminateAllSessions()">
                                Terminate All Other Sessions
                            </button>
                        </div>
                        
                        <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                            <h3 class="font-semibold text-yellow-800 mb-2">Danger Zone</h3>
                            <p class="text-sm text-yellow-700 mb-3">Irreversible actions</p>
                            <button class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition" 
                                    onclick="if(confirm('Are you sure? This will log you out and require password reset.')) { deactivateAccount() }">
                                Deactivate Account
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Backup Tab -->
                <div id="tab-backup" class="p-6 hidden">
                    <h2 class="text-xl font-bold text-[#002368] mb-6">Backup & Restore</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <i class="fas fa-database text-4xl text-[#002368] mb-4"></i>
                            <h3 class="font-semibold text-[#002368] mb-2">Database Backup</h3>
                            <p class="text-sm text-gray-600 mb-4">Create a complete backup of all employee data</p>
                            <button onclick="createBackup()" class="bg-[#002368] text-white px-4 py-2 rounded-lg hover:bg-[#FFC952] hover:text-[#002368] transition">
                                <i class="fas fa-download mr-2"></i>Download Backup
                            </button>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <i class="fas fa-upload text-4xl text-[#002368] mb-4"></i>
                            <h3 class="font-semibold text-[#002368] mb-2">Restore Data</h3>
                            <p class="text-sm text-gray-600 mb-4">Restore from a previous backup file</p>
                            <input type="file" id="restoreFile" class="hidden" accept=".sql,.zip">
                            <button onclick="document.getElementById('restoreFile').click()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-upload mr-2"></i>Upload Backup
                            </button>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <i class="fas fa-history text-4xl text-[#002368] mb-4"></i>
                            <h3 class="font-semibold text-[#002368] mb-2">Scheduled Backups</h3>
                            <p class="text-sm text-gray-600 mb-4">Configure automatic backups</p>
                            <select class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                                <option value="daily">Daily</option>
                                <option value="weekly" selected>Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <i class="fas fa-file-archive text-4xl text-[#002368] mb-4"></i>
                            <h3 class="font-semibold text-[#002368] mb-2">Recent Backups</h3>
                            <div class="space-y-2 text-sm">
                                <p class="text-gray-600">No backups found</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Activity Log Tab -->
                <div id="tab-activity" class="p-6 hidden">
                    <h2 class="text-xl font-bold text-[#002368] mb-6">Recent Activity</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-3 px-4">User</th>
                                    <th class="text-left py-3 px-4">Last Login</th>
                                    <th class="text-left py-3 px-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activityLog as $log): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-4">
                                        <div class="font-semibold"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                        <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($log['username']); ?></div>
                                    </td>
                                    <td class="py-3 px-4"><?php echo date('M d, Y H:i:s', strtotime($log['last_login'])); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Active</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.getElementById('tab-profile').classList.add('hidden');
            document.getElementById('tab-system')?.classList.add('hidden');
            document.getElementById('tab-security')?.classList.add('hidden');
            document.getElementById('tab-backup')?.classList.add('hidden');
            document.getElementById('tab-activity').classList.add('hidden');
            
            // Remove active class from all buttons
            document.getElementById('tab-profile-btn').classList.remove('active');
            document.getElementById('tab-system-btn')?.classList.remove('active');
            document.getElementById('tab-security-btn')?.classList.remove('active');
            document.getElementById('tab-backup-btn')?.classList.remove('active');
            document.getElementById('tab-activity-btn').classList.remove('active');
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName + '-btn').classList.add('active');
        }
        
        function createBackup() {
            window.location.href = 'backup.php?action=create';
        }
        
        function terminateAllSessions() {
            if (confirm('Are you sure you want to terminate all other sessions?')) {
                window.location.href = 'terminate_sessions.php';
            }
        }
        
        function deactivateAccount() {
            window.location.href = 'deactivate_account.php';
        }
        
        document.getElementById('restoreFile')?.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                if (confirm('Upload and restore from this backup? This will overwrite current data.')) {
                    // Handle file upload
                    const formData = new FormData();
                    formData.append('backup', e.target.files[0]);
                    
                    fetch('restore_backup.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            alert('Backup restored successfully');
                            location.reload();
                        } else {
                            alert('Error restoring backup');
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>