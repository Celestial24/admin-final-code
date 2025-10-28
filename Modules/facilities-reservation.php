<?php
// facilities_reservation_system.php
session_start();

// Security check: If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Load shared DB helper (keeps filename safe and centralized)
require_once __DIR__ . '/../db/db.php';

class ReservationSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = get_pdo();
    }
    
    
    public function makeReservation($data) {
        $pdo = $this->pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Check for time conflicts
            $conflictCheck = $pdo->prepare("
                SELECT COUNT(*) FROM reservations 
                WHERE facility_id = ? AND event_date = ? AND status IN ('pending', 'confirmed')
                AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
            ");
            $conflictCheck->execute([
                $data['facility_id'],
                $data['event_date'],
                $data['start_time'],
                $data['start_time'],
                $data['end_time'],
                $data['end_time']
            ]);
            
            if ($conflictCheck->fetchColumn() > 0) {
                throw new Exception("Time conflict: The facility is already booked for the selected time slot.");
            }
            
            // Calculate total amount
            $facility = $pdo->query("SELECT hourly_rate, capacity FROM facilities WHERE id = " . intval($data['facility_id']))->fetch();
            
            if ($data['guests_count'] > $facility['capacity']) {
                throw new Exception("Number of guests exceeds facility capacity.");
            }
            
            $start = strtotime($data['start_time']);
            $end = strtotime($data['end_time']);
            $hours = max(1, ceil(($end - $start) / 3600));
            $total_amount = $hours * $facility['hourly_rate'];
            
            $stmt = $pdo->prepare("INSERT INTO reservations (facility_id, customer_name, customer_email, customer_phone, event_type, event_date, start_time, end_time, guests_count, special_requirements, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                intval($data['facility_id']),
                htmlspecialchars($data['customer_name']),
                filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL),
                htmlspecialchars($data['customer_phone'] ?? ''),
                htmlspecialchars($data['event_type']),
                $data['event_date'],
                $data['start_time'],
                $data['end_time'],
                intval($data['guests_count']),
                htmlspecialchars($data['special_requirements'] ?? ''),
                $total_amount
            ]);
            
            $pdo->commit();
            return ['success' => true, 'message' => "Reservation request submitted successfully! We will contact you shortly to confirm."];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Error making reservation: " . $e->getMessage()];
        }
    }
    
    public function updateReservationStatus($reservationId, $status) {
        $pdo = $this->pdo;
        
        try {
            $stmt = $pdo->prepare("UPDATE reservations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, intval($reservationId)]);
            
            return ['success' => true, 'message' => "Reservation status updated successfully!"];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => "Error updating reservation: " . $e->getMessage()];
        }
    }
    
    public function addFacility($data) {
        $pdo = $this->pdo;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO facilities (name, type, capacity, location, description, hourly_rate, amenities) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                htmlspecialchars($data['name']),
                htmlspecialchars($data['type']),
                intval($data['capacity']),
                htmlspecialchars($data['location']),
                htmlspecialchars($data['description']),
                floatval($data['hourly_rate']),
                htmlspecialchars($data['amenities'] ?? '')
            ]);
            
            return ['success' => true, 'message' => "Facility added successfully!"];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => "Error adding facility: " . $e->getMessage()];
        }
    }
    
    public function fetchDashboardData() {
        $pdo = $this->pdo;
        $data = [];
        
        try {
            // Dashboard metrics
            $data['total_facilities'] = $pdo->query("SELECT COUNT(*) FROM facilities WHERE status = 'active'")->fetchColumn();
            $data['today_reservations'] = $pdo->query("SELECT COUNT(*) FROM reservations WHERE event_date = CURDATE() AND status IN ('confirmed', 'pending')")->fetchColumn();
            $data['pending_approvals'] = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
            $data['monthly_revenue'] = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM reservations WHERE status = 'confirmed' AND MONTH(event_date) = MONTH(CURDATE()) AND YEAR(event_date) = YEAR(CURDATE())")->fetchColumn();
            
            // Fetch facilities
            $data['facilities'] = $pdo->query("SELECT * FROM facilities WHERE status = 'active' ORDER BY name")->fetchAll();
            
            // Fetch reservations with pagination
            $data['reservations'] = $pdo->query("
                SELECT r.*, f.name as facility_name, f.capacity as facility_capacity 
                FROM reservations r 
                JOIN facilities f ON r.facility_id = f.id 
                ORDER BY r.event_date DESC, r.start_time DESC 
                LIMIT 50
            ")->fetchAll();
            
            // Today's schedule
            $data['today_schedule'] = $pdo->query("
                SELECT r.*, f.name as facility_name 
                FROM reservations r 
                JOIN facilities f ON r.facility_id = f.id 
                WHERE r.event_date = CURDATE() AND r.status = 'confirmed' 
                ORDER BY r.start_time
            ")->fetchAll();
            
        } catch(PDOException $e) {
            $data['error'] = "Error fetching data: " . $e->getMessage();
        }
        
        return $data;
    }
    
    public function getAvailableTimeSlots($facilityId, $date) {
        $pdo = $this->pdo;
        
        $stmt = $pdo->prepare("
            SELECT start_time, end_time 
            FROM reservations 
            WHERE facility_id = ? AND event_date = ? AND status IN ('confirmed', 'pending')
            ORDER BY start_time
        ");
        $stmt->execute([$facilityId, $date]);
        
        return $stmt->fetchAll();
    }
}

// Initialize system
$reservationSystem = new ReservationSystem();
$dashboard_data = $reservationSystem->fetchDashboardData();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'make_reservation':
                $result = $reservationSystem->makeReservation($_POST);
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'update_reservation_status':
                $result = $reservationSystem->updateReservationStatus($_POST['reservation_id'], $_POST['status']);
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'add_facility':
                $result = $reservationSystem->addFacility($_POST);
                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
                break;
                
            case 'check_availability':
                if (isset($_POST['facility_id']) && isset($_POST['event_date'])) {
                    $slots = $reservationSystem->getAvailableTimeSlots($_POST['facility_id'], $_POST['event_date']);
                    header('Content-Type: application/json');
                    echo json_encode($slots);
                    exit;
                }
                break;
                
            case 'update_status':
                if (isset($_POST['reservation_id']) && isset($_POST['status'])) {
                    $stmt = get_pdo()->prepare("UPDATE reservations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$_POST['status'], intval($_POST['reservation_id'])]);
                    $_SESSION['message'] = 'Reservation status updated.';
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=reports');
                    exit;
                }
                break;

            case 'export_csv':
                // Build query with optional filters
                $where = [];
                $params = [];
                if (!empty($_POST['from_date'])) { $where[] = 'event_date >= ?'; $params[] = $_POST['from_date']; }
                if (!empty($_POST['to_date'])) { $where[] = 'event_date <= ?'; $params[] = $_POST['to_date']; }
                if (!empty($_POST['status']) && $_POST['status'] !== 'all') { $where[] = 'status = ?'; $params[] = $_POST['status']; }

                $sql = "SELECT r.*, f.name as facility_name FROM reservations r LEFT JOIN facilities f ON r.facility_id = f.id";
                if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
                $sql .= ' ORDER BY r.event_date, r.start_time';

                $stmt = get_pdo()->prepare($sql);
                $stmt->execute($params);

                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="reservations_report.csv"');

                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID','Facility','Customer','Email','Phone','Event Type','Date','Start Time','End Time','Guests','Amount','Status','Created At','Updated At']);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($out, [
                        $row['id'],
                        $row['facility_name'],
                        $row['customer_name'],
                        $row['customer_email'],
                        $row['customer_phone'],
                        $row['event_type'],
                        $row['event_date'],
                        $row['start_time'],
                        $row['end_time'],
                        $row['guests_count'],
                        $row['total_amount'],
                        $row['status'],
                        $row['created_at'],
                        $row['updated_at']
                    ]);
                }
                fclose($out);
                exit;
        }
    }
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Facilities Reservation System - Hotel Management</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/facilities-reservation.css">
    </head>
    <body>
        <div class="container">
            <!-- Mobile Menu Overlay -->
            <div class="mobile-menu-overlay" onclick="closeSidebar()"></div>

            <!-- Sidebar -->
            <?php require_once __DIR__ . '/../include/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Top Header -->
                <header class="top-header">
                    <div class="header-title">
                        <button class="mobile-menu-btn" onclick="toggleSidebar()">
                            <span class="icon-img-placeholder">☰</span>
                        </button>
                        <h1 id="page-title">Facilities Reservation System</h1>
                        <p id="page-subtitle">Manage hotel facilities and reservations efficiently</p>
                    </div>

                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="openModal('reservation-modal')">
                            <span class="icon-img-placeholder">➕</span> New Reservation
                        </button>
                        <!-- Pinalitan ng button at inilagay ang logic sa JS -->
                        <button class="btn btn-outline" onclick="openLogoutModal()" style="margin-left: 10px;">
                            <span class="icon-img-placeholder">🚪</span> Logout
                        </button>
                    </div>
                </header>

                <!-- Dashboard Content -->
                <div class="dashboard-content">
                    <!-- Alert Messages -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <span class="icon-img-placeholder">✔️</span> <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-error">
                            <span class="icon-img-placeholder">⚠️</span> <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Dashboard Tab -->
                    <?php require_once __DIR__ . '/../include/dashboard.php'; ?>

                    <!-- Facilities Tab -->
                    <div id="facilities" class="tab-content">
                        <div class="d-flex justify-between align-center mb-2">
                            <h2><span class="icon-img-placeholder">🏢</span> Hotel Facilities</h2>
                            <button class="btn btn-primary" onclick="openModal('facility-modal')">
                                <span class="icon-img-placeholder">➕</span> Add Facility
                            </button>
                        </div>
                        
                        <div class="facilities-grid">
                            <?php foreach ($dashboard_data['facilities'] as $facility): ?>
                                <div class="facility-card">
                                    <div class="facility-image">
                                        <?php 
                                            // 1. I-normalize ang facility name para magamit sa filename
                                            $base_name = str_replace(' ', '', ucwords(strtolower($facility['name']))); // ExecutiveBoardroom
                                            $base_name_underscored = str_replace(' ', '_', strtolower($facility['name'])); // executive_boardroom
                                            $name_with_spaces_title_case = ucwords(strtolower($facility['name'])); // Executive Boardroom
                                            
                                            $possible_files = [
                                                // New files check (with spaces and Title Case)
                                                $name_with_spaces_title_case . '.jpeg', 
                                                $name_with_spaces_title_case . '.jpg', 
                                                // Existing checks (underscores and no spaces)
                                                $base_name_underscored . '.jpg', 
                                                $base_name_underscored . '.jpeg', 
                                                $base_name . '.jpg', 
                                                $base_name . '.jpeg',
                                                // Specific file names observed in screenshot (e.g., Grand Ballroom.jpeg)
                                                $facility['name'] . '.jpeg', 
                                                $facility['name'] . '.jpg',
                                            ];
                                            
                                            $image_url = '';
                                            $is_placeholder = true;
                                            
                                            foreach($possible_files as $file) {
                                                // FIX: Ensure file_exists uses the correct relative path from the server's perspective
                                                $full_path = __DIR__ . '/../assets/image/' . $file;
                                                if (file_exists($full_path)) {
                                                    $image_url = '../assets/image/' . $file;
                                                    $is_placeholder = false;
                                                    break;
                                                }
                                            }

                                            // FIX: Pinalitan ang match() ng switch statement para sa compatibility
                                            $placeholder_color = '1a365d'; // Default
                                            switch($facility['type']) {
                                                case 'banquet': $placeholder_color = '764ba2'; break; // Purple
                                                case 'meeting':
                                                case 'conference': $placeholder_color = '3182ce'; break; // Blue
                                                case 'outdoor': $placeholder_color = '38a169'; break; // Green
                                                case 'dining': $placeholder_color = '00b5d8'; break; // Teal
                                                case 'lounge': $placeholder_color = 'd69e2e'; break; // Yellow
                                                default: $placeholder_color = '1a365d'; break; // Primary Dark Blue
                                            }

                                            $placeholder_text = strtoupper(htmlspecialchars($facility['name'] ?: $facility['type']));


                                            if ($is_placeholder) {
                                                // Gumamit ng placeholder na may kulay at text
                                                $image_url = "https://placehold.co/400x200/{$placeholder_color}/FFFFFF?text=" . $placeholder_text;
                                                $onerror = "this.onerror=null;this.src='{$image_url}';";
                                            } else {
                                                // Gumamit ng placeholder bilang fallback kung sakaling may error sa image path sa browser
                                                $onerror = "this.onerror=null;this.src='https://placehold.co/400x200/{$placeholder_color}/FFFFFF?text=IMAGE+FAIL';";
                                            }
                                        ?>
                                        <img src="<?= htmlspecialchars($image_url) ?>" alt="<?= htmlspecialchars($facility['name']) ?>" onerror="<?= htmlspecialchars($onerror) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="facility-content">
                                        <div class="facility-header">
                                            <div>
                                                <div class="facility-name"><?= htmlspecialchars($facility['name']) ?></div>
                                                <button class="facility-type" onclick="filterByType('<?= htmlspecialchars($facility['type']) ?>')">
                                                    <?= strtoupper(htmlspecialchars($facility['type'])) ?>
                                                </button>
                                            </div>
                                        </div>
                                        <!-- BAGONG BUTTON: View Details -->
                                        <button class="btn btn-outline btn-sm mb-1" onclick="viewFacilityDetails(<?= $facility['id'] ?>)" style="padding: 0.4rem 0.8rem;">
                                            <span class="icon-img-placeholder" style="font-size: 0.9rem;">🔎</span> View Details
                                        </button>
                                        <div class="facility-details">
                                            <?= htmlspecialchars($facility['description']) ?>
                                        </div>
                                        <div class="facility-meta">
                                            <div class="meta-item"><span class="icon-img-placeholder">👤</span> Capacity: <?= $facility['capacity'] ?></div>
                                            <div class="meta-item"><span class="icon-img-placeholder">📍</span> <?= htmlspecialchars($facility['location']) ?></div>
                                        </div>
                                        <?php if (!empty($facility['amenities'])): ?>
                                            <div class="mb-1">
                                                <strong><span class="icon-img-placeholder">🌟</span> Amenities:</strong>
                                                <div style="font-size: 0.875rem; color: #718096; margin-top: 0.25rem;">
                                                    <?= htmlspecialchars($facility['amenities']) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="facility-price">₱<?= number_format($facility['hourly_rate'], 2) ?>/hour</div>
                                        <button class="btn btn-primary btn-block" onclick="openReservationModal(<?= $facility['id'] ?>)">
                                            <span class="icon-img-placeholder">➕</span> Book This Facility
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Reservations Tab -->
                    <div id="reservations" class="tab-content">
                        <div class="d-flex justify-between align-center mb-2">
                            <h2><span class="icon-img-placeholder">📅</span> Reservation Management</h2>
                        
                        </div>
                        
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <!-- Nag-set ng center alignment para sa karamihan ng headers -->
                                        <th style="width: 5%; text-align: center;">ID</th>
                                        <th style="width: 15%; text-align: left;">Facility</th>
                                        <th style="width: 15%; text-align: left;">Customer</th>
                                        <th style="width: 10%; text-align: center;">Event Type</th>
                                        <th style="width: 15%; text-align: center;">Date & Time</th>
                                        <th style="width: 5%; text-align: center;">Guests</th>
                                        <th style="width: 10%; text-align: center;">Amount</th> 
                                        <th style="width: 10%; text-align: center;">Status</th>
                                        <th style="width: 15%; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dashboard_data['reservations'] as $reservation): ?>
                                        <tr>
                                            <td style="text-align: center;">#<?= $reservation['id'] ?></td>
                                            <td style="text-align: left;"><?= htmlspecialchars($reservation['facility_name']) ?></td>
                                            <td style="text-align: left;">
                                                <div style="font-size: 0.9rem; font-weight: 600;"><?= htmlspecialchars($reservation['customer_name']) ?></div>
                                                <small style="color: #718096; font-size: 0.75rem;"><?= htmlspecialchars($reservation['customer_email'] ?? '') ?></small>
                                            </td>
                                            <td style="text-align: center;"><?= htmlspecialchars($reservation['event_type']) ?></td>
                                            <!-- INAYOS NA DATE & TIME STRUCTURE -->
                                            <td style="text-align: center;">
                                                <div style="font-size: 0.85rem; font-weight: 500; line-height: 1.2;">
                                                    <?= date('M d, Y', strtotime($reservation['event_date'])) ?>
                                                </div>
                                                <small style="color: #718096; font-size: 0.7rem; display: block;">
                                                    <?= date('g:i A', strtotime($reservation['start_time'])) ?> - <?= date('g:i A', strtotime($reservation['end_time'])) ?>
                                                </small>
                                            </td>
                                            <td style="text-align: center;"><?= $reservation['guests_count'] ?></td>
                                            <td style="font-weight: 600; text-align: center;">₱<?= number_format($reservation['total_amount'] ?? 0, 2) ?></td> 
                                            <td style="text-align: center;">
                                                <span class="status-badge status-<?= $reservation['status'] ?>">
                                                    <?= ucfirst($reservation['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1" style="flex-wrap: nowrap; justify-content: center;">
                                                    <?php if ($reservation['status'] == 'pending'): ?>
                                                        <button class="btn btn-success btn-sm" onclick="updateReservationStatus(<?= $reservation['id'] ?>, 'confirmed')" title="Confirm Reservation">
                                                            <span class="icon-img-placeholder">✔️</span>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm" onclick="updateReservationStatus(<?= $reservation['id'] ?>, 'cancelled')" title="Cancel Reservation">
                                                            <span class="icon-img-placeholder">✖️</span>
                                                        </button>
                                                    <?php elseif ($reservation['status'] == 'confirmed'): ?>
                                                        <button class="btn btn-warning btn-sm" onclick="updateReservationStatus(<?= $reservation['id'] ?>, 'completed')" title="Mark as Completed">
                                                            <span class="icon-img-placeholder">🏁</span>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline btn-sm" onclick="viewReservationDetails(<?= $reservation['id'] ?>)" title="View Details">
                                                        <span class="icon-img-placeholder">👁️</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Calendar Tab -->
                    <!-- Reports Tab -->
                    <div id="reports" class="tab-content">
                        <h2 class="mb-2"><span class="icon-img-placeholder">📈</span> Reservations Reports</h2>

                        <?php
                        // Server-side: handle GET filters for reports view
                        $r_from = $_GET['from_date'] ?? '';
                        $r_to = $_GET['to_date'] ?? '';
                        $r_status = $_GET['status'] ?? 'all';

                        $r_where = [];
                        $r_params = [];
                        if ($r_from) { $r_where[] = 'r.event_date >= ?'; $r_params[] = $r_from; }
                        if ($r_to) { $r_where[] = 'r.event_date <= ?'; $r_params[] = $r_to; }
                        if ($r_status !== 'all') { $r_where[] = 'r.status = ?'; $r_params[] = $r_status; }

                        $r_sql = "SELECT r.*, f.name as facility_name FROM reservations r LEFT JOIN facilities f ON r.facility_id = f.id";
                        if ($r_where) $r_sql .= ' WHERE ' . implode(' AND ', $r_where);
                        $r_sql .= ' ORDER BY r.event_date DESC, r.start_time DESC';

                        $r_stmt = get_pdo()->prepare($r_sql);
                        $r_stmt->execute($r_params);
                        $r_reservations = $r_stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <form method="get" class="filters">
                            From: <input type="date" name="from_date" value="<?= htmlspecialchars($r_from) ?>"> 
                            To: <input type="date" name="to_date" value="<?= htmlspecialchars($r_to) ?>"> 
                            Status: <select name="status">
                                <option value="all" <?= $r_status==='all'?'selected':'' ?>>All</option>
                                <option value="pending" <?= $r_status==='pending'?'selected':'' ?>>Pending</option>
                                <option value="confirmed" <?= $r_status==='confirmed'?'selected':'' ?>>Confirmed</option>
                                <option value="cancelled" <?= $r_status==='cancelled'?'selected':'' ?>>Cancelled</option>
                                <option value="completed" <?= $r_status==='completed'?'selected':'' ?>>Completed</option>
                            </select>
                            <button class="btn">Filter</button>
                        </form>

                        <form method="post" style="margin-bottom:12px">
                            <input type="hidden" name="action" value="export_csv">
                            <input type="hidden" name="from_date" value="<?= htmlspecialchars($r_from) ?>">
                            <input type="hidden" name="to_date" value="<?= htmlspecialchars($r_to) ?>">
                            <input type="hidden" name="status" value="<?= htmlspecialchars($r_status) ?>">
                            <button class="btn">Export CSV</button>
                        </form>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Facility</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Guests</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($r_reservations as $rr): ?>
                                        <tr>
                                            <td><?= $rr['id'] ?></td>
                                            <td><?= htmlspecialchars($rr['facility_name']) ?></td>
                                            <td><?= htmlspecialchars($rr['customer_name']) ?><br><small><?= htmlspecialchars($rr['customer_email']) ?></small></td>
                                            <td><?= htmlspecialchars($rr['event_date']) ?></td>
                                            <td><?= htmlspecialchars($rr['start_time']) ?> - <?= htmlspecialchars($rr['end_time']) ?></td>
                                            <td><?= $rr['guests_count'] ?></td>
                                            <td>₱<?= number_format($rr['total_amount'] ?? 0, 2) ?></td>
                                            <td><?= htmlspecialchars($rr['status']) ?></td>
                                            <td>
                                                <form method="post" style="display:inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="reservation_id" value="<?= $rr['id'] ?>">
                                                    <?php if ($rr['status'] !== 'confirmed'): ?>
                                                        <button class="btn" name="status" value="confirmed">Confirm</button>
                                                    <?php endif; ?>
                                                    <?php if ($rr['status'] !== 'cancelled'): ?>
                                                        <button class="btn btn-danger" name="status" value="cancelled">Cancel</button>
                                                    <?php endif; ?>
                                                    <?php if ($rr['status'] !== 'completed'): ?>
                                                        <button class="btn" name="status" value="completed">Complete</button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="calendar" class="tab-content">
                        <h2 class="mb-2"><span class="icon-img-placeholder">📅</span> Reservation Calendar</h2>
                        
                        <div class="calendar-grid">
                            <?php
                            // Display next 7 days
                            for ($i = 0; $i < 7; $i++):
                                $date = date('Y-m-d', strtotime("+$i days"));
                                $display_date = date('D, M d, Y', strtotime($date));
                                $day_events = array_filter($dashboard_data['reservations'], function($event) use ($date) {
                                    return $event['event_date'] == $date && $event['status'] == 'confirmed';
                                });
                            ?>
                            <div class="calendar-day">
                                <div class="calendar-date"><?= $display_date ?></div>
                                <div class="calendar-events">
                                    <?php foreach ($day_events as $event): ?>
                                    <div class="calendar-event">
                                        <div class="event-time">
                                            <?= date('g:i A', strtotime($event['start_time'])) ?> - <?= date('g:i A', strtotime($event['end_time'])) ?>
                                        </div>
                                        <div class="event-title"><?= htmlspecialchars($event['facility_name']) ?></div>
                                        <div class="event-details">
                                            <?= htmlspecialchars($event['customer_name']) ?> • <?= htmlspecialchars($event['event_type']) ?> • <?= $event['guests_count'] ?> guests
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($day_events)): ?>
                                    <div style="color: #718096; font-style: italic; text-align: center; padding: 1rem;">
                                        <span class="icon-img-placeholder">🚫</span> No reservations
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Management Tab -->
                    <div id="management" class="tab-content">
                        <h2 class="mb-2"><span class="icon-img-placeholder">⚙️</span> System Management</h2>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                            <!-- Facility Management -->
                            <div class="card">
                                <div class="card-header">
                                    <h3><span class="icon-img-placeholder">🏢</span> Facility Management</h3>
                                </div>
                                <div class="card-content">
                                    <button class="btn btn-primary mb-1" onclick="openModal('facility-modal')">
                                        <span class="icon-img-placeholder">➕</span> Add New Facility
                                    </button>
                                    <div style="max-height: 400px; overflow-y: auto;">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th><span class="icon-img-placeholder">🏷️</span> Name</th>
                                                    <th><span class="icon-img-placeholder">🗂️</span> Type</th>
                                                    <th><span class="icon-img-placeholder">₱</span> Rate</th>
                                                    <th><span class="icon-img-placeholder">🟢</span> Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($dashboard_data['facilities'] as $facility): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($facility['name']) ?></td>
                                                        <td><?= htmlspecialchars($facility['type']) ?></td>
                                                        <td>₱<?= number_format($facility['hourly_rate'], 2) ?></td>
                                                        <td>
                                                            <span class="status-badge status-<?= $facility['status'] ?? 'active' ?>">
                                                                <?= ucfirst($facility['status'] ?? 'active') ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- System Reports -->
                            <div class="card">
                                <div class="card-header">
                                    <h3><span class="icon-img-placeholder">📈</span> Quick Reports</h3>
                                </div>
                                <div class="card-content">
                                    <div class="d-flex flex-column gap-1">
                                        <div style="padding: 1rem; background: var(--light); border-radius: 8px;">
                                            <strong>Revenue This Month</strong>
                                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--success);">
                                                ₱<?= number_format($dashboard_data['monthly_revenue'], 2) ?>
                                            </div>
                                        </div>
                                        <div style="padding: 1rem; background: var(--light); border-radius: 8px;">
                                            <strong>Pending Approvals</strong>
                                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--warning);">
                                                <?= $dashboard_data['pending_approvals'] ?>
                                            </div>
                                        </div>
                                        <button class="btn btn-outline mt-1" onclick="generateReport()">
                                            <span class="icon-img-placeholder">📄</span> Generate Full Report
                                        </button>
                                        <button class="btn btn-outline" onclick="exportData()">
                                            <span class="icon-img-placeholder">📥</span> Export Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Modals -->
        <!-- Reservation Modal -->
        <div id="reservation-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Make New Reservation</h3>
                    <span class="close" onclick="closeModal('reservation-modal')">&times;</span>
                </div>
                <form id="reservation-form" method="POST">
                    <input type="hidden" name="action" value="make_reservation">
                    <div class="form-group">
                        <label for="facility_id">Select Facility</label>
                        <select id="facility_id" name="facility_id" class="form-control" required onchange="updateFacilityDetails()">
                            <option value="">Choose a facility...</option>
                            <?php foreach ($dashboard_data['facilities'] as $facility): ?>
                                <option value="<?= $facility['id'] ?>" data-rate="<?= $facility['hourly_rate'] ?>" data-capacity="<?= $facility['capacity'] ?>">
                                    <?= htmlspecialchars($facility['name']) ?> - ₱<?= number_format($facility['hourly_rate'], 2) ?>/hour
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="facility-details" style="display: none; background: var(--light); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <div><strong><span class="icon-img-placeholder">👤</span> Capacity:</strong> <span id="capacity-display"></span> people</div>
                        <div><strong><span class="icon-img-placeholder">₱</span> Hourly Rate:</strong> ₱<span id="rate-display"></span></div>
                        <div id="total-cost" style="font-weight: bold; color: var(--success); margin-top: 0.5rem;"></div>
                    </div>

                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_email">Email Address</label>
                        <input type="email" id="customer_email" name="customer_email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Phone Number</label>
                        <input type="tel" id="customer_phone" name="customer_phone" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="event_type">Event Type</label>
                        <select id="event_type" name="event_type" class="form-control" required>
                            <option value="">Select event type...</option>
                            <option value="Wedding">Wedding</option>
                            <option value="Business Meeting">Business Meeting</option>
                            <option value="Conference">Conference</option>
                            <option value="Birthday Party">Birthday Party</option>
                            <option value="Anniversary">Anniversary</option>
                            <option value="Corporate Event">Corporate Event</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="event_date">Event Date</label>
                        <input type="date" id="event_date" name="event_date" class="form-control" required min="<?= date('Y-m-d') ?>" onchange="checkAvailability()">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" class="form-control" required onchange="calculateTotal()">
                        </div>
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" class="form-control" required onchange="calculateTotal()">
                        </div>
                    </div>

                    <div id="availability-warning" class="alert alert-warning" style="display: none;">
                        <span class="icon-img-placeholder">⚠️</span> <span id="availability-message"></span>
                    </div>

                    <div class="form-group">
                        <label for="guests_count">Number of Guests</label>
                        <input type="number" id="guests_count" name="guests_count" class="form-control" required min="1" onchange="checkCapacity()">
                        <small id="capacity-warning" style="color: var(--danger); display: none;">
                            <span class="icon-img-placeholder">🚨</span> Exceeds facility capacity!
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="special_requirements">Special Requirements</label>
                        <textarea id="special_requirements" name="special_requirements" class="form-control" rows="3" placeholder="Any special arrangements or requirements..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <span class="icon-img-placeholder">📩</span> Submit Reservation Request
                    </button>
                </form>
            </div>
        </div>

        <!-- Facility Management Modal -->
        <div id="facility-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add New Facility</h3>
                    <span class="close" onclick="closeModal('facility-modal')">&times;</span>
                </div>
                <form id="facility-form" method="POST">
                    <input type="hidden" name="action" value="add_facility">
                    <div class="form-group">
                        <label for="facility_name">Facility Name</label>
                        <input type="text" id="facility_name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="facility_type">Facility Type</label>
                        <select id="facility_type" name="type" class="form-control" required>
                            <option value="">Select type...</option>
                            <option value="banquet">Banquet Hall</option>
                            <option value="meeting">Meeting Room</option>
                            <option value="conference">Conference Room</option>
                            <option value="outdoor">Outdoor Space</option>
                            <option value="dining">Private Dining</option>
                            <option value="lounge">Lounge</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="facility_capacity">Capacity</label>
                        <input type="number" id="facility_capacity" name="capacity" class="form-control" required min="1">
                    </div>

                    <div class="form-group">
                        <label for="facility_location">Location</label>
                        <input type="text" id="facility_location" name="location" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="facility_description">Description</label>
                        <textarea id="facility_description" name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="facility_rate">Hourly Rate ($)</label>
                        <input type="number" id="facility_rate" name="hourly_rate" class="form-control" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="facility_amenities">Amenities</label>
                        <textarea id="facility_amenities" name="amenities" class="form-control" rows="3" placeholder="List amenities separated by commas..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <span class="icon-img-placeholder">➕</span> Add Facility
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Logout Confirmation Modal (Galing sa JS file) -->
        <div id="logout-modal" class="modal">
            <div class="modal-content" style="max-width: 400px; text-align: center;">
                <div class="modal-header" style="justify-content: center; padding-bottom: 0.5rem; border-bottom: none;">
                    <h3 style="margin: 0; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); width: 100%; text-align: center;">Logout Confirmation</h3>
                </div>
                <div style="padding: 1rem 0 1.5rem;">
                    <p style="margin: 0;">Are you sure you want to exit this part of the system?</p>
                </div>
                <div class="d-flex justify-between" style="gap: 1rem;">
                    <button class="btn btn-outline" onclick="closeModal('logout-modal')" style="flex: 1; justify-content: center;">Cancel</button>
                    <button class="btn btn-danger" onclick="window.location.href='../auth/login.php?logout=1'" style="flex: 1; justify-content: center; white-space: nowrap;">
                        <span class="icon-img-placeholder">🚪</span> Confirm Logout
                    </button>
                </div>
            </div>
        </div>


        <script src="../assets/Javascript/facilities-reservation.js"></script>
    </body>
    </html>
