<?php

// Include necessary files and configurations
include './inc/head.php';  // Assuming this file includes necessary headers
include './inc/const.php'; // Assuming this file defines constants like 'API'
include './inc/db.php';    // Assuming this file includes your database connection
include './inc/upload.php'; // Assuming this file contains your upload function

$response = array();
$method = $_SERVER['REQUEST_METHOD'];

// Define allowed image types
$allowed_image_types = ['students', 'alumni', 'campuses', 'staffs'];

// Check if the request method is POST
if ($method == 'POST') {
    // Check if the API key is correct
    if (isset($_GET['api']) && $_GET['api'] == API) {
        // Check if a file is uploaded
        if (isset($_FILES['file']) && $_FILES['file']) {
            // Check if jamiaId and image_type are provided and not empty
            if (isset($_POST['jamiaId']) && !empty($_POST['jamiaId']) && isset($_POST['image_type']) && !empty($_POST['image_type'])) {
                $jamiaId = $_POST['jamiaId'];
                $image_type = $_POST['image_type'];

                // Validate the image type
                if (!in_array($image_type, $allowed_image_types)) {
                    http_response_code(400);
                    $response = array("success" => false, "message" => "Invalid image type. Allowed types are: " . implode(", ", $allowed_image_types));
                    echo json_encode($response);
                    exit;
                }
                
                // Define the upload directory based on image type
                $upload_dir = './uploads/' . $image_type . '/';
                
                // Ensure the upload directory exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Get the file extension of the uploaded file
                $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                // Create a new filename using jamiaId and the file extension
                $new_filename = $jamiaId . '.' . $file_extension; 
                $new_filepath = $upload_dir . $new_filename;

                // Check if a file with the same name already exists
                if (file_exists($new_filepath)) {
                    // Delete the existing file
                    if (!unlink($new_filepath)) {
                        http_response_code(500);
                        $response = array("success" => false, "message" => "Error deleting the existing file!");
                        echo json_encode($response);
                        exit;
                    }
                }

                // Proceed with the upload
                $upload = upload($upload_dir, $_FILES['file']);
                
                if ($upload['status']) {
                    // Move the uploaded file to the new filename
                    if (rename($upload_dir . $upload['filename'], $new_filepath)) {
                        // Success response
                        http_response_code(201);
                        $response = array(
                            "success" => true,
                            "message" => "File uploaded successfully",
                            "filename" => $new_filename // Return the new filename
                        );
                    } else {
                        // Error renaming the file
                        http_response_code(500);
                        $response = array("success" => false, "error" => true, "message" => "Error renaming the file!");
                    }
                } else {
                    // Error uploading the file
                    http_response_code(500);
                    $response = array("success" => false, "error" => true, "message" => "Error uploading the file!");
                }
            } else {
                // jamiaId or image type not provided or empty
                http_response_code(400);
                $response = array("success" => false, "message" => "Jamia ID or image type not provided");
            }
        } else {
            // File not provided
            http_response_code(400);
            $response = array("success" => false, "message" => "File not provided");
        }
    } else {
        // Unauthorized access
        http_response_code(401);
        $response = array("success" => false, "message" => "Unauthorized access");
    }    
} else {
    // Invalid method
    http_response_code(400);
    $response = array("success" => false, "message" => "Invalid Method");
}

// Output JSON response
echo json_encode($response);
?>