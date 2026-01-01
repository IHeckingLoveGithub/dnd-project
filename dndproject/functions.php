<?php

function is_logged_in() {
    return isset($_SESSION['player_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function get_header($title = 'D&D Manager') {
    $nav = '';
    if (is_logged_in()) {
        $nav = '
        <nav>
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="characters.php" class="nav-link">Characters</a>
            <a href="quests.php" class="nav-link">Quests</a>
            <a href="monsters.php" class="nav-link">Monsters</a>
            <a href="inventory.php" class="nav-link">Inventory</a>
            <a href="reports.php" class="nav-link">Reports</a>
            <a href="logout.php" class="nav-link" style="float:right">Logout (' . htmlspecialchars($_SESSION['username']) . ')</a>
        </nav>';
    } else {
        $nav = '
        <nav>
            <a href="index.php" class="nav-link">Home</a>
            <a href="login.php" class="nav-link">Login</a>
            <a href="register.php" class="nav-link">Register</a>
        </nav>';
    }

    return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>$title</title>
    <link rel='stylesheet' href='css/style.css'>
</head>
<body>
    <header>
        <h1>D&D Campaign Manager</h1>
        $nav
    </header>
    <main class='container'>
    ";
}

function get_footer() {
    return "
    </main>
    <footer>
        <p>&copy; " . date('Y') . " D&D Campaign Manager</p>
    </footer>
</body>
</html>
    ";
}
?>
