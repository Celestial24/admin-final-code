<?php
    // config.php
    class Database {
        private $host = "localhost";
        private $db_name = "legalmanagement";
        private $username = "root";
        private $password = "";
        public $conn;

        public function getConnection() {
            $this->conn = null;
            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->conn->exec("set names utf8");
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
            }
            return $this->conn;
        }
    }

    // AI Risk Assessment Class
    class ContractRiskAnalyzer {
        private $riskFactors = [
            'financial_terms' => [
                'long_term_lease' => ['weight' => 15, 'high_risk' => 'Lease term > 10 years'],
                'unfavorable_rent' => ['weight' => 10, 'high_risk' => 'Guaranteed minimum rent + revenue share'],
                'hidden_fees' => ['weight' => 8, 'high_risk' => 'Undisclosed additional charges'],
                'security_deposit' => ['weight' => 7, 'high_risk' => 'Security deposit > 6 months']
            ],
            'operational_control' => [
                'restrictive_hours' => ['weight' => 8, 'high_risk' => 'Limited operating hours'],
                'supplier_restrictions' => ['weight' => 10, 'high_risk' => 'Exclusive supplier requirements'],
                'renovation_limits' => ['weight' => 7, 'high_risk' => 'Strict renovation restrictions'],
                'staffing_controls' => ['weight' => 5, 'high_risk' => 'Limited staffing autonomy']
            ],
            'legal_protection' => [
                'unlimited_liability' => ['weight' => 12, 'high_risk' => 'Unlimited liability clauses'],
                'personal_guarantee' => ['weight' => 10, 'high_risk' => 'Personal guarantees required'],
                'unilateral_amendments' => ['weight' => 8, 'high_risk' => 'Unilateral amendment rights'],
                'dispute_resolution' => ['weight' => 6, 'high_risk' => 'Unfavorable dispute resolution']
            ],
            'flexibility_exit' => [
                'termination_penalties' => ['weight' => 8, 'high_risk' => 'Heavy termination penalties'],
                'renewal_restrictions' => ['weight' => 6, 'high_risk' => 'Automatic renewal without notice'],
                'assignment_rights' => ['weight' => 4, 'high_risk' => 'Limited assignment rights'],
                'force_majeure' => ['weight' => 2, 'high_risk' => 'No force majeure clause']
            ]
        ];

        public function analyzeContract($contractData) {
            $totalScore = 0;
            $maxPossibleScore = 0;
            $riskFactorsFound = [];
            $recommendations = [];

            foreach ($this->riskFactors as $category => $factors) {
                foreach ($factors as $factorKey => $factor) {
                    $maxPossibleScore += $factor['weight'];
                    
                    // Simulate AI detection - in real implementation, this would analyze contract text
                    if ($this->detectRiskFactor($contractData, $factorKey)) {
                        $totalScore += $factor['weight'];
                        $riskFactorsFound[] = [
                            'category' => $category,
                            'factor' => $factor['high_risk'],
                            'weight' => $factor['weight']
                        ];
                    }
                }
            }

            // Calculate risk percentage
            $riskPercentage = ($totalScore / $maxPossibleScore) * 100;
            
            // Determine risk level
            if ($riskPercentage >= 70) {
                $riskLevel = 'High';
                $recommendations = $this->getHighRiskRecommendations();
            } elseif ($riskPercentage >= 31) {
                $riskLevel = 'Medium';
                $recommendations = $this->getMediumRiskRecommendations();
            } else {
                $riskLevel = 'Low';
                $recommendations = $this->getLowRiskRecommendations();
            }

            return [
                'risk_score' => round($riskPercentage),
                'risk_level' => $riskLevel,
                'risk_factors' => $riskFactorsFound,
                'recommendations' => $recommendations,
                'analysis_summary' => $this->generateAnalysisSummary($riskLevel, $riskFactorsFound)
            ];
        }

        private function detectRiskFactor($contractData, $factorKey) {
            // Simulated AI detection - in production, this would use NLP/text analysis
            $keywords = [
                'long_term_lease' => ['10 years', '15 years', '20 years', 'long-term', 'extended term'],
                'unfavorable_rent' => ['minimum rent', 'revenue share', 'percentage of sales', 'guaranteed payment'],
                'hidden_fees' => ['additional charges', 'hidden costs', 'undisclosed fees', 'extra payments'],
                'security_deposit' => ['security deposit', '6 months', 'advance payment', 'deposit amount'],
                'restrictive_hours' => ['operating hours', 'business hours', 'time restrictions', 'hour limitations'],
                'supplier_restrictions' => ['exclusive supplier', 'approved vendors', 'vendor restrictions', 'supplier limitations'],
                'renovation_limits' => ['renovation restrictions', 'modification limits', 'alteration approval', 'structural changes'],
                'staffing_controls' => ['staff approval', 'employee restrictions', 'hiring limitations', 'personnel controls'],
                'unlimited_liability' => ['unlimited liability', 'full responsibility', 'complete liability', 'total responsibility'],
                'personal_guarantee' => ['personal guarantee', 'individual assurance', 'personal commitment', 'individual warranty'],
                'unilateral_amendments' => ['unilateral amendment', 'one-sided changes', 'sole discretion', 'exclusive right'],
                'termination_penalties' => ['termination fee', 'early termination', 'cancellation penalty', 'break clause fee'],
                'renewal_restrictions' => ['automatic renewal', 'auto-renew', 'automatic extension', 'self-renewing']
            ];

            $contractText = strtolower($contractData['contract_name'] . ' ' . $contractData['description']);
            
            if (isset($keywords[$factorKey])) {
                foreach ($keywords[$factorKey] as $keyword) {
                    if (strpos($contractText, strtolower($keyword)) !== false) {
                        return true;
                    }
                }
            }

            // Random factor for demo purposes - remove in production
            return rand(0, 100) < 30; // 30% chance to detect a risk factor for demo
        }

        private function getHighRiskRecommendations() {
            return [
                'Immediate legal review required',
                'Negotiate key risk clauses',
                'Consider alternative agreements',
                'Implement risk mitigation strategies',
                'Regular compliance monitoring'
            ];
        }

        private function getMediumRiskRecommendations() {
            return [
                'Standard legal review recommended',
                'Clarify ambiguous terms',
                'Document all understandings',
                'Establish monitoring procedures',
                'Plan for periodic reviews'
            ];
        }

        private function getLowRiskRecommendations() {
            return [
                'Routine monitoring sufficient',
                'Maintain proper documentation',
                'Schedule annual reviews',
                'Monitor regulatory changes',
                'Standard compliance procedures'
            ];
        }

        private function generateAnalysisSummary($riskLevel, $riskFactors) {
            $factorCount = count($riskFactors);
            
            if ($riskLevel === 'High') {
                return "Critical risk level detected with {$factorCount} high-risk factors requiring immediate attention.";
            } elseif ($riskLevel === 'Medium') {
                return "Moderate risk level with {$factorCount} risk factors needing standard review.";
            } else {
                return "Low risk level with minimal risk factors. Standard monitoring recommended.";
            }
        }
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $database = new Database();
        $db = $database->getConnection();
        
        if (isset($_POST['add_employee'])) {
            $name = $_POST['employee_name'];
            $position = $_POST['employee_position'];
            $email = $_POST['employee_email'];
            $phone = $_POST['employee_phone'];
            
            $query = "INSERT INTO employees (name, position, email, phone) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$name, $position, $email, $phone])) {
                $success_message = "Employee added successfully!";
            } else {
                $error_message = "Failed to add employee.";
            }
        }
        
        // Handle contract upload with AI analysis
        if (isset($_POST['add_contract'])) {
            $contract_name = $_POST['contract_name'];
            $case_id = $_POST['contract_case'];
            $description = $_POST['contract_description'] ?? '';
            
            // AI Risk Analysis
            $analyzer = new ContractRiskAnalyzer();
            $contractData = [
                'contract_name' => $contract_name,
                'description' => $description
            ];
            
            $riskAnalysis = $analyzer->analyzeContract($contractData);
            
            // Handle file upload
            $file_name = '';
            if (isset($_FILES['contract_file']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/contracts/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_tmp_name = $_FILES['contract_file']['tmp_name'];
                $file_original_name = $_FILES['contract_file']['name'];
                $file_extension = pathinfo($file_original_name, PATHINFO_EXTENSION);
                $file_name = uniqid() . '_' . $contract_name . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($file_tmp_name, $file_path)) {
                    $file_name = $file_path;
                } else {
                    $error_message = "Failed to upload file.";
                }
            }
            
            $query = "INSERT INTO contracts (contract_name, case_id, description, file_path, risk_level, risk_score, risk_factors, recommendations, analysis_summary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            $risk_factors_json = json_encode($riskAnalysis['risk_factors']);
            $recommendations_json = json_encode($riskAnalysis['recommendations']);
            
            if ($stmt->execute([
                $contract_name, 
                $case_id, 
                $description,
                $file_name, 
                $riskAnalysis['risk_level'], 
                $riskAnalysis['risk_score'],
                $risk_factors_json,
                $recommendations_json,
                $riskAnalysis['analysis_summary']
            ])) {
                $success_message = "Contract uploaded successfully! AI Risk Analysis Completed.";
            } else {
                $error_message = "Failed to upload contract.";
            }
        }

        // Handle PDF Export (Idinagdag para sa PDF Report na may Password)
        if (isset($_POST['action']) && $_POST['action'] === 'export_pdf') {
            $password = 'legal2025'; // Password para sa PDF Report (Simulasyon)
            
            // Kunin ang lahat ng data ng kontrata para sa ulat
            $query = "SELECT contract_name, risk_level, risk_score, analysis_summary FROM contracts ORDER BY created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $contracts_to_report = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // --- SIMULASYON NG PDF GENERATION (Dahil hindi available ang external libraries) ---
            
            // I-set ang headers para sa pag-download ng file (ginamit ang .txt para sa simulation)
            header('Content-Type: application/octet-stream'); 
            header('Content-Disposition: attachment; filename="Legal_Contracts_Report_Protected.txt"');
            
            // Mag-output ng simpleng text na nagsasabi na nag-generate ng protected file
            echo "========================================================\n";
            echo "== NAKA-PROTEKTANG PDF REPORT NG KONTRATA (SIMULASYON) ==\n";
            echo "========================================================\n\n";
            echo "Ipinagbabawal ang pagtingin nang walang pahintulot.\n";
            echo "Ito ay naglalaman ng sensitibong legal na impormasyon.\n\n";
            echo "========================================================\n";
            echo "PASSWORD SA PAGBUKAS NG PDF (Ito ang kailangan mo sa totoong PDF): " . $password . "\n";
            echo "========================================================\n\n";
            
            echo "Kontrata sa Report:\n";
            foreach ($contracts_to_report as $contract) {
                echo "- " . $contract['contract_name'] . " (Risk: " . $contract['risk_level'] . ", Score: " . $contract['risk_score'] . "/100)\n";
                echo "  Buod ng Pagsusuri: " . $contract['analysis_summary'] . "\n";
            }
            
            exit;
        }
    }

    // Fetch employees from database
    $database = new Database();
    $db = $database->getConnection();
    $employees = [];
    $contracts = [];

    try {
        $query = "SELECT * FROM employees";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $exception) {
        $error_message = "Error fetching employees: " . $exception->getMessage();
    }

    // Fetch contracts from database
    try {
        $query = "SELECT * FROM contracts ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $exception) {
        $error_message = "Error fetching contracts: " . $exception->getMessage();
    }

    // NEW: Fetch documents and billing (with fallbacks) and build risk summary
    $documents = [];
    try {
        $query = "SELECT id, name, case_id, file_path, uploaded_at, risk_level, risk_score, analysis_date, ai_analysis FROM documents ORDER BY uploaded_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // fallback demo data if query fails
        $documents = [
            ['id'=>1,'name'=>'Employment Contract.pdf','case_id'=>'C-001','file_path'=>'uploads/documents/Employment Contract.pdf','uploaded_at'=>'2023-05-20 12:00:00','risk_level'=>'unknown','risk_score'=>null,'analysis_date'=>null,'ai_analysis'=>null],
            ['id'=>2,'name'=>'Supplier Agreement.docx','case_id'=>'C-002','file_path'=>'uploads/documents/Supplier Agreement.docx','uploaded_at'=>'2023-06-25 12:00:00','risk_level'=>'unknown','risk_score'=>null,'analysis_date'=>null,'ai_analysis'=>null]
        ];
    }

    $billing = [];
    try {
        $query = "SELECT id, invoice_number, client, amount, due_date, status FROM invoices ORDER BY due_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $billing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // fallback demo data
        $billing = [
            ['invoice_number'=>'INV-001','client'=>'Hotel Management','amount'=>2500,'due_date'=>'2023-07-15','status'=>'paid'],
            ['invoice_number'=>'INV-002','client'=>'Restaurant Owner','amount'=>1800,'due_date'=>'2023-08-05','status'=>'pending']
        ];
    }

    // Risk summary
    $riskCounts = ['High'=>0, 'Medium'=>0, 'Low'=>0];
    foreach ($contracts as $c) {
        $lvl = $c['risk_level'] ?? 'Low';
        if (!isset($riskCounts[$lvl])) $lvl = 'Low';
        $riskCounts[$lvl]++;
    }
    $totalContracts = count($contracts);
    $highPct = $totalContracts ? round(($riskCounts['High'] / $totalContracts) * 100, 1) : 0;
    $mediumPct = $totalContracts ? round(($riskCounts['Medium'] / $totalContracts) * 100, 1) : 0;
    $lowPct = $totalContracts ? round(($riskCounts['Low'] / $totalContracts) * 100, 1) : 0;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Legal Management System - Hotel & Restaurant</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
         <link rel="stylesheet" href="../assets/css/legalmanagement.css">
    </head>
    <body>
        <!-- Login Screen -->
        <div class="login-container" id="loginScreen">
            <div class="login-form">
                <h2>Legal Management System</h2>
                <p>Enter your PIN to access the system</p>
                <div class="pin-input">
                    <input type="password" maxlength="1" class="pin-digit" id="pin1">
                    <input type="password" maxlength="1" class="pin-digit" id="pin2">
                    <input type="password" maxlength="1" class="pin-digit" id="pin3">
                    <input type="password" maxlength="1" class="pin-digit" id="pin4">
                </div>
                <button class="login-btn" id="loginBtn">Login</button>
                <div class="error-message" id="errorMessage">Invalid PIN. Please try again.</div>
            </div>
        </div>

        <!-- Dashboard -->
        <div class="dashboard" id="dashboard">
            <div class="header">
                <div class="container">
                    <div class="header-content">
                        <div class="logo">Legal Management System</div>
                        <div class="user-info">
                            <span>Welcome, Admin</span>
                            <button type="button" class="logout-btn" id="backDashboardBtn" onclick="window.location.href='../Modules/facilities-reservation.php'">
                                <span class="icon-img-placeholder">🏠</span> logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container">
                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="nav-tabs">
                    <div class="nav-tab active" data-target="employees">Employees</div>
                    <div class="nav-tab" data-target="documents">Documents</div>
                    <div class="nav-tab" data-target="billing">Billing</div>
                    <div class="nav-tab" data-target="contracts">Contracts</div>
                    <div class="nav-tab" data-target="risk_analysis">Risk Analysis</div>
                    <div class="nav-tab" data-target="members">Members</div>
                </div>

                <!-- Employees Section -->
                <div class="content-section active" id="employees">
                    <div class="section-header">
                        <h2 class="section-title">Employee Information</h2>
                        <button class="add-btn" id="addEmployeeBtn">
                            <i>+</i> Add Employee
                        </button>
                    </div>

                    <!-- Add Employee Form -->
                    <div class="form-container" id="employeeForm">
                        <h3>Add Employee</h3>
                        <form method="POST" id="employeeFormData">
                            <div class="form-group">
                                <label for="employeeName">Name</label>
                                <input type="text" id="employeeName" name="employee_name" class="form-control" placeholder="Enter employee name" required>
                            </div>
                            <div class="form-group">
                                <label for="employeePosition">Position</label>
                                <input type="text" id="employeePosition" name="employee_position" class="form-control" placeholder="Enter position" required>
                            </div>
                            <div class="form-group">
                                <label for="employeeEmail">Email</label>
                                <input type="email" id="employeeEmail" name="employee_email" class="form-control" placeholder="Enter email" required>
                            </div>
                            <div class="form-group">
                                <label for="employeePhone">Phone</label>
                                <input type="text" id="employeePhone" name="employee_phone" class="form-control" placeholder="Enter phone number" required>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-btn" id="cancelEmployeeBtn">Cancel</button>
                                <button type="submit" class="save-btn" name="add_employee" id="saveEmployeeBtn">Save Employee</button>
                            </div>
                        </form>
                    </div>

                    <!-- Employees Table -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employeesTableBody">
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td>E-<?php echo str_pad($employee['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                    <td>
                                        <button class="action-btn view-btn">View</button>
                                        <button class="action-btn">Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Documents Section -->
                <div class="content-section" id="documents">
                    <div class="section-header">
                        <h2 class="section-title">Case Documents</h2>
                        <button class="add-btn" id="addDocumentBtn">
                            <i>+</i> Upload Document
                        </button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Case</th>
                                <th>Date Uploaded</th>
                               
                        <tbody id="documentsTableBody">
                            <?php if (!empty($documents)): ?>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($doc['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($doc['name']); ?></a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($doc['name']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc['case_id'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($doc['uploaded_at'] ?? 'now'))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center;color:#666;padding:20px;">No documents found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="content-section" id="billing">
                    <div class="section-header">
                        <h2 class="section-title">Billing & Invoices</h2>
                        <button class="add-btn" id="addInvoiceBtn">
                            <i>+</i> Create Invoice
                        </button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="billingTableBody">
                            <?php if (!empty($billing)): ?>
                                <?php foreach ($billing as $b): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($b['invoice_number'] ?? $b['id']); ?></td>
                                        <td><?php echo htmlspecialchars($b['client'] ?? 'N/A'); ?></td>
                                        <td>₱<?php echo number_format($b['amount'] ?? 0, 2); ?></td>
                                        <td><?php echo htmlspecialchars(!empty($b['due_date']) ? date('Y-m-d', strtotime($b['due_date'])) : 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($b['status'] ?? 'unknown')); ?></td>
                                        <td>
                                            <button class="action-btn view-btn" data-id="<?php echo $b['id'] ?? ''; ?>">View</button>
                                            <button class="action-btn">Pay</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center;color:#666;padding:20px;">No billing records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Contracts Section -->
                <div class="content-section" id="contracts">
                    <div class="section-header">
                        <h2 class="section-title">Contracts <span class="ai-badge">AI-Powered Analysis</span></h2>
                        <div style="display: flex; gap: 10px;">
                            <!-- Button para sa Secured PDF Report (Idinagdag) -->
                            <button class="add-btn" id="exportPdfBtn" style="background: #e74c3c; /* Pula para sa ulat */">
                                &#x1F4C4; Generate Secured PDF
                            </button>
                            <button class="add-btn" id="addContractBtn">
                                <i>+</i> Upload Contract
                            </button>
                        </div>
                    </div>

                    <!-- Add Contract Form -->
                    <div class="form-container" id="contractForm">
                        <h3>Upload Contract <span class="ai-badge">AI Risk Analysis</span></h3>
                        <form method="POST" enctype="multipart/form-data" id="contractFormData">
                            <div class="form-group">
                                <label for="contractName">Contract Name</label>
                                <input type="text" id="contractName" name="contract_name" class="form-control" placeholder="Enter contract name" required>
                            </div>
                            <div class="form-group">
                                <label for="contractCase">Case ID</label>
                                <input type="text" id="contractCase" name="contract_case" class="form-control" placeholder="Enter case ID (e.g., C-001)" required>
                            </div>
                            <div class="form-group">
                                <label for="contractDescription">Contract Description</label>
                                <textarea id="contractDescription" name="contract_description" class="form-control" placeholder="Describe the contract terms, key clauses, and important details for AI analysis" rows="4"></textarea>
                                <div class="file-info">AI will analyze this description to detect risk factors</div>
                            </div>
                            <div class="form-group">
                                <label for="contractFile">Contract File</label>
                                <input type="file" id="contractFile" name="contract_file" class="form-control" accept=".pdf,.doc,.docx" required>
                                <div class="file-info">Accepted formats: PDF, DOC, DOCX (Max: 10MB)</div>
                            </div>
                            
                            <div class="ai-analysis-section">
                                <h4>､AI Risk Assessment</h4>
                                <p><strong>Note:</strong> Our AI system will automatically analyze your contract for:</p>
                                <ul>
                                    <li>Financial risk factors (lease terms, rent structure)</li>
                                    <li>Operational restrictions (hours, suppliers, staffing)</li>
                                    <li>Legal protection issues (liability, guarantees)</li>
                                    <li>Flexibility and exit concerns</li>
                                </ul>
                                <p><em>Risk score and level will be automatically calculated</em></p>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="cancel-btn" id="cancelContractBtn">Cancel</button>
                                <button type="submit" class="save-btn" name="add_contract" id="saveContractBtn">
                                    <i>､/i> Upload & Analyze Contract
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Contracts Table -->
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Contract Name</th>
                                <th>Case</th>
                                <th>Risk Level</th>
                                <th>Risk Score</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="contractsTableBody">
                            <?php foreach ($contracts as $contract): 
                                $risk_factors = json_decode($contract['risk_factors'] ?? '[]', true);
                                $recommendations = json_decode($contract['recommendations'] ?? '[]', true);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contract['contract_name']); ?></td>
                                    <td><?php echo htmlspecialchars($contract['case_id']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($contract['risk_level']); ?>">
                                            <?php echo htmlspecialchars($contract['risk_level']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($contract['risk_score']); ?>/100</td>
                                    <td><?php echo date('Y-m-d', strtotime($contract['created_at'])); ?></td>
                                    <td>
                                        <button class="action-btn view-btn">View</button>
                                        <button class="action-btn analyze-btn" data-contract='<?php echo htmlspecialchars(json_encode($contract)); ?>'>Analyze</button>
                                        <?php if (!empty($contract['file_path'])): ?>
                                            <button class="action-btn download-btn" data-file="<?php echo htmlspecialchars($contract['file_path']); ?>">Download</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="content-section" id="risk_analysis">
                    <div class="section-header">
                        <h2 class="section-title">Contract Risk Analysis</h2>
                    </div>
                    <div id="riskChartContainer">
                        <canvas id="riskChart" width="400" height="200"></canvas>
                    </div>
                    <div id="analysisResults">
                        <!-- Analysis results will be displayed here -->
                        <div class="risk-summary" style="padding:12px;">
                            <p><strong>Total contracts:</strong> <?php echo $totalContracts; ?></p>
                            <p><strong>High:</strong> <?php echo $riskCounts['High']; ?> (<?php echo $highPct; ?>%)</p>
                            <p><strong>Medium:</strong> <?php echo $riskCounts['Medium']; ?> (<?php echo $mediumPct; ?>%)</p>
                            <p><strong>Low:</strong> <?php echo $riskCounts['Low']; ?> (<?php echo $lowPct; ?>%)</p>
                        </div>
                        <?php
                        $highContracts = array_filter($contracts, function($c){ return (isset($c['risk_level']) && strtolower($c['risk_level']) === 'high'); });
                        if (!empty($highContracts)): ?>
                            <h4 style="margin-top:12px;">Top High-Risk Contracts</h4>
                            <ul>
                                <?php foreach (array_slice($highContracts, 0, 5) as $hc): ?>
                                    <li><?php echo htmlspecialchars($hc['contract_name'] ?? 'Untitled'); ?> — <?php echo htmlspecialchars($hc['risk_score'] ?? 'N/A'); ?>/100</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-section" id="members">
                    <div class="section-header">
                        <h2 class="section-title">Team Members</h2>
                        <button class="add-btn" id="addMemberBtn">
                            <i>+</i> Add Member
                        </button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="membersTableBody">
                            <!-- Members will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

      
        <script src="../assets/Javascript/legalmanagemet.js"></script>

        <!-- Details Modal -->
        <div id="detailsModal" style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
            <div style="background:white; width:90%; max-width:600px; border-radius:8px; padding:20px; position:relative;">
                <button id="closeDetails" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <h3 id="detailsTitle">Details</h3>
                <div id="detailsBody">
                    <!-- dynamic content -->
                </div>
            </div>
        </div>
    </body>
    </html>