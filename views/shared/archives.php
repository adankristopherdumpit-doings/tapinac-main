<?php
session_start();
require_once '../../database/db_connection.php';

/*
 * ---- DEBUG SAFETY & HEADERS ----
 * Make sure we don't accidentally echo HTML on the AJAX branch.
 */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if not logged in (for both page and AJAX)
if (!isset($_SESSION['user_id'])) {
    // If AJAX, return JSON instead of redirecting
    if (isset($_POST['action']) && $_POST['action'] === 'fetchDroppedStudent') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode(['error' => 'Not authenticated.']);
        exit();
    }
    header("Location: ../../login.php");
    exit();
}

// Allowed roles
$allowed_roles = ['adviser', 'masterteacher', 'principal', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    if (isset($_POST['action']) && $_POST['action'] === 'fetchDroppedStudent') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        echo json_encode(['error' => 'Unauthorized role.']);
        exit();
    }
    header("Location: ../security/unauthorized.php");
    exit();
}

/*
 * ---- DB CONNECTION GUARD ----
 * Try to normalize $conn if db_connection.php used a different variable.
 */
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $conn = $mysqli;
    } elseif (isset($db) && $db instanceof mysqli) {
        $conn = $db;
    }
}
if (isset($conn) && $conn instanceof mysqli) {
    @$conn->set_charset('utf8mb4');
}

/*
 * ---- AJAX: fetch a single dropped student ----
 */
if (isset($_POST['action']) && $_POST['action'] === 'fetchDroppedStudent') {
    header('Content-Type: application/json; charset=utf-8');

    // Validate connection early; return JSON with 200 so frontend shows message instead of generic error
    if (!isset($conn) || !($conn instanceof mysqli)) {
        http_response_code(200);
        echo json_encode(['error' => 'Database connection is not available.']);
        exit();
    }

    $archive_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    if ($archive_id <= 0) {
        http_response_code(200);
        echo json_encode(['error' => 'Invalid student id.']);
        exit();
    }

    $sql = "SELECT fname, mname, lname, gender FROM archives WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(200);
        echo json_encode(['error' => 'Failed to prepare query.']);
        exit();
    }

    $stmt->bind_param("i", $archive_id);
    if (!$stmt->execute()) {
        http_response_code(200);
        echo json_encode(['error' => 'Failed to execute query.']);
        $stmt->close();
        exit();
    }

    $result = $stmt->get_result();
    $student = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    http_response_code(200);
    echo json_encode($student ?: []); // empty object if not found
    exit();
}

/*
 * ---- PAGE RENDERING (non-AJAX) ----
 */

$role         = $_SESSION['role'];
$full_name    = $_SESSION['full_name'] ?? '';
$role_label   = ucfirst($role);
$header_color = ($role === 'masterteacher') ? '#1a1a1a' : '#44A344';

// Navigation file
$nav_file = "../../layoutnav/{$role}bar.php";
if (!file_exists($nav_file)) {
    $nav_file = "../../layoutnav/defaultbar.php";
}

// DB query: list archives
$archivesResult = null;
$archivesSql = "SELECT 
                    a.id,
                    COALESCE(gl.grade_name,  'Not assigned') AS grade,
                    COALESCE(s.section_name, 'Not assigned') AS section,
                    a.dropped_at
                FROM archives a
                LEFT JOIN grade_levels gl ON a.grade_level_id = gl.id
                LEFT JOIN sections s     ON a.section_id = s.id
                ORDER BY a.dropped_at DESC";
if (isset($conn) && $conn instanceof mysqli) {
    $archivesResult = $conn->query($archivesSql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css" />
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <title>Archives</title>
</head>
<body>

<?php include $nav_file; ?>

<div class="main-content">

    <!-- Sticky Header -->
    <div style="position: sticky; top: 0; z-index: 999; background-color: <?= htmlspecialchars($header_color) ?>; color: white; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 70px;">
        <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Archives</h2>
        <?php if (!empty($full_name)): ?>
        <span style="position: absolute; right: 20px;">
            Hello <?= htmlspecialchars($role_label . ' ' . $full_name) ?>
        </span>
        <?php endif; ?>
    </div>

    <div class="container-fluid py-3">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <!-- Toolbar -->
                <div class="p-3 d-flex gap-2 align-items-center">
                    <button class="btn btn-success">
                        <i class="bi bi-star-fill me-1"></i> Grade
                    </button>
                    <button class="btn btn-success">
                        <i class="bi bi-grid-fill me-1"></i> Sections
                    </button>
                    <div class="ms-auto" style="max-width: 300px; position: relative;">
                        <input type="text" class="form-control ps-5" placeholder="Search">
                        <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%); color: #888;"></i>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive px-3">
                    <table class="table table-bordered table-hover" id="archivesTable">
                        <thead class="table-info">
                            <tr>
                                <th>Grade</th>
                                <th>Section</th>
                                <th>Description</th>
                                <th>Academic Year</th>
                                <th>Expire</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($archivesResult && $archivesResult->num_rows > 0): ?>
                            <?php while ($row = $archivesResult->fetch_assoc()): ?>
                                <?php
                                    $droppedAt = $row['dropped_at'] ?? null;
                                    $year = $droppedAt ? (int)date('Y', strtotime($droppedAt)) : null;
                                    $ay   = $year ? ($year . '-' . ($year + 1)) : 'N/A';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['grade']) ?></td>
                                    <td><?= htmlspecialchars($row['section']) ?></td>
                                    <td>Dropped student record</td>
                                    <td><?= htmlspecialchars($ay) ?></td>
                                    <td>3 years</td>
                                    <td>
                                        <a href="#" 
                                           class="text-primary viewDroppedBtn" 
                                           data-id="<?= (int)$row['id'] ?>">
                                           View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No archived records found</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Dropped Student Details Modal -->
                <div class="modal fade" id="viewDroppedStudentModal" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Dropped Student Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <p><strong>First Name:</strong> <span id="fname"></span></p>
                        <p><strong>Middle Name:</strong> <span id="mname"></span></p>
                        <p><strong>Last Name:</strong> <span id="lname"></span></p>
                        <p><strong>Gender:</strong> <span id="gender"></span></p>
                        <div id="ajaxError" class="text-danger small mt-2" style="display:none;"></div>
                      </div>
                    </div>
                  </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function() {
    // Helper to open Bootstrap 5 modal
    function showDroppedModal() {
        var el = document.getElementById('viewDroppedStudentModal');
        var modal = window.__droppedModal || new bootstrap.Modal(el);
        window.__droppedModal = modal;
        modal.show();
    }

    $(document).on('click', '.viewDroppedBtn', function(e) {
        e.preventDefault();
        var id = $(this).data('id');

        $.ajax({
            url: window.location.href,      // post back to this page
            type: 'POST',
            dataType: 'json',               // expect JSON (no manual JSON.parse)
            data: { action: 'fetchDroppedStudent', student_id: id },
            success: function(data, status, xhr) {
                // If backend sent an error field, show it nicely
                if (data && data.error) {
                    console.error('Server error:', data.error);
                    $('#ajaxError').text(data.error).show();
                    // Clear fields
                    $('#fname, #mname, #lname, #gender').text('');
                    showDroppedModal();
                    return;
                }

                // No record found
                if (!data || Object.keys(data).length === 0) {
                    $('#ajaxError').text('No student found for the selected record.').show();
                    $('#fname, #mname, #lname, #gender').text('');
                    showDroppedModal();
                    return;
                }

                // Populate fields
                $('#ajaxError').hide().text('');
                $('#fname').text(data.fname ?? '');
                $('#mname').text(data.mname ?? '');
                $('#lname').text(data.lname ?? '');
                $('#gender').text(data.gender ?? '');

                showDroppedModal();
            },
            error: function(xhr, status, error) {
                // Surface useful debugging info in console
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                alert('Failed to load student details.');
            }
        });
    });

    // Force reload on bfcache back/forward
    window.addEventListener("pageshow", function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
