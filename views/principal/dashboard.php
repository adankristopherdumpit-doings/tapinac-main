<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'principal') {
    header("Location: ../../login.php");
    exit();
}

// Check if role is 'principal'
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'principal') {
    header("Location: ../security/unauthorized.php");
    exit();
}


// Redirect if user is using a mobile device
function isMobileDevice() {
    return preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT']);
}

if (isMobileDevice()) {
    header("Location: ../../mobile_not_supported.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=grading_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Count total subjects
$totalSubjects = 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
if ($stmt) {
    $totalSubjects = $stmt->fetchColumn();
}

// Count total teachers
$totalTeacher = 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
if ($stmt) {
    $totalTeacher = $stmt->fetchColumn();
}



$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? '';
$role_label = ucfirst($role);



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <title>Principal Dashboard</title>

    <!-- Block Mobile Devices CSS -->
    <style>
        @media (max-width: 767px) {
            body::before {
                content: "This page is only accessible on desktop or tablet devices.";
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: white;
                color: black;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                z-index: 9999;
                text-align: center;
                padding: 20px;
            }

            body > * {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<?php include '../../layoutnav/principalbar.php'; ?>

<div class = "main-content">
    <div style="background-color: #1a1a1a; color: white; padding: 20px 20px; display: flex; justify-content: center; align-items: center; position: relative; min-height: 70px;">
        <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Dashboard</h2>
        <?php if (!empty($full_name)): ?>
        <span style="position: absolute; right: 20px;">
            Hello <?php echo htmlspecialchars($role_label . ' ' . $full_name); ?>
        </span>
        <?php endif; ?>
    </div>

    <div class="container-fluid py-5 px-3 px-md-5" style="background-color: rgb(212, 212, 212); min-height: calc(100vh - 70px);">
        <div class="container">
            <div class="row gx-4 gy-4">

                <!-- Total Student -->
                <div class="col-12 col-sm-6 col-lg-6">
                    <div class="card text-white text-center h-100" style="background-color: #3b6ef5;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Student</h6>
                            <p class="card-text fs-5">0</p>
                        </div>
                    </div>
                </div>

                <!-- Total Subject -->
                <div class="col-12 col-sm-6 col-lg-6">
                    <div class="card text-dark text-center h-100" style="background-color: #ffde59;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Subject</h6>
                            <p class="card-text fs-5"><?= $totalSubjects ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Teacher -->
                <div class="col-12 col-sm-6 col-lg-6">
                    <div class="card text-white text-center h-100" style="background-color: #29a329;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Teacher</h6>
                            <p class="card-text fs-5"><?= $totalTeacher ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Fail -->
                <div class="col-12 col-sm-6 col-lg-6">
                    <div class="card text-white text-center h-100" style="background-color: #e63946;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Fail</h6>
                            <p class="card-text fs-5">0</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // If user clicks back, force page to reload from server
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>

</body>
</html>
