<?php
// --- Database connection ---
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

    // Get user from database
    $tsql = "SELECT * FROM Users WHERE Username = ?";
    $params = [$username];
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt && sqlsrv_has_rows($stmt)) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        // Verify password
        if (password_verify($password, $row['PasswordHash'])) {
            // SUCCESS
            echo "<script>alert('Login successful!'); window.location='homepage.html';</script>";
            exit;
        } else {
            // Wrong password
            echo "<script>alert('Invalid password!');</script>";
        }
    } else {
        // Username not found
        echo "<script>alert('Username not found!');</script>";
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
?>