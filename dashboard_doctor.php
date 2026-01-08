<?php
session_start();

// Verifier si connecte et si DOCTOR
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'DOCTOR') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/conection.php';
$pdo = $conn;

$user = $_SESSION['user'];
$doctorId = $user['id'];

try {
    // Compter MES patients
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = :doctor_id");
    $stmt->execute(['doctor_id' => $doctorId]);
    $myPatientCount = $stmt->fetchColumn();
    
    // Compter RDV aujourd'hui
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND DATE(date) = CURDATE()");
    $stmt->execute(['doctor_id' => $doctorId]);
    $todayAppointments = $stmt->fetchColumn();
    
    // Compter prescriptions ce mois
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE doctor_id = :doctor_id AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
    $stmt->execute(['doctor_id' => $doctorId]);
    $monthPrescriptions = $stmt->fetchColumn();
    
    // Compter RDV en attente
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND status = 'PENDING'");
    $stmt->execute(['doctor_id' => $doctorId]);
    $pendingAppointments = $stmt->fetchColumn();
    
    // Recuperer RDV d'aujourd'hui
    $stmt = $pdo->prepare("
        SELECT a.*, 
               p.first_name as patient_first_name, 
               p.last_name as patient_last_name,
               a.reason
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = :doctor_id 
        AND DATE(a.date) = CURDATE()
        ORDER BY a.date ASC
        LIMIT 5
    ");
    $stmt->execute(['doctor_id' => $doctorId]);
    $todayAppointmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $myPatientCount = 0;
    $todayAppointments = 0;
    $monthPrescriptions = 0;
    $pendingAppointments = 0;
    $todayAppointmentsList = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Docteur - Unity Care</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            color: #10b981;
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        
        .action-btn .icon {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
        }
        
        .schedule {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-top: 30px;
            animation: fadeIn 0.5s;
        }
        
        .schedule h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .appointment-item {
            background: #f0fdf4;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #10b981;
        }
        
        .appointment-item:last-child {
            margin-bottom: 0;
        }
        
        .appointment-time {
            font-weight: 600;
            color: #10b981;
            margin-bottom: 5px;
        }
        
        .appointment-patient {
            color: #333;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>🏥 Unity Care - Espace Docteur</h1>
    <div class="user-info">
        <div class="user-badge">
            👨‍⚕️ Dr. <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
        </div>
        <a href="logout.php" class="logout-btn">Deconnexion</a>
    </div>
</div>

<div class="container">
    <div class="welcome-card">
        <h2>👋 Bienvenue, Dr. <?php echo htmlspecialchars($user['last_name']); ?>!</h2>
        <p>Votre espace personnel pour gerer vos consultations, patients et prescriptions.</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">🧑</div>
            <h3><?php echo $myPatientCount; ?></h3>
            <p>Mes Patients</p>
        </div>
        
        <div class="stat-card">
            <div class="icon">📅</div>
            <h3><?php echo $todayAppointments; ?></h3>
            <p>RDV Aujourd'hui</p>
        </div>
        
        <div class="stat-card">
            <div class="icon">💊</div>
            <h3><?php echo $monthPrescriptions; ?></h3>
            <p>Prescriptions ce mois</p>
        </div>
        
        <div class="stat-card">
            <div class="icon">⏰</div>
            <h3><?php echo $pendingAppointments; ?></h3>
            <p>RDV en attente</p>
        </div>
    </div>
    
    <div class="quick-actions">
        <h3>⚡ Actions Rapides</h3>
        <div class="actions-grid">
            <a href="pages/appointments.php" class="action-btn">
                <span class="icon">📅</span>
                Mes Rendez-vous
            </a>
            
            <a href="pages/patients.php" class="action-btn">
                <span class="icon">🧑</span>
                Mes Patients
            </a>
            
            <a href="pages/prescriptions.php" class="action-btn">
                <span class="icon">📋</span>
                Creer Prescription
            </a>
            
            <a href="pages/medications.php" class="action-btn">
                <span class="icon">💊</span>
                Medicaments
            </a>
        </div>
    </div>
    
    <div class="schedule">
        <h3>📅 Rendez-vous d'Aujourd'hui</h3>
        
        <?php if (empty($todayAppointmentsList)): ?>
            <div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                <p>Aucun rendez-vous aujourd'hui</p>
            </div>
        <?php else: ?>
            <?php foreach ($todayAppointmentsList as $apt): ?>
                <div class="appointment-item">
                    <div class="appointment-time">
                        ⏰ <?php echo date('H:i', strtotime($apt['date'])); ?>
                    </div>
                    <div class="appointment-patient">
                        👤 Patient: <?php echo htmlspecialchars($apt['patient_first_name'] . ' ' . $apt['patient_last_name']); ?>
                        <?php if ($apt['reason']): ?>
                            <br>💬 <?php echo htmlspecialchars($apt['reason']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>