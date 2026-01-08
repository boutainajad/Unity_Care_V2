<?php
session_start();

// Verifier si connecte et si ADMIN
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/conection.php';
$pdo = $conn;

$user = $_SESSION['user'];

try {
    // Compter les docteurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM doctors");
    $doctorCount = $stmt->fetchColumn();
    
    // Compter les patients
    $stmt = $pdo->query("SELECT COUNT(*) FROM patients");
    $patientCount = $stmt->fetchColumn();
    
    // Compter les rendez-vous
    $stmt = $pdo->query("SELECT COUNT(*) FROM appointments");
    $appointmentCount = $stmt->fetchColumn();
    
    // Compter les prescriptions
    $stmt = $pdo->query("SELECT COUNT(*) FROM prescriptions");
    $prescriptionCount = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $doctorCount = 0;
    $patientCount = 0;
    $appointmentCount = 0;
    $prescriptionCount = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Unity Care</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            color: #667eea;
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-card h2 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 16px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            animation: fadeIn 0.5s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s;
        }
        
        .quick-actions h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
            display: block;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .action-btn .icon {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>🏥 Unity Care - Admin Panel</h1>
    <div class="user-info">
        <div class="user-badge">
            👤 <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
        </div>
        <a href="logout.php" class="logout-btn">Deconnexion</a>
    </div>
</div>

<div class="container">
    <div class="welcome-card">
        <h2>👋 Bienvenue, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
        <p>Vous êtes connecte en tant qu'<strong>Administrateur</strong>. Vous avez accès à toutes les fonctionnalites du système.</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">👨‍⚕️</div>
            <h3><?php echo $doctorCount; ?></h3>
            <p>Docteurs</p>
        </div>
        
        <div class="stat-card">
            <div class="icon">🧑</div>
            <h3><?php echo $patientCount; ?></h3>
            <p>Patients</p>
        </div>
        
        <div class="stat-card">
            <div class="icon">📅</div>
            <h3><?php echo $appointmentCount; ?></h3>
            <p>Rendez-vous</p>
        </div>
        
        <div class="stat-card">
            <div class="icon">💊</div>
            <h3><?php echo $prescriptionCount; ?></h3>
            <p>Prescriptions</p>
        </div>
    </div>
    
    <div class="quick-actions">
        <h3>⚡ Actions Rapides</h3>
        <div class="actions-grid">
            <a href="pages/doctors.php" class="action-btn">
                <span class="icon">👨‍⚕️</span>
                Gerer les Docteurs
            </a>
            
            <a href="pages/patients.php" class="action-btn">
                <span class="icon">🧑</span>
                Gerer les Patients
            </a>
            
            <a href="pages/appointments.php" class="action-btn">
                <span class="icon">📅</span>
                Rendez-vous
            </a>
            
            <a href="pages/departments.php" class="action-btn">
                <span class="icon">🏢</span>
                Departements
            </a>
            
            <a href="pages/medications.php" class="action-btn">
                <span class="icon">💊</span>
                Medicaments
            </a>
            
            <a href="pages/prescriptions.php" class="action-btn">
                <span class="icon">📋</span>
                Prescriptions
            </a>
        </div>
    </div>
</div>

</body>
</html>