   // Tab navigation (make it globally accessible)
   window.switchTab = function(tabName) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all nav links
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.classList.remove('active');
    });
    
    // Activate the selected tab
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.classList.add('active');
    } else {
        console.warn(`Tab with id "${tabName}" not found`);
    }
    
    // Activate the corresponding nav link
    const navLink = document.querySelector(`[data-tab="${tabName}"]`);
    if (navLink) {
        navLink.classList.add('active');
    }
    
    // Update page title
    const titles = {
        'dashboard': 'Facilities Reservation Dashboard',
        'facilities': 'Hotel Facilities',
        'reservations': 'Reservation Management',
        'calendar': 'Reservation Calendar',
        'management': 'System Management',
        'reports': 'Reports & Analytics'
    };
    
    const subtitles = {
        'dashboard': 'Manage hotel facilities and reservations efficiently',
        'facilities': 'Browse and manage all hotel facilities',
        'reservations': 'View and manage all reservations',
        'calendar': 'View upcoming reservations schedule',
        'management': 'System configuration and reports',
        'reports': 'Generate reports and export data'
    };
    
    const pageTitleEl = document.getElementById('page-title');
    const pageSubtitleEl = document.getElementById('page-subtitle');
    
    if (pageTitleEl) pageTitleEl.textContent = titles[tabName] || 'Facilities Reservation System';
    if (pageSubtitleEl) pageSubtitleEl.textContent = subtitles[tabName] || 'Manage hotel facilities and reservations';
    
    // Save active tab to sessionStorage
    sessionStorage.setItem('activeTab', tabName);
}

// Restore active tab on page load
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = sessionStorage.getItem('activeTab') || 'dashboard';
    switchTab(activeTab);
    
    // Initialize date fields (if they exist)
    const eventDateField = document.getElementById('event_date');
    const startTimeField = document.getElementById('start_time');
    const endTimeField = document.getElementById('end_time');
    
    if (eventDateField) {
        const today = new Date().toISOString().split('T')[0];
        eventDateField.value = today;
    }
    
    if (startTimeField) startTimeField.value = '09:00';
    if (endTimeField) endTimeField.value = '12:00';
    
    // Add event listeners to nav links (for redundancy with onclick handlers)
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            const tab = this.getAttribute('data-tab');
            const onclick = this.getAttribute('onclick');

            // If link has a real href (points to a PHP/page), allow normal navigation
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                // let browser follow the href (e.g., legalamanagement.php)
                return;
            }

            // If there's already an onclick handler, let it handle it
            if (onclick) {
                // The onclick handler will call switchTab, but we still prevent default
                e.preventDefault();
                return;
            }

            // Otherwise, handle it here
            if (tab) {
                e.preventDefault();
                switchTab(tab);
            }
        });
    });
});

// Modal functionality
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function openReservationModal(facilityId) {
    document.getElementById('facility_id').value = facilityId;
    updateFacilityDetails();
    openModal('reservation-modal');
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// Facility details and cost calculation
function updateFacilityDetails() {
    const facilitySelect = document.getElementById('facility_id');
    const selectedOption = facilitySelect.options[facilitySelect.selectedIndex];
    const detailsDiv = document.getElementById('facility-details');
    
    if (selectedOption.value) {
        const rate = selectedOption.getAttribute('data-rate');
        const capacity = selectedOption.getAttribute('data-capacity');
        
        document.getElementById('capacity-display').textContent = capacity;
        document.getElementById('rate-display').textContent = rate;
        detailsDiv.style.display = 'block';
        
        calculateTotal();
        checkAvailability();
    } else {
        detailsDiv.style.display = 'none';
    }
}

function calculateTotal() {
    const facilitySelect = document.getElementById('facility_id');
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const totalCost = document.getElementById('total-cost');
    
    if (facilitySelect.value && startTime && endTime) {
        const rate = parseFloat(facilitySelect.options[facilitySelect.selectedIndex].getAttribute('data-rate'));
        const start = new Date('2000-01-01 ' + startTime);
        const end = new Date('2000-01-01 ' + endTime);
        
        let hours = (end - start) / (1000 * 60 * 60);
        if (hours < 0) hours += 24; // Handle overnight
        
        const total = hours * rate;
        totalCost.innerHTML = `<i class="fas fa-calculator"></i> Estimated Total: ₱${total.toFixed(2)} (${Math.ceil(hours)} hours)`;
    }
}

function checkCapacity() {
    const facilitySelect = document.getElementById('facility_id');
    const guests = document.getElementById('guests_count').value;
    const warning = document.getElementById('capacity-warning');
    
    if (facilitySelect.value && guests) {
        const capacity = parseInt(facilitySelect.options[facilitySelect.selectedIndex].getAttribute('data-capacity'));
        if (parseInt(guests) > capacity) {
            warning.style.display = 'block';
        } else {
            warning.style.display = 'none';
        }
    }
}

async function checkAvailability() {
    const facilityId = document.getElementById('facility_id').value;
    const eventDate = document.getElementById('event_date').value;
    const warningDiv = document.getElementById('availability-warning');
    
    if (!facilityId || !eventDate) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'check_availability');
        formData.append('facility_id', facilityId);
        formData.append('event_date', eventDate);
        
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const bookedSlots = await response.json();
        const startTime = document.getElementById('start_time').value;
        const endTime = document.getElementById('end_time').value;
        
        if (startTime && endTime) {
            const conflict = bookedSlots.some(slot => {
                return (startTime < slot.end_time && endTime > slot.start_time);
            });
            
            if (conflict) {
                warningDiv.style.display = 'block';
                document.getElementById('availability-message').textContent = 
                    'Warning: This time slot may conflict with existing reservations.';
            } else {
                warningDiv.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error checking availability:', error);
    }
}

// Form submissions with validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const requiredFields = form.querySelectorAll('[required]');
        let valid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = 'var(--danger)';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!valid) {
            alert('Please fill in all required fields.');
            return;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        const formData = new FormData(this);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving data. Please try again.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});

// Update reservation status
function updateReservationStatus(reservationId, status) {
    const statusText = status === 'confirmed' ? 'confirm' : 
                     status === 'cancelled' ? 'cancel' : 'complete';
                     
    if (confirm(`Are you sure you want to ${statusText} this reservation?`)) {
        const formData = new FormData();
        formData.append('action', 'update_reservation_status');
        formData.append('reservation_id', reservationId);
        formData.append('status', status);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating reservation status.');
        });
    }
}

// View reservation details
function viewReservationDetails(reservationId) {
    alert(`Viewing details for reservation #${reservationId}. This would typically open a detailed view modal.`);
    // Implementation for detailed view would go here
}

// Generate report
function generateReport() {
    alert('Generating comprehensive facilities report... This would typically download a PDF report.');
    // Implementation for report generation would go here
}

// Export data
function exportData() {
    alert('Exporting system data... This would typically download a CSV or Excel file.');
    // Implementation for data export would go here
}

// Mobile sidebar functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('sidebar-open');
}

function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.remove('sidebar-open');
}

// Auto-refresh data every 5 minutes
setInterval(() => {
    // In a real application, this would refresh specific data components
    console.log('Auto-refresh check...');
}, 300000); // 5 minutes