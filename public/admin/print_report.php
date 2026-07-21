<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$user = $auth->getCurrentUser();

// Get date range from URL
$date_from = isset($_GET['from']) ? $db->escape($_GET['from']) : date('Y-m-01');
$date_to = isset($_GET['to']) ? $db->escape($_GET['to']) : date('Y-m-d');

// Get comprehensive statistics
$stats = [];

// Resident statistics
$stats['total_residents'] = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident'")->fetch_assoc()['count'];
$stats['new_residents'] = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
$stats['active_residents'] = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident' AND status='active'")->fetch_assoc()['count'];
$stats['inactive_residents'] = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident' AND status='inactive'")->fetch_assoc()['count'];

// Request statistics
$stats['total_requests'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
$stats['pending'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='pending' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
$stats['processing'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='processing' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
$stats['completed'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='completed' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];
$stats['cancelled'] = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='cancelled' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['count'];

// Get requests by type
$requests_by_type = $db->query("SELECT request_type, COUNT(*) as count FROM service_requests WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to' GROUP BY request_type");

// Get recent requests
$recent_requests = $db->query("SELECT sr.*, u.full_name, u.email, u.contact_number 
                               FROM service_requests sr 
                               JOIN users u ON sr.user_id = u.id 
                               WHERE DATE(sr.created_at) BETWEEN '$date_from' AND '$date_to' 
                               ORDER BY sr.created_at DESC");

// Get recent residents
$recent_residents = $db->query("SELECT * FROM users WHERE user_type='resident' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to' ORDER BY created_at DESC");

// Get announcements in date range
$announcements = $db->query("SELECT * FROM announcements WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to' ORDER BY created_at DESC");

// Get daily statistics for the period
$daily_stats = $db->query("SELECT DATE(created_at) as date, COUNT(*) as requests 
                          FROM service_requests 
                          WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to' 
                          GROUP BY DATE(created_at) 
                          ORDER BY date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarangayLink Complete Report - <?php echo date('F d, Y', strtotime($date_from)); ?> to <?php echo date('F d, Y', strtotime($date_to)); ?></title>
    <link rel="stylesheet" href="../../src/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        
        .print-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .print-btn.print {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }
        
        .print-btn.close {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .logo-section img {
            max-width: 80px;
            height: auto;
        }
        
        .report-header h1 {
            color: var(--primary-color);
            font-size: 2.2rem;
            margin: 10px 0;
        }
        
        .report-header h2 {
            color: #666;
            font-size: 1.3rem;
            font-weight: 400;
        }
        
        .report-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 0.95rem;
        }
        
        .section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-box h3 {
            font-size: 1rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-box .number {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-box .label {
            font-size: 0.85rem;
            color: #888;
        }
        
        .chart-container {
            height: 300px;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 0.9rem;
        }
        
        th {
            background: var(--primary-color);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-processing { background: #cce5ff; color: #004085; }
        .badge-completed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        
        .summary-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            font-size: 0.85rem;
            color: #888;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                padding: 0;
                background: white;
            }
            
            .report-container {
                max-width: 100%;
                padding: 0.5in;
            }
            
            .stat-box {
                break-inside: avoid;
            }
            
            .section {
                break-inside: avoid;
            }
            
            table {
                break-inside: auto;
            }
            
            tr {
                break-inside: avoid;
            }
            
            th {
                background: #333 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="print-btn print">🖨️ Print Report</button>
        <button onclick="window.close()" class="print-btn close">✖ Close</button>
    </div>
    
    <div class="report-container">
        <!-- Report Header -->
        <div class="report-header">
            <div class="logo-section">
                <img src="../../includes/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
                <h1>BarangayLink</h1>
            </div>
            <h1>Complete System Report</h1>
            <h2><?php echo date('F d, Y', strtotime($date_from)); ?> - <?php echo date('F d, Y', strtotime($date_to)); ?></h2>
        </div>
        
        <!-- Report Meta Information -->
        <div class="report-meta">
            <span><strong>Generated By:</strong> <?php echo htmlspecialchars($user['full_name']); ?> (Administrator)</span>
            <span><strong>Generated On:</strong> <?php echo date('F d, Y h:i A'); ?></span>
            <span><strong>Report ID:</strong> RPT-<?php echo date('YmdHis'); ?></span>
        </div>
        
        <!-- Executive Summary -->
        <div class="section">
            <div class="section-title">
                <span>📊</span> Executive Summary
            </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Total Residents</h3>
                    <div class="number"><?php echo $stats['total_residents']; ?></div>
                    <div class="label">+<?php echo $stats['new_residents']; ?> new this period</div>
                </div>
                <div class="stat-box">
                    <h3>Active Residents</h3>
                    <div class="number"><?php echo $stats['active_residents']; ?></div>
                    <div class="label"><?php echo $stats['inactive_residents']; ?> inactive</div>
                </div>
                <div class="stat-box">
                    <h3>Total Requests</h3>
                    <div class="number"><?php echo $stats['total_requests']; ?></div>
                    <div class="label">This period</div>
                </div>
                <div class="stat-box">
                    <h3>Completion Rate</h3>
                    <div class="number">
                        <?php 
                        $completion_rate = $stats['total_requests'] > 0 ? round(($stats['completed'] / $stats['total_requests']) * 100, 1) : 0;
                        echo $completion_rate . '%';
                        ?>
                    </div>
                    <div class="label"><?php echo $stats['completed']; ?> completed</div>
                </div>
            </div>
        </div>
        
        <!-- Request Statistics -->
        <div class="section">
            <div class="section-title">
                <span>📋</span> Request Statistics
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Pending</h3>
                    <div class="number"><?php echo $stats['pending']; ?></div>
                </div>
                <div class="stat-box">
                    <h3>Processing</h3>
                    <div class="number"><?php echo $stats['processing']; ?></div>
                </div>
                <div class="stat-box">
                    <h3>Completed</h3>
                    <div class="number"><?php echo $stats['completed']; ?></div>
                </div>
                <div class="stat-box">
                    <h3>Cancelled</h3>
                    <div class="number"><?php echo $stats['cancelled']; ?></div>
                </div>
            </div>
            
            <!-- Requests by Type Table -->
            <h3 style="margin: 20px 0 10px;">Requests by Type</h3>
            <table>
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
        
        <!-- Daily Statistics -->
        <div class="section">
            <div class="section-title">
                <span>📅</span> Daily Activity Report
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Requests Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_requests_period = 0;
                    while($row = $daily_stats->fetch_assoc()): 
                    $total_requests_period += $row['requests'];
                    ?>
                    <tr>
                        <td><?php echo date('F d, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['requests']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <tr style="font-weight: bold; background: #e9ecef;">
                        <td>Total</td>
                        <td><?php echo $total_requests_period; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Service Requests -->
        <div class="section">
            <div class="section-title">
                <span>📝</span> Recent Service Requests
            </div>
            <?php if ($recent_requests->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Resident</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $recent_requests->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                        <td>
                            <?php
                            $statusClass = '';
                            switch($row['status']) {
                                case 'pending': $statusClass = 'badge-pending'; break;
                                case 'processing': $statusClass = 'badge-processing'; break;
                                case 'completed': $statusClass = 'badge-completed'; break;
                                case 'cancelled': $statusClass = 'badge-cancelled'; break;
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($row['status']); ?></span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td><?php echo $row['contact_number'] ?: 'N/A'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">No service requests in this date range.</p>
            <?php endif; ?>
        </div>
        
        <!-- New Residents -->
        <div class="section page-break">
            <div class="section-title">
                <span>👥</span> New Residents Registered
            </div>
            <?php if ($recent_residents->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $recent_residents->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo $row['contact_number'] ?: 'N/A'; ?></td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">No new residents in this date range.</p>
            <?php endif; ?>
        </div>
        
        <!-- Announcements -->
        <div class="section">
            <div class="section-title">
                <span>📢</span> Announcements Posted
            </div>
            <?php if ($announcements->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Date Posted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $announcements->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                        <td><?php echo substr(htmlspecialchars($row['content']), 0, 100); ?>...</td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">No announcements in this date range.</p>
            <?php endif; ?>
        </div>
        
        <!-- Summary Card -->
        <div class="summary-card">
            <h3 style="margin-bottom: 15px; color: var(--primary-color);">📊 Report Summary</h3>
            <div class="summary-row">
                <span>Reporting Period:</span>
                <span><?php echo date('F d, Y', strtotime($date_from)); ?> - <?php echo date('F d, Y', strtotime($date_to)); ?></span>
            </div>
            <div class="summary-row">
                <span>Total Days:</span>
                <span><?php echo ceil((strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24)) + 1; ?> days</span>
            </div>
            <div class="summary-row">
                <span>Average Requests Per Day:</span>
                <span><?php echo $total_requests_period > 0 ? round($total_requests_period / max(1, ceil((strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24) + 1)), 1) : 0; ?></span>
            </div>
            <div class="summary-row">
                <span>Generated By:</span>
                <span><?php echo htmlspecialchars($user['full_name']); ?> (Administrator)</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>BarangayLink Management System - Complete System Report</p>
            <p>This report is automatically generated and contains official data from the BarangayLink database.</p>
            <p style="margin-top: 10px;">Report ID: RPT-<?php echo date('YmdHis'); ?> | Generated: <?php echo date('F d, Y h:i A'); ?></p>
        </div>
    </div>
    
    <script src="../../src/js/main.js"></script>
</body>
</html>