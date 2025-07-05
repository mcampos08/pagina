<?php
include 'config.php';

$error = '';
$debug_info = '';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // VULNERABLE: SQL Injection - pero con sintaxis correcta
    $query = "SELECT * FROM users WHERE username = '" . $username . "' AND password = '" . $password . "'";
    
    // VULNERABLE: Mostrar query en modo debug
    if (isset($_GET['debug'])) {
        $debug_info = "Debug Query: " . $query;
    }
    
    try {
        $result = $connection->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // VULNERABLE: Log con información sensible
            log_activity("Login successful for user: " . $user['username']);
            
            // VULNERABLE: Redirect sin validación
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';
            header("Location: " . $redirect);
            exit();
        } else {
            $error = "Invalid credentials";
            // VULNERABLE: Log con información sensible
            log_activity("Failed login attempt for: " . $username);
        }
        
    } catch (mysqli_sql_exception $e) {
        // VULNERABLE: Mostrar errores SQL detallados
        $error = "Database error: " . $e->getMessage();
        $debug_info = "Failed Query: " . $query;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vulnerable App - Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 12px 20px; border: none; border-radius: 3px; cursor: pointer; width: 100%; }
        button:hover { background: #005a8b; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background: #ffe6e6; border-radius: 3px; }
        .debug { color: blue; margin-bottom: 15px; padding: 10px; background: #e6f3ff; border-radius: 3px; font-size: 12px; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #007cba; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Vulnerable App - Login</h2>
        
        <?php if ($debug_info): ?>
            <div class="debug"><?php echo htmlspecialchars($debug_info); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        
        <div class="links">
            <p><a href="register.php">Create Account</a></p>
            <p><a href="?debug=1">Debug Mode</a></p>
        </div>
        
        <div style="margin-top: 20px; padding: 10px; background: #f9f9f9; border-radius: 3px; font-size: 12px;">
            <strong>Test Credentials:</strong><br>
            Admin: admin / admin123<br>
            User: user / user123
        </div>
        
        <!-- VULNERABLE: Información sensible en comentarios -->
        <!-- 
        SQL Injection Test:
        Username: admin' OR '1'='1' --
        Password: anything
        -->
    </div>
</body>
</html>
