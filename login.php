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

function logEvent($conn, $eventType, $userType, $identifier, $status) {
    $ip = $_SERVER['REMOTE_ADDR'];

    $query = "INSERT INTO AuditLogs (EventType, UserType, UserIdentifier, IPAddress, EventTime, Status)
              VALUES (?, ?, ?, ?, DATEADD(HOUR, 8, GETUTCDATE()), ?)";
    $params = [$eventType, $userType, $identifier, $ip, $status];

    sqlsrv_query($conn, $query, $params);
}

// Connect DB
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $input = $_POST['username']; 
    $password = $_POST['password'];

    // ==========================
    // 1. CHECK ADMIN
    // ==========================
    $adminQuery = "SELECT AdminID, PasswordHash, FullName 
                   FROM Admins 
                   WHERE Email = ? AND PasswordHash = ?";
    $adminParams = [$input, $password];

    $stmt = sqlsrv_query($conn, $adminQuery, $adminParams);

    if ($stmt) {
        $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($admin) {
            // ✅ Log Admin Success ONLY
            logEvent($conn, "Login", "Admin", $input, "Success");

            $_SESSION['admin_id'] = $admin['AdminID'];
            $_SESSION['admin_name'] = $admin['FullName'];

            header("Location: admin_dashboard.php");
            exit;
        }
    } else {
        die(print_r(sqlsrv_errors(), true));
    }

    // ==========================
    // 2. CHECK USER
    // ==========================
    $userQuery = "SELECT UserID, PasswordHash, FullName 
                  FROM Users 
                  WHERE EmailAddress = ?";
    $userParams = [$input];

    $stmt = sqlsrv_query($conn, $userQuery, $userParams);

    if ($stmt) {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($user && password_verify($password, $user['PasswordHash'])) {

            // ✅ Log User Success
            logEvent($conn, "Login", "User", $input, "Success");

            $_SESSION['UserID'] = $user['UserID'];
            $_SESSION['Username'] = $user['FullName'];

            header("Location: homepage.php");
            exit;
        }

    } else {
        die(print_r(sqlsrv_errors(), true));
    }

    // ==========================
    // 3. IF BOTH FAIL → LOG ONCE
    // ==========================
    logEvent($conn, "Login", "Unknown", $input, "Fail");

    $msg = "Invalid email or password";
    header("Location: login.html?message=" . urlencode($msg) . "&status=error");
    exit;
}

// Close connection
sqlsrv_close($conn);
?>