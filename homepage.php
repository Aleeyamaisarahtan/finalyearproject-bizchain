<?php
session_start();

$userID = isset($_SESSION['UserID']) ? $_SESSION['UserID'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BizChain Menu</title>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
    }

    .navbar {
        background-color: #007bff;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        color: white;
    }


    .navbar img {
        height: 45px;
        margin-right: 15px;
         border-radius: 50%;
        object-fit: cover;
    }

    .navbar ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 25px;
        justify-content: center;
        flex: 2;
    }

    .navbar ul li a {
        color: white;
        text-decoration: none;
        font-size: 16px;
        font-weight: 500;
        transition: 0.3s;
    }

    .navbar ul li a:hover {
        text-decoration: underline;
    }

    @media (max-width: 600px) {
        .navbar {
            flex-direction: column;
            text-align: center;
        }

        .navbar h2 {
            flex: unset;
            margin-bottom: 10px;
        }

        .navbar ul {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>
</head>

<body>

<!-- NAVIGATION BAR -->
<div class="navbar">
    <ul>
        <li><a href="#">Home</a></li>
        <li><a href="#">Verify</a></li>
        <li><a href="#">Report</a></li>
        <li><a href="#">About</a></li>
        <li><a href=business_registration.html>Register</a></li>
        <li><a href="#">Contact Us</a></li>
    </ul>
    <img src="profile picture.png" alt="BizChain Logo"> 
</div>


<div style="padding: 40px;">
    <h1>Welcome to BizChain</h1>
    <p>Your trusted platform for verifying businesses.</p>

    <?php if ($userID): ?>
        <p><strong>User ID:</strong> <?php echo htmlspecialchars($userID); ?></p>
    <?php else: ?>
        <p>You are not logged in.</p>
    <?php endif; ?>
</div>

</body>
</html>