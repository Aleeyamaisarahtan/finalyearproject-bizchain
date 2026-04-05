<?php
session_start();

// DB CONNECTION
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

// FUNCTION TO LOG EVENTS
function logEvent($conn, $eventType, $userType, $userIdentifier, $businessID) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $tsql = "INSERT INTO AuditLogs (EventType, UserType, UserIdentifier, BusinessOwnerID, IPAddress, CreatedAt)
             VALUES (?, ?, ?, ?, ?, DATEADD(HOUR, 8, GETDATE()))";
    $params = [$eventType, $userType, $userIdentifier, $businessID, $ip];
    sqlsrv_query($conn, $tsql, $params);
}

// GET IDs
$businessID = isset($_GET['businessID']) ? intval($_GET['businessID']) : null;
$userID = $_SESSION['UserID'] ?? null;

// FETCH COMPANY NAME
$companyName = "";
if ($businessID) {
    $sql = "SELECT CompanyName FROM BusinessOwners WHERE BusinessOwnerID = ?";
    $stmt = sqlsrv_query($conn, $sql, [$businessID]);
    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $companyName = $row['CompanyName'];
    }
}

// INSERT REPORT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($userID && $businessID) {

        // Handle Platform
        $platform = $_POST['platform'];
        if ($platform === "Other") $platform = $_POST['other_platform'] ?? "";

        // Handle Scam Type
        $scamType = $_POST['scam_type'];
        if ($scamType === "Others") $scamType = $_POST['other_scam_type'] ?? "";

        $scamDate = $_POST['scam_date'];
        $description = $_POST['description'];
        $amount = $_POST['amount'];

        // Handle file upload
        $evidencePath = null;
        if (!empty($_FILES['evidence_file']['name'])) {
            $targetDir = "evidence/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = time() . "_" . basename($_FILES["evidence_file"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["evidence_file"]["tmp_name"], $targetFilePath)) {
                $evidencePath = $targetFilePath; // Store this in DB
            } else {
                echo "<script>alert('Failed to upload evidence file');</script>";
            }
        }

        $sql = "
    INSERT INTO ScamReports 
    (BusinessOwnerID, UserID, Platform, ScamDate, ScamType, ScamDescription, AmountLost, Evidence, CreatedAt)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATEADD(HOUR, 8, GETDATE()))
";

        $params = [
            $businessID,
            $userID,
            $platform,
            $scamDate,
            $scamType,
            $description,
            $amount,
            $evidencePath
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) die(print_r(sqlsrv_errors(), true));

        // LOG EVENT
        logEvent($conn, "SubmitReport", "User", $userID, $businessID);

        echo "<script>alert('Report submitted successfully');</script>";
        header("Location: business_profile.php?id=" . $businessID);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Scamming Company</title>

<style>
    .main-wrapper {
    display: flex;
    min-height: 100vh;
}

.main-content {
   position: fixed;           
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 600px;
    z-index: 10;   

}

.register-container {
    width: 700px;
    background: rgba(255,255,255,0.15);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    color: black;
    overflow-y: auto;         
    max-height: 95vh;
    display: flex;
    flex-direction: column;
    align-items: center;     
    text-align: center; 
}

body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    margin: 0;
}

#bg-image {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -1;
}

.register-container input, .register-container select, .register-container textarea { 
    width:100%; 
    padding:12px; 
    border-radius:20px; 
    border:2px solid rgba(255,255,255,0.3); 
    background:transparent; 
    color:black; 
    font-size:14px; 
    margin-bottom:12px; 
    outline:none;
 }
.register-container input::placeholder, .register-container select option { 
    color:black; 
}
.register-container textarea { 
    min-height:100px; 
    resize:vertical; 
}

.register-container button { 
    width:100%; 
    padding:14px; 
    background-color:#007bff; 
    color:#fff; 
    border:none; 
    border-radius:40px; 
    font-size:16px; 
    cursor:pointer; 
    margin-top:10px; 
}

.register-container button:hover { 
    background-color:#0056b3; 
}

.note { 
    font-size:12px; 
    color:#ccc; 
    margin-top:15px; 
    text-align:center; 
}

.register-container label{
    width: 35%;             
    text-align: left;       
    margin-right: 15px;     
    font-weight: 500;

}
</style>

<script>
function toggleOther(selectId, otherBoxId) {
    var select = document.getElementById(selectId);
    var otherBox = document.getElementById(otherBoxId);

    if (select.value === "Other" || select.value === "Others") {
        otherBox.style.display = "block";
    } else {
        otherBox.style.display = "none";
    }
}
</script>

</head>

<body>

<img id="bg-image" src="SBackground.jpeg" alt="Background">
<div class="mainwrapper">
<?php include 'sidebar.php'; ?> </div>

<div class="main-content">
    <div class="register-container">

    <h2 style="color:#007bff;">REPORT SCAMMING COMPANY</h2>

   <form method="POST" enctype="multipart/form-data">

        <label>Company Name</label>
        <input type="text" value="<?= htmlspecialchars($companyName); ?>" readonly>

        <label>Platform Being Scam</label>
        <select name="platform" id="platform" onchange="toggleOther('platform','otherPlatformBox')" required>
            <option value="">-- Select Platform --</option>
            <option>Whatsapp</option>
            <option>Facebook</option>
            <option>Instagram</option>
            <option>SMS</option>
            <option>Tiktok</option>
            <option>Other</option>
        </select>

        <div id="otherPlatformBox" style="display:none;">
            <label>Other Platform</label>
            <input type="text" name="other_platform">
        </div>

        <label>Scam Date</label>
        <input type="date" name="scam_date" required>

        <label>Type of Scam</label>
        <select name="scam_type" id="scam_type" onchange="toggleOther('scam_type','otherScamTypeBox')" required>
            <option value="">-- Select Scam Type --</option>
            <option>Investment Scam</option>
            <option>Online Shopping Scam</option>
            <option>Job Scam</option>
            <option>Phishing / Impersonation</option>
            <option>Others</option>
        </select>

        <div id="otherScamTypeBox" style="display:none;">
            <label>Other Scam Type</label>
            <input type="text" name="other_scam_type">
        </div>

        <label>Scam Description</label>
        <textarea name="description" required></textarea>

        <label>Amount Lost (RM)</label>
        <input type="number" name="amount">

        <label>Evidence File</label>
    <input type="file" name="evidence_file" accept=".jpg,.jpeg,.png,.pdf">

        <button type="submit">Submit Report</button>
    </form>

    <p class="note">
        ⚠️ Please ensure all information is accurate. False reporting may lead to action.
    </p>
</div>

</body>
</html>