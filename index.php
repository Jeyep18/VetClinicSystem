<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PPL Paws & Tails VetClinic Management System - Vaccination Management Dashboard">
    <title>PPL Paws & Tails Vaccination Module</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    //VETERINARY CLINIC MANAGEMENT SYSTEM - MAIN DASHBOARD

    require_once 'db_connect.php';
    
    $message = '';
    $messageType = '';
    $searchResults = [];
    $selectedClientPets = [];
    $selectedClientName = '';
    
    // Get connection
    $conn = getConnection();
    
    if (!$conn) {
        $message = "Database connection failed. Please check your configuration.";
        $messageType = "error";
    }
    
    // HANDLE FORM SUBMISSIONS
    
    // New Client Registration
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        
        if ($_POST['action'] === 'register_client' && $conn) {
            $firstname = trim($_POST['firstname'] ?? '');
            $middlename = trim($_POST['middlename'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $suffix = trim($_POST['suffix'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $contactNumber = trim($_POST['contact_number'] ?? '');
            
            // Validate required fields
            if (empty($firstname) || empty($lastname) || empty($address)) {
                $message = "Please fill in all required fields (First Name, Last Name, Address).";
                $messageType = "error";
            } else {
                // Use transaction to insert client and contact number
                try {
                    $clientId = null;
                    
                    // Step 1: Insert into CLIENT and get the generated Client_ID
                    $sql = "INSERT INTO CLIENT (Firstname, Middlename, Lastname, Suffix, Address) 
                            VALUES (:firstname, :middlename, :lastname, :suffix, :address)
                            RETURNING Client_ID INTO :client_id";
                    
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':firstname', $firstname);
                    oci_bind_by_name($stmt, ':middlename', $middlename);
                    oci_bind_by_name($stmt, ':lastname', $lastname);
                    oci_bind_by_name($stmt, ':suffix', $suffix);
                    oci_bind_by_name($stmt, ':address', $address);
                    oci_bind_by_name($stmt, ':client_id', $clientId, 20);
                    
                    $resultClient = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
                    
                    if (!$resultClient) {
                        throw new Exception("Failed to create client record");
                    }
                    oci_free_statement($stmt);
                    
                    // Step 2: Insert into CLIENT_CONTACT if contact number provided
                    if (!empty($contactNumber)) {
                        $sqlContact = "INSERT INTO CLIENT_CONTACT (Client_ID, Contact_Number) 
                                       VALUES (:client_id, :contact_number)";
                        
                        $stmtContact = oci_parse($conn, $sqlContact);
                        oci_bind_by_name($stmtContact, ':client_id', $clientId);
                        oci_bind_by_name($stmtContact, ':contact_number', $contactNumber);
                        
                        $resultContact = oci_execute($stmtContact, OCI_NO_AUTO_COMMIT);
                        
                        if (!$resultContact) {
                            throw new Exception("Failed to save contact number");
                        }
                        oci_free_statement($stmtContact);
                    }
                    
                    // Step 3: Commit the transaction
                    if (commitTransaction($conn)) {
                        $message = "Client '$firstname $lastname' registered successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Failed to commit transaction");
                    }
                    
                } catch (Exception $e) {
                    rollbackTransaction($conn);
                    $message = "Error registering client: " . $e->getMessage();
                    $messageType = "error";
                }
            }
        }
    }
    
    // Client Search
    if ($conn) {
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        if (!empty($searchTerm)) {
            // Filter by search term
            $sql = "SELECT Client_ID, Firstname, Middlename, Lastname, Suffix, Address 
                    FROM CLIENT 
                    WHERE UPPER(Lastname) LIKE UPPER(:search) 
                       OR UPPER(Firstname) LIKE UPPER(:search)
                    ORDER BY Client_ID";
            
            $stmt = oci_parse($conn, $sql);
            $searchPattern = '%' . $searchTerm . '%';
            oci_bind_by_name($stmt, ':search', $searchPattern);
        } else {
            // Load all clients on initial page load
            $sql = "SELECT Client_ID, Firstname, Middlename, Lastname, Suffix, Address 
                    FROM CLIENT 
                    ORDER BY Client_ID";
            
            $stmt = oci_parse($conn, $sql);
        }
        
        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $searchResults[] = $row;
            }
        }
        oci_free_statement($stmt);
    }
    
    // View Pets for a Client
    if (isset($_GET['client_id']) && $conn) {
        $clientId = intval($_GET['client_id']);
        
        // Get client name
        $sql = "SELECT Firstname, Middlename, Lastname, Suffix FROM CLIENT WHERE Client_ID = :client_id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':client_id', $clientId);
        
        if (oci_execute($stmt)) {
            $client = oci_fetch_assoc($stmt);
            if ($client) {
                $selectedClientName = trim($client['FIRSTNAME'] . ' ' . 
                    ($client['MIDDLENAME'] ? $client['MIDDLENAME'] . ' ' : '') . 
                    $client['LASTNAME'] . 
                    ($client['SUFFIX'] ? ' ' . $client['SUFFIX'] : ''));
            }
        }
        oci_free_statement($stmt);
        
        // Get pets for this client
        $sql = "SELECT Pet_ID, Pet_Name, Breed, Birthdate, Markings 
                FROM PET 
                WHERE Owner_ID = :client_id 
                ORDER BY Pet_Name";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':client_id', $clientId);
        
        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $selectedClientPets[] = $row;
            }
        }
        oci_free_statement($stmt);
    }
    ?>
    
    <!-- Page Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-brand">
                    <img src="assets/Logo.svg" alt="PPL Paws & Tails Logo" class="header-logo">
                    <div class="header-text">
                        <h1>PPL Paws & Tails</h1>
                        <p class="subtitle">Vaccination Management System</p>
                    </div>
                </div>
                <p class="header-motto">"Compassionate care for your furry friends."</p>
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
        
        <div class="dashboard-grid">

            <!-- NEW CLIENT REGISTRATION -->

            <section class="card">
                <h2><img src="assets/icons/file-person.svg" alt="" class="section-icon"> New Client Registration</h2>
                <form method="POST" action="index.php" class="form">
                    <input type="hidden" name="action" value="register_client">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname">First Name <span class="required">*</span></label>
                            <input type="text" id="firstname" name="firstname" required 
                                   placeholder="Enter first name" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="middlename">Middle Name</label>
                            <input type="text" id="middlename" name="middlename" 
                                   placeholder="Enter M.I." maxlength="50">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lastname">Last Name <span class="required">*</span></label>
                            <input type="text" id="lastname" name="lastname" required 
                                   placeholder="Enter last name" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <input type="text" id="suffix" name="suffix" 
                                   placeholder="Jr., Sr., III" maxlength="10">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="address">Address <span class="required">*</span></label>
                            <textarea id="address" name="address" required 
                                      placeholder="Enter complete address (Street, Barangay, City)" maxlength="200" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number" 
                                   placeholder="e.g., 09171234567" maxlength="20"
                                   pattern="[0-9]{10,11}">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">Register Client</button>
                </form>
            </section>

            <!-- CLIENT SEARCH -->

            <section class="card">
                <h2><img src="assets/icons/search.svg" alt="" class="section-icon"> Search Client</h2>
                <form method="GET" action="index.php" class="form search-form">
                    <div class="form-group">
                        <label for="search">Search by Name</label>
                        <div class="search-row">
                            <input type="text" id="search" name="search" 
                                   placeholder="Enter client name..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button type="submit" class="btn btn-secondary">Search</button>
                        </div>
                    </div>
                </form>
                
                <!-- Client List -->
                 
                <?php if (!empty($searchResults)): ?>
                <?php $searchTerm = trim($_GET['search'] ?? ''); ?>
                <h3 class="results-heading"><?php echo $searchTerm ? 'Search Results' : 'Registered Clients'; ?> (<?php echo count($searchResults); ?> found)</h3>
                <div class="results-section">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['CLIENT_ID']); ?></td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars(
                                        $client['FIRSTNAME'] . ' ' . 
                                        ($client['MIDDLENAME'] ? $client['MIDDLENAME'] . ' ' : '') . 
                                        $client['LASTNAME'] . 
                                        ($client['SUFFIX'] ? ' ' . $client['SUFFIX'] : '')
                                    ); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($client['ADDRESS']); ?></td>
                                <td>
                                    <a href="index.php?client_id=<?php echo $client['CLIENT_ID']; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>" 
                                       class="btn btn-small">View Pets</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php elseif (isset($_GET['search'])): ?>
                <p class="no-results">No clients found matching your search.</p>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- PET LIST FOR SELECTED CLIENT -->

        <?php if (isset($_GET['client_id'])): ?>
        <section class="card full-width">
            <div class="card-header">
                <h2><img src="assets/icons/pet-svgrepo-com.svg" alt="" class="section-icon"> Pets for: <?php echo htmlspecialchars($selectedClientName); ?></h2>
                <a href="add_pet.php?client_id=<?php echo intval($_GET['client_id']); ?>" 
                   class="btn btn-primary">+ Add New Pet</a>
            </div>
            
            <?php if (!empty($selectedClientPets)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pet ID</th>
                        <th>Name</th>
                        <th>Breed</th>
                        <th>Birthdate</th>
                        <th>Markings</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($selectedClientPets as $pet): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pet['PET_ID']); ?></td>
                        <td><strong><?php echo htmlspecialchars($pet['PET_NAME']); ?></strong></td>
                        <td><?php echo htmlspecialchars($pet['BREED'] ?? 'N/A'); ?></td>
                        <td><?php echo $pet['BIRTHDATE'] ? date('M d, Y', strtotime($pet['BIRTHDATE'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($pet['MARKINGS'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="pet_record.php?pet_id=<?php echo $pet['PET_ID']; ?>" 
                               class="btn btn-accent">View / Vaccinate</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="no-results">No pets registered for this client. 
                <a href="add_pet.php?client_id=<?php echo intval($_GET['client_id']); ?>">Add a pet now</a>.</p>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </main>
    
    <!-- Page Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PPL Paws and Tails VetClinic Management System <br> Vaccination Management System Prototype</p>
        </div>
    </footer>
    
    <?php
    // Close connection
    if ($conn) {
        closeConnection($conn);
    }
    ?>
</body>
</html>
