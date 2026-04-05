<?php
// --- Database connection ---
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

    $ic = str_replace('-', '', $ic);

    if (!preg_match('/^[0-9]{12}$/', $ic)) {
        logEvent($conn, "Signup", "User", $email, "Fail");
        $msg = "Invalid IC number format.";
        echo "<script>alert('".$msg."'); window.location='signup.html';</script>";
        exit;
    }

    $year = substr($ic, 0, 2);
    $month = substr($ic, 2, 2);
    $day = substr($ic, 4, 2);

    $currentYear = date("Y");
    $fullYear = ($year <= substr($currentYear, 2, 2)) ? "20".$year : "19".$year;

    if (!checkdate((int)$month, (int)$day, (int)$fullYear)) {
        logEvent($conn, "Signup", "User", $email, "Fail");
        $msg = "Invalid IC date.";
        echo "<script>alert('".$msg."'); window.location='signup.html';</script>";
        exit;
    }

    $birthDate = new DateTime("$fullYear-$month-$day");
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;

    if ($age < 18) {
        logEvent($conn, "Signup", "User", $email, "Fail");
        $msg = "You must be 18 years or older to register.";
        echo "<script>alert('".$msg."'); window.location='signup.html';</script>";
        exit;
    }

    if ($password !== $confirmpassword) {
        logEvent($conn, "Signup", "User", $email, "Fail");
        $msg = "Passwords do not match.";
        echo "<script>alert('".$msg."'); window.location='signup.html';</script>";
        exit;
    }

    if (strlen($password) < 8 || 
        !preg_match('/[A-Za-z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[\W_]/', $password)) {
        logEvent($conn, "Signup", "User", $email, "Fail");
        $msg = "Password must be at least 8 characters and include letters, numbers, and symbols.";
        echo "<script>alert('".$msg."'); window.location='signup.html';</script>";
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $uploadDir = 'profile_picture/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $profilePicturePath = 'profile picture.png';

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
        logEvent($conn, "Signup", "User", $email, "Success");
        $msg = "Account created successfully!";
        echo "<script>alert('".$msg."'); window.location='login.html';</script>";
        exit;
    } else {
        logEvent($conn, "Signup", "User", $email, "Fail");
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
?>