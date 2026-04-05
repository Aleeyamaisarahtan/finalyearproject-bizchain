<?php
// Encryption key (must match upload)
$key = "YourVerySecretKey123!";

// Get business_id from GET
$businessId = $_GET['business_id'] ?? null;
if (!$businessId) die("Invalid business ID.");

// Database connection
$serverName = "localhost,1433";
$connectionOptions = [
    "Database" => "BizChainDB",
    "Uid" => "SA",
    "PWD" => "Bizchain123!",
    "Encrypt" => false
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) die(print_r(sqlsrv_errors(), true));

// Fetch fingerprint file name
$sql = "SELECT Fingerprint FROM BusinessOwners WHERE BusinessOwnerID=?";
$stmt = sqlsrv_query($conn, $sql, [$businessId]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row || empty($row['Fingerprint'])) die("No fingerprint found.");

$filePath = "fingerprint/" . $row['Fingerprint'];
if (!file_exists($filePath)) die("Fingerprint file missing.");

$encodedData = file_get_contents($filePath);
$decodedData = base64_decode($encodedData);

// Extract IV and encrypted content
$iv = substr($decodedData, 0, 16);
$encryptedContent = substr($decodedData, 16);

// Decrypt
$decryptedContent = openssl_decrypt($encryptedContent, 'AES-256-CBC', $key, 0, $iv);
if ($decryptedContent === false) die("Failed to decrypt fingerprint.");

// Determine MIME type from file extension
$ext = pathinfo($row['Fingerprint'], PATHINFO_EXTENSION);
if ($ext == "jpg" || $ext == "jpeg") header("Content-Type: image/jpeg");
else if ($ext == "png") header("Content-Type: image/png");

// Output the image
echo $decryptedContent;
?>