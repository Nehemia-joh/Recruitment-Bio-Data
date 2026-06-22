<?php
// admin/view_employee.php - Complete single file with download functionality
session_start();
require_once '../config/database.php';
requireLogin();

$pdo = getDBConnection();

// Handle file download
if (isset($_GET['download'])) {
    $file_id = $_GET['download'];
    $fileStmt = $pdo->prepare("SELECT * FROM employee_documents WHERE doc_id = ?");
    $fileStmt->execute([$file_id]);
    $file = $fileStmt->fetch();
    
    if ($file) {
        $filepath = '../' . $file['file_path'];
        if (file_exists($filepath)) {
            header('Content-Type: ' . $file['mime_type']);
            header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
            header('Content-Length: ' . $file['file_size_bytes']);
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            readfile($filepath);
            exit();
        }
    }
}

// Handle photo download
if (isset($_GET['download_photo'])) {
    $emp_id = $_GET['download_photo'];
    $photoStmt = $pdo->prepare("SELECT * FROM employee_documents WHERE employee_id = ? AND document_type = 'Passport Photo'");
    $photoStmt->execute([$emp_id]);
    $photo = $photoStmt->fetch();
    
    if ($photo) {
        $filepath = '../' . $photo['file_path'];
        if (file_exists($filepath)) {
            header('Content-Type: ' . $photo['mime_type']);
            header('Content-Disposition: attachment; filename="passport_photo_' . $emp_id . '.jpg"');
            header('Content-Length: ' . $photo['file_size_bytes']);
            readfile($filepath);
            exit();
        }
    }
}

// Handle multiple files download as ZIP
if (isset($_GET['download_all'])) {
    $emp_id = $_GET['download_all'];
    $filesStmt = $pdo->prepare("SELECT * FROM employee_documents WHERE employee_id = ?");
    $filesStmt->execute([$emp_id]);
    $files = $filesStmt->fetchAll();
    
    if (count($files) > 0) {
        // Create ZIP file
        $zip = new ZipArchive();
        $zipName = tempnam(sys_get_temp_dir(), 'emp_files_') . '.zip';
        
        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $filepath = '../' . $file['file_path'];
                if (file_exists($filepath)) {
                    $zip->addFile($filepath, $file['document_type'] . '_' . $file['original_name']);
                }
            }
            $zip->close();
            
            // Download ZIP
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="employee_' . $emp_id . '_documents.zip"');
            header('Content-Length: ' . filesize($zipName));
            readfile($zipName);
            unlink($zipName);
            exit();
        }
    }
}

$employee_id = $_GET['id'] ?? 0;

// Fetch employee details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: employees.php?error=Employee not found');
    exit();
}

// Fetch related data
$documents = $pdo->prepare("SELECT * FROM employee_documents WHERE employee_id = ? ORDER BY document_type");
$documents->execute([$employee_id]);

$spouse = $pdo->prepare("SELECT * FROM spouses WHERE employee_id = ?");
$spouse->execute([$employee_id]);
$spouse = $spouse->fetch();

$children = $pdo->prepare("SELECT * FROM biological_children WHERE employee_id = ?");
$children->execute([$employee_id]);

$family = $pdo->prepare("SELECT * FROM family_contacts WHERE employee_id = ?");
$family->execute([$employee_id]);

$emergency = $pdo->prepare("SELECT * FROM emergency_contacts WHERE employee_id = ?");
$emergency->execute([$employee_id]);

$relatives = $pdo->prepare("SELECT * FROM relatives_employed WHERE employee_id = ?");
$relatives->execute([$employee_id]);

$qualifications = $pdo->prepare("SELECT * FROM qualifications WHERE employee_id = ? ORDER BY year_obtained DESC");
$qualifications->execute([$employee_id]);

$history = $pdo->prepare("SELECT * FROM employment_history WHERE employee_id = ? ORDER BY date_from DESC");
$history->execute([$employee_id]);

$references = $pdo->prepare("SELECT * FROM `references` WHERE employee_id = ? ORDER BY reference_order");
$references->execute([$employee_id]);

// Get photo for display
$photoStmt = $pdo->prepare("SELECT * FROM employee_documents WHERE employee_id = ? AND document_type = 'Passport Photo'");
$photoStmt->execute([$employee_id]);
$photo = $photoStmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - Employee Details</title>
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
        .detail-section {
            transition: all 0.3s ease;
        }
        .detail-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .file-card {
            transition: all 0.3s ease;
        }
        .file-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 35, 104, 0.2);
        }
        .photo-preview {
            border: 3px solid #FFC952;
            transition: all 0.3s ease;
        }
        .photo-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .modal {
            transition: opacity 0.3s ease;
        }
        .modal.hidden {
            display: none;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar w-64 min-h-screen text-white fixed overflow-y-auto">
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
            <!-- Floating Action Buttons -->
            <div class="fixed bottom-8 right-8 z-50 flex flex-col space-y-3">
                <a href="?download_all=<?php echo $employee_id; ?>" 
                   class="bg-[#002368] text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg hover:bg-[#FFC952] hover:text-[#002368] transition transform hover:scale-110"
                   title="Download All Documents">
                    <i class="fas fa-download text-xl"></i>
                </a>
                <button onclick="window.print()" 
                        class="bg-green-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg hover:bg-green-700 transition transform hover:scale-110"
                        title="Print Profile">
                    <i class="fas fa-print text-xl"></i>
                </button>
            </div>
            
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-[#002368]">Employee Details</h1>
                        <p class="text-gray-600">View complete employee information</p>
                    </div>
                    <div class="space-x-3">
                        <a href="employees.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-600 transition">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </a>
                        <?php if (hasRole('admin') || hasRole('hr_manager')): ?>
                        <a href="edit_employee.php?id=<?php echo $employee_id; ?>" 
                           class="bg-[#002368] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition">
                            <i class="fas fa-edit mr-2"></i>Edit
                        </a>
                        <?php endif; ?>
                        <button onclick="showDownloadModal()"
                                class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                            <i class="fas fa-download mr-2"></i>Download
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Employee Header with Photo -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <div class="flex items-start space-x-8">
                    <!-- Photo Section -->
                    <div class="relative group">
                        <?php if ($photo): ?>
                            <img src="../<?php echo $photo['file_path']; ?>" 
                                 alt="Employee Photo" 
                                 class="w-40 h-40 rounded-full object-cover photo-preview cursor-pointer"
                                 onclick="showPhotoModal('../<?php echo $photo['file_path']; ?>')">
                            <div class="absolute -bottom-2 -right-2 flex space-x-2">
                                <a href="?download_photo=<?php echo $employee_id; ?>" 
                                   class="bg-[#002368] text-white p-3 rounded-full hover:bg-[#FFC952] hover:text-[#002368] transition shadow-lg"
                                   title="Download Photo">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button onclick="showPhotoModal('../<?php echo $photo['file_path']; ?>')"
                                        class="bg-[#FFC952] text-[#002368] p-3 rounded-full hover:bg-[#002368] hover:text-white transition shadow-lg"
                                        title="View Full Size">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="w-40 h-40 bg-[#80BFEC] rounded-full flex items-center justify-center text-5xl font-bold text-white photo-preview">
                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['surname'], 0, 1)); ?>
                            </div>
                            <div class="absolute -bottom-2 -right-2">
                                <span class="bg-gray-500 text-white p-2 rounded-full text-xs">No Photo</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1">
                        <h2 class="text-3xl font-bold text-[#002368]">
                            <?php echo htmlspecialchars($employee['surname'] . ' ' . $employee['first_name'] . ' ' . $employee['middle_name']); ?>
                        </h2>
                        <p class="text-xl text-gray-600 mt-1"><?php echo htmlspecialchars($employee['position']); ?></p>
                        <div class="grid grid-cols-4 gap-4 mt-4">
                            <div>
                                <p class="text-sm text-gray-500">Employee ID</p>
                                <p class="font-semibold text-lg">#<?php echo $employee['employee_id']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Gender</p>
                                <p class="font-semibold"><?php echo $employee['gender']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Marital Status</p>
                                <p class="font-semibold"><?php echo $employee['marital_status'] ?? 'Not specified'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Documents</p>
                                <p class="font-semibold"><?php echo $documents->rowCount(); ?> files</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Date of Birth</p>
                        <p class="font-semibold"><?php echo $employee['date_of_birth'] ? date('d M Y', strtotime($employee['date_of_birth'])) : 'Not specified'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Place of Birth</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['place_of_birth'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Age</p>
                        <p class="font-semibold"><?php echo $employee['age'] ?? 'Not specified'; ?> years</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Nationality</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['nationality'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Disabilities</p>
                        <p class="font-semibold"><?php echo $employee['has_disabilities']; ?></p>
                    </div>
                    <?php if ($employee['has_disabilities'] === 'Yes'): ?>
                    <div class="col-span-3">
                        <p class="text-sm text-gray-500">Disability Details</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['disabilities_details']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Home Phone</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['home_phone'] ?? 'Not specified'); ?></p>
                        <?php if (!empty($employee['home_phone'])): ?>
                        <a href="tel:<?php echo $employee['home_phone']; ?>" class="text-xs text-[#002368] hover:text-[#FFC952]">
                            <i class="fas fa-phone mr-1"></i>Call
                        </a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Mobile Phone</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['mobile_phone']); ?></p>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $employee['mobile_phone']); ?>" target="_blank" 
                           class="text-xs text-green-600 hover:text-green-800">
                            <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                        </a>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['email'] ?? 'Not specified'); ?></p>
                        <?php if (!empty($employee['email'])): ?>
                        <a href="mailto:<?php echo $employee['email']; ?>" class="text-xs text-[#002368] hover:text-[#FFC952]">
                            <i class="fas fa-envelope mr-1"></i>Send Email
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-span-3">
                        <p class="text-sm text-gray-500">Residential Address</p>
                        <p class="font-semibold"><?php echo nl2br(htmlspecialchars($employee['residential_address'])); ?></p>
                        <a href="https://maps.google.com/?q=<?php echo urlencode($employee['residential_address']); ?>" target="_blank"
                           class="text-xs text-[#002368] hover:text-[#FFC952]">
                            <i class="fas fa-map-marker-alt mr-1"></i>View on Maps
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Identification & Banking -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">Identification & Banking</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">National ID</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['identification_no']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">NSSF Number</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['nssf_no'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">TIN Number</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['tin_no'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Bank Name</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['bank_name'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Account Name</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['account_name'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Account Number</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($employee['account_number'] ?? 'Not specified'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Family Information -->
            <?php if ($spouse || $children->rowCount() > 0 || $family->rowCount() > 0 || $emergency->rowCount() > 0): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">Family Information</h3>
                
                <?php if ($spouse): ?>
                <div class="mb-6">
                    <h4 class="font-semibold text-[#002368] mb-3">Spouse</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Name</p>
                            <p><?php echo htmlspecialchars($spouse['full_name']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Phone</p>
                            <p><?php echo htmlspecialchars($spouse['phone']); ?></p>
                            <a href="tel:<?php echo $spouse['phone']; ?>" class="text-xs text-[#002368]">Call</a>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Occupation</p>
                            <p><?php echo htmlspecialchars($spouse['occupation']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Employer</p>
                            <p><?php echo htmlspecialchars($spouse['employer']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($children->rowCount() > 0): ?>
                <div class="mb-6">
                    <h4 class="font-semibold text-[#002368] mb-3">Children</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left py-2 px-4">Name</th>
                                    <th class="text-left py-2 px-4">Date of Birth</th>
                                    <th class="text-left py-2 px-4">Gender</th>
                                    <th class="text-left py-2 px-4">School/Employer</th>
                                    <th class="text-left py-2 px-4">Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($children as $child): ?>
                                <tr>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($child['full_names']); ?></td>
                                    <td class="py-2 px-4"><?php echo $child['date_of_birth'] ? date('d M Y', strtotime($child['date_of_birth'])) : 'N/A'; ?></td>
                                    <td class="py-2 px-4"><?php echo $child['gender']; ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($child['school_employer'] ?? 'N/A'); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($child['contact_number'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($emergency->rowCount() > 0): ?>
                <div class="mb-6">
                    <h4 class="font-semibold text-[#002368] mb-3">Emergency Contacts</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left py-2 px-4">Name</th>
                                    <th class="text-left py-2 px-4">Relationship</th>
                                    <th class="text-left py-2 px-4">Phone</th>
                                    <th class="text-left py-2 px-4">Address</th>
                                    <th class="text-left py-2 px-4">Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emergency as $contact): ?>
                                <tr>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($contact['full_name']); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($contact['relationship']); ?></td>
                                    <td class="py-2 px-4">
                                        <?php echo htmlspecialchars($contact['phone']); ?>
                                        <a href="tel:<?php echo $contact['phone']; ?>" class="ml-2 text-xs text-[#002368]">Call</a>
                                    </td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($contact['address']); ?></td>
                                    <td class="py-2 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs <?php echo $contact['priority'] === 'Primary' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $contact['priority']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Qualifications -->
            <?php if ($qualifications->rowCount() > 0): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">Qualifications</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left py-3 px-4">Level</th>
                                <th class="text-left py-3 px-4">Qualification</th>
                                <th class="text-left py-3 px-4">Institution</th>
                                <th class="text-left py-3 px-4">Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qualifications as $qual): ?>
                            <tr>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($qual['level']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($qual['qualification']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($qual['institution']); ?></td>
                                <td class="py-3 px-4"><?php echo $qual['year_obtained']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Employment History -->
            <?php if ($history->rowCount() > 0): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">Employment History</h3>
                <div class="space-y-4">
                    <?php foreach ($history as $job): ?>
                    <div class="border-l-4 border-[#FFC952] pl-4 py-2">
                        <h4 class="font-semibold text-[#002368]"><?php echo htmlspecialchars($job['position']); ?></h4>
                        <p class="text-gray-600"><?php echo htmlspecialchars($job['employer']); ?></p>
                        <p class="text-sm text-gray-500">
                            <?php echo $job['date_from'] ? date('M Y', strtotime($job['date_from'])) : 'N/A'; ?> - 
                            <?php echo $job['date_to'] ? date('M Y', strtotime($job['date_to'])) : 'Present'; ?>
                        </p>
                        <?php if ($job['leaving_reason']): ?>
                        <p class="text-sm text-gray-600 mt-2">Reason: <?php echo htmlspecialchars($job['leaving_reason']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- References -->
            <?php if ($references->rowCount() > 0): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">References</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($references as $ref): ?>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="font-semibold text-[#002368]"><?php echo htmlspecialchars($ref['full_name']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($ref['relationship']); ?></p>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($ref['organization']); ?></p>
                        <p class="text-sm mt-2">
                            <i class="fas fa-phone mr-1 text-[#002368]"></i> 
                            <?php echo htmlspecialchars($ref['phone']); ?>
                            <a href="tel:<?php echo $ref['phone']; ?>" class="ml-2 text-xs text-[#002368]">Call</a>
                        </p>
                        <p class="text-sm">
                            <i class="fas fa-envelope mr-1 text-[#002368]"></i> 
                            <?php echo htmlspecialchars($ref['email']); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Legal Information -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <h3 class="text-xl font-bold text-[#002368] mb-4 pb-2 border-b-2 border-[#FFC952]">Legal & Conduct Information</h3>
                <div class="space-y-4">
                    <div>
                        <p class="font-semibold">Arrest/Detention/Deportation Record:</p>
                        <p class="text-gray-700"><?php echo $employee['arrest_record'] ?? 'No'; ?></p>
                        <?php if (!empty($employee['arrest_details'])): ?>
                        <p class="text-sm text-gray-600 mt-1">Details: <?php echo htmlspecialchars($employee['arrest_details']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="font-semibold">Misconduct/Discharge Record:</p>
                        <p class="text-gray-700"><?php echo $employee['misconduct_record'] ?? 'No'; ?></p>
                        <?php if (!empty($employee['misconduct_details'])): ?>
                        <p class="text-sm text-gray-600 mt-1">Details: <?php echo htmlspecialchars($employee['misconduct_details']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Documents Section -->
            <?php if ($documents->rowCount() > 0): ?>
            <div class="bg-white rounded-xl shadow-lg p-8 mb-6 detail-section">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-[#002368]">Documents</h3>
                    <a href="?download_all=<?php echo $employee_id; ?>" 
                       class="bg-[#002368] text-white px-4 py-2 rounded-lg hover:bg-[#FFC952] hover:text-[#002368] transition text-sm">
                        <i class="fas fa-download mr-2"></i>Download All
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($documents as $doc): ?>
                    <div class="file-card border-2 border-[#80BFEC] rounded-lg overflow-hidden">
                        <div class="bg-gray-50 p-4 text-center">
                            <?php 
                            $extension = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                            $icon = 'fa-file';
                            $color = 'text-[#002368]';
                            
                            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                $icon = 'fa-file-image';
                                $color = 'text-green-600';
                            } elseif (in_array($extension, ['pdf'])) {
                                $icon = 'fa-file-pdf';
                                $color = 'text-red-600';
                            } elseif (in_array($extension, ['doc', 'docx'])) {
                                $icon = 'fa-file-word';
                                $color = 'text-blue-600';
                            } elseif (in_array($extension, ['xls', 'xlsx'])) {
                                $icon = 'fa-file-excel';
                                $color = 'text-green-700';
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?> text-4xl <?php echo $color; ?> mb-2"></i>
                            <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($doc['document_type']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo round($doc['file_size_bytes'] / 1024, 2); ?> KB</p>
                        </div>
                        <div class="flex border-t border-[#80BFEC]">
                            <a href="../<?php echo $doc['file_path']; ?>" target="_blank" 
                               class="flex-1 py-2 text-center text-[#002368] hover:bg-[#80BFEC] hover:text-white transition"
                               title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="?download=<?php echo $doc['doc_id']; ?>" 
                               class="flex-1 py-2 text-center text-[#002368] hover:bg-[#80BFEC] hover:text-white transition border-l border-[#80BFEC]"
                               title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Photo Modal -->
    <div id="photoModal" class="modal hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="relative max-w-4xl max-h-screen p-4">
            <button onclick="closePhotoModal()" class="absolute top-2 right-2 text-white bg-red-600 w-10 h-10 rounded-full hover:bg-red-700 z-10">
                <i class="fas fa-times"></i>
            </button>
            <img id="modalImage" src="" alt="Full size photo" class="max-w-full max-h-screen object-contain">
        </div>
    </div>
    
    <!-- Download Options Modal -->
    <div id="downloadModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h2 class="text-2xl font-bold text-[#002368] mb-4">Download Options</h2>
            <div class="space-y-3">
                <a href="?download_photo=<?php echo $employee_id; ?>" 
                   class="block w-full bg-[#002368] text-white px-4 py-3 rounded-lg hover:bg-[#FFC952] hover:text-[#002368] transition text-center">
                    <i class="fas fa-camera mr-2"></i>Download Passport Photo
                </a>
                <a href="?download_all=<?php echo $employee_id; ?>" 
                   class="block w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition text-center">
                    <i class="fas fa-file-archive mr-2"></i>Download All Documents (ZIP)
                </a>
                <div class="border-t border-gray-200 my-4 pt-4">
                    <h3 class="font-semibold text-[#002368] mb-2">Individual Documents</h3>
                    <div class="max-h-60 overflow-y-auto space-y-2">
                        <?php foreach ($documents as $doc): ?>
                        <a href="?download=<?php echo $doc['doc_id']; ?>" 
                           class="block text-sm text-[#002368] hover:text-[#FFC952] transition py-1">
                            <i class="fas fa-file mr-2"></i><?php echo htmlspecialchars($doc['document_type']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button onclick="closeDownloadModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Photo Modal Functions
        function showPhotoModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('photoModal').classList.remove('hidden');
        }
        
        function closePhotoModal() {
            document.getElementById('photoModal').classList.add('hidden');
        }
        
        // Download Modal Functions
        function showDownloadModal() {
            document.getElementById('downloadModal').classList.remove('hidden');
        }
        
        function closeDownloadModal() {
            document.getElementById('downloadModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.add('hidden');
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoModal();
                closeDownloadModal();
            }
        });
        
        // Print functionality with options
        function printEmployeeProfile() {
            const printContent = document.querySelector('.flex-1.ml-64.p-8').innerHTML;
            const originalContent = document.body.innerHTML;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Employee Profile - ${<?php echo json_encode($employee['surname'] . ' ' . $employee['first_name']); ?>}</title>
                        <link href="https://cdn.tailwindcss.com" rel="stylesheet">
                        <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                        <style>
                            body { font-family: 'Merriweather Sans', sans-serif; padding: 20px; }
                            .print-section { margin-bottom: 20px; page-break-inside: avoid; }
                            @media print {
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="max-w-6xl mx-auto">
                            ${printContent}
                        </div>
                        <script>
                            window.onload = function() { window.print(); window.close(); }
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>