<?php
session_start();

// Database connection
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

// Get BusinessOwnerID
$businessID = isset($_GET['id']) ? intval($_GET['id']) : null;
$business = null;

// INSERT COMMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (isset($_SESSION['UserID']) && $businessID) {
        $userID = $_SESSION['UserID'];
        $commentText = trim($_POST['comment']);
        if (!empty($commentText)) {
            $sql = "
                INSERT INTO BusinessComments (BusinessOwnerID, UserID, CommentText, CreatedAt)
                VALUES (?, ?, ?, DATEADD(HOUR, 8, GETDATE()))
            ";
            $params = [$businessID, $userID, $commentText];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) die(print_r(sqlsrv_errors(), true));
        }
    }
}

// FETCH BUSINESS
if ($businessID) {
    $tsql = "
        SELECT CompanyName, BusinessContactNumber, CompanyEmail,
               BusinessAddress, BusinessType, BusinessField, BusinessLogo,
               BusinessFacebook, BusinessFacebookLink,
               BusinessInstagram, BusinessInstagramLink,
               BusinessTikTok, BusinessTikTokLink,
               BusinessWebsite, StoreType,
               Status
        FROM BusinessOwners
        WHERE BusinessOwnerID = ?
    ";
    $stmt = sqlsrv_query($conn, $tsql, [$businessID]);
    if ($stmt !== false) $business = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

// FETCH REPORT COUNTS
$approvedReportsCount = 0;
$pendingReportsCount = 0;
if ($businessID) {
    $sql = "
        SELECT Status, COUNT(*) AS ReportCount
        FROM ScamReports
        WHERE BusinessOwnerID = ?
        GROUP BY Status
    ";
    $stmt = sqlsrv_query($conn, $sql, [$businessID]);
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['Status'] === 'Approved') $approvedReportsCount = $row['ReportCount'];
            elseif ($row['Status'] === 'Pending') $pendingReportsCount = $row['ReportCount'];
        }
    }
}

// FETCH COMMENTS
$comments = [];
if ($businessID) {
    $sql = "
SELECT u.FullName, u.ProfilePicture, bc.CommentText, bc.CreatedAt, bc.CommentReply
FROM BusinessComments bc
JOIN Users u ON bc.UserID = u.UserID
WHERE bc.BusinessOwnerID = ?
ORDER BY bc.CreatedAt DESC
    ";
    $stmt = sqlsrv_query($conn, $sql, [$businessID]);
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $comments[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Business Profile | BizChain</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    font-family: 'Poppins', sans-serif;
    overflow: hidden;
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

.main-wrapper {
    display: flex;
    min-height: 100vh;
}

.profile-wrapper {
    position: fixed;           
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 800px;
    z-index: 10;               
}

.top-center {
    display: flex;
    flex-direction: column;
    align-items: center; 
    margin-bottom: 20px;
}

.profile-card {
    background: rgba(255,255,255,0.15);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    color: black;
    overflow-y: auto;         
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    align-items: center;     
    text-align: center; 
}

/* Company Logo */
.profile-card .profile-pic {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 20px;
}

/* Company Name */
.profile-card h2 {
    color: #007bff;
    margin-bottom: 10px;
    font-size: 28px;
}


.section {
 width: 100%;
    background-color: rgba(255,255,255,0.85);
    padding: 20px;             
    border-radius: 8px;
    margin-bottom: 20px;     
    text-align: left; 
}

.verified { 
    color: green; 
    font-weight: bold; 
    margin-top: 5px; 
}

.section img.profile-pic {
    margin: 0 auto 15px auto;
    display: block;
}

.section h2 {
    margin-bottom: 10px;
}

.section p {
    margin: 8px 0;             
    line-height: 1.5;        
}


.verified, .scam-flag {
    display: block;
    margin: 10px auto;
    font-weight: bold;
}

.scam-flag { 
    color: red; 
    font-weight: bold; 
    margin: 10px 0; 
}

textarea { 
    width:100%; 
    padding:10px; 
    border-radius:6px; 
    border:1px solid #ccc; 
    font-size:14px; 
    resize: vertical; 
    min-height:80px; 
}

button { 
    margin-top:10px; 
    width:100%; 
    padding:12px; 
    border-radius:6px; 
    border:none; 
    font-size:14px; 
    cursor:pointer; 
    font-weight:bold; }

.submit-btn { 
    background-color: rgba(255,182,193,0.8); 
    color: black; 
}

.submit-btn:hover { 
    background-color: rgba(0,123,255,1); 
    color: white; 
}

.report-btn { 
    background-color: rgba(255,99,71,0.8); 
    color: black; 
}

.report-btn:hover { 
    background-color: rgba(255,0,0,1); 
    color:white; 
}

/* Comments Section Header */
.comments-section h3 { 
    font-size: 18px; 
    font-weight: 600; 
    margin-bottom: 12px; 
}

/* Each comment container */
.comment {
    display: flex;
    align-items: flex-start;
    background-color: rgba(255, 182, 193, 0.3); 
    padding: 8px 10px;
    border-radius: 12px;
    margin-bottom: 10px;
    gap: 8px; 
}

/* User profile picture */
.comment img {
    width: 35px; 
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

/* Comment text container */
.comment div {
    flex: 1;
    margin: 0; 
}

/* Commenter's name */
.comment strong {
    font-weight: 600;
    display: inline-block;
    margin-bottom: 2px; 
}

/* Timestamp */
.comment small {
    font-size: 12px;
    color: #555;
    margin-top: 2px; 
    display: block;
}

/* Reply from business owner */
.comment-reply {
    background-color: rgba(0, 123, 255, 0.2); 
    padding: 6px 10px; 
    border-radius: 10px;
    margin-top: 6px;
    font-size: 14px;
}
</style>
</head>
<body>

<img id="bg-image" src="SBackground.jpeg" alt="Background">

<div class="main-wrapper">
    <?php include 'Sidebar.php'; ?>

    <div class="profile-wrapper">
        <div class="profile-card">
        <?php if ($business): ?>
            <!-- Company Info -->
             <div class="top-center">
                <img src="<?= htmlspecialchars($business['BusinessLogo'] ?? 'profile picture.png'); ?>" alt="Logo" class="profile-pic">
                <h2><?= htmlspecialchars($business['CompanyName']); ?></h2>

                <?php if (!empty($business['Status']) && $business['Status'] === 'Approved'): ?>
                    <p class="verified">✔ Verified Business</p>
                <?php endif; ?>

                <?php if ($pendingReportsCount > 0): ?>
                    <div class="scam-flag">⚠️ Reported by <?= $pendingReportsCount ?> User<?= $pendingReportsCount > 1 ? 's' : '' ?> — Under Review</div>
                <?php elseif ($approvedReportsCount > 0): ?>
                    <div class="scam-flag">⚠️ Reported by <?= $approvedReportsCount ?> User<?= $approvedReportsCount > 1 ? 's' : '' ?></div>
                <?php endif; ?> </div>

            <div class="section">
                <p><strong>Business Type:</strong> <?= htmlspecialchars($business['BusinessType']); ?></p>
                <p><strong>Business Field:</strong> <?= htmlspecialchars($business['BusinessField']); ?></p>
                <p><strong>Business Email Address:</strong> <?= htmlspecialchars($business['CompanyEmail']); ?></p>
                <p><strong>Phone Number:</strong> <?= htmlspecialchars($business['BusinessContactNumber']); ?></p>
                <p><strong>Store Type:</strong> <?= htmlspecialchars($business['StoreType']); ?></p>
                <p><strong>Business Address:</strong> <?= htmlspecialchars($business['BusinessAddress']); ?></p>
                
            </div>

            <!-- Social Media -->
            <div class="section social-media">
                <h4>SOCIAL MEDIA</h4>
                <?php if (!empty($business['BusinessFacebook'])): ?>
                    <p><strong>Facebook:</strong> <a href="<?= htmlspecialchars($business['BusinessFacebookLink'] ?? $business['BusinessFacebook']); ?>" target="_blank"><?= htmlspecialchars($business['BusinessFacebook']); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($business['BusinessInstagram'])): ?>
                    <p><strong>Instagram:</strong> <a href="<?= htmlspecialchars($business['BusinessInstagramLink'] ?? $business['BusinessInstagram']); ?>" target="_blank"><?= htmlspecialchars($business['BusinessInstagram']); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($business['BusinessTikTok'])): ?>
                    <p><strong>TikTok:</strong> <a href="<?= htmlspecialchars($business['BusinessTikTokLink'] ?? $business['BusinessTikTok']); ?>" target="_blank"><?= htmlspecialchars($business['BusinessTikTok']); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($business['BusinessWebsite'])): ?>
                    <p><strong>Website:</strong> <a href="<?= htmlspecialchars($business['BusinessWebsite']); ?>" target="_blank"><?= htmlspecialchars($business['BusinessWebsite']); ?></a></p>
                <?php endif; ?>
            </div>

            <!-- Comments -->
            <div class="section comments-section">
                <h3>Comments</h3>
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $c): ?>
                        <div class="comment" style="display:flex; align-items:flex-start;">
                            <!-- User profile picture -->
                            <img src="<?= htmlspecialchars($c['ProfilePicture'] ?? 'Profile Picture.png'); ?>" 
                                alt="User Pic" 
                                style="width:40px; height:40px; border-radius:50%; object-fit:cover; margin-right:10px;">
                            
                            <!-- Comment text -->
                            <div>
                                <strong><?= htmlspecialchars($c['FullName']); ?></strong><br>
                                <?= htmlspecialchars($c['CommentText']); ?><br>
                                <small><?= $c['CreatedAt']->format('Y-m-d H:i'); ?></small>
                                
                                <?php if (!empty($c['CommentReply'])): ?>
                                    <div class="comment-reply"><strong>Owner Reply:</strong> <?= htmlspecialchars($c['CommentReply']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No comments yet.</p>
                <?php endif; ?>
            </div>

            <!-- Add Comment -->
            <div class="section comments-section">
                <h3>Leave a Comment</h3>
                <?php if (isset($_SESSION['UserID'])): ?>
                    <form method="POST">
                        <textarea name="comment" placeholder="Write your comment here..." required></textarea>
                        <button type="submit" class="submit-btn">Submit Comment</button>
                    </form>
                <?php else: ?>
                    <p style="color:red;">Please login to comment.</p>
                <?php endif; ?>
            </div>

            <!-- Report Button -->
            <div class="section">
                <?php if (isset($_SESSION['UserID'])): ?>
                    <a href="report_business.php?businessID=<?= $businessID ?>"><button class="report-btn">Report Business</button></a>
                <?php else: ?>
                    <button class="report-btn" onclick="alert('Please login first.')">Report Business</button>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <h2>Business not found</h2>
            <p>The business ID provided does not exist.</p>
        <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>