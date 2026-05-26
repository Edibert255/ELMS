<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Update download count
$sql = "UPDATE library_materials SET download_count = download_count + 1 WHERE id = '$material_id'";
mysqli_query($conn, $sql);

// Get file path
$sql = "SELECT file_path FROM library_materials WHERE id = '$material_id'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $material = mysqli_fetch_assoc($result);
    $file = "uploads/library/" . $material['file_path'];
    
    if (file_exists($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        exit();
    }
}

header("Location: library.php?error=file_not_found");
exit();
?>