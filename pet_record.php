<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pet Vaccination Record and History">
    <title>PPL Paws & Tails - Pet Vaccination Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    // PET VACCINATION RECORD
    
    require_once 'db_connect.php';
    
    $message = '';
    $messageType = '';
    $pet = null;
    $owner = null;
    $vaccinationHistory = [];
    $veterinarians = [];
    
    if (!isset($_GET['pet_id']) || !is_numeric($_GET['pet_id'])) {
        header('Location: index.php');
        exit;
    }
    
    $petId = intval($_GET['pet_id']);
    
    // Get connection
    $conn = getConnection();
    
    if (!$conn) {
        $message = "Database connection failed. Please check your configuration.";
        $messageType = "error";
    } else {

        // LOAD PET AND OWNER INFORMATION

        $sql = "SELECT p.Pet_ID, p.Pet_Name, p.Breed, p.Birthdate, p.Markings,
                       c.Client_ID, c.Firstname, c.Middlename, c.Lastname, c.Suffix, c.Address
                FROM PET p
                JOIN CLIENT c ON p.Owner_ID = c.Client_ID
                WHERE p.Pet_ID = :pet_id";
        
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':pet_id', $petId);
        
        if (oci_execute($stmt)) {
            $result = oci_fetch_assoc($stmt);
            if ($result) {
                $pet = [
                    'id' => $result['PET_ID'],
                    'name' => $result['PET_NAME'],
                    'breed' => $result['BREED'],
                    'birthdate' => $result['BIRTHDATE'],
                    'markings' => $result['MARKINGS']
                ];
                $owner = [
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
        
        // Redirect if pet not found
        if (!$pet) {
            header('Location: index.php?error=pet_not_found');
            exit;
        }
        
        // LOAD VETERINARIANS FOR DROPDOWN

        $sql = "SELECT Vet_ID, 
                       Firstname || ' ' || NVL(Middlename || ' ', '') || Lastname || NVL(' ' || Suffix, '') AS Vet_Name 
                FROM VETERINARIAN ORDER BY Lastname, Firstname";
        $stmt = oci_parse($conn, $sql);
        
        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $veterinarians[] = $row;
            }
        }
        oci_free_statement($stmt);
        
        // LOAD VACCINATION HISTORY
        
        $sql = "SELECT v.Vaccination_ID, v.Vaccine_Name, v.Against, v.Manufacturer, 
                       v.Lot_No, v.Next_Schedule,
                       vr.Visit_ID, vr.Visit_Date, vr.Pet_Weight,
                       vet.Firstname || ' ' || NVL(vet.Middlename || ' ', '') || vet.Lastname || NVL(' ' || vet.Suffix, '') AS Vet_Name,
                       NVL(p.Payment_Status, 'PENDING') AS Payment_Status,
                       p.Payment_ID, p.Amount, p.Payment_Method, p.Payment_Date
                FROM VACCINATION v
                JOIN VISIT_RECORD vr ON v.Visit_ID = vr.Visit_ID
                JOIN VETERINARIAN vet ON vr.Vet_ID = vet.Vet_ID
                LEFT JOIN PAYMENT p ON vr.Visit_ID = p.Visit_ID
                WHERE vr.Pet_ID = :pet_id
                ORDER BY vr.Visit_Date DESC, v.Vaccination_ID DESC";
        
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':pet_id', $petId);
        
        if (oci_execute($stmt)) {
            while ($row = oci_fetch_assoc($stmt)) {
                $vaccinationHistory[] = $row;
            }
        }
        oci_free_statement($stmt);
        
        // HANDLE NEW VACCINATION FORM SUBMISSION
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_vaccination') {

            $weight = floatval($_POST['weight'] ?? 0);
            $vetId = intval($_POST['vet_id'] ?? 0);
            $vaccineName = trim($_POST['vaccine_name'] ?? '');
            $against = trim($_POST['against'] ?? '');
            $manufacturer = trim($_POST['manufacturer'] ?? '');
            $lotNo = trim($_POST['lot_no'] ?? '');
            $nextSchedule = $_POST['next_schedule'] ?? '';
            
            // Validate fields
            if ($weight <= 0 || $vetId <= 0 || empty($vaccineName) || empty($lotNo)) {
                $message = "Please fill in all required fields (Weight, Veterinarian, Vaccine Name, Lot No).";
                $messageType = "error";
            } else {
                // TRANSACTION: Insert Visit Record and Vaccination
                $transactionSuccess = false;
                $visitId = null;
                
                try {
                    // Step 1: Insert into VISIT_RECORD and get generated Visit_ID
                    $sqlVisit = "INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
                                 VALUES (SYSDATE, :weight, :pet_id, :vet_id)
                                 RETURNING Visit_ID INTO :visit_id";
                    
                    $stmtVisit = oci_parse($conn, $sqlVisit);
                    oci_bind_by_name($stmtVisit, ':weight', $weight);
                    oci_bind_by_name($stmtVisit, ':pet_id', $petId);
                    oci_bind_by_name($stmtVisit, ':vet_id', $vetId);
                    oci_bind_by_name($stmtVisit, ':visit_id', $visitId, 20);
                    
                    $resultVisit = oci_execute($stmtVisit, OCI_NO_AUTO_COMMIT);
                    
                    if (!$resultVisit) {
                        throw new Exception("Failed to create visit record: " . getOracleError($stmtVisit));
                    }
                    
                    oci_free_statement($stmtVisit);
                    
                    // Step 2: Insert into VACCINATION using the retrieved Visit_ID
                    $sqlVaccine = "INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule)
                                   VALUES (:visit_id, :vaccine_name, :against, :manufacturer, :lot_no, 
                                           " . ($nextSchedule ? "TO_DATE(:next_schedule, 'YYYY-MM-DD')" : "NULL") . ")";
                    
                    $stmtVaccine = oci_parse($conn, $sqlVaccine);
                    oci_bind_by_name($stmtVaccine, ':visit_id', $visitId);
                    oci_bind_by_name($stmtVaccine, ':vaccine_name', $vaccineName);
                    oci_bind_by_name($stmtVaccine, ':against', $against);
                    oci_bind_by_name($stmtVaccine, ':manufacturer', $manufacturer);
                    oci_bind_by_name($stmtVaccine, ':lot_no', $lotNo);
                    
                    if ($nextSchedule) {
                        oci_bind_by_name($stmtVaccine, ':next_schedule', $nextSchedule);
                    }
                    
                    $resultVaccine = oci_execute($stmtVaccine, OCI_NO_AUTO_COMMIT);
                    
                    if (!$resultVaccine) {
                        throw new Exception("Failed to create vaccination record: " . getOracleError($stmtVaccine));
                    }
                    
                    oci_free_statement($stmtVaccine);
                    
                    // Step 3: Commit the transaction
                    if (commitTransaction($conn)) {
                        $transactionSuccess = true;
                        $message = "Vaccination record saved successfully! (Visit ID: $visitId)";
                        $messageType = "success";
                        
                        // Refresh the page to show updated history
                        header("Location: pet_record.php?pet_id=$petId&success=1");
                        exit;
                    } else {
                        throw new Exception("Failed to commit transaction");
                    }
                    
                } catch (Exception $e) {
                    rollbackTransaction($conn);
                    $message = "Error saving vaccination: " . $e->getMessage();
                    $messageType = "error";
                }
            }
        }
        
        // HANDLE PAYMENT FORM SUBMISSION
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
            $visitId = intval($_POST['visit_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentMethod = trim($_POST['payment_method'] ?? '');
            $paymentDate = $_POST['payment_date'] ?? '';
            
            // Validate fields
            if ($visitId <= 0 || $amount <= 0 || empty($paymentMethod)) {
                $message = "Please fill in all required payment fields.";
                $messageType = "error";
            } else {
                // Insert payment record
                $sql = "INSERT INTO PAYMENT (Visit_ID, Amount, Payment_Method, Payment_Date, Payment_Status) 
                        VALUES (:visit_id, :amount, :payment_method, 
                                " . ($paymentDate ? "TO_DATE(:payment_date, 'YYYY-MM-DD')" : "SYSDATE") . ", 
                                'PAID')";
                
                $stmt = oci_parse($conn, $sql);
                oci_bind_by_name($stmt, ':visit_id', $visitId);
                oci_bind_by_name($stmt, ':amount', $amount);
                oci_bind_by_name($stmt, ':payment_method', $paymentMethod);
                if ($paymentDate) {
                    oci_bind_by_name($stmt, ':payment_date', $paymentDate);
                }
                
                if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
                    header("Location: pet_record.php?pet_id=$petId&payment_success=1");
                    exit;
                } else {
                    $error = oci_error($stmt);
                    $message = "Error processing payment: " . $error['message'];
                    $messageType = "error";
                }
                oci_free_statement($stmt);
            }
        }
        
        // Check for success redirect
        if (isset($_GET['success'])) {
            $message = "Vaccination record saved successfully!";
            $messageType = "success";
        }
        
        if (isset($_GET['payment_success'])) {
            $message = "Payment processed successfully!";
            $messageType = "success";
        }
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
                <a href="index.php" class="btn btn-outline">← Back to Dashboard</a>
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
        
        <?php if ($pet): ?>

        <!-- PET DETAILS HEADER -->

        <section class="card pet-details">
            <div class="pet-header">
                <div class="pet-avatar"><img src="assets/icons/pet-svgrepo-com.svg" alt="Pet" class="avatar-icon"></div>
                <div class="pet-info">
                    <h2><?php echo htmlspecialchars($pet['name']); ?></h2>
                    <p class="pet-breed"><?php echo htmlspecialchars($pet['breed'] ?? 'Unknown Breed'); ?></p>
                </div>
            </div>
            <div class="pet-meta">
                <div class="meta-item">
                    <span class="meta-label">Pet ID</span>
                    <span class="meta-value"><?php echo htmlspecialchars($pet['id']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Birthdate</span>
                    <span class="meta-value"><?php echo $pet['birthdate'] ? date('M d, Y', strtotime($pet['birthdate'])) : 'N/A'; ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Markings</span>
                    <span class="meta-value"><?php echo htmlspecialchars($pet['markings'] ?? 'N/A'); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Owner</span>
                    <span class="meta-value"><?php echo htmlspecialchars($owner['name']); ?></span>
                </div>
            </div>
        </section>
        
        <div class="dashboard-grid">
            
            <!-- NEW VACCINATION FORM -->
            
            <section class="card">
                <h2><img src="assets/icons/injection-inject-vaccine-svgrepo-com.svg" alt="" class="section-icon"> Record New Vaccination</h2>
                <form method="POST" action="pet_record.php?pet_id=<?php echo $petId; ?>" class="form">
                    <input type="hidden" name="action" value="add_vaccination">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="weight">Pet Weight (kg) <span class="required">*</span></label>
                            <input type="number" id="weight" name="weight" required 
                                   placeholder="e.g., 5.5" step="0.01" min="0.01" max="500">
                        </div>
                        <div class="form-group">
                            <label for="vet_id">Veterinarian <span class="required">*</span></label>
                            <select id="vet_id" name="vet_id" required>
                                <option value="">-- Select Veterinarian --</option>
                                <?php foreach ($veterinarians as $vet): ?>
                                <option value="<?php echo $vet['VET_ID']; ?>">
                                    <?php echo htmlspecialchars($vet['VET_NAME']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="vaccine_name">Vaccine Name <span class="required">*</span></label>
                            <input type="text" id="vaccine_name" name="vaccine_name" required 
                                   placeholder="e.g., Rabies, 5-in-1" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="against">Against (Disease)</label>
                            <input type="text" id="against" name="against" 
                                   placeholder="e.g., Rabies Virus" maxlength="100">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="manufacturer">Manufacturer</label>
                            <input type="text" id="manufacturer" name="manufacturer" 
                                   placeholder="e.g., Zoetis" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="lot_no">Lot Number <span class="required">*</span></label>
                            <input type="text" id="lot_no" name="lot_no" required 
                                   placeholder="e.g., ABC123456" maxlength="30">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="next_schedule">Next Schedule Date</label>
                        <input type="date" id="next_schedule" name="next_schedule"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">
                        Save Vaccination Record
                    </button>
                </form>
            </section>
            
            
            <!-- VACCINATION HISTORY -->
            
            <section class="card">
                <h2><img src="assets/icons/paper-document-svgrepo-com.svg" alt="" class="section-icon"> Vaccination History</h2>
                
                <?php if (!empty($vaccinationHistory)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vaccine</th>
                                <th>Against</th>
                                <th>Manufacturer</th>
                                <th>Lot No</th>
                                <th>Weight</th>
                                <th>Vet</th>
                                <th>Next Due</th>
                                <th>Payment Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vaccinationHistory as $record): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($record['VISIT_DATE'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($record['VACCINE_NAME']); ?></strong></td>
                                <td><?php echo htmlspecialchars($record['AGAINST'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($record['MANUFACTURER'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($record['LOT_NO']); ?></td>
                                <td><?php echo number_format($record['PET_WEIGHT'], 2); ?> kg</td>
                                <td><?php echo htmlspecialchars($record['VET_NAME']); ?></td>
                                <td>
                                    <?php if ($record['NEXT_SCHEDULE']): ?>
                                        <?php 
                                        $nextDate = strtotime($record['NEXT_SCHEDULE']);
                                        $isOverdue = $nextDate < time();
                                        ?>
                                        <span class="<?php echo $isOverdue ? 'overdue' : 'upcoming'; ?>">
                                            <?php echo date('M d, Y', $nextDate); ?>
                                            <?php if ($isOverdue): ?> ⚠️<?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['PAYMENT_STATUS'] === 'PAID'): ?>
                                        <span class="status-badge status-paid">PAID</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['PAYMENT_STATUS'] === 'PAID'): ?>
                                        <button type="button" class="btn btn-small btn-secondary" 
                                                onclick="showReceipt(<?php echo $record['VISIT_ID']; ?>, '<?php echo date('M d, Y', strtotime($record['PAYMENT_DATE'])); ?>', <?php echo $record['AMOUNT']; ?>, '<?php echo htmlspecialchars($record['PAYMENT_METHOD']); ?>')">
                                            View Receipt
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-small btn-accent" 
                                                onclick="openPaymentModal(<?php echo $record['VISIT_ID']; ?>, '<?php echo htmlspecialchars($record['VACCINE_NAME']); ?>')">
                                            Pay Now
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="no-results">No vaccination records found for this pet.</p>
                <?php endif; ?>
            </section>
        </div>
        <?php endif; ?>
    </main>
    
    <!-- Page Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> PPL Paws and Tails VetClinic Management System <br> Vaccination Management System Prototype</p>
        </div>
    </footer>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><img src="assets/icons/credit-card.svg" alt="" class="section-icon"> Process Payment</h3>
                <button type="button" class="modal-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <form method="POST" action="pet_record.php?pet_id=<?php echo $petId; ?>" class="form">
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="visit_id" id="payment_visit_id">
                
                <div class="modal-body">
                    <p class="modal-info">Processing payment for: <strong id="payment_vaccine_name"></strong></p>
                    
                    <div class="form-group">
                        <label for="amount">Amount (₱) <span class="required">*</span></label>
                        <input type="number" id="amount" name="amount" required 
                               placeholder="Enter amount" step="0.01" min="0.01" max="99999.99">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method <span class="required">*</span></label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">-- Select Method --</option>
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Gcash">Gcash</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_date">Payment Date</label>
                        <input type="date" id="payment_date" name="payment_date" 
                               value="<?php echo date('Y-m-d'); ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><img src="assets/icons/paper-document-svgrepo-com.svg" alt="" class="section-icon"> Payment Receipt</h3>
                <button type="button" class="modal-close" onclick="closeReceiptModal()">&times;</button>
            </div>
            <div class="modal-body receipt-body">
                <div class="receipt-row">
                    <span class="receipt-label">Visit ID:</span>
                    <span id="receipt_visit_id" class="receipt-value"></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Payment Date:</span>
                    <span id="receipt_date" class="receipt-value"></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Amount:</span>
                    <span id="receipt_amount" class="receipt-value"></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Payment Method:</span>
                    <span id="receipt_method" class="receipt-value"></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Status:</span>
                    <span class="receipt-value"><span class="status-badge status-paid">PAID</span></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReceiptModal()">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        // Payment Modal Functions
        function openPaymentModal(visitId, vaccineName) {
            document.getElementById('payment_visit_id').value = visitId;
            document.getElementById('payment_vaccine_name').textContent = vaccineName;
            document.getElementById('amount').value = '';
            document.getElementById('payment_method').value = '';
            document.getElementById('paymentModal').style.display = 'flex';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        // Receipt Modal Functions
        function showReceipt(visitId, paymentDate, amount, paymentMethod) {
            document.getElementById('receipt_visit_id').textContent = visitId;
            document.getElementById('receipt_date').textContent = paymentDate;
            document.getElementById('receipt_amount').textContent = '₱' + parseFloat(amount).toFixed(2);
            document.getElementById('receipt_method').textContent = paymentMethod;
            document.getElementById('receiptModal').style.display = 'flex';
        }
        
        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
        
        document.getElementById('receiptModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReceiptModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePaymentModal();
                closeReceiptModal();
            }
        });
    </script>
    
    <?php
    // Close connection
    if ($conn) {
        closeConnection($conn);
    }
    ?>
</body>
</html>

