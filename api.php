<?php
// api.php
// API backend for RSVP submissions and check-in confirmation

header('Content-Type: application/json');
require_once 'config.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Read JSON input or form POST
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

switch ($action) {
    case 'submit_rsvp':
        $code = trim($input['code'] ?? '');
        $rsvp_status = trim($input['rsvp_status'] ?? 'Attending');
        $companion_type = trim($input['companion_type'] ?? 'none');
        $whatsapp = trim($input['whatsapp'] ?? '');

        // Validate values
        if (!in_array($rsvp_status, ['Attending', 'Absent'])) {
            $rsvp_status = 'Attending';
        }
        if (!in_array($companion_type, ['none', 'parents', 'sibling'])) {
            $companion_type = 'none';
        }

        if (!empty($code)) {
            // Pre-seeded student update
            $stmt = $pdo->prepare("SELECT * FROM students WHERE code = ?");
            $stmt->execute([$code]);
            $student = $stmt->fetch();

            if (!$student) {
                echo json_encode(['success' => false, 'message' => 'Invitation code not found.']);
                exit;
            }

            $update = $pdo->prepare("UPDATE students SET rsvp_status = ?, companion_type = ?, whatsapp = ? WHERE code = ?");
            $update->execute([$rsvp_status, $companion_type, $whatsapp, $code]);

            echo json_encode([
                'success' => true,
                'message' => 'RSVP updated successfully.',
                'student' => [
                    'code' => $code,
                    'name' => $student['name'],
                    'classroom' => $student['classroom'],
                    'rsvp_status' => $rsvp_status,
                    'companion_type' => $companion_type
                ]
            ]);
        } else {
            // General guest self-registration
            $name = trim($input['name'] ?? '');
            $classroom = trim($input['classroom'] ?? 'Guest');

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Name is required.']);
                exit;
            }

            $newCode = generateUniqueCode($pdo);
            $insert = $pdo->prepare("INSERT INTO students (code, name, classroom, whatsapp, rsvp_status, companion_type) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$newCode, $name, $classroom, $whatsapp, $rsvp_status, $companion_type]);

            echo json_encode([
                'success' => true,
                'message' => 'Registration successful.',
                'student' => [
                    'code' => $newCode,
                    'name' => $name,
                    'classroom' => $classroom,
                    'rsvp_status' => $rsvp_status,
                    'companion_type' => $companion_type
                ]
            ]);
        }
        break;

    case 'checkin':
        $code = trim($input['code'] ?? '');

        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Invitation code is required.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM students WHERE code = ?");
        $stmt->execute([$code]);
        $student = $stmt->fetch();

        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student or guest not found.']);
            exit;
        }

        // Mark as checked in
        $now = date('Y-m-d H:i:s');
        $update = $pdo->prepare("UPDATE students SET checked_in = 1, checked_in_at = ? WHERE code = ?");
        $update->execute([$now, $code]);

        echo json_encode([
            'success' => true,
            'message' => 'Check-in successful!',
            'student' => [
                'name' => $student['name'],
                'classroom' => $student['classroom'],
                'companion_type' => $student['companion_type'],
                'checked_in_at' => $now,
                'already_checked_in' => ($student['checked_in'] == 1)
            ]
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
?>
