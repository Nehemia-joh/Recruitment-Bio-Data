<?php
// admin/index.php - Admin Login Page
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silverleaf Academy - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Merriweather Sans', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #002368 0%, #80BFEC 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-card w-full max-w-md rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#002368]">Silverleaf Academy</h1>
            <p class="text-gray-600 mt-2">Admin Portal Login</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-[#002368] font-semibold mb-2">Username</label>
                <input type="text" name="username" required 
                       class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition"
                       placeholder="Enter your username">
            </div>
            
            <div>
                <label class="block text-[#002368] font-semibold mb-2">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-3 rounded-lg border-2 border-[#80BFEC] focus:border-[#FFC952] focus:outline-none transition"
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" 
                    class="w-full bg-[#002368] text-white py-3 rounded-lg font-semibold hover:bg-[#FFC952] hover:text-[#002368] transition duration-300">
                Login to Dashboard
            </button>
        </form>
        
    </div>
</body>
</html>