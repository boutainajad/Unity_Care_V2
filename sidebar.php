<?php
if (!isset($user)) {
    return; 
}

$currentScript = $_SERVER['SCRIPT_NAME'];
$isInPagesFolder = (strpos($currentScript, '/pages/') !== false);

if ($isInPagesFolder) {
    $dashboardLink = '../dashboard.php';
    $patientsLink = 'patients.php';
    $doctorsLink = 'doctors.php';
    $departmentsLink = 'departments.php';
    $medicationsLink = 'medications.php';
    $appointmentsLink = 'appointments.php';
    $prescriptionsLink = 'prescriptions.php';
} else {
    $dashboardLink = 'dashboard.php';
    $patientsLink = 'pages/patients.php';
    $doctorsLink = 'pages/doctors.php';
    $departmentsLink = 'pages/departments.php';
    $medicationsLink = 'pages/medications.php';
    $appointmentsLink = 'pages/appointments.php';
    $prescriptionsLink = 'pages/prescriptions.php';
}
?>

<nav class="sidebar">
    <ul>
        <li><a href="<?php echo $dashboardLink; ?>">Dashboard</a></li>

        <?php if ($user['role'] === 'ADMIN'): ?>
            <li><a href="<?php echo $patientsLink; ?>">Patients</a></li>
            <li><a href="<?php echo $doctorsLink; ?>">Doctors</a></li>
            <li><a href="<?php echo $departmentsLink; ?>">Departments</a></li>
            <li><a href="<?php echo $medicationsLink; ?>">Medications</a></li>
            <li><a href="<?php echo $appointmentsLink; ?>">Appointments</a></li>
            <li><a href="<?php echo $prescriptionsLink; ?>">Prescriptions</a></li>
        <?php endif; ?>

        <?php if ($user['role'] === 'DOCTOR'): ?>
            <li><a href="<?php echo $appointmentsLink; ?>">My Appointments</a></li>
            <li><a href="<?php echo $prescriptionsLink; ?>">Prescriptions</a></li>
        <?php endif; ?>

        <?php if ($user['role'] === 'PATIENT'): ?>
            <li><a href="<?php echo $appointmentsLink; ?>">My Appointments</a></li>
            <li><a href="<?php echo $prescriptionsLink; ?>">My Prescriptions</a></li>
        <?php endif; ?>
    </ul>
</nav>