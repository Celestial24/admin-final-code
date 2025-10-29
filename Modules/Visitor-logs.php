
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
     <link rel="stylesheet" href="../assets/css/Visitors.css">
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

   <script src="../assets/Javascript/Vistors.js"></script>
</body>
</html> 