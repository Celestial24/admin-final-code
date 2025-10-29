 // JavaScript for the Visitor Management System
        
        // Data storage (in a real application, this would be a database)
        let hotelVisitors = JSON.parse(localStorage.getItem('hotelVisitors')) || [];
        let restaurantVisitors = JSON.parse(localStorage.getItem('restaurantVisitors')) || [];
        let settings = JSON.parse(localStorage.getItem('visitorSettings')) || {
            businessName: "Hotel & Restaurant",
            timezone: "UTC",
            dataRetention: 365
        };

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Set up navigation
            setupNavigation();
            
            // Set up form submissions
            setupForms();
            
            // Initialize dashboard
            updateDashboard();
            
            // Load current visitors
            loadCurrentVisitors();
            
            // Apply settings
            applySettings();
        });

        // Navigation setup
        function setupNavigation() {
            // Main navigation
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pageId = this.getAttribute('data-page');
                    showPage(pageId);
                    
                    // Update active states
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update sidebar
                    const sidebarLinks = document.querySelectorAll('.sidebar-link');
                    sidebarLinks.forEach(l => {
                        if (l.getAttribute('data-page') === pageId) {
                            l.classList.add('active');
                        } else {
                            l.classList.remove('active');
                        }
                    });
                });
            });
             
            // Sidebar navigation
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pageId = this.getAttribute('data-page');
                    // If the sidebar link targets a sub-section like 'hotel-checkin',
                    // split by '-' and use the parent page as the main page to show.
                    let mainPage = pageId;
                    let tabToActivate = null;
                    if (pageId.includes('-')) {
                        const parts = pageId.split('-');
                        mainPage = parts[0];
                        // For cases like 'hotel-checkin' or 'restaurant-checkin'
                        // reconstruct expected data-tab value
                        tabToActivate = pageId;
                    }

                    showPage(mainPage);

                    // Update active states for sidebar
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    // Update main nav active item
                    const mainPages = ['dashboard', 'hotel', 'restaurant', 'reports'];
                    if (mainPages.includes(mainPage)) {
                        navLinks.forEach(l => {
                            if (l.getAttribute('data-page') === mainPage) {
                                l.classList.add('active');
                            } else {
                                l.classList.remove('active');
                            }
                        });
                    }

                    // If a specific inner tab is requested, activate it
                    if (tabToActivate) {
                        // Find the tab element with matching data-tab
                        const tabSelector = `.tab[data-tab="${tabToActivate}"]`;
                        const tabEl = document.querySelector(tabSelector);
                        if (tabEl) {
                            // Activate the tab within its parent page
                            const parent = tabEl.closest('.page');
                            const siblingTabs = parent.querySelectorAll('.tab');
                            siblingTabs.forEach(t => t.classList.remove('active'));
                            tabEl.classList.add('active');

                            // Show corresponding tab content
                            const tabContents = parent.querySelectorAll('.tab-content');
                            tabContents.forEach(content => {
                                if (content.id === `${tabToActivate}-tab`) {
                                    content.classList.add('active');
                                } else {
                                    content.classList.remove('active');
                                }
                            });
                        }
                    }
                });
            });
            
            // Tab navigation
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    const parent = this.closest('.page');
                    
                    // Update active tab
                    const siblingTabs = parent.querySelectorAll('.tab');
                    siblingTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabContents = parent.querySelectorAll('.tab-content');
                    tabContents.forEach(content => {
                        if (content.id === `${tabId}-tab`) {
                            content.classList.add('active');
                        } else {
                            content.classList.remove('active');
                        }
                    });
                });
            });
            
            // Show report date range based on selection
            const reportType = document.getElementById('report-type');
            if (reportType) {
                reportType.addEventListener('change', function() {
                    const customRange = document.getElementById('custom-date-range');
                    if (this.value === 'custom') {
                        customRange.style.display = 'block';
                    } else {
                        customRange.style.display = 'none';
                    }
                });
            }
        }

        // Show specific page
        function showPage(pageId) {
            // Hide all pages
            const pages = document.querySelectorAll('.page');
            pages.forEach(page => page.classList.remove('active'));
            
            // Show requested page
            const targetPage = document.getElementById(pageId);
            if (targetPage) {
                targetPage.classList.add('active');
                
                // Load data if needed
                if (pageId === 'dashboard') {
                    updateDashboard();
                } else if (pageId === 'hotel' || pageId === 'restaurant') {
                    loadCurrentVisitors();
                }
            }
        }

        // Setup form submissions
        function setupForms() {
            // Hotel check-in form
            const hotelCheckinForm = document.getElementById('hotel-checkin-form');
            if (hotelCheckinForm) {
                hotelCheckinForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    timeInHotelGuest();
                });
            }
            
            // Restaurant check-in form
            const restaurantCheckinForm = document.getElementById('restaurant-checkin-form');
            if (restaurantCheckinForm) {
                restaurantCheckinForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    timeInRestaurantVisitor();
                });
            }
            
            // Report form
            const reportForm = document.getElementById('report-form');
            if (reportForm) {
                reportForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    generateReport();
                });
            }
            
            // Settings form
            const settingsForm = document.getElementById('settings-form');
            if (settingsForm) {
                settingsForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveSettings();
                });
            }
        }

        // Time-in hotel guest
        function timeInHotelGuest() {
            const form = document.getElementById('hotel-checkin-form');
            const formData = new FormData(form);
            
            const guest = {
                id: Date.now(),
                name: formData.get('guest-name'),
                email: formData.get('guest-email'),
                phone: formData.get('guest-phone'),
                room: formData.get('room-number'),
                checkin: formData.get('checkin-date'),
                checkout: formData.get('checkout-date'),
                notes: formData.get('guest-notes'),
                status: 'timed-in',
                checkinTime: new Date().toISOString(),
                checkoutTime: null
            };
            
            hotelVisitors.push(guest);
            localStorage.setItem('hotelVisitors', JSON.stringify(hotelVisitors));
            
            // Show success message
            showAlert('Guest time-in recorded successfully!', 'success');
            
            // Reset form
            form.reset();
            
            // Update dashboard and tables
            updateDashboard();
            loadCurrentVisitors();
        }

        // Time-in restaurant visitor
        function timeInRestaurantVisitor() {
            const form = document.getElementById('restaurant-checkin-form');
            const formData = new FormData(form);
            
            const visitor = {
                id: Date.now(),
                name: formData.get('visitor-name'),
                phone: formData.get('visitor-phone'),
                partySize: parseInt(formData.get('party-size')),
                table: formData.get('table-number'),
                notes: formData.get('restaurant-notes'),
                status: 'timed-in',
                checkinTime: new Date().toISOString(),
                checkoutTime: null
            };
            
            restaurantVisitors.push(visitor);
            localStorage.setItem('restaurantVisitors', JSON.stringify(restaurantVisitors));
            
            // Show success message
            showAlert('Visitor time-in recorded successfully!', 'success');
            
            // Reset form
            form.reset();
            
            // Update dashboard and tables
            updateDashboard();
            loadCurrentVisitors();
        }

        // Time-out hotel guest
        function timeOutHotelGuest(guestId) {
            const guest = hotelVisitors.find(g => g.id === guestId);
            if (guest) {
                guest.status = 'timed-out';
                guest.checkoutTime = new Date().toISOString();
                localStorage.setItem('hotelVisitors', JSON.stringify(hotelVisitors));
                
                showAlert('Guest time-out recorded successfully!', 'success');
                updateDashboard();
                loadCurrentVisitors();
            }
        }

        // Time-out restaurant visitor
        function timeOutRestaurantVisitor(visitorId) {
            const visitor = restaurantVisitors.find(v => v.id === visitorId);
            if (visitor) {
                visitor.status = 'timed-out';
                visitor.checkoutTime = new Date().toISOString();
                localStorage.setItem('restaurantVisitors', JSON.stringify(restaurantVisitors));
                
                showAlert('Visitor time-out recorded successfully!', 'success');
                updateDashboard();
                loadCurrentVisitors();
            }
        }

        // Update dashboard statistics
        function updateDashboard() {
            const today = new Date().toDateString();
            
            // Hotel statistics
            const hotelToday = hotelVisitors.filter(guest => {
                const checkinDate = new Date(guest.checkinTime).toDateString();
                return checkinDate === today;
            }).length;
            
            const hotelCurrent = hotelVisitors.filter(guest => guest.status === 'timed-in').length;
            
            // Restaurant statistics
            const restaurantToday = restaurantVisitors.filter(visitor => {
                const checkinDate = new Date(visitor.checkinTime).toDateString();
                return checkinDate === today;
            }).length;
            
            const restaurantCurrent = restaurantVisitors.filter(visitor => visitor.status === 'timed-in').length;
            
            // Update DOM
            document.getElementById('hotel-today').textContent = hotelToday;
            document.getElementById('hotel-current').textContent = hotelCurrent;
            document.getElementById('restaurant-today').textContent = restaurantToday;
            document.getElementById('restaurant-current').textContent = restaurantCurrent;
            
            // Update recent activity
            updateRecentActivity();
        }

        // Update recent activity list
        function updateRecentActivity() {
            const activityContainer = document.getElementById('recent-activity');
            if (!activityContainer) return;
            
            // Combine recent activities from both hotel and restaurant
            const allActivities = [
                ...hotelVisitors.map(guest => ({
                    type: 'hotel',
                    name: guest.name,
                    action: guest.status === 'timed-in' ? 'time-in' : 'time-out',
                    time: guest.status === 'timed-in' ? guest.checkinTime : guest.checkoutTime,
                    details: `Room ${guest.room}`
                })),
                ...restaurantVisitors.map(visitor => ({
                    type: 'restaurant',
                    name: visitor.name,
                    action: visitor.status === 'timed-in' ? 'time-in' : 'time-out',
                    time: visitor.status === 'timed-in' ? visitor.checkinTime : visitor.checkoutTime,
                    details: `Table ${visitor.table}, Party of ${visitor.partySize}`
                }))
            ];
            
            // Sort by time (newest first)
            allActivities.sort((a, b) => new Date(b.time) - new Date(a.time));
            
            // Get top 5
            const recentActivities = allActivities.slice(0, 5);
            
            // Update DOM
            activityContainer.innerHTML = '';
            if (recentActivities.length === 0) {
                activityContainer.innerHTML = '<p>No recent activity</p>';
                return;
            }
            
            recentActivities.forEach(activity => {
                const activityEl = document.createElement('div');
                activityEl.className = 'activity-item';
                activityEl.innerHTML = `
                    <strong>${activity.name}</strong> ${activity.action} at the ${activity.type}
                    <br><small>${activity.details} • ${formatTime(activity.time)}</small>
                    <hr>
                `;
                activityContainer.appendChild(activityEl);
            });
        }

        // Load current visitors into tables
        function loadCurrentVisitors() {
            // Hotel current visitors
            const hotelCurrentTable = document.getElementById('hotel-current-table');
            if (hotelCurrentTable) {
                const tbody = hotelCurrentTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                const currentGuests = hotelVisitors.filter(guest => guest.status === 'checked-in');
                
                if (currentGuests.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No current guests</td></tr>';
                } else {
                    currentGuests.forEach(guest => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${guest.name}</td>
                            <td>${guest.room}</td>
                            <td>${formatDate(guest.checkin)}</td>
                            <td>${formatDate(guest.checkout)}</td>
                            <td>
                                <button class="btn-warning" onclick="timeOutHotelGuest(${guest.id})">Time-out</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            }
            
            // Restaurant current visitors
            const restaurantCurrentTable = document.getElementById('restaurant-current-table');
            if (restaurantCurrentTable) {
                const tbody = restaurantCurrentTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                const currentVisitors = restaurantVisitors.filter(visitor => visitor.status === 'checked-in');
                
                if (currentVisitors.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No current visitors</td></tr>';
                } else {
                    currentVisitors.forEach(visitor => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${visitor.name}</td>
                            <td>${visitor.partySize}</td>
                            <td>${visitor.table}</td>
                            <td>${formatTime(visitor.checkinTime)}</td>
                            <td>
                                <button class="btn-warning" onclick="timeOutRestaurantVisitor(${visitor.id})">Time-out</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            }
            
            // Hotel history
            const hotelHistoryTable = document.getElementById('hotel-history-table');
            if (hotelHistoryTable) {
                const tbody = hotelHistoryTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                // For demo, show all guests
                hotelVisitors.forEach(guest => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${guest.name}</td>
                        <td>${guest.room}</td>
                        <td>${formatDate(guest.checkin)}</td>
                        <td>${formatDate(guest.checkout)}</td>
                        <td><span class="status-badge status-${guest.status.replace('-', '')}">${guest.status}</span></td>
                    `;
                    tbody.appendChild(row);
                });
            }
            
            // Restaurant history
            const restaurantHistoryTable = document.getElementById('restaurant-history-table');
            if (restaurantHistoryTable) {
                const tbody = restaurantHistoryTable.querySelector('tbody');
                tbody.innerHTML = '';
                
                // For demo, show all visitors
                restaurantVisitors.forEach(visitor => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${visitor.name}</td>
                        <td>${visitor.partySize}</td>
                        <td>${visitor.table}</td>
                        <td>${formatTime(visitor.checkinTime)}</td>
                        <td>${visitor.checkoutTime ? formatTime(visitor.checkoutTime) : 'N/A'}</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        }

        // Generate reports
        function generateReport() {
            const reportType = document.getElementById('report-type').value;
            const venue = document.getElementById('report-venue').value;
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            let reportData = '';
            
            // Calculate date range based on report type
            let dateRange = { start: new Date(), end: new Date() };
            
            switch (reportType) {
                case 'daily':
                    dateRange.start.setHours(0, 0, 0, 0);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
                case 'weekly':
                    // Start of week (Sunday)
                    dateRange.start.setDate(dateRange.start.getDate() - dateRange.start.getDay());
                    dateRange.start.setHours(0, 0, 0, 0);
                    // End of week (Saturday)
                    dateRange.end.setDate(dateRange.start.getDate() + 6);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
                case 'monthly':
                    // Start of month
                    dateRange.start.setDate(1);
                    dateRange.start.setHours(0, 0, 0, 0);
                    // End of month
                    dateRange.end.setMonth(dateRange.end.getMonth() + 1, 0);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
                case 'custom':
                    dateRange.start = new Date(startDate);
                    dateRange.end = new Date(endDate);
                    dateRange.end.setHours(23, 59, 59, 999);
                    break;
            }
            
            // Filter data based on venue and date range
            let hotelData = [];
            let restaurantData = [];
            
            if (venue === 'all' || venue === 'hotel') {
                hotelData = hotelVisitors.filter(guest => {
                    const checkinDate = new Date(guest.checkinTime);
                    return checkinDate >= dateRange.start && checkinDate <= dateRange.end;
                });
            }
            
            if (venue === 'all' || venue === 'restaurant') {
                restaurantData = restaurantVisitors.filter(visitor => {
                    const checkinDate = new Date(visitor.checkinTime);
                    return checkinDate >= dateRange.start && checkinDate <= dateRange.end;
                });
            }
            
            // Generate report content
            reportData += `<h3>Report for ${formatDate(dateRange.start)} to ${formatDate(dateRange.end)}</h3>`;
            
            if (venue === 'all' || venue === 'hotel') {
                reportData += `<h4>Hotel Statistics</h4>`;
                reportData += `<p>Total Guests: ${hotelData.length}</p>`;
                reportData += `<p>Currently Time-in: ${hotelData.filter(g => g.status === 'timed-in').length}</p>`;
                
                if (hotelData.length > 0) {
                    reportData += `<table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    
                    hotelData.forEach(guest => {
                        reportData += `
                            <tr>
                                <td>${guest.name}</td>
                                <td>${guest.room}</td>
                                <td>${formatDate(guest.checkin)}</td>
                                <td>${formatDate(guest.checkout)}</td>
                                <td>${guest.status}</td>
                            </tr>`;
                    });
                    
                    reportData += `</tbody></table>`;
                }
            }
            
            if (venue === 'all' || venue === 'restaurant') {
                reportData += `<h4>Restaurant Statistics</h4>`;
                reportData += `<p>Total Visitors: ${restaurantData.length}</p>`;
                reportData += `<p>Total Covers: ${restaurantData.reduce((sum, visitor) => sum + visitor.partySize, 0)}</p>`;
                reportData += `<p>Currently Dining (Time-in): ${restaurantData.filter(v => v.status === 'timed-in').length}</p>`;
                
                if (restaurantData.length > 0) {
                    reportData += `<table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Party Size</th>
                                <th>Table</th>
                                <th>Time-in</th>
                                <th>Time-out</th>
                            </tr>
                        </thead>
                        <tbody>`;
                    
                    restaurantData.forEach(visitor => {
                        reportData += `
                            <tr>
                                <td>${visitor.name}</td>
                                <td>${visitor.partySize}</td>
                                <td>${visitor.table}</td>
                                <td>${formatTime(visitor.checkinTime)}</td>
                                <td>${visitor.checkoutTime ? formatTime(visitor.checkoutTime) : 'N/A'}</td>
                            </tr>`;
                    });
                    
                    reportData += `</tbody></table>`;
                }
            }
            
            // Display report
            document.getElementById('report-data').innerHTML = reportData;
            document.getElementById('report-results').style.display = 'block';
        }

        // Save settings
        function saveSettings() {
            const form = document.getElementById('settings-form');
            const formData = new FormData(form);
            
            settings.businessName = formData.get('business-name');
            settings.timezone = formData.get('timezone');
            settings.dataRetention = parseInt(formData.get('data-retention'));
            
            localStorage.setItem('visitorSettings', JSON.stringify(settings));
            
            showAlert('Settings saved successfully!', 'success');
            applySettings();
        }

        // Apply settings
        function applySettings() {
            // Update business name in UI if needed
            const businessNameEl = document.querySelector('.logo');
            if (businessNameEl && settings.businessName) {
                businessNameEl.textContent = `${settings.businessName} Visitor Management`;
            }
            
            // Populate settings form
            const businessNameInput = document.getElementById('business-name');
            const timezoneSelect = document.getElementById('timezone');
            const dataRetentionInput = document.getElementById('data-retention');
            
            if (businessNameInput) businessNameInput.value = settings.businessName;
            if (timezoneSelect) timezoneSelect.value = settings.timezone;
            if (dataRetentionInput) dataRetentionInput.value = settings.dataRetention;
        }

        // Utility functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        }

        function formatTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            
            // Insert at the top of the current page
            const currentPage = document.querySelector('.page.active');
            if (currentPage) {
                currentPage.insertBefore(alert, currentPage.firstChild);
            }
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
        }