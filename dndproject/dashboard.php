<?php
session_start();
require 'db.php';
require 'functions.php';

require_login();

$player_id = $_SESSION['player_id'];

// Fetch characters
$stmt = $pdo->prepare("
    SELECT c.*, cl.name as class_name, r.name as race_name 
    FROM characters c 
    JOIN classes cl ON c.class_id = cl.class_id
    JOIN races r ON c.race_id = r.race_id
    WHERE c.player_id = ?
");
$stmt->execute([$player_id]);
$characters = $stmt->fetchAll();

// Fetch active quests for all user's characters
$stmt = $pdo->prepare("
    SELECT cq.*, q.title, c.name as char_name
    FROM character_quests cq
    JOIN quests q ON cq.quest_id = q.quest_id
    JOIN characters c ON cq.char_id = c.char_id
    WHERE c.player_id = ? AND cq.status = 'active'
");
$stmt->execute([$player_id]);
$active_quests = $stmt->fetchAll();

echo get_header('Dashboard');
?>

<h2>Dashboard</h2>
<p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>

<div class="dashboard-grid">
    <div class="card">
        <h3>Your Characters</h3>
        <?php if(count($characters) > 0): ?>
            <ul>
            <?php foreach($characters as $char): ?>
                <li>
                    <strong><?php echo htmlspecialchars($char['name']); ?></strong> 
                    (Lvl <?php echo $char['level']; ?> <?php echo $char['race_name'] . ' ' . $char['class_name']; ?>)
                    - <a href="characters.php?action=edit&id=<?php echo $char['char_id']; ?>">Edit</a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have no characters yet.</p>
        <?php endif; ?>
        <a href="characters.php" class="button">Manage Characters</a>
    </div>

    <div class="card">
        <h3>Active Quests</h3>
        <?php if(count($active_quests) > 0): ?>
            <ul>
            <?php foreach($active_quests as $quest): ?>
                <li>
                    <strong><?php echo htmlspecialchars($quest['title']); ?></strong> 
                    (<?php echo htmlspecialchars($quest['char_name']); ?>)
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No active quests.</p>
        <?php endif; ?>
        <a href="quests.php" class="button secondary">View All Quests</a>
    </div>
    
    <div class="card">
        <h3>Quick Actions</h3>
        <a href="characters.php?action=create" class="button">New Character</a>
        <a href="campaigns.php" class="button">Manage Campaigns</a>
        <a href="reports.php" class="button secondary">View Reports</a>
    </div>
</div>

<?php echo get_footer(); ?>
