<?php
session_start();
require 'db.php';
require 'functions.php';

require_login();

$player_id = $_SESSION['player_id'];
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_character'])) {
        $name = $_POST['name'];
        $class_id = $_POST['class_id'];
        $race_id = $_POST['race_id'];
        
        $token_path = 'default_token.png';
        if (isset($_FILES['token_image']) && $_FILES['token_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['token_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_name = uniqid('token_') . '.' . $ext;
                $dest = 'uploads/tokens/' . $new_name;
                // Ensure directory exists
                if (!is_dir('uploads/tokens')) mkdir('uploads/tokens', 0777, true);
                
                if (move_uploaded_file($_FILES['token_image']['tmp_name'], $dest)) {
                    $token_path = $new_name;
                }
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO characters (player_id, name, class_id, race_id, token_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$player_id, $name, $class_id, $race_id, $token_path]);
        $message = "Character created!";
        $action = 'list';
    } elseif (isset($_POST['update_pos'])) {
        // Fallback for non-js movement
         $char_id = $_POST['char_id'];
         $x = $_POST['x'];
         $y = $_POST['y'];
         
         // Verify ownership
         $stmt = $pdo->prepare("UPDATE characters SET pos_x = ?, pos_y = ? WHERE char_id = ? AND player_id = ?");
         $stmt->execute([$x, $y, $char_id, $player_id]);
         $message = "Position updated.";
    }
}

// Data fetching helpers
$classes = $pdo->query("SELECT * FROM classes")->fetchAll();
$races = $pdo->query("SELECT * FROM races")->fetchAll();

echo get_header('Characters');
?>

<h2>Character Management</h2>

<?php if($message): ?>
    <div class="alert"><?php echo $message; ?></div>
<?php endif; ?>

<?php if($action == 'create'): ?>
    <div class="card">
        <h3>Create New Character</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_id">
                    <?php foreach($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>"><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Race</label>
                <select name="race_id">
                    <?php foreach($races as $r): ?>
                        <option value="<?php echo $r['race_id']; ?>"><?php echo $r['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Token Image (Optional)</label>
                <input type="file" name="token_image" accept="image/*">
            </div>
            <button type="submit" name="create_character" class="button">Create</button>
            <a href="characters.php" class="button secondary">Cancel</a>
        </form>
    </div>

<?php else: ?>
    <div style="margin-bottom: 20px;">
        <a href="characters.php?action=create" class="button">Create New Character</a>
    </div>

    <?php
    $stmt = $pdo->prepare("
        SELECT c.*, cl.name as class_name, r.name as race_name 
        FROM characters c 
        JOIN classes cl ON c.class_id = cl.class_id
        JOIN races r ON c.race_id = r.race_id
        WHERE c.player_id = ?
    ");
    $stmt->execute([$player_id]);
    $chars = $stmt->fetchAll();
    ?>

    <table>
        <thead>
            <tr>
                <th>Token</th>
                <th>Name</th>
                <th>Class</th>
                <th>Race</th>
                <th>Level</th>
                <th>XP</th>
                <th>Position (X, Y)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($chars as $c): ?>
            <tr>
                <td>
                    <?php if($c['token_image']): ?>
                        <img src="uploads/tokens/<?php echo htmlspecialchars($c['token_image']); ?>" alt="Token" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #ccc; display: flex; align-items: center; justify-content: center; font-size: 10px;">
                            <?php echo substr($c['name'], 0, 2); ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($c['name']); ?></td>
                <td><?php echo $c['class_name']; ?></td>
                <td><?php echo $c['race_name']; ?></td>
                <td><?php echo $c['level']; ?></td>
                <td><?php echo $c['xp']; ?></td>
                <td>
                    <span id="pos-display-<?php echo $c['char_id']; ?>"><?php echo $c['pos_x'] . ', ' . $c['pos_y']; ?></span>
                    <button onclick="moveToken(<?php echo $c['char_id']; ?>)" style="font-size: 0.8em; margin-left:10px;">Move</button>
                </td>
                <td>
                   <a href="inventory.php?char_id=<?php echo $c['char_id']; ?>">Inventory</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <script>
    function moveToken(charId) {
        let newX = prompt("Enter new X coordinate:");
        let newY = prompt("Enter new Y coordinate:");
        
        if(newX !== null && newY !== null) {
            fetch('move_token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'char_id=' + charId + '&x=' + newX + '&y=' + newY
            })
            .then(response => response.text())
            .then(data => {
                if(data.trim() === 'success') {
                    document.getElementById('pos-display-' + charId).innerText = newX + ', ' + newY;
                } else {
                    alert('Error moving token: ' + data);
                }
            });
        }
    }
    </script>

<?php endif; ?>

<?php echo get_footer(); ?>
