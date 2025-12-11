<?php

$input = file_get_contents("php://input");
$data = json_decode($input, true);

file_put_contents("mtn_log.txt", date('Y-m-d H:i:s') . " - " . $input . "\n", FILE_APPEND);

// You can update your database here
// Example: $data["status"] == "SUCCESSFUL"

http_response_code(200);
echo "OK";
