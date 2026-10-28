<?php
/**
 * Database Connection Test Script
 * Tests the PDO connection to verify MySQL connectivity
 *
 * This script verifies that:
 * 1. Database connection can be established
 * 2. Basic queries work correctly
 * 3. Error handling is in place
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_test.php') {
    // Allow access for test scripts
    define('TEST_MODE', true);
} else {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Include database connection file
require_once __DIR__ . '/db_connect.php';

try {
    echo "<h2>Database Connection Test</h2>\n";
    echo "<p><strong>Attempting connection to:</strong> " . DB_DSN . "</p>\n";

    // Create connection and test
    $pdo = getDbConnection();

    // Test 1: Get server info
    $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    echo "<p><strong>Server version:</strong> " . htmlspecialchars($serverInfo) . "</p>\n";

    // Test 2: Get database info
    // Note: PDO does not have a built-in constant for database name.
    // We query the database for its name instead.
    $dbStmt = $pdo->query("SELECT DATABASE()");
    $dbInfo = $dbStmt->fetchColumn();
    echo "<p><strong>Database name:</strong> " . htmlspecialchars($dbInfo) . "</p>\n";

    // Test 3: Run a simple query
    $testQuery = "SELECT 1 AS `test_result`, NOW() AS `current_time`";
    $result = dbSelectOne($testQuery);

    if ($result) {
        echo "<p><strong>Query successful!</strong> Result: " . htmlspecialchars($result['test_result']) . " (at " . htmlspecialchars($result['current_time']) . ")</p>\n";
    } else {
        echo "<p><strong>Query failed!</strong> No results returned.</p>\n";
    }

    // Test 4: Check connection status
    if ($pdo->inTransaction()) {
        echo "<p><strong>Connection:</strong> In transaction</p>\n";
    } else {
        echo "<p><strong>Connection:</strong> Not in transaction</p>\n";
    }

    // Test 5: Test a SELECT on students table
    $countQuery = "SELECT COUNT(*) AS total_students FROM students";
    $countResult = dbSelectOne($countQuery);

    if ($countResult) {
        echo "<p><strong>Student count:</strong> " . $countResult['total_students'] . " active students</p>\n";
    } else {
        echo "<p><strong>Student count query failed!</strong></p>\n";
    }

    echo "<h3>Test Complete</h3>";
    echo "<p>If you see this message, your database connection is working correctly.</p>";

} catch (PDOException $e) {
    // Handle connection errors
    echo "<h2>Database Connection Failed</h2>\n";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>SQL State:</strong> " . htmlspecialchars($e->getCode()) . "</p>\n";
    echo "<p><strong>Previous error:</strong> " . htmlspecialchars($e->getPrevious() ? $e->getPrevious()->getMessage() : 'None') . "</p>\n";

    // Log the detailed error for debugging (not shown to user)
    error_log("DB Test Error: " . $e->getMessage());
    echo "<p><em>Note: Detailed error has been logged for debugging.</em></p>";

    // Exit with error status
    exit(1);
}