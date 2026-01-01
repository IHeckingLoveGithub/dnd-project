<?php
session_start();
require 'db.php';
require 'functions.php';

require_login();

if (!isset($_GET['id'])) {
    header('Location: campaigns.php');
    exit;
}

$campaign_id = $_GET['id'];
$player_id = $_SESSION['player_id'];

// Fetch Campaign
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE campaign_id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    echo "Campaign not found.";
    exit;
}

// Fetch Characters in this campaign
$stmt = $pdo->prepare("SELECT * FROM characters WHERE campaign_id = ?");
$stmt->execute([$campaign_id]);
$characters = $stmt->fetchAll();

// Determine if I am DM
$is_dm = ($campaign['dm_id'] == $player_id);

// My Character(s) to control (if not DM, though DM can control all optionally, but let's stick to simple: DM sees all, players control theirs)
// Simple rule: You can move your own characters. DM can move anyone.
$my_char_ids = [];
if ($is_dm) {
    foreach ($characters as $c) $my_char_ids[] = $c['char_id'];
} else {
    foreach ($characters as $c) {
        if ($c['player_id'] == $player_id) {
            $my_char_ids[] = $c['char_id'];
        }
    }
}

$map_url = $campaign['map_image'] ? 'uploads/maps/' . $campaign['map_image'] : 'https://via.placeholder.com/1024x1024?text=No+Map';

echo get_header($campaign['name'] . ' - VTT');
?>

<style>
    .game-container {
        display: flex;
        gap: 20px;
    }
    .map-area {
        position: relative;
        width: 1024px; /* 32 * 32px */
        height: 1024px;
        border: 2px solid #333;
        background-image: url('<?php echo htmlspecialchars($map_url); ?>');
        background-size: cover; /* or contain, or 100% 100% */
        background-position: center;
        overflow: hidden;
    }
    .grid-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: grid;
        grid-template-columns: repeat(32, 1fr);
        grid-template-rows: repeat(32, 1fr);
        pointer-events: none; /* Let clicks pass through to tokens/map */
    }
    .grid-cell {
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .token {
        position: absolute;
        width: 32px;
        height: 32px;
        background-color: rgba(255, 0, 0, 0.7);
        border: 2px solid white;
        border-radius: 50%;
        color: white;
        font-size: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: grab;
        user-select: none;
        z-index: 10;
        transition: top 0.2s, left 0.2s; /* Smooth movement on update */
        background-size: cover;
    }
    .token.mine {
        border-color: #ffd700;
        cursor: grab;
        z-index: 20;
    }
    .token:active {
        cursor: grabbing;
    }
    .token-label {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.7);
        padding: 2px 5px;
        border-radius: 3px;
        white-space: nowrap;
        pointer-events: none;
    }
    .sidebar {
        width: 300px;
        background: #fff;
        padding: 15px;
        border: 1px solid #ccc;
    }
</style>

<div class="game-container">
    <div class="map-area" id="game-map">
        <!-- Grid -->
        <div class="grid-overlay">
            <?php for($i=0; $i<32*32; $i++): ?>
                <div class="grid-cell"></div>
            <?php endfor; ?>
        </div>
        
        <!-- Tokens rendered via JS or initially here -->
        <?php foreach($characters as $c): ?>
            <?php 
                $is_mine = in_array($c['char_id'], $my_char_ids); 
                $left = $c['pos_x'] * 32;
                $top = $c['pos_y'] * 32;
                $bg_image = $c['token_image'] == 'default_token.png' ? '' : "background-image: url('uploads/tokens/" . htmlspecialchars($c['token_image']) . "');";
            ?>
            <div class="token <?php echo $is_mine ? 'mine' : ''; ?>" 
                 id="token-<?php echo $c['char_id']; ?>"
                 data-id="<?php echo $c['char_id']; ?>"
                 data-x="<?php echo $c['pos_x']; ?>"
                 data-y="<?php echo $c['pos_y']; ?>"
                 draggable="<?php echo $is_mine ? 'true' : 'false'; ?>"
                 style="left: <?php echo $left; ?>px; top: <?php echo $top; ?>px; <?php echo $bg_image; ?>">
                 <?php if(!$bg_image): ?>
                    <?php echo substr($c['name'], 0, 2); ?>
                 <?php endif; ?>
                 <div class="token-label"><?php echo htmlspecialchars($c['name']); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="sidebar">
        <h3><?php echo htmlspecialchars($campaign['name']); ?></h3>
        <p>Invite Code: <strong><?php echo $campaign['invite_code']; ?></strong></p>
        
        <h4>Characters</h4>
        <ul>
            <?php foreach($characters as $c): ?>
                <li>
                    <?php echo htmlspecialchars($c['name']); ?> 
                    (<?php echo $c['pos_x'] . ',' . $c['pos_y']; ?>)
                </li>
            <?php endforeach; ?>
        </ul>
        
        <hr>
        <p><small>Drag tokens to move. Updates are real-time.</small></p>
        <a href="campaigns.php" class="button secondary">Exit Game</a>
    </div>
</div>

<!-- Pass accessible character IDs to JS -->
<script>
    const MY_CHAR_IDS = <?php echo json_encode($my_char_ids); ?>;
    const CAMPAIGN_ID = <?php echo $campaign_id; ?>;
</script>
<script src="js/vtt.js"></script>

<?php echo get_footer(); ?>
