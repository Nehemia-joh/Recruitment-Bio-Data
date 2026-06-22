<?php
// admin/export_report.php
session_start();
require_once '../config/database.php';
requireLogin();

$pdo = getDBConnection();
$type = $_GET['type'] ?? 'csv';
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

// Get data
$employees = $pdo->prepare("
    SELECT * FROM employees 
    WHERE DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
");
$employees->execute([$from, $to]);
$data = $employees->fetchAll();

if ($type === 'csv') {
    // CSV Export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employee_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Gender', 'Position', 'Phone', 'Email', 'Date Joined']);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['employee_id'],
            $row['surname'] . ' ' . $row['first_name'],
            $row['gender'],
            $row['position'],
            $row['mobile_phone'],
            $row['email'],
            $row['created_at']
        ]);
    }
    fclose($output);
    
} elseif ($type === 'excel') {
    // For Excel, we'll use CSV with .xls extension
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="employee_report_' . date('Y-m-d') . '.xls"');
    
    echo "ID\tName\tGender\tPosition\tPhone\tEmail\tDate Joined\n";
    foreach ($data as $row) {
        echo $row['employee_id'] . "\t" .
             $row['surname'] . ' ' . $row['first_name'] . "\t" .
             $row['gender'] . "\t" .
             $row['position'] . "\t" .
             $row['mobile_phone'] . "\t" .
             $row['email'] . "\t" .
             $row['created_at'] . "\n";
    }
    
} elseif ($type === 'pdf') {
    // For PDF, you'd need a library like TCPDF or Dompdf
    // This is a placeholder
    echo "PDF export would be here. Install a PDF library for actual export.";
    
} elseif ($type === 'print') {
    // Print-friendly view
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Employee Report</title>
        <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #002368; color: white; padding: 10px; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <h2>Employee Report (<?php echo $from; ?> to <?php echo $to; ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Position</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Date Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo $row['employee_id']; ?></td>
                    <td><?php echo $row['surname'] . ' ' . $row['first_name']; ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td><?php echo $row['position']; ?></td>
                    <td><?php echo $row['mobile_phone']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>window.print();</script>
    </body>
    </html>
    <?php
}
?>