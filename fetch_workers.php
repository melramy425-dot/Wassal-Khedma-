<?php
// نفترض صفحة مستقلة لا تحتاج جلسة هنا
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['skill']) || empty(trim($_GET['skill']))) {
    echo json_encode([]);
    exit;
}

$skill = trim($_GET['skill']);

$host = 'localhost';
$db   = 'wassal_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->prepare("SELECT id, name, location FROM workers WHERE skill = ? ORDER BY name ASC");
    $stmt->execute([$skill]);
    $workers = $stmt->fetchAll();

    echo json_encode($workers);

} catch (PDOException $e) {
    // في حالة الخطأ، نرجع مصفوفة فارغة
    echo json_encode([]);
}
