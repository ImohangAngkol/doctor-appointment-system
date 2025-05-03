<?php
require_once 'db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

session_start();

// Debugging: Log session and request data
error_log("Session data: " . print_r($_SESSION, true));
error_log("Request action: " . ($_GET['action'] ?? 'none'));

try {
    if (!isset($_GET['action'])) {
        throw new Exception("No action specified");
    }

    $action = $_GET['action'];

    switch ($action) {
        case 'getPatientAppointments':
            // Enhanced session validation
            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
                throw new Exception("Unauthorized - No user session");
            }
            
            $patientId = $_SESSION['user']['id'];
            error_log("Fetching appointments for patient ID: $patientId");
            
            $stmt = $pdo->prepare("
                SELECT a.*, d.name as doctor_name, d.specialty 
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                WHERE a.patient_id = ?
                ORDER BY a.appointment_date DESC
            ");
            $stmt->execute([$patientId]);
            $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug output
            error_log("Found " . count($appointments) . " appointments");
            echo json_encode($appointments);
            break;

        case 'getAll':
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception("Unauthorized - Admin access required");
            }
            
            $stmt = $pdo->query("
                SELECT a.*, p.name as patient_name, p.email, d.name as doctor_name, d.specialty 
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                ORDER BY a.appointment_date DESC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        

        default:
            throw new Exception("Invalid action: $action");
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>