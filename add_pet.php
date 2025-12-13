<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Add New Pet Registration">
    <title>VetClinic - Add New Pet</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    /**
     * ============================================================
     * VETERINARY CLINIC MANAGEMENT SYSTEM - ADD NEW PET
     * ============================================================
     * This page allows registering a new pet for an existing client
     * ============================================================
     */
    
    require_once 'db_connect.php';
    
    // Initialize variables
    $message = '';
    $messageType = '';
    $client = null;
    
    // Validate client_id parameter
    if (!isset($_GET['client_id']) || !is_numeric($_GET['client_id'])) {
        header('Location: index.php');
        exit;
    }
    
    $clientId = intval($_GET['client_id']);
    
    // Get database connection
    $conn = getConnection();
    
    if (!$conn) {
        $message = "Database connection failed. Please check your configuration.";
        $messageType = "error";
    } else {
        // Load client information
        $sql = "SELECT Client_ID, Firstname, Middlename, Lastname, Suffix, Address 
                FROM CLIENT WHERE Client_ID = :client_id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':client_id', $clientId);
        
        if (oci_execute($stmt)) {
            $result = oci_fetch_assoc($stmt);
            if ($result) {
                $client = [
                    'id' => $result['CLIENT_ID'],
                    'name' => trim($result['FIRSTNAME'] . ' ' . 
                              ($result['MIDDLENAME'] ? $result['MIDDLENAME'] . ' ' : '') . 
                              $result['LASTNAME'] . 
                              ($result['SUFFIX'] ? ' ' . $result['SUFFIX'] : '')),
                    'address' => $result['ADDRESS']
                ];
            }
        }
        oci_free_statement($stmt);
        
        // Redirect if client not found
        if (!$client) {
            header('Location: index.php?error=client_not_found');
            exit;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_pet') {
            $petName = trim($_POST['pet_name'] ?? '');
            $breed = trim($_POST['breed'] ?? '');
            $birthdate = $_POST['birthdate'] ?? '';
            $markings = trim($_POST['markings'] ?? '');
            
            // Validate required fields
            if (empty($petName)) {
                $message = "Please enter the pet's name.";
                $messageType = "error";
            } else {
                // Insert new pet
                $sql = "INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
                        VALUES (:owner_id, :pet_name, :breed, 
                                " . ($birthdate ? "TO_DATE(:birthdate, 'YYYY-MM-DD')" : "NULL") . ", 
                                :markings)";
                
                $stmt = oci_parse($conn, $sql);
                oci_bind_by_name($stmt, ':owner_id', $clientId);
                oci_bind_by_name($stmt, ':pet_name', $petName);
                oci_bind_by_name($stmt, ':breed', $breed);
                if ($birthdate) {
                    oci_bind_by_name($stmt, ':birthdate', $birthdate);
                }
                oci_bind_by_name($stmt, ':markings', $markings);
                
                if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
                    $message = "Pet '$petName' registered successfully!";
                    $messageType = "success";
                    
                    // Redirect back to client's pet list
                    header("Location: index.php?client_id=$clientId&pet_added=1");
                    exit;
                } else {
                    $error = oci_error($stmt);
                    $message = "Error registering pet: " . $error['message'];
                    $messageType = "error";
                }
                oci_free_statement($stmt);
            }
        }
    }
    ?>
    
    <!-- Page Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1>🏥 VetClinic Management System</h1>
                    <p class="subtitle">Add New Pet</p>
                </div>
                <a href="index.php?client_id=<?php echo $clientId; ?>" class="btn btn-outline">← Back to Pets</a>
            </div>
        </div>
    </header>
    
    <main class="container">
        <!-- Status Message -->
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($client): ?>
        <!-- Client Info Banner -->
        <section class="card info-banner">
            <p><strong>Registering pet for:</strong> <?php echo htmlspecialchars($client['name']); ?></p>
            <p class="muted"><?php echo htmlspecialchars($client['address']); ?></p>
        </section>
        
        <!-- Add Pet Form -->
        <section class="card">
            <h2>🐾 Pet Information</h2>
            <form method="POST" action="add_pet.php?client_id=<?php echo $clientId; ?>" class="form">
                <input type="hidden" name="action" value="add_pet">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="pet_name">Pet Name <span class="required">*</span></label>
                        <input type="text" id="pet_name" name="pet_name" required 
                               placeholder="Enter pet name" maxlength="50"
                               value="<?php echo htmlspecialchars($_POST['pet_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="breed">Breed</label>
                        <input type="text" id="breed" name="breed" 
                               placeholder="e.g., Labrador, Persian Cat" maxlength="50"
                               value="<?php echo htmlspecialchars($_POST['breed'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="birthdate">Birthdate</label>
                        <input type="date" id="birthdate" name="birthdate"
                               max="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="markings">Markings / Description</label>
                        <input type="text" id="markings" name="markings" 
                               placeholder="e.g., Brown with white spots" maxlength="200"
                               value="<?php echo htmlspecialchars($_POST['markings'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-large">
                        🐾 Register Pet
                    </button>
                    <a href="index.php?client_id=<?php echo $clientId; ?>" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </section>
        <?php endif; ?>
    </main>
    
    <!-- Page Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> VetClinic Management System - Vaccination Module</p>
        </div>
    </footer>
    
    <?php
    // Close database connection
    if ($conn) {
        closeConnection($conn);
    }
    ?>
</body>
</html>
