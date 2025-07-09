<?php

include '../inc/head.php';
include '../inc/const.php';
include '../inc/db.php';

$response = array();
$method = $_SERVER['REQUEST_METHOD'];


if ($method == "GET") {
    parse_str(file_get_contents("php://input"), $input);
    
    $sql = "SELECT DISTINCT std_batch_no FROM alumni ORDER BY std_batch_no";
    $resp = mysqli_query($conn, $sql);

    if ($resp) {
        if (mysqli_num_rows($resp) > 0) {
            $data = [];
            while ($row = mysqli_fetch_assoc($resp)) {
                $data[] = $row['std_batch_no'];
            }
            http_response_code(200);
            $response = array("success" => true, "data" => $data);
        } else {
            http_response_code(404);  // Change status code to 404 for not found
            $response = array("success" => false, "message" => "Batches not found");
        }
    } else {
        http_response_code(500);
        $response = array("success" => false, "message" => "Error executing query", "error" => mysqli_error($conn));
    }

    echo json_encode($response);
    $conn->close();
}
?>