<?php
header('Content-Type: application/json');
include '../inc/head.php';
include '../inc/const.php';
include '../inc/db.php';

$response = array();
$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Handle POST: Add new student
    parse_str(file_get_contents("php://input"), $input);

    if (isset($_GET['action']) && $_GET['action'] == 'upload') {
        // Required fields list
        $required_fields = [
            'name',
            'arName',
            'max',
            'pass',
            'examId'
        ];

        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($input[$field])) {
                $missing_fields[] = $field;
            }
        }

        // Check if there are missing fields
        if (!empty($missing_fields)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Missing required fields: " . implode(', ', $missing_fields)
            ]);
            exit();
        }

        // Secure inputs
        function secure_input($conn, $input, $field)
        {
            return isset($input[$field]) ? mysqli_real_escape_string($conn, $input[$field]) : "";
        }

        $examId = secure_input($conn, $input, 'examId');
        $name = secure_input($conn, $input, 'name');
        $arName = secure_input($conn, $input, 'arName');
        $max = secure_input($conn, $input, 'max');
        $pass = secure_input($conn, $input, 'pass');

        // SQL query
        $sql = "INSERT INTO exam_subjects (
            subject_name,subject_ar_name,max_mark,pass_mark,exam_id
        ) VALUES (?, ?, ?, ?, ?)";

        // Prepare the statement
        $stmt = $conn->prepare($sql);

        // Bind the parameters, ensuring empty fields are treated as empty strings
        $stmt->bind_param(
            "sssss",
            $name,
            $arName,
            $max,
            $pass,
            $examId
        );

        // Execute the statement
        if ($stmt->execute()) {
            // Get the last inserted ID
            $std_id = mysqli_insert_id($conn);

            http_response_code(201);
            $response = array(
                "success" => true,
                "message" => "Subject added successfully",
            );
        } else {
            http_response_code(500);
            $response = array(
                "success" => false,
                "message" => "Error adding Subject",
                "error" => $stmt->error
            );
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "PUT") {
    // Parse the raw input data
    parse_str(file_get_contents("php://input"), $input);

    // Check if the action is 'update'
    if (isset($_GET['action']) && $_GET['action'] === 'update') {
        // Define required fields
        $required_fields = ['name', 'arName', 'max', 'pass', 'examId', 'id'];

        // Check for missing fields
        $missing_fields = array_filter($required_fields, fn($field) => empty($input[$field]));
        if (!empty($missing_fields)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Missing required fields: " . implode(', ', $missing_fields)
            ]);
            exit();
        }

        // Extract and sanitize inputs
        $id = intval($input['id']);
        $name = trim($input['name']);
        $arName = trim($input['arName']);
        $max = floatval($input['max']);
        $pass = floatval($input['pass']);
        $examId = intval($input['examId']);

        // Prepare the SQL query
        $sql = "UPDATE exam_subjects 
                SET exam_id = ?, subject_name = ?, subject_ar_name = ?, max_mark = ?, pass_mark = ? 
                WHERE subject_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameters
            $stmt->bind_param("issddi", $examId, $name, $arName, $max, $pass, $id);

            // Execute the statement
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode([
                    "success" => true,
                    "message" => "Subject updated successfully"
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Error updating subject",
                    "error" => $stmt->error
                ]);
            }

            // Close the statement
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to prepare the SQL statement",
                "error" => $conn->error
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid action parameter"
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    // Check if action parameter is set
    if (isset($_GET['action']) && $_GET['action'] == 'examBasedSubjects') {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM exam_subjects WHERE exam_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $subjects = $result->fetch_all(MYSQLI_ASSOC);

        if ($subjects) {
            http_response_code(200);
            $response = array("success" => true, "data" => $subjects);
        } else {
            http_response_code(404);
            $response = array("success" => false, "message" => "No subjects found for the given exam ID");
        }
        $stmt->close();
    } elseif (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM exam_subjects WHERE subject_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if ($student) {
            http_response_code(200);
            $response = array("success" => true, "data" => $student);
        } else {
            http_response_code(404);
            $response = array("success" => false, "message" => "campuse not found");
        }
        $stmt->close();
    } else {
        // Get all alumni
        $result = mysqli_query($conn, "SELECT * FROM exam_subjects");
        $students = mysqli_fetch_all($result, MYSQLI_ASSOC);

        if ($students) {
            http_response_code(200);
            $response = array("success" => true, "data" => $students);
        } else {
            http_response_code(404);
            $response = array("success" => false, "message" => "No exams found");
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "DELETE") {
    // Handle DELETE request (delete student)
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = intval($_GET['id']);

        // Prepare statement to delete student
        $stmt = $conn->prepare("DELETE FROM alumni WHERE std_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            http_response_code(200);
            $response = array("success" => true, "message" => "Alumnus with ID: $id deleted successfully");
        } else {
            http_response_code(404);  // Record not found
            $response = array("success" => false, "message" => "No alumnus found with ID: $id", "error" => $conn->error);
        }

        $stmt->close();
    } else {
        http_response_code(400);
        $response = array("success" => false, "message" => "Invalid or missing ID parameter");
    }
} else {
    // Invalid method
    http_response_code(405); // Method Not Allowed
    $response = array("success" => false, "message" => "Invalid method");
}

// Output JSON response
echo json_encode($response);
