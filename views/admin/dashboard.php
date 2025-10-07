<?php
session_start();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Redirect if user is not an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../security/unauthorized.php");
    exit();
}

// // Redirect if user is using a mobile device
// function isMobileDevice() {
//     return preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT']);
// }

// if (isMobileDevice()) {
//     header("Location: ../../mobile_not_supported.php");
//     exit();
// }

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=grading_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


// Total users by role
function getTotalByRole($pdo, $roleName) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM users u
        INNER JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = ?
    ");
    $stmt->execute([$roleName]);
    return $stmt->fetchColumn();
}

// Assign totals
$totalmasterteachers = getTotalByRole($pdo, 'masterteacher');
$totalAdvisers    = getTotalByRole($pdo, 'Adviser');
$totalTeachers    = getTotalByRole($pdo, 'Teacher');
$totalPrincipals  = getTotalByRole($pdo, 'Principal');

// Total users overall
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();




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
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' /> -->


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
<?php include '../../layoutnav/adminbar.php'; ?>

<div class="main-content">
    <!-- Header -->
    <div style="background-color: #1a1a1a; color: white; padding: 20px; display: flex; justify-content: center; align-items: center; 
                position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; min-height: 70px;">
        <h2 class="m-0" style="position: absolute; left: 55%; transform: translateX(-50%);">Dashboard</h2>
        <?php if (isset($_SESSION['full_name'])): ?>
            <span style="position: absolute; right: 50px;">
                Hello Admin
            </span>
        <?php endif; ?>
    </div>

    <!-- Main Container -->
    <div class="container-fluid py-5 px-3 px-md-5" style="background-color: rgb(212, 212, 212); min-height: calc(100vh - 70px); margin-top: 50px;">
        <div class="row gx-4 gy-4">

            <!-- First Row -->
            <!-- Advisers -->
            <div class="col-12 col-md-6">
                <div class="card text-white text-center h-100" style="background-color: #ff6b6b;">
                    <div class="card-body p-4">
                        <i class="bi bi-mortarboard fs-2 mb-2"></i>
                        <h6 class="card-title">Advisers</h6>
                        <p class="card-text fs-5"><?= $totalAdvisers ?></p>
                    </div>
                </div>
            </div>

            <!-- masterteachers -->
            <div class="col-12 col-md-6">
                <div class="card text-white text-center h-100" style="background-color: #29a329;">
                    <div class="card-body p-4">
                        <i class="bi bi-building fs-2 mb-2"></i>
                        <h6 class="card-title">Master teachers</h6>
                        <p class="card-text fs-5"><?= $totalmasterteachers ?></p>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <!-- Principals -->
            <div class="col-12 col-md-6">
                <div class="card text-white text-center h-100" style="background-color: #ff9f59;">
                    <div class="card-body p-4">
                        <i class="bi bi-journal-text fs-2 mb-2"></i>
                        <h6 class="card-title">Principals</h6>
                        <p class="card-text fs-5"><?= $totalPrincipals ?></p>
                    </div>
                </div>
            </div>

            <!-- Teachers -->
            <div class="col-12 col-md-6">
                <div class="card text-white text-center h-100" style="background-color: #ffde59; color: black;">
                    <div class="card-body p-4">
                        <i class="bi bi-book fs-2 mb-2"></i>
                        <h6 class="card-title">Teachers</h6>
                        <p class="card-text fs-5"><?= $totalTeachers ?></p>
                    </div>
                </div>
            </div>

            <!-- Third Row: Total Users -->
            <div class="col-12">
                <div class="card text-white text-center h-100" style="background-color: #3b6ef5;">
                    <div class="card-body p-4">
                        <i class="bi bi-people-fill fs-2 mb-2"></i>
                        <h6 class="card-title">Total Users</h6>
                        <p class="card-text fs-5"><?= $totalUsers ?></p>
                    </div>
                </div>
            </div>

        </div>


        <!-- Calendar Widget -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Calendar Widget</h5>
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
       
    </div>
</div>


<!-- FullCalendar CSS/JS -->

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>

<style>
  #calendar {
    max-width: 100%;
    margin: 0 auto;
    height: 600px; /* important! */
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        themeSystem: 'bootstrap5', // applies Bootstrap 5 styling
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            { title: 'My Event', start: '2025-09-25' },
            { title: 'Another Event', start: '2025-09-27T10:00:00', end: '2025-09-27T12:00:00' }
        ]
    });
    calendar.render();
});
</script>

   


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
