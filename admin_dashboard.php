<?php
session_start();

// --- Direct logout handling ---
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
}

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
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
    }
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

// --- Business Owners ---
$businessSql = "SELECT b.*, u.FullName AS OwnerName 
                FROM BusinessOwners b
                LEFT JOIN Users u ON b.UserID = u.UserID
                ORDER BY b.BusinessOwnerID DESC";
$businessStmt = sqlsrv_query($conn, $businessSql);

// --- Scam Reports ---
$reportSql = "SELECT r.*, u.FullName AS ReporterName, b.CompanyName
              FROM ScamReports r
              LEFT JOIN Users u ON r.UserID = u.UserID
              LEFT JOIN BusinessOwners b ON r.BusinessOwnerID = b.BusinessOwnerID
              ORDER BY r.ReportID DESC";
$reportStmt = sqlsrv_query($conn, $reportSql);

// --- Contact Us ---
$contactSql = "SELECT * FROM ContactUs ORDER BY ContactUsID DESC";
$contactStmt = sqlsrv_query($conn, $contactSql);

?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<style>
body { font-family: Arial; background:#f4f6f8; margin:20px; }
h1 { color:#333; }
h2 { margin-top:40px; color:#007bff; }

table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { border:1px solid #ccc; padding:8px; font-size:13px; text-align:center; }
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
</head>
<body>
<div style="text-align:right; margin-bottom:10px;">
    <form method="POST" style="display:inline;">
        <button type="submit" name="logout" style="padding:6px 12px; background:#f44336; color:white; border:none; border-radius:4px; cursor:pointer;">Logout</button>
    </form>
</div>
<h1>Admin Dashboard</h1>

<!-- ===================== Business Registrations ===================== -->
<h2>Business Registrations</h2>
<table>
<tr>
<th>ID</th><th>Owner Name</th><th>Company Name</th><th>SSM Reg No</th>
<th>SSM Certificate</th><th>Business Type</th><th>Business Field</th><th>Store Type</th>
<th>Address</th><th>Email</th><th>Contact No</th><th>Facebook</th>
<th>Instagram</th><th>TikTok</th><th>Website</th><th>Fingerprint</th>
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

<!-- ===================== Scam Reports ===================== -->
<h2>Scam Reports</h2>
<table>
<tr>
<?php
$firstReport = sqlsrv_fetch_array($reportStmt, SQLSRV_FETCH_ASSOC);
if ($firstReport) {
    // Table headers
    foreach(array_keys($firstReport) as $col) echo "<th>".htmlspecialchars($col)."</th>";
    echo "<th>Owner Response</th><th>Action</th></tr>";

    do {
        echo "<tr>";
        foreach ($firstReport as $key => $value) {
            if ($value instanceof DateTime) $value = $value->format('Y-m-d H:i');

            // Show names instead of IDs
            if ($key === 'UserID') $value = $firstReport['ReporterName'] ?? '-';
            if ($key === 'BusinessOwnerID') $value = $firstReport['CompanyName'] ?? '-';

            // Make Evidence a clickable link
            if ($key === 'Evidence' && !empty($value)) {
                echo "<td><a href='".htmlspecialchars($value)."' target='_blank'>View</a></td>";
            } else {
                echo "<td>".htmlspecialchars($value ?? '-')."</td>";
            }
        }

        // Show Owner Response
        $ownerResponse = !empty($firstReport['OwnerResponse']) ? $firstReport['OwnerResponse'] : '-';
        echo "<td>".htmlspecialchars($ownerResponse)."</td>";

        // Action buttons
        echo "<td>
            <form method='POST' style='display:flex; gap:5px;'>
                <button type='submit' name='approve_report' value='".$firstReport['ReportID']."' class='approve'>Approve</button>
                <button type='submit' name='delete_report' value='".$firstReport['ReportID']."' class='delete'>Delete</button>
            </form>
        </td>";

        echo "</tr>";
    } while($firstReport = sqlsrv_fetch_array($reportStmt, SQLSRV_FETCH_ASSOC));
} else {
    echo "<tr><td colspan='100%'>No reports found.</td></tr>";
}
?>
</table>
</table>

<!-- ===================== Contact Messages ===================== -->
<h2>Contact Messages</h2>
<table>
<tr>
<th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Action</th>
</tr>
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

</body>
</html>