<?php
require_once 'db_connect.php';
session_start();

header('Content-Type: application/json');

$action = $_GET['action'];

try {
    switch ($action) {
        case 'login':
            $email = $_POST['email'];
            $password = $_POST['password'];

            // Check patient
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Check admin
            if (!$user) {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                $role = 'admin';
            } else {
                $role = 'patient';
            }

            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception("Invalid email or password");
            }

            $_SESSION['user'] = $user;
            $_SESSION['role'] = $role;

            echo json_encode([
                'success' => true,
                'role' => $role
            ]);
            break;

        case 'register':
            $name = $_POST['name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("Email already registered");
            }

            $stmt = $pdo->prepare("INSERT INTO patients (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $password]);

            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Please login.'
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