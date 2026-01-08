<?php
session_start();

if (isset($_SESSION['user'])) {
    switch ($_SESSION['user']['role']) {
        case 'ADMIN':
            header('Location: dashboard_admin.php');
            break;
        case 'DOCTOR':
            header('Location: dashboard_doctor.php');
            break;
        case 'PATIENT':
            header('Location: dashboard_patient.php');
            break;
        default:
            header('Location: dashboard_admin.php');
    }
    exit;
}

require_once __DIR__ . '/config/conection.php';
$pdo = $conn;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $email = $input;
        if (strpos($input, '@') === false) {
            $email = $input . '@unity.com';
        }
        
        // Chercher par email 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $userData = null;
            $referenceId = $user['reference_id'] ?? $user['id'];
            
            switch ($user['role']) {
                case 'ADMIN':
                    $userData = [
                        'first_name' => $user['first_name'] ?? 'Admin',
                        'last_name' => $user['last_name'] ?? 'User'
                    ];
                    break;
                    
                case 'DOCTOR':
                    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = :id");
                    $stmt->execute(['id' => $referenceId]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$userData) {
                        $userData = [
                            'first_name' => explode('@', $user['email'])[0],
                            'last_name' => 'Doctor'
                        ];
                    }
                    break;
                    
                case 'PATIENT':
                    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
                    $stmt->execute(['id' => $referenceId]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$userData) {
                        $userData = [
                            'first_name' => explode('@', $user['email'])[0],
                            'last_name' => 'Patient'
                        ];
                    }
                    break;
                    
                default:
                    $userData = [
                        'first_name' => 'User',
                        'last_name' => ''
                    ];
            }
            
            $_SESSION['user'] = [
                'id' => $referenceId,
                'email' => $user['email'],
                'username' => explode('@', $user['email'])[0],
                'role' => $user['role'],
                'first_name' => $userData['first_name'] ?? 'User',
                'last_name' => $userData['last_name'] ?? ''
            ];
            
            switch ($user['role']) {
                case 'ADMIN':
                    header('Location: dashboard_admin.php');
                    break;
                case 'DOCTOR':
                    header('Location: dashboard_doctor.php');
                    break;
                case 'PATIENT':
                    header('Location: dashboard_patient.php');
                    break;
                default:
                    header('Location: dashboard_admin.php');
            }
            exit;
        } else {
            $error = "Email ou mot de passe incorrect!";
        }
        
    } catch (PDOException $e) {
        $error = "Erreur de connexion: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Unity Care</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
            animation: shake 0.3s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-hint {
            font-size: 12px;
            color: #64748b;
            font-weight: 400;
            margin-top: 4px;
        }
        
        input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e1e8ed;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            color: #999;
            font-size: 13px;
            position: relative;
            z-index: 1;
        }
        
        
        
        
        
        .account-option {
            background: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 2px solid #e0f2fe;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .account-option:hover {
            border-color: #3b82f6;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }
        
        .account-option:last-child {
            margin-bottom: 0;
        }
        
        .account-option .role {
            font-weight: 600;
            color: #1e40af;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .account-option .credentials {
            font-size: 12px;
            color: #64748b;
        }
        
        .account-option .credentials span {
            color: #1e3a8a;
            font-weight: 500;
        }
        
    
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
            }
            
            .login-header h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <h2>🏥 Unity Care</h2>
        <p>Système de Gestion Medicale</p>
    </div>
    
    <?php if ($error): ?>
        <div class="error">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="email">
                Email ou Nom d'utilisateur
            </label>
            <input type="text" id="email" name="email" placeholder="admin" required autofocus>
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        
        <button type="submit">Se connecter</button>
    
        
   
    </form>
</div>

<script>
function fillCredentials(username, password) {
    document.getElementById('email').value = username;
    document.getElementById('password').value = password;
    document.getElementById('password').focus();
}
</script>

</body>
</html>