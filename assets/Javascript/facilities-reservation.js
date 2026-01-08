// --- TAB NAVIGATION ---
window.switchTab = function (tabName) {
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
    }

    // Activate the corresponding nav link
    const navLink = document.querySelector(`[data-tab="${tabName}"]`);
    if (navLink) {
        navLink.classList.add('active');
    }

    // Update page title & subtitle
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
    if (pageSubtitleEl) pageSubtitleEl.textContent = subtitles[tabName] || 'Manage hotel';

    sessionStorage.setItem('activeTab', tabName);
};

// --- MODAL CONTROLS ---
window.openModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
};

window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};

window.openReservationModal = function (facilityId) {
    const fIdInput = document.getElementById('facility_id');
    if (fIdInput) {
        fIdInput.value = facilityId;
        if (typeof updateFacilityDetails === 'function') updateFacilityDetails();
        openModal('reservation-modal');
    }
};

window.openLogoutModal = function () {
    openModal('logout-modal');
};

// --- FACILITY LOGIC ---
window.updateFacilityDetails = function () {
    const facilitySelect = document.getElementById('facility_id');
    if (!facilitySelect) return;

    const selectedOption = facilitySelect.options[facilitySelect.selectedIndex];
    const detailsDiv = document.getElementById('facility-details');

    if (selectedOption && selectedOption.value) {
        const rate = selectedOption.getAttribute('data-rate');
        const capacity = selectedOption.getAttribute('data-capacity');

        const capDisp = document.getElementById('capacity-display');
        const rateDisp = document.getElementById('rate-display');

        if (capDisp) capDisp.textContent = capacity;
        if (rateDisp) rateDisp.textContent = rate;
        if (detailsDiv) detailsDiv.style.display = 'block';

        calculateTotal();
        checkAvailability();
    } else if (detailsDiv) {
        detailsDiv.style.display = 'none';
    }
};

window.calculateTotal = function () {
    const facilitySelect = document.getElementById('facility_id');
    const startTime = document.getElementById('start_time')?.value;
    const endTime = document.getElementById('end_time')?.value;
    const totalCost = document.getElementById('total-cost');

    if (facilitySelect?.value && startTime && endTime && totalCost) {
        const rateStr = facilitySelect.options[facilitySelect.selectedIndex].getAttribute('data-rate');
        const rate = parseFloat(rateStr || 0);
        const start = new Date('2000-01-01 ' + startTime);
        const end = new Date('2000-01-01 ' + endTime);

        let hours = (end - start) / (1000 * 60 * 60);
        if (hours < 0) hours += 24;

        const total = hours * rate;
        totalCost.innerHTML = `<i class="fa-solid fa-calculator"></i> Estimated Total: ₱${total.toFixed(2)} (${Math.ceil(hours)} hours)`;
    }
};

window.checkCapacity = function () {
    const facilitySelect = document.getElementById('facility_id');
    const guests = document.getElementById('guests_count')?.value;
    const warning = document.getElementById('capacity-warning');

    if (facilitySelect?.value && guests && warning) {
        const capacity = parseInt(facilitySelect.options[facilitySelect.selectedIndex].getAttribute('data-capacity') || 0);
        warning.style.display = (parseInt(guests) > capacity) ? 'block' : 'none';
    }
};

window.checkAvailability = async function () {
    const fId = document.getElementById('facility_id')?.value;
    const eDate = document.getElementById('event_date')?.value;
    const warningDiv = document.getElementById('availability-warning');

    if (!fId || !eDate || !warningDiv) return;

    try {
        const formData = new FormData();
        formData.append('action', 'check_availability');
        formData.append('facility_id', fId);
        formData.append('event_date', eDate);

        const response = await fetch('', { method: 'POST', body: formData });
        const bookedSlots = await response.json();

        const sTime = document.getElementById('start_time')?.value;
        const eTime = document.getElementById('end_time')?.value;

        if (sTime && eTime) {
            const conflict = bookedSlots.some(slot => (sTime < slot.end_time && eTime > slot.start_time));
            warningDiv.style.display = conflict ? 'block' : 'none';
            const msg = document.getElementById('availability-message');
            if (msg) msg.textContent = conflict ? 'Warning: This time slot may conflict with existing reservations.' : '';
        }
    } catch (e) { console.error('Availability check failed:', e); }
};

// --- RESERVATIONS ---
window.updateReservationStatus = function (id, status) {
    const formData = new FormData();
    formData.append('action', 'update_reservation_status');
    formData.append('reservation_id', id);
    formData.append('status', status);

    fetch('', { method: 'POST', body: formData })
        .then(() => location.reload())
        .catch(err => console.error('Status update error:', err));
};

window.viewReservationDetails = function (data) {
    if (!data) return;
    const res = typeof data === 'string' ? JSON.parse(data) : data;
    const body = document.getElementById('reservation-details-body');
    if (!body) return;

    const colors = { 'pending': '#744210', 'confirmed': '#22543d', 'cancelled': '#c53030', 'completed': '#1a365d' };
    const bgs = { 'pending': '#fefcbf', 'confirmed': '#c6f6d5', 'cancelled': '#fed7d7', 'completed': '#bee3f8' };

    body.innerHTML = `
        <div style="background: ${bgs[res.status] || '#f7fafc'}; padding: 12px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(0,0,0,0.05);">
            <span style="font-weight: 700; color: ${colors[res.status] || '#2d3748'};">Status: ${res.status.toUpperCase()}</span>
            <span style="font-size: 0.85rem; color: #64748b;">ID: #${res.id}</span>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <h4 style="margin-bottom: 8px; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Customer Info</h4>
                <p style="margin: 4px 0;"><i class="fa-solid fa-user" style="width: 20px; color: #64748b;"></i> ${res.customer_name}</p>
                <p style="margin: 4px 0;"><i class="fa-solid fa-envelope" style="width: 20px; color: #64748b;"></i> ${res.customer_email || 'N/A'}</p>
                <p style="margin: 4px 0;"><i class="fa-solid fa-phone" style="width: 20px; color: #64748b;"></i> ${res.customer_phone || 'N/A'}</p>
            </div>
            <div>
                <h4 style="margin-bottom: 8px; font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Event Details</h4>
                <p style="margin: 4px 0;"><i class="fa-solid fa-building" style="width: 20px; color: #64748b;"></i> ${res.facility_name}</p>
                <p style="margin: 4px 0;"><i class="fa-solid fa-star" style="width: 20px; color: #64748b;"></i> ${res.event_type}</p>
                <p style="margin: 4px 0;"><i class="fa-solid fa-users" style="width: 20px; color: #64748b;"></i> ${res.guests_count} guests</p>
            </div>
        </div>
        <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; margin-bottom: 20px;">
            <p style="margin: 4px 0;"><i class="fa-solid fa-calendar-days" style="width: 20px; color: #64748b;"></i> <strong>Date:</strong> ${res.event_date}</p>
            <p style="margin: 4px 0;"><i class="fa-solid fa-clock" style="width: 20px; color: #64748b;"></i> <strong>Time:</strong> ${res.start_time} - ${res.end_time}</p>
            <p style="font-size: 1.25rem; color: #059669; margin-top: 12px; font-weight: 700;">₱${parseFloat(res.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</p>
        </div>
        <div style="background: #f8fafc; padding: 15px; border-radius: 10px; border-left: 4px solid #3b82f6;">
            <h4 style="margin-bottom: 5px; font-size: 0.85rem; color: #475569; font-weight: 600;">Special Requirements</h4>
            <p style="margin: 0; color: #64748b; font-style: ${res.special_requirements ? 'normal' : 'italic'}; line-height: 1.5;">
                ${res.special_requirements || 'No special requirements specified.'}
            </p>
        </div>
    `;
    openModal('details-modal');
};

// --- MANAGEMENT CARD TOGGLES ---
window.showManagementCard = function (type) {
    // Hide all cards
    document.querySelectorAll('.management-card').forEach(el => el.style.display = 'none');

    // Show selected card
    const sel = document.querySelector('.management-card.management-' + type);
    if (sel) sel.style.display = 'block';

    // Update active button styling
    const btns = {
        'facilities': document.getElementById('show-facilities-card'),
        'maintenance': document.getElementById('show-maintenance-card'),
        'reports': document.getElementById('show-reports-card')
    };

    Object.keys(btns).forEach(key => {
        if (btns[key]) btns[key].classList.toggle('active', key === type);
    });
};

// --- INITIALIZATION ---
function initializePage() {
    // Tab wiring
    const activeTab = sessionStorage.getItem('activeTab') || 'dashboard';
    switchTab(activeTab);

    // Initial management card
    showManagementCard('facilities');

    // Nav links listeners
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            const tab = this.getAttribute('data-tab');
            if (!href || href === '#' || href.startsWith('javascript:')) {
                if (tab) {
                    e.preventDefault();
                    switchTab(tab);
                }
            }
        });
    });

    // Button click listeners for Management
    const bf = document.getElementById('show-facilities-card');
    const bm = document.getElementById('show-maintenance-card');
    const br = document.getElementById('show-reports-card');

    if (bf) bf.addEventListener('click', () => showManagementCard('facilities'));
    if (bm) bm.addEventListener('click', () => showManagementCard('maintenance'));
    if (br) br.addEventListener('click', () => showManagementCard('reports'));

    // Global click-to-close behavior
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) closeModal(e.target.id);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage);
} else {
    initializePage();
}
