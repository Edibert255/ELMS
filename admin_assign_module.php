<?php
include 'config.php';
session_start();

// CHECK IF ADMIN IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$success = "";
$error = "";

// GET SELECTED MODULE FROM URL
$selected_module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
$selected_module = null;

if ($selected_module_id > 0) {
    $sql_mod = "SELECT * FROM registered_modules WHERE id = '$selected_module_id'";
    $result_mod = mysqli_query($conn, $sql_mod);
    if ($result_mod && mysqli_num_rows($result_mod) > 0) {
        $selected_module = mysqli_fetch_assoc($result_mod);
    }
}

// ASSIGN MODULE TO LECTURER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_module'])) {
    $module_id = (int)$_POST['module_id'];
    $lecturer_id = (int)$_POST['lecturer_id'];
    
    // Get module details
    $sql_mod = "SELECT * FROM registered_modules WHERE id = '$module_id'";
    $result_mod = mysqli_query($conn, $sql_mod);
    $module = mysqli_fetch_assoc($result_mod);
    
    // Check if already assigned
    $check = mysqli_query($conn, "SELECT id FROM lecturer_modules WHERE lecturer_id = '$lecturer_id' AND module_code = '{$module['module_code']}'");
    if (mysqli_num_rows($check) > 0) {
        $error = "This module is already assigned to this lecturer!";
    } else {
        $sql = "INSERT INTO lecturer_modules (lecturer_id, module_code, module_name, semester, academic_year, credits) 
                VALUES ('$lecturer_id', '{$module['module_code']}', '{$module['module_name']}', '{$module['semester']}', '{$module['academic_year']}', '{$module['credits']}')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Module assigned to lecturer successfully!";
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// REMOVE ASSIGNMENT
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $sql = "DELETE FROM lecturer_modules WHERE id = '$id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Assignment removed successfully!";
    } else {
        $error = "Remove failed!";
    }
}

// FETCH ALL LECTURERS
$lecturers = [];
$sql_lec = "SELECT * FROM users WHERE role = 'lecturer' AND status = 'active' ORDER BY full_name ASC";
$result_lec = mysqli_query($conn, $sql_lec);
if ($result_lec && mysqli_num_rows($result_lec) > 0) {
    while ($row = mysqli_fetch_assoc($result_lec)) {
        $lecturers[] = $row;
    }
}

// FETCH ALL MODULES (for dropdown)
$modules = [];
$sql_mods = "SELECT * FROM registered_modules ORDER BY semester ASC, module_code ASC";
$result_mods = mysqli_query($conn, $sql_mods);
if ($result_mods && mysqli_num_rows($result_mods) > 0) {
    while ($row = mysqli_fetch_assoc($result_mods)) {
        $modules[] = $row;
    }
}

// FETCH CURRENT ASSIGNMENTS
$assignments = [];
$sql_assign = "SELECT lm.*, u.full_name as lecturer_name 
               FROM lecturer_modules lm 
               JOIN users u ON lm.lecturer_id = u.id 
               ORDER BY u.full_name ASC, lm.semester ASC";
$result_assign = mysqli_query($conn, $sql_assign);
if ($result_assign && mysqli_num_rows($result_assign) > 0) {
    while ($row = mysqli_fetch_assoc($result_assign)) {
        $assignments[] = $row;
    }
}

// Default values
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';
$photo = 'default.png';
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Module to Lecturer - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .assign-container { background: white; border-radius: 12px; padding: 25px; margin-top: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-card { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: flex-end; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: white; }
        .btn-assign { background: #28a745; color: white; padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-assign:hover { background: #218838; }
        .assignments-table { width: 100%; border-collapse: collapse; overflow-x: auto; display: block; }
        .assignments-table th, .assignments-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .assignments-table th { background: #0056b3; color: white; }
        .assignments-table tr:hover { background: #f8f9fa; }
        .section-title { font-size: 18px; font-weight: bold; margin: 20px 0 15px 0; color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .btn-remove { background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 15px; font-size: 11px; }
        .badge-sem1 { background: #0056b3; color: white; }
        .badge-sem2 { background: #28a745; color: white; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .success-msg, .error-msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .success-msg { background: #d4edda; color: #155724; }
        .error-msg { background: #f8d7da; color: #721c24; }
        .info-box { background: #e8f4fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0056b3; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

   <?php include 'sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <header class="top-header"><div class="welcome-top"><p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p><i class="fas fa-bell"></i></div></header>
        <main>
            <div class="panel-title">Assign Module to Lecturer</div>
            <div class="info-banner"><span><strong>Admin:</strong> <?php echo $admin_name; ?></span><span><strong>Total Assignments:</strong> <?php echo count($assignments); ?></span></div>

            <div class="assign-container">
                <?php if($success): ?><div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

                <?php if($selected_module): ?>
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> Assigning Module:</strong> <?php echo $selected_module['module_code']; ?> - <?php echo $selected_module['module_name']; ?> (<?php echo $selected_module['semester']; ?>)
                </div>
                <?php endif; ?>

                <!-- Assign Form -->
                <div class="form-card">
                    <h3><i class="fas fa-user-tie"></i> Assign Module to Lecturer</h3>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Module</label>
                                <select name="module_id" required>
                                    <option value="">-- Select Module --</option>
                                    <?php foreach($modules as $mod): ?>
                                    <option value="<?php echo $mod['id']; ?>" <?php echo ($selected_module_id == $mod['id']) ? 'selected' : ''; ?>>
                                        <?php echo $mod['module_code']; ?> - <?php echo $mod['module_name']; ?> (<?php echo $mod['semester']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select Lecturer</label>
                                <select name="lecturer_id" required>
                                    <option value="">-- Select Lecturer --</option>
                                    <?php foreach($lecturers as $lec): ?>
                                    <option value="<?php echo $lec['id']; ?>"><?php echo $lec['full_name']; ?> (<?php echo $lec['username']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="assign_module" class="btn-assign"><i class="fas fa-check-circle"></i> Assign Module</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Current Assignments -->
                <div class="section-title"><i class="fas fa-list"></i> Current Module Assignments</div>
                <?php if(empty($assignments)): ?>
                    <div class="empty-state"><i class="fas fa-user-tie"></i><p>No modules assigned to lecturers yet.</p></div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="assignments-table">
                            <thead><tr><th>Lecturer</th><th>Module Code</th><th>Module Name</th><th>Semester</th><th>Academic Year</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach($assignments as $assign): ?>
                                <tr>
                                    <td><strong><?php echo $assign['lecturer_name']; ?></strong></td>
                                    <td><?php echo $assign['module_code']; ?></td>
                                    <td><?php echo $assign['module_name']; ?></td>
                                    <td><span class="badge <?php echo $assign['semester'] == 'Semester 1' ? 'badge-sem1' : 'badge-sem2'; ?>"><?php echo $assign['semester']; ?></span></td>
                                    <td><?php echo $assign['academic_year']; ?></td>
                                    <td><a href="?remove=<?php echo $assign['id']; ?>" class="btn-remove" onclick="return confirm('Remove this assignment?')"><i class="fas fa-trash"></i> Remove</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>function toggleSub(id){const menu=document.getElementById(id);if(menu){menu.classList.toggle('active');}}</script>
</body>
</html>