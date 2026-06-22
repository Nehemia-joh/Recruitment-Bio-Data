<?php
// index.php - Complete Employee Bio-Data Form with Tailwind CSS
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_name = 'employee_management';
$db_user = 'root';
$db_pass = 'chance00';

// First, let's create the emergency_contacts table if it doesn't exist
try {
    $pdo_setup = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo_setup->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    $pdo_setup->exec("USE $db_name");
    
    // Check if columns exist in employees table and add them if they don't
    $stmt = $pdo_setup->query("SHOW COLUMNS FROM employees LIKE 'marital_status'");
    if ($stmt->rowCount() == 0) {
        $pdo_setup->exec("ALTER TABLE employees ADD COLUMN marital_status VARCHAR(50) AFTER gender");
    }
    
    $stmt = $pdo_setup->query("SHOW COLUMNS FROM employees LIKE 'arrest_record'");
    if ($stmt->rowCount() == 0) {
        $pdo_setup->exec("ALTER TABLE employees ADD COLUMN arrest_record ENUM('Yes', 'No') DEFAULT 'No' AFTER mobile_money_number");
    }
    
    $stmt = $pdo_setup->query("SHOW COLUMNS FROM employees LIKE 'arrest_details'");
    if ($stmt->rowCount() == 0) {
        $pdo_setup->exec("ALTER TABLE employees ADD COLUMN arrest_details TEXT AFTER arrest_record");
    }
    
    $stmt = $pdo_setup->query("SHOW COLUMNS FROM employees LIKE 'misconduct_record'");
    if ($stmt->rowCount() == 0) {
        $pdo_setup->exec("ALTER TABLE employees ADD COLUMN misconduct_record ENUM('Yes', 'No') DEFAULT 'No' AFTER arrest_details");
    }
    
    $stmt = $pdo_setup->query("SHOW COLUMNS FROM employees LIKE 'misconduct_details'");
    if ($stmt->rowCount() == 0) {
        $pdo_setup->exec("ALTER TABLE employees ADD COLUMN misconduct_details TEXT AFTER misconduct_record");
    }
    
    // Create emergency_contacts table if it doesn't exist
    $pdo_setup->exec("
        CREATE TABLE IF NOT EXISTS emergency_contacts (
            emergency_id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            relationship VARCHAR(100),
            phone VARCHAR(50),
            address TEXT,
            priority ENUM('Primary', 'Secondary') DEFAULT 'Primary',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
        )
    ");
    
    // Check if reference_order column exists in references table, if not, add it
    $stmt = $pdo_setup->query("SHOW COLUMNS FROM `references` LIKE 'reference_order'");
    if ($stmt->rowCount() == 0) {
        $pdo_setup->exec("ALTER TABLE `references` ADD COLUMN reference_order INT DEFAULT NULL AFTER organization");
    }
    
} catch (PDOException $e) {
    // Silently handle - will be caught in main process
}

// Create upload directories
$base_upload_dir = 'uploads/';
$directories = ['photos', 'ids', 'certificates', 'birth_certificates', 'professional_certificates', 'documents', 'cv'];

foreach ($directories as $dir) {
    if (!file_exists($base_upload_dir . $dir)) {
        mkdir($base_upload_dir . $dir, 0777, true);
    }
}

// Initialize variables
$success_message = '';
$error_messages = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Establish database connection
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Validate required fields
        $required_fields = ['surname', 'first_name', 'mobile_phone', 'residential_address', 'identification_no', 'position', 'marital_status'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field " . ucfirst(str_replace('_', ' ', $field)) . " is required.");
            }
        }
        
        // Calculate age if date of birth is provided
        $age = null;
        if (!empty($_POST['date_of_birth'])) {
            $dob = new DateTime($_POST['date_of_birth']);
            $today = new DateTime();
            $age = $dob->diff($today)->y;
        }
        
        // Insert main employee data
        $sql = "INSERT INTO employees (
            surname, first_name, middle_name, other_names, maiden_name,
            home_phone, mobile_phone, email, residential_address,
            date_of_birth, place_of_birth, age, gender,
            nationality, identification_no, id_place_of_issue, id_expiry_date,
            driving_permit_no, driving_place_of_issue, driving_expiry_date,
            nssf_no, position, work_station,
            has_disabilities, disabilities_details,
            tin_no, nhif_no, bank_name, account_name, account_number, mobile_money_number,
            marital_status, arrest_record, arrest_details, misconduct_record, misconduct_details
        ) VALUES (
            :surname, :first_name, :middle_name, :other_names, :maiden_name,
            :home_phone, :mobile_phone, :email, :residential_address,
            :date_of_birth, :place_of_birth, :age, :gender,
            :nationality, :identification_no, :id_place_of_issue, :id_expiry_date,
            :driving_permit_no, :driving_place_of_issue, :driving_expiry_date,
            :nssf_no, :position, :work_station,
            :has_disabilities, :disabilities_details,
            :tin_no, :nhif_no, :bank_name, :account_name, :account_number, :mobile_money_number,
            :marital_status, :arrest_record, :arrest_details, :misconduct_record, :misconduct_details
        )";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            'surname' => $_POST['surname'],
            'first_name' => $_POST['first_name'],
            'middle_name' => $_POST['middle_name'] ?? null,
            'other_names' => $_POST['other_names'] ?? null,
            'maiden_name' => $_POST['maiden_name'] ?? null,
            'home_phone' => $_POST['home_phone'] ?? null,
            'mobile_phone' => $_POST['mobile_phone'],
            'email' => $_POST['email'] ?? null,
            'residential_address' => $_POST['residential_address'],
            'date_of_birth' => $_POST['date_of_birth'] ?? null,
            'place_of_birth' => $_POST['place_of_birth'] ?? null,
            'age' => $age,
            'gender' => $_POST['gender'] ?? null,
            'nationality' => $_POST['nationality'] ?? 'Tanzanian',
            'identification_no' => $_POST['identification_no'],
            'id_place_of_issue' => $_POST['id_place_of_issue'] ?? null,
            'id_expiry_date' => $_POST['id_expiry_date'] ?? null,
            'driving_permit_no' => $_POST['driving_permit_no'] ?? null,
            'driving_place_of_issue' => $_POST['driving_place_of_issue'] ?? null,
            'driving_expiry_date' => $_POST['driving_expiry_date'] ?? null,
            'nssf_no' => $_POST['nssf_no'] ?? null,
            'position' => $_POST['position'],
            'work_station' => $_POST['work_station'] ?? null,
            'has_disabilities' => $_POST['has_disabilities'] ?? 'No',
            'disabilities_details' => $_POST['disabilities_details'] ?? null,
            'tin_no' => $_POST['tin_no'] ?? null,
            'nhif_no' => $_POST['nhif_no'] ?? null,
            'bank_name' => $_POST['bank_name'] ?? 'CRDB',
            'account_name' => $_POST['account_name'] ?? null,
            'account_number' => $_POST['account_number'] ?? null,
            'mobile_money_number' => $_POST['mobile_money_number'] ?? null,
            'marital_status' => $_POST['marital_status'],
            'arrest_record' => $_POST['arrest_record'] ?? 'No',
            'arrest_details' => $_POST['arrest_details'] ?? null,
            'misconduct_record' => $_POST['misconduct_record'] ?? 'No',
            'misconduct_details' => $_POST['misconduct_details'] ?? null
        ];
        
        $stmt->execute($params);
        $employee_id = $pdo->lastInsertId();
        
        // Handle file uploads
        $uploaded_files = [];
        
        // File upload function
        $file_upload_types = [
            'passport_photo' => ['dir' => 'photos', 'type' => 'Passport Photo'],
            'cv' => ['dir' => 'cv', 'type' => 'Curriculum Vitae'],
            'national_id' => ['dir' => 'ids', 'type' => 'National ID'],
            'birth_certificate' => ['dir' => 'birth_certificates', 'type' => 'Birth Certificate'],
        ];
        
        // Handle file uploads
        foreach ($file_upload_types as $field_name => $config) {
            if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field_name];
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = $field_name . '_' . $employee_id . '_' . time() . '.' . $extension;
                $filepath = $base_upload_dir . $config['dir'] . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $uploaded_files[] = [
                        'employee_id' => $employee_id,
                        'document_type' => $config['type'],
                        'file_path' => $filepath,
                        'original_name' => $file['name'],
                        'mime_type' => $file['type'],
                        'file_size_bytes' => $file['size']
                    ];
                }
            }
        }
        
        // Handle multiple professional certificates
        if (!empty($_FILES['professional_certificates']['name'][0])) {
            foreach ($_FILES['professional_certificates']['name'] as $key => $name) {
                if ($_FILES['professional_certificates']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['professional_certificates']['tmp_name'][$key];
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $filename = 'prof_cert_' . $employee_id . '_' . time() . '_' . $key . '.' . $extension;
                    $filepath = $base_upload_dir . 'professional_certificates/' . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $uploaded_files[] = [
                            'employee_id' => $employee_id,
                            'document_type' => 'Professional Certificate',
                            'file_path' => $filepath,
                            'original_name' => $name,
                            'mime_type' => $_FILES['professional_certificates']['type'][$key],
                            'file_size_bytes' => $_FILES['professional_certificates']['size'][$key]
                        ];
                    }
                }
            }
        }
        
        // Handle multiple academic certificates
        if (!empty($_FILES['academic_certificates']['name'][0])) {
            foreach ($_FILES['academic_certificates']['name'] as $key => $name) {
                if ($_FILES['academic_certificates']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['academic_certificates']['tmp_name'][$key];
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $filename = 'acad_cert_' . $employee_id . '_' . time() . '_' . $key . '.' . $extension;
                    $filepath = $base_upload_dir . 'certificates/' . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $uploaded_files[] = [
                            'employee_id' => $employee_id,
                            'document_type' => 'Academic Certificate',
                            'file_path' => $filepath,
                            'original_name' => $name,
                            'mime_type' => $_FILES['academic_certificates']['type'][$key],
                            'file_size_bytes' => $_FILES['academic_certificates']['size'][$key]
                        ];
                    }
                }
            }
        }
        
        // Insert uploaded files into database
        if (!empty($uploaded_files)) {
            $file_sql = "INSERT INTO employee_documents (employee_id, document_type, file_path, original_name, mime_type, file_size_bytes) 
                        VALUES (:employee_id, :document_type, :file_path, :original_name, :mime_type, :file_size_bytes)";
            $file_stmt = $pdo->prepare($file_sql);
            
            foreach ($uploaded_files as $file_data) {
                $file_stmt->execute($file_data);
            }
        }
        
        // Insert spouse data if provided
        if (!empty($_POST['spouse_name'])) {
            $spouse_sql = "INSERT INTO spouses (employee_id, full_name, phone, occupation, employer) 
                          VALUES (:employee_id, :full_name, :phone, :occupation, :employer)";
            $spouse_stmt = $pdo->prepare($spouse_sql);
            $spouse_stmt->execute([
                'employee_id' => $employee_id,
                'full_name' => $_POST['spouse_name'],
                'phone' => $_POST['spouse_phone'] ?? null,
                'occupation' => $_POST['spouse_occupation'] ?? null,
                'employer' => $_POST['spouse_employer'] ?? null
            ]);
        }
        
        // Insert children data
        if (!empty($_POST['child_names'])) {
            $child_sql = "INSERT INTO biological_children (employee_id, full_names, date_of_birth, gender, school_employer, contact_number) 
                         VALUES (:employee_id, :full_names, :date_of_birth, :gender, :school_employer, :contact_number)";
            $child_stmt = $pdo->prepare($child_sql);
            
            foreach ($_POST['child_names'] as $index => $child_name) {
                if (!empty($child_name)) {
                    $child_stmt->execute([
                        'employee_id' => $employee_id,
                        'full_names' => $child_name,
                        'date_of_birth' => $_POST['child_dobs'][$index] ?? null,
                        'gender' => $_POST['child_genders'][$index] ?? null,
                        'school_employer' => $_POST['child_schools'][$index] ?? null,
                        'contact_number' => $_POST['child_contacts'][$index] ?? null
                    ]);
                }
            }
        }
        
        // Insert family contacts
        if (!empty($_POST['family_names'])) {
            $family_sql = "INSERT INTO family_contacts (employee_id, relationship, full_name, phone, address, occupation) 
                          VALUES (:employee_id, :relationship, :full_name, :phone, :address, :occupation)";
            $family_stmt = $pdo->prepare($family_sql);
            
            foreach ($_POST['family_names'] as $index => $family_name) {
                if (!empty($family_name)) {
                    $family_stmt->execute([
                        'employee_id' => $employee_id,
                        'relationship' => $_POST['family_relationships'][$index] ?? 'Next of Kin',
                        'full_name' => $family_name,
                        'phone' => $_POST['family_phones'][$index] ?? null,
                        'address' => $_POST['family_addresses'][$index] ?? null,
                        'occupation' => $_POST['family_occupations'][$index] ?? null
                    ]);
                }
            }
        }
        
        // Insert emergency contacts
        if (!empty($_POST['emergency_names'])) {
            $emergency_sql = "INSERT INTO emergency_contacts (employee_id, full_name, relationship, phone, address, priority) 
                            VALUES (:employee_id, :full_name, :relationship, :phone, :address, :priority)";
            $emergency_stmt = $pdo->prepare($emergency_sql);
            
            foreach ($_POST['emergency_names'] as $index => $emergency_name) {
                if (!empty($emergency_name)) {
                    $emergency_stmt->execute([
                        'employee_id' => $employee_id,
                        'full_name' => $emergency_name,
                        'relationship' => $_POST['emergency_relationships'][$index] ?? '',
                        'phone' => $_POST['emergency_phones'][$index] ?? null,
                        'address' => $_POST['emergency_addresses'][$index] ?? null,
                        'priority' => $_POST['emergency_priorities'][$index] ?? 'Primary'
                    ]);
                }
            }
        }
        
        // Insert relatives employed at Silverleaf
        if (!empty($_POST['relative_names'])) {
            $relative_sql = "INSERT INTO relatives_employed (employee_id, full_name, relationship, position, work_station) 
                            VALUES (:employee_id, :full_name, :relationship, :position, :work_station)";
            $relative_stmt = $pdo->prepare($relative_sql);
            
            foreach ($_POST['relative_names'] as $index => $relative_name) {
                if (!empty($relative_name)) {
                    $relative_stmt->execute([
                        'employee_id' => $employee_id,
                        'full_name' => $relative_name,
                        'relationship' => $_POST['relative_relationships'][$index] ?? '',
                        'position' => $_POST['relative_positions'][$index] ?? null,
                        'work_station' => $_POST['relative_stations'][$index] ?? null
                    ]);
                }
            }
        }
        
        // Insert qualifications
        if (!empty($_POST['qualification_levels'])) {
            $qual_sql = "INSERT INTO qualifications (employee_id, level, qualification, institution, year_obtained) 
                        VALUES (:employee_id, :level, :qualification, :institution, :year_obtained)";
            $qual_stmt = $pdo->prepare($qual_sql);
            
            foreach ($_POST['qualification_levels'] as $index => $level) {
                if (!empty($level)) {
                    $qual_stmt->execute([
                        'employee_id' => $employee_id,
                        'level' => $level,
                        'qualification' => $_POST['qualification_names'][$index] ?? '',
                        'institution' => $_POST['institutions'][$index] ?? '',
                        'year_obtained' => $_POST['years_obtained'][$index] ?? null
                    ]);
                }
            }
        }
        
        // Insert employment history
        if (!empty($_POST['previous_employers'])) {
            $history_sql = "INSERT INTO employment_history (employee_id, employer, position, date_from, date_to, leaving_reason) 
                           VALUES (:employee_id, :employer, :position, :date_from, :date_to, :leaving_reason)";
            $history_stmt = $pdo->prepare($history_sql);
            
            foreach ($_POST['previous_employers'] as $index => $employer) {
                if (!empty($employer)) {
                    $history_stmt->execute([
                        'employee_id' => $employee_id,
                        'employer' => $employer,
                        'position' => $_POST['previous_positions'][$index] ?? '',
                        'date_from' => $_POST['dates_from'][$index] ?? null,
                        'date_to' => $_POST['dates_to'][$index] ?? null,
                        'leaving_reason' => $_POST['leaving_reasons'][$index] ?? null
                    ]);
                }
            }
        }
        
        // Insert references (without reference_order if column doesn't exist)
        if (!empty($_POST['ref1_name'])) {
            // Check if reference_order column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM `references` LIKE 'reference_order'");
            $has_reference_order = $stmt->rowCount() > 0;
            
            if ($has_reference_order) {
                $ref_sql = "INSERT INTO `references` (employee_id, full_name, relationship, phone, email, organization, reference_order) 
                           VALUES (:employee_id, :full_name, :relationship, :phone, :email, :organization, :reference_order)";
            } else {
                $ref_sql = "INSERT INTO `references` (employee_id, full_name, relationship, phone, email, organization) 
                           VALUES (:employee_id, :full_name, :relationship, :phone, :email, :organization)";
            }
            
            $ref_stmt = $pdo->prepare($ref_sql);
            
            for ($i = 1; $i <= 3; $i++) {
                if (!empty($_POST["ref{$i}_name"])) {
                    $params = [
                        'employee_id' => $employee_id,
                        'full_name' => $_POST["ref{$i}_name"],
                        'relationship' => $_POST["ref{$i}_relationship"] ?? null,
                        'phone' => $_POST["ref{$i}_phone"] ?? null,
                        'email' => $_POST["ref{$i}_email"] ?? null,
                        'organization' => $_POST["ref{$i}_organization"] ?? null
                    ];
                    
                    if ($has_reference_order) {
                        $params['reference_order'] = $i;
                    }
                    
                    $ref_stmt->execute($params);
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        $success_message = "Employee bio-data has been successfully saved! Employee ID: " . $employee_id;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $error_messages[] = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - Employee Bio-Data Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Merriweather Sans', sans-serif;
        }
        body {
            background-color: #ECECEC;
        }
        .text-main { color: #002368; }
        .bg-main { background-color: #002368; }
        .border-main { border-color: #002368; }
        .text-highlight { color: #80BFEC; }
        .bg-highlight { background-color: #80BFEC; }
        .border-highlight { border-color: #80BFEC; }
        .text-decoration { color: #FFC952; }
        .bg-decoration { background-color: #FFC952; }
        .border-decoration { border-color: #FFC952; }
        .text-body { color: #000000; }
        .bg-body { background-color: #FFFFFF; }
        .hover-scale {
            transition: transform 0.3s ease;
        }
        .hover-scale:hover {
            transform: scale(1.02);
        }
        .file-upload-area {
            border: 2px dashed #80BFEC;
            transition: all 0.3s ease;
        }
        .file-upload-area:hover {
            border-color: #FFC952;
            background-color: rgba(128, 191, 236, 0.05);
        }
        .certification-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #FFC952;
        }
    </style>
</head>
<body class="bg-[#ECECEC] min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Header -->
        <div class="bg-[#FFFFFF] rounded-2xl shadow-xl mb-8 overflow-hidden border-t-4 border-[#FFC952]">
            <div class="bg-[#002368] px-8 py-6">
                <h1 class="text-4xl font-bold text-[#FFFFFF] text-center">Silverleaf Academy</h1>
                <p class="text-[#ECECEC] text-center mt-2 text-lg">Employee Bio-Data Registration Form</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-2 p-4 bg-[#ECECEC]">
                <?php
                $steps = ['Personal', 'Contact', 'IDs', 'Employment', 'Family', 'Declaration'];
                foreach ($steps as $index => $step) {
                    $active = $index === 0 ? 'bg-[#002368] text-[#FFFFFF]' : 'bg-[#FFFFFF] text-[#002368]';
                    echo "<div class='$active rounded-lg px-4 py-2 text-center font-semibold shadow-sm'>$step</div>";
                }
                ?>
            </div>
            
            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="mx-6 mt-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_messages)): ?>
                <div class="mx-6 mt-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                    <?php foreach ($error_messages as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Main Form -->
        <form method="POST" enctype="multipart/form-data" class="space-y-6" id="employeeForm">
            <!-- Section 1: Personal Information -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">1. Personal Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Surename <span class="text-red-500">*</span></label>
                        <input type="text" name="surname" required class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Middle Name</label>
                        <input type="text" name="middle_name" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Other Names</label>
                        <input type="text" name="other_names" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Maiden Name</label>
                        <input type="text" name="maiden_name" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" required class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                            <option value="">Select Gender</option>
                            <option value="MALE">Male</option>
                            <option value="FEMALE">Female</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Marital Status <span class="text-red-500">*</span></label>
                        <select name="marital_status" required class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                            <option value="">Select Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Place of Birth</label>
                        <input type="text" name="place_of_birth" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Nationality</label>
                        <input type="text" name="nationality" value="Tanzanian" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-[#002368] font-semibold mb-2">Do you have any disabilities?</label>
                    <select name="has_disabilities" class="w-full md:w-1/3 px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                    </select>
                </div>
                
                <div class="mt-4">
                    <label class="block text-[#002368] font-semibold mb-2">If yes, please specify:</label>
                    <textarea name="disabilities_details" rows="3" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition"></textarea>
                </div>
            </div>
            
            <!-- Section 2: Contact Information -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">2. Contact Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Home Phone</label>
                        <input type="tel" name="home_phone" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Mobile Phone <span class="text-red-500">*</span></label>
                        <input type="tel" name="mobile_phone" required class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Email Address</label>
                        <input type="email" name="email" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-[#002368] font-semibold mb-2">Residential Address <span class="text-red-500">*</span></label>
                    <textarea name="residential_address" required rows="3" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition"></textarea>
                </div>
            </div>
            
            <!-- Section 3: Identification Documents -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">3. Identification Documents</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">National ID / NIDA <span class="text-red-500">*</span></label>
                        <input type="text" name="identification_no" required class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Place of Issue</label>
                        <input type="text" name="id_place_of_issue" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Expiry Date</label>
                        <input type="date" name="id_expiry_date" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Driving Permit</label>
                        <input type="text" name="driving_permit_no" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Place of Issue</label>
                        <input type="text" name="driving_place_of_issue" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Expiry Date</label>
                        <input type="date" name="driving_expiry_date" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">NSSF Number</label>
                        <input type="text" name="nssf_no" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">TIN Number</label>
                        <input type="text" name="tin_no" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">NHIF Number</label>
                        <input type="text" name="nhif_no" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                </div>
            </div>
            
            <!-- Section 4: Employment Details -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">4. Employment Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Position <span class="text-red-500">*</span></label>
                        <input type="text" name="position" required class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Work Station</label>
                        <input type="text" name="work_station" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                </div>
            </div>
            
            <!-- Section 5: Banking Information -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">5. Banking Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Bank Name</label>
                        <input type="text" name="bank_name" value="CRDB" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Account Name</label>
                        <input type="text" name="account_name" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Account Number</label>
                        <input type="text" name="account_number" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Mobile Money Number</label>
                        <input type="text" name="mobile_money_number" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                </div>
            </div>
            
            <!-- Section 6: Document Uploads -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">6. Document Uploads</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Passport Photo -->
                    <div class="file-upload-area rounded-lg p-4 text-center">
                        <label class="block text-[#002368] font-semibold mb-2">Passport Photo</label>
                        <input type="file" name="passport_photo" id="passport_photo" accept="image/*" class="hidden">
                        <label for="passport_photo" class="cursor-pointer block">
                            <div class="text-4xl mb-2">📸</div>
                            <div class="text-sm text-[#002368]">Click to upload</div>
                            <div class="text-xs text-gray-500 mt-1">JPG, PNG (Max 2MB)</div>
                        </label>
                    </div>
                    
                    <!-- Birth Certificate -->
                    <div class="file-upload-area rounded-lg p-4 text-center">
                        <label class="block text-[#002368] font-semibold mb-2">Birth Certificate</label>
                        <input type="file" name="birth_certificate" id="birth_certificate" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                        <label for="birth_certificate" class="cursor-pointer block">
                            <div class="text-4xl mb-2">👶</div>
                            <div class="text-sm text-[#002368]">Click to upload</div>
                            <div class="text-xs text-gray-500 mt-1">PDF, JPG (Max 3MB)</div>
                        </label>
                    </div>
                    
                    <!-- CV -->
                    <div class="file-upload-area rounded-lg p-4 text-center">
                        <label class="block text-[#002368] font-semibold mb-2">Curriculum Vitae</label>
                        <input type="file" name="cv" id="cv" accept=".pdf,.doc,.docx" class="hidden">
                        <label for="cv" class="cursor-pointer block">
                            <div class="text-4xl mb-2">📄</div>
                            <div class="text-sm text-[#002368]">Click to upload</div>
                            <div class="text-xs text-gray-500 mt-1">PDF, DOC (Max 5MB)</div>
                        </label>
                    </div>
                    
                    <!-- National ID -->
                    <div class="file-upload-area rounded-lg p-4 text-center">
                        <label class="block text-[#002368] font-semibold mb-2">National ID Scan</label>
                        <input type="file" name="national_id" id="national_id" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                        <label for="national_id" class="cursor-pointer block">
                            <div class="text-4xl mb-2">🆔</div>
                            <div class="text-sm text-[#002368]">Click to upload</div>
                            <div class="text-xs text-gray-500 mt-1">PDF, JPG (Max 3MB)</div>
                        </label>
                    </div>
                </div>
                
                <!-- Multiple Professional Certificates -->
                <div class="mt-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Professional Certificates</h3>
                    <div id="professional-certificates-container">
                        <div class="file-upload-area rounded-lg p-4 text-center mb-4">
                            <input type="file" name="professional_certificates[]" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="prof_cert_1">
                            <label for="prof_cert_1" class="cursor-pointer block">
                                <div class="text-4xl mb-2">📜</div>
                                <div class="text-sm text-[#002368]">Click to upload professional certificate</div>
                                <div class="text-xs text-gray-500 mt-1">PDF, JPG (Max 5MB each)</div>
                            </label>
                        </div>
                    </div>
                    <button type="button" onclick="addProfessionalCertificate()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                        + Add Another Professional Certificate
                    </button>
                </div>
                
                <!-- Multiple Academic Certificates -->
                <div class="mt-6">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Academic Certificates</h3>
                    <div id="academic-certificates-container">
                        <div class="file-upload-area rounded-lg p-4 text-center mb-4">
                            <input type="file" name="academic_certificates[]" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="acad_cert_1">
                            <label for="acad_cert_1" class="cursor-pointer block">
                                <div class="text-4xl mb-2">🎓</div>
                                <div class="text-sm text-[#002368]">Click to upload academic certificate</div>
                                <div class="text-xs text-gray-500 mt-1">PDF, JPG (Max 5MB each)</div>
                            </label>
                        </div>
                    </div>
                    <button type="button" onclick="addAcademicCertificate()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                        + Add Another Academic Certificate
                    </button>
                </div>
            </div>
            
            <!-- Section 7: Family Information -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">7. Family Information</h2>
                
                <!-- Spouse Information -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Spouse Information (if applicable)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Spouse Full Name</label>
                            <input type="text" name="spouse_name" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Spouse Phone</label>
                            <input type="tel" name="spouse_phone" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Spouse Occupation</label>
                            <input type="text" name="spouse_occupation" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Spouse Employer</label>
                            <input type="text" name="spouse_employer" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                        </div>
                    </div>
                </div>
                
                <!-- Children Information -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Biological Children</h3>
                    <div id="children-container">
                        <!-- Child items will be added here via JavaScript -->
                    </div>
                    <button type="button" onclick="addChild()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                        + Add Child
                    </button>
                </div>
                
                <!-- Family Contacts -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Family Contacts (Parents, Siblings)</h3>
                    <div id="family-container">
                        <!-- Family contact items will be added here via JavaScript -->
                    </div>
                    <button type="button" onclick="addFamily()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                        + Add Family Contact
                    </button>
                </div>
                
                <!-- Emergency Contacts -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-[#002368] mb-4">Emergency Contacts</h3>
                    <div id="emergency-container">
                        <!-- Emergency contact items will be added here via JavaScript -->
                    </div>
                    <button type="button" onclick="addEmergency()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                        + Add Emergency Contact
                    </button>
                </div>
            </div>
            
            <!-- Section 8: Relatives at Silverleaf -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">8. Relatives Employed at Silverleaf Academy</h2>
                <div id="relatives-container">
                    <!-- Relative items will be added here via JavaScript -->
                </div>
                <button type="button" onclick="addRelative()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                    + Add Relative
                </button>
            </div>
            
            <!-- Section 9: Qualifications -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">9. Academic & Professional Qualifications</h2>
                <div id="qualifications-container">
                    <!-- Qualification items will be added here via JavaScript -->
                </div>
                <button type="button" onclick="addQualification()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                    + Add Qualification
                </button>
            </div>
            
            <!-- Section 10: Employment History -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">10. Previous Employment History</h2>
                <div id="history-container">
                    <!-- Employment history items will be added here via JavaScript -->
                </div>
                <button type="button" onclick="addHistory()" class="bg-[#FFC952] text-[#002368] px-6 py-2 rounded-lg font-semibold hover:bg-[#002368] hover:text-[#FFFFFF] transition">
                    + Add Employment
                </button>
            </div>
            
            <!-- Section 11: Legal & Conduct Information -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">11. Legal & Conduct Information</h2>
                
                <!-- Arrest/Detention/Deportation -->
                <div class="mb-6">
                    <label class="block text-[#002368] font-semibold mb-3">Have you ever been arrested / detained / deported by any Police / Military / Authority either in Tanzania or abroad?</label>
                    <div class="flex space-x-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="arrest_record" value="Yes" class="w-5 h-5 text-[#002368] border-2 border-[#80BFEC] focus:ring-[#FFC952]">
                            <span class="ml-2 text-[#000000]">Yes</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="arrest_record" value="No" checked class="w-5 h-5 text-[#002368] border-2 border-[#80BFEC] focus:ring-[#FFC952]">
                            <span class="ml-2 text-[#000000]">No</span>
                        </label>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-[#002368] font-semibold mb-2">If yes, give details:</label>
                    <textarea name="arrest_details" rows="3" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition"></textarea>
                </div>
                
                <!-- Discharge/Resignation for Misconduct -->
                <div class="mb-6">
                    <label class="block text-[#002368] font-semibold mb-3">Have you ever been discharged or been forced to resign for misconduct or unsatisfactory service from any position?</label>
                    <div class="flex space-x-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="misconduct_record" value="Yes" class="w-5 h-5 text-[#002368] border-2 border-[#80BFEC] focus:ring-[#FFC952]">
                            <span class="ml-2 text-[#000000]">Yes</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="misconduct_record" value="No" checked class="w-5 h-5 text-[#002368] border-2 border-[#80BFEC] focus:ring-[#FFC952]">
                            <span class="ml-2 text-[#000000]">No</span>
                        </label>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-[#002368] font-semibold mb-2">If yes, give details:</label>
                    <textarea name="misconduct_details" rows="3" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition"></textarea>
                </div>
            </div>
            
            <!-- Section 12: References (Three persons NOT related) -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">12. References</h2>
                <p class="text-sm text-gray-600 mb-6">List three competent and responsible persons NOT related to you by blood or marriage who are qualified to supply definite information regarding your character and ability.</p>
                
                <!-- Reference 1 -->
                <div class="mb-8 p-6 bg-[#ECECEC] rounded-lg">
                    <h3 class="text-lg font-bold text-[#002368] mb-4">Reference 1</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="ref1_name" required class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Relationship</label>
                            <input type="text" name="ref1_relationship" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Phone</label>
                            <input type="tel" name="ref1_phone" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Email</label>
                            <input type="email" name="ref1_email" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Organization</label>
                            <input type="text" name="ref1_organization" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <!-- Reference 2 -->
                <div class="mb-8 p-6 bg-[#ECECEC] rounded-lg">
                    <h3 class="text-lg font-bold text-[#002368] mb-4">Reference 2</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="ref2_name" required class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Relationship</label>
                            <input type="text" name="ref2_relationship" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Phone</label>
                            <input type="tel" name="ref2_phone" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Email</label>
                            <input type="email" name="ref2_email" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Organization</label>
                            <input type="text" name="ref2_organization" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <!-- Reference 3 -->
                <div class="mb-8 p-6 bg-[#ECECEC] rounded-lg">
                    <h3 class="text-lg font-bold text-[#002368] mb-4">Reference 3</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="ref3_name" required class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Relationship</label>
                            <input type="text" name="ref3_relationship" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Phone</label>
                            <input type="tel" name="ref3_phone" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Email</label>
                            <input type="email" name="ref3_email" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#002368] font-semibold mb-2">Organization</label>
                            <input type="text" name="ref3_organization" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 13: Certification -->
            <div class="bg-[#FFFFFF] rounded-2xl shadow-lg p-8 hover-scale certification-box">
                <h2 class="text-2xl font-bold text-[#002368] mb-6 pb-3 border-b-4 border-[#FFC952]">13. Certification</h2>
                
                <div class="bg-yellow-50 border-l-4 border-[#FFC952] p-6 mb-6">
                    <p class="text-sm text-[#000000] leading-relaxed">
                        <strong class="text-[#002368]">IMPORTANT NOTICE:</strong> Before signing this form make sure you have answered all questions fully and completely. A false statement on this form is cause for dismissal or denial of offer for employment. <strong class="text-[#002368]">PLEASE NOTE THAT ALL INFORMATION GIVEN WILL BE VERIFIED.</strong>
                    </p>
                </div>
                
                <div class="mb-6">
                    <p class="text-lg font-semibold text-[#002368] mb-4">I DO SOLEMNLY AFFIRM THAT THE INFORMATION CONTAINED HEREIN IS CORRECT TO THE BEST OF MY KNOWLEDGE AND BELIEF.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Name of Employee (as usually written and which will be used officially)</label>
                        <input type="text" name="certification_name" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                        <p class="text-xs text-gray-500 mt-1">This name will be used officially for all documentation</p>
                    </div>
                    
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Date</label>
                        <input type="date" name="certification_date" class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition">
                    </div>
                </div>
                
                <div class="mt-6 flex items-center">
                    <input type="checkbox" name="certification_agreement" id="certification_agreement" required class="w-5 h-5 text-[#002368] border-2 border-[#80BFEC] focus:ring-[#FFC952]">
                    <label for="certification_agreement" class="ml-3 text-[#000000] font-semibold">
                        I hereby certify that all information provided in this form is true and complete to the best of my knowledge. <span class="text-red-500">*</span>
                    </label>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex justify-end gap-4 mt-8">
                <button type="reset" class="px-8 py-3 bg-gray-300 text-[#002368] rounded-lg font-semibold hover:bg-gray-400 transition">
                    Reset Form
                </button>
                <button type="submit" class="px-8 py-3 bg-[#002368] text-[#FFFFFF] rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition">
                    Submit Employee Bio-Data
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // Counters for dynamic elements
        let profCertCounter = 1;
        let acadCertCounter = 1;
        let childCounter = 0;
        let familyCounter = 0;
        let emergencyCounter = 0;
        let relativeCounter = 0;
        let qualificationCounter = 0;
        let historyCounter = 0;
        
        // Add Professional Certificate
        function addProfessionalCertificate() {
            profCertCounter++;
            const container = document.getElementById('professional-certificates-container');
            const newId = 'prof_cert_' + profCertCounter;
            const newItem = document.createElement('div');
            newItem.className = 'file-upload-area rounded-lg p-4 text-center mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <input type="file" name="professional_certificates[]" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="${newId}">
                <label for="${newId}" class="cursor-pointer block">
                    <div class="text-4xl mb-2">📜</div>
                    <div class="text-sm text-[#002368]">Click to upload professional certificate</div>
                    <div class="text-xs text-gray-500 mt-1">PDF, JPG (Max 5MB each)</div>
                </label>
            `;
            container.appendChild(newItem);
        }
        
        // Add Academic Certificate
        function addAcademicCertificate() {
            acadCertCounter++;
            const container = document.getElementById('academic-certificates-container');
            const newId = 'acad_cert_' + acadCertCounter;
            const newItem = document.createElement('div');
            newItem.className = 'file-upload-area rounded-lg p-4 text-center mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <input type="file" name="academic_certificates[]" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="${newId}">
                <label for="${newId}" class="cursor-pointer block">
                    <div class="text-4xl mb-2">🎓</div>
                    <div class="text-sm text-[#002368]">Click to upload academic certificate</div>
                    <div class="text-xs text-gray-500 mt-1">PDF, JPG (Max 5MB each)</div>
                </label>
            `;
            container.appendChild(newItem);
        }
        
        // Add Child
        function addChild() {
            childCounter++;
            const container = document.getElementById('children-container');
            const newItem = document.createElement('div');
            newItem.className = 'bg-[#ECECEC] p-4 rounded-lg mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Child's Full Name</label>
                        <input type="text" name="child_names[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Date of Birth</label>
                        <input type="date" name="child_dobs[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Gender</label>
                        <select name="child_genders[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="">Select</option>
                            <option value="MALE">Male</option>
                            <option value="FEMALE">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">School/Employer</label>
                        <input type="text" name="child_schools[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Contact Number</label>
                        <input type="tel" name="child_contacts[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        // Add Family Contact
        function addFamily() {
            familyCounter++;
            const container = document.getElementById('family-container');
            const newItem = document.createElement('div');
            newItem.className = 'bg-[#ECECEC] p-4 rounded-lg mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Full Name</label>
                        <input type="text" name="family_names[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Relationship</label>
                        <select name="family_relationships[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Guardian">Guardian</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Phone</label>
                        <input type="tel" name="family_phones[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Address</label>
                        <input type="text" name="family_addresses[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Occupation</label>
                        <input type="text" name="family_occupations[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        // Add Emergency Contact
        function addEmergency() {
            emergencyCounter++;
            const container = document.getElementById('emergency-container');
            const newItem = document.createElement('div');
            newItem.className = 'bg-[#ECECEC] p-4 rounded-lg mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Full Name</label>
                        <input type="text" name="emergency_names[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Relationship</label>
                        <input type="text" name="emergency_relationships[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Phone</label>
                        <input type="tel" name="emergency_phones[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Address</label>
                        <input type="text" name="emergency_addresses[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Priority</label>
                        <select name="emergency_priorities[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="Primary">Primary</option>
                            <option value="Secondary">Secondary</option>
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        // Add Relative
        function addRelative() {
            relativeCounter++;
            const container = document.getElementById('relatives-container');
            const newItem = document.createElement('div');
            newItem.className = 'bg-[#ECECEC] p-4 rounded-lg mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Full Name</label>
                        <input type="text" name="relative_names[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Relationship</label>
                        <input type="text" name="relative_relationships[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Position</label>
                        <input type="text" name="relative_positions[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Work Station</label>
                        <input type="text" name="relative_stations[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        // Add Qualification
        function addQualification() {
            qualificationCounter++;
            const container = document.getElementById('qualifications-container');
            const newItem = document.createElement('div');
            newItem.className = 'bg-[#ECECEC] p-4 rounded-lg mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Level</label>
                        <select name="qualification_levels[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                            <option value="">Select Level</option>
                            <option value="Certificate">Certificate</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Degree">Degree</option>
                            <option value="Masters">Masters</option>
                            <option value="PhD">PhD</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Qualification</label>
                        <input type="text" name="qualification_names[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Institution</label>
                        <input type="text" name="institutions[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Year Obtained</label>
                        <input type="number" name="years_obtained[]" min="1900" max="2099" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        // Add Employment History
        function addHistory() {
            historyCounter++;
            const container = document.getElementById('history-container');
            const newItem = document.createElement('div');
            newItem.className = 'bg-[#ECECEC] p-4 rounded-lg mb-4 relative';
            newItem.innerHTML = `
                <button type="button" onclick="removeItem(this)" class="absolute top-2 right-2 bg-red-500 text-white w-6 h-6 rounded-full hover:bg-red-600">×</button>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Employer</label>
                        <input type="text" name="previous_employers[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Position</label>
                        <input type="text" name="previous_positions[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Date From</label>
                        <input type="date" name="dates_from[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[#002368] font-semibold mb-2">Date To</label>
                        <input type="date" name="dates_to[]" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="block text-[#002368] font-semibold mb-2">Reason for Leaving</label>
                        <textarea name="leaving_reasons[]" rows="2" class="w-full px-4 py-2 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none"></textarea>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
        }
        
        // Remove Item
        function removeItem(button) {
            if (confirm('Are you sure you want to remove this item?')) {
                const item = button.closest('.bg-\\[\\#ECECEC\\], .file-upload-area');
                if (item) {
                    item.remove();
                }
            }
        }
        
        // File upload preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const label = this.closest('.file-upload-area').querySelector('.text-sm');
                if (label && this.files.length > 0) {
                    label.textContent = '📁 ' + this.files[0].name;
                }
            });
        });
        
        // Form validation
        document.getElementById('employeeForm').addEventListener('submit', function(e) {
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            // Check if certification checkbox is checked
            const certificationCheckbox = document.getElementById('certification_agreement');
            if (!certificationCheckbox.checked) {
                certificationCheckbox.classList.add('border-red-500');
                isValid = false;
                alert('You must certify that the information provided is true and complete.');
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields marked with * and accept the certification.');
            }
        });
    </script>
</body>
</html>