<?php
/**
 * Database Connection - PDO with Exception Handling
 * Reusable connection class for StudentHub MySQL database
 *
 * Configuration: mysql:host=localhost;dbname=studenthub;charset=utf8mb4
 * Error Mode: PDO::ERRMODE_EXCEPTION
 */

// Prevent direct access
if (basename($_SERVER['SCRIPT_FILENAME']) === 'db_connect.php') {
    http_response_code(403);
    exit('Direct access forbidden');
}

/**
 * Database Configuration Constants
 * In production, these should be in a separate config file outside web root
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'studenthub');
define('DB_CHARSET', 'utf8mb4');
define('DB_USER', 'root');      // Change for your environment
define('DB_PASS', '');          // Change for your environment (empty for XAMPP default)

/**
 * DSN (Data Source Name) for MySQL connection
 */
define('DB_DSN', "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET);

/**
 * PDO Options for secure and efficient connections
 */
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
]);

/**
 * Get a new PDO database connection
 *
 * @return PDO Database connection instance
 * @throws PDOException If connection fails
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Database connection unavailable. Please try again later.", 0, $e);
        }
    }

    return $pdo;
}

/**
 * Execute a SELECT query and return all results
 */
function dbSelectAll(string $sql, array $params = []): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a SELECT query and return single row
 */
function dbSelectOne(string $sql, array $params = [])
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Execute an INSERT query and return the new row ID
 */
function dbInsert(string $sql, array $params = [])
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        return (int)$pdo->lastInsertId();
    }
    return false;
}

/**
 * Execute an UPDATE or DELETE query and return affected rows
 */
function dbExecute(string $sql, array $params = []): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Begin a database transaction
 */
function dbBeginTransaction(): bool
{
    $pdo = getDbConnection();
    return $pdo->beginTransaction();
}

/**
 * Commit a database transaction
 */
function dbCommit(): bool
{
    $pdo = getDbConnection();
    return $pdo->commit();
}

/**
 * Rollback a database transaction
 */
function dbRollback(): bool
{
    $pdo = getDbConnection();
    return $pdo->rollBack();
}

/**
 * Check if a record exists in a table
 */
function dbExists(string $table, array $conditions): bool
{
    $where = [];
    foreach ($conditions as $key => $value) {
        $where[] = "`$key` = :$key";
    }

    $sql = "SELECT 1 FROM `$table` WHERE " . implode(' AND ', $where) . " LIMIT 1";
    return dbSelectOne($sql, $conditions) !== false;
}

/**
 * Sanitize identifier (table/column names) to prevent SQL injection
 */
function sanitizeIdentifier(string $identifier): string
{
    return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
}