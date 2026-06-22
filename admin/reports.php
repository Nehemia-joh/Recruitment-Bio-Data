<?php
// admin/reports.php
session_start();
require_once '../config/database.php';
requireLogin();

$pdo = getDBConnection();

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'summary';

// Get statistics for reports
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$totalMale = $pdo->query("SELECT COUNT(*) FROM employees WHERE gender = 'MALE'")->fetchColumn();
$totalFemale = $pdo->query("SELECT COUNT(*) FROM employees WHERE gender = 'FEMALE'")->fetchColumn();

// Get employees by position
$positionStats = $pdo->query("
    SELECT position, COUNT(*) as count 
    FROM employees 
    WHERE position IS NOT NULL 
    GROUP BY position 
    ORDER BY count DESC
")->fetchAll();

// Get employees by month (hiring trend)
$hiringTrend = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as hires
    FROM employees
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

// Get age distribution
$ageGroups = $pdo->query("
    SELECT 
        CASE 
            WHEN age < 25 THEN 'Under 25'
            WHEN age BETWEEN 25 AND 35 THEN '25-35'
            WHEN age BETWEEN 36 AND 45 THEN '36-45'
            WHEN age BETWEEN 46 AND 55 THEN '46-55'
            ELSE 'Over 55'
        END as age_group,
        COUNT(*) as count
    FROM employees
    WHERE age IS NOT NULL
    GROUP BY age_group
    ORDER BY age_group
")->fetchAll();

// Get qualification stats
$qualStats = $pdo->query("
    SELECT level, COUNT(*) as count 
    FROM qualifications 
    GROUP BY level 
    ORDER BY count DESC
")->fetchAll();

// Get marital status stats
$maritalStats = $pdo->query("
    SELECT marital_status, COUNT(*) as count 
    FROM employees 
    WHERE marital_status IS NOT NULL
    GROUP BY marital_status
")->fetchAll();

// Get department/position based on date filter
$filteredEmployees = $pdo->prepare("
    SELECT * FROM employees 
    WHERE DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
");
$filteredEmployees->execute([$date_from, $date_to]);
$filteredCount = $filteredEmployees->rowCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Merriweather Sans', sans-serif; }
        body { background-color: #ECECEC; }
        .sidebar { background: linear-gradient(180deg, #002368 0%, #001845 100%); }
        .report-card { transition: transform 0.3s ease; }
        .report-card:hover { transform: translateY(-5px); }
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
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 bg-white bg-opacity-20 rounded-lg">
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
                        <h1 class="text-3xl font-bold text-[#002368]">Reports & Analytics</h1>
                        <p class="text-gray-600">Comprehensive employee data analysis</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="exportAsPDF()" 
                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                        </button>
                        <button onclick="exportAsExcel()" 
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <form method="GET" class="flex items-end space-x-4">
                    <div>
                        <label class="block text-sm font-semibold text-[#002368] mb-2">Date From</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                               class="px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#002368] mb-2">Date To</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                               class="px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#002368] mb-2">Report Type</label>
                        <select name="report_type" class="px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                            <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                            <option value="analytical" <?php echo $report_type == 'analytical' ? 'selected' : ''; ?>>Analytical Report</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-[#002368] text-white px-6 py-2 rounded-lg hover:bg-[#FFC952] hover:text-[#002368] transition">
                        <i class="fas fa-filter mr-2"></i>Apply Filter
                    </button>
                </form>
            </div>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 report-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Total Employees</p>
                            <p class="text-3xl font-bold text-[#002368]"><?php echo $totalEmployees; ?></p>
                        </div>
                        <div class="bg-[#80BFEC] bg-opacity-20 p-3 rounded-full">
                            <i class="fas fa-users text-2xl text-[#002368]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 report-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Male/Female Ratio</p>
                            <p class="text-2xl font-bold text-[#002368]"><?php echo $totalMale; ?> : <?php echo $totalFemale; ?></p>
                        </div>
                        <div class="flex space-x-1">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">M: <?php echo $totalMale; ?></span>
                            <span class="bg-pink-100 text-pink-800 px-2 py-1 rounded text-xs">F: <?php echo $totalFemale; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 report-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Filtered Period</p>
                            <p class="text-2xl font-bold text-[#002368]"><?php echo $filteredCount; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-calendar-check text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6 report-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm">Avg Age</p>
                            <p class="text-2xl font-bold text-[#002368]">
                                <?php 
                                $avgAge = $pdo->query("SELECT AVG(age) FROM employees WHERE age IS NOT NULL")->fetchColumn();
                                echo round($avgAge, 1) . ' years';
                                ?>
                            </p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-full">
                            <i class="fas fa-clock text-2xl text-orange-600"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Gender Distribution Chart -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Gender Distribution</h3>
                    <canvas id="genderChart" height="200"></canvas>
                </div>
                
                <!-- Position Distribution Chart -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Top Positions</h3>
                    <canvas id="positionChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Second Row Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Age Distribution -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Age Distribution</h3>
                    <canvas id="ageChart" height="200"></canvas>
                </div>
                
                <!-- Hiring Trend -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Hiring Trend (Last 12 Months)</h3>
                    <canvas id="hiringChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Detailed Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Qualification Stats -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Qualifications Breakdown</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-2">Qualification Level</th>
                                    <th class="text-right py-2">Count</th>
                                    <th class="text-right py-2">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalQuals = array_sum(array_column($qualStats, 'count'));
                                foreach ($qualStats as $stat): 
                                $percentage = $totalQuals > 0 ? round(($stat['count'] / $totalQuals) * 100, 1) : 0;
                                ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><?php echo htmlspecialchars($stat['level']); ?></td>
                                    <td class="text-right py-2"><?php echo $stat['count']; ?></td>
                                    <td class="text-right py-2"><?php echo $percentage; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Marital Status Stats -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Marital Status</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-2">Status</th>
                                    <th class="text-right py-2">Count</th>
                                    <th class="text-right py-2">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalMarital = array_sum(array_column($maritalStats, 'count'));
                                foreach ($maritalStats as $stat): 
                                $percentage = $totalMarital > 0 ? round(($stat['count'] / $totalMarital) * 100, 1) : 0;
                                ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2"><?php echo htmlspecialchars($stat['marital_status'] ?: 'Not Specified'); ?></td>
                                    <td class="text-right py-2"><?php echo $stat['count']; ?></td>
                                    <td class="text-right py-2"><?php echo $percentage; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Export Options -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="export_report.php?type=csv&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" 
                   class="bg-gray-100 hover:bg-gray-200 text-[#002368] p-4 rounded-lg text-center font-semibold transition">
                    <i class="fas fa-file-csv mr-2"></i>Export as CSV
                </a>
                <a href="export_report.php?type=excel&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" 
                   class="bg-gray-100 hover:bg-gray-200 text-[#002368] p-4 rounded-lg text-center font-semibold transition">
                    <i class="fas fa-file-excel mr-2"></i>Export as Excel
                </a>
                <a href="export_report.php?type=print&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" target="_blank"
                   class="bg-gray-100 hover:bg-gray-200 text-[#002368] p-4 rounded-lg text-center font-semibold transition">
                    <i class="fas fa-print mr-2"></i>Print Report
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Gender Chart
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: ['Male (<?php echo $totalMale; ?>)', 'Female (<?php echo $totalFemale; ?>)'],
                datasets: [{
                    data: [<?php echo $totalMale; ?>, <?php echo $totalFemale; ?>],
                    backgroundColor: ['#002368', '#80BFEC'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Position Chart
        const positionCtx = document.getElementById('positionChart').getContext('2d');
        new Chart(positionCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    $topPositions = array_slice($positionStats, 0, 5);
                    foreach ($topPositions as $pos) {
                        echo "'" . addslashes(substr($pos['position'], 0, 20)) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Number of Employees',
                    data: [<?php foreach ($topPositions as $pos) { echo $pos['count'] . ','; } ?>],
                    backgroundColor: '#FFC952',
                    borderColor: '#002368',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Age Chart
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($ageGroups as $age) { echo "'" . $age['age_group'] . "',"; } ?>],
                datasets: [{
                    label: 'Number of Employees',
                    data: [<?php foreach ($ageGroups as $age) { echo $age['count'] . ','; } ?>],
                    backgroundColor: '#002368',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Hiring Trend Chart
        const hiringCtx = document.getElementById('hiringChart').getContext('2d');
        new Chart(hiringCtx, {
            type: 'line',
            data: {
                labels: [<?php 
                    foreach (array_reverse($hiringTrend) as $trend) {
                        echo "'" . date('M Y', strtotime($trend['month'] . '-01')) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'New Hires',
                    data: [<?php foreach (array_reverse($hiringTrend) as $trend) { echo $trend['hires'] . ','; } ?>],
                    borderColor: '#FFC952',
                    backgroundColor: 'rgba(255, 201, 82, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        function exportAsPDF() {
            window.open('export_report.php?type=pdf&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>', '_blank');
        }
        
        function exportAsExcel() {
            window.open('export_report.php?type=excel&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>', '_blank');
        }
    </script>
</body>
</html>