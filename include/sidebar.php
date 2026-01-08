<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_dashboard = ($current_page == 'facilities-reservation.php');

function get_nav_link($tab, $is_dashboard)
{
    if ($is_dashboard) {
        return "#\" onclick=\"event.preventDefault(); if(typeof switchTab === 'function') switchTab('$tab'); return false;\"";
    } else {
        return "../Modules/facilities-reservation.php?tab=$tab\"";
    }
}
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <a href="../Modules/facilities-reservation.php" class="logo-link" title="Go to Dashboard">
            <div class="logo-area">
                <div class="logo">
                    <img src="../assets/image/logo.png" alt="Atiéra Logo"
                        style="height:80px; width:auto; display:block; margin:0 auto;">
                </div>
            </div>
        </a>
    </div>

    <div class="nav-section">
        <div class="nav-title">Main Navigation</div>
        <ul class="nav-links">
            <li><a href="<?= get_nav_link('dashboard', $is_dashboard) ?> class=" <?= ($is_dashboard && (!isset($_GET['tab']) || $_GET['tab'] == 'dashboard')) ? 'active' : '' ?>" data-tab="dashboard">
                    <span class="icon-img-placeholder">📊</span> Dashboard
                </a></li>
            <li><a href="<?= get_nav_link('facilities', $is_dashboard) ?> class=" <?= (isset($_GET['tab']) && $_GET['tab'] == 'facilities') ? 'active' : '' ?>" data-tab="facilities">
                    <span class="icon-img-placeholder">🏢</span> Facilities
                </a></li>
            <li><a href="<?= get_nav_link('reservations', $is_dashboard) ?> class=" <?= (isset($_GET['tab']) && $_GET['tab'] == 'reservations') ? 'active' : '' ?>" data-tab="reservations">
                    <span class="icon-img-placeholder">📅</span> Reservations
                </a></li>
            <li><a href="<?= get_nav_link('calendar', $is_dashboard) ?> class=" <?= (isset($_GET['tab']) && $_GET['tab'] == 'calendar') ? 'active' : '' ?>" data-tab="calendar">
                    <span class="icon-img-placeholder">📅</span> Calendar
                </a></li>
            <li><a href="<?= get_nav_link('management', $is_dashboard) ?> class=" <?= (isset($_GET['tab']) && $_GET['tab'] == 'management') ? 'active' : '' ?>" data-tab="management">
                    <span class="icon-img-placeholder">⚙️</span> Management
                </a></li>
            <li><a href="../Modules/legalmanagement.php"
                    class="<?= ($current_page == 'legalmanagement.php') ? 'active' : '' ?>">
                    <span class="icon-img-placeholder">⚖️</span> legal management
                </a></li>
            <li><a href="document management(archiving).php"
                    class="<?= ($current_page == 'document management(archiving).php') ? 'active' : '' ?>"
                    style="white-space: nowrap;">
                    <span class="icon-img-placeholder">🗄️</span> Document archiving
                </a></li>
            <li><a href="../Modules/Visitor-logs.php"
                    class="<?= ($current_page == 'Visitor-logs.php') ? 'active' : '' ?>">
                    <span class="icon-img-placeholder">🚶</span> visitors Log
                </a></li>
        </ul>
    </div>

    <div class="nav-section">
        <div class="nav-title">External Links</div>
        <ul class="nav-links">
            <li><a href="<?= get_nav_link('reports', $is_dashboard) ?> class=" <?= (isset($_GET['tab']) && $_GET['tab'] == 'reports') ? 'active' : '' ?>" data-tab="reports">
                    <span class="icon-img-placeholder">📈</span> Reports
                </a></li>
            <li><a href="../include/Settings.php" class="<?= ($current_page == 'Settings.php') ? 'active' : '' ?>">
                    <span class="icon-img-placeholder">⚙️</span> Settings
                </a></li>
        </ul>
    </div>
</nav>