<?php
session_start();

// Verifier si connecte et si PATIENT
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'PATIENT') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/conection.php';
$pdo = $conn;

$user = $_SESSION['user'];
$patientId = $user['id'];

try {
    // Compter RDV a venir
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = :patient_id AND date >= NOW()");
    $stmt->execute(['patient_id' => $patientId]);
    $upcomingAppointments = $stmt->fetchColumn();
    
    // Compter RDV passes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = :patient_id AND date < NOW()");
    $stmt->execute(['patient_id' => $patientId]);
    $pastAppointments = $stmt->fetchColumn();
    
    // Compter prescriptions actives 
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = :patient_id AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute(['patient_id' => $patientId]);
    $activePrescriptions = $stmt->fetchColumn();
    
    // Recuperer les prochains RDV
    $stmt = $pdo->prepare("
        SELECT a.*, 
               d.first_name as doctor_first_name, 
               d.last_name as doctor_last_name,
               d.specialization,
               a.reason,
               a.status
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = :patient_id 
        AND a.date >= NOW()
        ORDER BY a.date ASC
        LIMIT 5
    ");
    $stmt->execute(['patient_id' => $patientId]);
    $upcomingAppointmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
    $stmt->execute(['id' => $patientId]);
    $patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $upcomingAppointments = 0;
    $pastAppointments = 0;
    $activePrescriptions = 0;
    $upcomingAppointmentsList = [];
    $patientInfo = null;
}

function getStatusBadge($status) {
    switch($status) {
        case 'CONFIRMED':
            return '<div class="appointment-status confirmed">Confirme</div>';
        case 'PENDING':
            return '<div class="appointment-status pending">En attente</div>';
        case 'CANCELLED':
            return '<div class="appointment-status cancelled">Annule</div>';
        default:
            return '<div class="appointment-status">' . htmlspecialchars($status) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Patient - Unity Care</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            color: #3b82f6;
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
        
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s;
        }
        
        .info-card h3 {
            color: #3b82f6;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        
        .info-value {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }
        
        .action-btn .icon {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
        }
        
        .appointments-section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s;
        }
        
        .appointments-section h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .appointment-card {
            background: #eff6ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3b82f6;
        }
        
        .appointment-card:last-child {
            margin-bottom: 0;
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .appointment-date {
            font-weight: 600;
            color: #3b82f6;
            font-size: 16px;
        }
        
        .appointment-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .appointment-status.confirmed {
            background: #10b981;
            color: white;
        }
        
        .appointment-status.pending {
            background: #f59e0b;
            color: white;
        }
        
        .appointment-status.cancelled {
            background: #ef4444;
            color: white;
        }
        
        .appointment-doctor {
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .appointment-reason {
            color: #6b7280;
            font-size: 13px;
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
            
            .appointment-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>🏥 Unity Care - Espace Patient</h1>
    <div class="user-info">
        <div class="user-badge">
            🧑 <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
        </div>
        <a href="logout.php" class="logout-btn">Deconnexion</a>
    </div>
</div>

<div class="container">
    <div class="welcome-card">
        <h2>👋 Bienvenue, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
        <p>Votre espace personnel pour gerer vos rendez-vous medicaux et consulter vos prescriptions.</p>
    </div>
    
    <div class="info-cards">
        <div class="info-card">
            <h3>👤 Informations Personnelles</h3>
            <div class="info-item">
                <span class="info-label">Nom complet</span>
                <span class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <?php if ($patientInfo && isset($patientInfo['phone'])): ?>
            <div class="info-item">
                <span class="info-label">Telephone</span>
                <span class="info-value"><?php echo htmlspecialchars($patientInfo['phone']); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <span class="info-label">N° Patient</span>
                <span class="info-value">#<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></span>
            </div>
        </div>
        
        <div class="info-card">
            <h3>📊 Statistiques</h3>
            <div class="info-item">
                <span class="info-label">RDV à venir</span>
                <span class="info-value"><?php echo $upcomingAppointments; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">RDV passes</span>
                <span class="info-value"><?php echo $pastAppointments; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Prescriptions actives</span>
                <span class="info-value"><?php echo $activePrescriptions; ?></span>
            </div>
        </div>
    </div>
    
    <div class="quick-actions">
        <h3>⚡ Actions Rapides</h3>
        <div class="actions-grid">
            <a href="pages/appointments.php" class="action-btn">
                <span class="icon">📅</span>
                Prendre RDV
            </a>
            
            <a href="pages/appointments.php" class="action-btn">
                <span class="icon">🕐</span>
                Mes RDV
            </a>
            
            <a href="pages/prescriptions.php" class="action-btn">
                <span class="icon">📋</span>
                Mes Prescriptions
            </a>
            
            <a href="#" class="action-btn">
                <span class="icon">📞</span>
                Contacter Clinique
            </a>
        </div>
    </div>
    
    <div class="appointments-section">
        <h3>📅 Mes Prochains Rendez-vous</h3>
        
        <?php if (empty($upcomingAppointmentsList)): ?>
            <div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                <p>Aucun rendez-vous à venir</p>
                <p style="margin-top: 10px;"><a href="pages/appointments.php" style="color: #3b82f6;">Prendre un rendez-vous</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($upcomingAppointmentsList as $apt): ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <div class="appointment-date">
                            📅 <?php echo date('l d F Y - H:i', strtotime($apt['date'])); ?>
                        </div>
                        <?php echo getStatusBadge($apt['status']); ?>
                    </div>
                    <div class="appointment-doctor">
                        👨‍⚕️ Dr. <?php echo htmlspecialchars($apt['doctor_first_name'] . ' ' . $apt['doctor_last_name']); ?>
                        <?php if ($apt['specialization']): ?>
                            - <?php echo htmlspecialchars($apt['specialization']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($apt['reason']): ?>
                        <div class="appointment-reason">
                            💬 <?php echo htmlspecialchars($apt['reason']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>