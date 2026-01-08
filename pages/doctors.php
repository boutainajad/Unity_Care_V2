<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

// Database connection
require_once __DIR__ . '/../config/conection.php';
$pdo = $conn;

$message = '';
$messageType = '';

// DELETE DOCTOR
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "✅ Docteur supprime avec succès!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "❌ Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// ADD OR UPDATE DOCTOR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $specialization = trim($_POST['specialization']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // UPDATE
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("
                UPDATE doctors 
                SET first_name = :first_name, 
                    last_name = :last_name, 
                    specialization = :specialization, 
                    phone = :phone, 
                    email = :email, 
                    department_id = :department_id 
                WHERE id = :id
            ");
            $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'specialization' => $specialization,
                'phone' => $phone,
                'email' => $email,
                'department_id' => $departmentId,
                'id' => $id
            ]);
            $message = "✅ Docteur modifie avec succès!";
            $messageType = "success";
        } else {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO doctors (first_name, last_name, specialization, phone, email, department_id) 
                VALUES (:first_name, :last_name, :specialization, :phone, :email, :department_id)
            ");
            $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'specialization' => $specialization,
                'phone' => $phone,
                'email' => $email,
                'department_id' => $departmentId
            ]);
            $message = "✅ Docteur ajoute avec succès!";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "❌ Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// GET ALL DOCTORS
try {
    $stmt = $pdo->query("
        SELECT d.*, dep.name as department_name 
        FROM doctors d 
        LEFT JOIN departments dep ON d.department_id = dep.id 
        ORDER BY d.id DESC
    ");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $doctors = [];
    $message = "❌ Erreur lors de la recuperation des docteurs: " . $e->getMessage();
    $messageType = "error";
}

// GET ALL DEPARTMENTS
try {
    $stmtDep = $pdo->query("SELECT * FROM departments ORDER BY name");
    $departments = $stmtDep->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
}

// GET DOCTOR FOR EDIT
$editDoctor = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $editDoctor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "❌ Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

ob_start();
?>

<div class="page-header">
    <h1>👨‍⚕️ Gestion des Docteurs</h1>
    <button class="btn btn-primary" onclick="document.getElementById('doctorModal').style.display='block'">
        ➕ Ajouter un Docteur
    </button>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon">👨‍⚕️</div>
        <div class="stat-info">
            <h3><?php echo count($doctors); ?></h3>
            <p>Total Docteurs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏢</div>
        <div class="stat-info">
            <h3><?php echo count($departments); ?></h3>
            <p>Departements</p>
        </div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Prenom</th>
                <th>Nom</th>
                <th>Specialisation</th>
                <th>Telephone</th>
                <th>Email</th>
                <th>Departement</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($doctors)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">
                        😔 Aucun docteur trouve!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($doctors as $doctor): ?>
                <tr>
                    <td><?php echo $doctor['id']; ?></td>
                    <td><?php echo htmlspecialchars($doctor['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($doctor['last_name']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($doctor['specialization'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($doctor['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($doctor['email'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($doctor['department_name'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="?edit=<?php echo $doctor['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
                        <a href="?delete=<?php echo $doctor['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce docteur?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="doctorModal" class="modal" style="display: <?php echo $editDoctor ? 'block' : 'none'; ?>">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('doctorModal').style.display='none'">&times;</span>
        <h2><?php echo $editDoctor ? '✏️ Modifier le Docteur' : '➕ Ajouter un Docteur'; ?></h2>
        
        <form method="POST">
            <?php if ($editDoctor): ?>
                <input type="hidden" name="id" value="<?php echo $editDoctor['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Prenom *</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?php echo $editDoctor['first_name'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Nom *</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?php echo $editDoctor['last_name'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="specialization">Specialisation</label>
                    <input type="text" id="specialization" name="specialization" 
                           value="<?php echo $editDoctor['specialization'] ?? ''; ?>" 
                           placeholder="Ex: Cardiologie, Pediatrie...">
                </div>
                
                <div class="form-group">
                    <label for="department_id">Departement</label>
                    <select id="department_id" name="department_id">
                        <option value="">-- Selectionner --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo (isset($editDoctor) && $editDoctor['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Telephone</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo $editDoctor['phone'] ?? ''; ?>"
                           placeholder="Ex: 0612345678">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo $editDoctor['email'] ?? ''; ?>"
                           placeholder="Ex: docteur@unity.care">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('doctorModal').style.display='none'">❌ Annuler</button>
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

.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 3em;
}

.stat-info h3 {
    font-size: 2em;
    color: #667eea;
    margin-bottom: 5px;
}

.stat-info p {
    color: #666;
    font-size: 0.9em;
}

.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

th, td {
    padding: 15px;
    text-align: left;
}

tbody tr:nth-child(even) {
    background: #f8f9fa;
}

tbody tr:hover {
    background: #e9ecef;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
    margin-right: 5px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
.form-group select {
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 15px;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #667eea;
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
    
    .table-container {
        overflow-x: scroll;
    }
}
</style>

<script>
window.onclick = function(event) {
    const modal = document.getElementById('doctorModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Auto-hide alerts after 5 seconds
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