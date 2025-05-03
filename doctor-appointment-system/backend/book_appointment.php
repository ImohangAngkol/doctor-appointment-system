<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    switch ($action) {
        case 'getDoctors':
            $stmt = $pdo->query("SELECT id, name, specialty FROM doctors");
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($doctors);
            break;

        case 'bookAppointment':
            // Validate input
            $required = ['name', 'email', 'phone', 'doctor_id', 'appointment_date'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Check if patient exists or create new
            $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            $patient = $stmt->fetch();

            if (!$patient) {
                $stmt = $pdo->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone']]);
                $patient_id = $pdo->lastInsertId();
            } else {
                $patient_id = $patient['id'];
            }

            // Create appointment
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) 
                                   VALUES (?, ?, ?, 'scheduled')");
            $stmt->execute([$patient_id, $_POST['doctor_id'], $_POST['appointment_date']]);
            $appointment_id = $pdo->lastInsertId();

            // Get the created appointment details
            $stmt = $pdo->prepare("
                SELECT a.*, p.name as patient_name, p.email, p.phone, d.name as doctor_name, d.specialty 
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointment_id]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'appointment' => $appointment,
                'patient' => [
                    'name' => $appointment['patient_name'],
                    'email' => $appointment['email'],
                    'phone' => $appointment['phone']
                ],
                'doctor' => [
                    'name' => $appointment['doctor_name'],
                    'specialty' => $appointment['specialty']
                ]
            ]);
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>