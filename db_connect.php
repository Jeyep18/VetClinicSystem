<?php
/**
 * VETERINARY CLINIC MANAGEMENT SYSTEM - DATABASE CONNECTION
 * Oracle Database connection using OCI8 driver
 * 
 * REQUIREMENTS:
 * - PHP OCI8 extension 
 * - Oracle Instant Client 
 */

// DATABASE CONFIGURATION
define('DB_USERNAME', 'vet_db');     
define('DB_PASSWORD', 'vetclinic');     
define('DB_CONNECTION', 'localhost/XE');    

function getConnection() {
    $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_CONNECTION, 'AL32UTF8');
    
    if (!$conn) {
        $error = oci_error();
        error_log("Oracle Connection Error: " . $error['message']);
        return false;
    }
    
    return $conn;
}

function closeConnection($conn) {
    if ($conn) {
        return oci_close($conn);
    }
    return false;
}

function executeQuery($conn, $sql, $binds = [], $autoCommit = false) {
    // Parse SQL statement
    $stmt = oci_parse($conn, $sql);
    
    if (!$stmt) {
        $error = oci_error($conn);
        error_log("Oracle Parse Error: " . $error['message']);
        return false;
    }
    
    foreach ($binds as $name => $value) {
        oci_bind_by_name($stmt, $name, $binds[$name], -1);
    }
    
    // Execute statement
    $mode = $autoCommit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT;
    $result = oci_execute($stmt, $mode);
    
    if (!$result) {
        $error = oci_error($stmt);
        error_log("Oracle Execute Error: " . $error['message']);
        return false;
    }
    
    return $stmt;
}

function fetchAll($stmt) {
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $rows[] = $row;
    }
    return $rows;
}

function commitTransaction($conn) {
    return oci_commit($conn);
}


function rollbackTransaction($conn) {
    return oci_rollback($conn);
}

function getOracleError($resource = null) {
    $error = oci_error($resource);
    return $error ? $error['message'] : 'Unknown error';
}

function toOracleDate($date) {
    if (empty($date)) return null;
    $timestamp = strtotime($date);
    return date('d-M-Y', $timestamp); // DD-MON-YYYY format
}

function fromOracleDate($oracleDate) {
    if (empty($oracleDate)) return '';
    $timestamp = strtotime($oracleDate);
    return date('Y-m-d', $timestamp);
}
?>
