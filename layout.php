<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

$currentDir = dirname($_SERVER['SCRIPT_NAME']);
$isInSubfolder = (strpos($currentDir, '/pages') !== false);
$assetsPath = $isInSubfolder ? '../assets/style.css' : 'assets/style.css';
$logoutPath = $isInSubfolder ? '../logout.php' : 'logout.php';
$dashboardPath = $isInSubfolder ? '../dashboard.php' : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unity Care Clinic</title>
    <link rel="stylesheet" href="<?php echo $assetsPath; ?>">
</head>
<body>

<header>
    <h2>Unity Care Clinic</h2>
    <a href="<?php echo $logoutPath; ?>">Logout</a>
</header>

<div class="container">
    <?php include __DIR__ . '/sidebar.php'; ?>  
    <main>
        <?php echo $content ?? ''; ?>
    </main>
</div>

</body>
</html>