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
        $stmt = $pdo->prepare("DELETE FROM prescriptions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "Prescription supprimee!";
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
    $medicationId = (int)$_POST['medication_id'];
    $dosageInstructions = trim($_POST['dosage_instructions']);
    $date = $_POST['date'];
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE prescriptions SET doctor_id = :doctor_id, patient_id = :patient_id, 
                                  medication_id = :medication_id, dosage_instructions = :dosage_instructions, 
                                  date = :date WHERE id = :id");
            $stmt->execute([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'medication_id' => $medicationId,
                'dosage_instructions' => $dosageInstructions,
                'date' => $date,
                'id' => $id
            ]);
            $message = "Prescription modifiee!";
            $messageType = "success";
        } else {
            $stmt = $pdo->prepare("INSERT INTO prescriptions (doctor_id, patient_id, medication_id, dosage_instructions, date) 
                                  VALUES (:doctor_id, :patient_id, :medication_id, :dosage_instructions, :date)");
            $stmt->execute([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'medication_id' => $medicationId,
                'dosage_instructions' => $dosageInstructions,
                'date' => $date
            ]);
            $message = "Prescription ajoutee!";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Recuperer toutes les prescriptions
$stmt = $pdo->query("SELECT pres.*, 
                     CONCAT(d.first_name, ' ', d.last_name) as doctor_name, 
                     CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                     m.name as medication_name
                     FROM prescriptions pres
                     LEFT JOIN doctors d ON pres.doctor_id = d.id
                     LEFT JOIN patients p ON pres.patient_id = p.id
                     LEFT JOIN medications m ON pres.medication_id = m.id
                     ORDER BY pres.date DESC");
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recuperer les donnees pour les selects
$stmtDoctors = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM doctors ORDER BY first_name");
$doctors = $stmtDoctors->fetchAll(PDO::FETCH_ASSOC);

$stmtPatients = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM patients ORDER BY first_name");
$patients = $stmtPatients->fetchAll(PDO::FETCH_ASSOC);

$stmtMedications = $pdo->query("SELECT id, name FROM medications ORDER BY name");
$medications = $stmtMedications->fetchAll(PDO::FETCH_ASSOC);

$editPrescription = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editPrescription = $stmt->fetch(PDO::FETCH_ASSOC);
}

ob_start();
?>

<div class="page-header">
    <h1>📋 Gestion des Prescriptions</h1>
    <button class="btn btn-primary" onclick="document.getElementById('presModal').style.display='block'">
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
                <th>Date</th>
                <th>Patient</th>
                <th>Docteur</th>
                <th>Medicament</th>
                <th>Instructions</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($prescriptions)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">Aucune prescription</td>
                </tr>
            <?php else: ?>
                <?php foreach ($prescriptions as $pres): ?>
                <tr>
                    <td><?php echo $pres['id']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($pres['date'])); ?></td>
                    <td><?php echo htmlspecialchars($pres['patient_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($pres['doctor_name'] ?? 'N/A'); ?></td>
                    <td><strong><?php echo htmlspecialchars($pres['medication_name'] ?? 'N/A'); ?></strong></td>
                    <td><?php echo htmlspecialchars(substr($pres['dosage_instructions'] ?? '', 0, 40)) . (strlen($pres['dosage_instructions'] ?? '') > 40 ? '...' : ''); ?></td>
                    <td>
                        <a href="?edit=<?php echo $pres['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
                        <a href="?delete=<?php echo $pres['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Supprimer?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="presModal" class="modal" style="display: <?php echo $editPrescription ? 'block' : 'none'; ?>">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('presModal').style.display='none'">&times;</span>
        <h2><?php echo $editPrescription ? '✏️ Modifier' : '➕ Ajouter'; ?></h2>
        
        <form method="POST" id="presForm">
            <?php if ($editPrescription): ?>
                <input type="hidden" name="id" value="<?php echo $editPrescription['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="patient_id">Patient *</label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">-- Selectionner --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>" 
                                    <?php echo (isset($editPrescription) && $editPrescription['patient_id'] == $p['id']) ? 'selected' : ''; ?>>
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
                                    <?php echo (isset($editPrescription) && $editPrescription['doctor_id'] == $d['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="medication_id">Medicament *</label>
                    <select id="medication_id" name="medication_id" required>
                        <option value="">-- Selectionner --</option>
                        <?php foreach ($medications as $m): ?>
                            <option value="<?php echo $m['id']; ?>" 
                                    <?php echo (isset($editPrescription) && $editPrescription['medication_id'] == $m['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date">Date *</label>
                    <input type="date" id="date" name="date" 
                           value="<?php echo $editPrescription['date'] ?? date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="dosage_instructions">Instructions de dosage</label>
                <textarea id="dosage_instructions" name="dosage_instructions" rows="4" 
                          placeholder="Ex: Prendre 1 comprime 3 fois par jour..."><?php echo $editPrescription['dosage_instructions'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('presModal').style.display='none'">❌ Annuler</button>
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
// SOLUTION - Modal se ferme correctement apres Modifier
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('presModal');
    const form = document.getElementById('presForm');
    
    // Fermer le modal apres soumission du formulaire
    if (form) {
        form.addEventListener('submit', function() {
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Si on a un message de succes, s'assurer que le modal est ferme
    const hasSuccess = document.querySelector('.alert-success');
    if (hasSuccess && modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('presModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
});

// Fermer en cliquant dehors
window.onclick = function(event) {
    const modal = document.getElementById('presModal');
    if (event.target == modal) {
        modal.style.display = "none";
        document.body.style.overflow = 'auto';
    }
}

// Auto hide messages apres 5 secondes
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert-success, .alert-error');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>

<?php
$content = ob_get_clean();
$user = $_SESSION['user'];
include __DIR__ . '/../layout.php';
?>