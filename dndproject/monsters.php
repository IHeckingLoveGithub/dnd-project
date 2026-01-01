<?php
session_start();
require 'db.php';
require 'functions.php';
require_login();

$monsters = $pdo->query("SELECT * FROM monsters ORDER BY name")->fetchAll();

echo get_header('Monsters');
?>

<h2>Monster Bestiary</h2>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>HP</th>
            <th>XP Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($monsters as $m): ?>
        <tr>
            <td><?php echo htmlspecialchars($m['name']); ?></td>
            <td><?php echo $m['type']; ?></td>
            <td><?php echo $m['hp']; ?></td>
            <td><?php echo $m['xp_value']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php echo get_footer(); ?>
