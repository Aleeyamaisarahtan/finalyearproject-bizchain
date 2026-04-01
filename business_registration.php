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

$sqlCheck = "SELECT * FROM BusinessOwners WHERE UserID=?";
$stmtCheck = sqlsrv_query($conn, $sqlCheck, [$userID]);
$existingBusiness = ($stmtCheck !== false) ? sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC) : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect form data
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

    // --- Handle SSM Certificate upload ---
    $uploadDir = 'ssm_certificate/'; 
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ssmCertificatePath = $existingBusiness['SSMCertificate'] ?? null;

    if (isset($_FILES['ssm_certificate']) && $_FILES['ssm_certificate']['error'] === 0) {
        $fileTmpPath = $_FILES['ssm_certificate']['tmp_name'];
        $fileName = $_FILES['ssm_certificate']['name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'ssm_' . uniqid() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpPath, $destination)) {
            $ssmCertificatePath = $destination;
        } else {
            die("Error uploading SSM certificate.");
        }
    }

$businessLogoPath = $existingBusiness['BusinessLogo'] ?? null; //

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
    if ($existingBusiness) {
        if (
            $existingBusiness['SSMRegistrationNumber'] === $ssmRegNo &&
            $existingBusiness['StoreType'] === $storeType &&
            $existingBusiness['SSMCertificate'] === $ssmCertificatePath
        ) {
            $status = $existingBusiness['Status'] ?? 'Pending';
        }
    }

    if ($existingBusiness) {
        // --- Update ---
        $sql = "UPDATE BusinessOwners SET
            CompanyName=?, SSMRegistrationNumber=?, SSMCertificate=?, BusinessLogo=?, BusinessType=?, BusinessField=?, StoreType=?, BusinessAddress=?, CompanyEmail=?, BusinessContactNumber=?, BusinessFacebook=?, BusinessFacebookLink=?, BusinessInstagram=?, BusinessInstagramLink=?, BusinessTikTok=?, BusinessTikTokLink=?, BusinessWebsite=?, Status=?
            WHERE UserID=?";
        $params = [
            $companyName,$ssmRegNo,$ssmCertificatePath,$businessLogoPath,$businessType,$businessField,$storeType,$businessAddress,$companyEmail,$businessContactNumber,$facebook,$facebookLink,$instagram,$instagramLink,$tiktok,$tiktokLink,$website,$status,$userID
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) { print_r(sqlsrv_errors()); die(); }
        echo "<script>alert('Business updated successfully!'); window.location='homepage.php';</script>";
        exit();
    } else {
        // --- Insert ---
        $sql = "INSERT INTO BusinessOwners 
            (UserID, CompanyName, SSMRegistrationNumber, SSMCertificate, BusinessLogo, BusinessType, BusinessField, StoreType, BusinessAddress, CompanyEmail, BusinessContactNumber, BusinessFacebook, BusinessFacebookLink, BusinessInstagram, BusinessInstagramLink, BusinessTikTok, BusinessTikTokLink, BusinessWebsite, Status, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATEADD(HOUR, 8, GETDATE()))";
        $params = [
            $userID,$companyName,$ssmRegNo,$ssmCertificatePath,$businessLogoPath,$businessType,$businessField,$storeType,$businessAddress,$companyEmail,$businessContactNumber,$facebook,$facebookLink,$instagram,$instagramLink,$tiktok,$tiktokLink,$website,$status
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) { print_r(sqlsrv_errors()); die(); }
        echo "<script>alert('Business registered successfully!'); window.location='homepage.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BizChain | Register Business</title>
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

@media (max-width:768px){ .main-content{ margin-left:0; padding:20px 10px; } .register-container{ width:100%; } }
</style>
</head>
<body>
<img id="bg-image" src="SBackground.jpeg" alt="Background">
<div class="mainwrapper">
<?php include 'sidebar.php'; ?> </div>

<div class="main-content">
    <div class="register-container">
        <h2>REGISTER BUSINESS COMPANY</h2>
        <form action="" method="post" enctype="multipart/form-data">

            <label>Company Name</label>
            <input type="text" name="company_name" placeholder="Company Name" required>

            <label>Upload Business Logo</label>
            <input type="file" name="business_logo" accept=".pdf,.jpg,.jpeg,.png">

            <label>SSM Registration Number</label>
            <input type="text" name="ssm_reg_no" placeholder="SSM Registration Number" required>

            <label>Upload SSM Certificate</label>
            <input type="file" name="ssm_certificate" accept=".pdf,.jpg,.jpeg,.png" required>

            <label>Business Type (Legal Structure)</label>
            <select id="businessType" name="business_type" onchange="toggleOther('businessType','otherBusinessType')" required>
                <option value="">-- Select Business Type --</option>
                <option>Sole Proprietorship</option>
                <option>Partnership</option>
                <option>Private Limited (Sdn Bhd)</option>
                <option>Public Limited (Berhad)</option>
                <option>Non-Profit Organisation</option>
                <option value="Others">Others</option>
            </select>
            <input type="text" id="otherBusinessType" name="other_business_type" placeholder="Please specify" style="display:none;">

            <label>Business Field / Industry</label>
            <select id="businessField" name="business_field" onchange="toggleOther('businessField','otherBusinessField')" required>
                <option value="">-- Select Business Field --</option>
                <option>Food & Beverage (F&B)</option>
                <option>Retail</option>
                <option>Wedding & Event</option>
                <option>Technology / IT</option>
                <option>Finance / Banking</option>
                <option>Health & Wellness</option>
                <option>Education</option>
                <option value="Others">Others</option>
            </select>
            <input type="text" id="otherBusinessField" name="other_business_field" placeholder="Please specify" style="display:none;">

            <label>Store Type</label>
            <select name="store_type" id="storeType" onchange="toggleAddress()" required>
                <option value="">-- Select Store Type --</option>
                <option value="Physical">Physical Store</option>
                <option value="Online">Online Store</option>
                <option value="Both">Both (Physical & Online)</option>
            </select>

            <div id="businessAddressContainer" style="display:none;">
                <label>Business Address</label>
                <textarea name="business_address" placeholder="Business Address"></textarea>
            </div>

            <label>Company Email</label>
            <input type="email" name="company_email" placeholder="Company Email" required>

            <label>Business Contact Number</label>
            <input type="tel" name="business_contact_number" placeholder="Contact Number" required>

            <label>Facebook Username(Optional)</label>
            <input type="text" name="facebook" placeholder="Facebook Username">
            <label>Facebook Link (Optional)</label>
            <input type="url" name="facebook_link" placeholder="">

            <label>Instagram Username(Optional)</label>
            <input type="text" name="instagram" placeholder="Instagram Username">
            <label>Instagram Link (Optional)</label>
            <input type="url" name="instagram_link" placeholder="">

            <label>TikTok Username(Optional)</label>
            <input type="text" name="tiktok" placeholder="TikTok Username">
            <label>TikTok Link (Optional)</label>
            <input type="url" name="tiktok_link" placeholder="">

            <label>Website Link(Optional)</label>
            <input type="url" name="website" placeholder="Website URL">

            <label style="display:flex; align-items:flex-start; font-size:13px; margin-top:10px;white-space:nowrap">
                <input type="checkbox" required style="margin-top:2px; margin-right:10px;">
                I consent to the collection and processing of my personal data in accordance with the<br>
                Privacy Policy and agree to the Terms of Service.
            </label>

            <button type="submit">Register Company</button>
            <p class="note">⚠️ Please ensure all information is accurate. Incorrect details or missing SSM certificate may delay verification.</p>

        </form>
    </div>
</div>

<script>
function toggleOther(selectId, inputId){
    const select = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    input.style.display = select.value==="Others"?"block":"none";
    if(select.value!=="Others") input.value="";
}

function toTitleCase(str){
    return str.replace(/\w\S*/g, txt=>txt.charAt(0).toUpperCase()+txt.substr(1).toLowerCase());
}

document.getElementById("otherBusinessType").addEventListener("input", function(){ this.value = toTitleCase(this.value); });
document.getElementById("otherBusinessField").addEventListener("input", function(){ this.value = toTitleCase(this.value); });

function toggleAddress(){
    const storeType = document.getElementById('storeType').value;
    const addressContainer = document.getElementById('businessAddressContainer');
    if(storeType==="Physical" || storeType==="Both"){
        addressContainer.style.display="block";
        addressContainer.querySelector('textarea').required=true;
    } else {
        addressContainer.style.display="none";
        addressContainer.querySelector('textarea').required=false;
        addressContainer.querySelector('textarea').value="";
    }
}
</script>

</body>
</html>