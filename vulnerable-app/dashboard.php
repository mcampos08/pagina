<?php
include 'config.php';

// VULNERABLE: Sin validación de autenticación adecuada
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// VULNERABLE: XSS en parámetros GET
$message = isset($_GET['message']) ? $_GET['message'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// VULNERABLE: Información sensible expuesta
$users_result = null;
if ($role === 'admin' && $search) {
    // VULNERABLE: SQL Injection en búsqueda
    $query = "SELECT * FROM users WHERE username LIKE '%" . $search . "%'";
    
    try {
        $users_result = $connection->query($query);
    } catch (mysqli_sql_exception $e) {
        $search_error = "Search error: " . $e->getMessage();
    }
}

// VULNERABLE: Obtener estadísticas sin validación
$stats_query = "SELECT COUNT(*) as total_users FROM users";
$stats_result = $connection->query($stats_query);
$total_users = $stats_result->fetch_assoc()['total_users'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Vulnerable App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f5f5; }
        .header { background: #007cba; color: white; padding: 15px 20px; }
        .header h1 { margin: 0; }
        .header .user-info { margin-top: 5px; opacity: 0.9; }
        .container { padding: 20px; }
        .admin-panel { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .message { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .search-form { margin-bottom: 20px; }
        .search-form input[type="text"] { padding: 8px; width: 200px; }
        .search-form button { padding: 8px 15px; background: #007cba; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; }
        .stats { background: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .quick-links { background: white; padding: 15px; border-radius: 5px; }
        .quick-links a { display: inline-block; margin-right: 15px; padding: 8px 12px; background: #007cba; color: white; text-decoration: none; border-radius: 3px; }
        .quick-links a:hover { background: #005a8b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <div class="user-info">
            Role: <?php echo htmlspecialchars($role); ?> | 
            User ID: <?php echo htmlspecialchars($user_id); ?> | 
            <a href="logout.php" style="color: white;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <!-- VULNERABLE: XSS sin sanitización -->
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="stats">
            <h3>System Statistics</h3>
            <p>Total Users: <?php echo $total_users; ?></p>
            <p>Your Session ID: <?php echo session_id(); ?></p>
        </div>
        
        <?php if ($role === 'admin'): ?>
            <div class="admin-panel">
                <h3>🔐 Admin Panel</h3>
                <p>Welcome Administrator! You have full access to the system.</p>
                
                <div class="search-form">
                    <form method="GET">
                        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                        <input type="hidden" name="message" value="<?php echo htmlspecialchars($message); ?>">
                    </form>
                </div>
                
                <?php if (isset($search_error)): ?>
                    <div class="error"><?php echo htmlspecialchars($search_error); ?></div>
                <?php endif; ?>
                
                <?php if ($users_result && $users_result->num_rows > 0): ?>
                    <h4>Search Results for "<?php echo htmlspecialchars($search); ?>":</h4>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <!-- VULNERABLE: Contraseñas en texto plano -->
                                <td><?php echo htmlspecialchars($user['password']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php elseif ($search): ?>
                    <p>No users found for "<?php echo htmlspecialchars($search); ?>"</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="quick-links">
            <h3>Quick Actions</h3>
            <!-- VULNERABLE: Acceso directo a archivos sensibles -->
            <a href="admin_panel.php">Admin Panel</a>
            <a href="file_manager.php">File Manager</a>
            <a href="?message=<script>alert('XSS Test')</script>">XSS Test</a>
            <a href="config.php">View Config</a>
        </div>
    </div>
</body>
</html>
