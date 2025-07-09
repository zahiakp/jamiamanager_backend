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
        // Required fields validation
        $required_fields = ['exam_id', 'marks_data'];
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
    
        $exam_id = intval($input['exam_id']);
        $marks_data = json_decode($input['marks_data'], true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid marks data format"
            ]);
            exit();
        }
    
        $success_count = 0;
        $error_messages = [];
        $current_year = date('Y');
        $academic_year = (date('n') >= 6) ? sprintf('%d-%d', $current_year, $current_year + 1) 
                                         : sprintf('%d-%d', $current_year - 1, $current_year);
    
        foreach ($marks_data as $student_id => $subjects) {
            $student_id = intval($student_id);
            
            try {
                // Check if record exists
                $check_sql = "SELECT * FROM exam_marks WHERE student_id = ? AND exam_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param('ii', $student_id, $exam_id);
                $check_stmt->execute();
                $existing_record = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();
    
                // Initialize subject data from existing record or as NULLs
                $subject_data = [];
                for ($i = 1; $i <= 12; $i++) {
                    $subject_data[$i] = [
                        'id' => $existing_record ? $existing_record["subject{$i}_id"] : null,
                        'mark' => $existing_record ? $existing_record["subject{$i}_mark"] : null
                    ];
                }
    
                // Process provided subjects
                $subject_count = 0;
                $total_marks = 0;
                $subject_index = 1; // Tracks next available subject column (1-12)
    
                foreach ($subjects as $subject_id => $mark) {
                    if ($subject_index > 12) {
                        $error_messages[] = "Student ID $student_id: Cannot add more than 12 subjects";
                        break;
                    }
    
                    $subject_id = intval($subject_id);
                    $mark = floatval($mark);
    
                    if ($mark >= 0 && $mark <= 100) {
                        $subject_data[$subject_index]['id'] = $subject_id;
                        $subject_data[$subject_index]['mark'] = $mark;
                        $subject_count++;
                        $total_marks += $mark;
                        $subject_index++;
                    }
                }
    
                // Calculate percentage and grade
                $percentage = ($subject_count > 0) ? round(($total_marks / ($subject_count * 100)) * 100, 2) : 0;
                
                $grade = '';
                if ($percentage >= 90) $grade = 'A+';
                elseif ($percentage >= 80) $grade = 'A';
                elseif ($percentage >= 70) $grade = 'B+';
                elseif ($percentage >= 60) $grade = 'B';
                elseif ($percentage >= 50) $grade = 'C';
                elseif ($percentage >= 40) $grade = 'D';
                else $grade = 'F';
    
                if ($existing_record) {
                    // Build UPDATE statement
                    $update_sql = "UPDATE exam_marks SET ";
                    $update_fields = [];
                    $update_values = [];
                    $types = '';
    
                    // Add subject fields to update
                    for ($i = 1; $i <= 12; $i++) {
                        $update_fields[] = "subject{$i}_id = ?, subject{$i}_mark = ?";
                        $update_values[] = $subject_data[$i]['id'];
                        $update_values[] = $subject_data[$i]['mark'];
                        $types .= 'id'; // integer (subject_id), double (mark)
                    }
    
                    // Add calculated fields
                    $update_fields[] = "percentage = ?";
                    $update_values[] = $percentage;
                    $types .= 'd'; // double
    
                    $update_fields[] = "overall_grade = ?";
                    $update_values[] = $grade;
                    $types .= 's'; // string
    
                    $update_fields[] = "academic_year = ?";
                    $update_values[] = $academic_year;
                    $types .= 's'; // string
    
                    // Add WHERE conditions
                    $update_sql .= implode(", ", $update_fields) . " WHERE student_id = ? AND exam_id = ?";
                    $update_values[] = $student_id;
                    $update_values[] = $exam_id;
                    $types .= 'ii'; // integer, integer
    
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param($types, ...$update_values);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    // Prepare INSERT statement
                    $sql = "INSERT INTO exam_marks (
                        student_id, exam_id,
                        subject1_id, subject1_mark, 
                        subject2_id, subject2_mark,
                        subject3_id, subject3_mark,
                        subject4_id, subject4_mark,
                        subject5_id, subject5_mark,
                        subject6_id, subject6_mark,
                        subject7_id, subject7_mark,
                        subject8_id, subject8_mark,
                        subject9_id, subject9_mark,
                        subject10_id, subject10_mark,
                        subject11_id, subject11_mark,
                        subject12_id, subject12_mark,
                        percentage, overall_grade, academic_year
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                    ON DUPLICATE KEY UPDATE
                        subject1_id = VALUES(subject1_id),
                        subject1_mark = VALUES(subject1_mark),
                        subject2_id = VALUES(subject2_id),
                        subject2_mark = VALUES(subject2_mark),
                        subject3_id = VALUES(subject3_id),
                        subject3_mark = VALUES(subject3_mark),
                        subject4_id = VALUES(subject4_id),
                        subject4_mark = VALUES(subject4_mark),
                        subject5_id = VALUES(subject5_id),
                        subject5_mark = VALUES(subject5_mark),
                        subject6_id = VALUES(subject6_id),
                        subject6_mark = VALUES(subject6_mark),
                        subject7_id = VALUES(subject7_id),
                        subject7_mark = VALUES(subject7_mark),
                        subject8_id = VALUES(subject8_id),
                        subject8_mark = VALUES(subject8_mark),
                        subject9_id = VALUES(subject9_id),
                        subject9_mark = VALUES(subject9_mark),
                        subject10_id = VALUES(subject10_id),
                        subject10_mark = VALUES(subject10_mark),
                        subject11_id = VALUES(subject11_id),
                        subject11_mark = VALUES(subject11_mark),
                        subject12_id = VALUES(subject12_id),
                        subject12_mark = VALUES(subject12_mark),
                        percentage = VALUES(percentage),
                        overall_grade = VALUES(overall_grade),
                        academic_year = VALUES(academic_year)";
    
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Prepare failed - " . $conn->error);
                    }
    
                    // Prepare parameters
                    $params = [$student_id, $exam_id];
                    $types = 'ii'; // student_id, exam_id
    
                    // Add all subjects
                    for ($i = 1; $i <= 12; $i++) {
                        $params[] = $subject_data[$i]['id'];
                        $params[] = $subject_data[$i]['mark'];
                        $types .= 'id'; // subject_id (int), mark (double)
                    }
    
                    // Add calculated fields
                    $params[] = $percentage;
                    $params[] = $grade;
                    $params[] = $academic_year;
                    $types .= 'dss'; // percentage (double), grade (string), academic_year (string)
    
                    // Bind and execute
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $stmt->close();
                }
    
                $success_count++;
            } catch (Exception $e) {
                $error_messages[] = "Student ID $student_id: " . $e->getMessage();
            }
        }
    
        $response = [
            "success" => $success_count > 0,
            "message" => $success_count > 0 ? "Marks uploaded for $success_count students" : "Failed to upload marks for any students",
            "errors" => $error_messages
        ];
    
        http_response_code($success_count > 0 ? 201 : 500);
        echo json_encode($response);
        exit();
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
    if ($_GET['action'] === 'getExamMarksByCampus') {
    
        $campusId = $_GET['campus_id'] ?? null;
        $examId = $_GET['exam_id'] ?? null;
        $academicYear = $_GET['academic_year'] ?? null;
    
        if (!$campusId || !$examId || !$academicYear) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing parameters"]);
            exit;
        }
    
        // Step 1: Get exam level
        $examRes = mysqli_query($conn, "SELECT exam_level FROM exams WHERE exam_id = '$examId'");
        $exam = mysqli_fetch_assoc($examRes);
        if (!$exam) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Exam not found"]);
            exit;
        }
        $examLevel = $exam['exam_level'];
    
        // Step 2: Get all matching rows from exam_marks table
        $query = mysqli_query($conn, "
            SELECT * FROM exam_marks 
            WHERE exam_id = '$examId' 
            AND academic_year = '$academicYear' 
            AND student_id IN (
                SELECT std_id FROM students 
                WHERE std_cur_campus = '$campusId' 
                AND std_jamia_class = '$examLevel'
            )
        ");
    
        $rows = mysqli_fetch_all($query, MYSQLI_ASSOC);
        if (!$rows) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "No mark records found"]);
            exit;
        }
    
        // Step 3: Transform data to desired format
        $result = [];
    
        foreach ($rows as $row) {
            $studentId = $row['student_id'];
            $result[$studentId] = [];
    
            for ($i = 1; $i <= 10; $i++) {
                $subKey = "subject{$i}_id";
                $markKey = "subject{$i}_mark";
    
                if (!empty($row[$subKey]) && $row[$markKey] !== null) {
                    $result[$studentId][$row[$subKey]] = $row[$markKey];
                }
            }
        }
    
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data" => $result
        ]);
    } else if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM alumni WHERE std_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if ($student) {
            http_response_code(200);
            echo json_encode(["success" => true,"data" => $student]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false,"message" => "Campus not found"]);
        }
        $stmt->close();
    } else {
        // Get all alumni
        $result = mysqli_query($conn, "SELECT * FROM exams");
        $students = mysqli_fetch_all($result, MYSQLI_ASSOC);

        if ($students) {
            http_response_code(200);
            echo json_encode(["success" => true, "data" => $students]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "No exams found"]);
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
