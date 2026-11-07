<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'discord_tickets');

// إعدادات Discord
define('DISCORD_CLIENT_ID', 'your_client_id');
define('DISCORD_CLIENT_SECRET', 'your_client_secret');
define('DISCORD_REDIRECT_URI', 'https://yourwebsite.com/callback.php');
define('DISCORD_BOT_TOKEN', 'your_bot_token');

// إعدادات الجلسة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// الاتصال بقاعدة البيانات
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        die("❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// التحقق من تسجيل الدخول
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// التحقق من الصلاحيات
function is_admin($user_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT role FROM users WHERE discord_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return isset($user['role']) && $user['role'] == 'admin';
}

// جلب إحصائيات التذاكر
function get_ticket_stats() {
    $conn = get_db_connection();
    
    $stats = [
        'total' => 0,
        'open' => 0,
        'closed' => 0,
        'escalated' => 0,
        'pending' => 0
    ];
    
    // إجمالي التذاكر
    $result = $conn->query("SELECT COUNT(*) as total FROM tickets");
    if ($row = $result->fetch_assoc()) {
        $stats['total'] = $row['total'];
    }
    
    // التذاكر المفتوحة
    $result = $conn->query("SELECT COUNT(*) as open FROM tickets WHERE status = 'open'");
    if ($row = $result->fetch_assoc()) {
        $stats['open'] = $row['open'];
    }
    
    // التذاكر المغلقة
    $result = $conn->query("SELECT COUNT(*) as closed FROM tickets WHERE status = 'closed'");
    if ($row = $result->fetch_assoc()) {
        $stats['closed'] = $row['closed'];
    }
    
    // التذاكر المرفوعة
    $result = $conn->query("SELECT COUNT(*) as escalated FROM tickets WHERE status = 'escalated'");
    if ($row = $result->fetch_assoc()) {
        $stats['escalated'] = $row['escalated'];
    }
    
    // التذاكر المعلقة
    $result = $conn->query("SELECT COUNT(*) as pending FROM tickets WHERE status = 'pending'");
    if ($row = $result->fetch_assoc()) {
        $stats['pending'] = $row['pending'];
    }
    
    $conn->close();
    return $stats;
}

// جلب تذاكر المستخدم
function get_user_tickets($user_id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("
        SELECT * FROM tickets 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tickets = [];
    
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $tickets;
}

// الحصول على اسم القسم
function get_category_name($category) {
    $categories = [
        'technical' => 'الدعم الفني',
        'billing' => 'الفواتير',
        'general' => 'عام',
        'bug' => 'مشكلة',
        'suggestion' => 'مقترح'
    ];
    return $categories[$category] ?? $category;
}

// الحصول على اسم الأولوية
function get_priority_name($priority) {
    $priorities = [
        'low' => 'منخفضة',
        'medium' => 'متوسطة',
        'high' => 'عالية',
        'urgent' => 'عاجلة'
    ];
    return $priorities[$priority] ?? $priority;
}

// الحصول على اسم الحالة
function get_status_name($status) {
    $statuses = [
        'open' => 'مفتوحة',
        'closed' => 'مغلقة',
        'escalated' => 'مرفوعة',
        'pending' => 'معلقة'
    ];
    return $statuses[$status] ?? $status;
}

// تسجيل الأخطاء
function log_error($message) {
    $log_file = 'logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
?>