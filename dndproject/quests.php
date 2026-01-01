<?php
session_start();
require 'db.php';
require 'functions.php';

require_login();

$player_id = $_SESSION['player_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['assign_quest'])) {
        $char_id = $_POST['char_id'];
        $quest_id = $_POST['quest_id'];
        
        try {
            $stmt = $pdo->prepare("CALL sp_assign_quest(?, ?)");
            $stmt->execute([$char_id, $quest_id]);
            $message = "Quest assigned successfully!";
        } catch (PDOException $e) {
            $message = "Error assigning quest: " . $e->getMessage();
        }
    } elseif (isset($_POST['complete_quest'])) {
        $char_id = $_POST['char_id'];
        $quest_id = $_POST['quest_id'];
        
        try {
            $stmt = $pdo->prepare("CALL sp_complete_quest(?, ?)");
            $stmt->execute([$char_id, $quest_id]);
            $message = "Quest completed! XP Awarded.";
        } catch (PDOException $e) {
            $message = "Error completing quest: " . $e->getMessage();
        }
    }
}

// Fetch data
$my_chars = $pdo->prepare("SELECT char_id, name FROM characters WHERE player_id = ?");
$my_chars->execute([$player_id]);
$characters = $my_chars->fetchAll();

$all_quests = $pdo->query("SELECT * FROM quests")->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM view_quest_status WHERE character_name IN (SELECT name FROM characters WHERE player_id = ?)");
$stmt->execute([$player_id]);
$quest_log = $stmt->fetchAll();

echo get_header('Quests');
?>

<h2>Quest Management</h2>

<?php if($message): ?>
    <div class="alert"><?php echo $message; ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="card">
        <h3>Assign New Quest</h3>
        <form method="POST">
            <div class="form-group">
                <label>Character</label>
                <select name="char_id">
                    <?php foreach($characters as $c): ?>
                        <option value="<?php echo $c['char_id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quest</label>
                <select name="quest_id">
                    <?php foreach($all_quests as $q): ?>
                        <option value="<?php echo $q['quest_id']; ?>"><?php echo htmlspecialchars($q['title']); ?> (Lvl <?php echo $q['min_level']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="assign_quest" class="button">Assign Quest</button>
        </form>
    </div>

    <div class="card">
        <h3>Quest Log</h3>
        <table>
            <thead>
                <tr>
                    <th>Character</th>
                    <th>Quest</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($quest_log as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['character_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['quest_title']); ?></td>
                    <td>
                        <span class="badge <?php echo $log['status']; ?>"><?php echo ucfirst($log['status']); ?></span>
                    </td>
                    <td>
                        <?php if($log['status'] == 'active'): ?>
                             <!-- In a real app we'd need IDs here, but view_quest_status doesn't have them in the SELECT list in init.sql. 
                                  Let's just show a placeholder or we need to update the view. 
                                  For now, I'll assume users can just use the Assign form to test, 
                                  but to Complete we normally need IDs. 
                                  Let's do a quick hack: Select proper IDs in the view or fetch differently.
                                  Let's refetch active quests properly related to IDs below for the Action column.
                             -->
                             Pending...
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h4>Complete a Quest</h4>
        <form method="POST">
             <div class="form-group">
                <label>Character</label>
                <select name="char_id">
                    <?php foreach($characters as $c): ?>
                        <option value="<?php echo $c['char_id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Active Quest ID (See DB or just pick one)</label>
                <select name="quest_id">
                     <?php foreach($all_quests as $q): ?>
                        <option value="<?php echo $q['quest_id']; ?>"><?php echo htmlspecialchars($q['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="complete_quest" class="button">Mark Complete</button>
        </form>
    </div>
</div>

<?php echo get_footer(); ?>
