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

    // --- HANDLE PROFILE PICTURE ---
$uploadDir = 'profile_picture/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$profilePicturePath = 'profile picture.png'; // default image

if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {

    $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
    $fileName = $_FILES['profile_picture']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExt = ['jpg', 'jpeg', 'png'];

    if (in_array($fileExt, $allowedExt)) {

        $newFileName = 'user_' . uniqid() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destination)) {
            $profilePicturePath = $destination;
        }
    }
}

 
    $tsql = "INSERT INTO Users 
([FullName], [ICNumber], [Gender], [Username], [PhoneNumber], [EmailAddress], [PasswordHash], [ProfilePicture], [CreatedAt]) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATEADD(HOUR, 8, GETDATE()))";

    $params = [$fullname, $ic, $gender, $username, $phonenum, $email, $hashedPassword, $profilePicturePath];

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