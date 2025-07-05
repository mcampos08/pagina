<?php
include 'config.php';

$message = '';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    
    // VULNERABLE: Sin validación de entrada
    // VULNERABLE: SQL Injection
    $query = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";
    
    if (mysqli_query($connection, $query)) {
        $message = "User registered successfully!";
    } else {
        // VULNERABLE: Exposición de información de error
        $message = "Error: " . mysqli_error($connection);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Vulnerable App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 400px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .message { color: green; margin-bottom: 10px; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if ($message): ?>
            <div class="<?php echo (strpos($message, 'Error') !== false) ? 'error' : 'message'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Register</button>
        </form>
        
        <p><a href="login.php">Already have an account?</a></p>
    </div>
</body>
</html>
