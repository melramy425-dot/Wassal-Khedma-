<?php
session_start();

// التحقق إذا كان المستخدم مسجل دخول أصلاً
if (!isset($_SESSION['user_id'])) {
    // إذا مش مسجل، وجهه مباشرة لصفحة تسجيل الدخول
    header("Location: login.php");
    exit();
}

// ——————————————————————
// تسجيل حدث الخروج (اختياري)
// لو حابب تخزن معلومات عن الخروج مثلاً في جدول في قاعدة البيانات
try {
    $host = 'localhost';
    $db = 'wassal_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

    // سجّل وقت الخروج للمستخدم
    $stmt = $pdo->prepare("INSERT INTO user_logouts (user_id, logout_time, user_ip) VALUES (?, NOW(), ?)");
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt->execute([$_SESSION['user_id'], $user_ip]);

} catch (PDOException $e) {
    // لو فيه مشكلة في الاتصال بقاعدة البيانات، ممكن تكتبها في سجل الأخطاء بدون التأثير على الخروج
    error_log("Logout DB Error: " . $e->getMessage());
}

// ——————————————————————
// تنظيف وإلغاء الجلسة

// حذف كل بيانات الجلسة
$_SESSION = [];

// حذف ملفات تعريف الارتباط الخاصة بالجلسة
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// إنهاء الجلسة
session_destroy();

// إعادة التوجيه لصفحة تسجيل الدخول مع رسالة خروج آمنة
header("Location: login.php?message=logout_success");
exit();
