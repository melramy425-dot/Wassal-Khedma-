<?php
// ممكن تستخدم متغير للعناوين لو حابب، أو تخليه ثابت
$page_title = "Wassal Khedma | خدمات صيانة - عمال محترفين في خدمتك";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>
  <style>
    /* الصق كل التنسيقات كما في HTML الأصلي */
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f9fbfc;
      color: #333;
      line-height: 1.6;
    }
    header {
      background: #2c3e50;
      color: white;
      padding: 20px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
    }
    header img {
      height: 60px;
      cursor: pointer;
    }
    header h1 {
      margin: 0;
      font-size: 2rem;
    }
    header p {
      margin-top: 5px;
      font-size: 1rem;
    }
    nav {
      background-color: #34495e;
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      padding: 15px;
      gap: 20px;
      position: relative;
      z-index: 10;
    }
    nav a {
      color: white;
      font-weight: bold;
      text-decoration: none;
      padding: 8px 15px;
      border-radius: 6px;
      transition: background-color 0.3s, color 0.3s;
      white-space: nowrap;
    }
    nav a:hover,
    nav a:focus {
      color: #1abc9c;
      background-color: #2c3e50;
      outline: none;
    }
    #menu-toggle {
      display: none;
      background-color: #1abc9c;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      padding: 6px 12px;
      border-radius: 6px;
      margin-left: 10px;
    }
    @media (max-width: 768px) {
      nav {
        flex-direction: column;
        align-items: center;
        gap: 10px;
        display: none;
      }
      nav.show {
        display: flex;
      }
      #menu-toggle {
        display: block;
      }
      nav .dropdown-content {
        left: 0 !important;
        right: auto !important;
      }
    }
    .dropdown {
      position: relative;
      display: inline-block;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #34495e;
      min-width: 160px;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
      z-index: 1000;
      border-radius: 8px;
      right: 0;
    }
    .dropdown-content a {
      color: white;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      font-weight: normal;
      border-radius: 0;
    }
    .dropdown-content a:hover,
    .dropdown-content a:focus {
      background-color: #1abc9c;
      color: #fff;
      outline: none;
    }
    .dropdown:hover .dropdown-content {
      display: block;
    }
    .dropdown > a::after {
      content: ' ▼';
      font-size: 0.7rem;
    }
    .hero {
      display: flex;
      flex-direction: column;
      align-items: center;
      background: linear-gradient(135deg, #1abc9c, #3498db);
      color: white;
      padding: 60px 20px;
      text-align: center;
    }
    .hero h2 {
      font-size: 2.5rem;
      margin-bottom: 10px;
      line-height: 1.2;
      max-width: 900px;
    }
    .hero p {
      font-size: 1.2rem;
      max-width: 650px;
      margin: auto;
    }
    .cta-buttons {
      margin-top: 30px;
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }
    .cta-buttons a {
      display: inline-block;
      margin: 0;
      padding: 14px 28px;
      background-color: white;
      color: #2c3e50;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: background-color 0.3s, transform 0.2s;
      min-width: 140px;
      text-align: center;
      user-select: none;
    }
    .cta-buttons a:hover,
    .cta-buttons a:focus {
      background-color: #ecf0f1;
      outline: none;
      transform: scale(1.05);
    }
    .gps-button {
      text-align: center;
      margin: 40px auto;
    }
    .gps-button button {
      background-color: #e67e22;
      color: white;
      padding: 14px 30px;
      font-size: 18px;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: background-color 0.3s, transform 0.2s;
      user-select: none;
    }
    .gps-button button:hover,
    .gps-button button:focus {
      background-color: #d35400;
      outline: none;
      transform: scale(1.05);
    }
    .audio-section {
      text-align: center;
      margin: 30px auto;
    }
    .audio-section button {
      background: #27ae60;
      color: white;
      border: none;
      padding: 12px 24px;
      font-size: 16px;
      border-radius: 30px;
      cursor: pointer;
      transition: background-color 0.3s, transform 0.2s;
      user-select: none;
    }
    .audio-section button:hover,
    .audio-section button:focus {
      background: #1e8449;
      outline: none;
      transform: scale(1.05);
    }
    .section {
      padding: 60px 20px;
      max-width: 1100px;
      margin: auto;
    }
    .section h2 {
      text-align: center;
      margin-bottom: 40px;
      color: #2c3e50;
    }
    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
    }
    .feature {
      background: white;
      border-radius: 10px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .feature h3 {
      color: #1abc9c;
      margin-bottom: 10px;
    }
    .feature p {
      font-size: 1rem;
      line-height: 1.4;
    }
    .feature.expanded p {
      font-size: 1.1rem;
      color: #555;
    }
    .team-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }
    .team-gallery a {
      display: block;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }
    .team-gallery a:hover,
    .team-gallery a:focus {
      transform: scale(1.05);
      outline: none;
    }
    .team-gallery img {
      width: 100%;
      object-fit: cover;
      height: 220px;
      vertical-align: middle;
    }
    footer {
      background: #2c3e50;
      color: white;
      text-align: center;
      padding: 20px 10px;
      margin-top: 40px;
    }
    #chat-button {
      position: fixed;
      bottom: 30px;
      left: 30px;
      background-color: #25d366;
      color: white;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      transition: background-color 0.3s;
    }
    #chat-button:hover,
    #chat-button:focus {
      background-color: #128c43;
      outline: none;
    }
    #chat-button img {
      width: 32px;
      height: 32px;
      user-select: none;
    }
  </style>
</head>
<body>

  <header>
    <img src="images/wassal.jfif" alt="Wassal Khedma Logo" onclick="window.location.href='index.php';" />
    <div>
      <h1>Wassal Khedma</h1>
      <p>منصة بتوصلك بالعامل الصح في أسرع وقت</p>
    </div>
    <button id="menu-toggle" aria-label="فتح/إغلاق قائمة التنقل">☰</button>
  </header>

  <nav id="nav-menu" aria-label="قائمة التنقل الرئيسية">
    <a href="index.php">الرئيسية</a>
    <a href="workers.php">العمال</a>
    <a href="request.php">طلب خدمة</a>
    <a href="gps.php">أقرب عامل</a>
    <a href="about.php">عن Wassal Khedma</a>
    <a href="contact.php">تواصل معنا</a>

    <div class="dropdown">
      <a href="#" aria-haspopup="true" aria-expanded="false" tabindex="0">الدفع</a>
      <div class="dropdown-content" aria-label="قائمة الدفع">
        <a href="payment.php">إتمام الدفع</a>
        <a href="payment_history.php">سجل الدفعات</a>
      </div>
    </div>

    <div class="dropdown">
      <a href="#" aria-haspopup="true" aria-expanded="false" tabindex="0">العمال</a>
      <div class="dropdown-content" aria-label="قائمة العمال">
        <a href="workers.php">قائمة العمال</a>
        <a href="chat.php">مراسلة العمال</a>
        <a href="register.php">تسجيل عامل جديد</a>


      </div>
    </div>

    <a href="login.php">تسجيل الدخول</a>
  </nav>

  <section class="hero" aria-label="قسم الترحيب والخدمات الرئيسية">
    <h2>
      محتاج صنايعي يجيلك لحد عندك؟<br />
      مع Wassal Khedma هتلاقي أقرب فني مضمون وسريع يوصل لحد باب بيتك
    </h2>
    <p>مع Wassal Khedma تقدر تطلب العامل الصح في منطقتك بسهولة وسرعة</p>
    <div class="cta-buttons">
      <a href="request.php" role="button">اطلب خدمة</a>
      <a href="register.php" role="button">سجل كعامل</a>
      <a href="find-worker.php" role="button">ابحث عن عامل</a>
    </div>
    <img
      src="https://kafradwar.com/static/img/Mehan.jpg"
      alt="خدمات Wassal Khedma"
    />
  </section>

  <div class="gps-button">
    <a href="gps.php" role="button" aria-label="تحديد أقرب عامل الآن">
      <button id="gps-button">
        <img
          src="https://cdn-icons-png.flaticon.com/512/684/684908.png"
          alt="GPS"
          width="24"
          height="24"
          aria-hidden="true"
        />
        حدد أقرب عامل الآن
      </button>
    </a>
  </div>

  <div class="audio-section">
    <button onclick="document.getElementById('voice').play()" aria-label="تشغيل رسالة Wassal Khedma">رسالتنا</button>
    <audio id="voice" preload="auto">
      <source src="ElevenLabs_Text_to_Speech_audio.mp3" type="audio/mp3" />
      متصفحك لا يدعم الصوت.
    </audio>
  </div>

  <section class="section" aria-label="مميزات Wassal Khedma">
    <h2>مميزات Wassal Khedma</h2>
    <div class="features">
      <div class="feature expanded">
        <h3>📱 سهل وسريع</h3>
        <p>واجهة استخدام سهلة وبسيطة تشتغل من أي جهاز سواء موبايل، تابلت أو كمبيوتر.</p>
      </div>
      <div class="feature expanded">
        <h3>📍 تحديد موقعك</h3>
        <p>استخدم GPS لتحديد أقرب عامل إليك فورًا بدون الحاجة للبحث يدويًا، مما يوفر الوقت والجهد.</p>
      </div>
      <div class="feature expanded">
        <h3>💬 تواصل مباشر</h3>
        <p>تواصل مع العامل عبر التطبيق مباشرة بدون وسيط لضمان سرعة الاستجابة وجودة الخدمة.</p>
      </div>
      <div class="feature expanded">
        <h3>⭐ تقييمات العملاء</h3>
        <p>اعتمد على تقييمات حقيقية من عملاء سابقين لاختيار العامل الأنسب لك بثقة عالية.</p>
      </div>
    </div>
  </section>

  <section class="section" aria-label="فريق العمل الجاهز لخدمتك">
    <h2>فريق العمل الجاهز لخدمتك</h2>
    <div class="team-gallery">
      <a href="workers.php" title="عامل محترف">
        <img
          src="https://zeitarbeit-international.de/wp-content/uploads/2022/03/iStock-1049769264.jpg"
          alt="عامل محترف"
        />
      </a>
      <a href="workers.php" title="عمال صيانة">
        <img
          src="https://cdn.alweb.com/thumbs/alta3beer/article/fit710x532/%D8%AA%D8%B9%D8%A8%D9%8A%D8%B1-%D8%B9%D9%86-%D9%85%D9%87%D9%86%D8%A9-%D8%A7%D9%84%D9%86%D8%AC%D8%A7%D8%B1.jpg"
          alt="عمال صيانة"
        />
      </a>
      <a href="workers.php" title="سباك متمكن">
        <img
          src="https://alfursan-cleaning-services.com/wp-content/uploads/2020/01/%D8%B3%D8%A8%D8%A7%D9%83%D9%83.jpg"
          alt="سباك متمكن"
        />
      </a>
      <a href="workers.php" title="كهربائي متمرس">
        <img
          src="https://www.smasco.com/wp-content/uploads/2025/02/9-signs-an-operator-is-ready-to-lead-in-a-foreman-job-jpg.webp"
          alt="كهربائي متمرس"
        />
      </a>
    </div>
  </section>

  <a href="chat.php" id="chat-button" title="مراسلة العمال" aria-label="مراسلة العمال">
    <img src="https://cdn-icons-png.flaticon.com/512/124/124034.png" alt="Chat Icon" />
  </a>

  <footer>
    <p>© 2025 Wassal Khedma - دايمًا في خدمتك في أي وقت وأي مكان</p>
  </footer>

  <script>
    // تفعيل زرار القائمة في الموبايل
    const menuToggle = document.getElementById('menu-toggle');
    const navMenu = document.getElementById('nav-menu');

    menuToggle.addEventListener('click', () => {
      navMenu.classList.toggle('show');
    });
  </script>
<?php include 'footer.php'; ?>

</body>
</html>
