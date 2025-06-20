<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Devices Monitoring</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.3.4/axios.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .controls {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        .online {
            color: #27ae60;
        }

        .offline {
            color: #e74c3c;
        }

        .total {
            color: #3498db;
        }

        .rate {
            color: #f39c12;
        }

        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .device-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .device-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .device-card.online::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
        }

        .device-card.offline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }

        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .device-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-online {
            background: #d5f4e6;
            color: #27ae60;
        }

        .status-offline {
            background: #fce4ec;
            color: #e74c3c;
        }

        .device-info {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #666;
        }

        .info-label {
            font-weight: 500;
        }

        .device-actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 20px;
        }

        .btn-check {
            background: linear-gradient(45deg, #3498db, #2980b9);
        }

        .btn-history {
            background: linear-gradient(45deg, #95a5a6, #7f8c8d);
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .last-updated {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-top: 20px;
        }

        .no-devices {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                text-align: center;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .devices-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🌐 Network Devices Monitor</h1>
            <p>Real-time monitoring of your network devices</p>
        </div>

        <div class="controls">
            <div>
                <button class="btn" id="refreshBtn" onclick="refreshAllDevices()">
                    <span id="refreshText">🔄 Refresh All</span>
                    <div class="loading" id="refreshLoading" style="display: none;"></div>
                </button>
            </div>

            <div class="auto-refresh">
                <span>Auto Refresh:</span>
                <label class="switch">
                    <input type="checkbox" id="autoRefreshToggle" onchange="toggleAutoRefresh()">
                    <span class="slider"></span>
                </label>
                <span id="autoRefreshStatus">Off</span>
            </div>
        </div>

        <div class="stats" id="statsContainer">
            <div class="stat-card">
                <div class="stat-number total" id="totalDevices">0</div>
                <div class="stat-label">Total Devices</div>
            </div>
            <div class="stat-card">
                <div class="stat-number online" id="onlineDevices">0</div>
                <div class="stat-label">Online</div>
            </div>
            <div class="stat-card">
                <div class="stat-number offline" id="offlineDevices">0</div>
                <div class="stat-label">Offline</div>
            </div>
            <div class="stat-card">
                <div class="stat-number rate" id="successRate">0%</div>
                <div class="stat-label">Success Rate</div>
            </div>
        </div>

        <div class="devices-grid" id="devicesContainer">
            <div class="no-devices">
                <div class="loading" style="margin: 0 auto 20px;"></div>
                Loading devices...
            </div>
        </div>

        <div class="last-updated" id="lastUpdated">
            Last updated: Never
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;
        let isRefreshing = false;

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDevices();
        });

        // Load devices data
        async function loadDevices() {
            try {
                const response = await axios.get('/api/devices/status');
                
                if (response.data.success) {
                    updateStats(response.data.summary);
                    renderDevices(response.data.devices);
                    updateLastUpdated(response.data.last_updated);
                } else {
                    showError('Failed to load devices data');
                }
            } catch (error) {
                console.error('Error loading devices:', error);
                showError('Error loading devices data');
            }
        }

        // Update statistics
        function updateStats(summary) {
            document.getElementById('totalDevices').textContent = summary.total;
            document.getElementById('onlineDevices').textContent = summary.online;
            document.getElementById('offlineDevices').textContent = summary.offline;
            document.getElementById('successRate').textContent = summary.success_rate + '%';
        }

        // Render devices
        function renderDevices(devices) {
            const container = document.getElementById('devicesContainer');
            
            if (devices.length === 0) {
                container.innerHTML = '<div class="no-devices">No devices found. Please add devices to monitor.</div>';
                return;
            }

            container.innerHTML = devices.map(device => `
                <div class="device-card ${device.is_online ? 'online' : 'offline'}">
                    <div class="device-header">
                        <div class="device-name">${device.name || 'Unknown Device'}</div>
                        <div class="status-badge ${device.is_online ? 'status-online' : 'status-offline'}">
                            ${device.is_online ? 'Online' : 'Offline'}
                        </div>
                    </div>
                    
                    <div class="device-info">
                        <div class="info-row">
                            <span class="info-label">IP Address:</span>
                            <span>${device.ip_address}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Seen:</span>
                            <span>${device.last_seen_at ? formatDate(device.last_seen_at) : 'Never'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Location:</span>
                            <span>${device.location || 'Unknown'}</span>
                        </div>
                        ${device.latest_status_log ? `
                        <div class="info-row">
                            <span class="info-label">Latency:</span>
                            <span>${device.latest_status_log.latency ? device.latest_status_log.latency + 'ms' : 'N/A'}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="device-actions">
                        <button class="btn btn-small btn-check" onclick="checkDevice(${device.id})">
                            Check Now
                        </button>
                        <button class="btn btn-small btn-history" onclick="showHistory(${device.id})">
                            History
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Refresh all devices
        async function refreshAllDevices() {
            if (isRefreshing) return;
            
            isRefreshing = true;
            const refreshBtn = document.getElementById('refreshBtn');
            const refreshText = document.getElementById('refreshText');
            const refreshLoading = document.getElementById('refreshLoading');
            
            refreshBtn.disabled = true;
            refreshText.style.display = 'none';
            refreshLoading.style.display = 'inline-block';

            try {
                const response = await axios.post('/api/devices/check-all');
                
                if (response.data.success) {
                    // Reload the devices data
                    await loadDevices();
                    showSuccess('All devices checked successfully!');
                } else {
                    showError(response.data.message || 'Failed to check devices');
                }
            } catch (error) {
                console.error('Error checking devices:', error);
                showError('Error checking devices');
            } finally {
                isRefreshing = false;
                refreshBtn.disabled = false;
                refreshText.style.display = 'inline';
                refreshLoading.style.display = 'none';
            }
        }

        // Check specific device
        async function checkDevice(deviceId) {
            try {
                const response = await axios.post(`/api/devices/${deviceId}/check`);
                
                if (response.data.success) {
                    // Reload devices to show updated status
                    await loadDevices();
                    showSuccess('Device checked successfully!');
                } else {
                    showError('Failed to check device');
                }
            } catch (error) {
                console.error('Error checking device:', error);
                showError('Error checking device');
            }
        }

        // Show device history (placeholder)
        function showHistory(deviceId) {
            // This would typically open a modal or navigate to a history page
            alert(`History for device ${deviceId} - Feature coming soon!`);
        }

        // Toggle auto refresh
        function toggleAutoRefresh() {
            const toggle = document.getElementById('autoRefreshToggle');
            const status = document.getElementById('autoRefreshStatus');
            
            if (toggle.checked) {
                // Enable auto refresh (every 30 seconds)
                autoRefreshInterval = setInterval(loadDevices, 30000);
                status.textContent = 'On (30s)';
                showSuccess('Auto refresh enabled (30 seconds)');
            } else {
                // Disable auto refresh
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
                status.textContent = 'Off';
                showSuccess('Auto refresh disabled');
            }
        }

        // Update last updated time
        function updateLastUpdated(timestamp) {
            const element = document.getElementById('lastUpdated');
            element.textContent = `Last updated: ${formatDate(timestamp)}`;
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }

        // Show success message
        function showSuccess(message) {
            // Simple alert for now - you can implement a toast notification system
            console.log('Success:', message);
        }

        // Show error message
        function showError(message) {
            // Simple alert for now - you can implement a toast notification system
            console.error('Error:', message);
            alert('Error: ' + message);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });
    </script>
</body>

</html>