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
        
        if (isset($_POST['update_employee'])) {
            $empId = intval($_POST['employee_id'] ?? 0);
            $name = $_POST['employee_name'] ?? '';
            $position = $_POST['employee_position'] ?? '';
            $email = $_POST['employee_email'] ?? '';
            $phone = $_POST['employee_phone'] ?? '';
            if ($empId > 0) {
                $query = "UPDATE employees SET name = ?, position = ?, email = ?, phone = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$name, $position, $email, $phone, $empId])) {
                    $success_message = "Employee updated successfully!";
                } else {
                    $error_message = "Failed to update employee.";
                }
            } else {
                $error_message = "Invalid employee ID.";
            }
        }
        // Unified create/update handler
        if (isset($_POST['save_employee'])) {
            $empId = intval($_POST['employee_id'] ?? 0);
            $name = $_POST['employee_name'] ?? '';
            $position = $_POST['employee_position'] ?? '';
            $email = $_POST['employee_email'] ?? '';
            $phone = $_POST['employee_phone'] ?? '';
            if ($empId > 0) {
                $q = "UPDATE employees SET name=?, position=?, email=?, phone=? WHERE id=?";
                $s = $db->prepare($q);
                if ($s->execute([$name,$position,$email,$phone,$empId])) {
                    $success_message = "Employee updated successfully!";
                } else {
                    $error_message = "Failed to update employee.";
                }
            } else {
                $q = "INSERT INTO employees (name, position, email, phone) VALUES (?, ?, ?, ?)";
                $s = $db->prepare($q);
                if ($s->execute([$name,$position,$email,$phone])) {
                    $success_message = "Employee added successfully!";
                } else {
                    $error_message = "Failed to add employee.";
                }
            }
        }
        
        // Add Document
        if (isset($_POST['add_document'])) {
            $doc_name = $_POST['doc_name'] ?? '';
            $doc_case = $_POST['doc_case'] ?? '';
            $file_path = '';
            if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/documents/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $tmp = $_FILES['doc_file']['tmp_name'];
                $orig = $_FILES['doc_file']['name'];
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                $fname = uniqid('doc_') . '.' . $ext;
                $dest = $upload_dir . $fname;
                if (move_uploaded_file($tmp, $dest)) $file_path = $dest;
            }
            $q = "INSERT INTO documents (name, case_id, file_path, uploaded_at) VALUES (?, ?, ?, NOW())";
            $s = $db->prepare($q);
            if ($s->execute([$doc_name, $doc_case, $file_path])) {
                $success_message = "Document uploaded successfully!";
            } else {
                $error_message = "Failed to upload document.";
            }
        }
        // Update Document
        if (isset($_POST['update_document'])) {
            $doc_id = intval($_POST['document_id'] ?? 0);
            $doc_name = $_POST['doc_name'] ?? '';
            $doc_case = $_POST['doc_case'] ?? '';
            if ($doc_id > 0) {
                $q = "UPDATE documents SET name = ?, case_id = ? WHERE id = ?";
                $s = $db->prepare($q);
                if ($s->execute([$doc_name, $doc_case, $doc_id])) {
                    $success_message = "Document updated successfully!";
                } else {
                    $error_message = "Failed to update document.";
                }
            } else {
                $error_message = "Invalid document ID.";
            }
        }
        // Delete Document
        if (isset($_POST['delete_document'])) {
            $doc_id = intval($_POST['document_id'] ?? 0);
            if ($doc_id > 0) {
                $q = "DELETE FROM documents WHERE id = ?";
                $s = $db->prepare($q);
                if ($s->execute([$doc_id])) {
                    $success_message = "Document deleted.";
                } else {
                    $error_message = "Failed to delete document.";
                }
            }
        }
        // Add Invoice
        if (isset($_POST['add_invoice'])) {
            $inv_number = $_POST['invoice_number'] ?? '';
            $client = $_POST['client'] ?? '';
            $amount = floatval($_POST['amount'] ?? 0);
            $due_date = $_POST['due_date'] ?? date('Y-m-d');
            $status = $_POST['status'] ?? 'pending';
            $q = "INSERT INTO invoices (invoice_number, client, amount, due_date, status) VALUES (?, ?, ?, ?, ?)";
            $s = $db->prepare($q);
            if ($s->execute([$inv_number, $client, $amount, $due_date, $status])) {
                $success_message = "Invoice created successfully!";
            } else {
                $error_message = "Failed to create invoice.";
            }
        }
        // Pay invoice (set to paid)
        if (isset($_POST['pay_invoice'])) {
            $invoice_id = intval($_POST['invoice_id'] ?? 0);
            if ($invoice_id > 0) {
                $q = "UPDATE invoices SET status = 'paid' WHERE id = ?";
                $s = $db->prepare($q);
                if ($s->execute([$invoice_id])) {
                    $success_message = "Invoice has been marked as PAID.";
                } else {
                    $error_message = "Payment failed. Try again.";
                }
            } else {
                $error_message = "Invalid invoice ID.";
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
            // Optional cover image upload -> saved as related document
            $image_path = '';
            if (isset($_FILES['contract_image']) && $_FILES['contract_image']['error'] === UPLOAD_ERR_OK) {
                $img_dir = 'uploads/contracts/images/';
                if (!is_dir($img_dir)) { mkdir($img_dir, 0777, true); }
                $img_tmp = $_FILES['contract_image']['tmp_name'];
                $img_name = $_FILES['contract_image']['name'];
                $img_ext = pathinfo($img_name, PATHINFO_EXTENSION);
                $img_file = uniqid('cimg_') . '.' . $img_ext;
                $img_dest = $img_dir . $img_file;
                if (move_uploaded_file($img_tmp, $img_dest)) {
                    $image_path = $img_dest;
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
                if (!empty($image_path)) {
                    try {
                        $dq = $db->prepare("INSERT INTO documents (name, case_id, file_path, uploaded_at) VALUES (?, ?, ?, NOW())");
                        $dq->execute(['Contract Image: ' . $contract_name, $case_id, $image_path]);
                    } catch (PDOException $e) {
                        // ignore
                    }
                }
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
        // Add supporting document for a contract (saves into documents table using contract's case_id)
        if (isset($_POST['add_contract_document'])) {
            $contractId = intval($_POST['contract_id'] ?? 0);
            $docName = $_POST['doc_name'] ?? '';
            if ($contractId > 0 && $docName !== '') {
                // Fetch case_id for linking
                $stmt = $db->prepare("SELECT case_id FROM contracts WHERE id = ? LIMIT 1");
                $stmt->execute([$contractId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $caseId = $row['case_id'] ?? '';

                $file_path = '';
                if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/contracts/docs/';
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                    $tmp = $_FILES['doc_file']['tmp_name'];
                    $orig = $_FILES['doc_file']['name'];
                    $ext = pathinfo($orig, PATHINFO_EXTENSION);
                    $fname = uniqid('cdoc_') . '.' . $ext;
                    $dest = $upload_dir . $fname;
                    if (move_uploaded_file($tmp, $dest)) $file_path = $dest;
                }

                $q = "INSERT INTO documents (name, case_id, file_path, uploaded_at) VALUES (?, ?, ?, NOW())";
                $s = $db->prepare($q);
                if ($s->execute([$docName, $caseId, $file_path])) {
                    $success_message = "Contract document uploaded successfully!";
                } else {
                    $error_message = "Failed to upload contract document.";
                }
            } else {
                $error_message = "Invalid contract or missing document name.";
            }
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
        <style>
            /* Center all table header and cell content within this module */
            .data-table th,
            .data-table td {
                text-align: center !important;
                vertical-align: middle;
            }

            /* When password modal is active, hide everything except the password modal */
            .pwd-focus *:not(#passwordModal):not(#passwordModal *) {
                opacity: 0 !important;
                pointer-events: none !important;
                user-select: none !important;
                transition: opacity .08s linear;
            }
            /* Ensure password modal always on top */
            #passwordModal { z-index: 99999 !important; }
        </style>
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
        <!-- Employee Information Modal (Create/Update) -->
        <div id="employeeInfoModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1250;">
            <div style="background:white; width:92%; max-width:600px; border-radius:12px; padding:20px; position:relative;">
                <button type="button" id="closeEmployeeInfo" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <h3 id="employeeInfoTitle" style="margin-top:0;">Employee Information</h3>
                <form method="POST" id="employeeInfoForm">
                    <input type="hidden" name="save_employee" value="1">
                    <input type="hidden" name="employee_id" id="info_emp_id" value="">
                    <div class="form-group">
                        <label for="info_emp_name">Name</label>
                        <input type="text" id="info_emp_name" name="employee_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="info_emp_position">Position</label>
                        <input type="text" id="info_emp_position" name="employee_position" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="info_emp_email">Email</label>
                        <input type="email" id="info_emp_email" name="employee_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="info_emp_phone">Phone</label>
                        <input type="text" id="info_emp_phone" name="employee_phone" class="form-control" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancelEmployeeInfo">Cancel</button>
                        <button type="submit" class="save-btn" id="saveEmployeeInfoBtn">Save</button>
                    </div>
                </form>
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
                                <span class="icon-img-placeholder">⏻</span> logout
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
                                        <button class="action-btn view-btn" 
                                                data-type="employee-view" 
                                                data-emp='<?php echo htmlspecialchars(json_encode($employee)); ?>'>View</button>
                                        <button class="action-btn" 
                                                data-type="employee-edit" 
                                                data-emp='<?php echo htmlspecialchars(json_encode($employee)); ?>'>Edit</button>
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
                                <th>Actions</th>
                            </tr>
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
                                        <td>
                                            <button class="action-btn view-btn" data-type="doc-edit" data-doc='<?php echo htmlspecialchars(json_encode($doc)); ?>'>Edit</button>
                                            <button class="action-btn" data-type="doc-delete" data-doc='<?php echo htmlspecialchars(json_encode($doc)); ?>'>Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center;color:#666;padding:20px;">No documents found.</td></tr>
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
                                            <button class="action-btn view-btn" data-type="invoice-view" data-invoice='<?php echo htmlspecialchars(json_encode($b)); ?>'>View</button>
                                            <button class="action-btn" style="background:#16a34a;color:#fff;border-radius:8px;padding:6px 10px;" data-type="invoice-pay" data-invoice='<?php echo htmlspecialchars(json_encode($b)); ?>'>Pay</button>
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
                    <!-- Hidden form to trigger secured PDF generation via POST -->
                    <form id="exportPdfForm" method="POST" style="display:none">
                        <input type="hidden" name="action" value="export_pdf">
                    </form>

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
                            <div class="form-group">
                                <label for="contractImage">Larawang Pang-cover (opsyonal)</label>
                                <input type="file" id="contractImage" name="contract_image" class="form-control" accept="image/*">
                                <div class="file-info">Mga pinapayagang format: JPG, PNG, JPEG (Max: 5MB)</div>
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
                                    <i>+</i> Upload & Analyze Contract
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
                                        <button class="action-btn view-btn" 
                                                data-type="contract-view"
                                                data-contract='<?php echo htmlspecialchars(json_encode($contract)); ?>'>View</button>
                                        <button class="action-btn analyze-btn" 
                                                data-type="contract-analyze"
                                                data-contract='<?php echo htmlspecialchars(json_encode($contract)); ?>'>Analyze</button>
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
                    <!-- Fallback content shown if no dynamic content is provided -->
                    <form id="genericModalForm" style="display:block">
                        <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; margin-bottom:10px;">
                            <h4 style="margin:0 0 6px;">Quick Note</h4>
                            <p style="margin:0 0 8px; color:#475569;">Enter an optional note then submit. This is a default content view that appears when no specific details are loaded.</p>
                            <textarea name="note" rows="3" class="form-control" placeholder="Type your note here…" style="width:100%;"></textarea>
                        </div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="submit" class="save-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Password Gate Modal -->
        <div id="passwordModal" style="display:none; position:fixed; inset:0; background:rgba(2,6,23,.55); backdrop-filter: blur(2px); align-items:center; justify-content:center; z-index:2000;">
            <div style="background:linear-gradient(180deg,#ffffff,#f8fafc); width:92%; max-width:420px; border-radius:14px; padding:22px 18px; position:relative; box-shadow:0 16px 48px rgba(2,6,23,.25); border:1px solid #e2e8f0;">
                <h3 style="margin:0 0 8px; font-weight:800; color:#0f172a;">Security Check</h3>
                <p style="margin:0 0 10px; color:#475569;">Enter password to continue </p>
                <form id="passwordForm">
                    <input type="password" id="pwdInput" class="form-control" placeholder="Enter password " style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:10px;">
                    <div style="display:flex; gap:10px; margin-top:14px; justify-content:flex-end;">
                        <button type="button" class="cancel-btn" id="pwdCancel">Cancel</button>
                        <button type="submit" class="save-btn">Continue</button>
                    </div>
                </form>
                <div id="pwdError" style="color:#e11d48; font-size:.9rem; margin-top:8px; display:none;">Incorrect password.</div>
            </div>
        </div>

        <!-- Edit Employee Modal -->
        <div id="editEmployeeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1200;">
            <div style="background:white; width:92%; max-width:560px; border-radius:10px; padding:20px; position:relative;">
                <button type="button" id="closeEditEmployee" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <h3 style="margin-top:0;">Edit Employee</h3>
                <form method="POST" id="editEmployeeForm">
                    <input type="hidden" name="update_employee" value="1">
                    <input type="hidden" name="employee_id" id="edit_emp_id">
                    <div class="form-group">
                        <label for="edit_emp_name">Name</label>
                        <input type="text" id="edit_emp_name" name="employee_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_emp_position">Position</label>
                        <input type="text" id="edit_emp_position" name="employee_position" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_emp_email">Email</label>
                        <input type="email" id="edit_emp_email" name="employee_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_emp_phone">Phone</label>
                        <input type="text" id="edit_emp_phone" name="employee_phone" class="form-control" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancelEditEmployee">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contract Form Modal wrapper -->
        <div id="contractFormModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1150;">
            <div style="background:white; width:94%; max-width:720px; border-radius:10px; padding:20px; position:relative;">
                <button type="button" id="closeContractFormModal" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <div id="contractFormContainer">
                    <!-- The existing contract form will be moved here dynamically -->
                </div>
            </div>
        </div>
        <!-- Employee Form Modal wrapper -->
        <div id="employeeFormModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1150;">
            <div style="background:white; width:94%; max-width:720px; border-radius:10px; padding:20px; position:relative;">
                <button type="button" id="closeEmployeeFormModal" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <div id="employeeFormContainer"></div>
            </div>
        </div>
        <!-- Document Form Modal wrapper -->
        <div id="documentFormModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1150;">
            <div style="background:white; width:94%; max-width:720px; border-radius:10px; padding:20px; position:relative;">
                <button type="button" id="closeDocumentFormModal" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <div id="documentFormContainer">
                    <h3>Upload Document</h3>
                    <form method="POST" enctype="multipart/form-data" id="documentFormData">
                        <input type="hidden" name="add_document" value="1">
                        <div class="form-group">
                            <label for="doc_name">Document Name</label>
                            <input type="text" id="doc_name" name="doc_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="doc_case">Case ID</label>
                            <input type="text" id="doc_case" name="doc_case" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="doc_file">File</label>
                            <input type="file" id="doc_file" name="doc_file" class="form-control" accept=".pdf,.doc,.docx" required>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="cancel-btn" id="cancelDocumentBtn">Cancel</button>
                            <button type="submit" class="save-btn">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Edit Document Modal -->
        <div id="editDocumentModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1200;">
            <div style="background:white; width:92%; max-width:560px; border-radius:10px; padding:20px; position:relative;">
                <button type="button" id="closeEditDocument" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <h3 style="margin-top:0;">Edit Document</h3>
                <form method="POST" id="editDocumentForm">
                    <input type="hidden" name="update_document" value="1">
                    <input type="hidden" name="document_id" id="edit_doc_id">
                    <div class="form-group">
                        <label for="edit_doc_name">Document Name</label>
                        <input type="text" id="edit_doc_name" name="doc_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_doc_case">Case ID</label>
                        <input type="text" id="edit_doc_case" name="doc_case" class="form-control" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancelEditDocument">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Invoice Form Modal wrapper -->
        <div id="invoiceFormModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1150;">
            <div style="background:white; width:94%; max-width:720px; border-radius:10px; padding:20px; position:relative;">
                <button type="button" id="closeInvoiceFormModal" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <div id="invoiceFormContainer">
                    <h3>Create Invoice</h3>
                    <form method="POST" id="invoiceFormData">
                        <input type="hidden" name="add_invoice" value="1">
                        <div class="form-group">
                            <label for="inv_number">Invoice #</label>
                            <input type="text" id="inv_number" name="invoice_number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="inv_client">Client</label>
                            <input type="text" id="inv_client" name="client" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="inv_amount">Amount</label>
                            <input type="number" step="0.01" id="inv_amount" name="amount" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="inv_due">Due Date</label>
                            <input type="date" id="inv_due" name="due_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="inv_status">Status</label>
                            <select id="inv_status" name="status" class="form-control" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="cancel-btn" id="cancelInvoiceBtn">Cancel</button>
                            <button type="submit" class="save-btn">Save Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Pay confirmation modal -->
        <div id="payConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1600;">
            <div style="background:white; width:92%; max-width:420px; border-radius:14px; padding:20px; position:relative; box-shadow:0 16px 48px rgba(2,6,23,.25); border:1px solid #e2e8f0;">
                <h3 style="margin:0 0 8px;">Confirm Payment</h3>
                <p id="payConfirmText" style="margin:0 0 14px; color:#475569;">Do you want to pay this invoice?</p>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="cancel-btn" id="cancelPayBtn">No</button>
                    <form method="POST" id="payInvoiceForm" style="margin:0;">
                        <input type="hidden" name="pay_invoice" value="1">
                        <input type="hidden" name="invoice_id" id="pay_invoice_id" value="">
                        <button type="submit" class="save-btn" style="background:#16a34a;">Yes, Pay</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Contract: Upload Supporting Document Modal -->
        <div id="contractDocsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1500;">
            <div style="background:white; width:94%; max-width:640px; border-radius:12px; padding:20px; position:relative;">
                <button type="button" id="closeContractDocsModal" style="position:absolute; right:12px; top:12px; background:#e74c3c; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;">Close</button>
                <h3 style="margin-top:0;">Upload Contract Document</h3>
                <form method="POST" enctype="multipart/form-data" id="contractDocsForm">
                    <input type="hidden" name="add_contract_document" value="1">
                    <input type="hidden" name="contract_id" id="contract_docs_contract_id" value="">
                    <div class="form-group">
                        <label for="contract_doc_name">Document Name</label>
                        <input type="text" id="contract_doc_name" name="doc_name" class="form-control" placeholder="e.g., Annex A, Addendum, Scanned Signature" required>
                    </div>
                    <div class="form-group">
                        <label for="contract_doc_file">File</label>
                        <input type="file" id="contract_doc_file" name="doc_file" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" id="cancelContractDocsBtn">Cancel</button>
                        <button type="submit" class="save-btn">Upload Document</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        (function(){
            const detailsModal = document.getElementById('detailsModal');
            const detailsTitle = document.getElementById('detailsTitle');
            const detailsBody  = document.getElementById('detailsBody');
            const closeDetails = document.getElementById('closeDetails');
            const pwdModal     = document.getElementById('passwordModal');
            const pwdForm      = document.getElementById('passwordForm');
            const pwdInput     = document.getElementById('pwdInput');
            const pwdError     = document.getElementById('pwdError');
            const pwdCancel    = document.getElementById('pwdCancel');
            const PASSWORD     = '123';

            // Password gate helper — shows password modal and calls callback on successful entry.
            function withPasswordGate(cb){
                if (typeof cb !== 'function') return;
                if (!pwdModal || !pwdForm || !pwdInput) { cb(); return; }

                // Close/hide other modals and remember previous display states
                const modalsToHide = [detailsModal, empInfoModal, contractFormModal, employeeFormModal, documentFormModal, editModal, editDocModal, invoiceFormModal, payConfirmModal, contractDocsModal];
                const prevDisplay = new Map();
                modalsToHide.forEach(m => {
                    try {
                        if (!m) return;
                        prevDisplay.set(m, m.style.display || '');
                        // use closeModal helper to ensure consistent behavior
                        closeModal(m);
                    } catch(e){}
                });

                // Add class to body to visually hide everything except the password modal
                document.body.classList.add('pwd-focus');

                // Reset UI and show password modal
                pwdError.style.display = 'none';
                pwdInput.value = '';
                openModal(pwdModal);

                // Submit handler (one-time)
                const onSubmit = function(e){
                    e.preventDefault();
                    if (pwdInput.value === PASSWORD){
                        try { closeModal(pwdModal); } catch(e){}
                        pwdForm.removeEventListener('submit', onSubmit);
                        // remove body class and proceed
                        document.body.classList.remove('pwd-focus');
                        cb();
                    } else {
                        pwdError.style.display = 'block';
                    }
                };

                // Cancel/cleanup - restore previous modal visibility states and remove body class
                const onCancelCleanup = function(){
                    try { pwdForm.removeEventListener('submit', onSubmit); } catch(e){}
                    try { closeModal(pwdModal); } catch(e){}
                    prevDisplay.forEach((disp, m) => {
                        try { m.style.display = disp || 'none'; } catch(e){}
                    });
                    document.body.classList.remove('pwd-focus');
                };

                pwdForm.addEventListener('submit', onSubmit);
                pwdCancel.addEventListener('click', onCancelCleanup, { once:true });
            }

            const editModal    = document.getElementById('editEmployeeModal');
            const closeEdit    = document.getElementById('closeEditEmployee');
            const cancelEdit   = document.getElementById('cancelEditEmployee');
            const editForm     = document.getElementById('editEmployeeForm');
            const fId = document.getElementById('edit_emp_id');
            const fName = document.getElementById('edit_emp_name');
            const fPos = document.getElementById('edit_emp_position');
            const fEmail = document.getElementById('edit_emp_email');
            const fPhone = document.getElementById('edit_emp_phone');

            // Employee info modal (unified create/update)
            const empInfoModal = document.getElementById('employeeInfoModal');
            const closeEmpInfo = document.getElementById('closeEmployeeInfo');
            const cancelEmpInfo = document.getElementById('cancelEmployeeInfo');
            const empInfoForm = document.getElementById('employeeInfoForm');
            const infoId = document.getElementById('info_emp_id');
            const infoName = document.getElementById('info_emp_name');
            const infoPos = document.getElementById('info_emp_position');
            const infoEmail = document.getElementById('info_emp_email');
            const infoPhone = document.getElementById('info_emp_phone');
            const employeeInfoTitle = document.getElementById('employeeInfoTitle');
            const contractForm = document.getElementById('contractForm');
            const contractFormModal = document.getElementById('contractFormModal');
            const contractFormContainer = document.getElementById('contractFormContainer');
            const addContractBtn = document.getElementById('addContractBtn');
            const cancelContractBtn = document.getElementById('cancelContractBtn');
            const closeContractFormModal = document.getElementById('closeContractFormModal');
            const exportPdfBtn = document.getElementById('exportPdfBtn');
            const exportPdfForm = document.getElementById('exportPdfForm');
            // Employee form modal
            const employeeForm = document.getElementById('employeeForm');
            const employeeFormModal = document.getElementById('employeeFormModal');
            const employeeFormContainer = document.getElementById('employeeFormContainer');
            const addEmployeeBtn = document.getElementById('addEmployeeBtn');
            const closeEmployeeFormModal = document.getElementById('closeEmployeeFormModal');
            // Document form modal
            const documentFormModal = document.getElementById('documentFormModal');
            const documentFormContainer = document.getElementById('documentFormContainer');
            const addDocumentBtn = document.getElementById('addDocumentBtn');
            const cancelDocumentBtn = document.getElementById('cancelDocumentBtn');
            const closeDocumentFormModal = document.getElementById('closeDocumentFormModal');
            // Edit document modal
            const editDocModal = document.getElementById('editDocumentModal');
            const closeEditDoc = document.getElementById('closeEditDocument');
            const cancelEditDoc = document.getElementById('cancelEditDocument');
            const editDocForm = document.getElementById('editDocumentForm');
            const editDocId = document.getElementById('edit_doc_id');
            const editDocName = document.getElementById('edit_doc_name');
            const editDocCase = document.getElementById('edit_doc_case');
            // Invoice form modal
            const invoiceFormModal = document.getElementById('invoiceFormModal');
            const addInvoiceBtn = document.getElementById('addInvoiceBtn');
            const closeInvoiceFormModal = document.getElementById('closeInvoiceFormModal');
            const cancelInvoiceBtn = document.getElementById('cancelInvoiceBtn');
            // Pay modal
            const payConfirmModal = document.getElementById('payConfirmModal');
            const cancelPayBtn = document.getElementById('cancelPayBtn');
            const payInvoiceId = document.getElementById('pay_invoice_id');
            const payConfirmText = document.getElementById('payConfirmText');
            // Contract docs modal
            const contractDocsModal = document.getElementById('contractDocsModal');
            const closeContractDocsModal = document.getElementById('closeContractDocsModal');
            const cancelContractDocsBtn = document.getElementById('cancelContractDocsBtn');
            const contractDocsContractId = document.getElementById('contract_docs_contract_id');

            function openModal(el){ el.style.display = 'flex'; }
            function closeModal(el){ el.style.display = 'none'; }

            closeDetails.addEventListener('click', () => closeModal(detailsModal));
            // Default submit handler for generic modal content
            document.addEventListener('submit', (e)=>{
                const t = e.target;
                if (t && t.id === 'genericModalForm'){
                    e.preventDefault();
                    // Simple acknowledgement and close
                    detailsBody.innerHTML = `<div style="padding:10px; color:#16a34a;">Submitted. Thank you!</div>`;
                    setTimeout(()=> closeModal(detailsModal), 800);
                }
            });
            pwdCancel.addEventListener('click', () => closeModal(pwdModal));
            [closeEdit, cancelEdit].forEach(b => b.addEventListener('click', ()=> closeModal(editModal)));
            [closeContractFormModal].forEach(b => b.addEventListener('click', ()=> closeModal(contractFormModal)));
            cancelContractBtn?.addEventListener('click', ()=> closeModal(contractFormModal));
            [closeEmployeeFormModal].forEach(b => b.addEventListener('click', ()=> closeModal(employeeFormModal)));
            [closeDocumentFormModal].forEach(b => b.addEventListener('click', ()=> closeModal(documentFormModal)));
            cancelDocumentBtn?.addEventListener('click', ()=> closeModal(documentFormModal));
            [closeEditDoc, cancelEditDoc].forEach(b => b.addEventListener('click', ()=> closeModal(editDocModal)));
            [closeInvoiceFormModal].forEach(b => b.addEventListener('click', ()=> closeModal(invoiceFormModal)));
            cancelInvoiceBtn?.addEventListener('click', ()=> closeModal(invoiceFormModal));
            cancelPayBtn?.addEventListener('click', ()=> closeModal(payConfirmModal));
            [closeEmpInfo, cancelEmpInfo].forEach(b => b.addEventListener('click', ()=>{
                // reset form to editable and show actions when modal is closed
                try {
                    const actions = empInfoForm.querySelector('.form-actions');
                    if (actions) actions.style.display = '';
                    [infoName, infoPos, infoEmail, infoPhone].forEach(i => { if (i) { i.readOnly = false; i.disabled = false; } });
                } catch(e){}
                closeModal(empInfoModal);
            }));

            // Wire employee action buttons
            document.querySelectorAll('[data-type="employee-view"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const emp = JSON.parse(btn.getAttribute('data-emp') || '{}');
                    withPasswordGate(()=>{
                        // populate fields
                        employeeInfoTitle.textContent = 'Employee Information';
                        infoId.value = emp.id || '';
                        infoName.value = emp.name || '';
                        infoPos.value = emp.position || '';
                        infoEmail.value = emp.email || '';
                        infoPhone.value = emp.phone || '';

                        // Make inputs read-only for view mode
                        [infoName, infoPos, infoEmail, infoPhone].forEach(i => {
                            if (i) { i.readOnly = true; i.disabled = false; }
                        });

                        // Hide form action buttons (Save / Cancel) for pure view
                        const actions = empInfoForm.querySelector('.form-actions');
                        if (actions) actions.style.display = 'none';

                        openModal(empInfoModal);
                    });
                });
            });

            document.querySelectorAll('[data-type="employee-edit"]').forEach(btn=>{

                btn.addEventListener('click', ()=>{
                    const emp = JSON.parse(btn.getAttribute('data-emp') || '{}');
                    withPasswordGate(()=>{
                        // populate fields
                        employeeInfoTitle.textContent = 'Edit Employee';
                        infoId.value = emp.id || '';
                        infoName.value = emp.name || '';
                        infoPos.value = emp.position || '';
                        infoEmail.value = emp.email || '';
                        infoPhone.value = emp.phone || '';

                        // Make inputs editable for edit mode
                        [infoName, infoPos, infoEmail, infoPhone].forEach(i => {
                            if (i) { i.readOnly = false; i.disabled = false; }
                        });

                        // Show form action buttons
                        const actions = empInfoForm.querySelector('.form-actions');
                        if (actions) actions.style.display = '';

                        openModal(empInfoModal);
                    });
                });
            });

            // Render the contract form into the modal (fallback-safe)
            function renderContractFormInModal(){
                // If the original form exists and hasn't been moved yet, clone its inner fields
                if (contractForm && contractForm.querySelector('form')){
                    // Build a fresh form to avoid duplicate IDs
                    contractFormContainer.innerHTML = `
                        <h3>Upload Contract <span class="ai-badge">AI Risk Analysis</span></h3>
                        <form method="POST" enctype="multipart/form-data">
                                                       <input type="hidden" name="add_contract" value="1">
                            <div class="form-group">
                                <label for="contractNameModal">Contract Name</label>
                                <input type="text" id="contractNameModal" name="contract_name" class="form-control" placeholder="Enter contract name" required>
                            </div>
                            <div class="form-group">
                                <label for="contractCaseModal">Case ID</label>
                                <input type="text" id="contractCaseModal" name="contract_case" class="form-control" placeholder="Enter case ID (e.g., C-001)" required>
                            </div>
                            <div class="form-group">
                                <label for="contractDescriptionModal">Contract Description</label>
                                <textarea id="contractDescriptionModal" name="contract_description" class="form-control" placeholder="Describe the contract terms, key clauses, and important details for AI analysis" rows="4"></textarea>
                                <div class="file-info">AI will analyze this description to detect risk factors</div>
                            </div>
                            <div class="form-group">
                                <label for="contractFileModal">Contract File</label>
                                <input type="file" id="contractFileModal" name="contract_file" class="form-control" accept=".pdf,.doc,.docx" required>
                                <div class="file-info">Accepted formats: PDF, DOC, DOCX (Max: 10MB)</div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-btn" id="cancelContractBtnModal">Cancel</button>
                                <button type="submit" class="save-btn">
                                    <i>+</i> Upload & Analyze Contract
                                </button>
                            </div>
                        </form>
                    `;
                    // Hook up cancel inside modal
                    const cancelBtnLocal = contractFormContainer.querySelector('#cancelContractBtnModal');
                    cancelBtnLocal?.addEventListener('click', ()=> closeModal(contractFormModal));
                } else {
                    // Absolute fallback (shouldn't happen) - minimal info
                    contractFormContainer.innerHTML = `
                        <h3>Upload Contract</h3>
                        <div class="alert alert-error">Original form not found. Using fallback form.</div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="add_contract" value="1">
                            <div class="form-group">
                                <label>Contract Name</label>
                                <input type="text" name="contract_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Case ID</label>
                                <input type="text" name="contract_case" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Contract File</label>
                                <input type="file" name="contract_file" class="form-control" accept=".pdf,.doc,.docx" required>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-btn" id="cancelContractBtnModal">Cancel</button>
                                <button type="submit" class="save-btn">Upload</button>
                            </div>
                        </form>
                    `;
                    const cancelBtnLocal = contractFormContainer.querySelector('#cancelContractBtnModal');
                    cancelBtnLocal?.addEventListener('click', ()=> closeModal(contractFormModal));
                }
            }

            // Open the contract modal with content
            if (addContractBtn && contractForm && contractFormContainer){
                addContractBtn.addEventListener('click', ()=>{
                    // Always render fresh content so it's never empty
                    renderContractFormInModal();
                    openModal(contractFormModal);
                });
            }

            // Move employee form into modal container when opening
            if (addEmployeeBtn && employeeForm && employeeFormContainer){
                addEmployeeBtn.addEventListener('click', ()=>{
                    // Use unified modal for adding
                    employeeInfoTitle.textContent = 'Add Employee';
                    infoId.value = '';
                    infoName.value = '';
                    infoPos.value = '';
                    infoEmail.value = '';
                    infoPhone.value = '';
                    openModal(empInfoModal);
                });
            }

            // Open Document upload modal
            addDocumentBtn?.addEventListener('click', ()=> openModal(documentFormModal));

            // Document edit/delete buttons
            document.querySelectorAll('[data-type="doc-edit"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const d = JSON.parse(btn.getAttribute('data-doc') || '{}');
                    withPasswordGate(()=>{
                        editDocId.value = d.id || '';
                        editDocName.value = d.name || '';
                        editDocCase.value = d.case_id || '';
                        openModal(editDocModal);
                    });
                });
            });
            document.querySelectorAll('[data-type="doc-delete"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const d = JSON.parse(btn.getAttribute('data-doc') || '{}');
                    withPasswordGate(()=>{
                        if (confirm('Delete document "' + (d.name||'') + '"?')){
                            const f = document.createElement('form');
                            f.method = 'POST';
                            f.innerHTML = '<input type="hidden" name="delete_document" value="1"><input type="hidden" name="document_id" value="'+ (d.id||'') +'">';
                            document.body.appendChild(f); f.submit();
                        }
                    });
                });
            });

            // Open invoice form modal
            addInvoiceBtn?.addEventListener('click', ()=> openModal(invoiceFormModal));

            // Billing actions: view + pay with password, then confirmation for pay
            document.querySelectorAll('[data-type="invoice-view"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const inv = JSON.parse(btn.getAttribute('data-invoice') || '{}');
                    withPasswordGate(()=>{
                        detailsTitle.textContent = 'Invoice Details';
                        detailsBody.innerHTML = `
                          <div style="display:grid; grid-template-columns:160px 1fr; gap:8px; line-height:1.8;">
                            <div><strong>Invoice #</strong></div><div>${inv.invoice_number || inv.id || ''}</div>
                            <div><strong>Client</strong></div><div>${inv.client || ''}</div>
                            <div><strong>Amount</strong></div><div>₱${Number(inv.amount||0).toFixed(2)}</div>
                            <div><strong>Due Date</strong></div><div>${inv.due_date || ''}</div>
                            <div><strong>Status</strong></div><div>${(inv.status||'').toString().toUpperCase()}</div>
                          </div>`;
                        openModal(detailsModal);
                    });
                });
            });
            document.querySelectorAll('[data-type="invoice-pay"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const inv = JSON.parse(btn.getAttribute('data-invoice') || '{}');
                    withPasswordGate(()=>{
                        payInvoiceId.value = inv.id || '';
                        payConfirmText.textContent = `Do you want to pay invoice ${inv.invoice_number || inv.id || ''} for ₱${Number(inv.amount||0).toFixed(2)}?`;
                        openModal(payConfirmModal);
                    });
                });
            });

            // Contract row actions
            document.querySelectorAll('[data-type="contract-view"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const c = JSON.parse(btn.getAttribute('data-contract') || '{}');
                    withPasswordGate(()=>{
                        detailsTitle.textContent = 'Contract Details';
                        detailsBody.innerHTML = `<div style="padding:10px;color:#64748b;">Loading details…</div>`;
                        openModal(detailsModal);
                        try {
                            const rf = (()=>{ try { return JSON.parse(c.risk_factors || '[]'); } catch { return []; } })();
                            const rec = (()=>{ try { return JSON.parse(c.recommendations || '[]'); } catch { return []; } })();
                            const uploaded = (c.created_at ? new Date(c.created_at).toLocaleDateString() : '');
                            detailsBody.innerHTML = `
                                <div style="display:grid; grid-template-columns:160px 1fr; gap:8px; line-height:1.8;">
                                    <div><strong>Contract</strong></div><div>${c.contract_name || ''}</div>
                                    <div><strong>Case</strong></div><div>${c.case_id || ''}</div>
                                    <div><strong>Risk</strong></div><div>${(c.risk_level || 'N/A')} — ${c.risk_score || 'N/A'}/100</div>
                                    <div><strong>Uploaded</strong></div><div>${uploaded}</div>
                                    <div style="grid-column:1/-1"><strong>Risk Factors</strong><ul style="margin:.4rem 0 0 1rem;">${rf.map(r=>`<li>${(r.factor||'')}</li>`).join('') || '<li>None</li>'}</ul></div>
                                    <div style="grid-column:1/-1"><strong>Recommendations</strong><ul style="margin:.4rem 0 0 1rem;">${rec.map(x=>`<li>${x}</li>`).join('') || '<li>None</li>'}</ul></div>
                                </div>`;
                        } catch (err) {
                            detailsBody.innerHTML = `<div style="padding:10px;color:#b91c1c;">Unable to load details. Please try again.</div>`;
                        }
                    });
                });
            });
            document.querySelectorAll('[data-type="contract-analyze"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const c = JSON.parse(btn.getAttribute('data-contract') || '{}');
                    withPasswordGate(()=>{
                        detailsTitle.textContent = 'AI Risk Analysis';
                        detailsBody.innerHTML = `<div style="padding:10px;color:#64748b;">Loading analysis…</div>`;
                        openModal(detailsModal);
                        try {
                            const score = c.risk_score ?? 'N/A';
                            const level = c.risk_level ?? 'Unknown';
                            detailsBody.innerHTML = `
                                <div style="display:flex; align-items:center; gap:16px; margin-bottom:12px;">
                                    <div style="width:64px; height:64px; border-radius:50%; background:#eef2ff; display:grid; place-items:center; color:#4338ca;">
                                        <span style="font-size:20px; font-weight:700;">${score}</span>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;">Risk Score</div>
                                        <div style="color:#64748b;">Level: ${level}</div>
                                    </div>
                                </div>
                                <div style="height:10px; background:#e5e7eb; border-radius:999px; overflow:hidden;">
                                    <div style="height:100%; width:${Number(score)||0}%; background:${level==='High'?'#ef4444':(level==='Medium'?'#f59e0b':'#22c55e')};"></div>
                                </div>
                                <p style="margin-top:10px; color:#64748b;">Password protected analysis view.</p>`;
                        } catch (err) {
                            detailsBody.innerHTML = `<div style="padding:10px;color:#b91c1c;">Unable to load analysis. Please try again.</div>`;
                        }
                    });
                });
            });
            // Contract: upload supporting document
            document.querySelectorAll('[data-type="contract-upload-doc"]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const c = JSON.parse(btn.getAttribute('data-contract') || '{}');
                    withPasswordGate(()=>{
                        contractDocsContractId.value = c.id || '';
                        openModal(contractDocsModal);
                    });
                });
            });

            // Risk chart init (avoid loop/double init)
            let riskChartRef = null;
            function initRiskChart(){
                const ctx = document.getElementById('riskChart');
                if (!ctx) return;
                const data = {
                    labels: ['High','Medium','Low'],
                    datasets: [{
                        label: 'Contracts',
                        data: [<?php echo $riskCounts['High']; ?>, <?php echo $riskCounts['Medium']; ?>, <?php echo $riskCounts['Low']; ?>],
                        backgroundColor: ['#ef4444','#f59e0b','#22c55e']
                    }]
                };
                if (riskChartRef){ riskChartRef.destroy(); }
                riskChartRef = new Chart(ctx, { type:'bar', data, options:{ responsive:true, plugins:{ legend:{ display:false } } } });
            }
            document.addEventListener('DOMContentLoaded', initRiskChart);

            // Generate Secured PDF (password-gated)
            exportPdfBtn?.addEventListener('click', ()=>{
                withPasswordGate(()=>{
                    exportPdfForm?.submit();
                });
            });
        })();
        </script>
    </body>
    </html>