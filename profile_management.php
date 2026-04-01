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
if ($conn === false) die(print_r(sqlsrv_errors(), true));

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];

// --- Fetch existing business info ---
$sql = "SELECT * FROM BusinessOwners WHERE UserID=?";
$stmt = sqlsrv_query($conn, $sql, [$userID]);
$business = ($stmt !== false) ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = $_POST['company_name'];
    $ssmRegNo = $_POST['ssm_reg_no'];

    $businessType = $_POST['business_type'] === 'Others' ? $_POST['other_business_type'] : $_POST['business_type'];
    $businessField = $_POST['business_field'] === 'Others' ? $_POST['other_business_field'] : $_POST['business_field'];

    $storeType = $_POST['store_type'];
    $businessAddress = $_POST['business_address'] ?? null;

    $companyEmail = $_POST['company_email'];
    $businessContactNumber = $_POST['business_contact_number'];

    $facebook = $_POST['facebook'] ?? null;
    $instagram = $_POST['instagram'] ?? null;
    $tiktok = $_POST['tiktok'] ?? null;
    $website = $_POST['website'] ?? null;

    $facebookLink = $_POST['facebook_link'] ?? null;
    $instagramLink = $_POST['instagram_link'] ?? null;
    $tiktokLink = $_POST['tiktok_link'] ?? null;

    // --- Handle SSM certificate ---
    $uploadDir = 'ssm_certificate/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ssmCertificatePath = $business['SSMCertificate'] ?? null;
    $ssmChanged = false;

    if (isset($_FILES['ssm_certificate']) && $_FILES['ssm_certificate']['error'] === 0) {
        $fileTmpPath = $_FILES['ssm_certificate']['tmp_name'];
        $fileName = $_FILES['ssm_certificate']['name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

        $newFileName = 'ssm_' . uniqid() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destination)) {
            $ssmCertificatePath = $destination;
            $ssmChanged = true;
        } else {
            die("Error uploading SSM certificate.");
        }
    }

    // --- Handle Business Logo ---
    $logoUploadDir = 'business_logo/';
    if (!is_dir($logoUploadDir)) mkdir($logoUploadDir, 0755, true);

    $businessLogoPath = $business['BusinessLogo'] ?? null;

    if (isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] === 0) {
        $fileTmpPath = $_FILES['business_logo']['tmp_name'];
        $fileName = $_FILES['business_logo']['name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

        $newFileName = 'logo_' . uniqid() . '.' . $fileExt;
        $destination = $logoUploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destination)) {
            $businessLogoPath = $destination;
        } else {
            die("Error uploading Business Logo.");
        }
    }

    // --- Determine status ---
    $status = 'Pending';
    if ($business) {
        if (
            $business['SSMRegistrationNumber'] === $ssmRegNo &&
            $business['StoreType'] === $storeType &&
            !$ssmChanged
        ) {
            $status = $business['Status'] ?? 'Pending';
        }
    }

    // --- Update or Insert database ---
    if ($business) {
        // --- UPDATE ---
        $sqlUpdate = "UPDATE BusinessOwners SET
            CompanyName = ?, 
            SSMRegistrationNumber = ?, 
            SSMCertificate = ?, 
            BusinessLogo = ?, 
            BusinessType = ?, 
            BusinessField = ?, 
            StoreType = ?, 
            BusinessAddress = ?, 
            CompanyEmail = ?, 
            BusinessContactNumber = ?, 
            BusinessFacebook = ?, 
            BusinessFacebookLink = ?, 
            BusinessInstagram = ?, 
            BusinessInstagramLink = ?, 
            BusinessTikTok = ?, 
            BusinessTikTokLink = ?, 
            BusinessWebsite = ?, 
            Status = ?
            WHERE UserID = ?";

        $params = [
            $companyName,
            $ssmRegNo,
            $ssmCertificatePath,
            $businessLogoPath,
            $businessType,
            $businessField,
            $storeType,
            $businessAddress,
            $companyEmail,
            $businessContactNumber,
            $facebook,
            $facebookLink,
            $instagram,
            $instagramLink,
            $tiktok,
            $tiktokLink,
            $website,
            $status,
            $userID
        ];

        $stmt = sqlsrv_query($conn, $sqlUpdate, $params);
        if ($stmt === false) {
            echo "<pre>"; print_r(sqlsrv_errors()); echo "</pre>"; die();
        } else {
            sqlsrv_free_stmt($stmt);
            echo "<script>
                    alert('Profile updated successfully!');
                    window.location='homepage.php';
                  </script>";
            exit();
        }

    } else {
        // --- INSERT NEW BUSINESS ---
        $sqlInsert = "INSERT INTO BusinessOwners 
            (UserID, CompanyName, SSMRegistrationNumber, SSMCertificate, BusinessLogo, BusinessType, BusinessField, StoreType, BusinessAddress, CompanyEmail, BusinessContactNumber, BusinessFacebook, BusinessFacebookLink, BusinessInstagram, BusinessInstagramLink, BusinessTikTok, BusinessTikTokLink, BusinessWebsite, Status, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATEADD(HOUR, 8, GETDATE()))";

        $params = [
            $userID,
            $companyName,
            $ssmRegNo,
            $ssmCertificatePath,
            $businessLogoPath,
            $businessType,
            $businessField,
            $storeType,
            $businessAddress,
            $companyEmail,
            $businessContactNumber,
            $facebook,
            $facebookLink,
            $instagram,
            $instagramLink,
            $tiktok,
            $tiktokLink,
            $website,
            $status
        ];

        $stmt = sqlsrv_query($conn, $sqlInsert, $params);
        if ($stmt === false) {
            echo "<pre>"; print_r(sqlsrv_errors()); echo "</pre>"; die();
        } else {
            sqlsrv_free_stmt($stmt);
            echo "<script>
                    alert('Business registered successfully!');
                    window.location='homepage.php';
                  </script>";
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BizChain | Profile  Management</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
    max-width: 500px;
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
    color:#black; 
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
</head>
<body>

<img id="bg-image" src="SBackground.jpeg" alt="Background">
<div class="mainwrapper">
<?php include 'sidebar.php'; ?> </div>

<div class="main-content">
    <div class="register-container">
<h2>PROFILE MANAGEMENT</h2>

<form method="POST" enctype="multipart/form-data">
    <label>Business Name</label>
    <input type="text" name="company_name" value="<?= htmlspecialchars($business['CompanyName'] ?? '', ENT_QUOTES); ?>" required>

    <label>Business Logo</label>
<?php if (!empty($business['BusinessLogo'])): ?>
    <p>CURRENT: 
        <a href="<?= htmlspecialchars($business['BusinessLogo'], ENT_QUOTES); ?>" target="_blank">
            VIEW LOGO
        </a>
    </p>
<?php endif; ?>
<input type="file" name="business_logo" accept=".jpg,.jpeg,.png,.gif">

    <label>SSM Registration Number</label>
    <input type="text" name="ssm_reg_no" value="<?= htmlspecialchars($business['SSMRegistrationNumber'] ?? '', ENT_QUOTES); ?>" required>

    <label>SSM Certificate</label>
    <?php if(!empty($business['SSMCertificate'])): ?>
        <p>CURRENT:<a href="<?= htmlspecialchars($business['SSMCertificate'], ENT_QUOTES); ?>" target="_blank">VIEW SSM FILE</a></p>
    <?php endif; ?>
    <input type="file" name="ssm_certificate" accept=".pdf,.jpg,.jpeg,.png">

    <!-- Business Type -->
<label>Business Type</label>
<select id="businessType" name="business_type" onchange="toggleOther('businessType','otherBusinessType')" required>
    <option value="">-- Select Business Type --</option>
    <?php
    $types = ["Sole Proprietorship","Partnership","Private Limited (Sdn Bhd)","Public Limited (Berhad)","Non-Profit Organisation","Others"];
    $businessTypeValue = $business['BusinessType'] ?? '';
    $isOtherBusinessType = !in_array($businessTypeValue, $types);
    foreach($types as $t){
        // If existing value is not in list, select 'Others'
        $selected = ($businessTypeValue === $t || ($isOtherBusinessType && $t === 'Others')) ? 'selected' : '';
        echo "<option value='$t' $selected>$t</option>";
    }
    ?>
</select>
<input type="text" id="otherBusinessType" name="other_business_type" placeholder="Please specify" 
       style="display:<?= $isOtherBusinessType ? 'block' : 'none' ?>; margin-top:10px;"
       value="<?= $isOtherBusinessType ? htmlspecialchars($businessTypeValue, ENT_QUOTES) : '' ?>">

<!-- Business Field -->
<label>Business Field / Industry</label>
<select id="businessField" name="business_field" onchange="toggleOther('businessField','otherBusinessField')" required>
    <option value="">-- Select Business Field --</option>
    <?php
    $fields = ["Food & Beverage (F&B)","Retail","Wedding & Event","Technology / IT","Finance / Banking","Health & Wellness","Education","Others"];
    $businessFieldValue = $business['BusinessField'] ?? '';
    $isOtherBusinessField = !in_array($businessFieldValue, $fields);
    foreach($fields as $f){
        $selected = ($businessFieldValue === $f || ($isOtherBusinessField && $f === 'Others')) ? 'selected' : '';
        echo "<option value='$f' $selected>$f</option>";
    }
    ?>
</select>
<input type="text" id="otherBusinessField" name="other_business_field" placeholder="Please specify"
       style="display:<?= $isOtherBusinessField ? 'block' : 'none' ?>; margin-top:10px;"
       value="<?= $isOtherBusinessField ? htmlspecialchars($businessFieldValue, ENT_QUOTES) : '' ?>">

    <label>Store Type</label>
    <select name="store_type" id="storeType" onchange="toggleAddress()" required>
        <option value="">-- Select Store Type --</option>
        <?php
        $stores = ["Physical","Online","Both"];
        foreach($stores as $s){
            $selected = ($business['StoreType'] ?? '') === $s ? 'selected' : '';
            echo "<option value='$s' $selected>$s</option>";
        }
        ?>
    </select>

    <div id="businessAddressContainer" style="display:none;">
        <label>Business Address</label>
        <textarea name="business_address"><?= htmlspecialchars($business['BusinessAddress'] ?? '', ENT_QUOTES); ?></textarea>
    </div>

    <label>Company Email</label>
    <input type="email" name="company_email" value="<?= htmlspecialchars($business['CompanyEmail'] ?? '', ENT_QUOTES); ?>" required>

    <label>Business Contact Number</label>
    <input type="text" name="business_contact_number" value="<?= htmlspecialchars($business['BusinessContactNumber'] ?? '', ENT_QUOTES); ?>">

    <label>Facebook Username</label>
    <input type="text" name="facebook" value="<?= htmlspecialchars($business['BusinessFacebook'] ?? '', ENT_QUOTES); ?>">
    <label>Facebook Link</label>
    <input type="url" name="facebook_link" value="<?= htmlspecialchars($business['BusinessFacebookLink'] ?? '', ENT_QUOTES); ?>">

    <label>Instagram Username</label>
    <input type="text" name="instagram" value="<?= htmlspecialchars($business['BusinessInstagram'] ?? '', ENT_QUOTES); ?>">
    <label>Instagram Link</label>
    <input type="url" name="instagram_link" value="<?= htmlspecialchars($business['BusinessInstagramLink'] ?? '', ENT_QUOTES); ?>">

    <label>TikTok Username</label>
    <input type="text" name="tiktok" value="<?= htmlspecialchars($business['BusinessTikTok'] ?? '', ENT_QUOTES); ?>">
    <label>TikTok Link</label>
    <input type="url" name="tiktok_link" value="<?= htmlspecialchars($business['BusinessTikTokLink'] ?? '', ENT_QUOTES); ?>">

    <label>Website Link</label>
    <input type="url" name="website" value="<?= htmlspecialchars($business['BusinessWebsite'] ?? '', ENT_QUOTES); ?>">

    <button type="submit">UPDATE</button>
</form>
</div>

<script>
function toggleOther(selectId, inputId) {
    const select = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    if(select.value === 'Others') input.style.display='block';
    else { input.style.display='none'; input.value=''; }
}

function toggleAddress(){
    const storeType = document.getElementById('storeType').value;
    const container = document.getElementById('businessAddressContainer');
    if(storeType === 'Physical' || storeType === 'Both'){
        container.style.display='block';
        container.querySelector('textarea').required = true;
    } else {
        container.style.display='none';
        container.querySelector('textarea').required = false;
    }
}
toggleAddress();
</script>
</body>
</html>