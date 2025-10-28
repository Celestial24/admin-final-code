<?php

// <!-- Sidebar -->
?>
<nav class="sidebar">
    <a href="../Modules/facilities-reservation.php" class="logo-link" title="Go to Dashboard">
        <div class="logo-area">
            <div class="logo">
                <img src="../assets/image/logo.png" alt="Atiéra Logo" style="height:80px; width:auto; display:block; margin:0 auto;">
            </div>
        </div>
    </a>
    
    <div class="nav-section">
        <div class="nav-title">Main Navigation</div>
        <ul class="nav-links">
            <li><a href="#" class="active" data-tab="dashboard" onclick="event.preventDefault(); switchTab('dashboard'); return false;">
                <span class="icon-img-placeholder">📊</span> Dashboard
            </a></li>
            <li><a href="#" data-tab="facilities" onclick="event.preventDefault(); switchTab('facilities'); return false;">
                <span class="icon-img-placeholder">🏢</span> Facilities
            </a></li>
            <li><a href="#" data-tab="reservations" onclick="event.preventDefault(); switchTab('reservations'); return false;">
                <span class="icon-img-placeholder">📅</span> Reservations
            </a></li>
            <li><a href="#" data-tab="calendar" onclick="event.preventDefault(); switchTab('calendar'); return false;">
                <span class="icon-img-placeholder">📅</span> Calendar
            </a></li>
            <li><a href="#" data-tab="management" onclick="event.preventDefault(); switchTab('management'); return false;">
                <span class="icon-img-placeholder">⚙️</span> Management
            </a></li>
            <li><a href="../Modules/legalmanagement.php">
                <span class="icon-img-placeholder">⚖️</span> legal management
            </a></li>
            <li><a href="document management(archiving).php">
                <span class="icon-img-placeholder">🗄️</span> Document archiving
            </a></li>
            <li><a href="visitorslog.php">
                <span class="icon-img-placeholder">🚶</span> visitors Log
            </a></li>
        </ul>
    </div>

    <div class="nav-section">
        <div class="nav-title">External Links</div>
        <ul class="nav-links">
            <li><a href="#" data-tab="reports" onclick="event.preventDefault(); switchTab('reports'); return false;">
                <span class="icon-img-placeholder">📈</span> Reports
            </a></li>
        </ul>
    </div>
</nav>
