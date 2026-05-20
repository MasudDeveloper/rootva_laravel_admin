<?php
// PCash Standalone cURL Test Script

// Configurations
$api_user = '01577095615'; // Change if needed
$api_key = '6252FS3WM45RXK6G1L158RY79B2H9QCOZ892P0T9JFL5SU6QOI'; // Set your full API key here

$test_type = isset($_GET['type']) ? $_GET['type'] : 'balance'; // 'balance' or 'recharge'

echo "<html><head><title>PCash API Test Tool</title>";
echo "<style>body{font-family:sans-serif;background:#f4f7f6;padding:20px;}pre{background:#fff;padding:15px;border:1px solid #ccc;border-radius:5px;overflow-x:auto;}h2{color:#333;}</style>";
echo "</head><body>";
echo "<h1>PCash API Test Tool</h1>";
echo "<p>Testing outside Laravel framework</p>";
echo "<hr>";
echo "<a href='?type=balance'>Test Balance Query</a> | ";
echo "<a href='?type=recharge'>Test Recharge Request</a>";
echo "<br><br>";

if ($test_type === 'balance') {
    echo "<h2>Testing Balance Query</h2>";
    $url = 'https://pcashmoney.click/sendapi/balance';
    $postData = [
        'user' => $api_user,
        'key' => $api_key
    ];
} else {
    echo "<h2>Testing Recharge Request</h2>";
    $url = 'https://pcashmoney.click/sendapi/request';
    
    $raw_operator = 'Airtel'; // Airtel, GP, Robi, Banglalink, Teletalk, Skitto etc.
    $op_map = [
        'GP' => 'GP', 'Grameenphone' => 'GP',
        'Robi' => 'RB', 'RB' => 'RB',
        'Airtel' => 'AT', 'AT' => 'AT',
        'Banglalink' => 'BL', 'BL' => 'BL',
        'Teletalk' => 'TT', 'TT' => 'TT',
        'Skitto' => 'SK', 'SK' => 'SK'
    ];
    $operator_code = isset($op_map[$raw_operator]) ? $op_map[$raw_operator] : 'AT';

    $postData = [
        'user' => $api_user,
        'key' => $api_key,
        'amount' => '20',
        'number' => '01644416378', 
        'service' => '64',
        'type' => '1',
        'id' => uniqid(),
        'operator' => $operator_code
    ];
}

$headers = [
    'band-key: flexisoftwarebd',
    'refer: rootvaadmin.rootvabd.com'
];

echo "<h3>Sent Request Data:</h3>";
echo "<strong>URL:</strong> " . htmlspecialchars($url) . "<br>";
echo "<strong>Headers:</strong><pre>" . htmlspecialchars(print_r($headers, true)) . "</pre>";
echo "<strong>POST Fields:</strong><pre>" . htmlspecialchars(print_r($postData, true)) . "</pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
curl_setopt($ch, CURLOPT_REFERER, 'rootvaadmin.rootvabd.com');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

// Enable capturing request headers for debugging
curl_setopt($ch, CURLINFO_HEADER_OUT, true);

$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$sentHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
curl_close($ch);

echo "<h3>cURL Debug Information:</h3>";
echo "<strong>Sent HTTP Headers (from cURL):</strong><pre>" . htmlspecialchars($sentHeaders) . "</pre>";

if ($error) {
    echo "<h3>cURL Error:</h3>";
    echo "<pre style='color:red;'>" . htmlspecialchars($error) . "</pre>";
} else {
    echo "<h3>HTTP Status Code:</h3>";
    echo "<pre>" . htmlspecialchars($httpCode) . "</pre>";
    
    echo "<h3>Raw Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "</body></html>";
