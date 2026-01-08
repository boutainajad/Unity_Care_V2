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
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "Departement supprime!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// AJOUTER ou MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE departments SET name = :name, location = :location WHERE id = :id");
            $stmt->execute(['name' => $name, 'location' => $location, 'id' => $id]);
            $message = "Departement modifie!";
            $messageType = "success";
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (name, location) VALUES (:name, :location)");
            $stmt->execute(['name' => $name, 'location' => $location]);
            $message = "Departement ajoute!";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Recuperer tous les departements
$stmt = $pdo->query("SELECT d.*, COUNT(doc.id) as doctor_count 
                     FROM departments d 
                     LEFT JOIN doctors doc ON d.id = doc.department_id 
                     GROUP BY d.id 
                     ORDER BY d.name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editDepartment = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editDepartment = $stmt->fetch(PDO::FETCH_ASSOC);
}

ob_start();
?>

<div class="page-header">
    <h1>🏢 Gestion des Departements</h1>
    <button class="btn btn-primary" onclick="document.getElementById('deptModal').style.display='block'">
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
                <th>Nom</th>
                <th>Localisation</th>
                <th>Docteurs</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($departments)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px;">Aucun departement</td>
                </tr>
            <?php else: ?>
                <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><?php echo $dept['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($dept['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($dept['location'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-info"><?php echo $dept['doctor_count']; ?></span></td>
                    <td>
                        <a href="?edit=<?php echo $dept['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
                        <a href="?delete=<?php echo $dept['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Supprimer?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="deptModal" class="modal" style="display: <?php echo $editDepartment ? 'block' : 'none'; ?>">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('deptModal').style.display='none'">&times;</span>
        <h2><?php echo $editDepartment ? '✏️ Modifier' : '➕ Ajouter'; ?></h2>
        
        <form method="POST">
            <?php if ($editDepartment): ?>
                <input type="hidden" name="id" value="<?php echo $editDepartment['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Nom *</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo $editDepartment['name'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="location">Localisation</label>
                <input type="text" id="location" name="location" 
                       value="<?php echo $editDepartment['location'] ?? ''; ?>">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('deptModal').style.display='none'">❌ Annuler</button>
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
    margin: 10% auto;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
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

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 20px;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input {
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 15px;
}

.form-group input:focus {
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('deptModal');
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
        const modal = document.getElementById('deptModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
});
window.onclick = function(event) {
    const modal = document.getElementById('deptModal');
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