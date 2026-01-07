<?php $title = 'Dashboard'; ?>
<?php ob_start(); ?>

<h1>Dashboard</h1>
<p>Welcome to Contract Manager!</p>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
