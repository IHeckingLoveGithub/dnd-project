<?php
session_start();
require 'db.php';
require 'functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Check if username/email exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username or Email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

echo get_header('Register');
?>

<h2>Register</h2>

<?php if($error): ?>
    <div class="alert"><?php echo $error; ?></div>
<?php endif; ?>

<?php if($success): ?>
    <div class="alert" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<form method="POST" action="register.php" style="max-width: 400px; margin: 0 auto;">
    <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
    </div>
    
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
    </div>
    
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    
    <button type="submit" class="button">Register</button>
</form>

<?php echo get_footer(); ?>
