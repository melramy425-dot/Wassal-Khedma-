<?php
session_start();

if (!isset($_SESSION['user_id'])) {
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

    // نستقبل id الطلب من GET لعرض موقع الطلب أو العامل/العميل
    if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
        throw new Exception('طلب غير صالح.');
    }
    $request_id = (int)$_GET['request_id'];

    // جلب بيانات الطلب وموقع العميل والعامل (يفترض وجود حقول lat, lng في الجداول)
    $stmt = $pdo->prepare("
        SELECT r.*, 
               c.name AS client_name, c.lat AS client_lat, c.lng AS client_lng, 
               w.name AS worker_name, w.lat AS worker_lat, w.lng AS worker_lng
        FROM requests r
        JOIN users c ON r.client_id = c.id
        JOIN workers w ON r.worker_id = w.id
        WHERE r.id = ?
    ");
    $stmt->execute([$request_id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('لم يتم العثور على الطلب.');
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>موقع الخدمة على الخريطة - Wassal Khedma</title>
<style>
  body, html {
    height: 100%;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f9;
  }
  #map {
    height: 80vh;
    width: 100%;
    max-width: 900px;
    margin: 20px auto;
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0,0,0,0.15);
  }
  .container {
    max-width: 900px;
    margin: 10px auto 30px;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    text-align: center;
  }
  h1 {
    color: #2c3e50;
    margin-bottom: 15px;
  }
  a.back-link {
    display: inline-block;
    margin-top: 15px;
    text-decoration: none;
    color: #1abc9c;
    font-weight: bold;
  }
  a.back-link:hover {
    text-decoration: underline;
  }
  .error-message {
    color: #e74c3c;
    font-weight: bold;
    margin: 30px;
    text-align: center;
  }
</style>

<!-- هنا تستبدل YOUR_API_KEY بمفتاح Google Maps API الخاص بك -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&language=ar"></script>

</head>
<body>

<div class="container" role="main" aria-label="موقع الطلب على الخريطة">
  <h1>موقع الخدمة على الخريطة</h1>

  <?php if (isset($error)): ?>
    <div class="error-message" role="alert"><?= htmlspecialchars($error) ?></div>
    <a href="dashboard.php" class="back-link">العودة إلى لوحة التحكم</a>
  <?php else: ?>
    <div id="map" aria-label="خريطة موقع الخدمة"></div>

    <a href="dashboard.php" class="back-link">العودة إلى لوحة التحكم</a>
  <?php endif; ?>
</div>

<?php if (!isset($error)): ?>
<script>
  function initMap() {
    const clientPos = { lat: parseFloat("<?= $order['client_lat'] ?>"), lng: parseFloat("<?= $order['client_lng'] ?>") };
    const workerPos = { lat: parseFloat("<?= $order['worker_lat'] ?>"), lng: parseFloat("<?= $order['worker_lng'] ?>") };

    // مركز الخريطة يكون بين العميل والعامل تقريبا
    const centerLat = (clientPos.lat + workerPos.lat) / 2;
    const centerLng = (clientPos.lng + workerPos.lng) / 2;

    const map = new google.maps.Map(document.getElementById('map'), {
      zoom: 12,
      center: { lat: centerLat, lng: centerLng },
      mapTypeId: 'roadmap',
      gestureHandling: 'greedy'
    });

    // علامات (Markers) للعميل والعامل
    const clientMarker = new google.maps.Marker({
      position: clientPos,
      map: map,
      title: "موقع العميل: <?= htmlspecialchars($order['client_name']) ?>",
      icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
    });

    const workerMarker = new google.maps.Marker({
      position: workerPos,
      map: map,
      title: "موقع العامل: <?= htmlspecialchars($order['worker_name']) ?>",
      icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png'
    });

    // نافذة معلومات عند الضغط على العلامات
    const clientInfo = new google.maps.InfoWindow({
      content: "<strong>العميل:</strong> <?= htmlspecialchars($order['client_name']) ?>"
    });
    clientMarker.addListener('click', () => clientInfo.open(map, clientMarker));

    const workerInfo = new google.maps.InfoWindow({
      content: "<strong>العامل:</strong> <?= htmlspecialchars($order['worker_name']) ?>"
    });
    workerMarker.addListener('click', () => workerInfo.open(map, workerMarker));
  }

  window.onload = initMap;
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>

</body>
</html>
