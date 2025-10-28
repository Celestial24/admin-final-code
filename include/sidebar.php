
<!-- Sidebar -->
<nav class="sidebar">
    <div class="logo-area">
        <div class="logo">
            <img src="../assets/image/logo.png" alt="Atiéra" style="height:80px; width:auto; display:block; margin:0 auto;">
        </div>
    </div>
    
    <div class="nav-section">
        <div class="nav-title">Main Navigation</div>
        <ul class="nav-links">
            <li><a href="#" class="active" data-tab="dashboard" onclick="event.preventDefault(); switchTab('dashboard'); return false;"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="#" data-tab="facilities" onclick="event.preventDefault(); switchTab('facilities'); return false;"><i class="fas fa-building"></i> Facilities</a></li>
            <li><a href="#" data-tab="reservations" onclick="event.preventDefault(); switchTab('reservations'); return false;"><i class="fas fa-calendar-check"></i> Reservations</a></li>
            <li><a href="#" data-tab="calendar" onclick="event.preventDefault(); switchTab('calendar'); return false;"><i class="fas fa-calendar-alt"></i> Calendar</a></li>
            <li><a href="#" data-tab="management" onclick="event.preventDefault(); switchTab('management'); return false;"><i class="fas fa-cog"></i> Management</a></li>
            <li><a href="../Modules/legalmanagement.php"><i class="fas fa-cog"></i> legal management</a></li>
            <li><a href="document management(archiving).php"><i class="fas fa-cog"></i> Document archiving</a></li>
            <li><a href="visitorslog.php"><i class="fas fa-cog"></i> visitors Log</a></li>
        </ul>
    </div>

    <div class="nav-section">
        <div class="nav-title">External Links</div>
        <ul class="nav-links">
            <li><a href="#" data-tab="reports" onclick="event.preventDefault(); switchTab('reports'); return false;"><i class="fas fa-chart-bar"></i> Reports</a></li>
        </ul>
    </div>
</nav>
