<?php
session_start();

// --- Direct logout handling ---
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
}

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

// --- Upload fingerprint ---
if (isset($_POST['upload_fp']) && isset($_FILES['fingerprint'])) {
    $businessId = $_POST['business_id'];
    if ($_FILES['fingerprint']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($_FILES['fingerprint']['type'], $allowedTypes)) {
            $ext = pathinfo($_FILES['fingerprint']['name'], PATHINFO_EXTENSION);
            $newName = uniqid("fp_", true) . "." . $ext;
            $uploadPath = "fingerprint/" . $newName;
            move_uploaded_file($_FILES['fingerprint']['tmp_name'], $uploadPath);
            $sql = "UPDATE BusinessOwners SET Fingerprint=? WHERE BusinessOwnerID=?";
            sqlsrv_query($conn, $sql, [$newName, $businessId]);
        }
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- Approve/Reject business ---
if (isset($_POST['business_id'], $_POST['action'])) {
    $businessId = $_POST['business_id'];
    if ($_POST['action'] === 'approve') {
        $sql = "UPDATE BusinessOwners SET Status='Approved' WHERE BusinessOwnerID=?";
        sqlsrv_query($conn, $sql, [$businessId]);
    } else { 
        $sql = "DELETE FROM BusinessOwners WHERE BusinessOwnerID=?";
        sqlsrv_query($conn, $sql, [$businessId]);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- Approve/Delete scam report ---
if (isset($_POST['approve_report'])) {
    $reportId = $_POST['approve_report'];
    $sql = "UPDATE ScamReports SET Status='Approved' WHERE ReportID=?";
    sqlsrv_query($conn, $sql, [$reportId]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['delete_report'])) {
    $sql = "DELETE FROM ScamReports WHERE ReportID=?";
    sqlsrv_query($conn, $sql, [$_POST['delete_report']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- Delete contact message ---
if (isset($_POST['delete_contact'])) {
    $sql = "DELETE FROM ContactUs WHERE ContactUsID=?";
    sqlsrv_query($conn, $sql, [$_POST['delete_contact']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/* ===================== FETCH DATA ===================== */

// Business Owners
$businessSql = "SELECT b.*, u.FullName AS OwnerName 
                FROM BusinessOwners b
                LEFT JOIN Users u ON b.UserID = u.UserID
                ORDER BY b.BusinessOwnerID DESC";
$businessStmt = sqlsrv_query($conn, $businessSql);

// Scam Reports
$reportSql = "SELECT r.*, u.FullName AS ReporterName, b.CompanyName
              FROM ScamReports r
              LEFT JOIN Users u ON r.UserID = u.UserID
              LEFT JOIN BusinessOwners b ON r.BusinessOwnerID = b.BusinessOwnerID
              ORDER BY r.ReportID DESC";
$reportStmt = sqlsrv_query($conn, $reportSql);

// Contact Messages
$contactSql = "SELECT * FROM ContactUs ORDER BY ContactUsID DESC";
$contactStmt = sqlsrv_query($conn, $contactSql);

// Audit Logs
$auditSql = "SELECT * FROM AuditLogs ORDER BY CreatedAt DESC";
$auditStmt = sqlsrv_query($conn, $auditSql);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
body { font-family: Arial; margin:0; background:#f4f6f8; }
h1 { color:#333; margin:0; padding:20px; }
nav { background:#007bff; padding:10px 20px; color:white; display:flex; align-items:center; justify-content:space-between; }
nav .nav-links { display:flex; gap:15px; }
nav .nav-links a { color:white; text-decoration:none; font-weight:bold; }
nav .nav-links a.active { text-decoration:underline; }
nav form button { background:#f44336; border:none; padding:6px 12px; color:white; border-radius:4px; cursor:pointer; }

/* Container sections */
.section { display:none; padding:20px; }
.section.active { display:block; }

/* Tables */
table { width:100%; border-collapse:collapse; margin-top:10px; font-size:13px; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#007bff; color:white; }
button { padding:5px 10px; border:none; border-radius:4px; cursor:pointer; }
.approve { background:#4CAF50; color:white; }
.reject { background:#f44336; color:white; }
.delete { background:#555; color:white; }

/* Fingerprint styling */
.fingerprint-wrapper { display:flex; flex-direction:column; align-items:center; gap:5px; min-width:120px; }
.view-fp { background:#17a2b8; color:white; padding:4px 8px; border-radius:5px; text-decoration:none; font-size:12px; }
.view-fp:hover { background:#138496; }
.no-fp { font-size:12px; color:#777; }
.fp-upload-form { display:flex; flex-direction:column; align-items:center; gap:3px; }
.fp-input { font-size:12px; }
.fp-upload-btn { background:#28a745; color:white; border:none; padding:4px 8px; border-radius:5px; cursor:pointer; font-size:12px; }
.fp-upload-btn:hover { background:#218838; }
</style>
<script>
function showSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('nav .nav-links a').forEach(a => a.classList.remove('active'));
    document.querySelector('nav .nav-links a[href="#'+id+'"]').classList.add('active');
}
</script>
</head>
<body>

<nav>
    <div><strong>Admin Dashboard</strong></div>
    <div class="nav-links">
        <a href="#business" onclick="showSection('business')" class="active">Business Registrations</a>
        <a href="#reports" onclick="showSection('reports')">Scam Reports</a>
        <a href="#contacts" onclick="showSection('contacts')">Contact Messages</a>
        <a href="#audit" onclick="showSection('audit')">Audit Logs</a>
    </div>
    <form method="POST">
        <button type="submit" name="logout">Logout</button>
    </form>
</nav>

<h1>Welcome, Admin</h1>

<!-- Business Registrations -->
<div class="section active" id="business">
<h2>Business Registrations</h2>
<table>
<tr>
<th>ID</th><th>Owner Name</th><th>Company Name</th><th>SSM Reg No</th><th>SSM Certificate</th>
<th>Business Type</th><th>Business Field</th><th>Store Type</th><th>Address</th><th>Email</th>
<th>Contact No</th><th>Facebook</th><th>Instagram</th><th>TikTok</th><th>Website</th><th>Fingerprint</th>
<th>Status</th><th>Created At</th><th>Action</th>
</tr>
<?php while($b = sqlsrv_fetch_array($businessStmt, SQLSRV_FETCH_ASSOC)) { ?>
<tr>
<td><?= $b['BusinessOwnerID'] ?></td>
<td><?= htmlspecialchars($b['OwnerName'] ?? '-') ?></td>
<td><?= htmlspecialchars($b['CompanyName']) ?></td>
<td><?= htmlspecialchars($b['SSMRegistrationNumber']) ?></td>
<td><?php if($b['SSMCertificate']): ?><a href="<?= $b['SSMCertificate'] ?>" target="_blank">View</a><?php else: ?>-<?php endif; ?></td>
<td><?= htmlspecialchars($b['BusinessType']) ?></td>
<td><?= htmlspecialchars($b['BusinessField']) ?></td>
<td><?= htmlspecialchars($b['StoreType'] ?? '-') ?></td>
<td><?= htmlspecialchars($b['BusinessAddress']) ?></td>
<td><?= htmlspecialchars($b['CompanyEmail']) ?></td>
<td><?= htmlspecialchars($b['BusinessContactNumber']) ?></td>
<td><?= htmlspecialchars($b['BusinessFacebook'] ?? '-') ?></td>
<td><?= htmlspecialchars($b['BusinessInstagram'] ?? '-') ?></td>
<td><?= htmlspecialchars($b['BusinessTikTok'] ?? '-') ?></td>
<td><?= htmlspecialchars($b['BusinessWebsite'] ?? '-') ?></td>
<td>
<div class="fingerprint-wrapper">
<?php if (!empty($b['Fingerprint'])): ?>
<a href="fingerprint/<?= $b['Fingerprint'] ?>" target="_blank" class="view-fp">View</a>
<?php else: ?><span class="no-fp">No File</span><?php endif; ?>
<form method="POST" enctype="multipart/form-data" class="fp-upload-form">
<input type="hidden" name="business_id" value="<?= $b['BusinessOwnerID'] ?>">
<input type="file" name="fingerprint" accept=".jpg,.jpeg,.png" class="fp-input">
<button type="submit" name="upload_fp" class="fp-upload-btn">Upload</button>
</form>
</div>
</td>
<td><?= $b['Status'] ?></td>
<td><?= $b['CreatedAt'] ? $b['CreatedAt']->format('Y-m-d H:i') : '-' ?></td>
<td>
<?php if($b['Status'] === 'Pending'): ?>
<form method="POST" style="display:inline;">
<input type="hidden" name="business_id" value="<?= $b['BusinessOwnerID'] ?>">
<button type="submit" name="action" value="approve" class="approve">Approve</button>
<button type="submit" name="action" value="reject" class="reject">Reject</button>
</form>
<?php else: ?> - <?php endif; ?>
</td>
</tr>
<?php } ?>
</table>
</div>

<!-- Scam Reports -->
<div class="section" id="reports">
<h2>Scam Reports</h2>
<table>
<tr>
<th>ReportID</th><th>Reporter</th><th>Company</th><th>Platform</th><th>Scam Date</th><th>Scam Type</th><th>Description</th><th>Amount Lost</th><th>Evidence</th><th>Status</th><th>Owner Response</th><th>Action</th>
</tr>
<?php while($r = sqlsrv_fetch_array($reportStmt, SQLSRV_FETCH_ASSOC)) { ?>
<tr>
<td><?= $r['ReportID'] ?></td>
<td><?= htmlspecialchars($r['ReporterName'] ?? '-') ?></td>
<td><?= htmlspecialchars($r['CompanyName'] ?? '-') ?></td>
<td><?= htmlspecialchars($r['Platform']) ?></td>
<td><?= $r['ScamDate'] ? $r['ScamDate']->format('Y-m-d') : '-' ?></td>
<td><?= htmlspecialchars($r['ScamType']) ?></td>
<td><?= htmlspecialchars($r['ScamDescription']) ?></td>
<td><?= htmlspecialchars($r['AmountLost']) ?></td>
<td><?php if(!empty($r['Evidence'])): ?><a href="<?= $r['Evidence'] ?>" target="_blank">View</a><?php else: ?>-<?php endif; ?></td>
<td><?= htmlspecialchars($r['Status'] ?? '-') ?></td>
<td><?= htmlspecialchars($r['OwnerResponse'] ?? '-') ?></td>
<td>
<form method="POST" style="display:flex; gap:5px;">
<button type="submit" name="approve_report" value="<?= $r['ReportID'] ?>" class="approve">Approve</button>
<button type="submit" name="delete_report" value="<?= $r['ReportID'] ?>" class="delete">Delete</button>
</form>
</td>
</tr>
<?php } ?>
</table>
</div>

<!-- Contact Messages -->
<div class="section" id="contacts">
<h2>Contact Messages</h2>
<table>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Action</th></tr>
<?php while($c = sqlsrv_fetch_array($contactStmt, SQLSRV_FETCH_ASSOC)) { ?>
<tr>
<td><?= $c['ContactUsID'] ?></td>
<td><?= htmlspecialchars($c['Name'] ?? '-') ?></td>
<td><?= htmlspecialchars($c['Email'] ?? '-') ?></td>
<td><?= htmlspecialchars($c['Message'] ?? '-') ?></td>
<td><form method="POST"><button name="delete_contact" value="<?= $c['ContactUsID'] ?>" class="delete">Done</button></form></td>
</tr>
<?php } ?>
</table>
</div>

<!-- Audit Logs -->
<div class="section" id="audit">
<h2>Audit Logs</h2>
<table>
<tr>
<th>Event Type</th><th>User Type</th><th>User Identifier</th><th>Status</th><th>Event Time</th></tr>
<?php
$auditSql = "SELECT * FROM AuditLogs ORDER BY EventTime DESC";
$auditStmt = sqlsrv_query($conn, $auditSql);
if ($auditStmt === false) {
    die("Error fetching audit logs: " . print_r(sqlsrv_errors(), true));
}

while($a = sqlsrv_fetch_array($auditStmt, SQLSRV_FETCH_ASSOC)) { ?>
<tr>
<td><?= htmlspecialchars($a['EventType']) ?></td>
<td><?= htmlspecialchars($a['UserType']) ?></td>
<td><?= htmlspecialchars($a['UserIdentifier']) ?></td>
<td><?= htmlspecialchars($a['Status']) ?></td>
<td>
<?php 
if ($a['EventTime'] instanceof DateTime) {
    echo $a['EventTime']->format('Y-m-d H:i');
} else {
    echo htmlspecialchars($a['EventTime'] ?? '-');
}
?>
</td>
</tr>
<?php } ?>
</table>
</div>

</body>
</html>