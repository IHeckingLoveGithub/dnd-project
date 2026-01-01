<?php
session_start();
require 'db.php';

if (!isset($_SESSION['player_id'])) {
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $char_id = $_POST['char_id'];
    $x = $_POST['x'];
    $y = $_POST['y'];
    $player_id = $_SESSION['player_id'];

    // Security check: ensure character belongs to logged in user
    $stmt = $pdo->prepare("UPDATE characters SET pos_x = ?, pos_y = ? WHERE char_id = ? AND player_id = ?");
    $result = $stmt->execute([$x, $y, $char_id, $player_id]);

    if ($result && $stmt->rowCount() > 0) {
        echo "success";
    } else {
        echo "failed or no change";
    }
}
?>
