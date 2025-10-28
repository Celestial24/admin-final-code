<?php
// Database Configuration
class Database {
    private $host = "localhost";
    private $db_name = "hotel_document_management";
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

// File upload configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif']);

// Document Class
class Document {
    private $conn;
    private $table_name = "documents";

    public $id;
    public $name;
    public $category;
    public $file_path;
    public $file_size;
    public $description;
    public $upload_date;
    public $is_deleted;
    public $deleted_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new document
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, category=:category, file_path=:file_path, 
                    file_size=:file_size, description=:description, upload_date=:upload_date";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->file_path = htmlspecialchars(strip_tags($this->file_path));
        $this->file_size = htmlspecialchars(strip_tags($this->file_size));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->upload_date = htmlspecialchars(strip_tags($this->upload_date));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":file_path", $this->file_path);
        $stmt->bindParam(":file_size", $this->file_size);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":upload_date", $this->upload_date);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get all active documents
    public function readActive() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_deleted = 0 ORDER BY upload_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get all deleted documents
    public function readDeleted() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_deleted = 1 ORDER BY deleted_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get single document
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->name = $row['name'];
            $this->category = $row['category'];
            $this->file_path = $row['file_path'];
            $this->file_size = $row['file_size'];
            $this->description = $row['description'];
            $this->upload_date = $row['upload_date'];
            $this->is_deleted = $row['is_deleted'];
            $this->deleted_date = $row['deleted_date'];
            return true;
        }
        return false;
    }

    // Move document to trash
    public function moveToTrash() {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_deleted = 1, deleted_date = :deleted_date 
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":deleted_date", $this->deleted_date);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Restore document from trash
    public function restore() {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_deleted = 0, deleted_date = NULL 
                 WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Permanently delete document
    public function deletePermanent() {
        // First get file path to delete physical file
        $query = "SELECT file_path FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $file_path = $row['file_path'];
            // Delete physical file
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Delete from database
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Search documents
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE (name LIKE ? OR description LIKE ? OR category LIKE ?) 
                 AND is_deleted = 0 
                 ORDER BY upload_date DESC";

        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->execute();
        return $stmt;
    }
}

// Handle API requests
if (isset($_GET['api'])) {
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    $database = new Database();
    $db = $database->getConnection();
    $document = new Document($db);

    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            // Get all active documents
            if(isset($_GET['action']) && $_GET['action'] == 'active') {
                $stmt = $document->readActive();
                $documents = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $documents[] = $row;
                }
                echo json_encode($documents);
            }
            // Get all deleted documents
            elseif(isset($_GET['action']) && $_GET['action'] == 'deleted') {
                $stmt = $document->readDeleted();
                $documents = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $documents[] = $row;
                }
                echo json_encode($documents);
            }
            // Get single document
            elseif(isset($_GET['id'])) {
                $document->id = $_GET['id'];
                if($document->readOne()) {
                    echo json_encode([
                        'id' => $document->id,
                        'name' => $document->name,
                        'category' => $document->category,
                        'file_path' => $document->file_path,
                        'file_size' => $document->file_size,
                        'description' => $document->description,
                        'upload_date' => $document->upload_date,
                        'is_deleted' => $document->is_deleted,
                        'deleted_date' => $document->deleted_date
                    ]);
                } else {
                    echo json_encode(["message" => "Document not found."]);
                }
            }
            // Search documents
            elseif(isset($_GET['search'])) {
                $stmt = $document->search($_GET['search']);
                $documents = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $documents[] = $row;
                }
                echo json_encode($documents);
            }
            break;

        case 'POST':
            // Handle file upload
            if(isset($_FILES['file'])) {
                $response = uploadFile();
                if($response['success']) {
                    $document->name = $_POST['name'];
                    $document->category = $_POST['category'];
                    $document->file_path = $response['file_path'];
                    $document->file_size = $response['file_size'];
                    $document->description = $_POST['description'];
                    $document->upload_date = date('Y-m-d');

                    if($document->create()) {
                        echo json_encode(["message" => "Document uploaded successfully."]);
                    } else {
                        echo json_encode(["message" => "Unable to upload document."]);
                    }
                } else {
                    echo json_encode(["message" => $response['message']]);
                }
            }
            // Move to trash
            elseif(isset($_POST['action']) && $_POST['action'] == 'trash') {
                $document->id = $_POST['id'];
                $document->deleted_date = date('Y-m-d');
                if($document->moveToTrash()) {
                    echo json_encode(["message" => "Document moved to trash."]);
                } else {
                    echo json_encode(["message" => "Unable to move document to trash."]);
                }
            }
            // Restore from trash
            elseif(isset($_POST['action']) && $_POST['action'] == 'restore') {
                $document->id = $_POST['id'];
                if($document->restore()) {
                    echo json_encode(["message" => "Document restored successfully."]);
                } else {
                    echo json_encode(["message" => "Unable to restore document."]);
                }
            }
            break;

        case 'DELETE':
            parse_str(file_get_contents("php://input"), $delete_vars);
            $document->id = $delete_vars['id'];
            if($document->deletePermanent()) {
                echo json_encode(["message" => "Document permanently deleted."]);
            } else {
                echo json_encode(["message" => "Unable to delete document."]);
            }
            break;
    }
    exit;
}

function uploadFile() {
    $upload_dir = UPLOAD_DIR;
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['file'];
    $file_name = basename($file['name']);
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];

    // Check for errors
    if($file_error !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.'];
    }

    // Check file size
    if($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File is too large.'];
    }

    // Check file type
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if(!in_array($file_ext, ALLOWED_TYPES)) {
        return ['success' => false, 'message' => 'File type not allowed.'];
    }

    // Generate unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $file_path = $upload_dir . $new_filename;

    // Move file to upload directory
    if(move_uploaded_file($file_tmp, $file_path)) {
        return [
            'success' => true,
            'file_path' => $file_path,
            'file_size' => formatFileSize($file_size)
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel & Restaurant Document Management</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        nav ul li a:hover, nav ul li a.active {
            background-color: rgba(255,255,255,0.1);
        }
        
        main {
            padding: 2rem 0;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }
        
        .sidebar {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .sidebar h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .sidebar ul {
            list-style: none;
        }
        
        .sidebar ul li {
            margin-bottom: 10px;
        }
        
        .sidebar ul li a {
            display: block;
            padding: 8px 12px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: var(--light);
            color: var(--secondary);
        }
        
        .content {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .search-box {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        
        .search-box button {
            padding: 10px 15px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .file-card {
            background-color: var(--light);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .file-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--secondary);
        }
        
        .file-name {
            font-weight: 500;
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .file-meta {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .file-actions {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        
        .file-actions button {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .tab.active {
            border-bottom: 2px solid var(--secondary);
            color: var(--secondary);
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid var(--success);
            color: #27ae60;
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid var(--danger);
            color: #c0392b;
        }
        
        footer {
            text-align: center;
            padding: 20px 0;
            margin-top: 40px;
            color: #7f8c8d;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
                justify-content: center;
            }
            
            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">Hotel<span>Archive</span></div>
                <nav>
                    <ul>
                        <li><a href="#" class="active">Dashboard</a></li>
                        <li><a href="#">Settings</a></li>
                        <li><a href="#">Help</a></li>
                        <li><a href="#">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="dashboard">
            <aside class="sidebar">
                <h3>Categories</h3>
                <ul>
                    <li><a href="#" class="active">All Documents</a></li>
                    <li><a href="fn.php">Financial Records</a></li>
                    <li><a href="#">HR Documents</a></li>
                    <li><a href="#">Guest Records</a></li>
                    <li><a href="#">Inventory</a></li>
                    <li><a href="#">Compliance</a></li>
                    <li><a href="#">Marketing</a></li>
                    <li><a href="#">Trash Bin</a></li>
                </ul>
                
                <h3>Quick Stats</h3>
                <ul>
                    <li>Total Files: 127</li>
                    <li>Storage Used: 2.3 GB</li>
                    <li>Files in Trash: 12</li>
                </ul>
            </aside>
            
            <div class="content">
                <div class="content-header">
                    <h2>Document Management</h2>
                    <button class="btn btn-primary" id="uploadBtn">Upload Document</button>
                </div>
                
                <div class="tabs">
                    <div class="tab active" data-tab="active">Active Files</div>
                    <div class="tab" data-tab="trash">Trash Bin</div>
                </div>
                
                <div class="tab-content active" id="active-tab">
                    <div class="search-box">
                        <input type="text" placeholder="Search documents...">
                        <button>Search</button>
                    </div>
                    
                    <div class="file-grid" id="activeFiles">
                        <!-- Active files will be populated here -->
                    </div>
                </div>
                
                <div class="tab-content" id="trash-tab">
                    <div class="alert alert-danger">
                        Files in trash will be permanently deleted after 30 days.
                    </div>
                    
                    <div class="file-grid" id="trashFiles">
                        <!-- Trash files will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Upload Modal -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Document</h3>
                <span class="close">&times;</span>
            </div>
            <form id="uploadForm">
                <div class="form-group">
                    <label for="fileInput">Select File</label>
                    <input type="file" id="fileInput" required>
                </div>
                <div class="form-group">
                    <label for="fileName">File Name</label>
                    <input type="text" id="fileName" placeholder="Enter file name" required>
                </div>
                <div class="form-group">
                    <label for="fileCategory">Category</label>
                    <select id="fileCategory" required>
                        <option value="">Select Category</option>
                        <option value="Financial Records">Financial Records</option>
                        <option value="HR Documents">HR Documents</option>
                        <option value="Guest Records">Guest Records</option>
                        <option value="Inventory">Inventory</option>
                        <option value="Compliance">Compliance</option>
                        <option value="Marketing">Marketing</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fileDescription">Description (Optional)</label>
                    <input type="text" id="fileDescription" placeholder="Enter file description">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" id="cancelUpload">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- File Details Modal -->
    <div class="modal" id="fileDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>File Details</h3>
                <span class="close">&times;</span>
            </div>
            <div id="fileDetailsContent">
                <!-- File details will be populated here -->
            </div>
        </div>
    </div>
    
    <footer class="container">
        <p>Hotel & Restaurant Document Management System &copy; 2023</p>
    </footer>

    <script>
        // DOM Elements
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadModal = document.getElementById('uploadModal');
        const fileDetailsModal = document.getElementById('fileDetailsModal');
        const cancelUpload = document.getElementById('cancelUpload');
        const uploadForm = document.getElementById('uploadForm');
        const tabs = document.querySelectorAll('.tab');
        const closeButtons = document.querySelectorAll('.close');
        const activeFilesContainer = document.getElementById('activeFiles');
        const trashFilesContainer = document.getElementById('trashFiles');
        const fileDetailsContent = document.getElementById('fileDetailsContent');
        const searchInput = document.querySelector('.search-box input');
        const searchButton = document.querySelector('.search-box button');

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            loadActiveFiles();
            
            uploadBtn.addEventListener('click', function() {
                uploadModal.style.display = 'flex';
            });
            
            cancelUpload.addEventListener('click', function() {
                uploadModal.style.display = 'none';
            });
            
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFileUpload();
            });
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Hide all tab content
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    
                    // Show corresponding tab content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                    
                    // Load appropriate files
                    if(tabId === 'active') {
                        loadActiveFiles();
                    } else if(tabId === 'trash') {
                        loadTrashFiles();
                    }
                });
            });
            
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    uploadModal.style.display = 'none';
                    fileDetailsModal.style.display = 'none';
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === uploadModal) {
                    uploadModal.style.display = 'none';
                }
                if (e.target === fileDetailsModal) {
                    fileDetailsModal.style.display = 'none';
                }
            });

            // Search functionality
            searchButton.addEventListener('click', function() {
                const searchTerm = searchInput.value.trim();
                if(searchTerm) {
                    searchFiles(searchTerm);
                } else {
                    loadActiveFiles();
                }
            });

            searchInput.addEventListener('keypress', function(e) {
                if(e.key === 'Enter') {
                    const searchTerm = searchInput.value.trim();
                    if(searchTerm) {
                        searchFiles(searchTerm);
                    } else {
                        loadActiveFiles();
                    }
                }
            });
        });

        // Functions
        async function loadActiveFiles() {
            try {
                const response = await fetch('?api=true&action=active');
                const files = await response.json();
                renderFiles(files, activeFilesContainer, false);
            } catch (error) {
                console.error('Error loading active files:', error);
                activeFilesContainer.innerHTML = '<p>Error loading files. Please try again.</p>';
            }
        }

        async function loadTrashFiles() {
            try {
                const response = await fetch('?api=true&action=deleted');
                const files = await response.json();
                renderFiles(files, trashFilesContainer, true);
            } catch (error) {
                console.error('Error loading trash files:', error);
                trashFilesContainer.innerHTML = '<p>Error loading trash files. Please try again.</p>';
            }
        }

        async function searchFiles(searchTerm) {
            try {
                const response = await fetch(`?api=true&search=${encodeURIComponent(searchTerm)}`);
                const files = await response.json();
                renderFiles(files, activeFilesContainer, false);
            } catch (error) {
                console.error('Error searching files:', error);
                activeFilesContainer.innerHTML = '<p>Error searching files. Please try again.</p>';
            }
        }

        function renderFiles(files, container, isTrash = false) {
            container.innerHTML = '';
            
            if(files.length === 0) {
                container.innerHTML = '<p>No files found.</p>';
                return;
            }
            
            files.forEach(file => {
                container.appendChild(createFileCard(file, isTrash));
            });
        }
        
        function createFileCard(file, isTrash = false) {
            const fileCard = document.createElement('div');
            fileCard.className = 'file-card';
            fileCard.setAttribute('data-id', file.id);
            
            // Determine file icon based on extension
            const fileExtension = file.name.split('.').pop().toLowerCase();
            let fileIcon = '📄'; // Default icon
            
            if (fileExtension === 'pdf') fileIcon = '📕';
            else if (['doc', 'docx'].includes(fileExtension)) fileIcon = '📘';
            else if (['xls', 'xlsx'].includes(fileExtension)) fileIcon = '📗';
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) fileIcon = '🖼️';
            
            fileCard.innerHTML = `
                <div class="file-icon">${fileIcon}</div>
                <div class="file-name">${file.name}</div>
                <div class="file-meta">
                    <div>${file.category}</div>
                    <div>${file.upload_date} • ${file.file_size}</div>
                </div>
                <div class="file-actions">
                    ${isTrash ? 
                        `<button class="btn btn-success restore-btn">Restore</button>
                         <button class="btn btn-danger delete-btn">Delete Permanently</button>` :
                        `<button class="btn btn-primary view-btn">View</button>
                         <button class="btn btn-danger delete-btn">Delete</button>`
                    }
                </div>
            `;
            
            // Add event listeners to buttons
            const viewBtn = fileCard.querySelector('.view-btn');
            const deleteBtn = fileCard.querySelector('.delete-btn');
            const restoreBtn = fileCard.querySelector('.restore-btn');
            
            if (viewBtn) {
                viewBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    showFileDetails(file.id);
                });
            }
            
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (isTrash) {
                        deleteFilePermanently(file.id);
                    } else {
                        moveToTrash(file.id);
                    }
                });
            }
            
            if (restoreBtn) {
                restoreBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    restoreFile(file.id);
                });
            }
            
            fileCard.addEventListener('click', function() {
                showFileDetails(file.id);
            });
            
            return fileCard;
        }
        
        async function showFileDetails(fileId) {
            try {
                const response = await fetch(`?api=true&id=${fileId}`);
                const file = await response.json();
                
                if(file.message) {
                    alert(file.message);
                    return;
                }
                
                fileDetailsContent.innerHTML = `
                    <div class="file-details">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="font-size: 3rem;">${getFileIcon(file.name)}</div>
                            <h3>${file.name}</h3>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <div>${file.category}</div>
                        </div>
                        <div class="form-group">
                            <label>Upload Date</label>
                            <div>${file.upload_date}</div>
                        </div>
                        <div class="form-group">
                            <label>File Size</label>
                            <div>${file.file_size}</div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <div>${file.description || 'No description provided'}</div>
                        </div>
                        ${file.is_deleted ? 
                            `<div class="form-group">
                                <label>Deleted Date</label>
                                <div>${file.deleted_date}</div>
                            </div>` : ''
                        }
                        <div class="form-actions">
                            ${file.is_deleted ? 
                                `<button class="btn btn-success" id="restoreDetailBtn">Restore File</button>
                                 <button class="btn btn-danger" id="deletePermanentlyDetailBtn">Delete Permanently</button>` :
                                `<button class="btn btn-danger" id="deleteDetailBtn">Move to Trash</button>`
                            }
                            <button class="btn" id="closeDetailsBtn">Close</button>
                        </div>
                    </div>
                `;
                
                // Add event listeners to buttons in modal
                const restoreDetailBtn = document.getElementById('restoreDetailBtn');
                const deletePermanentlyDetailBtn = document.getElementById('deletePermanentlyDetailBtn');
                const deleteDetailBtn = document.getElementById('deleteDetailBtn');
                const closeDetailsBtn = document.getElementById('closeDetailsBtn');
                
                if (restoreDetailBtn) {
                    restoreDetailBtn.addEventListener('click', function() {
                        restoreFile(file.id);
                        fileDetailsModal.style.display = 'none';
                    });
                }
                
                if (deletePermanentlyDetailBtn) {
                    deletePermanentlyDetailBtn.addEventListener('click', function() {
                        deleteFilePermanently(file.id);
                        fileDetailsModal.style.display = 'none';
                    });
                }
                
                if (deleteDetailBtn) {
                    deleteDetailBtn.addEventListener('click', function() {
                        moveToTrash(file.id);
                        fileDetailsModal.style.display = 'none';
                    });
                }
                
                if (closeDetailsBtn) {
                    closeDetailsBtn.addEventListener('click', function() {
                        fileDetailsModal.style.display = 'none';
                    });
                }
                
                fileDetailsModal.style.display = 'flex';
            } catch (error) {
                console.error('Error loading file details:', error);
                alert('Error loading file details. Please try again.');
            }
        }
        
        function getFileIcon(fileName) {
            const fileExtension = fileName.split('.').pop().toLowerCase();
            
            if (fileExtension === 'pdf') return '📕';
            if (['doc', 'docx'].includes(fileExtension)) return '📘';
            if (['xls', 'xlsx'].includes(fileExtension)) return '📗';
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) return '🖼️';
            
            return '📄';
        }
        
        async function handleFileUpload() {
            const fileInput = document.getElementById('fileInput');
            const fileName = document.getElementById('fileName').value;
            const fileCategory = document.getElementById('fileCategory').value;
            const fileDescription = document.getElementById('fileDescription').value;
            
            if(!fileInput.files[0]) {
                alert('Please select a file to upload.');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('name', fileName);
            formData.append('category', fileCategory);
            formData.append('description', fileDescription);
            
            try {
                const response = await fetch('?api=true', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                alert(result.message);
                
                if(!result.message.includes('error')) {
                    // Reset form and close modal
                    uploadForm.reset();
                    uploadModal.style.display = 'none';
                    // Reload files
                    loadActiveFiles();
                }
            } catch (error) {
                console.error('Error uploading file:', error);
                alert('Error uploading file. Please try again.');
            }
        }
        
        async function moveToTrash(fileId) {
            if (confirm('Are you sure you want to move this file to trash?')) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'trash');
                    formData.append('id', fileId);
                    
                    const response = await fetch('?api=true', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    alert(result.message);
                    loadActiveFiles();
                } catch (error) {
                    console.error('Error moving file to trash:', error);
                    alert('Error moving file to trash. Please try again.');
                }
            }
        }
        
        async function restoreFile(fileId) {
            try {
                const formData = new FormData();
                formData.append('action', 'restore');
                formData.append('id', fileId);
                
                const response = await fetch('?api=true', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                alert(result.message);
                loadTrashFiles();
                
                // Switch to active files tab
                tabs[0].click();
            } catch (error) {
                console.error('Error restoring file:', error);
                alert('Error restoring file. Please try again.');
            }
        }
        
        async function deleteFilePermanently(fileId) {
            if (confirm('Are you sure you want to permanently delete this file? This action cannot be undone.')) {
                try {
                    const response = await fetch('?api=true', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${fileId}`
                    });
                    
                    const result = await response.json();
                    alert(result.message);
                    loadTrashFiles();
                } catch (error) {
                    console.error('Error deleting file:', error);
                    alert('Error deleting file. Please try again.');
                }
            }
        }
    </script>
</body>
</html>