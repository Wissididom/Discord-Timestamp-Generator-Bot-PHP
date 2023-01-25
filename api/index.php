<?php
$jsonReq = json_decode(file_get_contents('php://input'), true);
if ($jsonReq['type'] == 1) {
    $responseObj = array(
        'type' => 1
    );
    echo json_encode($responseObj);
}
?>
