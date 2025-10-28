<?php
// Dashboard Tab
?>
<div id="dashboard" class="tab-content active">
    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Total Facilities</h3>
                    <div class="stat-number"><?= $dashboard_data['total_facilities'] ?></div>
                </div>
                <div class="stat-icon">
                    <!-- Ibinabalik sa simpleng icon/emoji, umaasa sa CSS styling ng .stat-icon -->
                    <span class="icon-img-placeholder" style="font-size: 1.5rem;">🏢</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Today's Reservations</h3>
                    <div class="stat-number"><?= $dashboard_data['today_reservations'] ?></div>
                </div>
                <div class="stat-icon">
                    <!-- Ibinabalik sa simpleng icon/emoji -->
                    <span class="icon-img-placeholder" style="font-size: 1.5rem;">📅</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Pending Approvals</h3>
                    <div class="stat-number"><?= $dashboard_data['pending_approvals'] ?></div>
                </div>
                <div class="stat-icon">
                    <!-- Ibinabalik sa simpleng icon/emoji -->
                    <span class="icon-img-placeholder" style="font-size: 1.5rem;">⏳</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-info">
                    <h3>Monthly Revenue</h3>
                    <div class="stat-number">₱<?= number_format($dashboard_data['monthly_revenue'], 2) ?></div>
                </div>
                <div class="stat-icon">
                    <!-- Ibinabalik sa simpleng icon/emoji -->
                    <span class="icon-img-placeholder" style="font-size: 1.5rem;">💰</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="card mb-2">
        <div class="card-header">
            <h3><span class="icon-img-placeholder">🗓️</span> Today's Schedule</h3>
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
                    <span class="icon-img-placeholder">🚫</span> No reservations scheduled for today.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Facilities Overview -->
    <div class="card">
        <div class="card-header">
            <h3><span class="icon-img-placeholder">🏢</span> Available Facilities</h3>
            <button class="btn btn-outline" onclick="switchTab('facilities')">
                <span class="icon-img-placeholder">👁️</span> View All
            </button>
        </div>
        <div class="card-content">
            <div class="facilities-grid">
                <?php foreach (array_slice($dashboard_data['facilities'], 0, 3) as $facility): ?>
                    <div class="facility-card">
                        <div class="facility-image">
                            <?php 
                                // Clean the facility name for file lookup
                                $facility_name_clean = strtolower(trim(htmlspecialchars($facility['name'])));
                                $base_path = '../assets/image/';
                                $image_url = null;

                                // 1. Check for specific hardcoded filenames based on user's file list
                                if ($facility_name_clean === 'executive boardroom') {
                                    $image_url = $base_path . 'executive_boardroom.jpg';
                                } elseif ($facility_name_clean === 'grand ballroom') {
                                    // Use .jpeg extension as per user's file list
                                    $image_url = $base_path . 'Grand Ballroom.jpeg';
                                } elseif ($facility_name_clean === 'harbor view dining room') {
                                    // Use .jpeg extension as per user's file list
                                    $image_url = $base_path . 'Harbor View Dining Room.jpeg';
                                }

                                // 2. Fallback to image_url from database if set (less reliable, but kept for completeness)
                                if (!$image_url && !empty($facility['image_url'])) {
                                    $image_url = $base_path . htmlspecialchars($facility['image_url']);
                                }

                                // 3. Set URL for file existence check, or placeholder URL
                                if ($image_url && file_exists($image_url)) {
                                    $final_image_url = $image_url;
                                } else {
                                    // Piliin ang tamang fallback placeholder image
                                    $fallback_text = strtoupper(htmlspecialchars($facility['name']));
                                    $color = match(strtolower($facility['type'])) {
                                        'banquet' => '764ba2', // Purple for Banquet
                                        'dining' => '207e7e', // Teal for Dining
                                        'meeting' => '4F46E5', // Indigo for Meeting
                                        default => '4F46E5' 
                                    };
                                    $final_image_url = "https://placehold.co/400x200/{$color}/FFFFFF?text=" . $fallback_text;
                                }

                                $onerror = "this.onerror=null;this.src='https://placehold.co/400x200/4F46E5/FFFFFF?text=FACILITY';";
                            ?>
                            <img src="<?= htmlspecialchars($final_image_url) ?>" alt="<?= htmlspecialchars($facility['name']) ?>" onerror="<?= htmlspecialchars($onerror) ?>" style="width: 100%; height: 100%; object-fit: cover;">
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
                            <div class="facility-price">₱<?= number_format($facility['hourly_rate'], 2) ?>/hour</div>
                            <button class="btn btn-primary btn-block" onclick="openReservationModal(<?= $facility['id'] ?>)">
                                <span class="icon-img-placeholder">➕</span> Book Now
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
