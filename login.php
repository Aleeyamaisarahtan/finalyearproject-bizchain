<?php
session_start();

// Database connection (your SQL Server connection)
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
    $username = $_POST['username'];
    $password = $_POST['password'];

    $tsql = "SELECT UserID, PasswordHash FROM Users WHERE Username = ?";
    $params = [$username];
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row && password_verify($password, $row['PasswordHash'])) {
            $_SESSION['UserID'] = $row['UserID'];
            $_SESSION['Username'] = $username;

            header("Location: homepage.php");
            exit;
        } else {
            $msg = "Invalid username or password";
            header("Location: signin.html?message=" . urlencode($msg) . "&status=error");
            exit;
        }
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
}
sqlsrv_close($conn);
?>