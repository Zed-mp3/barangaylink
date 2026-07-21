<?php
// C:\xampp\htdocs\barangaylink\public\user\check_announcements_updates.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

try {
    $auth = requireAjaxAuth('user');
    $db = new Database();
    
    // Get the last check timestamp - support both 'last' and 'since' parameters
    $last_check = isset($_GET['last']) ? (int)$_GET['last'] : 0;
    if ($last_check == 0 && isset($_GET['since'])) {
        $last_check = (int)$_GET['since'];
    }
    
    // If no timestamp provided, use last 1 hour as fallback
    if ($last_check == 0) {
        $last_check = time() - 3600;
    }
    
    // Check for new announcements using timestamp comparison
    $sql = "SELECT 
                COUNT(*) as count,
                MAX(UNIX_TIMESTAMP(created_at)) as last_update
            FROM announcements 
            WHERE UNIX_TIMESTAMP(created_at) > ?";
    
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $last_check);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $count = (int)($row['count'] ?? 0);
        $last_update = (int)($row['last_update'] ?? $last_check);
        
        // Get the actual announcements if there are new ones
        $announcements = [];
        if ($count > 0) {
            $details_sql = "SELECT 
                                a.*,
                                UNIX_TIMESTAMP(a.created_at) as timestamp,
                                u.full_name as author_name
                            FROM announcements a
                            LEFT JOIN users u ON a.created_by = u.id
                            WHERE UNIX_TIMESTAMP(a.created_at) > ?
                            ORDER BY a.created_at DESC
                            LIMIT 10";
            
            $details_stmt = $db->prepare($details_sql);
            if ($details_stmt) {
                $details_stmt->bind_param("i", $last_check);
                $details_stmt->execute();
                $details_result = $details_stmt->get_result();
                
                while ($row = $details_result->fetch_assoc()) {
                    $announcements[] = $row;
                }
                $details_stmt->close();
            }
        }
        
        echo json_encode([
            'success' => true,
            'hasUpdates' => $count > 0,
            'count' => $count,
            'lastUpdate' => $last_update,
            'announcements' => $announcements
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Database prepare failed'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>