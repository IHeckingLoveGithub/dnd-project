<?php
session_start();
require 'db.php';
require 'functions.php';

require_login();

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="character_report.csv"');
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, ['Character ID', 'Character Name', 'Player', 'Class', 'Race', 'Level', 'XP']);
    
    // Data
    $stmt = $pdo->query("SELECT * FROM view_character_growth");
    while($row = $stmt->fetch()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Fetch Reports Data
$growth_report = $pdo->query("SELECT * FROM view_character_growth")->fetchAll();
$quest_report = $pdo->query("SELECT * FROM view_quest_status")->fetchAll();

// Custom 3-table join for Inventory Summary if view not exists (though we created it in init.sql as view_inventory_summary)
// Let's check view_inventory_summary just in case, or write one here.
// In init.sql I wrote: CREATE VIEW view_inventory_summary ...
$inventory_report = $pdo->query("SELECT * FROM view_inventory_summary")->fetchAll();


echo get_header('Reports');
?>

<h2>Campaign Reports</h2>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Character Growth Report</h3>
        <a href="reports.php?export=csv" class="button">Export to CSV</a>
    </div>
    <p><em>Combines Characters, Users, Classes, and Races.</em></p>
    <table>
        <thead>
            <tr>
                <th>Character</th>
                <th>Player</th>
                <th>Class</th>
                <th>Race</th>
                <th>Level</th>
                <th>XP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($growth_report as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['character_name']); ?></td>
                <td><?php echo htmlspecialchars($r['player_name']); ?></td>
                <td><?php echo htmlspecialchars($r['class_name']); ?></td>
                <td><?php echo htmlspecialchars($r['race_name']); ?></td>
                <td><?php echo $r['level']; ?></td>
                <td><?php echo $r['xp']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Quest Participation</h3>
    <p><em>Combines Characters, Quests, and User progress.</em></p>
    <table>
        <thead>
            <tr>
                <th>Character</th>
                <th>Quest</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($quest_report as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['character_name']); ?></td>
                <td><?php echo htmlspecialchars($r['quest_title']); ?></td>
                <td><span class="badge <?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Global Inventory Summary</h3>
    <p><em>Combines Characters, Items, and Inventory listings.</em></p>
    <table>
        <thead>
            <tr>
                <th>Character</th>
                <th>Item</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Est. Value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($inventory_report as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['character_name']); ?></td>
                <td><?php echo htmlspecialchars($r['item_name']); ?></td>
                <td><?php echo $r['type']; ?></td>
                <td><?php echo $r['quantity']; ?></td>
                <td><?php echo $r['value'] * $r['quantity']; ?> gp</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>PDF Export</h3>
    <p>To export these reports as PDF, please use your browser's Print function (Ctrl+P / Cmd+P) and select "Save as PDF".</p>
    <button onclick="window.print()" class="button secondary">Print / Save as PDF</button>
</div>

<?php echo get_footer(); ?>
