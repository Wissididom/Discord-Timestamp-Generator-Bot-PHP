<?php
$PUBLIC_KEY = '4f5a52fc3192dac7356d0352d8cf9eec9a1c906b30eaf1c959fda5a0679260e5';
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo "405 Method Not Allowed";
    return;
}
$signature = $_SERVER['HTTP_X_SIGNATURE_ED25519');
$timestamp = $_SERVER['HTTP_X_SIGNATURE_TIMESTAMP'];
$strReq = file_get_contents('php://input');
if (!sodium_crypto_sign_verify_detached(sodium_hex2bin($signature), $timestamp . $strReq, sodium_hex2bin($PUBLIC_KEY))) {
    http_response_code(401);
    echo "401 Unauthorized - Signature Validation Failed!";
    return;
}
$jsonReq = json_decode($strReq, true);
if (is_null($jsonReq)) {
    http_response_code(400);
    echo "Invalid Form Body";
    return;
}
$outputJson = true;
$responseObj = array();
switch ($jsonReq['type']) {
    case 1: // InteractionType::Ping
        $responseObj["type"] = 1; // InteractionResponseType::Pong
    break;
    case 2:
        $responseObj["type"] = 2; // InteractionResponseType::Acknowledge
    break;
    default:
        http_response_code(400);
        echo "400 Bad Request";
        $outputJson = false;
    break;
}
if ($outputJson)
    echo json_encode($responseObj);
?>
