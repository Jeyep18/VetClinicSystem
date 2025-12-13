<?php
/**
 * PHP Info & Oracle Connection Test
 * Access this at: http://localhost:8080/test_connection.php
 */
echo "<h1>VetClinic - Connection Test</h1>";
echo "<hr>";
// Check PHP version
echo "<h2>1. PHP Version</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
// Check if OCI8 extension is loaded
echo "<h2>2. OCI8 Extension Status</h2>";
if (extension_loaded('oci8')) {
    echo "<p style='color: green; font-weight: bold;'>✅ OCI8 extension is LOADED</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ OCI8 extension is NOT LOADED</p>";
    echo "<p>You need to enable the OCI8 extension in php.ini</p>";
    echo "<p>Your php.ini location: " . php_ini_loaded_file() . "</p>";
}
// Check loaded extensions
echo "<h2>3. Loaded Extensions (related to Oracle)</h2>";
$extensions = get_loaded_extensions();
$oracle_related = array_filter($extensions, function($ext) {
    return stripos($ext, 'oci') !== false || stripos($ext, 'oracle') !== false || stripos($ext, 'pdo') !== false;
});
if (empty($oracle_related)) {
    echo "<p>No Oracle-related extensions found.</p>";
} else {
    echo "<ul>";
    foreach ($oracle_related as $ext) {
        echo "<li>$ext</li>";
    }
    echo "</ul>";
}
// Try to connect if OCI8 is available
echo "<h2>4. Oracle Connection Test</h2>";
if (extension_loaded('oci8')) {
    // Include credentials
    $username = 'vet_db';
    $password = 'vetclinic';
    $connection_string = 'localhost/XE';
    
    echo "<p>Attempting to connect to: $connection_string</p>";
    
    // Suppress errors to catch them manually
    $conn = @oci_connect($username, $password, $connection_string, 'AL32UTF8');
    
    if ($conn) {
        echo "<p style='color: green; font-weight: bold;'>✅ Successfully connected to Oracle!</p>";
        
        // Test query
        $stmt = oci_parse($conn, "SELECT Vet_ID, Firstname || ' ' || NVL(Middlename || ' ', '') || Lastname || NVL(' ' || Suffix, '') AS Vet_Name FROM VETERINARIAN");
        if (oci_execute($stmt)) {
            echo "<p>Query test passed. Veterinarians in database:</p>";
            echo "<ul>";
            while ($row = oci_fetch_assoc($stmt)) {
                echo "<li>" . htmlspecialchars($row['VET_NAME']) . "</li>";
            }
            echo "</ul>";
        }
        oci_close($conn);
    } else {
        $error = oci_error();
        echo "<p style='color: red; font-weight: bold;'>❌ Connection FAILED</p>";
        echo "<p>Error Code: " . ($error['code'] ?? 'Unknown') . "</p>";
        echo "<p>Error Message: " . ($error['message'] ?? 'No message') . "</p>";
        
        echo "<h3>Common Solutions:</h3>";
        echo "<ul>";
        echo "<li><strong>ORA-12541</strong>: Oracle listener not running. Start Oracle services.</li>";
        echo "<li><strong>ORA-01017</strong>: Wrong username/password. Check db_connect.php credentials.</li>";
        echo "<li><strong>ORA-12514</strong>: Service name wrong. Try 'localhost/XEPDB1' instead of 'localhost/XE'.</li>";
        echo "<li><strong>ORA-12560</strong>: TNS protocol adapter error. Oracle service not started.</li>";
        echo "</ul>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Cannot test connection - OCI8 extension not loaded</p>";
}
echo "<hr>";
echo "<p><a href='index.php'>← Back to Dashboard</a></p>";
?>