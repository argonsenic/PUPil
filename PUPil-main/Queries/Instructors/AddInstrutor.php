<?php
/**
 * Add Instructor Handler (Admin only)
 * Allows admin to add new instructors to the system
 */

require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../Authentication/Login.php");
    exit();
}

$error = '';
$success = '';
$form_data = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $form_data['username'] = trim($_POST['username'] ?? '');
    $form_data['password'] = $_POST['password'] ?? '';
    $form_data['first_name'] = trim($_POST['first_name'] ?? '');
    $form_data['middle_name'] = trim($_POST['middle_name'] ?? '');
    $form_data['last_name'] = trim($_POST['last_name'] ?? '');
    $form_data['suffix_name'] = trim($_POST['suffix_name'] ?? '');
    $form_data['phone_number'] = trim($_POST['phone_number'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($form_data['username'])) {
        $errors[] = "Username is required";
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($form_data['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($form_data['password']) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if (empty($form_data['first_name'])) $errors[] = "First name is required";
    if (empty($form_data['last_name'])) $errors[] = "Last name is required";
    
    // Check if username exists
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE user_name = ?";
        $check_stmt = sqlsrv_query($conn, $check_sql, array($form_data['username']));
        if ($check_stmt && sqlsrv_has_rows($check_stmt)) {
            $errors[] = "Username already taken";
        }
        sqlsrv_free_stmt($check_stmt);
    }
    
    if (empty($errors)) {
        
        sqlsrv_begin_transaction($conn);
        
        try {
            // Get instructor role ID
            $role_sql = "SELECT id FROM roles WHERE role = 'instructor'";
            $role_stmt = sqlsrv_query($conn, $role_sql);
            $role_row = sqlsrv_fetch_array($role_stmt, SQLSRV_FETCH_ASSOC);
            $role_id = $role_row['id'];
            sqlsrv_free_stmt($role_stmt);
            
            // Generate account code
            $account_code = 'INS_' . date('Ymd') . '_' . rand(1000, 9999);
            
            // Hash password
            $password_hash = password_hash($form_data['password'], PASSWORD_DEFAULT);
            
            // Insert into users
            $user_sql = "INSERT INTO users (user_name, password_hash, role_id, account_code) VALUES (?, ?, ?, ?)";
            $user_stmt = sqlsrv_query($conn, $user_sql, array($form_data['username'], $password_hash, $role_id, $account_code));
            
            if (!$user_stmt) {
                throw new Exception("Failed to create user account");
            }
            
            // Get user ID
            $user_id_sql = "SELECT @@IDENTITY AS id";
            $user_id_stmt = sqlsrv_query($conn, $user_id_sql);
            $user_id_row = sqlsrv_fetch_array($user_id_stmt, SQLSRV_FETCH_ASSOC);
            $user_id = $user_id_row['id'];
            sqlsrv_free_stmt($user_id_stmt);
            
            // Insert into instructor_profiles
            $profile_sql = "INSERT INTO instructor_profiles 
                            (first_name, middle_name, last_name, suffix_name, phone_number, account_id) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            
            $profile_params = array(
                $form_data['first_name'], $form_data['middle_name'], $form_data['last_name'],
                $form_data['suffix_name'], $form_data['phone_number'], $user_id
            );
            
            $profile_stmt = sqlsrv_query($conn, $profile_sql, $profile_params);
            
            if (!$profile_stmt) {
                throw new Exception("Failed to create instructor profile");
            }
            
            sqlsrv_commit($conn);
            
            $success = "Instructor added successfully! Username: " . htmlspecialchars($form_data['username']);
            $form_data = [];
            
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            $error = "Failed to add instructor: " . $e->getMessage();
        }
        
        if (isset($user_stmt)) sqlsrv_free_stmt($user_stmt);
        if (isset($profile_stmt)) sqlsrv_free_stmt($profile_stmt);
        
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get existing instructors
$instructors_sql = "SELECT ip.id, ip.first_name, ip.last_name, ip.phone_number,
                           u.user_name, u.account_code
                    FROM instructor_profiles ip
                    INNER JOIN users u ON ip.account_id = u.id
                    ORDER BY ip.last_name";
$instructors_stmt = sqlsrv_query($conn, $instructors_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Instructor - Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .card-header h2 { font-size: 20px; }
        .card-body { padding: 20px; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        label { display: block; margin-bottom: 5px; color: #333; font-weight: 500; font-size: 14px; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus { outline: none; border-color: #667eea; }
        button { background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #218838; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #dc3545; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .actions a { margin-right: 10px; text-decoration: none; padding: 5px 10px; border-radius: 3px; font-size: 12px; color: white; }
        .view-btn { background: #17a2b8; }
        .edit-btn { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Add New Instructor</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($form_data['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Suffix</label>
                            <input type="text" name="suffix_name" value="<?php echo htmlspecialchars($form_data['suffix_name'] ?? ''); ?>" placeholder="Jr., Sr., III">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <button type="submit">Add Instructor</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Instructor List</h2>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Instructor ID</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Username</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($instructor = sqlsrv_fetch_array($instructors_stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($instructor['account_code']); ?></td>
                                <td><?php echo $instructor['last_name'] . ', ' . $instructor['first_name']; ?></td>
                                <td><?php echo htmlspecialchars($instructor['phone_number'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($instructor['user_name']); ?></td>
                                <td class="actions">
                                    <a href="GetInstructor.php?id=<?php echo $instructor['id']; ?>" class="view-btn">View</a>
                                    <a href="UpdateInstructor.php?id=<?php echo $instructor['id']; ?>" class="edit-btn">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php
if (isset($instructors_stmt)) sqlsrv_free_stmt($instructors_stmt);
?>