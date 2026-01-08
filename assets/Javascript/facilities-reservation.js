// Tab navigation (make it globally accessible)
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
document.addEventListener('DOMContentLoaded', function () {
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
        link.addEventListener('click', function (e) {
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

// BAGONG FUNCTION: Logout Modal Handler
window.openLogoutModal = function () {
    openModal('logout-modal');
}

// BAGONG FUNCTION: Confirmed Logout Redirect
window.confirmLogout = function () {
    // I-close ang modal at i-redirect sa login page na may logout flag
    closeModal('logout-modal');
    window.location.href = '../auth/login.php?logout=1';
}

// Close modal when clicking outside
window.addEventListener('click', function (event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function (event) {
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
        totalCost.innerHTML = `<span class="icon-img-placeholder">🧮</span> Estimated Total: ₱${total.toFixed(2)} (${Math.ceil(hours)} hours)`;
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
    form.addEventListener('submit', function (e) {
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
            // Pinalitan ang alert() ng console.log() dahil bawal ang alert()
            console.error('Please fill in all required fields.');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="icon-img-placeholder">🔄</span> Processing...';
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
                // Pinalitan ang alert() ng console.error()
                console.error('Error saving data. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    });
});

// Update reservation status
function updateReservationStatus(reservationId, status) {
    const statusText = status === 'confirmed' ? 'confirm' :
        status === 'cancelled' ? 'cancel' : 'complete';

    // Pinalitan ang confirm() ng simulated UI behavior
    if (true) { // Assuming a custom modal confirms the action instead of built-in confirm()
        console.log(`SIMULATION: User confirmed action to ${statusText} reservation #${reservationId}`);

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
                // Pinalitan ang alert() ng console.error()
                console.error('Error updating reservation status.');
            });
    }
}

// View reservation details
function viewReservationDetails(data) {
    if (!data) return;

    // Convert to object if it's a string (though we passed object in PHP)
    const reservation = typeof data === 'string' ? JSON.parse(data) : data;

    const body = document.getElementById('reservation-details-body');
    if (!body) return;

    // Status color mapping
    const statusColors = {
        'pending': '#744210',
        'confirmed': '#22543d',
        'cancelled': '#c53030',
        'completed': '#1a365d'
    };

    const statusBg = {
        'pending': '#fefcbf',
        'confirmed': '#c6f6d5',
        'cancelled': '#fed7d7',
        'completed': '#bee3f8'
    };

    body.innerHTML = `
        <div style="background: ${statusBg[reservation.status] || '#f7fafc'}; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(0,0,0,0.05);">
            <span style="font-weight: 700; color: ${statusColors[reservation.status] || '#2d3748'};">Status: ${reservation.status.toUpperCase()}</span>
            <span style="font-size: 0.85rem; color: #4a5568;">ID: #${reservation.id}</span>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <h4 style="margin-bottom: 8px; font-size: 0.9rem; color: #718096; text-transform: uppercase;">Customer Info</h4>
                <p><strong><span class="icon-img-placeholder">👤</span> Name:</strong> ${reservation.customer_name}</p>
                <p><strong><span class="icon-img-placeholder">📧</span> Email:</strong> ${reservation.customer_email || 'N/A'}</p>
                <p><strong><span class="icon-img-placeholder">📞</span> Phone:</strong> ${reservation.customer_phone || 'N/A'}</p>
            </div>
            <div>
                <h4 style="margin-bottom: 8px; font-size: 0.9rem; color: #718096; text-transform: uppercase;">Event Details</h4>
                <p><strong><span class="icon-img-placeholder">🏢</span> Facility:</strong> ${reservation.facility_name}</p>
                <p><strong><span class="icon-img-placeholder">🎭</span> Type:</strong> ${reservation.event_type}</p>
                <p><strong><span class="icon-img-placeholder">👥</span> Guests:</strong> ${reservation.guests_count} people</p>
            </div>
        </div>
        
        <div style="border-top: 1px solid #e2e8f0; padding-top: 15px; margin-bottom: 20px;">
            <h4 style="margin-bottom: 8px; font-size: 0.9rem; color: #718096; text-transform: uppercase;">Schedule & Cost</h4>
            <div style="display: flex; gap: 30px;">
                <p><strong><span class="icon-img-placeholder">📅</span> Date:</strong> ${reservation.event_date}</p>
                <p><strong><span class="icon-img-placeholder">⌚</span> Time:</strong> ${reservation.start_time} - ${reservation.end_time}</p>
            </div>
            <p style="font-size: 1.1rem; color: #38a169; margin-top: 10px;"><strong><span class="icon-img-placeholder">💰</span> Total Amount:</strong> ₱${parseFloat(reservation.total_amount).toLocaleString(undefined, { minimumFractionDigits: 2 })}</p>
        </div>
        
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 4px solid #3182ce;">
            <h4 style="margin-bottom: 5px; font-size: 0.9rem; color: #4a5568;">Special Requirements</h4>
            <p style="margin: 0; color: #4a5568; font-style: ${reservation.special_requirements ? 'normal' : 'italic'};">
                ${reservation.special_requirements || 'No special requirements specified.'}
            </p>
        </div>
    `;

    openModal('details-modal');
}

// Generate report
function generateReport() {
    // Pinalitan ang alert() ng console.log()
    console.log('Generating comprehensive facilities report... This would typically download a PDF report.');
    // Implementation for report generation would go here
}

// Export data
function exportData() {
    // Pinalitan ang alert() ng console.log()
    console.log('Exporting system data... This would typically download a CSV or Excel file.');
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

/* --- START: Added tab helpers moved from inline PHP --- */

(function () {
    // Expose openTab globally so existing inline onclick handlers still work
    window.openTab = function (tabId) {
        // Prefer centralized switchTab if available
        if (typeof window.switchTab === 'function') {
            try {
                window.switchTab(tabId);
                const el = document.getElementById(tabId);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            } catch (e) {
                console.warn('switchTab failed, falling back to manual openTab:', e);
            }
        }

        // Fallback: hide all tab contents and show the requested one
        document.querySelectorAll('.tab-content').forEach(function (el) {
            el.style.display = 'none';
            el.classList.remove('active');
        });

        var t = document.getElementById(tabId);
        if (t) {
            t.style.display = 'block';
            t.classList.add('active');
            t.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            console.warn('openTab: tab not found ->', tabId);
        }

        // Persist selection
        try { sessionStorage.setItem('activeTab', tabId); } catch (e) { /* ignore */ }
    };

    // Make elements with data-open-tab clickable (optional: add data-open-tab in PHP to turn whole card clickable)
    function attachDataOpenTab() {
        document.querySelectorAll('[data-open-tab]').forEach(function (el) {
            el.style.cursor = 'pointer';
            el.addEventListener('click', function (e) {
                // avoid double-activation if inner clickable elements exist
                if (e.target && (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('button'))) return;
                var tab = el.getAttribute('data-open-tab');
                if (tab) window.openTab(tab);
            });
        });
    }

    // Initialize on DOMContentLoaded: restore last active tab (or dashboard)
    document.addEventListener('DOMContentLoaded', function () {
        // Attach data-open-tab handlers
        attachDataOpenTab();

        var active = 'dashboard';
        try {
            var stored = sessionStorage.getItem('activeTab');
            if (stored) active = stored;
        } catch (e) { /* ignore storage errors */ }

        // If switchTab exists prefer it (keeps nav link state in sync)
        if (typeof window.switchTab === 'function') {
            window.switchTab(active);
        } else {
            // Fallback: hide all and show chosen
            document.querySelectorAll('.tab-content').forEach(function (el) { el.style.display = 'none'; });
            var t = document.getElementById(active);
            if (t) t.style.display = 'block';
        }
    });
})();

/* --- END: Added tab helpers moved from inline PHP --- */

// Mock Contracts Data (Fix for PHP dependency)
// IMPORTANT: This object contains nested JSON strings for 'risk_factors' and 'recommendations' 
// to simulate complex data from an an AI analysis service.
const MOCK_CONTRACTS_DATA = [
    { contract_name: 'Client Services Agreement', case_id: 'C-001', risk_level: 'High', risk_score: 85, upload_date: '2023-05-20', file_path: 'contract_c001.pdf', analysis_summary: 'Significant exposure in termination and liability clauses.', risk_factors: '[{"category": "legal_risk", "factor": "Ambiguous termination clause", "weight": 40}, {"category": "financial_risk", "factor": "High liability cap", "weight": 45}]', recommendations: '["Redraft Clause 7.1 to clearly define termination conditions.", "Lower the liability cap to 50% of annual service fee."]' },
    { contract_name: 'Vendor Supply Contract', case_id: 'C-002', risk_level: 'Medium', risk_score: 55, upload_date: '2023-06-25', file_path: 'contract_c002.pdf', analysis_summary: 'Standard contract with minor intellectual property concerns regarding derived work.', risk_factors: '[{"category": "ip_risk", "factor": "Ownership unclear on derived work", "weight": 55}]', recommendations: '["Add a clause clarifying IP ownership for all derivative materials."]' },
    { contract_name: 'Employment NDA', case_id: 'E-010', risk_level: 'Low', risk_score: 20, upload_date: '2023-07-10', file_path: 'contract_e010.pdf', analysis_summary: 'Standard, low-risk non-disclosure agreement.', risk_factors: '[]', recommendations: '["No major changes needed. Standardize for all new hires."]' },
    { contract_name: 'Joint Venture Agreement', case_id: 'C-003', risk_level: 'High', risk_score: 92, upload_date: '2023-08-01', file_path: 'contract_c003.pdf', analysis_summary: 'Extremely high commitment with open-ended duration and no defined exit strategy.', risk_factors: '[{"category": "strategic_risk", "factor": "Open-ended duration", "weight": 50}, {"category": "financial_risk", "factor": "Uncapped capital calls", "weight": 42}]', recommendations: '["Define a hard stop date or periodic review for termination.", "Cap the capital commitment amount."]' },
    { contract_name: 'Lease Agreement', case_id: 'F-005', risk_level: 'Medium', risk_score: 40, upload_date: '2023-09-15', file_path: 'contract_f005.pdf', analysis_summary: 'Standard commercial lease with a few negotiable points in the maintenance section.', risk_factors: '[]', recommendations: '["Ensure maintenance responsibilities are fully covered by tenant."]' }
];

document.addEventListener('DOMContentLoaded', function () {
    const pinInputs = document.querySelectorAll('.pin-digit');
    const loginBtn = document.getElementById('loginBtn');
    const errorMessage = document.getElementById('errorMessage');
    const loginScreen = document.getElementById('loginScreen');
    const dashboard = document.getElementById('dashboard');
    const logoutBtn = document.getElementById('logoutBtn');

    // Correct PIN (in a real application, this would be stored securely)
    const correctPIN = '1234';

    // Focus on first PIN input
    pinInputs[0]?.focus();

    // Move to next input when a digit is entered
    pinInputs.forEach((input, index) => {
        input.addEventListener('input', function () {
            // Only allow numbers and max 1 digit (ADDED VALIDATION)
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);

            if (this.value.length === 1 && index < pinInputs.length - 1) {
                pinInputs[index + 1].focus();
            }

            // Hide error message on input change
            errorMessage.style.display = 'none';
        });

        // Allow backspace to move to previous input
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                pinInputs[index - 1].focus();
            }
        });
    });

    // Login functionality
    loginBtn?.addEventListener('click', function () {
        const enteredPIN = Array.from(pinInputs).map(input => input.value).join('');

        if (enteredPIN === correctPIN) {
            // Successful login
            loginScreen.style.display = 'none';
            dashboard.style.display = 'block';
            // Note: The original legalmanagement.php HTML defines the contracts section as active by default.
            // Since this JS is now merged with another system, we need to ensure the legal management
            // tabs are properly initialized IF the user is on the legal management page.
            // Since we don't have the context of the current page (facilities vs legal), we can skip 
            // the default tab setting here if the legal management page isn't active.

            // Initialize dashboard data
            initializeDashboard();
        } else {
            // Failed login
            errorMessage.style.display = 'block';
            pinInputs.forEach(input => {
                input.value = '';
            });
            pinInputs[0]?.focus();
        }
    });

    // Logout functionality
    logoutBtn?.addEventListener('click', function () {
        dashboard.style.display = 'none';
        loginScreen.style.display = 'flex';

        // Clear PIN inputs
        pinInputs.forEach(input => {
            input.value = '';
        });
        pinInputs[0]?.focus();
        errorMessage.style.display = 'none';

        // Hide all content sections and reactivate login screen
        document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
        document.querySelectorAll('.nav-tab').forEach(tab => tab.classList.remove('active'));
    });

    // Navigation tabs
    const navTabs = document.querySelectorAll('.nav-tab');
    const contentSections = document.querySelectorAll('.content-section');

    navTabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');

            // Update active tab
            navTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Show corresponding content section
            contentSections.forEach(section => {
                section.classList.remove('active');
                if (section.id === targetId) {
                    section.classList.add('active');
                }
            });
        });
    });

    // Initialize dashboard with sample data
    function initializeDashboard() {
        // Sample data for other sections
        const documents = [
            { name: 'Employment Contract.pdf', case: 'C-001', date: '2023-05-20' },
            { name: 'Supplier Agreement.docx', case: 'C-002', date: '2023-06-25' }
        ];

        const billing = [
            { invoice: 'INV-001', client: 'Hotel Management', amount: '$2,500', dueDate: '2023-07-15', status: 'paid' },
            { invoice: 'INV-002', client: 'Restaurant Owner', amount: '$1,800', dueDate: '2023-08-05', status: 'pending' }
        ];

        const members = [
            { name: 'Robert Wilson', position: 'Senior Legal Counsel', email: 'robert@legalteam.com', phone: '(555) 111-2222' },
            { name: 'Emily Davis', position: 'Legal Assistant', email: 'emily@legalteam.com', phone: '(555) 333-4444' }
        ];

        // Populate tables with data
        populateTable('documentsTableBody', documents, 'document');
        populateTable('billingTableBody', billing, 'billing');
        populateTable('membersTableBody', members, 'member');
        // Contracts table uses the mock data
        populateTable('contractsTableBody', MOCK_CONTRACTS_DATA, 'contract');


        // Initialize risk analysis chart
        initializeRiskChart();

        // Set up form handlers
        setupFormHandlers();
    }

    // Function to populate tables with data
    function populateTable(tableId, data, type) {
        const tableBody = document.getElementById(tableId);
        if (!tableBody) return;

        tableBody.innerHTML = '';

        data.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';

            if (type === 'document') {
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">${item.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.case}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.date}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button class="action-btn view-btn bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded-lg text-xs">View</button>
                    </td>
                `;
            } else if (type === 'billing') {
                const statusClass = `status-${item.status}`;
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">${item.invoice}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.client}</td>
                    <td class="px-6 py-4 whitespace-nowrap font-medium">${item.amount}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.dueDate}</td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="status-badge ${statusClass}">${item.status.toUpperCase()}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
                        <button class="action-btn view-btn bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded-lg text-xs">View</button>
                        <button class="action-btn bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded-lg text-xs">Edit</button>
                    </td>
                `;
            } else if (type === 'member') {
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">${item.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.position}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-blue-600 hover:underline">${item.email}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.phone}</td>
                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
                        <button class="action-btn view-btn bg-gray-200 hover:bg-gray-300 text-gray-700 py-1 px-3 rounded-lg text-xs">View</button>
                        <button class="action-btn bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded-lg text-xs">Edit</button>
                    </td>
                `;
            } else if (type === 'contract') {
                const statusClass = `status-${item.risk_level.toLowerCase()}`;
                // Attach the full contract object as a data attribute (JSON string) for the 'Analyze' button
                // Reversing the quoting issue for JSON string
                const contractDataString = JSON.stringify(item).replace(/"/g, '&quot;');

                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">${item.contract_name}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.case_id}</td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="status-badge ${statusClass}">${item.risk_level.toUpperCase()}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap">${item.risk_score}/100</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.upload_date}</td>
                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
                        <button class="action-btn analyze-btn bg-blue-600 hover:bg-blue-700 text-white py-1 px-3 rounded-lg text-xs" data-contract="${contractDataString}">AI Analyze</button>
                        <button class="action-btn download-btn bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded-lg text-xs" data-file="${item.file_path}">Download</button>
                    </td>
                `;
            }

            tableBody.appendChild(row);
        });
    }

    // Function to initialize risk analysis chart (FIXED: uses MOCK_CONTRACTS_DATA)
    function initializeRiskChart() {
        const ctx = document.getElementById('riskChart');
        if (!ctx) return;

        const contracts = MOCK_CONTRACTS_DATA; // <-- FIXED: Uses JavaScript mock data

        const riskCounts = { High: 0, Medium: 0, Low: 0 };

        contracts.forEach(contract => {
            if (riskCounts.hasOwnProperty(contract.risk_level)) {
                riskCounts[contract.risk_level]++;
            }
        });

        // Destroy previous chart instance if it exists (for re-initialization)
        const existingChart = Chart.getChart(ctx);
        if (existingChart) {
            existingChart.destroy();
        }

        const chartCtx = ctx.getContext('2d');

        const chart = new Chart(chartCtx, {
            type: 'bar',
            data: {
                labels: ['High Risk', 'Medium Risk', 'Low Risk'],
                datasets: [{
                    label: 'Number of Contracts',
                    data: [riskCounts.High, riskCounts.Medium, riskCounts.Low],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.7)', // Red for High
                        'rgba(245, 158, 11, 0.7)', // Yellow/Orange for Medium
                        'rgba(16, 185, 129, 0.7)' // Green for Low
                    ],
                    borderColor: [
                        'rgba(239, 68, 68, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: true, color: '#f3f4f6' },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Contract Risk Distribution (Based on ' + contracts.length + ' files)',
                        font: { size: 16 }
                    }
                }
            }
        });

        // Display analysis results
        const totalContracts = contracts.length;
        const highRiskPercentage = totalContracts > 0 ? ((riskCounts.High / totalContracts) * 100).toFixed(1) : 0;
        const analysisResults = document.getElementById('analysisResults');
        if (analysisResults) {
            analysisResults.innerHTML = `
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Risk Analysis Summary</h3>
                <div class="space-y-2 text-gray-700">
                    <p><strong>Total Contracts Analyzed:</strong> <span class="font-bold text-blue-600">${totalContracts}</span></p>
                    <p><strong>High Risk:</strong> <span class="font-bold text-red-500">${riskCounts.High} (${highRiskPercentage}%)</span></p>
                    <p><strong>Medium Risk:</strong> <span class="font-bold text-yellow-600">${riskCounts.Medium}</span></p>
                    <p><strong>Low Risk:</strong> <span class="font-bold text-green-600">${riskCounts.Low}</span></p>
                    <p class="pt-2 border-t mt-3"><strong>AI Recommendation:</strong> ${riskCounts.High > 0 ? '<span class="font-semibold text-red-500">Immediate review needed for high-risk files.</span>' : '<span class="font-semibold text-green-600">All contracts are within acceptable risk levels.</span>'}</p>
                </div>
            `;
        }
    }

    // Enhanced Form Handlers
    function setupFormHandlers() {
        // Employee form is currently not fully implemented in HTML/JS, so focusing on contracts
        // Employee form handlers
        // ... (Employee form handlers and validation are omitted for brevity as they are not the source of the error)

        // Contract form handlers
        const addContractBtn = document.getElementById('addContractBtn');
        const contractForm = document.getElementById('contractForm');
        const cancelContractBtn = document.getElementById('cancelContractBtn');
        const contractFormData = document.getElementById('contractFormData');

        if (addContractBtn && contractForm) {
            addContractBtn.addEventListener('click', function () {
                contractForm.style.display = 'block';
                contractForm.scrollIntoView({ behavior: 'smooth' });
            });
        }

        if (cancelContractBtn && contractForm) {
            cancelContractBtn.addEventListener('click', function () {
                contractForm.style.display = 'none';
                resetContractForm();
            });
        }

        if (contractFormData) {
            contractFormData.addEventListener('submit', function (e) {
                e.preventDefault();
                if (validateContractForm()) {
                    console.log('SUCCESS: Form submitted with data:', new FormData(this));
                    // In a real app, this would send data to the server and re-initialize the dashboard
                    contractForm.style.display = 'none';
                    resetContractForm();
                    // For demonstration, we'll simulate an update
                    console.log('SIMULATION: Contract uploaded and sent for AI analysis.');
                } else {
                    console.error('FORM ERROR: Contract submission failed validation.');
                }
            });
        }

        // Client-side form validation for contracts
        function validateContractForm() {
            const name = document.getElementById('contractName').value.trim();
            const caseId = document.getElementById('contractCase').value.trim();
            const fileInput = document.getElementById('contractFile');
            const file = fileInput.files[0];

            clearContractErrors();

            let isValid = true;

            if (!name) {
                showError('contractName', 'Contract name is required');
                isValid = false;
            }

            if (!caseId) {
                showError('contractCase', 'Case ID is required');
                isValid = false;
            }

            if (!file) {
                showError('contractFile', 'Please select a file');
                isValid = false;
            } else if (file.size > 10 * 1024 * 1024) { // 10MB limit
                showError('contractFile', 'File size must be less than 10MB');
                isValid = false;
            } else if (!['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(file.type)) {
                showError('contractFile', 'Please upload a PDF, DOC, or DOCX file');
                isValid = false;
            }

            return isValid;
        }

        // Helper function to show errors
        function showError(fieldId, message) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.classList.add('error');

                let errorElement = field.parentNode.querySelector('.error-text');
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-text';
                    field.parentNode.appendChild(errorElement);
                }
                errorElement.textContent = message;
            }
        }

        // Helper function to clear contract errors
        function clearContractErrors() {
            const fields = ['contractName', 'contractCase', 'contractFile'];
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.classList.remove('error');

                    const errorElement = field.parentNode.querySelector('.error-text');
                    if (errorElement) {
                        errorElement.remove();
                    }
                }
            });
        }

        // Reset contract form
        function resetContractForm() {
            document.getElementById('contractFormData')?.reset();
            clearContractErrors();
        }

        // FIX: Replaced alert() with console.log() and simulated UI behavior
        document.getElementById('addDocumentBtn')?.addEventListener('click', function () {
            console.log('SIMULATION: Document upload form would appear here.');
            // Show a simple confirmation message on the screen instead of alert()
            const button = this;
            button.textContent = 'Form Shown!';
            setTimeout(() => button.textContent = 'Add New Document', 1500);
        });

        document.getElementById('addInvoiceBtn')?.addEventListener('click', function () {
            console.log('SIMULATION: Invoice creation form would appear here.');
            const button = this;
            button.textContent = 'Form Shown!';
            setTimeout(() => button.textContent = 'Create New Invoice', 1500);
        });

        document.getElementById('addMemberBtn')?.addEventListener('click', function () {
            console.log('SIMULATION: Team member addition form would appear here.');
            const button = this;
            button.textContent = 'Form Shown!';
            setTimeout(() => button.textContent = 'Add New Member', 1500);
        });

        // FIX: Replaced confirm() with console.log and simulated UI behavior
        document.getElementById('exportPdfBtn')?.addEventListener('click', function () {
            const password = 'legal2025';
            console.log("SIMULATION: Download confirmation required. Password for Secured PDF: " + password);

            // --- Custom Modal Simulation (Replacing confirm) ---
            const modal = document.getElementById('detailsModal');
            document.getElementById('detailsTitle').innerText = 'Secure Report Export';
            document.getElementById('detailsBody').innerHTML = `
                <div class="text-lg text-gray-800">
                    <p class="mb-2">Are you sure you want to download the Secured PDF Report?</p>
                    <p class="font-mono text-sm bg-gray-100 p-3 rounded-lg border">Password for the PDF: <strong class="text-blue-600">${password}</strong></p>
                    <p class="mt-4 text-sm text-gray-500">NOTE: This is a simulation. Downloading a .txt file containing the report data will be simulated upon confirmation.</p>
                </div>
            `;
            document.getElementById('detailsModal').style.display = 'flex';

            // We'll simulate 'OK' by adding a temporary button to the modal footer
            const footer = modal.querySelector('.mt-4.pt-3.border-t.flex.justify-end');
            const originalCloseBtn = footer.querySelector('button');
            originalCloseBtn.textContent = 'Cancel';

            const confirmDownloadBtn = document.createElement('button');
            confirmDownloadBtn.textContent = 'Confirm Download';
            confirmDownloadBtn.className = 'bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow transition duration-200 ml-3';
            confirmDownloadBtn.addEventListener('click', function () {
                console.log("DOWNLOAD START: Simulating download of secured_legal_report.txt (Password: " + password + ")");

                // Actual file download simulation (creating a blob and downloading)
                const data = "Secured Legal Report (Password: legal2025)\n\n" + JSON.stringify(MOCK_CONTRACTS_DATA, null, 2);
                const blob = new Blob([data], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'secured_legal_report.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                // Clean up modal
                modal.style.display = 'none';
                footer.removeChild(confirmDownloadBtn);
                originalCloseBtn.textContent = 'Close';
            });
            footer.appendChild(confirmDownloadBtn);
        });
    }
});

// Global click listener for view, analyze, and modal close
document.addEventListener('click', function (e) {
    const detailsModal = document.getElementById('detailsModal');

    // Close modal button/overlay
    if (e.target.id === 'closeDetails' || (e.target.classList.contains('modal') && e.target.id === 'detailsModal')) {
        detailsModal.style.display = 'none';
        // Restore original close button in case a temp confirm button was added
        const footer = detailsModal.querySelector('.mt-4.pt-3.border-t.flex.justify-end');
        const confirmBtn = footer.querySelector('.bg-green-600');
        if (confirmBtn) {
            confirmBtn.remove();
            footer.querySelector('button').textContent = 'Close';
        }
        return;
    }

    // Analyze button handler for contracts with AI analysis
    if (e.target && e.target.classList.contains('analyze-btn')) {
        const contractDataString = e.target.getAttribute('data-contract');
        if (!contractDataString) return;

        // FIX: The replacement of " in the JSON string needs to be reversed for proper parsing
        const contractData = JSON.parse(contractDataString.replace(/&quot;/g, '"'));

        let riskFactorsHtml = '';
        let recommendationsHtml = '';

        // Parse risk factors
        try {
            const riskFactors = JSON.parse(contractData.risk_factors || '[]');
            riskFactors.forEach(factor => {
                // Using lucide-react equivalent icons via SVG or simple text 剥 庁
                riskFactorsHtml += `
                    <div class="risk-factor-item flex items-start">
                        <span class="mr-2 text-red-500 font-bold">•</span>
                        <div>
                            <strong>${factor.category.replace('_', ' ').toUpperCase()}:</strong> ${factor.factor} 
                            <span class="text-xs text-gray-500">(Weight: ${factor.weight})</span>
                        </div>
                    </div>
                `;
            });
        } catch (e) {
            console.error("Error parsing risk factors:", e);
            riskFactorsHtml = '<div class="risk-factor-item text-gray-500 italic">No specific risk factors identified.</div>';
        }

        // Parse recommendations
        try {
            const recommendations = JSON.parse(contractData.recommendations || '[]');
            recommendations.forEach(rec => {
                recommendationsHtml += `<div class="recommendation-item flex items-start"><span class="mr-2 text-green-600 font-bold">✓</span> ${rec}</div>`;
            });
        } catch (e) {
            console.error("Error parsing recommendations:", e);
            recommendationsHtml = '<div class="recommendation-item text-gray-500 italic">No specific recommendations available.</div>';
        }

        const statusClass = `status-${contractData.risk_level.toLowerCase()}`;
        const html = `
            <div style="line-height:1.6;">
                <div class="ai-analysis-section p-4 bg-blue-50 rounded-lg mb-6 border border-blue-200">
                    <h4 class="flex items-center text-blue-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 15v-6h-2v6h2zm0-8V7h-2v2h2z"/></svg>
                        AI Risk Analysis Report
                    </h4>
                    <p class="mb-1"><strong>Contract:</strong> ${contractData.contract_name}</p>
                    <p class="mb-1"><strong>Case ID:</strong> ${contractData.case_id}</p>
                    <p class="mb-1"><strong>Risk Level:</strong> <span class="status-badge ${statusClass}">${contractData.risk_level.toUpperCase()}</span></p>
                    <p class="mb-1"><strong>Risk Score:</strong> <span class="font-bold text-lg">${contractData.risk_score}/100</span></p>
                    <p class="mt-2 text-sm italic border-t pt-2"><strong>Summary:</strong> ${contractData.analysis_summary || 'No summary available'}</p>
                </div>
                
                <div class="ai-analysis-section mb-6">
                    <h5 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 22h20zM12 8v4M12 16h.01"/></svg>
                        Identified Risk Factors
                    </h5>
                    <div class="risk-factors">
                        ${riskFactorsHtml}
                    </div>
                </div>
                
                <div class="ai-analysis-section">
                    <h5 class="text-lg font-semibold text-gray-800 mb-2 border-b pb-1 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
                        AI Recommendations
                    </h5>
                    <div class="recommendations">
                        ${recommendationsHtml}
                    </div>
                </div>
            </div>
        `;

        document.getElementById('detailsTitle').innerText = `AI Risk Analysis for ${contractData.contract_name}`;
        document.getElementById('detailsBody').innerHTML = html;
        detailsModal.style.display = 'flex';
    }

    // Download button handler for contracts
    if (e.target && e.target.classList.contains('download-btn')) {
        const filePath = e.target.getAttribute('data-file');
        console.log(`SIMULATION: Initiating download for file path: ${filePath}`);

        // Simulation: create a dummy link to show the file name
        if (filePath) {
            const tempLink = document.createElement('a');
            tempLink.href = '#'; // Use dummy link
            tempLink.textContent = `Downloading ${filePath}... (Check console)`;
            tempLink.className = 'text-green-600 text-sm block mt-2';
            e.target.parentNode.appendChild(tempLink);

            setTimeout(() => {
                tempLink.remove();
            }, 2000);
        }
    }
});

// Real-time AI analysis preview
document.getElementById('contractDescription')?.addEventListener('input', function (e) {
    const description = e.target.value;
    const previewDiv = document.getElementById('aiAnalysisPreview');

    if (description.length > 50) {
        console.log('AI analyzing contract description...');
        // In a real implementation, this would call an API for real-time analysis
        // For demo: update a visible section with simulated analysis
        let preview = document.getElementById('analysisPreviewText');
        if (!preview) {
            preview = document.createElement('p');
            preview.id = 'analysisPreviewText';
            preview.className = 'text-sm text-gray-600 italic mt-2 p-2 bg-blue-50 rounded';
            document.getElementById('contractDescription').parentNode.appendChild(preview);
        }

        // Simple mock logic for preview
        let risk = description.toLowerCase().includes('liability') || description.toLowerCase().includes('breach') ? 'Medium/High' : 'Low';
        preview.innerHTML = `AI Preview: <span class="font-semibold text-blue-700">${risk} Risk</span> predicted. Focus on clause structure and duration.`;
    } else {
        document.getElementById('analysisPreviewText')?.remove();
    }
});

/* --- START: Management card toggles --- */
(function () {
    function showManagementCard(type) {
        document.querySelectorAll('.management-card').forEach(function (el) {
            el.style.display = 'none';
        });
        var sel = document.querySelector('.management-card.management-' + type);
        if (sel) sel.style.display = 'block';
        // update active button styling (simple)
        var a = document.getElementById('show-facilities-card');
        var b = document.getElementById('show-reports-card');
        if (a && b) {
            a.classList.toggle('active', type === 'facilities');
            b.classList.toggle('active', type === 'reports');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // wire buttons if present
        var bf = document.getElementById('show-facilities-card');
        var br = document.getElementById('show-reports-card');
        if (bf) bf.addEventListener('click', function () { showManagementCard('facilities'); });
        if (br) br.addEventListener('click', function () { showManagementCard('reports'); });

        // initial state: show facilities card by default
        showManagementCard('facilities');
    });
})();
/* --- END: Management card toggles --- */
