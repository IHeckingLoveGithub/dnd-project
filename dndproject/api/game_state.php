<?php
require '../db.php';

header('Content-Type: application/json');

if (!isset($_GET['campaign_id'])) {
    echo json_encode([]);
    exit;
}

$id = $_GET['campaign_id'];

$stmt = $pdo->prepare("SELECT char_id, pos_x, pos_y FROM characters WHERE campaign_id = ?");
$stmt->execute([$id]);
$chars = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($chars);
?>
