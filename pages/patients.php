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
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "Patient supprime avec succès!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// AJOUTER ou MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $gender = $_POST['gender'];
    $dateOfBirth = $_POST['date_of_birth'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // MODIFIER
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE patients SET first_name = :first_name, last_name = :last_name, 
                                  gender = :gender, date_of_birth = :date_of_birth, phone = :phone, 
                                  email = :email, address = :address WHERE id = :id");
            $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'id' => $id
            ]);
            $message = "Patient modifie avec succès!";
            $messageType = "success";
        } else {
            // AJOUTER
            $stmt = $pdo->prepare("INSERT INTO patients (first_name, last_name, gender, date_of_birth, phone, email, address) 
                                  VALUES (:first_name, :last_name, :gender, :date_of_birth, :phone, :email, :address)");
            $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth,
                'phone' => $phone,
                'email' => $email,
                'address' => $address
            ]);
            $message = "Patient ajoute avec succès!";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Recuperer tous les patients
try {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY id DESC");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $patients = [];
    $message = "Erreur: " . $e->getMessage();
    $messageType = "error";
}

// Recuperer un patient pour modification
$editPatient = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editPatient = $stmt->fetch(PDO::FETCH_ASSOC);
}

ob_start();
?>

<div class="page-header">
    <h1>👥 Gestion des Patients</h1>
    <button class="btn btn-primary" onclick="document.getElementById('patientModal').style.display='block'">
        ➕ Ajouter un Patient
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
                <th>Prenom</th>
                <th>Nom</th>
                <th>Genre</th>
                <th>Date Naissance</th>
                <th>Telephone</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($patients)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">
                        Aucun patient trouve. Ajoutez votre premier patient!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($patients as $patient): ?>
                <tr>
                    <td><?php echo $patient['id']; ?></td>
                    <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                    <td><?php echo $patient['gender'] == 'M' ? '👨 Homme' : '👩 Femme'; ?></td>
                    <td><?php echo $patient['date_of_birth'] ? date('d/m/Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></td>
                    <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></td>
                    <td>
                        <a href="?edit=<?php echo $patient['id']; ?>" class="btn btn-sm btn-warning">✏️ Modifier</a>
                        <a href="?delete=<?php echo $patient['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Supprimer ce patient?')">
                           🗑️ Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="patientModal" class="modal" style="display: <?php echo $editPatient ? 'block' : 'none'; ?>">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('patientModal').style.display='none'">&times;</span>
        <h2><?php echo $editPatient ? '✏️ Modifier le Patient' : '➕ Ajouter un Patient'; ?></h2>
        
        <form method="POST" action="">
            <?php if ($editPatient): ?>
                <input type="hidden" name="id" value="<?php echo $editPatient['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Prenom *</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?php echo $editPatient['first_name'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Nom *</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?php echo $editPatient['last_name'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="gender">Genre *</label>
                    <select id="gender" name="gender" required>
                        <option value="">-- Selectionner --</option>
                        <option value="M" <?php echo (isset($editPatient) && $editPatient['gender'] == 'M') ? 'selected' : ''; ?>>👨 Homme</option>
                        <option value="F" <?php echo (isset($editPatient) && $editPatient['gender'] == 'F') ? 'selected' : ''; ?>>👩 Femme</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_of_birth">Date de Naissance *</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" 
                           value="<?php echo $editPatient['date_of_birth'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Telephone</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo $editPatient['phone'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo $editPatient['email'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Adresse</label>
                <textarea id="address" name="address" rows="3"><?php echo $editPatient['address'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('patientModal').style.display='none'">
                    ❌ Annuler
                </button>
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
    padding: 6px 12px;
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

.close:hover {
    color: #000;
}

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
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('patientModal');
    const form = modal ? modal.querySelector('form') : null;
    if (form) {
        form.addEventListener('submit', function() {
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
    const hasSuccess = document.querySelector('.alert-success');
    if (hasSuccess && modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('patientModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
});
window.onclick = function(event) {
    const modal = document.getElementById('patientModal');
    if (event.target == modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
setTimeout(() => {
    document.querySelectorAll('.alert-success, .alert-error').forEach(alert => {
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