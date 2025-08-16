<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>عن wassal khedma </title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f7fa;
      color: #333;
    }

    header {
      background-color: #2c3e50;
      color: white;
      padding: 20px;
      text-align: center;
    }

    nav {
      background-color: #34495e;
      display: flex;
      justify-content: center;
      gap: 30px;
      padding: 15px 0;
    }

    nav a {
      color: white;
      text-decoration: none;
      font-weight: bold;
    }

    nav a:hover {
      color: #1abc9c;
    }

    .about-section {
      max-width: 900px;
      margin: 40px auto;
      padding: 30px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .about-section h2 {
      text-align: center;
      color: #2c3e50;
    }

    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .feature {
      background-color: #ecf0f1;
      padding: 20px;
      border-radius: 8px;
      text-align: center;
    }

    .feature img {
      max-width: 80px;
      margin-bottom: 15px;
    }

    .feature h3 {
      color: #1abc9c;
    }

    footer {
      background: #2c3e50;
      color: white;
      text-align: center;
      padding: 15px;
      margin-top: 40px;
    }
  </style>
</head>
<body>

  <header>
    <h1>wassal khedma</h1>
    <p>منصة ذكية لربط أصحاب الحرف بالعملاء</p>
  </header>

  <nav>
    <a href="index.php">الرئيسية</a>
    <a href="workers.php">العمال</a>
    <a href="request.php">طلب خدمة</a>
    <a href="contact.php">تواصل معنا</a>
    <a href="login.php">تسجيل الدخول</a>
  </nav>

  <section class="about-section">
    <h2>عن wassal khedma</h2>
    <p>
      wassal khedma هو موقع تم تصميمه لمساعدة الناس في المناطق الشعبية والريفية لعرض مهاراتهم الحرفية، مثل النجارة، السباكة، الكهرباء، وغيرها، وجعلهم متاحين للعملاء اللي محتاجين الخدمات دي بشكل سريع وسهل.
    </p>

    <p>
      هدفنا هو تقديم حل بسيط وفعّال يوصل بين مقدم الخدمة والعميل بدون تعقيد، ومن غير ما نطلب شهادات أو خبرات رسمية. كل اللي محتاجه هو رقم موبايلك ومهارتك.
    </p>

    <div class="features">
      <div class="feature">
        <img src="https://cdn-icons-png.flaticon.com/512/2920/2920203.png" alt="سهولة التسجيل">
        <h3>سهولة التسجيل</h3>
        <p>عامل؟ سجل بياناتك في أقل من دقيقة وخلي الناس توصلك بسهولة.</p>
      </div>
      <div class="feature">
        <img src="https://cdn-icons-png.flaticon.com/512/2942/2942673.png" alt="بحث سريع">
        <h3>بحث سريع حسب الموقع</h3>
        <p>العميل يقدر يلاقي أقرب حد عنده مهارة معينة بكل سرعة.</p>
      </div>
      <div class="feature">
        <img src="https://cdn-icons-png.flaticon.com/512/1388/1388849.png" alt="بدون تعقيد">
        <h3>بدون شروط معقدة</h3>
        <p>مش محتاج CV ولا شهادات. بس مهارتك وخدمتك.</p>
      </div>
    </div>
  </section>

  <footer>
    <p>&copy; 2025 wassal khedma  - كلنا نخدم بعض بإيدينا.</p>
  </footer>
<?php include 'footer.php'; ?>

</body>
</html>
