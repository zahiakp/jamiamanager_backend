<?php
include '../inc/head.php';
include '../inc/const.php';
include '../inc/db.php';

$response = array();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Handle POST: Add new student education details
        parse_str(file_get_contents("php://input"), $input);

        if (isset($_GET['action']) && $_GET['action'] == 'upload') {
            $missing_fields = [];

            // Required field checks
            if (!isset($input['stdId'])) $missing_fields[] = 'std_id';
            if (!isset($input['foundationCourse'])) $missing_fields[] = 'foundation_course';
            if (!isset($input['foundationCampus'])) $missing_fields[] = 'foundation_campus';
            if (!isset($input['courseStatus'])) $missing_fields[] = 'course_status';

            // Check if there are missing fields
            if (!empty($missing_fields)) {
                http_response_code(400);
                $response = array(
                    "success" => false,
                    "message" => "Missing required fields: " . implode(', ', $missing_fields)
                );
                echo json_encode($response);
                exit();
            }

            // Securely handle each input
            $stdId = mysqli_real_escape_string($conn, $input['stdId']);
            $foundationCourse = mysqli_real_escape_string($conn, $input['foundationCourse']);
            $foundationCampus = mysqli_real_escape_string($conn, $input['foundationCampus']);
            $bachelorCourse = isset($input['bachelorCourse']) ? mysqli_real_escape_string($conn, $input['bachelorCourse']) :"";
            $bachelorCampus = isset($input['bachelorCampus']) ? mysqli_real_escape_string($conn, $input['bachelorCampus']) :"";
            $finishingCourse = isset($input['finishingCourse']) ? mysqli_real_escape_string($conn, $input['finishingCourse']) :"";
            $finishingCampus = isset($input['finishingCampus']) ? mysqli_real_escape_string($conn, $input['finishingCampus']) :"";
            $courseStatus = mysqli_real_escape_string($conn, $input['courseStatus']);
            $rehlaReport = isset($input['rehlaReport']) ? mysqli_real_escape_string($conn, $input['rehlaReport']) :"";
            $dissertation = isset($input['dissertation']) ? mysqli_real_escape_string($conn, $input['dissertation']) :"";
            $internship = isset($input['internship']) ? mysqli_real_escape_string($conn, $input['internship']) :"";

            // SQL insert query with all fields
            $sql = "INSERT INTO prev_education 
                    (std_id, foundation_course, foundation_campus, bachelor_course, bachelor_campus, finishing_course, finishing_campus, course_status, rehla_report, dissertation, internship)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssssss",
                $stdId,
                $foundationCourse,
                $foundationCampus,
                $bachelorCourse,
                $bachelorCampus,
                $finishingCourse,
                $finishingCampus,
                $courseStatus,
                $rehlaReport,
                $dissertation,
                $internship
            );

            if ($stmt->execute()) {
                http_response_code(201);
                $response = array(
                    "success" => true,
                    "message" => "Education details added successfully",
                );
            } else {
                http_response_code(500);
                $response = array(
                    "success" => false,
                    "message" => "Error adding education details",
                    "error" => $stmt->error
                );
            }

            // Send the response and exit
            echo json_encode($response);
            exit();
        }
        break;
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM prev_education WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student) {
                http_response_code(200);
                $response = array("success" => true, "data" => $student);
            } else {
                http_response_code(404);
                $response = array("success" => false, "message" => "Course Data not found");
            }
            $stmt->close();
        } else if (isset($_GET['stdId'])) {
            $id = intval($_GET['stdId']);
            $stmt = $conn->prepare("SELECT * FROM prev_education WHERE std_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student) {
                http_response_code(200);
                $response = array("success" => true, "data" => $student);
            } else {
                http_response_code(404);
                $response = array("success" => false, "message" => "Course Data not found");
            }
            $stmt->close();
        } else {
            // Get all Course Data
            $result = mysqli_query($conn, "SELECT * FROM prev_education");
            $students = mysqli_fetch_all($result, MYSQLI_ASSOC);

            if ($students) {
                http_response_code(200);
                $response = array("success" => true, "data" => $students);
            } else {
                http_response_code(404);
                $response = array("success" => false, "message" => "No students found");
            }
        }
        break;
    case 'PUT':
        // Handle PUT: Update existing student education details
        parse_str(file_get_contents("php://input"), $input);

        if (isset($_GET['action']) && $_GET['action'] == 'update') {
            $missing_fields = [];

            // Required field checks
            if (!isset($input['id'])) $missing_fields[] = 'id'; // Primary key
            if (!isset($input['stdId'])) $missing_fields[] = 'std_id'; // Additional data
            if (!isset($input['foundationCourse'])) $missing_fields[] = 'foundation_course';
            if (!isset($input['foundationCampus'])) $missing_fields[] = 'foundation_campus';
            if (!isset($input['courseStatus'])) $missing_fields[] = 'course_status';

            // Check if there are missing fields
            if (!empty($missing_fields)) {
                http_response_code(400);
                $response = array(
                    "success" => false,
                    "message" => "Missing required fields: " . implode(', ', $missing_fields)
                );
                echo json_encode($response);
                exit();
            }

            // Securely handle each input
            $id = mysqli_real_escape_string($conn, $input['id']); // Primary key
            $stdId = mysqli_real_escape_string($conn, $input['stdId']); // Additional data
            $foundationCourse = mysqli_real_escape_string($conn, $input['foundationCourse']);
            $foundationCampus = mysqli_real_escape_string($conn, $input['foundationCampus']);
            $bachelorCourse = isset($input['bachelorCourse']) ? mysqli_real_escape_string($conn, $input['bachelorCourse']) :"";
            $bachelorCampus = isset($input['bachelorCampus']) ? mysqli_real_escape_string($conn, $input['bachelorCampus']) :"";
            $finishingCourse = isset($input['finishingCourse']) ? mysqli_real_escape_string($conn, $input['finishingCourse']) :"";
            $finishingCampus = isset($input['finishingCampus']) ? mysqli_real_escape_string($conn, $input['finishingCampus']) :"";
            $courseStatus = mysqli_real_escape_string($conn, $input['courseStatus']);
            $rehlaReport = isset($input['rehlaReport']) ? mysqli_real_escape_string($conn, $input['rehlaReport']) :"";
            $dissertation = isset($input['dissertation']) ? mysqli_real_escape_string($conn, $input['dissertation']) :"";
            $internship = isset($input['internship']) ? mysqli_real_escape_string($conn, $input['internship']) :"";

            // SQL update query
            $sql = "UPDATE prev_education SET 
                        std_id = ?, 
                        foundation_course = ?, 
                        foundation_campus = ?, 
                        bachelor_course = ?, 
                        bachelor_campus = ?, 
                        finishing_course = ?, 
                        finishing_campus = ?, 
                        course_status = ?, 
                        rehla_report = ?, 
                        dissertation = ?, 
                        internship = ?
                        WHERE id = ?"; // Using 'id' for the WHERE clause

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssss",
                $stdId, // Set std_id value
                $foundationCourse,
                $foundationCampus,
                $bachelorCourse,
                $bachelorCampus,
                $finishingCourse,
                $finishingCampus,
                $courseStatus,
                $rehlaReport,
                $dissertation,
                $internship,
                $id // Adding id to the end for the WHERE clause
            );

            if ($stmt->execute()) {
                http_response_code(200);
                $response = array(
                    "success" => true,
                    "message" => "Education details updated successfully",
                );
            } else {
                http_response_code(500);
                $response = array(
                    "success" => false,
                    "message" => "Error updating education details",
                    "error" => $stmt->error
                );
            }

            // Send the response and exit
            echo json_encode($response);
            exit();
        }
        break;

    case 'DELETE':
        // Handle DELETE: Delete a student
        parse_str(file_get_contents("php://input"), $input);
        if (!isset($input['id'])) {
            http_response_code(400);
            $response = array("success" => false, "message" => "Student ID is required for deletion");
            break;
        }

        $id = intval($input['id']);
        $stmt = $conn->prepare("DELETE FROM prev_education WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                $response = array("success" => true, "message" => "Course Detail successfully deleted");
            } else {
                http_response_code(404);
                $response = array("success" => false, "message" => "Course Detail not found");
            }
        } else {
            http_response_code(500);
            $response = array("success" => false, "message" => "Error deleting Course Detail", "error" => mysqli_error($conn));
        }
        $stmt->close();
        break;

    default:
        http_response_code(405); // Method Not Allowed
        $response = array("success" => false, "message" => "Invalid method");
        break;
}

// Output JSON response
echo json_encode($response);
