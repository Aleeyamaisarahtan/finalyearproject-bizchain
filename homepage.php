<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BizChain | Homepage</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    font-family: 'Poppins', sans-serif;
}

#bg-video {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -1;      
}

body {
    display: flex;
    min-height: 100vh;
    color: white;
}

.overlay-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    text-align: center;
    pointer-events: none;
}

.overlay-content h1 {
    font-size: 60px;
    color: white;
    text-shadow:
        2px 2px 0 #000,
        -2px 2px 0 #000,
        2px -2px 0 #000,
        -2px -2px 0 #000,
        2px 0 0 #000,
        -2px 0 0 #000,
        0 2px 0 #000,
        0 -2px 0 #000; 
}

.overlay-content p {
    font-size: 23px;
    padding-top: 10px;
    color: white;
    text-shadow:
        1px 1px 0 #000,
        -1px 1px 0 #000,
        1px -1px 0 #000,
        -1px -1px 0 #000,
        1px 0 0 #000,
        -1px 0 0 #000,
        0 1px 0 #000,
        0 -1px 0 #000;
}
</style>
</head>
<body>

<!-- Video Background -->
<video id="bg-video" autoplay muted loop>
    <source src="VideoBackground.mp4" type="video/mp4">
   
</video>

<!-- Sidebar -->
<?php include 'Sidebar.php'; ?>

<!-- Main Content -->
<div class="overlay-content">
    <h1>WELCOME TO BIZCHAIN</h1>
    <strong><p>STAY ALERT. BEWARE OF SCAMMERS AND VERIFY BUSINESES HERE.</p></strong>
</div>

</body>
</html>