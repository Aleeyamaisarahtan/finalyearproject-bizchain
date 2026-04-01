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
    header("Location: login.html");
    exit;
}

$userID = $_SESSION['UserID'];

$sql = "SELECT * FROM BusinessOwners WHERE UserID=?";
$stmt = sqlsrv_query($conn, $sql, [$userID]);
$business = ($stmt !== false) ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$business) {
    die("No business registered yet. <a href='business_registration.php'>Register Now</a>");
}

$businessOwnerID = $business['BusinessOwnerID'] ?? 0;

// --- Handle comment reply submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content'], $_POST['comment_id'])) {
    $commentID = intval($_POST['comment_id']);
    $replyContent = trim($_POST['reply_content']);

    if ($commentID && !empty($replyContent)) {
        $sqlUpdate = "UPDATE BusinessComments SET CommentReply=? WHERE CommentID=?";
        $paramsUpdate = [$replyContent, $commentID];
        $stmtUpdate = sqlsrv_query($conn, $sqlUpdate, $paramsUpdate);

        if ($stmtUpdate === false) {
            echo "<pre>"; print_r(sqlsrv_errors()); echo "</pre>";
            die("Error saving reply.");
        } else {
            sqlsrv_free_stmt($stmtUpdate);
            header("Location: business_dashboard.php"); 
            exit;
        }
    }
}

// --- Handle scam report response submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['response_content'], $_POST['report_id'])) {
    $reportID = intval($_POST['report_id']);
    $responseContent = trim($_POST['response_content']);

    if ($reportID && !empty($responseContent)) {
        $sqlUpdateReport = "UPDATE ScamReports SET OwnerResponse=? WHERE ReportID=?";
        $paramsUpdateReport = [$responseContent, $reportID];
        $stmtUpdateReport = sqlsrv_query($conn, $sqlUpdateReport, $paramsUpdateReport);

        if ($stmtUpdateReport === false) {
            echo "<pre>"; print_r(sqlsrv_errors()); echo "</pre>";
            die("Error saving response.");
        } else {
            sqlsrv_free_stmt($stmtUpdateReport);
            header("Location: business_dashboard.php"); 
            exit;
        }
    }
}

$sqlReports = "SELECT * FROM ScamReports WHERE BusinessOwnerID=? ORDER BY ScamDate DESC";
$stmtReports = sqlsrv_query($conn, $sqlReports, [$businessOwnerID]);
$reports = [];
if ($stmtReports !== false) {
    while ($row = sqlsrv_fetch_array($stmtReports, SQLSRV_FETCH_ASSOC)) {
        $reports[] = $row;
    }
}

$sqlComments = "SELECT * FROM BusinessComments WHERE BusinessOwnerID=? ORDER BY CommentID DESC";
$stmtComments = sqlsrv_query($conn, $sqlComments, [$businessOwnerID]);
$comments = [];
if ($stmtComments !== false) {
    while ($row = sqlsrv_fetch_array($stmtComments, SQLSRV_FETCH_ASSOC)) {
        // Fetch the user's name separately
        $fullName = 'Unknown User';
        if (!empty($row['UserID'])) {
            $sqlUser = "SELECT FullName FROM Users WHERE UserID=?";
            $stmtUser = sqlsrv_query($conn, $sqlUser, [$row['UserID']]);
            if ($stmtUser !== false) {
                $user = sqlsrv_fetch_array($stmtUser, SQLSRV_FETCH_ASSOC);
                if ($user) $fullName = $user['FullName'];
            }
        }
        $row['FullName'] = $fullName;
        $comments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<title>BizChain | Business Dashboard</title>
<style>
body { font-family: 'Poppins', sans-serif; min-height: 100vh; margin: 0; }
.main-wrapper { display: flex; min-height: 100vh; }
#bg-image { position: fixed; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:-1; }
.main-content { position: fixed; top:50%; left:50%; transform: translate(-50%, -50%); width:90%; max-width:800px; z-index:10; }
.register-container { width: 900px; background: rgba(255,255,255,0.15); padding: 30px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); backdrop-filter: blur(10px); color: black; overflow-y: auto; max-height: 95vh; display: flex; flex-direction: column; align-items: center; text-align: center; }

.register-container input, .register-container select, .register-container textarea { width:100%; padding:12px; border-radius:20px; border:2px solid rgba(255,255,255,0.3); background:transparent; color:black; font-size:14px; margin-bottom:12px; outline:none; }
.register-container input::placeholder, .register-container select option { color:black; }
.register-container textarea { min-height:100px; resize:vertical; }
.register-container button { width:100%; padding:14px; background-color:#007bff; color:#fff; border:none; border-radius:40px; font-size:16px; cursor:pointer; margin-top:10px; }
.register-container button:hover { background-color:#0056b3; }

.register-container label { width: 35%; text-align: left; margin-right: 15px; font-weight: 500; }
h2 { color:#007bff; margin-bottom:20px; text-align:left; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { text-align:left; padding:12px; border-bottom:1px solid #ddd; background:#f1f1f1; }
a { color:#007bff; text-decoration:underline; }

.section { 
    margin-top:30px; 
    width:100%; 
    background-color: rgba(255,255,255,0.85); 
    padding:20px; 
    border-radius:8px; 
    text-align:left; 
}
.comment { display:flex; align-items:flex-start; background-color: rgba(255, 182, 193, 0.25); padding:12px; border-radius:12px; margin-bottom:12px; gap:12px; }
.comment img { width:40px; height:40px; border-radius:50%; object-fit:cover; }
.comment-reply { width:100%; display:block; background-color: rgba(0,123,255,0.15); padding:12px 16px; border-radius:10px; margin-top:10px; border-left:5px solid #007bff; box-sizing:border-box; }
.comment > div { flex:1; }

textarea { width:100%; padding:8px; border-radius:12px; margin-top:5px; border:2px solid rgba(255,255,255,0.3); background:transparent; color:black; }
textarea::placeholder { color:black; }
button { margin-top:5px; padding:8px 12px; border-radius:12px; border:none; cursor:pointer; background:#007bff; color:white; }
button:hover { background:#0056b3; }

.register-container h2 { text-align:left; width:100%; }
</style>
</head>
<body>
<img id="bg-image" src="SBackground.jpeg" alt="Background">
<div class="mainwrapper">
<?php include 'sidebar.php'; ?>
</div>

<div class="main-content">
<div class="register-container">
<h1>BUSINESS DASHBOARD</h1>

<!-- Business Details Table -->
<table>
<tr><th>Business Name</th><td><?= htmlspecialchars($business['CompanyName'], ENT_QUOTES) ?></td></tr>
<tr>
<th>Business Logo</th>
<td>
<?php if(!empty($business['BusinessLogo'])): ?>
    <img src="<?= htmlspecialchars($business['BusinessLogo'], ENT_QUOTES) ?>" alt="Business Logo" style="max-width:120px; max-height:120px; border-radius:8px;">
<?php else: ?>
    <img src="profile picture.png" alt="Default Logo" style="max-width:120px; max-height:120px;">
<?php endif; ?>
</td>
</tr>
<tr><th>Business Type</th><td><?= htmlspecialchars($business['BusinessType'], ENT_QUOTES) ?></td></tr>
<tr><th>Business Field</th><td><?= htmlspecialchars($business['BusinessField'], ENT_QUOTES) ?></td></tr>
<tr><th>Store Type</th><td><?= htmlspecialchars($business['StoreType'], ENT_QUOTES) ?></td></tr>
<tr><th>Business Address</th><td><?= htmlspecialchars($business['BusinessAddress'], ENT_QUOTES) ?></td></tr>
<tr><th>Company Email</th><td><?= htmlspecialchars($business['CompanyEmail'], ENT_QUOTES) ?></td></tr>
<tr><th>Business Contact Number</th><td><?= htmlspecialchars($business['BusinessContactNumber'], ENT_QUOTES) ?></td></tr>
<tr><th>Facebook</th><td><?= htmlspecialchars($business['BusinessFacebook'], ENT_QUOTES) ?> | <a href="<?= htmlspecialchars($business['BusinessFacebookLink'], ENT_QUOTES) ?>" target="_blank">Link</a></td></tr>
<tr><th>Instagram</th><td><?= htmlspecialchars($business['BusinessInstagram'], ENT_QUOTES) ?> | <a href="<?= htmlspecialchars($business['BusinessInstagramLink'], ENT_QUOTES) ?>" target="_blank">Link</a></td></tr>
<tr><th>TikTok</th><td><?= htmlspecialchars($business['BusinessTikTok'], ENT_QUOTES) ?> | <a href="<?= htmlspecialchars($business['BusinessTikTokLink'], ENT_QUOTES) ?>" target="_blank">Link</a></td></tr>
<tr><th>Website</th><td><a href="<?= htmlspecialchars($business['BusinessWebsite'], ENT_QUOTES) ?>" target="_blank"><?= htmlspecialchars($business['BusinessWebsite'], ENT_QUOTES) ?></a></td></tr>
<tr><th>Status</th><td><?= htmlspecialchars($business['Status'], ENT_QUOTES) ?></td></tr>
<tr><th>Created At</th><td><?= $business['CreatedAt'] ? $business['CreatedAt']->format('Y-m-d H:i') : '-' ?></td></tr>
</table>

<a href="profile_management.php"><button>Edit Profile</button></a>

<!-- Scam Reports Section -->
<div class="section">
<h3>Scam Reports</h3>
<?php if(empty($reports)): ?>
    <p>No reports yet.</p>
<?php else: ?>
    <?php foreach($reports as $r): ?>
        <div class="comment">
            <div>
                <strong>Scam Type:</strong> <?= htmlspecialchars($r['ScamType'], ENT_QUOTES) ?><br>
                <strong>Description:</strong> <?= htmlspecialchars($r['ScamDescription'], ENT_QUOTES) ?><br>
                <strong>Date:</strong> <?= $r['ScamDate'] ? $r['ScamDate']->format('Y-m-d') : '-' ?><br>
                <?php if(!empty($r['AmountLost'])): ?>
                    <strong>Amount Lost:</strong> <?= htmlspecialchars($r['AmountLost'], ENT_QUOTES) ?><br>
                <?php endif; ?>
                <?php if(!empty($r['Platform'])): ?>
                    <strong>Platform:</strong> <?= htmlspecialchars($r['Platform'], ENT_QUOTES) ?><br>
                <?php endif; ?>
                <?php if(!empty($r['Evidence'])): ?>
                    <div class="comment-reply">
                        <strong>Evidence:</strong><br>
                        <a href="<?= htmlspecialchars($r['Evidence'], ENT_QUOTES) ?>" target="_blank">View Evidence</a>
                    </div>
                <?php endif; ?>

                <?php if(!empty($r['OwnerResponse'])): ?>
                    <div class="comment-reply" style="background-color: rgba(0,200,0,0.15); border-left:5px solid green;">
                        <strong>Your Response:</strong> <?= htmlspecialchars($r['OwnerResponse'], ENT_QUOTES) ?>
                    </div>
                <?php else: ?>
                    <form method="POST" style="margin-top:10px;">
                        <input type="hidden" name="report_id" value="<?= $r['ReportID'] ?>">
                        <textarea name="response_content" placeholder="Clarify or respond to this report..." required></textarea>
                        <button type="submit">Submit Response</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Comments Section -->
<div class="section comments-section">
<h3>Business Comments</h3>
<?php if(empty($comments)): ?>
    <p>No comments yet.</p>
<?php else: ?>
    <?php foreach($comments as $c): ?>
        <div class="comment">
            <img src="<?= htmlspecialchars($c['ProfilePicture'] ?? 'Profile Picture.png'); ?>" alt="User">
            <div>
                <strong><?= htmlspecialchars($c['FullName'], ENT_QUOTES) ?></strong><br>
                <?= htmlspecialchars($c['CommentText'], ENT_QUOTES) ?>
                <?php if(!empty($c['CommentReply'])): ?>
                    <div class="comment-reply">
                        <strong>Your Reply:</strong> <?= htmlspecialchars($c['CommentReply'], ENT_QUOTES) ?>
                    </div>
                <?php else: ?>
                    <form method="POST" style="margin-top:8px;">
                        <input type="hidden" name="comment_id" value="<?= $c['CommentID'] ?>">
                        <textarea name="reply_content" placeholder="Write your reply..." required></textarea>
                        <button type="submit">Reply</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

</div>
</div>
</body>
</html>