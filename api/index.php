<?php
$jsonReq = json_decode(file_get_contents('php://input'), true);
if (is_null($jsonReq)) {
    http_response_code(400);
    echo "Invalid Form Body";
}
if ($jsonReq['type'] == 1) {
    $responseObj = array(
        'type' => 1
    );
    echo json_encode($responseObj);
}
?>