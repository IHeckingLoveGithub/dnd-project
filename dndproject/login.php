<?php
session_start();
require 'db.php';
require 'functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT player_id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['player_id'] = $user['player_id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

echo get_header('Login');
?>

<h2>Login</h2>

<?php if($error): ?>
    <div class="alert"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" action="login.php" style="max-width: 400px; margin: 0 auto;">
    <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
    </div>
    
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    
    <button type="submit" class="button">Login</button>
</form>

<?php echo get_footer(); ?>
