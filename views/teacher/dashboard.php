<?php
session_start();

// Force page to expire so browser doesn't cache it
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Redirect if user is not an teacher
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../security/unauthorized.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=grading_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$totalSubjects = 0;
$stmt = $pdo->query("SELECT COUNT(*) FROM subject_assignments WHERE teacher_id = " . $_SESSION['user_id']);
if ($stmt) {
    $count = $stmt->fetchColumn();
    $totalSubjects = ($count !== false) ? $count : 0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    
    <!-- Bootstrap 5 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="../../assets/css/sidebar.css" />
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css" />
    <title>Dashboard</title>
</head>

<body>

    <!-- Navigation Bar -->
    <?php include '../../layoutnav/teacherbar.php'; ?>

    <div class="main-content">
        <div class="header-bar">
            <h2>Dashboard</h2>
            <?php if (isset($_SESSION['full_name'])): ?>
            <span class="greeting">Hello Teacher <?= htmlspecialchars($_SESSION['full_name']); ?></span>
            <?php endif; ?>
        </div>

        <div class="container-fluid p-4" style="min-height: calc(100vh - 70px);">
            <div class="row g-4" style="max-width: 900px; margin: 0 auto;">
                <!-- Total Student -->
                <div class="col-12 col-md-6">
                    <div class="card text-white text-center" style="background-color: #3b6ef5;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Student</h6>
                            <p class="card-text fs-3">0</p>
                        </div>
                    </div>
                </div>
                <!-- Total Subject -->
                <div class="col-12 col-md-6">
                    <div class="card text-dark text-center" style="background-color: #ffde59;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Subject</h6>
                            <p class="card-text fs-3"><?= $totalSubjects == 0 ? '0' : $totalSubjects ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Pass -->
                <div class="col-12 col-md-6">
                    <div class="card text-white text-center" style="background-color: #29a329;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Pass</h6>
                            <p class="card-text fs-3">0</p>
                        </div>
                    </div>
                </div>

                <!-- Total Fail -->
                <div class="col-12 col-md-6">
                    <div class="card text-white text-center" style="background-color: #e63946;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Fail</h6>
                            <p class="card-text fs-3">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end main-content -->

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // If user clicks back, force page to reload from server
        window.addEventListener("pageshow", function(event) {
            if (event.persisted) window.location.reload();
        });
    </script>

</body>

</html>
