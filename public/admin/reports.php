<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$user = $auth->getCurrentUser();

// Get date range
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get statistics
$stats = [];

// Total residents
$stats['total_residents'] = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident'")->fetch_assoc()['count'];

// New residents this month
$stats['new_residents'] = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident' AND MONTH(created_at) = MONTH(CURRENT_DATE())")->fetch_assoc()['count'];

// Total requests
$stats['total_requests'] = $db->query("SELECT COUNT(*) as count FROM service_requests")->fetch_assoc()['count'];

// Requests by status
$stats['pending_requests'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='pending'")->fetch_assoc()['count'];
$stats['in_progress_requests'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='in_progress'")->fetch_assoc()['count'];
$stats['completed_requests'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='completed'")->fetch_assoc()['count'];
$stats['rejected_requests'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='rejected'")->fetch_assoc()['count'];

// Requests by type
$requests_by_type = $db->query("SELECT request_type, COUNT(*) as count FROM service_requests GROUP BY request_type");

// Monthly requests
$monthly_requests = $db->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM service_requests GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month DESC LIMIT 6");

// Get data for charts
$chart_labels = [];
$chart_data = [];

$monthly_data = $db->query("SELECT DATE_FORMAT(created_at, '%M') as month, COUNT(*) as count FROM service_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY created_at ASC");
while($row = $monthly_data->fetch_assoc()) {
    $chart_labels[] = $row['month'];
    $chart_data[] = $row['count'];
}

// Get status distribution
$status_labels = [];
$status_data = [];
$status_colors = [];

$status_result = $db->query("SELECT status, COUNT(*) as count FROM service_requests GROUP BY status");
while($row = $status_result->fetch_assoc()) {
    $status_labels[] = ucfirst($row['status']);
    $status_data[] = $row['count'];
    switch($row['status']) {
        case 'pending':
            $status_colors[] = '#ffc107';
            break;
        case 'in_progress':
            $status_colors[] = '#17a2b8';
            break;
        case 'completed':
            $status_colors[] = '#28a745';
            break;
        case 'rejected':
            $status_colors[] = '#dc3545';
            break;
        default:
            $status_colors[] = '#6c757d';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BarangayLink Admin</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Reports Specific Styles */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin: 20px 0;
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .chart-card h3 {
            margin: 0 0 20px;
            color: var(--primary-color);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-card h3 i {
            font-size: 1.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2c3e50 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .stat-card h3 {
            margin: 0 0 10px;
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .stat-card .stat-number {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card small {
            opacity: 0.8;
            font-size: 0.85rem;
        }
        
        .stat-card:nth-child(1) { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card:nth-child(4) { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card:nth-child(5) { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-card:nth-child(6) { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        
        .export-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .export-btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 10px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .export-btn.csv { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .export-btn.pdf { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .export-btn.print { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
        .export-btn.excel { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 500;
            color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .summary-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .summary-table td:first-child {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .summary-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .chart-container {
                page-break-inside: avoid;
            }
            
            .stats-grid {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Side Navigation (Admin Version) -->
    <div class="sidenav admin-sidenav no-print">
        <div class="sidenav-header">
            <div class="sidenav-logo">
                <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
            </div>
            <h2>BarangayLink</h2>
            <p>Admin Portal</p>
        </div>
        
        <div class="sidenav-user">
            <div class="sidenav-avatar admin-avatar">
                <?php 
                $initial = strtoupper(substr($user['full_name'], 0, 1));
                echo $initial;
                ?>
            </div>
            <div class="sidenav-user-info">
                <div class="sidenav-user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="sidenav-user-type admin-badge">Administrator</div>
            </div>
        </div>
        
        <ul class="sidenav-menu">
            <li class="sidenav-item">
                <a href="dashboard.php">
                    <span class="sidenav-icon">📊</span>
                    <span class="sidenav-text">Dashboard</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="announcements.php">
                    <span class="sidenav-icon">📢</span>
                    <span class="sidenav-text">Announcements</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="service_requests.php">
                    <span class="sidenav-icon">📋</span>
                    <span class="sidenav-text">Service Requests</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="residents.php">
                    <span class="sidenav-icon">👥</span>
                    <span class="sidenav-text">Residents</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="reports.php" class="active">
                    <span class="sidenav-icon">📈</span>
                    <span class="sidenav-text">Reports</span>
                </a>
            </li>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
<li class="sidenav-item">
   <a href="registration_codes.php">
      <span class="sidenav-icon">🔑</span>
      <span class="sidenav-text">Registration Codes</span>
   </a>
</li>
<?php endif; ?>
            <li class="sidenav-item">
                <a href="profile.php">
                    <span class="sidenav-icon">👤</span>
                    <span class="sidenav-text">Profile</span>
                </a>
            </li>
            <li class="sidenav-divider"></li>
            <li class="sidenav-item">
                <a href="../logout.php" class="logout-link">
                    <span class="sidenav-icon">🚪</span>
                    <span class="sidenav-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Top Navbar (Mobile Only) -->
    <nav class="navbar mobile-only no-print">
        <div class="navbar-brand">
            <a href="dashboard.php">
                <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink" style="height: 30px; vertical-align: middle;">
                BarangayLink Admin
            </a>
        </div>
        <div class="user-info">
            <span class="user-avatar"><?php echo $initial; ?></span>
            <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
        <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
    </nav>
    
    <div class="main-content">
        <div class="container">
            <h1 class="no-print">Reports & Analytics</h1>
            
            <!-- Date Filter Card -->
            <div class="filter-card no-print">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--primary-color);">📅 Select Date Range</h3>
                <form method="GET" action="" id="reportForm" class="filter-form">
                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Residents</h3>
                    <div class="stat-number"><?php echo $stats['total_residents']; ?></div>
                    <small>+<?php echo $stats['new_residents']; ?> this month</small>
                </div>
                <div class="stat-card">
                    <h3>Total Requests</h3>
                    <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending</h3>
                    <div class="stat-number"><?php echo $stats['pending_requests']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>In Progress</h3>
                    <div class="stat-number"><?php echo $stats['in_progress_requests']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Completed</h3>
                    <div class="stat-number"><?php echo $stats['completed_requests']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Rejected</h3>
                    <div class="stat-number"><?php echo $stats['rejected_requests']; ?></div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="row">
                <div class="col">
                    <div class="chart-card">
                        <h3><i>📊</i> Monthly Request Trends</h3>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="chart-card">
                        <h3><i>🥧</i> Request Status Distribution</h3>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Data Tables -->
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3>📋 Requests by Type</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table sortable" id="requestsByTypeTable">
                                    <thead>
                                        <tr>
                                            <th>Request Type</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total = $stats['total_requests'];
                                        $requests_by_type->data_seek(0);
                                        while($row = $requests_by_type->fetch_assoc()): 
                                        $percentage = $total > 0 ? round(($row['count'] / $total) * 100, 2) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                                            <td><?php echo $row['count']; ?></td>
                                            <td><?php echo $percentage; ?>%</td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3>📅 Monthly Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table sortable" id="monthlyRequestsTable">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Requests</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $monthly_requests->data_seek(0);
                                        while($row = $monthly_requests->fetch_assoc()): 
                                        ?>
                                        <tr>
                                            <td><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                                            <td><?php echo $row['count']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Export Buttons -->
            <div class="card no-print">
                <div class="card-header">
                    <h3>📥 Export Reports</h3>
                </div>
                <div class="card-body">
                    <div class="export-buttons">
                        <a href="export_residents.php" class="export-btn csv">
                            📊 Export Residents List (CSV)
                        </a>
                        <a href="export_requests.php" class="export-btn csv">
                            📋 Export Service Requests (CSV)
                        </a>
                        <a href="print_report.php?from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>" class="export-btn print" target="_blank">
                            🖨️ View/Print Full Report
                        </a>
                        <button onclick="exportAllData()" class="export-btn excel">
                            📑 Export All Data (CSV)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
    
    <script>
    // Initialize charts
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Trends Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Number of Requests',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#764ba2',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_data); ?>,
                    backgroundColor: <?php echo json_encode($status_colors); ?>,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    });
    
    // Export all data function
    function exportAllData() {
        // Get all tables
        const tables = ['requestsByTypeTable', 'monthlyRequestsTable'];
        let csvContent = "BarangayLink Complete Report\n";
        csvContent += "Generated on: " + new Date().toLocaleString() + "\n\n";
        
        tables.forEach(tableId => {
            const table = document.getElementById(tableId);
            if (table) {
                csvContent += "\n" + tableId.replace(/([A-Z])/g, ' $1').trim() + "\n";
                csvContent += "----------------------------------------\n";
                
                const rows = table.querySelectorAll('tr');
                rows.forEach(row => {
                    const cols = row.querySelectorAll('td, th');
                    const rowData = [];
                    cols.forEach(col => rowData.push('"' + col.textContent.trim() + '"'));
                    csvContent += rowData.join(',') + '\n';
                });
            }
        });
        
        // Add statistics
        csvContent += "\nSummary Statistics\n";
        csvContent += "----------------------------------------\n";
        csvContent += `Total Residents,<?php echo $stats['total_residents']; ?>\n`;
        csvContent += `New Residents This Month,<?php echo $stats['new_residents']; ?>\n`;
        csvContent += `Total Requests,<?php echo $stats['total_requests']; ?>\n`;
        csvContent += `Pending Requests,<?php echo $stats['pending_requests']; ?>\n`;
        csvContent += `In_progress Requests,<?php echo $stats['in_progress_requests']; ?>\n`;
        csvContent += `Completed Requests,<?php echo $stats['completed_requests']; ?>\n`;
        csvContent += `Rejected Requests,<?php echo $stats['rejected_requests']; ?>\n`;
        
        // Download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'barangaylink_complete_report.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Print full report
    function printFullReport() {
        window.open('print_report.php?from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>', '_blank');
    }
    </script>
</body>
</html>