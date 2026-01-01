<?php
session_start();
require 'db.php';
require 'functions.php';

require_login();

$player_id = $_SESSION['player_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $char_id = $_POST['char_id'];
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];
    
    // Check if item already exists in inventory
    $check = $pdo->prepare("SELECT inv_id, quantity FROM inventory WHERE char_id = ? AND item_id = ?");
    $check->execute([$char_id, $item_id]);
    $existing = $check->fetch();
    
    if ($existing) {
        $new_qty = $existing['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE inv_id = ?");
        $stmt->execute([$new_qty, $existing['inv_id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO inventory (char_id, item_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$char_id, $item_id, $quantity]);
    }
    $message = "Item added to inventory.";
}

// Fetch Data
$my_chars = $pdo->prepare("SELECT char_id, name FROM characters WHERE player_id = ?");
$my_chars->execute([$player_id]);
$characters = $my_chars->fetchAll();

$items = $pdo->query("SELECT * FROM items ORDER BY name")->fetchAll();

// Get inventory view for user's characters
$stmt = $pdo->prepare("
    SELECT i.name as item_name, i.type, i.value, inv.quantity, c.name as char_name
    FROM inventory inv
    JOIN items i ON inv.item_id = i.item_id
    JOIN characters c ON inv.char_id = c.char_id
    WHERE c.player_id = ?
");
$stmt->execute([$player_id]);
$inventory = $stmt->fetchAll();

echo get_header('Inventory');
?>

<h2>Inventory Management</h2>

<?php if($message): ?>
    <div class="alert"><?php echo $message; ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="card">
        <h3>Add Item</h3>
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
                <label>Item</label>
                <select name="item_id">
                    <?php foreach($items as $i): ?>
                        <option value="<?php echo $i['item_id']; ?>"><?php echo htmlspecialchars($i['name']); ?> (<?php echo $i['type']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="1" min="1">
            </div>
            <button type="submit" name="add_item" class="button">Add Item</button>
        </form>
    </div>
    
    <div class="card">
        <h3>Character Inventories</h3>
        <table>
            <thead>
                <tr>
                    <th>Character</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($inventory as $inv): ?>
                <tr>
                    <td><?php echo htmlspecialchars($inv['char_name']); ?></td>
                    <td><?php echo htmlspecialchars($inv['item_name']); ?></td>
                    <td><?php echo $inv['type']; ?></td>
                    <td><?php echo $inv['quantity']; ?></td>
                    <td><?php echo $inv['value']; ?> gp</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo get_footer(); ?>
