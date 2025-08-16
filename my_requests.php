<?php
session_start();

// تأكد تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// إعدادات قاعدة البيانات
$host = 'localhost';
$db   = 'wassal_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // إضافة طلب جديد
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add_request') {
            $city = trim($_POST['city'] ?? '');
            $area = trim($_POST['area'] ?? '');
            $details = trim($_POST['details'] ?? '');

            if ($city && $area && $details) {
                $stmt = $pdo->prepare("INSERT INTO service_requests (user_id, city, area, details, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([$user_id, $city, $area, $details]);
                $message = "تم إضافة الطلب بنجاح.";
            } else {
                $error = "يرجى ملء كل بيانات الطلب.";
            }
        } elseif ($_POST['action'] === 'update_status') {
            $request_id = (int)($_POST['request_id'] ?? 0);
            $new_status = $_POST['new_status'] ?? '';

            if (in_array($new_status, ['pending', 'completed', 'canceled'])) {
                // تأكد إن الطلب يخص المستخدم
                $stmt = $pdo->prepare("SELECT id FROM service_requests WHERE id = ? AND user_id = ?");
                $stmt->execute([$request_id, $user_id]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $request_id]);
                    $message = "تم تحديث حالة الطلب.";
                } else {
                    $error = "الطلب غير موجود أو لا تملك صلاحية تعديله.";
                }
            } else {
                $error = "حالة غير صالحة.";
            }
        } elseif ($_POST['action'] === 'add_balance') {
            $amount = floatval($_POST['amount'] ?? 0);
            if ($amount > 0) {
                $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->execute([$amount, $user_id]);
                $message = "تمت إضافة الرصيد بنجاح.";
            } else {
                $error = "المبلغ غير صالح.";
            }
        }
    }

    // جلب الرصيد الحالي
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $wallet = $stmt->fetchColumn();
    if ($wallet === false) {
        $wallet = 0;
    }

    // جلب الطلبات
    $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();

} catch (PDOException $e) {
    die("حدث خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// مصفوفة المناطق كما في الكود الأصلي
$cityAreas = [
    "اسكندرية" => ["جليم","كامب شيزار","بحري","سيدي جابر","ميامي","جناكليز","سان ستيفانو","باكوس","العطارين","الشاطبي","كليوباترا","المنتزه","المعمورة","محرم بك"],
    "القاهرة" => ["السبتية","شبرا","التوفيقية","الضرب الأحمر","التجمع","مدينة نصر","التحرير","الحسين","السيدة زينب","المعادي"],
    "الجيزة" => ["الدقي","إمبابة","أكتوبر","الشيخ زايد","الوراق","الهرم","حدائق الأهرام"]
];

function getStatusText($status) {
    switch($status) {
        case "pending": return "جاري التنفيذ";
        case "completed": return "مكتمل";
        case "canceled": return "ملغي";
        default: return "غير معروف";
    }
}

function getStatusClass($status) {
    switch($status) {
        case "pending": return "pending";
        case "completed": return "completed";
        case "canceled": return "canceled";
        default: return "";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>طلباتي - Wassal Khedma</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f4f6f8;
        margin: 0;
    }
    header {
        background: #1a73e8;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    header img {
        height: 40px;
        margin-left: 10px;
    }
    header h1 {
        font-size: 20px;
        margin: 0;
    }
    nav {
        background: #0d47a1;
        padding: 10px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    nav a {
        color: white;
        text-decoration: none;
        background: #1976d2;
        padding: 8px 15px;
        border-radius: 6px;
        transition: background 0.3s;
    }
    nav a:hover {
        background: #1565c0;
    }
    .container {
        padding: 20px;
        max-width: 900px;
        margin: auto;
    }
    .wallet {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .wallet button {
        background: #43a047;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        cursor: pointer;
    }
    .wallet button:hover {
        background: #2e7d32;
    }
    .form-section {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    select, input, button {
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        margin-bottom: 10px;
        width: 100%;
        box-sizing: border-box;
    }
    button.add-request {
        background: #1a73e8;
        color: white;
        border: none;
        cursor: pointer;
    }
    button.add-request:hover {
        background: #0d47a1;
    }
    .request-card {
        background: white;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .status {
        padding: 5px 10px;
        border-radius: 6px;
        font-weight: bold;
        display: inline-block;
        margin-top: 10px;
    }
    .pending { background: orange; color: white; }
    .completed { background: green; color: white; }
    .canceled { background: red; color: white; }

    /* رسالة نجاح وخطأ */
    .message {
        margin-bottom: 20px;
        padding: 10px;
        border-radius: 6px;
        font-weight: bold;
    }
    .message.success {
        background: #d4edda;
        color: #155724;
    }
    .message.error {
        background: #f8d7da;
        color: #721c24;
    }
</style>
</head>
<body>

<header>
    <div style="display: flex; align-items: center;">
        <img src="images/wassal.jfif" alt="Logo">
        <h1>Wassal Khedma</h1>
    </div>
    <div>🔔 <span id="notif-count"><?= count(array_filter($requests, fn($r) => $r['status'] === 'pending')) ?></span></div>
</header>

<nav>
    <a href="index.php">الرئيسية</a>
    <a href="worker_dashboard.php">لوحة العامل</a>
    <a href="chat.php">الشات</a>
    <a href="my_requests.php" style="background:#1565c0;">طلباتي</a>
    <a href="request.php">طلب خدمة</a>
    <a href="login.php">تسجيل الدخول</a>
    <a href="logout.php">تسجيل الخروج</a>
</nav>

<div class="container">

    <?php if (isset($message)): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php elseif (isset($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="wallet">
        <span>💰 الرصيد: <strong id="wallet-balance"><?= number_format($wallet, 2) ?></strong> جنيه</span>
        <button onclick="showAddBalance()">إضافة رصيد</button>
    </div>

    <div id="add-balance-form" class="form-section" style="display:none;">
        <form method="POST" onsubmit="return validateBalanceForm();">
            <input type="hidden" name="action" value="add_balance">
            <input type="number" name="amount" id="balance-amount" placeholder="أدخل المبلغ لإضافته" step="0.01" min="0.01" required>
            <button type="submit">إضافة الرصيد</button>
            <button type="button" onclick="hideAddBalance()" style="background:#888; margin-top:5px;">إلغاء</button>
        </form>
    </div>

    <div class="form-section">
        <h3>إضافة طلب جديد</h3>
        <form method="POST" onsubmit="return validateRequestForm();">
            <input type="hidden" name="action" value="add_request">
            <select id="city" name="city" onchange="updateAreas()" required>
                <option value="">اختر المحافظة</option>
                <?php foreach(array_keys($cityAreas) as $cityName): ?>
                    <option value="<?= htmlspecialchars($cityName) ?>"><?= htmlspecialchars($cityName) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="area" name="area" required>
                <option value="">اختر المنطقة</option>
            </select>
            <input type="text" name="details" id="details" placeholder="تفاصيل الطلب" required>
            <button class="add-request" type="submit">إضافة الطلب</button>
        </form>
    </div>

    <div id="requests-list">
        <?php if (empty($requests)): ?>
            <p>لا توجد طلبات حالياً.</p>
        <?php else: ?>
            <?php foreach ($requests as $req): ?>
                <div class="request-card" aria-live="polite">
                    <h4>📍 <?= htmlspecialchars($req['city']) ?> - <?= htmlspecialchars($req['area']) ?></h4>
                    <p><?= nl2br(htmlspecialchars($req['details'])) ?></p>
                    <span class="status <?= getStatusClass($req['status']) ?>"><?= getStatusText($req['status']) ?></span>

                    <?php if ($req['status'] === 'pending'): ?>
                        <form method="POST" style="margin-top:10px;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <button type="submit" name="new_status" value="completed" style="background:green; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer;">✅ مكتمل</button>
                            <button type="submit" name="new_status" value="canceled" style="background:red; color:white; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; margin-left:8px;">❌ إلغاء</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    const cityAreas = <?= json_encode($cityAreas, JSON_UNESCAPED_UNICODE) ?>;

    function updateAreas() {
        const city = document.getElementById("city").value;
        const areaSelect = document.getElementById("area");
        areaSelect.innerHTML = '<option value="">اختر المنطقة</option>';
        if (cityAreas[city]) {
            cityAreas[city].forEach(area => {
                const option = document.createElement('option');
                option.value = area;
                option.textContent = area;
                areaSelect.appendChild(option);
            });
        }
    }

    function showAddBalance() {
        document.getElementById('add-balance-form').style.display =
