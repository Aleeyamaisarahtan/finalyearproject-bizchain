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
    $fullname = $_POST['fullname'];
    $ic = $_POST['ic'];
    $gender = $_POST['gender'];
    $username = $_POST['username'];
    $phonenum = $_POST['phonenum'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];

    // Check passwords match
    if ($password !== $confirmpassword) {
        $msg = "Passwords do not match.";
        echo "<script>alert('".$msg."'); window.location='signup.html';</script>";
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

 
    $tsql = "INSERT INTO Users ([FullName], [ICNumber], [Gender], [Username], [PhoneNumber], [EmailAddress], [PasswordHash], [CreatedAt]) 
         VALUES (?, ?, ?, ?, ?, ?, ?,DATEADD(HOUR, 8, GETDATE()))";

    $params = [$fullname, $ic, $gender, $username, $phonenum, $email, $hashedPassword];

    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt) {
        $msg = "Account created successfully!";
        // Alert message + redirect to login.html
        echo "<script>alert('".$msg."'); window.location='login.html';</script>";
        exit;
    } else {
        $msg = "Error creating account. Please try again.";
        echo "<script>alert('".$msg."'); window.location='signup.html';</script>";
        exit;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
} else {

    header("Location: signup.html");
    exit;
}