<?php
session_start();

// تحقق تسجيل الدخول (اختياري لو الصفحة خاصة بالعملاء فقط)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit();
}

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
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// استلام معايير البحث من النموذج
$service = $_GET['service'] ?? '';
$location = $_GET['location'] ?? '';
$min_experience = isset($_GET['min_experience']) ? (int)$_GET['min_experience'] : 0;

// بناء استعلام البحث مع المعايير المختارة
$sql = "SELECT * FROM workers WHERE 1=1 ";
$params = [];

if ($service !== '') {
    $sql .= "AND service LIKE ? ";
    $params[] = "%$service%";
}

if ($location !== '') {
    $sql .= "AND location LIKE ? ";
    $params[] = "%$location%";
}

if ($min_experience > 0) {
    $sql .= "AND experience >= ? ";
    $params[] = $min_experience;
}

$sql .= "AND availability = 'متاح' ORDER BY experience DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workers = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>البحث عن عمال - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f9;
    margin: 0; padding: 20px;
  }
  .container {
    max-width: 900px;
    margin: auto;
    background: white;
    padding: 25px 30px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
  }
  h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
  }
  form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 30px;
  }
  input[type="text"], select {
    padding: 12px 15px;
    border-radius: 8px;
    border: 1.5px solid #ccc;
    font-size: 16px;
    width: 220px;
    transition: border-color 0.3s;
  }
  input[type="text"]:focus, select:focus {
    border-color: #1abc9c;
    outline: none;
  }
  button {
    background-color: #1abc9c;
    color: white;
    border: none;
    padding: 14px 30px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
  }
  button:hover {
    background-color: #16a085;
  }
  .worker-card {
    border: 1.5px solid #ddd;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 18px;
    box-shadow: 0 0 7px rgba(0,0,0,0.05);
    transition: box-shadow 0.3s;
  }
  .worker-card:hover {
    box-shadow: 0 0 15px rgba(26, 188, 156, 0.3);
  }
  .worker-name {
    font-size: 22px;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 6px;
  }
  .worker-details {
    color: #555;
    margin-bottom: 8px;
  }
  .no-results {
    text-align: center;
    font-size: 20px;
    color: #888;
    margin-top: 40px;
  }
</style>
</head>
<body>

<div class="container" role="main" aria-label="البحث عن العمال">
  <h1>ابحث عن عامل مناسب لك</h1>

  <form method="GET" action="find_worker.php" aria-label="نموذج البحث عن عامل">
    <input type="text" name="service" placeholder="نوع الخدمة" value="<?= htmlspecialchars($service) ?>" aria-label="نوع الخدمة" />
    <input type="text" name="location" placeholder="الموقع" value="<?= htmlspecialchars($location) ?>" aria-label="الموقع" />
    <select name="min_experience" aria-label="الخبرة الدنيا">
      <option value="0" <?= $min_experience === 0 ? 'selected' : '' ?>>الخبرة: كل المستويات</option>
      <option value="1" <?= $min_experience === 1 ? 'selected' : '' ?>>1 سنة فأكثر</option>
      <option value="2" <?= $min_experience === 2 ? 'selected' : '' ?>>2 سنة فأكثر</option>
      <option value="3" <?= $min_experience === 3 ? 'selected' : '' ?>>3 سنوات فأكثر</option>
      <option value="5" <?= $min_experience === 5 ? 'selected' : '' ?>>5 سنوات فأكثر</option>
    </select>
    <button type="submit">ابحث</button>
  </form>

  <?php if (count($workers) === 0): ?>
    <p class="no-results">لم يتم العثور على عمال حسب معايير البحث.</p>
  <?php else: ?>
    <?php foreach ($workers as $worker): ?>
      <div class="worker-card" tabindex="0" aria-label="معلومات العامل <?= htmlspecialchars($worker['name']) ?>">
        <div class="worker-name"><?= htmlspecialchars($worker['name']) ?></div>
        <div class="worker-details"><strong>الخدمة:</strong> <?= htmlspecialchars($worker['service']) ?></div>
        <div class="worker-details"><strong>الموقع:</strong> <?= htmlspecialchars($worker['location']) ?></div>
        <div class="worker-details"><strong>الخبرة:</strong> <?= (int)$worker['experience'] ?> سنة</div>
        <div class="worker-details"><strong>التوفر:</strong> <?= htmlspecialchars($worker['availability']) ?></div>
        <a href="request.php?worker_id=<?= (int)$worker['id'] ?>" style="color:#1abc9c; font-weight:bold; text-decoration:none;">طلب الخدمة</a>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>
<?php include 'footer.php'; ?>

</body>
</html>
