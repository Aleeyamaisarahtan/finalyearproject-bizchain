<?php
session_start();

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


$reportThreshold = 10; 

$sql = "
    SELECT bo.BusinessOwnerID, bo.CompanyName, bo.BusinessType, bo.BusinessField,
           bo.BusinessLogo,
           COUNT(sr.ReportID) AS ApprovedReports
    FROM BusinessOwners bo
    JOIN ScamReports sr ON bo.BusinessOwnerID = sr.BusinessOwnerID
    WHERE sr.Status = 'Approved'
    GROUP BY bo.BusinessOwnerID, bo.CompanyName, bo.BusinessType, bo.BusinessField, bo.BusinessLogo
    HAVING COUNT(sr.ReportID) >= ?
    ORDER BY ApprovedReports DESC
";

$params = [$reportThreshold];
$stmt = sqlsrv_query($conn, $sql, $params);

$scamBusinesses = [];
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $scamBusinesses[] = $row;
    }
} else {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Careful with Scammers | BizChain</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    font-family: 'Poppins', sans-serif;
    overflow-x: hidden;
}

/* Background Image */
#bg-image {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -1;
}

.center-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 900px;
    padding: 30px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 15px;
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    color: #fff;
    z-index: 2;
    max-height: 90vh;
    overflow-y: auto;
}

h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 28px;
    font-weight: 700;
    color: #ff4b5c;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
}

.card {
    background: rgba(255, 182, 193, 0.25); 
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: #000; 
}

.card h3 {
    margin: 0 0 5px 0;
    color: #000; 
}

.card p {
    margin: 3px 0;
    color: #000;
}

.card a {
    text-decoration: none;
    color: #800000;
    font-weight: bold;
}

.card a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<img id="bg-image" src="SBackground.jpeg" alt="Background">

<?php include 'Sidebar.php'; ?>

<div class="center-content">
    <h2>⚠️ Careful with Scammers</h2>

    <?php if (!empty($scamBusinesses)): ?>
        <?php foreach ($scamBusinesses as $b): ?>
            <div class="card">
    <div class="card-content" style="display:flex; align-items:center; gap:15px;">
        <!-- Business Logo or Fallback -->
        <div class="logo" style="flex-shrink:0;">
           <img src="<?= !empty($b['BusinessLogo']) ? htmlspecialchars($b['BusinessLogo']) : 'Profile Picture.png'; ?>" 
     alt="Logo" 
     style="width:60px; height:60px; object-fit:cover; border-radius:50px;">
        </div>
        <div class="info">
            <h3 style="color:blue;" ><?= htmlspecialchars($b['CompanyName']); ?></h3>
            <p><strong>Business Type:</strong> <?= htmlspecialchars($b['BusinessType']); ?></p>
            <p><strong>Business Field:</strong> <?= htmlspecialchars($b['BusinessField']); ?></p>
            <p><strong>Approved Reports:</strong> <?= $b['ApprovedReports']; ?></p>
            <p><a href="business_profile.php?id=<?= $b['BusinessOwnerID']; ?>">View Business Profile</a></p>
        </div>
    </div>
</div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center; color:#fff;">No businesses have reached the report threshold yet.</p>
    <?php endif; ?>
</div>

</body>
</html>