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
            'type',
            'subjectCount',
            'isCommon',
            'year',
            'level',
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

        $name = secure_input($conn, $input, 'name');
        $type = secure_input($conn, $input, 'type');
        $level = secure_input($conn, $input, 'level');
        $year = secure_input($conn, $input, 'year');
        $isCommon = secure_input($conn, $input, 'isCommon');
        $subjectCount = secure_input($conn, $input, 'subjectCount');

        // SQL query
        $sql = "INSERT INTO exams (
            exam_name,exam_type, exam_level, exam_year, is_common, subject_count
        ) VALUES (?, ?, ?, ?, ?, ?)";

        // Prepare the statement
        $stmt = $conn->prepare($sql);

        // Bind the parameters, ensuring empty fields are treated as empty strings
        $stmt->bind_param(
            "ssssss",
            $name,
            $type,
            $level,
            $year,
            $isCommon,
            $subjectCount,
        );

        // Execute the statement
        if ($stmt->execute()) {
            // Get the last inserted ID
            $std_id = mysqli_insert_id($conn);

            http_response_code(201);
            $response = array(
                "success" => true,
                "message" => "Exam added successfully",
                "std_id" => $std_id // Include the std_id in the response
            );
        } else {
            http_response_code(500);
            $response = array(
                "success" => false,
                "message" => "Error adding Exam",
                "error" => $stmt->error
            );
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "PUT") {
    parse_str(file_get_contents("php://input"), $input);

    if (isset($_GET['action']) && $_GET['action'] == 'update') {
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

        $sql = "UPDATE alumni 
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
                "message" => "Alumnus updated successfully"
            );
        } else {
            http_response_code(500);
            $response = array(
                "success" => false,
                "message" => "Error updating Alumnus",
                "error" => $stmt->error
            );
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
    // Check if action parameter is set
    if (isset($_GET['action']) && $_GET['action'] == 'collectJamiaIds') {
        // Collect all jamiaId from students and alumni
        $resultStudents = mysqli_query($conn, "SELECT std_jamia_id FROM students");
        $resultAlumni = mysqli_query($conn, "SELECT std_jamia_id FROM alumni");

        // Fetch all jamiaIds from students and alumni tables
        $jamiaIdsStudents = mysqli_fetch_all($resultStudents, MYSQLI_ASSOC);
        $jamiaIdsAlumni = mysqli_fetch_all($resultAlumni, MYSQLI_ASSOC);

        // Extract the jamiaId values into a flat array
        $jamiaIds = array_column($jamiaIdsStudents, 'std_jamia_id');
        $jamiaIds = array_merge($jamiaIds, array_column($jamiaIdsAlumni, 'std_jamia_id'));

        if ($jamiaIds) {
            http_response_code(200);
            $response = array("success" => true, "data" => $jamiaIds);
        } else {
            http_response_code(404);
            $response = array("success" => false, "message" => "No jamiaIds found");
        }
    } else if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM alumni WHERE std_id = ?");
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
        $result = mysqli_query($conn, "SELECT * FROM exams");
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
