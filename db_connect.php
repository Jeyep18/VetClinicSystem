<?php
/**
 * ============================================================
 * VETERINARY CLINIC MANAGEMENT SYSTEM - DATABASE CONNECTION
 * ============================================================
 * Oracle Database connection using OCI8 driver
 * 
 * REQUIREMENTS:
 * - PHP OCI8 extension enabled
 * - Oracle Instant Client installed
 * ============================================================
 */

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
// Modify these values to match your Oracle database setup

define('DB_USERNAME', 'vet_db');     // Oracle username
define('DB_PASSWORD', 'vetclinic');     // Oracle password
define('DB_CONNECTION', 'localhost/XE');    // Connection string

// Alternative connection strings:
// define('DB_CONNECTION', 'localhost:1521/XE');           // With port
// define('DB_CONNECTION', '192.168.1.100/ORCL');          // Remote server
// define('DB_CONNECTION', '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=XE)))');

// ============================================================
// DATABASE CONNECTION FUNCTION
// ============================================================

/**
 * Establishes a connection to the Oracle database
 * 
 * @return resource|false Returns connection resource on success, false on failure
 */
function getConnection() {
    // Attempt to connect to Oracle database
    $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_CONNECTION, 'AL32UTF8');
    
    // Check if connection was successful
    if (!$conn) {
        $error = oci_error();
        error_log("Oracle Connection Error: " . $error['message']);
        return false;
    }
    
    return $conn;
}

/**
 * Closes an Oracle database connection
 * 
 * @param resource $conn The connection resource to close
 * @return bool Returns true on success
 */
function closeConnection($conn) {
    if ($conn) {
        return oci_close($conn);
    }
    return false;
}

/**
 * Executes a SQL query and returns the statement
 * 
 * @param resource $conn Database connection
 * @param string $sql SQL query to execute
 * @param array $binds Optional array of bind variables ['name' => value]
 * @param bool $autoCommit Whether to auto-commit the transaction
 * @return resource|false Returns statement resource on success, false on failure
 */
function executeQuery($conn, $sql, $binds = [], $autoCommit = false) {
    // Parse the SQL statement
    $stmt = oci_parse($conn, $sql);
    
    if (!$stmt) {
        $error = oci_error($conn);
        error_log("Oracle Parse Error: " . $error['message']);
        return false;
    }
    
    // Bind variables if provided
    foreach ($binds as $name => $value) {
        oci_bind_by_name($stmt, $name, $binds[$name], -1);
    }
    
    // Execute the statement
    $mode = $autoCommit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT;
    $result = oci_execute($stmt, $mode);
    
    if (!$result) {
        $error = oci_error($stmt);
        error_log("Oracle Execute Error: " . $error['message']);
        return false;
    }
    
    return $stmt;
}

/**
 * Fetches all rows from a statement as associative array
 * 
 * @param resource $stmt Statement resource from executeQuery
 * @return array Array of rows
 */
function fetchAll($stmt) {
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Commits the current transaction
 * 
 * @param resource $conn Database connection
 * @return bool Returns true on success
 */
function commitTransaction($conn) {
    return oci_commit($conn);
}

/**
 * Rolls back the current transaction
 * 
 * @param resource $conn Database connection
 * @return bool Returns true on success
 */
function rollbackTransaction($conn) {
    return oci_rollback($conn);
}

/**
 * Gets the last error message from Oracle
 * 
 * @param resource|null $resource Connection or statement resource
 * @return string Error message
 */
function getOracleError($resource = null) {
    $error = oci_error($resource);
    return $error ? $error['message'] : 'Unknown error';
}

/**
 * Formats a PHP date to Oracle date format
 * 
 * @param string $date Date string in Y-m-d format
 * @return string Oracle formatted date
 */
function toOracleDate($date) {
    if (empty($date)) return null;
    $timestamp = strtotime($date);
    return date('d-M-Y', $timestamp); // DD-MON-YYYY format for Oracle
}

/**
 * Formats an Oracle date to PHP/HTML date format
 * 
 * @param string $oracleDate Oracle date string
 * @return string PHP formatted date (Y-m-d)
 */
function fromOracleDate($oracleDate) {
    if (empty($oracleDate)) return '';
    $timestamp = strtotime($oracleDate);
    return date('Y-m-d', $timestamp);
}
?>
