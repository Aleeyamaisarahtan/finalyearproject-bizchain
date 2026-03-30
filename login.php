<?php
session_start();

// Database connection
$serverName = "localhost,1433";
$connectionOptions = [
    "Database" => "BizChainDB",
    "Uid" => "SA",
    "PWD" => "Bizchain123!",
    "Encrypt" => false
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $_POST['username']; 
    $password = $_POST['password'];

    // --- 1. Check Admin
    $adminQuery = "SELECT AdminID, PasswordHash, FullName FROM Admins WHERE Email = ? AND PasswordHash = ?";
    $adminParams = [$input, $password];
    $stmt = sqlsrv_query($conn, $adminQuery, $adminParams);

    if ($stmt) {
        $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($admin) {
            $_SESSION['admin_id'] = $admin['AdminID'];
            $_SESSION['admin_name'] = $admin['FullName'];
            header("Location: admin_dashboard.php");
            exit;
        }
    } else {
        die(print_r(sqlsrv_errors(), true));
    }

    // --- 2. Check Users by EMAIL
    $userQuery = "SELECT UserID, PasswordHash, FullName FROM Users WHERE EmailAddress = ?";
    $userParams = [$input];
    $stmt = sqlsrv_query($conn, $userQuery, $userParams);

    if ($stmt) {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($user && password_verify($password, $user['PasswordHash'])) {
            // User login success
            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Username'] = $user['FullName'];
            header("Location: homepage.php");
            exit;
        } else {
            $msg = "Invalid email or password";
            header("Location: login.html?message=" . urlencode($msg) . "&status=error");
            exit;
        }
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}

sqlsrv_close($conn);
?>