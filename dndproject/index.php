<?php
session_start();
require 'functions.php';

echo get_header('Welcome to D&D Manager');
?>

<div class="hero">
    <h2>Welcome, Adventurer!</h2>
    <p>Manage your Dungeons & Dragons campaigns with ease.</p>
    
    <?php if(!is_logged_in()): ?>
        <div class="actions">
            <a href="login.php" class="button">Login</a>
            <a href="register.php" class="button secondary">Register</a>
        </div>
    <?php else: ?>
        <div class="actions">
            <a href="dashboard.php" class="button">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<div class="features">
    <div class="feature-card">
        <h3>Character Tracking</h3>
        <p>Keep track of stats, XP, and HP.</p>
    </div>
    <div class="feature-card">
        <h3>Quest Management</h3>
        <p>Log active and completed quests.</p>
    </div>
    <div class="feature-card">
        <h3>Inventory System</h3>
        <p>Manage loot and items efficiently.</p>
    </div>
</div>

<?php echo get_footer(); ?>
