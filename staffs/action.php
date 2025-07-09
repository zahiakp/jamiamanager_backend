<?php
include '../inc/head.php';
include '../inc/const.php';
include '../inc/db.php';

$response = array();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        parse_str(file_get_contents("php://input"), $input);

        if (isset($_GET['action']) && $_GET['action'] == 'upload') {
            // Required fields list
            $required_fields = [
                'name',
                'jamiaId',
                'staffId',
                'phone',
                'campus',
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
            $image = secure_input($conn, $input, 'image');
            $jamiaId = secure_input($conn, $input, 'jamiaId');
            $staffId = secure_input($conn, $input, 'staffId');
            $email = secure_input($conn, $input, 'email');
            $phone = secure_input($conn, $input, 'phone');
            $dob = secure_input($conn, $input, 'dob');
            $address = secure_input($conn, $input, 'address');
            $district = secure_input($conn, $input, 'district');
            $place = secure_input($conn, $input, 'place');
            $department = secure_input($conn, $input, 'department');
            $ISqualification = secure_input($conn, $input, 'ISqualification');
            $position = secure_input($conn, $input, 'position');
            $role = secure_input($conn, $input, 'role');
            $experience = secure_input($conn, $input, 'experience');
            $campus = secure_input($conn, $input, 'campus');
            $status = secure_input($conn, $input, 'status');

            // SQL query
            $sql = "INSERT INTO staffs (
            
       staff_fire_id, staff_name, staff_image, staff_place,staff_district, staff_address, staff_email, staff_dob, staff_cur_campus, staff_status, staff_role, staff_phone, staff_jamia_id, staff_position, staff_department, IS_qualification, staff_experience
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            // Prepare the statement
            $stmt = $conn->prepare($sql);

            // Bind the parameters, ensuring empty fields are treated as empty strings
            $stmt->bind_param(
                "sssssssssssssssss",
                $staffId,
                $name,
                $image,
                $place,
                $district,
                $address,
                $email,
                $dob,
                $campus,
                $status,
                $role,
                $phone,
                $jamiaId,
                $position,
                $department,
                $ISqualification,
                $experience,
            );

            // Execute the statement
            if ($stmt->execute()) {
                // Get the last inserted ID
                $std_id = mysqli_insert_id($conn);

                http_response_code(201);
                $response = array(
                    "success" => true,
                    "message" => "Staff added successfully",
                    "std_id" => $std_id // Include the std_id in the response
                );
            } else {
                http_response_code(500);
                $response = array(
                    "success" => false,
                    "message" => "Error adding staff",
                    "error" => $stmt->error
                );
            }
        }
        break;
    case 'GET':
        if (isset($_GET['id'])) {
            // Fetch a specific student by std_id
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM staffs WHERE staff_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student) {
                http_response_code(200);
                $response = array("success" => true, "data" => $student);
            } else {
                http_response_code(404);
                $response = array("success" => false, "message" => "staff not found");
            }
            $stmt->close();
        } elseif (isset($_GET['action']) && $_GET['action'] == 'collectStaffJamiaId') {
            // Collect all std_jamia_id from both students and alumni tables
            $sql = "
                    SELECT staff_jamia_id FROM staffs
                ";

            $result = mysqli_query($conn, $sql);

            if ($result) {
                // Initialize an empty array to hold the jamia IDs
                $jamia_ids = array();
                // Fetch all std_jamia_id values into the array
                while ($row = mysqli_fetch_assoc($result)) {
                    $jamia_ids[] = $row['staff_jamia_id']; // Add only the std_jamia_id to the array
                }
                if (!empty($jamia_ids)) {
                    http_response_code(200);
                    $response = array("success" => true, "data" => $jamia_ids); // Return the array of IDs
                } else {
                    http_response_code(404);
                    $response = array("success" => false, "message" => "No staff_jamia_id found");
                }
            } else {
                http_response_code(500);
                $response = array("success" => false, "message" => "Error executing query", "error" => mysqli_error($conn));
            }
        } else {
            // Get all students
            $result = mysqli_query($conn, "SELECT * FROM staffs");
            $students = mysqli_fetch_all($result, MYSQLI_ASSOC);

            if ($students) {
                http_response_code(200);
                $response = array("success" => true, "data" => $students);
            } else {
                http_response_code(404);
                $response = array("success" => false, "message" => "No staff found");
            }
        }
        break;
    case 'PUT':
        parse_str(file_get_contents("php://input"), $input);

        if (isset($_GET['action']) && $_GET['action'] == 'update') {
            // Required fields list
            $required_fields = [
                'id', // Assuming 'id' is the primary key for the record to update
                'name',
                'jamiaId',
                'phone',
                'campus',
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

            $id = secure_input($conn, $input, 'id'); // Get the id of the record to update
            $name = secure_input($conn, $input, 'name');
            $image = secure_input($conn, $input, 'image');
            $jamiaId = secure_input($conn, $input, 'jamiaId');
            $staffId = secure_input($conn, $input, 'staffId');
            $email = secure_input($conn, $input, 'email');
            $phone = secure_input($conn, $input, 'phone');
            $dob = secure_input($conn, $input, 'dob');
            $address = secure_input($conn, $input, 'address');
            $district = secure_input($conn, $input, 'district');
            $place = secure_input($conn, $input, 'place');
            $department = secure_input($conn, $input, 'department');
            $ISqualification = secure_input($conn, $input, 'ISqualification');
            $position = secure_input($conn, $input, 'position');
            $role = secure_input($conn, $input, 'role');
            $experience = secure_input($conn, $input, 'experience');
            $campus = secure_input($conn, $input, 'campus');
            $status = secure_input($conn, $input, 'status');

            // SQL query to update the staff record based on the id
            $sql = "UPDATE staffs SET 
                    staff_name = ?, 
                    staff_image = ?, 
                    staff_place = ?, 
                    staff_district = ?, 
                    staff_address = ?, 
                    staff_email = ?, 
                    staff_dob = ?, 
                    staff_cur_campus = ?, 
                    staff_status = ?, 
                    staff_role = ?, 
                    staff_phone = ?, 
                    staff_jamia_id = ?, 
                    staff_fire_id = ?, 
                    staff_position = ?, 
                    staff_department = ?, 
                    IS_qualification = ?, 
                    staff_experience = ? 
                    WHERE staff_id = ?"; // Use 'id' to identify the record

            // Prepare the statement
            $stmt = $conn->prepare($sql);

            // Bind the parameters, ensuring empty fields are treated as empty strings
            $stmt->bind_param(
                "ssssssssssssssssss",
                $name,
                $image, // Assuming you want to update the image as well
                $place,
                $district,
                $address,
                $email,
                $dob,
                $campus,
                $status,
                $role,
                $phone,
                $jamiaId,
                $StaffId,
                $position,
                $department,
                $ISqualification,
                $experience,
                $id // The identifier for the record to update
            );

            // Execute the statement
            if ($stmt->execute()) {
                http_response_code(200);
                $response = array(
                    "success" => true,
                    "message" => "Staff updated successfully"
                );
            } else {
                http_response_code(500);
                $response = array(
                    "success" => false,
                    "message" => "Error updating staff",
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
            $stmt = $conn->prepare("DELETE FROM staffs WHERE staff_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                $response = array("success" => true, "message" => "Staff with ID: $id deleted successfully");
            } else {
                http_response_code(404);  // Record not found
                $response = array("success" => false, "message" => "No staff found with ID: $id", "error" => $conn->error);
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
