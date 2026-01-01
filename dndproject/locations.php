<?php
session_start();
require 'db.php';
require 'functions.php';
require_login();

$locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();

echo get_header('Locations');
?>

<h2>Locations</h2>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Coordinates</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($locations as $l): ?>
        <tr>
            <td><?php echo htmlspecialchars($l['name']); ?></td>
            <td><?php echo $l['coordinates']; ?></td>
            <td><?php echo htmlspecialchars($l['description']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php echo get_footer(); ?>
