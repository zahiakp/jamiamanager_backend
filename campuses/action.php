<?php
include '../inc/head.php';
include '../inc/const.php';
include '../inc/db.php';

header('Content-Type: application/json'); // Set the content type to JSON
$response = array();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            parse_str(file_get_contents("php://input"), $input);

            if (isset($_GET['action']) && $_GET['action'] == 'upload') {
                $missing_fields = [];

                // Check for required fields
                $required_fields = [
                    'jamiaId',
                    'name',
                    'place'
                ];

                foreach ($required_fields as $field) {
                    if (!isset($input[$field])) {
                        $missing_fields[] = $field;
                    }
                }

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
                $jamiaId = $input['jamiaId']; // No escaping
                $jamiathulHindId = isset($input['jamiathulHindId']) ? $input['jamiathulHindId'] : "";
                $locationLink = isset($input['locationLink']) ? $input['locationLink'] : "";
                $incharge = isset($input['incharge']) ? $input['incharge'] : "";
                $assistIncharge = isset($input['assistIncharge']) ? $input['assistIncharge'] : "";
                $inchargePhone = isset($input['inchargePhone']) ? $input['inchargePhone'] : "";
                $assistInchargePhone = isset($input['assistInchargePhone']) ? $input['assistInchargePhone'] : "";
                $affiliationtype = isset($input['affiliationtype']) ? $input['affiliationtype'] : "";
                $faculties = isset($input['faculties']) ? $input['faculties'] : "";
                $name = $input['name']; // No escaping
                $phone = isset($input['phone']) ? $input['phone'] : "";
                $image = isset($input['image']) ? $input['image'] : "";
                $place = $input['place']; // No escaping
                $taluk = isset($input['taluk']) ? $input['taluk'] : "";
                $district = isset($input['district']) ? $input['district'] : "";
                $state = isset($input['state']) ? $input['state'] : "";
                $pincode = isset($input['pincode']) ? $input['pincode'] : "";
                $email = isset($input['email']) ? $input['email'] : "";
                $hashighschool = isset($input['hashighschool']) ? $input['hashighschool'] : "";
                $hashss = isset($input['hashss']) ? $input['hashss'] : "";
                $hasdegree = isset($input['hasdegree']) ? $input['hasdegree'] : "";
                $haspg = isset($input['haspg']) ? $input['haspg'] : "";
                $highschoolCourse = isset($input['highschoolCourse']) ? $input['highschoolCourse'] : "";
                $hssCourse = isset($input['hssCourse']) ? $input['hssCourse'] : "";
                $degreeCourse = isset($input['degreeCourse']) ? $input['degreeCourse'] : "";
                $pgCourse = isset($input['pgCourse']) ? $input['pgCourse'] : "";

                // SQL insert query with all fields
                $sql = "INSERT INTO campuses (name, place,image, district, state, taluk, pincode, locationLink , phone, email, jamiaId, jamiathulHindId, incharge, incharge_phone, assist_incharge, assist_incharge_phone, hasHSS, hasDegree, hasHighSchool, hasPG, hss_course, degree_course, highschool_course, pg_course, affiliationType, faculties)
                        VALUES (?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssssssssssssssssssss",
                    $name,
                    $place,
                    $image,
                    $district,
                    $state,
                    $taluk,
                    $pincode,
                    $locationLink,
                    $phone,
                    $email,
                    $jamiaId,
                    $jamiathulHindId,
                    $incharge,
                    $inchargePhone,
                    $assistIncharge,
                    $assistInchargePhone,
                    $hashss,
                    $hasdegree,
                    $hashighschool,
                    $haspg,
                    $hssCourse,
                    $degreeCourse,
                    $highschoolCourse,
                    $pgCourse,
                    $affiliationtype,
                    $faculties
                );

                if ($stmt->execute()) {
                    // Get the last inserted ID
                    $std_id = mysqli_insert_id($conn);

                    http_response_code(201);
                    $response = array(
                        "success" => true,
                        "message" => "Campus added successfully",
                        "std_id" => $std_id // Include the std_id in the response
                    );
                } else {
                    http_response_code(500);
                    $response = array(
                        "success" => false,
                        "message" => "Error adding campus",
                        "error" => $stmt->error
                    );
                }
            }
            break;
        case 'GET':
            // Handle GET: Retrieve student data
            if (isset($_GET['action']) && $_GET['action'] == 'collectAllJamiaId') {
                // Collect all std_jamia_id from both students and alumni tables
                $sql = "
                        SELECT jamiaId FROM campuses
                    ";

                $result = mysqli_query($conn, $sql);

                if ($result) {
                    // Initialize an empty array to hold the jamia IDs
                    $jamia_ids = array();
                    // Fetch all std_jamia_id values into the array
                    while ($row = mysqli_fetch_assoc($result)) {
                        $jamia_ids[] = $row['jamiaId']; // Add only the std_jamia_id to the array
                    }
                    if (!empty($jamia_ids)) {
                        http_response_code(200);
                        $response = array("success" => true, "data" => $jamia_ids); // Return the array of IDs
                    } else {
                        http_response_code(404);
                        $response = array("success" => false, "message" => "No jamiaId found");
                    }
                }
            } elseif (isset($_GET['action']) && $_GET['action'] == 'levelCheck') {
                // Check if level parameter is provided
                if (isset($_GET['level'])) {
                    $level = $_GET['level'];
                    $validLevels = ['hasHSS', 'hasDegree', 'hasHighSchool', 'hasPG'];

                    // Validate the level parameter
                    if (in_array($level, $validLevels)) {
                        // Prepare SQL to fetch campuses where the specified level is 1
                        $sql = "SELECT id FROM campuses WHERE $level = 1";
                        $result = mysqli_query($conn, $sql);

                        if ($result) {
                            $jamia_ids = array();
                            while ($row = mysqli_fetch_assoc($result)) {
                                $jamia_ids[] = $row['id'];
                            }

                            if (!empty($jamia_ids)) {
                                http_response_code(200);
                                $response = array("success" => true, "data" => $jamia_ids);
                            } else {
                                http_response_code(404);
                                $response = array("success" => false, "message" => "No campuses found with $level = 1");
                            }
                        } else {
                            http_response_code(500);
                            $response = array("success" => false, "message" => "Database query failed");
                        }
                    } else {
                        http_response_code(400);
                        $response = array("success" => false, "message" => "Invalid level parameter");
                    }
                } else {
                    http_response_code(400);
                    $response = array("success" => false, "message" => "Level parameter is required");
                }
            } elseif (isset($_GET['action']) && $_GET['action'] == 'campusCheck') {
                // Check if campusId parameter is provided
                if (isset($_GET['campusId'])) {
                    $campusId = intval($_GET['campusId']);
                    $validLevels = ['hasHSS', 'hasDegree', 'hasHighSchool', 'hasPG'];

                    // Prepare SQL to fetch level values for the specified campus
                    $sql = "SELECT hasHSS, hasDegree, hasHighSchool, hasPG FROM campuses WHERE id = $campusId";
                    $result = mysqli_query($conn, $sql);

                    if ($result && mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        $activeLevels = array();

                        // Check which levels are active (value = 1)
                        foreach ($validLevels as $level) {
                            if ($row[$level] == 1) {
                                $activeLevels[] = $level;
                            }
                        }

                        if (!empty($activeLevels)) {
                            http_response_code(200);
                            $response = array("success" => true, "data" => $activeLevels);
                        } else {
                            http_response_code(404);
                            $response = array("success" => false, "message" => "No active levels found for this campus");
                        }
                    } else {
                        http_response_code(404);
                        $response = array("success" => false, "message" => "Campus not found");
                    }
                } else {
                    http_response_code(400);
                    $response = array("success" => false, "message" => "campusId parameter is required");
                }
            } elseif (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $stmt = $conn->prepare("SELECT * FROM campuses WHERE id = ?");
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
                // Get all students
                $result = mysqli_query($conn, "SELECT * FROM campuses");
                $students = mysqli_fetch_all($result, MYSQLI_ASSOC);

                if ($students) {
                    http_response_code(200);
                    $response = array("success" => true, "data" => $students);
                } else {
                    http_response_code(404);
                    $response = array("success" => false, "message" => "No campuse found");
                }
            }
            break;

        case 'PUT':
            parse_str(file_get_contents("php://input"), $input);

            if (isset($_GET['action']) && $_GET['action'] == 'update') {
                $missing_fields = [];

                // Check for required fields
                $required_fields = [
                    'id',
                    'jamiaId',
                    'name'
                ];

                foreach ($required_fields as $field) {
                    if (!isset($input[$field])) {
                        $missing_fields[] = $field;
                    }
                }

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

                // Securely handle each input without escaping
                $id = $input['id']; // Campus ID to update
                $jamiaId = $input['jamiaId'];
                $jamiathulHindId = $input['jamiathulHindId'] ?? ""; // Use null coalescing operator
                $locationLink = $input['locationLink'] ?? "";
                $incharge = $input['incharge'] ?? "";
                $assistIncharge = $input['assistIncharge'] ?? "";
                $inchargePhone = $input['inchargePhone'] ?? "";
                $assistInchargePhone = $input['assistInchargePhone'] ?? "";
                $affiliationType = $input['affiliationtype'] ?? "";
                $faculties = $input['faculties'] ?? "";
                $name = $input['name'];
                $phone = $input['phone'] ?? "";
                $image = $input['image'] ?? "";
                $place = $input['place'] ?? "";
                $taluk = $input['taluk'] ?? "";
                $district = $input['district'] ?? "";
                $state = $input['state'] ?? "";
                $pincode = $input['pincode'] ?? "";
                $email = $input['email'] ?? "";
                $hashighschool = $input['hashighschool'] ?? "";
                $hashss = $input['hashss'] ?? "";
                $hasdegree = $input['hasdegree'] ?? "";
                $haspg = $input['haspg'] ?? "";
                $highschoolCourse = $input['highschoolCourse'] ?? "";
                $hssCourse = $input['hssCourse'] ?? "";
                $degreeCourse = $input['degreeCourse'] ?? "";
                $pgCourse = $input['pgCourse'] ?? "";

                // SQL update query
                $sql = "UPDATE campuses SET 
                                name = ?, 
                                place = ?, 
                                image = ?, 
                                district = ?, 
                                state = ?, 
                                taluk = ?, 
                                pincode = ?, 
                                locationLink = ?, 
                                phone = ?, 
                                email = ?, 
                                jamiaId = ?, 
                                jamiathulHindId = ?, 
                                incharge = ?, 
                                incharge_phone = ?, 
                                assist_incharge = ?, 
                                assist_incharge_phone = ?, 
                                hasHSS = ?, 
                                hasDegree = ?, 
                                hasHighSchool = ?, 
                                hasPG = ?, 
                                hss_course = ?, 
                                degree_course = ?, 
                                highschool_course = ?, 
                                pg_course = ?, 
                                affiliationType = ?,
                                faculties = ?
                            WHERE id = ?";

                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    http_response_code(500);
                    $response = array(
                        "success" => false,
                        "message" => "Error preparing statement",
                        "error" => $conn->error
                    );
                    echo json_encode($response);
                    exit();
                }

                // Bind parameters
                $stmt->bind_param(
                    "ssssssssssssssssssssssssssi",
                    $name,
                    $place,
                    $image,
                    $district,
                    $state,
                    $taluk,
                    $pincode,
                    $locationLink,
                    $phone,
                    $email,
                    $jamiaId,
                    $jamiathulHindId,
                    $incharge,
                    $inchargePhone,
                    $assistIncharge,
                    $assistInchargePhone,
                    $hashss,
                    $hasdegree,
                    $hashighschool,
                    $haspg,
                    $hssCourse,
                    $degreeCourse,
                    $highschoolCourse,
                    $pgCourse,
                    $affiliationType,
                    $faculties,
                    $id // Bind the ID for the update
                );

                if (!$stmt->execute()) {
                    http_response_code(500);
                    $response = array(
                        "success" => false,
                        "message" => "Error updating campus",
                        "error" => $stmt->error
                    );
                } else {
                    http_response_code(200);
                    $response = array(
                        "success" => true,
                        "message" => "Campus updated successfully"
                    );
                }
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
            $stmt = $conn->prepare("DELETE FROM campuses WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    http_response_code(200);
                    $response = array("success" => true, "message" => "Campus successfully deleted");
                } else {
                    http_response_code(404);
                    $response = array("success" => false, "message" => "Campus not found");
                }
            } else {
                http_response_code(500);
                $response = array("success" => false, "message" => "Error deleting campus", "error" => mysqli_error($conn));
            }
            $stmt->close();
            break;

        default:
            http_response_code(405); // Method Not Allowed
            $response = array("success" => false, "message" => "Invalid method");
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = array("success" => false, "message" => "Internal Server Error", "error" => $e->getMessage());
}

// Output JSON response
echo json_encode($response);
