<?php
// Request-Types: https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-interaction-type
// Response-Types: https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-response-object-interaction-callback-type
$PUBLIC_KEY = '4f5a52fc3192dac7356d0352d8cf9eec9a1c906b30eaf1c959fda5a0679260e5';
$APPLICATION_ID = '1026494571988918272';
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo "405 Method Not Allowed";
    return;
}
$signature = $_SERVER['HTTP_X_SIGNATURE_ED25519'];
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
$responseObj = [];
switch ($jsonReq["type"]) {
    case 1: // PING
        $responseObj["type"] = 1; // PONG
        break;
    case 2: // APPLICATION_COMMAND
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();
        //$responseObj["type"] = 4; // CHANNEL_MESSAGE_WITH_SOURCE
        $responseObj["type"] = 5; // DEFERRED_CHANNEL_MESSAGE_WITH_SOURCE
        $responseObj["data"] = [];
        $options = [
            "ephemeral" => true
        ];
        for ($i = 0; $i < count($jsonReq["data"]["options"]); $i++) {
            switch ($jsonReq["data"]["options"][$i]["name"]) {
                case "ephemeral":
                    if ($jsonReq["data"]["options"][$i]["value"] == false)
                        $options["ephemeral"] = false;
                    break;
            }
        }
        if ($options["ephemeral"]) {
            $responseObj["data"]["flags"] = 64; // https://discord-api-types.dev/api/discord-api-types-v10/enum/MessageFlags
        }
        if ($outputJson) {
            header("Content-Type: application/json");
            echo str_replace(',"data":[]', '', json_encode($responseObj));
        }
        ob_end_flush();
        ob_flush();
        flush();
        $msgContent = "Test";
        $responseObj = [
            "content" => $msgContent
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf("https://discord.com/api/v10/webhooks/%s/%s/messages/@original", $APPLICATION_ID, $jsonReq["token"]));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($responseObj));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        $patchResponse = curl_exec($curl);
        curl_close($curl);
        error_log($patchResponse);
        break;
    case 4: // APPLICATION_COMMAND_AUTOCOMPLETE
        // Response Object: https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-response-object-autocomplete
        if ($jsonReq["data"]["name"] == 'timestamp') {
            $responseObj["type"] = 8; // APPLICATION_COMMAND_AUTOCOMPLETE_RESULT
            for ($i = 0; $i < count($jsonReq["data"]["options"]); $i++) {
                if (is_null($jsonReq["data"]["options"][$i]["focused"]) || !$jsonReq["data"]["options"][$i]["focused"])
                    continue;
                $timezones = array_filter(DateTimeZone::listIdentifiers(), function($tz)  {
                    return str_contains(strtolower(zone), strtolower(str_replace(" ", "_", $jsonReq["data"]["options"][$i])));
                });
                $timezones = array_slice($timezones, 0, 25);
                $responseObj["data"] = array_map(function($tz) {
                    return [
                        "name" => $tz,
                        "value" => $tz
                    ];
                });
            }
        }
        break;
    default:
        http_response_code(400);
        echo "400 Bad Request";
        $outputJson = false;
        break;
}
if ($outputJson) {
    header("Content-Type: application/json");
    echo str_replace(',"data":[]', '', json_encode($responseObj));
}
?>
