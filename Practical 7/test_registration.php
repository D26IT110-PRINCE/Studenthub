<?php
/**
 * Test Registration Script
 * Run this via PHP CLI to test the CSV storage functionality
 */

require_once 'process_registration.php';

// Test data
$testData = [
    'name' => 'Test Student',
    'email' => 'test@example.com',
    'mobile' => '9876543210',
    'password' => 'Test@1234',
    'confirm-password' => 'Test@1234',
    'course' => 'btech',
    'year' => '2',
    'gender' => 'male',
    'terms' => 'on'
];

// Simulate POST
$_POST = $testData;
$_POST['csrf_token'] = 'test_token';

// Test email validation
echo "Testing email validation...\n";
[$email, $errors] = validateEmail('test@example.com');
echo "Valid email: " . ($errors ? "FAIL - " . implode(", ", $errors) : "PASS") . "\n";

[$email, $errors] = validateEmail('invalid-email');
echo "Invalid email: " . ($errors ? "PASS - " . implode(", ", $errors) : "FAIL") . "\n";

// Test mobile validation
echo "\nTesting mobile validation...\n";
[$mobile, $errors] = validateMobile('9876543210');
echo "Valid mobile: " . ($errors ? "FAIL - " . implode(", ", $errors) : "PASS") . "\n";

[$mobile, $errors] = validateMobile('+919876543210');
echo "Valid mobile with +91: " . ($errors ? "FAIL - " . implode(", ", $errors) : "PASS") . "\n";

[$mobile, $errors] = validateMobile('1234567890');
echo "Invalid mobile: " . ($errors ? "PASS - " . implode(", ", $errors) : "FAIL") . "\n";

// Test password validation
echo "\nTesting password validation...\n";
[$password, $errors] = validatePassword('Test@1234');
echo "Valid password: " . ($errors ? "FAIL - " . implode(", ", $errors) : "PASS") . "\n";

[$password, $errors] = validatePassword('weak');
echo "Weak password: " . ($errors ? "PASS - " . implode(", ", $errors) : "FAIL") . "\n";

// Test name validation
echo "\nTesting name validation...\n";
[$name, $errors] = validateName('John Doe');
echo "Valid name: " . ($errors ? "FAIL - " . implode(", ", $errors) : "PASS") . "\n";

[$name, $errors] = validateName('J');
echo "Short name: " . ($errors ? "PASS - " . implode(", ", $errors) : "FAIL") . "\n";

[$name, $errors] = validateName('John123');
echo "Name with numbers: " . ($errors ? "PASS - " . implode(", ", $errors) : "FAIL") . "\n";

// Test CSV write
echo "\nTesting CSV write...\n";
$testData['password_hash'] = password_hash('Test@1234', PASSWORD_DEFAULT);
if (writeRegistrationToCsv($testData)) {
    echo "CSV write: PASS\n";
} else {
    echo "CSV write: FAIL\n";
}

// Test reading back
echo "\nTesting CSV read...\n";
$registrations = readRegistrations();
if (!empty($registrations)) {
    $last = end($registrations);
    echo "Last registration: " . $last['name'] . " (" . $last['email'] . ")\n";
    echo "CSV read: PASS\n";
} else {
    echo "CSV read: FAIL - no records found\n";
}

// Test duplicate email check
echo "\nTesting duplicate email check...\n";
if (emailExists('test@example.com')) {
    echo "Duplicate detection: PASS\n";
} else {
    echo "Duplicate detection: FAIL\n";
}

echo "\nAll tests completed!\n";