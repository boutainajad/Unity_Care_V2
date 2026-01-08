<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/conection.php';
$pdo = $conn;

$message = '';
$messageType = '';

// SUPPRIMER
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "Rendez-vous supprime!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// AJOUTER ou MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = (int)$_POST['doctor_id'];
    $patientId = (int)$_POST['patient_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $reason = trim($_POST['reason']);
    $status = $_POST['status'];
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE appointments SET doctor_id = :doctor_id, patient_id = :patient_id, 
                                  date = :date, time = :time, reason = :reason, status = :status WHERE id = :id");
            $stmt->execute([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'date' => $date,
                'time' => $time,
                'reason' => $reason,
                'status' => $status,
                'id' => $id
            ]);
            $message = "Rendez-vous modifie!";
            $messageType = "success";
        } else {
            $stmt = $pdo->prepare("INSERT INTO appointments (doctor_id, patient_id, date, time, reason, status) 
                                  VALUES (:doctor_id, :patient_id, :date, :time, :reason, :status)");
            $stmt->execute([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'date' => $date,
                'time' => $time,
                'reason' => $reason,
                'status' => $status
            ]);
            $message = "Rendez-vous ajoute!";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Recuperer tous les rendez vous
$stmt = $pdo->query("SELECT a.*, 
                     CONCAT(d.first_name, ' ', d.last_name) as doctor_name, 
                     CONCAT(p.first_name, ' ', p.last_name) as patient_name
                     FROM appointments a
                     LEFT JOIN doctors d ON a.doctor_id = d.id
                     LEFT JOIN patients p ON a.patient_id = p.id
                     ORDER BY a.date DESC, a.time DESC");
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtDoctors = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name, specialization FROM doctors ORDER BY first_name");
$doctors = $stmtDoctors->fetchAll(PDO::FETCH_ASSOC);

$stmtPatients = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM patients ORDER BY first_name");
$patients = $stmtPatients->fetchAll(PDO::FETCH_ASSOC);

$editAppointment = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editAppointment = $stmt->fetch(PDO::FETCH_ASSOC);
}

ob_start();
?>

<div class="page-header">
    <h1>📅 Gestion des Rendez-vous</h1>
    <button class="btn btn-primary" onclick="document.getElementById('aptModal').style.display='block'">
        ➕ Ajouter
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Docteur</th>
                <th>Date</th>
                <th>Heure</th>
                <th>Raison</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">Aucun rendez-vous</td>
                </tr>
            <?php else: ?>
                <?php foreach ($appointments as $apt): ?>
                <tr>
                    <td><?php echo $apt['id']; ?></td>
                    <td><?php echo htmlspecialchars($apt['patient_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($apt['doctor_name'] ?? 'N/A'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($apt['date'])); ?></td>
                    <td><?php echo date('H:i', strtotime($apt['time'])); ?></td>
                    <td><?php echo htmlspecialchars(substr($apt['reason'] ?? '', 0, 30)) . (strlen($apt['reason'] ?? '') > 30 ? '...' : ''); ?></td>
                    <td>
                        <?php
                        $statusClass = '';
                        $statusText = '';
                        switch($apt['status']) {
                            case 'scheduled': 
                                $statusClass = 'badge-warning'; 
                                $statusText = '⏳ Prevu';
                                break;
                            case 'done': 
                                $statusClass = 'badge-success'; 
                                $statusText = '✅ Fait';
                                break;
                            case 'cancelled': 
                                $statusClass = 'badge-danger'; 
                                $statusText = '❌ Annule';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                    <td>
                        <a href="?edit=<?php echo $apt['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
                        <a href="?delete=<?php echo $apt['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Supprimer?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="aptModal" class="modal" style="display: <?php echo $editAppointment ? 'block' : 'none'; ?>">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('aptModal').style.display='none'">&times;</span>
        <h2><?php echo $editAppointment ? '✏️ Modifier' : '➕ Ajouter'; ?></h2>
        
        <form method="POST">
            <?php if ($editAppointment): ?>
                <input type="hidden" name="id" value="<?php echo $editAppointment['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="patient_id">Patient *</label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">-- Selectionner --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>" 
                                    <?php echo (isset($editAppointment) && $editAppointment['patient_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="doctor_id">Docteur *</label>
                    <select id="doctor_id" name="doctor_id" required>
                        <option value="">-- Selectionner --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['id']; ?>" 
                                    <?php echo (isset($editAppointment) && $editAppointment['doctor_id'] == $d['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['full_name']); ?>
                                <?php if ($d['specialization']): ?>
                                    - <?php echo htmlspecialchars($d['specialization']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date">Date *</label>
                    <input type="date" id="date" name="date" 
                           value="<?php echo $editAppointment['date'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="time">Heure *</label>
                    <input type="time" id="time" name="time" 
                           value="<?php echo $editAppointment['time'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="status">Statut *</label>
                <select id="status" name="status" required>
                    <option value="scheduled" <?php echo (isset($editAppointment) && $editAppointment['status'] == 'scheduled') ? 'selected' : ''; ?>>⏳ Prevu</option>
                    <option value="done" <?php echo (isset($editAppointment) && $editAppointment['status'] == 'done') ? 'selected' : ''; ?>>✅ Fait</option>
                    <option value="cancelled" <?php echo (isset($editAppointment) && $editAppointment['status'] == 'cancelled') ? 'selected' : ''; ?>>❌ Annule</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="reason">Raison</label>
                <textarea id="reason" name="reason" rows="3"><?php echo $editAppointment['reason'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('aptModal').style.display='none'">❌ Annuler</button>
            </div>
        </form>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.btn-sm {
    padding: 6px 10px;
    font-size: 13px;
    margin-right: 5px;
}

.badge-warning { background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge-success { background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge-danger { background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow: auto;
}

.modal-content {
    background-color: #fefefe;
    margin: 3% auto;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover { color: #000; }

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #3b82f6;
    outline: none;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 4px solid #10b981;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 4px solid #ef4444;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
}
</style>

<script>
window.onclick = function(event) {
    const modal = document.getElementById('aptModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php
$content = ob_get_clean();
$user = $_SESSION['user'];
include __DIR__ . '/../layout.php';
?>