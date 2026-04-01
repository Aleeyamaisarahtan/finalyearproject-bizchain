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

// --- Handle form submission ---
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $enquiryType = trim($_POST['enquiry_type'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $enquiryType && $message) {
        $sql = "INSERT INTO ContactUs (Name, Email, EnquiryType, Message) VALUES (?, ?, ?, ?)";
        $params = [$name, $email, $enquiryType, $message];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            $error = "Error saving message. Please try again.";
        } else {
            $success = true;
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<title>Contact Us | BizChain</title>

<style>
/* --- Your existing styles --- */
.main-wrapper { display: flex; min-height: 100vh; }
.main-content { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px; z-index: 10; }
.register-container { width: 700px; background: rgba(255,255,255,0.15); padding: 30px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3); backdrop-filter: blur(10px); color: black; overflow-y: auto; max-height: 95vh; display: flex; flex-direction: column; align-items: center; text-align: center; }
body { font-family: 'Poppins', sans-serif; min-height: 100vh; margin: 0; }
#bg-image { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
.register-container input, .register-container select, .register-container textarea { width:100%; padding:12px; border-radius:20px; border:2px solid rgba(255,255,255,0.3); background:transparent; color:black; font-size:14px; margin-bottom:12px; outline:none; }
.register-container input::placeholder, .register-container select option { color:black; }
.register-container textarea { min-height:100px; resize:vertical; }
.register-container button { width:100%; padding:14px; background-color:#007bff; color:#fff; border:none; border-radius:40px; font-size:16px; cursor:pointer; margin-top:10px; }
.register-container button:hover { background-color:#0056b3; }
.note { font-size:12px; color:#ccc; margin-top:15px; text-align:center; }
.register-container label { width: 35%; text-align: left; margin-right: 15px; font-weight: 500; }
.success { color: green; margin-bottom: 10px; }
.error { color: red; margin-bottom: 10px; }
</style>
</head>

<body>

<img id="bg-image" src="SBackground.jpeg" alt="Background">
<div class="mainwrapper">
<?php include 'sidebar.php'; ?> 
</div>

<div class="main-content">
    <div class="register-container">

        <h1>CONTACT US</h1>
        <p>If you have any questions, feedback, or require assistance regarding business verification, reporting, or platform usage, please feel free to contact us.</p>

        <?php if($success): ?>
            <p class="success">Your message has been sent successfully!</p>
        <?php elseif($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="" method="post">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Enter your full name" required>

            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email address" required>

            <label>Enquiry Type</label>
            <select name="enquiry_type" required>
                <option value="">-- Select Enquiry Type --</option>
                <option value="General Enquiry">General Enquiry</option>
                <option value="Business Verification">Business Verification</option>
                <option value="Report a Scam">Report a Scam</option>
                <option value="Technical Support">Technical Support</option>
                <option value="Other">Other</option>
            </select>

            <label>Message</label>
            <textarea name="message" placeholder="Write your message here..." required></textarea>

            <button type="submit">Submit Message</button>
        </form>

    </div>
</div>

</body>
</html>