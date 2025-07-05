<?php
include 'config.php';

// VULNERABLE: Directory traversal
$dir = $_GET['dir'] ?? '.';
$files = scandir($dir);

// VULNERABLE: File inclusion
if ($_GET['view']) {
    $file = $_GET['view'];
    // VULNERABLE: Sin validación de path
    include $file;
}

// VULNERABLE: File deletion
if ($_GET['delete']) {
    $file = $_GET['delete'];
    // VULNERABLE: Sin validación
    unlink($file);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Manager - Vulnerable App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .file-list { margin-top: 20px; }
        .file-item { margin: 5px 0; padding: 5px; border: 1px solid #ddd; }
        .actions { margin-left: 10px; }
        a { margin-right: 10px; }
    </style>
</head>
<body>
    <h1>File Manager</h1>
    <p>Current directory: <?php echo htmlspecialchars($dir); ?></p>
    
    <div class="file-list">
        <?php foreach ($files as $file): ?>
            <?php if ($file !== '.' && $file !== '..'): ?>
                <div class="file-item">
                    <strong><?php echo $file; ?></strong>
                    <div class="actions">
                        <a href="?view=<?php echo urlencode($dir . '/' . $file); ?>">View</a>
                        <a href="?delete=<?php echo urlencode($dir . '/' . $file); ?>" onclick="return confirm('Delete file?')">Delete</a>
                        <?php if (is_dir($dir . '/' . $file)): ?>
                            <a href="?dir=<?php echo urlencode($dir . '/' . $file); ?>">Enter</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</body>
</html>
