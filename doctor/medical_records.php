<?php
require_once '../config/db.php';
check_login('doctor');

$doctor_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle adding medical record
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_record'])) {
    $patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $diagnosis = isset($_POST['diagnosis']) ? sanitize_input($_POST['diagnosis']) : '';
    $symptoms = isset($_POST['symptoms']) ? sanitize_input($_POST['symptoms']) : '';
    $treatment = isset($_POST['treatment']) ? sanitize_input($_POST['treatment']) : '';
    $notes = isset($_POST['notes']) 