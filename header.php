<?php
// مثال: تحديد صفحة الحالية لتفعيل الرابط الحالي في القائمة
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
  /* Reset Box Sizing */
  * {
    box-sizing: border-box;
  }

  header {
    background-color: #2c3e50;
    color: white;
    padding: 15px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 999;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  }

  header .logo {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 12px;
    user-select: none;
  }

  header .logo img {
    height: 50px;
    width: auto;
    border-radius: 8px;
  }

  header .logo h1 {
    font-size: 1.8rem;
    margin: 0;
    font-weight: 700;
    color: #1abc9c;
    user-select: none;
  }

  nav {
    display: flex;
    gap: 25px;
    align-items: center;
    flex-wrap: wrap;
  }

  nav a {
    color: white;
    font-weight: 600;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 6px;
    transition: background-color 0.3s, color 0.3s;
  }

  nav a:hover,
  nav a.active {
    background-color: #1abc9c;
    color: #2c3e50;
    outline: none;
  }

  /* زر القائمة في الموبايل */
  #menu-toggle {
    display: none;
    font-size: 28px;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px 10px;
  }

  /* Responsive */
  @media (max-width: 768px) {
    nav {
      display: none;
      flex-direction: column;
      width: 100%;
      background-color: #2c3e50;
      margin-top: 10px;
      border-radius: 8px;
      padding: 10px 0;
    }
    nav.show {
      display: flex;
    }
    #menu-toggle {
      display: block;
    }
  }
</style>

<header>
  <div class="logo" onclick="window.location.href='index.php'">
    <img src="images/wassal.jfif" alt="Wassal Khedma Logo" />
    <h1>Wassal Khedma</h1>
  </div>

  <button id="menu-toggle" aria-label="فتح وإغلاق القائمة">☰</button>

  <nav id="nav-menu" aria-label="قائمة التنقل الرئيسية">
    <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">الرئيسية</a>
    <a href="workers.php" class="<?= $current_page == 'workers.php' ? 'active' : '' ?>">العمال</a>
    <a href="request.php" class="<?= $current_page == 'request.php' ? 'active' : '' ?>">طلب خدمة</a>
    <a href="gps.php" class="<?= $current_page == 'gps.php' ? 'active' : '' ?>">أقرب عامل</a>
    <a href="about.php" class="<?= $current_page == 'about.php' ? 'active' : '' ?>">عن Wassal Khedma</a>
    <a href="contact.php" class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">تواصل معنا</a>
    <a href="login.php" class="<?= $current_page == 'login.php' ? 'active' : '' ?>">تسجيل الدخول</a>
  </nav>
</header>

<script>
  const menuToggle = document.getElementById('menu-toggle');
  const navMenu = document.getElementById('nav-menu');

  menuToggle.addEventListener('click', () => {
    navMenu.classList.toggle('show');
  });
</script>
