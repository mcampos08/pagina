<?php
include 'config.php';

// VULNERABLE: Sin verificación de roles
// Cualquier usuario puede acceder

// VULNERABLE: Command Injection
if ($_POST['command']) {
    $command = $_POST['command'];
    // VULNERABLE: Ejecución directa de comandos
    $output = shell_exec($command);
}

// VULNERABLE: File Upload sin validación
if ($_FILES['file']) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($_FILES["file"]["name"]);
    // VULNERABLE: Sin validación de tipo de archivo
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    $upload_message = "File uploaded: " . $target_file;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Vulnerable App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; }
        textarea { width: 100%; height: 200px; }
        input[type="text"], input[type="file"] { width: 100%; padding: 5px; }
        button { background: #dc3545; color: white; padding: 8px 15px; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Admin Panel</h1>
    
    <div class="section">
        <h3>System Command Execution</h3>
        <form method="POST">
            <input type="text" name="command" placeholder="Enter system command..." value="<?php echo htmlspecialchars($_POST['command'] ?? ''); ?>">
            <button type="submit">Execute</button>
        </form>
        <?php if (isset($output)): ?>
            <h4>Output:</h4>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h3>File Upload</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file">
            <button type="submit">Upload</button>
        </form>
        <?php if (isset($upload_message)): ?>
            <p><?php echo $upload_message; ?></p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h3>Database Direct Access</h3>
        <!-- VULNERABLE: Credenciales expuestas -->
        <p>Database: vulnerable_app</p>
        <p>User: admin</p>
        <p>Password: admin123</p>
    </div>
</body>
</html>
