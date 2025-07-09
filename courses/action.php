<?php

include '../inc/head.php';
include '../inc/const.php';
include '../inc/db.php';

$response = array();
$method = $_SERVER['REQUEST_METHOD'];

if ($method == "POST") {
    // Handle POST request (Add new student)
    parse_str(file_get_contents("php://input"), $input);

    if (isset($_GET['action']) && $_GET['action'] == 'add-course') {
        // Check if all required fields are set
        if (
            !isset($input['name']) || !isset($input['level']) || !isset($input['type']) ||
            !isset($input['status'])
        ) {
            http_response_code(400);
            $response = array("success" => false, "message" => "Missing required fields");
            echo json_encode($response);
            exit();
        }

        // Sanitize inputs
        $name = mysqli_real_escape_string($conn, $input['name']);
        $level = mysqli_real_escape_string($conn, $input['level']);
        $type = mysqli_real_escape_string($conn, $input['type']);
        $status = mysqli_real_escape_string($conn, $input['status']);

        // Insert new student
        $stmt = $conn->prepare("INSERT INTO courses (course_name, course_level, course_type, course_status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $level, $type, $status);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            http_response_code(201);
            $response = array("success" => true, "message" => "Course added successfully");
        } else {
            http_response_code(500);
            $response = array("success" => false, "message" => "Error adding Course", "error" => $conn->error);
        }

        $stmt->close();
    }
} else if ($method == "GET") {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Fetch specific course record by id
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $course = $result->fetch_assoc();
        
        if ($course) {
            http_response_code(200);
            $response = array("success" => true, "data" => $course);
        } else {
            http_response_code(404);
            $response = array("success" => false, "message" => "No course found with ID: $id");
        }

        $stmt->close();
    } elseif (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Fetch specific alumni record by std_id
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $alumnus = $result->fetch_assoc();
        
        if ($alumnus) {
            http_response_code(200);
            $response = array("success" => true, "data" => $alumnus);
        } else {
            http_response_code(404);
            $response = array("success" => false, "message" => "course not found with id: $id");
        }

        $stmt->close();
    } else {
        // Fetch all records from both courses and alumni tables
        $resultCourses = mysqli_query($conn, "SELECT * FROM courses");
        $courses = mysqli_fetch_all($resultCourses, MYSQLI_ASSOC);

        if (!empty($courses)) {
            http_response_code(200);
            $response = array(
                "success" => true, 
                "courses" => $courses,
            );
        } else {
            http_response_code(404);
            $response = array("success" => false, "message" => "No data found in courses or alumni");
        }
    }
    echo json_encode($response);
}
 elseif ($method == "PUT") {
    // Handle PUT request (Update student)
    parse_str(file_get_contents("php://input"), $input);

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $name = mysqli_real_escape_string($conn, $input['name']);
        $level = mysqli_real_escape_string($conn, $input['level']);
        $type = mysqli_real_escape_string($conn, $input['type']);
        $status = mysqli_real_escape_string($conn, $input['status']);

        // Prepare statement to update student info
        $stmt = $conn->prepare("UPDATE courses SET course_name = ?, course_level = ?, course_type = ?, course_status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $level, $type, $status, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            http_response_code(200);
            $response = array("success" => true, "message" => "Course with ID: $id updated successfully");
        } else {
            http_response_code(500);
            $response = array("success" => false, "message" => "Error updating Course with ID: $id", "error" => $conn->error);
        }

        $stmt->close();
    } else {
        http_response_code(400);
        $response = array("success" => false, "message" => "Missing ID parameter");
    }
} elseif ($method == "DELETE") {
    // Handle DELETE request (delete student)
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // Prepare statement to delete student
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            http_response_code(200);
            $response = array("success" => true, "message" => "Alumnus with ID: $id deleted successfully");
        } else {
            http_response_code(500);
            $response = array("success" => false, "message" => "Error deleting Alumnus with ID: $id", "error" => $conn->error);
        }

        $stmt->close();
    } else {
        http_response_code(400);
        $response = array("success" => false, "message" => "Missing ID parameter");
    }
} else {
    // Invalid method
    http_response_code(405); // Method Not Allowed
    $response = array("success" => false, "message" => "Invalid method");
}

// Output JSON response
echo json_encode($response);
