<?php
session_start();

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

if (!isset($_SESSION['UserID'])) {
    die("You must be logged in to register a business.");
}

$userID = $_SESSION['UserID'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect form data
    $companyName = $_POST['company_name'];
    $ssmRegNo = $_POST['ssm_reg_no'];

    $businessType = $_POST['business_type'] === 'Others' ? $_POST['other_business_type'] : $_POST['business_type'];
    $businessField = $_POST['business_field'] === 'Others' ? $_POST['other_business_field'] : $_POST['business_field'];

    $businessAddress = $_POST['business_address'];
    $companyEmail = $_POST['company_email'];
    $businessContactNumber = $_POST['business_contact_number'];
    $facebook = $_POST['facebook'] ?? null;
    $instagram = $_POST['instagram'] ?? null;
    $tiktok = $_POST['tiktok'] ?? null;
    $website = $_POST['website'] ?? null;

    // --- Handle file upload ---
    $uploadDir = 'C:\\Users\\User\\OneDrive\\Desktop\\BIZCHAIN\\finalyearproject-bizchain\\ssm_certificate\\';
    $ssmCertificatePath = null;

    if (isset($_FILES['ssm_certificate']) && $_FILES['ssm_certificate']['error'] === 0) {
        $fileTmpPath = $_FILES['ssm_certificate']['tmp_name'];
        $fileName = $_FILES['ssm_certificate']['name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

        // Generate unique filename
        $newFileName = 'ssm_' . uniqid() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;

        // Move uploaded file
        if (move_uploaded_file($fileTmpPath, $destination)) {
            $ssmCertificatePath = $destination;
        } else {
            die("Error uploading SSM certificate.");
        }
    } else {
        die("SSM certificate is required.");
    }

    // --- Insert into database ---
$sql = "INSERT INTO BusinessOwners 
    (UserID, CompanyName, SSMRegistrationNumber, SSMCertificate, BusinessType, BusinessField, BusinessAddress, CompanyEmail, BusinessContactNumber, BusinessFacebook, BusinessInstagram, BusinessTikTok, BusinessWebsite, CreatedAt)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATEADD(HOUR, 8, GETDATE()))";

    $params = [
        $userID,
        $companyName,
        $ssmRegNo,
        $ssmCertificatePath, 
        $businessType,
        $businessField,
        $businessAddress,
        $companyEmail,
        $businessContactNumber,
        $facebook,
        $instagram,
        $tiktok,
        $website
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo "<pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
        die();
    } else {
        sqlsrv_free_stmt($stmt);
        echo "<script>alert('Business registered successfully!'); window.location='homepage.php';</script>";
        exit();
    }
}
?>