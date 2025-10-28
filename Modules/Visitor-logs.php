
<?php
// config.php - Database configuration

class Database {
    private $host = "localhost";
    private $db_name = "visitor_management";
    private $username = "root";  // Change as per your setup
    private $password = "";      // Change as per your setup
    public $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// Helper functions
function executeQuery($sql, $params = []) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $exception) {
        return false;
    }
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return [];
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}

function getLastInsertId() {
    $database = new Database();
    $db = $database->getConnection();
    return $db->lastInsertId();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Management System</title>
    <style>
        /* CSS Styles */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #2ecc71;
            --warning: #f39c12;
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
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            transition: background 0.3s;
        }
        
        nav ul li a:hover, nav ul li a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .sidebar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 10px 15px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: var(--secondary);
            color: white;
        }
        
        .content {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page {
            display: none;
        }
        
        .page.active {
            display: block;
        }
        
        h1, h2, h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--secondary);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--secondary);
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        form {
            max-width: 600px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-warning {
            background: var(--warning);
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-danger {
            background: var(--accent);
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-checked-in {
            background: #d4edda;
            color: #155724;
        }
        
        .status-checked-out {
            background: #f8d7da;
            color: #721c24;
        }
        /* New classes using "timed" terminology */
        .status-timed-in {
            background: #d4edda;
            color: #155724;
        }

        .status-timed-out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom: 3px solid var(--secondary);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 15px;
                justify-content: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">Visitor Management System</div>
                <nav>
                    <ul>
                        <li><a href="#" class="nav-link active" data-page="dashboard">Dashboard</a></li>
                        <li><a href="#" class="nav-link" data-page="hotel">Hotel</a></li>
                        <li><a href="#" class="nav-link" data-page="restaurant">Restaurant</a></li>
                        <li><a href="#" class="nav-link" data-page="reports">Reports</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="main-content">
            <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li><a href="#" class="sidebar-link active" data-page="dashboard">Dashboard</a></li>
                    <li><a href="#" class="sidebar-link" data-page="hotel-checkin">Hotel Time-in</a></li>
                    <li><a href="#" class="sidebar-link" data-page="hotel-visitors">Hotel Visitors</a></li>
                    <li><a href="#" class="sidebar-link" data-page="restaurant-checkin">Restaurant time-in</a></li>
                    <li><a href="#" class="sidebar-link" data-page="restaurant-visitors">Restaurant Visitors</a></li>
                    <li><a href="#" class="sidebar-link" data-page="reports">Reports</a></li>
                    <li><a href="#" class="sidebar-link" data-page="settings">Settings</a></li>
                </ul>
            </aside>

            <main class="content">
                <!-- Dashboard Page -->
                <div id="dashboard" class="page active">
                    <h1>Dashboard</h1>
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-number" id="hotel-today">0</div>
                            <div class="stat-label">Hotel Visitors Today</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="restaurant-today">0</div>
                            <div class="stat-label">Restaurant Visitors Today</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="hotel-current">0</div>
                            <div class="stat-label">Currently in Hotel</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="restaurant-current">0</div>
                            <div class="stat-label">Currently in Restaurant</div>
                        </div>
                    </div>

                    <div class="card">
                        <h2>Recent Activity</h2>
                        <div id="recent-activity">
                            <!-- Activity will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Hotel Page -->
                <div id="hotel" class="page">
                    <h1>Hotel Management</h1>
                    <div class="tabs">
                        <div class="tab active" data-tab="hotel-checkin">Time-in</div>
                        <div class="tab" data-tab="hotel-visitors">Current Visitors</div>
                        <div class="tab" data-tab="hotel-history">Visitor History</div>
                    </div>

                    <div class="tab-content active" id="hotel-checkin-tab">
                        <h2>Hotel Guest Time-in</h2>
                        <form id="hotel-checkin-form">
                            <div class="form-group">
                                <label for="guest-name">Full Name</label>
                                <input type="text" id="guest-name" name="guest-name" required>
                            </div>
                            <div class="form-group">
                                <label for="guest-email">Email</label>
                                <input type="email" id="guest-email" name="guest-email" required>
                            </div>
                            <div class="form-group">
                                <label for="guest-phone">Phone</label>
                                <input type="tel" id="guest-phone" name="guest-phone" required>
                            </div>
                            <!-- ID Type & Number field removed as requested -->
                            <div class="form-group">
                                <label for="room-number">Room Number</label>
                                <input type="text" id="room-number" name="room-number" required>
                            </div>
                            <div class="form-group">
                                <label for="checkin-date">Time-in Date</label>
                                <input type="date" id="checkin-date" name="checkin-date" required>
                            </div>
                            <div class="form-group">
                                <label for="checkout-date">Time-out Date</label>
                                <input type="date" id="checkout-date" name="checkout-date" required>
                            </div>
                            <div class="form-group">
                                <label for="guest-notes">Notes</label>
                                <textarea id="guest-notes" name="guest-notes" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn-success">Time-in Guest</button>
                        </form>
                    </div>

                    <div class="tab-content" id="hotel-visitors-tab">
                        <h2>Current Hotel Guests</h2>
                        <table id="hotel-current-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-content" id="hotel-history-tab">
                        <h2>Hotel Visitor History</h2>
                        <div class="form-group">
                            <label for="hotel-history-date">Filter by Date</label>
                            <input type="date" id="hotel-history-date">
                        </div>
                        <table id="hotel-history-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Room</th>
                                    <th>Time-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Restaurant Page -->
                <div id="restaurant" class="page">
                    <h1>Restaurant Management</h1>
                    <div class="tabs">
                        <div class="tab active" data-tab="restaurant-checkin">Time-in</div>
                        <div class="tab" data-tab="restaurant-visitors">Current Visitors</div>
                        <div class="tab" data-tab="restaurant-history">Visitor History</div>
                    </div>

                    <div class="tab-content active" id="restaurant-checkin-tab">
                        <h2>Restaurant Visitor Time-in</h2>
                        <form id="restaurant-checkin-form">
                            <div class="form-group">
                                <label for="visitor-name">Full Name</label>
                                <input type="text" id="visitor-name" name="visitor-name" required>
                            </div>
                            <div class="form-group">
                                <label for="visitor-phone">Phone</label>
                                <input type="tel" id="visitor-phone" name="visitor-phone">
                            </div>
                            <div class="form-group">
                                <label for="party-size">Party Size</label>
                                <input type="number" id="party-size" name="party-size" min="1" required>
                            </div>
                            <div class="form-group">
                                <label for="table-number">Table Number</label>
                                <input type="text" id="table-number" name="table-number" required>
                            </div>
                            <div class="form-group">
                                <label for="restaurant-notes">Notes</label>
                                <textarea id="restaurant-notes" name="restaurant-notes" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn-success">Time-in Visitor</button>
                        </form>
                    </div>

                    <div class="tab-content" id="restaurant-visitors-tab">
                        <h2>Current Restaurant Visitors</h2>
                        <table id="restaurant-current-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Party Size</th>
                                    <th>Table</th>
                                    <th>Check-in Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-content" id="restaurant-history-tab">
                        <h2>Restaurant Visitor History</h2>
                        <div class="form-group">
                            <label for="restaurant-history-date">Filter by Date</label>
                            <input type="date" id="restaurant-history-date">
                        </div>
                        <table id="restaurant-history-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Party Size</th>
                                    <th>Table</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reports Page -->
                <div id="reports" class="page">
                    <h1>Reports</h1>
                    <div class="card">
                        <h2>Generate Reports</h2>
                        <form id="report-form">
                            <div class="form-group">
                                <label for="report-type">Report Type</label>
                                <select id="report-type" name="report-type">
                                    <option value="daily">Daily Report</option>
                                    <option value="weekly">Weekly Report</option>
                                    <option value="monthly">Monthly Report</option>
                                    <option value="custom">Custom Date Range</option>
                                </select>
                            </div>
                            <div class="form-group" id="custom-date-range" style="display: none;">
                                <label for="start-date">Start Date</label>
                                <input type="date" id="start-date" name="start-date">
                                <label for="end-date">End Date</label>
                                <input type="date" id="end-date" name="end-date">
                            </div>
                            <div class="form-group">
                                <label for="report-venue">Venue</label>
                                <select id="report-venue" name="report-venue">
                                    <option value="all">All Venues</option>
                                    <option value="hotel">Hotel Only</option>
                                    <option value="restaurant">Restaurant Only</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-success">Generate Report</button>
                        </form>
                    </div>

                    <div class="card" id="report-results" style="display: none;">
                        <h2>Report Results</h2>
                        <div id="report-data">
                            <!-- Report data will be displayed here -->
                        </div>
                    </div>
                </div>

                <!-- Settings Page -->
                <div id="settings" class="page">
                    <h1>System Settings</h1>
                    <div class="card">
                        <h2>General Settings</h2>
                        <form id="settings-form">
                            <div class="form-group">
                                <label for="business-name">Business Name</label>
                                <input type="text" id="business-name" name="business-name" value="Hotel & Restaurant">
                            </div>
                            <div class="form-group">
                                <label for="timezone">Timezone</label>
                                <select id="timezone" name="timezone">
                                    <option value="UTC">UTC</option>
                                    <option value="EST">Eastern Time</option>
                                    <option value="PST">Pacific Time</option>
                                    <!-- Add more timezones as needed -->
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="data-retention">Data Retention (days)</label>
                                <input type="number" id="data-retention" name="data-retention" value="365" min="30">
                            </div>
                            <button type="submit" class="btn-success">Save Settings</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // JavaScript for the Visitor Management System
        
        // Data storage (in a real application, this would be a database)
        let hotelVisitors = JSON.parse(localStorage.getItem('hotelVisitors')) || [];
        let restaurantVisitors = JSON.parse(localStorage.getItem('restaurantVisitors')) || [];
        let settings = JSON.parse(localStorage.getItem('visitorSettings')) || {
            businessName: "Hotel & Restaurant",
            timezone: "UTC",
            dataRetention: 365
        };

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Set up navigation
            setupNavigation();
            
            // Set up form submissions
            setupForms();
            
            // Initialize dashboard
            updateDashboard();
            
            // Load current visitors
            loadCurrentVisitors();
            
            // Apply settings
            applySettings();
        });

        // Navigation setup
        function setupNavigation() {
            // Main navigation
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pageId = this.getAttribute('data-page');
                    showPage(pageId);
                    
                    // Update active states
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update sidebar
                    const sidebarLinks = document.querySelectorAll('.sidebar-link');
                    sidebarLinks.forEach(l => {
                        if (l.getAttribute('data-page') === pageId) {
                            l.classList.add('active');
                        } else {
                            l.classList.remove('active');
                        }
                    });
                });
            });
             
            // Sidebar navigation
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pageId = this.getAttribute('data-page');
                    // If the sidebar link targets a sub-section like 'hotel-checkin',
                    // split by '-' and use the parent page as the main page to show.
                    let mainPage = pageId;
                    let tabToActivate = null;
                    if (pageId.includes('-')) {
                        const parts = pageId.split('-');
                        mainPage = parts[0];
                        // For cases like 'hotel-checkin' or 'restaurant-checkin'
                        // reconstruct expected data-tab value
                        tabToActivate = pageId;
                    }

                    showPage(mainPage);

                    // Update active states for sidebar
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    // Update main nav active item
                    const mainPages = ['dashboard', 'hotel', 'restaurant', 'reports'];
                    if (mainPages.includes(mainPage)) {
                        navLinks.forEach(l => {
                            if (l.getAttribute('data-page') === mainPage) {
                                l.classList.add('active');
                            } else {
                                l.classList.remove('active');
                            }
                        });
                    }

                    // If a specific inner tab is requested, activate it
                    if (tabToActivate) {
                        // Find the tab element with matching data-tab
                        const tabSelector = `.tab[data-tab="${tabToActivate}"]`;
                        const tabEl = document.querySelector(tabSelector);
                        if (tabEl) {
                            // Activate the tab within its parent page
                            const parent = tabEl.closest('.page');
                            const siblingTabs = parent.querySelectorAll('.tab');
                            siblingTabs.forEach(t => t.classList.remove('active'));
                            tabEl.classList.add('active');

                            // Show corresponding tab content
                            const tabContents = parent.querySelectorAll('.tab-content');
                            tabContents.forEach(content => {
                                if (content.id === `${tabToActivate}-tab`) {
                                    content.classList.add('active');
                                } else {
                                    content.classList.remove('active');
                                }
                            });
                        }
                    }
                });
            });
            
            // Tab navigation
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    const parent = this.closest('.page');
                    
                    // Update active tab
                    const siblingTabs = parent.querySelectorAll('.tab');
                    siblingTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabContents = parent.querySelectorAll('.tab-content');
                    tabContents.forEach(content => {
                        if (content.id === `${tabId}-tab`) {
                            content.classList.add('active');
                        } else {
                            content.classList.remove('active');
                        }
                    });
                });
            });
            
            // Show report date range based on selection
            const reportType = document.getElementById('report-type');
            if (reportType) {
                reportType.addEventListener('change', function() {
                    const customRange = document.getElementById('custom-date-range');
                    if (this.value === 'custom') {
                        customRange.style.display = 'block';
                    } else {
                        customRange.style.display = 'none';
                    }
                });
            }
        }

        // Show specific page
        function showPage(pageId) {
            // Hide all pages
            const pages = document.querySelectorAll('.page');
            pages.forEach(page => page.classList.remove('active'));
            
            // Show requested page
            const targetPage = document.getElementById(pageId);
            if (targetPage) {
                targetPage.classList.add('active');
                
                // Load data if needed
                if (pageId === 'dashboard') {
                    updateDashboard();
                } else if (pageId === 'hotel' || pageId === 'restaurant') {
                    loadCurrentVisitors();
                }
            }
        }

        // Setup form submissions
        function setupForms() {
            // Hotel check-in form
            const hotelCheckinForm = document.getElementById('hotel-checkin-form');
            if (hotelCheckinForm) {
                hotelCheckinForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    timeInHotelGuest();
                });
            }
            
            // Restaurant check-in form
            const restaurantCheckinForm = document.getElementById('restaurant-checkin-form');
            if (restaurantCheckinForm) {
                restaurantCheckinForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    timeInRestaurantVisitor();
                });
            }
            
            // Report form
            const reportForm = document.getElementById('report-form');
            if (reportForm) {
                reportForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    generateReport();
                });
            }
            
            // Settings form
            const settingsForm = document.getElementById('settings-form');
            if (settingsForm) {
                settingsForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveSettings();
                });
            }
        }

        // Time-in hotel guest
        function timeInHotelGuest() {
            const form = document.getElementById('hotel-checkin-form');
            const formData = new FormData(form);
            
            const guest = {
                id: Date.now(),
                name: formData.get('guest-name'),
                email: formData.get('guest-email'),
                phone: formData.get('guest-phone'),
                room: formData.get('room-number'),
                checkin: formData.get('checkin-date'),
                checkout: formData.get('checkout-date'),
                notes: formData.get('guest-notes'),
                status: 'timed-in',
                checkinTime: new Date().toISOString(),
                checkoutTime: null
            };
            
            hotelVisitors.push(guest);
            localStorage.setItem('hotelVisitors', JSON.stringify(hotelVisitors));
            
            // Show success message
            showAlert('Guest time-in recorded successfully!', 'success');
            
            // Reset form
            form.reset();
            
            // Update dashboard and tables
            updateDashboard();
            loadCurrentVisitors();
        }

        // Time-in restaurant visitor
        function timeInRestaurantVisitor() {
            const form = document.getElementById('restaurant-checkin-form');
            const formData = new FormData(form);
            
            const visitor = {
                id: Date.now(),
                name: formData.get('visitor-name'),
                phone: formData.get('visitor-phone'),
                partySize: parseInt(formData.get('party-size')),
                table: formData.get('table-number'),
                notes: formData.get('restaurant-notes'),
                status: 'timed-in',
                checkinTime: new Date().toISOString(),
                checkoutTime: null
            };
            
            restaurantVisitors.push(visitor);
            localStorage.setItem('restaurantVisitors', JSON.stringify(restaurantVisitors));
            
            // Show success message
            showAlert('Visitor time-in recorded successfully!', 'success');
            
            // Reset form
            form.reset();
            
            // Update dashboard and tables
            updateDashboard();
            loadCurrentVisitors();
        }

        // Time-out hotel guest
        function timeOutHotelGuest(guestId) {
            const guest = hotelVisitors.find(g => g.id === guestId);
            if (guest) {
                guest.status = 'timed-out';
                guest.checkoutTime = new Date().toISOString();
                localStorage.setItem('hotelVisitors', JSON.stringify(hotelVisitors));
                
                showAlert('Guest time-out recorded successfully!', 'success');
                updateDashboard();
                loadCurrentVisitors();
            }
        }

        // Time-out restaurant visitor
        function timeOutRestaurantVisitor(visitorId) {
            const visitor = restaurantVisitors.find(v => v.id === visitorId);
            if (visitor) {
                visitor.status = 'timed-out';
                visitor.checkoutTime = new Date().toISOString();
                localStorage.setItem('restaurantVisitors', JSON.stringify(restaurantVisitors));
                
                showAlert('Visitor time-out recorded successfully!', 'success');
                updateDashboard();
                loadCurrentVisitors();
            }
        }

        // Update dashboard statistics
        function updateDashboard() {
            const today = new Date().toDateString();
            
            // Hotel statistics
            const hotelToday = hotelVisitors.filter(guest => {
                const checkinDate = new Date(guest.checkinTime).toDateString();
                return checkinDate === today;
            }).length;
            
            const hotelCurrent = hotelVisitors.filter(guest => guest.status === 'timed-in').length;
            
            // Restaurant statistics
            const restaurantToday = restaurantVisitors.filter(visitor => {
                const checkinDate = new Date(visitor.checkinTime).toDateString();
                return checkinDate === today;
            }).length;
            
            const restaurantCurrent = restaurantVisitors.filter(visitor => visitor.status === 'timed-in').length;
            
            // Update DOM
            document.getElementById('hotel-today').textContent = hotelToday;
            document.getElementById('hotel-current').textContent = hotelCurrent;
            document.getElementById('restaurant-today').textContent = restaurantToday;
            document.getElementById('restaurant-current').textContent = restaurantCurrent;
            
            // Update recent activity
            updateRecentActivity();
        }

        // Update recent activity list
        function updateRecentActivity() {
            const activityContainer = document.getElementById('recent-activity');
            if (!activityContainer) return;
            
            // Combine recent activities from both hotel and restaurant
            const allActivities = [
                ...hotelVisitors.map(guest => ({
                    type: 'hotel',
                    name: guest.name,
                    action: guest.status === 'timed-in' ? 'time-in' : 'time-out',
                    time: guest.status === 'timed-in' ? guest.checkinTime : guest.checkoutTime,
                    details: `Room ${guest.room}`
                })),
                ...restaurantVisitors.map(visitor => ({
                    type: 'restaurant',
                    name: visitor.name,
                    action: visitor.status === 'timed-in' ? 'time-in' : 'time-out',
                    time: visitor.status === 'timed-in' ? visitor.checkinTime : visitor.checkoutTime,
                    details: `Table ${visitor.table}, Party of ${visitor.partySize}`
                }))
            ];
            
            // Sort by time (newest first)
            allActivities.sort((a, b) => new Date(b.time) - new Date(a.time));
            
            // Get top 5
            const recentActivities = allActivities.slice(0, 5);
            
            // Update DOM
            activityContainer.innerHTML = '';
            if (recentActivities.length === 0) {
                activityContainer.innerHTML = '<p>No recent activity</p>';
                return;
            }
            
            recentActivities.forEach(activity => {
                const activityEl = document.createElement('div');
                activityEl.className = 'activity-item';
                activityEl.innerHTML = `
                    <strong>${activity.name}</strong> ${activity.action} at the ${activity.type}
                    <br><small>${activity.details} • ${formatTime(activity.time)}</small>
                    <hr>
                `;
                activityContainer.appendChild(activityEl);
            });
        }

        // Load current visitors into tables
        function loadCurrentVisitors() {
            // Hotel current visitors
            const hotelCurrentTable = document.getElementById('hotel-current-table');
            if (hotelCurrentTable) {
                const tbody = hotelCurrentTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                const currentGuests = hotelVisitors.filter(guest => guest.status === 'checked-in');
                
                if (currentGuests.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No current guests</td></tr>';
                } else {
                    currentGuests.forEach(guest => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${guest.name}</td>
                            <td>${guest.room}</td>
                            <td>${formatDate(guest.checkin)}</td>
                            <td>${formatDate(guest.checkout)}</td>
                            <td>
                                <button class="btn-warning" onclick="timeOutHotelGuest(${guest.id})">Time-out</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            }
            
            // Restaurant current visitors
            const restaurantCurrentTable = document.getElementById('restaurant-current-table');
            if (restaurantCurrentTable) {
                const tbody = restaurantCurrentTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                const currentVisitors = restaurantVisitors.filter(visitor => visitor.status === 'checked-in');
                
                if (currentVisitors.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No current visitors</td></tr>';
                } else {
                    currentVisitors.forEach(visitor => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${visitor.name}</td>
                            <td>${visitor.partySize}</td>
                            <td>${visitor.table}</td>
                            <td>${formatTime(visitor.checkinTime)}</td>
                            <td>
                                <button class="btn-warning" onclick="timeOutRestaurantVisitor(${visitor.id})">Time-out</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            }
            
            // Hotel history
            const hotelHistoryTable = document.getElementById('hotel-history-table');
            if (hotelHistoryTable) {
                const tbody = hotelHistoryTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                // For demo, show all guests
                hotelVisitors.forEach(guest => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${guest.name}</td>
                        <td>${guest.room}</td>
                        <td>${formatDate(guest.checkin)}</td>
                        <td>${formatDate(guest.checkout)}</td>
                        <td><span class="status-badge status-${guest.status.replace('-', '')}">${guest.status}</span></td>
                    `;
                    tbody.appendChild(row);
                });
            }
            
            // Restaurant history
            const restaurantHistoryTable = document.getElementById('restaurant-history-table');
            if (restaurantHistoryTable) {
                const tbody = restaurantHistoryTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                // For demo, show all visitors
                restaurantVisitors.forEach(visitor => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${visitor.name}</td>
                        <td>${visitor.partySize}</td>
                        <td>${visitor.table}</td>
                        <td>${formatTime(visitor.checkinTime)}</td>
                        <td>${visitor.checkoutTime ? formatTime(visitor.checkoutTime) : 'N/A'}</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        }

        // Generate reports
        function generateReport() {
            const reportType = document.getElementById('report-type').value;
            const venue = document.getElementById('report-venue').value;
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            let reportData = '';
            
            // Calculate date range based on report type
            let dateRange = { start: new Date(), end: new Date() };
            
            switch (reportType) {
                case 'daily':
                    dateRange.start.setHours(0, 0, 0, 0);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
                case 'weekly':
                    // Start of week (Sunday)
                    dateRange.start.setDate(dateRange.start.getDate() - dateRange.start.getDay());
                    dateRange.start.setHours(0, 0, 0, 0);
                    // End of week (Saturday)
                    dateRange.end.setDate(dateRange.start.getDate() + 6);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
                case 'monthly':
                    // Start of month
                    dateRange.start.setDate(1);
                    dateRange.start.setHours(0, 0, 0, 0);
                    // End of month
                    dateRange.end.setMonth(dateRange.end.getMonth() + 1, 0);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
                case 'custom':
                    dateRange.start = new Date(startDate);
                    dateRange.end = new Date(endDate);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
            }
            
            // Filter data based on venue and date range
            let hotelData = [];
            let restaurantData = [];
            
            if (venue === 'all' || venue === 'hotel') {
                hotelData = hotelVisitors.filter(guest => {
                    const checkinDate = new Date(guest.checkinTime);
                    return checkinDate >= dateRange.start && checkinDate <= dateRange.end;
                });
            }
            
            if (venue === 'all' || venue === 'restaurant') {
                restaurantData = restaurantVisitors.filter(visitor => {
                    const checkinDate = new Date(visitor.checkinTime);
                    return checkinDate >= dateRange.start && checkinDate <= dateRange.end;
                });
            }
            
            // Generate report content
            reportData += `<h3>Report for ${formatDate(dateRange.start)} to ${formatDate(dateRange.end)}</h3>`;
            
            if (venue === 'all' || venue === 'hotel') {
                reportData += `<h4>Hotel Statistics</h4>`;
                reportData += `<p>Total Guests: ${hotelData.length}</p>`;
                reportData += `<p>Currently Time-in: ${hotelData.filter(g => g.status === 'timed-in').length}</p>`;
                
                if (hotelData.length > 0) {
                    reportData += `<table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    
                    hotelData.forEach(guest => {
                        reportData += `
                            <tr>
                                <td>${guest.name}</td>
                                <td>${guest.room}</td>
                                <td>${formatDate(guest.checkin)}</td>
                                <td>${formatDate(guest.checkout)}</td>
                                <td>${guest.status}</td>
                            </tr>`;
                    });
                    
                    reportData += `</tbody></table>`;
                }
            }
            
            if (venue === 'all' || venue === 'restaurant') {
                reportData += `<h4>Restaurant Statistics</h4>`;
                reportData += `<p>Total Visitors: ${restaurantData.length}</p>`;
                reportData += `<p>Total Covers: ${restaurantData.reduce((sum, visitor) => sum + visitor.partySize, 0)}</p>`;
                reportData += `<p>Currently Dining (Time-in): ${restaurantData.filter(v => v.status === 'timed-in').length}</p>`;
                
                if (restaurantData.length > 0) {
                    reportData += `<table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Party Size</th>
                                <th>Table</th>
                                <th>Time-in</th>
                                <th>Time-out</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    
                    restaurantData.forEach(visitor => {
                        reportData += `
                            <tr>
                                <td>${visitor.name}</td>
                                <td>${visitor.partySize}</td>
                                <td>${visitor.table}</td>
                                <td>${formatTime(visitor.checkinTime)}</td>
                                <td>${visitor.checkoutTime ? formatTime(visitor.checkoutTime) : 'N/A'}</td>
                            </tr>`;
                    });
                    
                    reportData += `</tbody></table>`;
                }
            }
            
            // Display report
            document.getElementById('report-data').innerHTML = reportData;
            document.getElementById('report-results').style.display = 'block';
        }

        // Save settings
        function saveSettings() {
            const form = document.getElementById('settings-form');
            const formData = new FormData(form);
            
            settings.businessName = formData.get('business-name');
            settings.timezone = formData.get('timezone');
            settings.dataRetention = parseInt(formData.get('data-retention'));
            
            localStorage.setItem('visitorSettings', JSON.stringify(settings));
            
            showAlert('Settings saved successfully!', 'success');
            applySettings();
        }

        // Apply settings
        function applySettings() {
            // Update business name in UI if needed
            const businessNameEl = document.querySelector('.logo');
            if (businessNameEl && settings.businessName) {
                businessNameEl.textContent = `${settings.businessName} Visitor Management`;
            }
            
            // Populate settings form
            const businessNameInput = document.getElementById('business-name');
            const timezoneSelect = document.getElementById('timezone');
            const dataRetentionInput = document.getElementById('data-retention');
            
            if (businessNameInput) businessNameInput.value = settings.businessName;
            if (timezoneSelect) timezoneSelect.value = settings.timezone;
            if (dataRetentionInput) dataRetentionInput.value = settings.dataRetention;
        }

        // Utility functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }

        function formatTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            // Insert at the top of the current page
            const currentPage = document.querySelector('.page.active');
            if (currentPage) {
                currentPage.insertBefore(alert, currentPage.firstChild);
            }
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
        }
    </script>
</body>
</html> 