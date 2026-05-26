<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// GET STUDENT'S COURSE/PROGRAM
$student_program = '';
$student_year = '';
$student_name = '';

if ($role == 'student') {
    $sql_student = "SELECT s.*, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = '$user_id'";
    $result_student = mysqli_query($conn, $sql_student);
    if ($result_student && mysqli_num_rows($result_student) > 0) {
        $student_data = mysqli_fetch_assoc($result_student);
        $student_program = $student_data['program_of_study'] ?? '';
        $student_year = $student_data['year_of_study'] ?? '';
        $student_name = $student_data['full_name'] ?? '';
    }
}

// PAGINATION AND SEARCH
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$semester_filter = isset($_GET['semester']) ? mysqli_real_escape_string($conn, $_GET['semester']) : '';

// Build query
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

// Fetch modules
$modules = [];
$sql = "SELECT * FROM registered_modules $where ORDER BY semester ASC, module_code ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $modules[] = $row;
    }
}

// Get unique programs for filter
$programs = [];
$sql_prog = "SELECT DISTINCT program FROM registered_modules WHERE program IS NOT NULL AND program != ''";
$result_prog = mysqli_query($conn, $sql_prog);
if ($result_prog && mysqli_num_rows($result_prog) > 0) {
    while ($row = mysqli_fetch_assoc($result_prog)) {
        $programs[] = $row['program'];
    }
}

// Default values
$photo = 'default.png';
$first_name = explode(' ', $student_name)[0];
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Modules - CBE ELMS</title>
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
        
        /* Header Stats */
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
        
        @media (max-width: 768px) {
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

   <?php include 'sidebar_student.php'; ?>
    <div class="content-wrapper">
        <header class="top-header">
            <div class="welcome-top">
                <p>Mwanza Campus | <strong>Today:</strong> <?php echo date("d M, Y"); ?></p>
                <i class="fas fa-bell"></i>
            </div>
        </header>

        <main>
            <div class="panel-title">Registered Modules</div>
            
            <div class="info-banner">
                <span><strong>Program:</strong> <?php echo $student_program ?: 'All Programs'; ?></span>
                <span><strong>Academic Year:</strong> 2025/2026</span>
                <span><strong>Student:</strong> <?php echo $student_name ?: 'Student'; ?></span>
            </div>

            <div class="modules-container">
                <!-- Stats Header -->
                <div class="stats-header">
                    <h3><i class="fas fa-book-open"></i> Module Registration Summary</h3>
                    <p>Total Modules: <?php echo $total_records; ?> | Showing page <?php echo $page; ?> of <?php echo $total_pages; ?></p>
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
                    <a href="modules.php?limit=<?php echo $limit; ?>" class="clear-btn"><i class="fas fa-times"></i> Clear Filters</a>
                    <?php endif; ?>
                </div>

                <!-- Modules Table -->
                <?php if(empty($modules)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open" style="font-size: 48px;"></i>
                        <p>No modules found.</p>
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
                                    <td class="module-code"><?php echo htmlspecialchars($module['module_code']); ?></td>
                                    <td><?php echo $module['credits']; ?></td>
                                    <td><?php echo htmlspecialchars($module['program'] ?? 'DIT'); ?></td>
                                    <td><?php echo htmlspecialchars($module['department'] ?? 'ICT'); ?></td>
                                    <td><span class="semester-badge semester-<?php echo $module['semester'] == 'Semester 1' ? '1' : '2'; ?>"><?php echo $module['semester']; ?></span></td>
                                    <td><?php echo htmlspecialchars($module['intake'] ?? 'September'); ?></td>
                                    <td><?php echo htmlspecialchars($module['academic_year']); ?></td>
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
                menu.classList.toggle('active');
            }
        }
        
        function changeLimit() {
            const limit = document.getElementById('limitSelect').value;
            window.location.href = 'modules.php?limit=' + limit + '&search=<?php echo urlencode($search); ?>&semester=<?php echo urlencode($semester_filter); ?>';
        }
        
        function filterBySemester() {
            const semester = document.getElementById('semesterFilter').value;
            window.location.href = 'modules.php?limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&semester=' + semester;
        }
    </script>

</body>
</html>