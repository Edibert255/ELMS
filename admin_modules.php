<?php
include 'config.php';
session_start();

// CHECK IF ADMIN IS LOGGED IN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];
$admin_username = $_SESSION['username'];
$success = "";
$error = "";

// FETCH ADMIN DATA FOR SIDEBAR
$sql_admin = "SELECT * FROM users WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$photo = isset($admin_data['photo']) ? $admin_data['photo'] : 'default.png';
$first_name = explode(' ', $admin_name)[0];
$last_name = isset(explode(' ', $admin_name)[1]) ? explode(' ', $admin_name)[1] : '';

// PAGINATION AND SEARCH
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$semester_filter = isset($_GET['semester']) ? mysqli_real_escape_string($conn, $_GET['semester']) : '';

// ADD NEW MODULE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_module'])) {
    $module_code = mysqli_real_escape_string($conn, $_POST['module_code']);
    $module_name = mysqli_real_escape_string($conn, $_POST['module_name']);
    $credits = (int)$_POST['credits'];
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $intake = mysqli_real_escape_string($conn, $_POST['intake']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $check = mysqli_query($conn, "SELECT id FROM registered_modules WHERE module_code = '$module_code'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Module code already exists!";
    } else {
        $sql = "INSERT INTO registered_modules (module_code, module_name, credits, program, department, semester, intake, academic_year, description) 
                VALUES ('$module_code', '$module_name', '$credits', '$program', '$department', '$semester', '$intake', '$academic_year', '$description')";
        
        if (mysqli_query($conn, $sql)) {
            $success = "Module added successfully!";
            $_POST = array();
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// EDIT MODULE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_module'])) {
    $module_id = (int)$_POST['module_id'];
    $module_code = mysqli_real_escape_string($conn, $_POST['module_code']);
    $module_name = mysqli_real_escape_string($conn, $_POST['module_name']);
    $credits = (int)$_POST['credits'];
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $intake = mysqli_real_escape_string($conn, $_POST['intake']);
    $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $sql = "UPDATE registered_modules SET 
            module_code = '$module_code',
            module_name = '$module_name',
            credits = '$credits',
            program = '$program',
            department = '$department',
            semester = '$semester',
            intake = '$intake',
            academic_year = '$academic_year',
            description = '$description'
            WHERE id = '$module_id'";
    
    if (mysqli_query($conn, $sql)) {
        $success = "Module updated successfully!";
    } else {
        $error = "Update failed: " . mysqli_error($conn);
    }
}

// DELETE MODULE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM registered_modules WHERE id = '$id'";
    if (mysqli_query($conn, $sql)) {
        $success = "Module deleted successfully!";
    } else {
        $error = "Delete failed!";
    }
}

// Build WHERE clause
$where = "WHERE 1=1";
if ($search != '') {
    $where .= " AND (module_name LIKE '%$search%' OR module_code LIKE '%$search%')";
}
if ($semester_filter != '') {
    $where .= " AND semester = '$semester_filter'";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM registered_modules $where";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// FETCH MODULES
$modules = [];
$sql = "SELECT * FROM registered_modules $where ORDER BY semester ASC, module_code ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $modules[] = $row;
    }
}

// GET UNIQUE PROGRAMS FOR FILTER
$programs = [];
$sql_prog = "SELECT DISTINCT program FROM registered_modules WHERE program IS NOT NULL AND program != ''";
$result_prog = mysqli_query($conn, $sql_prog);
if ($result_prog && mysqli_num_rows($result_prog) > 0) {
    while ($row = mysqli_fetch_assoc($result_prog)) {
        $programs[] = $row['program'];
    }
}

// GET MODULE FOR EDITING
$edit_module = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $result_edit = mysqli_query($conn, "SELECT * FROM registered_modules WHERE id = '$edit_id'");
    if ($result_edit && mysqli_num_rows($result_edit) > 0) {
        $edit_module = mysqli_fetch_assoc($result_edit);
    }
}
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules - Admin | CBE ELMS</title>
    <link rel="stylesheet" href="students_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modules-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        /* Stats Header */
        .stats-header {
            background: linear-gradient(135deg, #0056b3, #003d82);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .stats-header h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .stats-header p {
            font-size: 13px;
            opacity: 0.9;
        }
        
        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .entries-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .entries-selector select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 250px;
        }
        
        .search-box button {
            background: #0056b3;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        /* Form Card */
        .form-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 13px;
            color: #333;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .full-width {
            grid-column: span 3;
        }
        
        /* Modules Table */
        .modules-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .modules-table th {
            background: #0056b3;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        
        .modules-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .modules-table tr:hover {
            background: #f8f9fa;
        }
        
        .module-code {
            font-weight: bold;
            color: #0056b3;
        }
        
        .semester-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .semester-1 {
            background: #28a745;
            color: white;
        }
        
        .semester-2 {
            background: #fd7e14;
            color: white;
        }
        
        /* Action Buttons */
        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 13px;
        }
        
        .pagination a.active {
            background: #0056b3;
            color: white;
            border-color: #0056b3;
        }
        
        .pagination a:hover:not(.active) {
            background: #e9ecef;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .showing-info {
            font-size: 13px;
            color: #666;
        }
        
        /* Filter row */
        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-row select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        
        .clear-btn {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        .success-msg, .error-msg {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .modules-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
            }
            .toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

     <?php include 'sidebar_admin.php'; ?>

    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Manage Modules</div>
            
            <div class="info-banner">
                <span><strong>Admin:</strong> <?php echo $admin_name; ?></span>
                <span><strong>Total Modules:</strong> <?php echo $total_records; ?></span>
                <span><strong>System Status:</strong> Online</span>
            </div>

            <div class="modules-container">
                <?php if($success): ?>
                    <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if($error): ?>
                    <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Stats Header -->
                <div class="stats-header">
                    <h3><i class="fas fa-book-open"></i> Module Registration Summary</h3>
                    <p>Total Modules: <?php echo $total_records; ?> | Showing page <?php echo $page; ?> of <?php echo $total_pages; ?></p>
                </div>

                <!-- Add/Edit Module Form -->
                <div class="form-card">
                    <h3><i class="fas fa-<?php echo $edit_module ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $edit_module ? 'Edit Module' : 'Add New Module'; ?></h3>
                    <form method="POST">
                        <?php if($edit_module): ?>
                            <input type="hidden" name="module_id" value="<?php echo $edit_module['id']; ?>">
                        <?php endif; ?>
                        <div class="form-grid">
                            <div class="form-group"><label>Module Code *</label><input type="text" name="module_code" value="<?php echo $edit_module['module_code'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Module Name *</label><input type="text" name="module_name" value="<?php echo $edit_module['module_name'] ?? ''; ?>" required></div>
                            <div class="form-group"><label>Credits</label><input type="number" name="credits" value="<?php echo $edit_module['credits'] ?? 12; ?>"></div>
                            <div class="form-group"><label>Program</label><input type="text" name="program" value="<?php echo $edit_module['program'] ?? 'DIT'; ?>"></div>
                            <div class="form-group"><label>Department</label><input type="text" name="department" value="<?php echo $edit_module['department'] ?? 'ICT'; ?>"></div>
                            <div class="form-group"><label>Semester *</label>
                                <select name="semester" required>
                                    <option value="Semester 1" <?php echo ($edit_module['semester'] ?? '') == 'Semester 1' ? 'selected' : ''; ?>>Semester 1</option>
                                    <option value="Semester 2" <?php echo ($edit_module['semester'] ?? '') == 'Semester 2' ? 'selected' : ''; ?>>Semester 2</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Intake</label><input type="text" name="intake" value="<?php echo $edit_module['intake'] ?? 'September'; ?>"></div>
                            <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" value="<?php echo $edit_module['academic_year'] ?? '2025/2026'; ?>" required></div>
                            <div class="form-group full-width"><label>Description</label><textarea name="description" rows="2"><?php echo $edit_module['description'] ?? ''; ?></textarea></div>
                        </div>
                        <button type="submit" name="<?php echo $edit_module ? 'edit_module' : 'add_module'; ?>" class="btn-save">
                            <i class="fas fa-save"></i> <?php echo $edit_module ? 'Update Module' : 'Add Module'; ?>
                        </button>
                        <?php if($edit_module): ?>
                            <a href="admin_modules.php" style="margin-left:10px; background:#6c757d; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="entries-selector">
                        <span>Show</span>
                        <select id="limitSelect" onchange="changeLimit()">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                        <span>entries</span>
                    </div>
                    <div class="search-box">
                        <form method="GET" id="searchForm">
                            <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                            <input type="hidden" name="page" value="1">
                            <input type="text" name="search" placeholder="Search modules..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><i class="fas fa-search"></i> Search</button>
                        </form>
                    </div>
                </div>

                <!-- Filter Row -->
                <div class="filter-row">
                    <select name="semester" id="semesterFilter" onchange="filterBySemester()">
                        <option value="">All Semesters</option>
                        <option value="Semester 1" <?php echo $semester_filter == 'Semester 1' ? 'selected' : ''; ?>>Semester 1</option>
                        <option value="Semester 2" <?php echo $semester_filter == 'Semester 2' ? 'selected' : ''; ?>>Semester 2</option>
                    </select>
                    <?php if($search != '' || $semester_filter != ''): ?>
                    <a href="admin_modules.php?limit=<?php echo $limit; ?>" class="clear-btn"><i class="fas fa-times"></i> Clear Filters</a>
                    <?php endif; ?>
                </div>

                <!-- Modules Table -->
                <?php if(empty($modules)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open" style="font-size: 48px;"></i>
                        <p>No modules found.</p>
                        <p>Use the form above to add modules.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="modules-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Module Name</th>
                                    <th>Module Code</th>
                                    <th>Credit</th>
                                    <th>Program</th>
                                    <th>Department</th>
                                    <th>Semester</th>
                                    <th>Intake</th>
                                    <th>Academic Year</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sn = $offset + 1;
                                foreach($modules as $module): 
                                ?>
                                <tr>
                                    <td><?php echo $sn++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($module['module_name']); ?></strong></td>
                                    <td class="module-code"><?php echo htmlspecialchars($module['module_code']); ?>\(
                                    <td><?php echo $module['credits']; ?>\(
                                    <td><?php echo htmlspecialchars($module['program'] ?? 'DIT'); ?>\(
                                    <td><?php echo htmlspecialchars($module['department'] ?? 'ICT'); ?>\(
                                    <td><span class="semester-badge semester-<?php echo $module['semester'] == 'Semester 1' ? '1' : '2'; ?>"><?php echo $module['semester']; ?></span>\(
                                    <td><?php echo htmlspecialchars($module['intake'] ?? 'September'); ?>\(
                                    <td><?php echo htmlspecialchars($module['academic_year']); ?>\(
                                    <td class="btn-group">
                                        <a href="?edit=<?php echo $module['id']; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?delete=<?php echo $module['id']; ?>&limit=<?php echo $limit; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>" class="btn-delete" onclick="return confirm('Delete this module?')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="showing-info">
                            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> entries
                        </div>
                        <div>
                            <a href="?limit=<?php echo $limit; ?>&page=1&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>" class="<?php echo $page == 1 ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i> Previous</a>
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if($i == $page): ?>
                                    <a href="#" class="active"><?php echo $i; ?></a>
                                <?php elseif($i <= 5 || $i > $total_pages - 2): ?>
                                    <a href="?limit=<?php echo $limit; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>"><?php echo $i; ?></a>
                                <?php elseif($i == 6): ?>
                                    <span>...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <a href="?limit=<?php echo $limit; ?>&page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>" class="<?php echo $page == $total_pages ? 'disabled' : ''; ?>">Next <i class="fas fa-chevron-right"></i></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleSub(id) {
            const menu = document.getElementById(id);
            if (menu) {
                const isActive = menu.classList.contains('active');
                document.querySelectorAll('.submenu').forEach(s => s.classList.remove('active'));
                if (!isActive) menu.classList.add('active');
            }
        }
        
        function changeLimit() {
            const limit = document.getElementById('limitSelect').value;
            window.location.href = 'admin_modules.php?limit=' + limit + '&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>';
        }
        
        function filterBySemester() {
            const semester = document.getElementById('semesterFilter').value;
            window.location.href = 'admin_modules.php?limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&semester=' + semester;
        }
    </script>

</body>
</html>