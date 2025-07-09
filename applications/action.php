<?php
include '../inc/head.php';
include '../inc/const.php';
include '../inc/db.php';

$response = array();
$method = $_SERVER['REQUEST_METHOD'];

// Define input sanitization function outside of blocks
function secure_input($conn, $input, $field)
{
    return isset($input[$field]) ? mysqli_real_escape_string($conn, $input[$field]) : "";
}

switch ($method) {
    case 'POST':
        parse_str(file_get_contents("php://input"), $input);
ob_clean();
ini_set('display_errors', 0);               // Don't show errors in output
ini_set('log_errors', 1);                   // Log errors instead
ini_set('error_log', __DIR__ . '/php-error.log'); // Save errors to this file
header('Content-Type: application/json');
        if (isset($_GET['action']) && $_GET['action'] === 'upload') {
            $required_fields = [
                'name', 'place', 'campus', 'jamiaClass',
                'email', 'phone1', 'father', 'mother', 'dob', 'address', 'taluk',
                'district', 'state', 'pincode', 'aadhar', 'admissionYear'
            ];

            $missing_fields = [];
            foreach ($required_fields as $field) {
                if (!isset($input[$field])) {
                    $missing_fields[] = $field;
                }
            }

            if (!empty($missing_fields)) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Missing required fields: " . implode(', ', $missing_fields)
                ]);
                exit();
            }

            // Secure and fetch values
            $name = secure_input($conn, $input, 'name');
            $place = secure_input($conn, $input, 'place');
            $campus = secure_input($conn, $input, 'campus');
            $jamiaClass = secure_input($conn, $input, 'jamiaClass');
            $email = secure_input($conn, $input, 'email');
            $phone1 = secure_input($conn, $input, 'phone1');
            $phone2 = secure_input($conn, $input, 'phone2');
            $father = secure_input($conn, $input, 'father');
            $mother = secure_input($conn, $input, 'mother');
            $dob = secure_input($conn, $input, 'dob');
            $address = secure_input($conn, $input, 'address');
            $taluk = secure_input($conn, $input, 'taluk');
            $district = secure_input($conn, $input, 'district');
            $country = secure_input($conn, $input, 'country');
            $state = secure_input($conn, $input, 'state');
            $pincode = secure_input($conn, $input, 'pincode');
            $aadhar = secure_input($conn, $input, 'aadhar');
            $admissionYear = secure_input($conn, $input, 'admissionYear');

            $sql = "INSERT INTO applications (
                        std_fullname, std_place, std_cur_campus, std_jamia_class,
                        std_email, std_phone1, std_phone2, std_father, std_mother, std_dob, std_address, 
                        std_taluk, std_district,std_country, std_state, std_pincode, std_aadhar, std_admission_year
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
    "ssssssssssssssssss",
    $name, $place, $campus, $jamiaClass,
    $email, $phone1, $phone2, $father, $mother, $dob, $address,
    $taluk, $district, $country, $state, $pincode, $aadhar, $admissionYear
);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    "success" => true,
                    "message" => "Application added successfully",
                    "std_id" => mysqli_insert_id($conn)
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => "Error adding application",
                    "error" => $stmt->error
                ]);
            }
        }

        break;
    case 'GET':
        if (isset($_GET['id'])) {
            // Fetch a specific student by std_id
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM students WHERE std_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student) {
                http_response_code(200);
                $response = array("success" => true, "data" => $student);
            } else {
                http_response_code(404);
                $response = array("success" => false, "message" => "Student not found");
            }
            $stmt->close();
        } elseif (isset($_GET['action']) && $_GET['action'] == 'collectfilterdData') {
            // Initialize filter variables
            $campus_filter = isset($_GET['campus']) ? $_GET['campus'] : null;
            $level_filter = isset($_GET['level']) ? $_GET['level'] : null;
            
            // Base query - now selecting both ID and name
            $sql = "SELECT std_id, std_jamia_id, std_fullname FROM students WHERE 1=1";
            
            // Apply filters for students table
            if ($campus_filter) {
                $sql .= " AND std_cur_campus = '" . mysqli_real_escape_string($conn, $campus_filter) . "'";
            }
            if ($level_filter) {
                $sql .= " AND std_jamia_class = '" . mysqli_real_escape_string($conn, $level_filter) . "'";
            }
            
            $result = mysqli_query($conn, $sql);
        
            if ($result) {
                // Initialize an empty array to hold the results
                $students = array();
                // Fetch all rows into the array
                while ($row = mysqli_fetch_assoc($result)) {
                    $students[] = array(
                        'id' => $row['std_id'],
                        'jamia_id' => $row['std_jamia_id'],
                        'fullname' => $row['std_fullname']
                    );
                }
                if (!empty($students)) {
                    http_response_code(200);
                    $response = array(
                        "success" => true, 
                        "data" => $students,
                        "filters" => array(
                            "campus" => $campus_filter,
                            "level" => $level_filter
                        )
                    );
                } else {
                    http_response_code(404);
                    $response = array(
                        "success" => false, 
                        "message" => "No records found with the given filters",
                        "filters" => array(
                            "campus" => $campus_filter,
                            "level" => $level_filter
                        )
                    );
                }
            } else {
                http_response_code(500);
                $response = array(
                    "success" => false, 
                    "message" => "Error executing query", 
                    "error" => mysqli_error($conn)
                );
            }
        } elseif (isset($_GET['action']) && $_GET['action'] == 'collectAllJamiaId') {
            // Collect all std_jamia_id from both students and alumni tables
            $sql = "
                    SELECT std_jamia_id FROM alumni
                    UNION
                    SELECT std_jamia_id FROM students
                ";

            $result = mysqli_query($conn, $sql);

            if ($result) {
                // Initialize an empty array to hold the jamia IDs
                $jamia_ids = array();
                // Fetch all std_jamia_id values into the array
                while ($row = mysqli_fetch_assoc($result)) {
                    $jamia_ids[] = $row['std_jamia_id']; // Add only the std_jamia_id to the array
                }
                if (!empty($jamia_ids)) {
                    http_response_code(200);
                    $response = array("success" => true, "data" => $jamia_ids); // Return the array of IDs
                } else {
                    http_response_code(404);
                    $response = array("success" => false, "message" => "No std_jamia_id found");
                }
            } else {
                http_response_code(500);
                $response = array("success" => false, "message" => "Error executing query", "error" => mysqli_error($conn));
            }
        } else {
            // Get all students
            $result = mysqli_query($conn, "SELECT * FROM students");
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
        parse_str(file_get_contents("php://input"), $input);

        if (isset($_GET['action']) && $_GET['action'] == 'moveon-std') {
            $missing_fields = [];
            if (!isset($input['ids'])) {
                $missing_fields[] = 'ids';
            }
            if (!isset($input['campus'])) {
                $missing_fields[] = 'campus';
            }
            if (!isset($input['generalClass'])) {
                $missing_fields[] = 'generalClass';
            }
            if (!isset($input['jamiaClass'])) {
                $missing_fields[] = 'jamiaClass';
            }
        
            if (!empty($missing_fields)) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Missing required fields: " . implode(', ', $missing_fields)
                ]);
                exit();
            }
        
            // Convert IDs to array if it's a string
            $ids = is_array($input['ids']) ? $input['ids'] : explode(',', $input['ids']);
            $ids = array_map('intval', $ids); // Convert all to integers
            $ids = array_filter($ids); // Remove empty values
        
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "No valid student IDs provided"
                ]);
                exit();
            }
        
            $campus = mysqli_real_escape_string($conn, $input['campus']);
            $generalClass = mysqli_real_escape_string($conn, $input['generalClass']);
            $jamiaClass = mysqli_real_escape_string($conn, $input['jamiaClass']);
        
            $conn->begin_transaction();
        
            try {
                foreach ($ids as $id) {
                    $stmt = $conn->prepare("SELECT * FROM students WHERE std_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 0) {
                        throw new Exception("Student not found with ID: $id");
                    }
        
                    $updateStmt = $conn->prepare("UPDATE students SET std_cur_campus = ?, std_jamia_class = ?, std_academic_class = ? WHERE std_id = ?");
                    $updateStmt->bind_param("sssi", $campus, $jamiaClass, $generalClass, $id);
                    
                    if (!$updateStmt->execute()) {
                        throw new Exception("Error moving student with ID: $id - " . $conn->error);
                    }
                    
                    $updateStmt->close();
                    $stmt->close();
                }
        
                $conn->commit();
                
                echo json_encode([
                    "success" => true,
                    "message" => "All students moved successfully",
                    "count" => count($ids)
                ]);
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode([
                    "success" => false,
                    "message" => $e->getMessage()
                ]);
                exit();
            }
        } elseif (isset($_GET['action']) && $_GET['action'] == 'update') {
            // Required fields list
            $required_fields = [
                'stdId', // Primary key for updating the student
                'name',
                'place',
                'jamiaId',
                'jamiathulHindId',
                'campus',
                'jamiaClass',
                'academicClass',
                'academicCourse',
                'batch',
                'status',
                'email',
                'phone1',
                'father',
                'mother',
                'dob',
                'address',
                'taluk',
                'district',
                'state',
                'pincode',
                'aadhar',
                'admissionYear',
                'image'
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

            // Secure inputs without manual escaping
            $stdId = isset($input['stdId']) ? $input['stdId'] : null; // No need for escaping
            $name = isset($input['name']) ? $input['name'] : '';
            $place = isset($input['place']) ? $input['place'] : '';
            $jamiaId = isset($input['jamiaId']) ? $input['jamiaId'] : '';
            $jamiathulHindId = isset($input['jamiathulHindId']) ? $input['jamiathulHindId'] : '';
            $campus = isset($input['campus']) ? $input['campus'] : '';
            $jamiaClass = isset($input['jamiaClass']) ? $input['jamiaClass'] : '';
            $academicClass = isset($input['academicClass']) ? $input['academicClass'] : '';
            $academicCourse = isset($input['academicCourse']) ? $input['academicCourse'] : '';
            $batch = isset($input['batch']) ? $input['batch'] : '';
            $status = isset($input['status']) ? $input['status'] : '';
            $email = isset($input['email']) ? $input['email'] : '';
            $phone1 = isset($input['phone1']) ? $input['phone1'] : '';
            $phone2 = isset($input['phone2']) ? $input['phone2'] : ''; // Optional field
            $father = isset($input['father']) ? $input['father'] : '';
            $mother = isset($input['mother']) ? $input['mother'] : '';
            $dob = isset($input['dob']) ? $input['dob'] : '';
            $address = isset($input['address']) ? $input['address'] : '';
            $taluk = isset($input['taluk']) ? $input['taluk'] : '';
            $district = isset($input['district']) ? $input['district'] : '';
            $state = isset($input['state']) ? $input['state'] : '';
            $pincode = isset($input['pincode']) ? $input['pincode'] : '';
            $aadhar = isset($input['aadhar']) ? $input['aadhar'] : '';
            $admissionYear = isset($input['admissionYear']) ? $input['admissionYear'] : null; // Handle as integer if provided
            $image = isset($input['image']) ? $input['image'] : ''; // Handle image as a string for now

            // Optional fields
            $prismMember = isset($input['prismMember']) ? $input['prismMember'] : '';
            $foundationCourse = isset($input['foundationCourse']) ? $input['foundationCourse'] : '';
            $foundationCampus = isset($input['foundationCampus']) ? $input['foundationCampus'] : '';
            $bachelorCourse = isset($input['bachelorCourse']) ? $input['bachelorCourse'] : '';
            $bachelorCampus = isset($input['bachelorCampus']) ? $input['bachelorCampus'] : '';
            $finishingCourse = isset($input['finishingCourse']) ? $input['finishingCourse'] : '';
            $finishingCampus = isset($input['finishingCampus']) ? $input['finishingCampus'] : '';
            $pgCourse = isset($input['pgCourse']) ? $input['pgCourse'] : '';
            $pgCampus = isset($input['pgCampus']) ? $input['pgCampus'] : '';
            $rehlaReport = isset($input['rehlaReport']) ? $input['rehlaReport'] : '';
            $internship = isset($input['internship']) ? $input['internship'] : '';
            $dissertation = isset($input['dissertation']) ? $input['dissertation'] : '';

            $sql = "UPDATE students 
                SET std_fullname=?, std_place=?, std_jamia_id=?, std_jamiathulhind_id=?, std_image=?, std_cur_campus=?, std_jamia_class=?, 
                    std_academic_class=?, std_academic_course=?, std_batch_no=?, std_prism_member=?, std_status=?, std_email=?, 
                    std_phone1=?, std_phone2=?, std_father=?, std_mother=?, std_dob=?, std_address=?, std_taluk=?, std_district=?, 
                    std_state=?, std_pincode=?, std_aadhar=?, std_admission_year=?, foundation_course=?, foundation_campus=?, 
                    bachelor_course=?, bachelor_campus=?, finishing_course=?, finishing_campus=?, pg_course=?, pg_campus=?, 
                    rehla_report=?, internship=?, dissertation=?
                WHERE std_id=?";

            // Prepare the statement
            $stmt = $conn->prepare($sql);

            // Bind the parameters, ensuring the number of variables matches the placeholders
            $stmt->bind_param(
                "ssssssssssssssssssssssssssssssssssssi",
                $name,
                $place,
                $jamiaId,
                $jamiathulHindId,
                $image,
                $campus,
                $jamiaClass,
                $academicClass,
                $academicCourse,
                $batch,
                $prismMember,
                $status,
                $email,
                $phone1,
                $phone2,
                $father,
                $mother,
                $dob,
                $address,
                $taluk,
                $district,
                $state,
                $pincode,
                $aadhar,
                $admissionYear,
                $foundationCourse,
                $foundationCampus,
                $bachelorCourse,
                $bachelorCampus,
                $finishingCourse,
                $finishingCampus,
                $pgCourse,
                $pgCampus,
                $rehlaReport,
                $internship,
                $dissertation,
                $stdId // std_id is added as the last parameter to identify the record to update
            );

            // Execute the statement
            if ($stmt->execute()) {
                http_response_code(200);
                $response = array(
                    "success" => true,
                    "message" => "Student updated successfully"
                );
            } else {
                http_response_code(500);
                $response = array(
                    "success" => false,
                    "message" => "Error updating Student",
                    "error" => $stmt->error
                );
            }
        }
        break;



    case 'DELETE':
        // Handle DELETE request (delete student)
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = intval($_GET['id']);

            // Prepare statement to delete student
            $stmt = $conn->prepare("DELETE FROM students WHERE std_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                $response = array("success" => true, "message" => "Student with ID: $id deleted successfully");
            } else {
                http_response_code(404);  // Record not found
                $response = array("success" => false, "message" => "No student found with ID: $id", "error" => $conn->error);
            }

            $stmt->close();
        } else {
            http_response_code(400);
            $response = array("success" => false, "message" => "Invalid or missing ID parameter");
        }
        break;

    default:
        http_response_code(405); // Method Not Allowed
        $response = array("success" => false, "message" => "Invalid method");
        break;
}

// Output JSON response
echo json_encode($response);
