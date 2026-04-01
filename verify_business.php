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

// Initialize results
$results = [];

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search = trim($_GET['q']);

$search = trim($_GET['q']);
$searchNoSpace = str_replace(' ', '', $search);

$tsql = "
    SELECT BusinessOwnerID, CompanyName, BusinessField, BusinessType,
           SSMRegistrationNumber, BusinessContactNumber, BusinessAddress, CompanyEmail,
           BusinessFacebook, BusinessInstagram, BusinessTiktok, BusinessWebsite, StoreType
    FROM BusinessOwners
    WHERE REPLACE(CompanyName, ' ', '') LIKE ?
    OR REPLACE(BusinessField, ' ', '') LIKE ?
    OR CompanyEmail LIKE ?
       OR SSMRegistrationNumber LIKE ? 
       OR BusinessContactNumber LIKE ?
       OR BusinessFacebook LIKE ?
       OR BusinessInstagram LIKE ?
       OR BusinessTiktok LIKE ?
       OR BusinessWebsite LIKE ?
";
$param = "%$searchNoSpace%";
$params = [$param, $param,$param, $param, $param,$param, $param, $param, $param];
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = $row;
        }
    } else {
        $error = sqlsrv_errors();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Business</title>
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

.center-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 900px;
    z-index: 2;
}

.search-box {
    background: rgba(255, 255, 255, 0.15);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    text-align: center;
    backdrop-filter: blur(10px);
    color: white;
}

.search-box h2 {
    margin-bottom: 20px;
    font-weight: 600;
    font-size: 28px;
    color: black;
    font-weight:bold;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.7);
}

.search-box form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

input[type="text"] {
    width: 100%;
    padding: 12px 15px;
    border-radius: 8px;
    border: 2px solid black; 
    font-size: 16px;
    background: rgba(255,255,255,0.1);
    color: black;
}

input[type="text"]::placeholder {
     color: rgba(0,0,0,0.5); 
}

button {
    margin-top: 10px;
    padding: 12px;
    background-color: rgba(255, 182, 193, 0.8);
    color: black;
    border: none;
    font-weight:bold;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background-color: rgba(0,123,255,1);
}

.results {
    margin-top: 20px;
    text-align: left;
    color: black;
}

.results table {
    margin-top:10px;
    width: 100%;
    border-collapse: collapse;
}

.results th, .results td {
text-align: center;
    padding: 15px;
    border: 1px solid rgba(255,255,255,0.3);
    white-space: normal;   /* allow wrapping */
    word-break: break-word; /* break long words if needed */
    max-width: 200px; 
}

.results th {
    background-color: rgba(255, 182, 193, 0.8);
    color: black;
    font-weight: 500;
}
.results a {
    color: black;
    text-decoration: underline;
}
</style>
</head>
<body>

<img id="bg-image" src="SBackground.jpeg" alt="Background">
<?php include 'Sidebar.php'; ?>

<div class="center-content">
    <div class="search-box">
        <h2>SEARCH FOR BUSINESS</h2>
        <form method="get" action="">
            <input type="text" name="q" placeholder="Enter Business Name or Business's Info" required>
           <button type="submit">SEARCH</button>
        </form>

        <?php if (!empty($results)): ?>
        <div class="results">
            <h3>RESULT</h3>
            <table>
                <tr>
                    <th>COMPANY NAME</th>
                    <th>BUSINESS FIELD</th>
                    <th>STORE TYPE</th>
                    <th>ADDRESS</th>
                    <th>MORE DETAILS</th>
                </tr>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['CompanyName']); ?></td>
                    <td><?= htmlspecialchars($row['BusinessField']); ?></td>
                    <td><?= htmlspecialchars($row['StoreType']); ?></td>
                    <td><?php 
                        if ($row['StoreType'] === 'Both' || $row['StoreType'] === 'Physical') {
                            echo htmlspecialchars($row['BusinessAddress']);
                        } else {
                            echo '-';
                        }
                    ?>
                    </td>
                    <td><a href="business_profile.php?id=<?= urlencode($row['BusinessOwnerID']); ?>">View Details</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
                <?php elseif(isset($_GET['q'])): ?>
                    <p style="margin-top:15px;color:red;">No Business Found For "<?= htmlspecialchars($search); ?>"</p>
                <?php endif; ?>
    </div>
</div>

</body>
</html>