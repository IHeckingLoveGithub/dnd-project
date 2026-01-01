<?php
session_start();
require 'db.php';
require 'functions.php';

require_login();

$player_id = $_SESSION['player_id'];
$message = '';
$error = '';

// Handle Create Campaign
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_campaign'])) {
    $name = trim($_POST['name']);
    $invite_code = substr(md5(uniqid(rand(), true)), 0, 6);
    
    // Handle Map Upload
    $map_path = null;
    if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['map_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = uniqid('map_') . '.' . $ext;
            $dest = 'uploads/maps/' . $new_name;
            if (!is_dir('uploads/maps')) {
                mkdir('uploads/maps', 0777, true);
            }
            if (move_uploaded_file($_FILES['map_image']['tmp_name'], $dest)) {
                $map_path = $new_name;
            } else {
                $error = "Failed to move uploaded map.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO campaigns (name, dm_id, invite_code, map_image) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $player_id, $invite_code, $map_path])) {
            $message = "Campaign '$name' created! Invite Code: <strong>$invite_code</strong>";
        } else {
            $error = "Database error.";
        }
    }
}

// Handle Join Campaign
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_campaign'])) {
    $code = trim($_POST['invite_code']);
    $char_id = $_POST['char_id'];
    
    // Verify code
    $stmt = $pdo->prepare("SELECT campaign_id FROM campaigns WHERE invite_code = ?");
    $stmt->execute([$code]);
    $camp = $stmt->fetch();
    
    if ($camp) {
        // Link character to campaign
        $upd = $pdo->prepare("UPDATE characters SET campaign_id = ? WHERE char_id = ? AND player_id = ?");
        $upd->execute([$camp['campaign_id'], $char_id, $player_id]);
        $message = "Joined campaign successfully!";
    } else {
        $error = "Invalid invite code.";
    }
}

// Fetch Data
// Campaigns I DM
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE dm_id = ?");
$stmt->execute([$player_id]);
$my_campaigns = $stmt->fetchAll();

// Campaigns I'm playing in (via characters)
$stmt = $pdo->prepare("
    SELECT c.name as campaign_name, ch.name as char_name, c.campaign_id
    FROM characters ch
    JOIN campaigns c ON ch.campaign_id = c.campaign_id
    WHERE ch.player_id = ?
");
$stmt->execute([$player_id]);
$playing_campaigns = $stmt->fetchAll();

// My available characters (not in a campaign yet)
$stmt = $pdo->prepare("SELECT * FROM characters WHERE player_id = ? AND campaign_id IS NULL");
$stmt->execute([$player_id]);
$avail_chars = $stmt->fetchAll();

echo get_header('Campaigns');
?>

<h2>Campaign Management</h2>

<?php if($message): ?>
    <div class="alert" style="background-color: #d4edda; color: #155724;"><?php echo $message; ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert"><?php echo $error; ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Create Campaign -->
    <div class="card">
        <h3>Create Campaign (DM Mode)</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Campaign Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Upload Map (Image)</label>
                <input type="file" name="map_image" accept="image/*">
            </div>
            <button type="submit" name="create_campaign" class="button">Create Campaign</button>
        </form>
    </div>

    <!-- Join Campaign -->
    <div class="card">
        <h3>Join a Campaign</h3>
        <?php if(count($avail_chars) > 0): ?>
        <form method="POST">
            <div class="form-group">
                <label>Select Character</label>
                <select name="char_id">
                    <?php foreach($avail_chars as $c): ?>
                        <option value="<?php echo $c['char_id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Invite Code</label>
                <input type="text" name="invite_code" required placeholder="e.g. a1b2c3">
            </div>
            <button type="submit" name="join_campaign" class="button">Join</button>
        </form>
        <?php else: ?>
            <p>You have no available characters to join with. <a href="characters.php?action=create">Create one first.</a></p>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Required DM Campaigns -->
    <div class="card">
        <h3>Campaigns You DM</h3>
        <?php if(count($my_campaigns) > 0): ?>
            <ul>
            <?php foreach($my_campaigns as $mc): ?>
                <li>
                    <strong><?php echo htmlspecialchars($mc['name']); ?></strong> 
                    (Code: <?php echo $mc['invite_code']; ?>)
                    <br>
                    <a href="game.php?id=<?php echo $mc['campaign_id']; ?>" class="button" style="font-size:0.8rem; padding: 5px 10px;">Launch VTT</a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You are not running any campaigns.</p>
        <?php endif; ?>
    </div>

    <!-- Playing Campaigns -->
    <div class="card">
        <h3>Campaigns You Play In</h3>
        <?php if(count($playing_campaigns) > 0): ?>
            <ul>
            <?php foreach($playing_campaigns as $pc): ?>
                <li>
                    <strong><?php echo htmlspecialchars($pc['campaign_name']); ?></strong> 
                    as <em><?php echo htmlspecialchars($pc['char_name']); ?></em>
                    <br>
                    <a href="game.php?id=<?php echo $pc['campaign_id']; ?>" class="button" style="font-size:0.8rem; padding: 5px 10px;">Enter Game</a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have not joined any campaigns.</p>
        <?php endif; ?>
    </div>
</div>

<?php echo get_footer(); ?>
