<!-- Dashboard Tab -->
<div id="dashboard" class="tab-content active">
    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Total Facilities</h3>
                    <div class="stat-number"><?= $dashboard_data['total_facilities'] ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-building"></i></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Today's Reservations</h3>
                    <div class="stat-number"><?= $dashboard_data['today_reservations'] ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Pending Approvals</h3>
                    <div class="stat-number"><?= $dashboard_data['pending_approvals'] ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Monthly Revenue</h3>
                    <div class="stat-number">₱<?= number_format($dashboard_data['monthly_revenue'], 2) ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            </div>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="card mb-2">
        <div class="card-header">
            <h3><i class="fas fa-calendar-day"></i> Today's Schedule</h3>
        </div>
        <div class="card-content">
            <?php if (!empty($dashboard_data['today_schedule'])): ?>
                <div class="calendar-grid">
                    <?php foreach ($dashboard_data['today_schedule'] as $event): ?>
                        <div class="calendar-event">
                            <div class="event-time">
                                <?= date('g:i A', strtotime($event['start_time'])) ?> - <?= date('g:i A', strtotime($event['end_time'])) ?>
                            </div>
                            <div class="event-title"><?= htmlspecialchars($event['facility_name']) ?></div>
                            <div class="event-details">
                                <?= htmlspecialchars($event['customer_name']) ?> • <?= htmlspecialchars($event['event_type']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center" style="color: #718096; padding: 2rem;">
                    <i class="fas fa-calendar-times"></i> No reservations scheduled for today.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Facilities Overview -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-building"></i> Available Facilities</h3>
            <button class="btn btn-outline" onclick="switchTab('facilities')">
                <i class="fas fa-eye"></i> View All
            </button>
        </div>
        <div class="card-content">
            <div class="facilities-grid">
                <?php foreach (array_slice($dashboard_data['facilities'], 0, 3) as $facility): ?>
                    <div class="facility-card">
                        <div class="facility-image">
                            <?php if (!empty($facility['image_url']) && file_exists('../images/' . $facility['image_url'])): ?>
                                <img src="../images/<?= htmlspecialchars($facility['image_url']) ?>" alt="<?= htmlspecialchars($facility['name']) ?>">
                            <?php else: ?>
                                <i class="<?= 
                                    match($facility['type']) {
                                        'banquet' => 'fas fa-glass-cheers',
                                        'meeting' => 'fas fa-briefcase',
                                        'outdoor' => 'fas fa-umbrella-beach',
                                        'conference' => 'fas fa-users',
                                        'dining' => 'fas fa-utensils',
                                        'lounge' => 'fas fa-cocktail',
                                        default => 'fas fa-building'
                                    }
                                ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="facility-content">
                            <div class="facility-header">
                                <div>
                                    <div class="facility-name"><?= htmlspecialchars($facility['name']) ?></div>
                                    <span class="facility-type"><?= strtoupper(htmlspecialchars($facility['type'])) ?></span>
                                </div>
                            </div>
                            <div class="facility-details">
                                <?= htmlspecialchars($facility['description']) ?>
                            </div>
                            <div class="facility-meta">
                                <div class="meta-item"><i class="fas fa-users"></i> Capacity: <?= $facility['capacity'] ?></div>
                                <div class="meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($facility['location']) ?></div>
                            </div>
                            <div class="facility-price">₱<?= number_format($facility['hourly_rate'], 2) ?>/hour</div>
                            <button class="btn btn-primary btn-block" onclick="openReservationModal(<?= $facility['id'] ?>)">
                                <i class="fas fa-calendar-plus"></i> Book Now
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

