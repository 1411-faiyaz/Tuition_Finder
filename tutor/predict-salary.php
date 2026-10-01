<?php
/**
 * AJAX endpoint for the Tutor Salary Prediction Assistant.
 * Tutor-only, both here (server-side session check) and one layer deeper
 * (the shared API key sent on to the Python service in config/backend.php).
 * A guardian/student calling this URL directly gets redirected by
 * require_role() below, exactly like messages/fetch.php and
 * messages/send.php already do for other AJAX endpoints in this app.
 */
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor'], '../login.php', '../index.php');
require_once __DIR__ . '/../config/backend.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

// Allow-lists mirror the categories the model was trained on
// (backend/data/tuition_salary_data_5000.csv) and backend/main.py's
// own Literal[] validation. Keep all three in sync.
$ALLOWED_PLACES  = ['Badda', 'Banani', 'Gulshan', 'Khilkhet', 'Middle Badda', 'Mirpur',
                     'Mohakhali', 'New Market', 'Notun Bazar', 'Rampura', 'Shahbag', 'Uttara'];
$ALLOWED_VERSION = ['Bangla', 'English Medium', 'English Version'];
$ALLOWED_SUBJECT = ['All Subjects', 'Bangla', 'Biology', 'Chemistry', 'English',
                     'General Science', 'ICT', 'Math', 'Physics'];

$place       = trim($_POST['place'] ?? '');
$classNumber = (int)($_POST['class_number'] ?? 0);
$version     = trim($_POST['version'] ?? '');
$subject     = trim($_POST['subject'] ?? '');
$daysPerWeek = (int)($_POST['days_per_week'] ?? 0);
$hoursPerDay = (float)($_POST['hours_per_day'] ?? 0);

$errors = [];
if (!in_array($place, $ALLOWED_PLACES, true))   $errors[] = 'Please choose a valid area.';
if ($classNumber < 1 || $classNumber > 12)      $errors[] = 'Class must be between 1 and 12.';
if (!in_array($version, $ALLOWED_VERSION, true)) $errors[] = 'Please choose a valid curriculum.';
if (!in_array($subject, $ALLOWED_SUBJECT, true)) $errors[] = 'Please choose a valid subject.';
if ($daysPerWeek < 1 || $daysPerWeek > 7)       $errors[] = 'Days per week must be between 1 and 7.';
if ($hoursPerDay < 0.5 || $hoursPerDay > 6)     $errors[] = 'Hours per day must be between 0.5 and 6.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

$payload = json_encode([
    'place'         => $place,
    'class_number'  => $classNumber,
    'version'       => $version,
    'subject'       => $subject,
    'days_per_week' => $daysPerWeek,
    'hours_per_day' => $hoursPerDay,
]);

$ch = curl_init($SALARY_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: ' . $SALARY_API_KEY,
    ],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_CONNECTTIMEOUT => 3,
]);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$FRIENDLY_ERROR = "Sorry, I couldn't generate the salary prediction right now. Please try again.";

if ($curlErrno || $response === false) {
    error_log('Salary prediction service unreachable: ' . $curlError);
    http_response_code(503);
    echo json_encode(['error' => $FRIENDLY_ERROR]);
    exit;
}

$decoded = json_decode($response, true);

if ($httpCode !== 200 || !isset($decoded['predicted_salary'])) {
    error_log('Salary prediction service error: HTTP ' . $httpCode . ' - ' . $response);
    http_response_code(502);
    echo json_encode(['error' => $FRIENDLY_ERROR]);
    exit;
}

echo json_encode(['predicted_salary' => (int)$decoded['predicted_salary']]);
