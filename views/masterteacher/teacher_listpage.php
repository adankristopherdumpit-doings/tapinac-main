<?php
session_start();
include '../../database/db_connection.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../security/unauthorized.php");
    exit();
}

// User & role info
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? '';
$role_label = ucfirst($role);
$header_color = ($role === 'masterteacher') ? '#1a1a1a' : '#44A344';

// Nav file
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php"; // fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="../../assets/image/logo/logoone.png" />
  <title>Monitor All Classes</title>
  <link rel="stylesheet" href="../../assets/css/sidebar.css" />
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    .section-btn {
      background-color: #e0e0e0;
      border: 1px solid #bbb;
      padding: 15px;
      margin: 5px;
      border-radius: 5px;
      font-weight: bold;
      color: black;
      text-decoration: none;
      display: inline-block;
      min-width: 120px;
      text-align: center;
    }
    .section-btn:hover {
      background-color: #17a2b8;
      color: white;
    }
  </style>
</head>
<body>

<!-- Navigation -->
<?php include $nav_file; ?>

<div class="main-content">
  <!-- Sticky Header -->
  <div style="
      position: sticky;
      top: 0;
      z-index: 999;
      background-color: <?= $header_color ?>;
      color: white;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 70px;
  ">
    <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Monitor All Classes</h2>
    <?php if (!empty($full_name)): ?>
        <span style="position: absolute; right: 20px;">
            Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
        </span>
    <?php endif; ?>
  </div>
  <div class="d-flex justify-content-between mb-3">
    <a href="../masterteacher/student_page.php" class="btn btn-secondary">Back</a>
  </div>
  <!-- Class Sections -->
  <div class="container mt-4">
    <!-- Example layout -->
    <h5>Grade 1</h5>
    <div>
      <a href="section_page.php?grade=1&section=SSES" class="section-btn">SSES</a>
      <a href="section_page.php?grade=1&section=Rose" class="section-btn">Rose</a>
      <a href="section_page.php?grade=1&section=Santan" class="section-btn">Santan</a>
      <a href="section_page.php?grade=1&section=Daisy" class="section-btn">Daisy</a>
    </div>

    <h5 class="mt-4">Grade 2</h5>
    <div>
      <a href="section_page.php?grade=2&section=SSES" class="section-btn">SSES</a>
      <a href="section_page.php?grade=2&section=Venus" class="section-btn">Venus</a>
      <a href="section_page.php?grade=2&section=Neptune" class="section-btn">Neptune</a>
      <a href="section_page.php?grade=2&section=Saturn" class="section-btn">Saturn</a>
    </div>

    <h5 class="mt-4">Grade 3</h5>
    <div>
      <a href="section_page.php?grade=3&section=SSES" class="section-btn">SSES</a>
      <a href="section_page.php?grade=3&section=Mabini" class="section-btn">Mabini</a>
      <a href="section_page.php?grade=3&section=Bonifacio" class="section-btn">Bonifacio</a>
      <a href="section_page.php?grade=3&section=Rizal" class="section-btn">Rizal</a>
    </div>
        <h5 class="mt-4">Grade 4</h5>
    <div>
      <a href="section_page.php?grade=4&section=SSES" class="section-btn">SSES</a>
      <a href="section_page.php?grade=4&section=Topaz" class="section-btn">Topaz</a>
      <a href="section_page.php?grade=4&section=Emerald" class="section-btn">Emerald</a>
      <a href="section_page.php?grade=4&section=Jade" class="section-btn">Jade</a>
    </div>

    <h5 class="mt-4">Grade 5</h5>
    <div>
      <a href="section_page.php?grade=5&section=SSES" class="section-btn">SSES</a>
      <a href="section_page.php?grade=5&section=Diamond" class="section-btn">Diamond</a>
      <a href="section_page.php?grade=5&section=Pearl" class="section-btn">Pearl</a>
      <a href="section_page.php?grade=5&section=Ruby" class="section-btn">Ruby</a>
    </div>
        <h5 class="mt-4">Grade 6</h5>
    <div>
      <a href="section_page.php?grade=6&section=SSES" class="section-btn">SSES</a>
      <a href="section_page.php?grade=6&section=Love" class="section-btn">Love</a>
      <a href="section_page.php?grade=6&section=Hope" class="section-btn">Hope</a>
      <a href="section_page.php?grade=6&section=Humility" class="section-btn">Humility</a>
      <a href="section_page.php?grade=6&section=Faith" class="section-btn">Faith</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
