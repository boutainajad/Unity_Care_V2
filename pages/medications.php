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
        $stmt = $pdo->prepare("DELETE FROM medications WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = "Medicament supprime!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// AJOUTER ou MODIFIER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $instructions = trim($_POST['instructions']);
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE medications SET name = :name, instructions = :instructions WHERE id = :id");
            $stmt->execute(['name' => $name, 'instructions' => $instructions, 'id' => $id]);
            $message = "Medicament modifie!";
            $messageType = "success";
        } else {
            $stmt = $pdo->prepare("INSERT INTO medications (name, instructions) VALUES (:name, :instructions)");
            $stmt->execute(['name' => $name, 'instructions' => $instructions]);
            $message = "Medicament ajoute!";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
    }
}

// Recuperer tous les medicaments
$stmt = $pdo->query("SELECT m.*, COUNT(p.id) as prescription_count 
                     FROM medications m 
                     LEFT JOIN prescriptions p ON m.id = p.medication_id 
                     GROUP BY m.id 
                     ORDER BY m.name");
$medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editMedication = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM medications WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $editMedication = $stmt->fetch(PDO::FETCH_ASSOC);
}

ob_start();
?>

<div class="page-header">
    <h1>💊 Gestion des Medicaments</h1>
    <button class="btn btn-primary" onclick="document.getElementById('medModal').style.display='block'">
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
                <th>Instructions</th>
                <th>Prescriptions</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($medications)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px;">Aucun medicament</td>
                </tr>
            <?php else: ?>
                <?php foreach ($medications as $med): ?>
                <tr>
                    <td><?php echo $med['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($med['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars(substr($med['instructions'] ?? '', 0, 50)) . (strlen($med['instructions'] ?? '') > 50 ? '...' : ''); ?></td>
                    <td><span class="badge badge-info"><?php echo $med['prescription_count']; ?></span></td>
                    <td>
                        <a href="?edit=<?php echo $med['id']; ?>" class="btn btn-sm btn-warning">✏️</a>
                        <a href="?delete=<?php echo $med['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Supprimer?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="medModal" class="modal" style="display: <?php echo $editMedication ? 'block' : 'none'; ?>">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('medModal').style.display='none'">&times;</span>
        <h2><?php echo $editMedication ? '✏️ Modifier' : '➕ Ajouter'; ?></h2>
        
        <form method="POST" id="medForm">
            <?php if ($editMedication): ?>
                <input type="hidden" name="id" value="<?php echo $editMedication['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Nom *</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo $editMedication['name'] ?? ''; ?>" 
                       placeholder="Ex: Paracetamol, Aspirine..." required>
            </div>
            
            <div class="form-group">
                <label for="instructions">Instructions</label>
                <textarea id="instructions" name="instructions" rows="4" 
                          placeholder="Ex: Prendre 1 comprime 3 fois par jour..."><?php echo $editMedication['instructions'] ?? ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Enregistrer</button>
                <button type="button" class="btn btn-secondary" 
                        onclick="document.getElementById('medModal').style.display='none'">❌ Annuler</button>
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

.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.modal {
    display: none;
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
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.close:hover { 
    color: #000; 
}

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

.form-group input,
.form-group textarea {
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 15px;
    font-family: inherit;
    transition: border-color 0.3s;
}

.form-group input:focus,
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
    animation: slideIn 0.3s ease;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 4px solid #ef4444;
    margin-bottom: 20px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(-20px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('medModal');
    const form = document.getElementById('medForm');
    
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
        const modal = document.getElementById('medModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
});

window.onclick = function(event) {
    const modal = document.getElementById('medModal');
    if (event.target == modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Auto-hide messages
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