<?php
session_start();
require_once __DIR__ . '/../database/database.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["admin"] = $username;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Glassmorphism</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Placeholder styling for missing icons if you don't use a library */
        .icon-box, .icon-small { 
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>

<div class="app-container">
    
    <div class="glass-card header-card" style="margin-top: 5vh;">
        <div class="header-content">
            <div class="icon-box">
                <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            </div>
            <div>
                <h1>Admin Portal Login</h1>
                <p>Enter your credentials to access the dashboard.</p>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-header">
            <svg class="icon-small" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <h2>Secure Sign In</h2>
        </div>

        <?php if (!empty($error)): ?>
            <div class="glass-card" style="background: rgba(239, 68, 68, 0.3); margin-bottom: 1.5rem; padding: 1rem;">
                <p style="color: white; font-weight: bold; font-size: 0.9rem;"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <svg class="icon-small" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Log In
            </button>
        </form>
    </div>

</div>

</body>
</html>