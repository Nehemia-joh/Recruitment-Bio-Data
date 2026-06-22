<?php
// admin/employees.php
session_start();
require_once '../config/database.php';
requireLogin();

$pdo = getDBConnection();

// Handle search and filters
$search = $_GET['search'] ?? '';
$gender = $_GET['gender'] ?? '';
$position = $_GET['position'] ?? '';

$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM qualifications WHERE employee_id = e.employee_id) as qual_count 
          FROM employees e WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (e.surname LIKE ? OR e.first_name LIKE ? OR e.identification_no LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($gender) {
    $query .= " AND e.gender = ?";
    $params[] = $gender;
}

if ($position) {
    $query .= " AND e.position LIKE ?";
    $params[] = "%$position%";
}

$query .= " ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Get unique positions for filter
$positions = $pdo->query("SELECT DISTINCT position FROM employees ORDER BY position")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - Employee Management</title>
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
                    <a href="employees.php" class="flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-lg">
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
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-[#002368]">Employee Management</h1>
                        <p class="text-gray-600">View and manage all employee records</p>
                    </div>
                    <?php if (hasRole('admin') || hasRole('hr_manager')): ?>
                    <a href="../index.php" target="_blank" 
                       class="bg-[#002368] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition">
                        <i class="fas fa-plus mr-2"></i>Add New Employee
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-[#002368] mb-2">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name or ID..."
                               class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-[#002368] mb-2">Gender</label>
                        <select name="gender" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="">All</option>
                            <option value="MALE" <?php echo $gender === 'MALE' ? 'selected' : ''; ?>>Male</option>
                            <option value="FEMALE" <?php echo $gender === 'FEMALE' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-[#002368] mb-2">Position</label>
                        <select name="position" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="">All Positions</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?php echo htmlspecialchars($pos); ?>" <?php echo $position === $pos ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pos); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-[#002368] text-white px-4 py-2 rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Employee Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-[#002368] text-white">
                            <tr>
                                <th class="text-left py-4 px-6">ID</th>
                                <th class="text-left py-4 px-6">Name</th>
                                <th class="text-left py-4 px-6">Gender</th>
                                <th class="text-left py-4 px-6">Position</th>
                                <th class="text-left py-4 px-6">NID</th>
                                <th class="text-left py-4 px-6">Phone</th>
                                <th class="text-left py-4 px-6">Quals</th>
                                <th class="text-left py-4 px-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-500">
                                    No employees found
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $emp): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-4 px-6 font-semibold">#<?php echo $emp['employee_id']; ?></td>
                                    <td class="py-4 px-6">
                                        <?php echo htmlspecialchars($emp['surname'] . ' ' . $emp['first_name']); ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2 py-1 rounded-full text-xs <?php echo $emp['gender'] === 'MALE' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                            <?php echo $emp['gender']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($emp['position']); ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($emp['identification_no']); ?></td>
                                    <td class="py-4 px-6"><?php echo htmlspecialchars($emp['mobile_phone']); ?></td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="bg-[#FFC952] text-[#002368] px-2 py-1 rounded-full text-xs font-bold">
                                            <?php echo $emp['qual_count']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex space-x-3">
                                            <a href="view_employee.php?id=<?php echo $emp['employee_id']; ?>" 
                                               class="text-[#002368] hover:text-[#FFC952] transition" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (hasRole('admin') || hasRole('hr_manager')): ?>
                                            <a href="edit_employee.php?id=<?php echo $emp['employee_id']; ?>" 
                                               class="text-green-600 hover:text-green-800 transition" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_employee.php?id=<?php echo $emp['employee_id']; ?>" 
                                               class="text-red-600 hover:text-red-800 transition" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="export_employee.php?id=<?php echo $emp['employee_id']; ?>" 
                                               class="text-gray-600 hover:text-gray-800 transition" title="Export">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>