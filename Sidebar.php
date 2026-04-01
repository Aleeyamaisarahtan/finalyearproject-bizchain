<?php
$userID = $_SESSION['UserID'] ?? null;

$fullname = "Guest";
$username = "guest";
$profilePicture = "profile picture.png";

if ($userID) {
    // Database connection
    $serverName = "localhost,1433";
    $connectionOptions = [
        "Database" => "BizChainDB",
        "Uid" => "SA",
        "PWD" => "Bizchain123!",
        "Encrypt" => false
    ];
    $conn = sqlsrv_connect($serverName, $connectionOptions);

    
    if ($conn) {
        $query = "SELECT FullName, Username, ProfilePicture FROM Users WHERE UserID = ?";
        $params = [$userID];
        $stmt = sqlsrv_query($conn, $query, $params);

        if ($stmt) {
            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($user) {
    $fullname = $user['FullName'];
    $username = $user['Username'];

    if (!empty($user['ProfilePicture'])) {
        $profilePicture = $user['ProfilePicture'];
    }
}
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <title>BizChain | Sidebar</title>

<style>
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

.container { 
    width: 100%; 
    min-height: 100vh; 
}

.sidebar {
    position: relative;
    width: 256px;
    height: 100vh;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background-color: #fff;
    padding: 24px;
    border-radius: 30px;
    transition: all 0.3s;
}

.sidebar .head {
    display: flex;
    gap: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f6f6f6;
}

.user-img {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
}

.user-img img { 
    width: 100%; 
    object-fit: cover; 
}

.user-detail .Fullname, .menu .Fullname {
    font-size: 14px;
    font-weight: 600;
    color: black;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.user-detail .username { 
    font-size: 14px; 
    color: black;
    font-weight: 400; }

.nav { 
    flex: 1; 
    display: flex; 
    flex-direction: column; 
}

.menu ul li { 
    list-style: none; 
    margin-bottom: 1px; 
}

.menu ul li a {
    display: flex; 
    align-items: center; 
    gap: 10px;
    font-size: 14px; 
    font-weight: 500; 
    color: #757575;
    text-decoration: none; 
    padding: 12px 8px;
    border-radius: 8px; 
    transition: all 0.3s;
}

.menu ul li > a:hover, .menu ul li.active > a { 
    color: #000; 
    background-color: #f6f6f6; 
}

.menu ul li .icon { 
    font-size: 20px; 
}

.menu ul li .text { 
    flex: unset; 
}

.menu:not(:last-child) { 
    padding-bottom: 10px; 
    margin-bottom: 20px; 
    border-bottom: 2px solid #f6f6f6; 
}

.menu:last-child { 
    margin-top: auto; 
}

.menu-btn {
    position: absolute; 
    right: -14px; 
    top: 3.5%;
    width: 28px; 
    height: 28px;
    border-radius: 8px; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    cursor: pointer; 
    color: #757575; 
    border: 2px solid #f6f6f6; 
    background-color: #fff;
}

.menu-btn:hover i { 
    color: #000; 
}

.menu-btn i { 
    transition: all 0.3s; 
}

.sidebar.active { 
    width: 92px; 
}

.sidebar.active .menu-btn i { 
    transform: rotate(180deg); 
}

.sidebar.active .user-detail {
     display: none; 
}

.sidebar.active .menu .Fullname { 
    display: flex; 
    justify-content: center; 
    align-items: center; 
}

.sidebar.active .menu > ul > li > a {
    position: relative; 
    display: flex; 
    align-items: center; 
    justify-content: center;
}

.sidebar.active .menu > ul > li > a .text {
    position: absolute; 
    left: 70px; 
    top: 50%;
    transform: translateY(-50%);
    padding: 10px; 
    border-radius: 4px;
    color: #fff; 
    background-color: #000;
    opacity: 0; 
    visibility: hidden; 
    transition: all 0.3s;
}

.sidebar.active .menu > ul > li > a:hover .text {
    left: 50px; 
    opacity: 1; 
    visibility: visible;
}

.sidebar, 
.sidebar .user-detail, 
.sidebar .menu ul li a {
    font-family: 'Poppins', sans-serif;
}

</style>
</head>
<body>

<div class="container">
    <div class="sidebar active">
        <div class="menu-btn">
            <i class="bi bi-caret-left-fill"></i>
        </div>
        <div class="head">
            <div class="user-img">
                <img src="<?= htmlspecialchars($profilePicture, ENT_QUOTES); ?>" alt="Profile Picture">
            </div>
            <div class="user-detail">
        <div class="Fullname">
            <?php 
            $words = explode(' ', $fullname);
            echo htmlspecialchars(implode(' ', array_slice($words, 0, 2)));
            ?>
        </div>

        <div class="username"><?php echo htmlspecialchars($username); ?></div>
            </div>
        </div>

        <div class="nav">
            <!-- Main Menu -->
            <div class="menu">
                <div class="Fullname">Menu</div>
                <ul>
                    <li><a href="homepage.php"><i class="icon bi bi-house"></i><span class="text">Home</span></a></li>
                    <li><a href="verify_business.php"><i class="icon bi bi-search"></i><span class="text">Search</span></a></li>
                    <li><a href="Scammer.php"><i class="icon bi bi-exclamation-triangle"></i><span class="text">Beware of Scammer</span></a></li>
                </ul>
            </div>

            <div class="menu">
    <div class="Fullname">Business</div>
    <ul>
        <li>
            <a href="business_registration.php">
                <i class="icon bi bi-plus-circle"></i>
                <span class="text">Register Business</span>
            </a>
        </li>

        <li>
            <a href="profile_management.php">
                <i class="icon bi bi-gear-fill"></i>
                <span class="text">Profile Management</span>
            </a>
        </li>

        <li>
            <a href="business_dashboard.php">
                <i class="icon bi bi-exclamation-triangle"></i>
                <span class="text">Business Dashboard</span>
            </a>
        </li>
    </ul>
</div>

            <!-- Account Menu -->
            <div class="menu">
                <div class="Fullname">Account</div>
                <ul>
                    <li><a href="contact_us.php"><i class="icon bi bi-info-circle"></i><span class="text">Help</span></a></li>
                    <li><a href="login.html"><i class="icon bi bi-box-arrow-left"></i><span class="text">Log Out</span></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    // Sidebar toggle
    $(".menu-btn").click(function(){
        $(".sidebar").toggleClass("active");
    });

    // Menu expand/collapse
    $(".menu > ul > li").click(function(e){
        $(this).siblings().removeClass("active");
        $(this).toggleClass("active");
        $(this).find("ul").slideToggle();
    });
</script>

</body>
</html>