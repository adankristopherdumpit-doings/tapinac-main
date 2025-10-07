<?php
session_start();

// Debugging (turn on errors)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    header("Location: ../../security/unauthorized.php"); 
    exit();
}

// Database connection (PDO only)
$pdo = new PDO("mysql:host=localhost;dbname=grading_system", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get filters
$role   = $_GET['role'] ?? '';
$sort   = $_GET['sort'] ?? 'name-asc';
$search = $_GET['search'] ?? '';
$params = [];

// Base SQL query
$sql = "
    SELECT 
        users.id AS user_id,
        CONCAT(teachers.fname, ' ', teachers.mname, ' ', teachers.lname) AS fullname,
        roles.role_name AS role,
        teachers.email,
        teachers.created_at,
        teachers.updated_at
    FROM users
    JOIN teachers ON users.teacher_id = teachers.id
    JOIN roles ON users.role_id = roles.id
    WHERE 1
";

// Exclude the currently logged-in admin
$sql .= " AND users.id != ?";
$params[] = $_SESSION['user_id'];

// Filter by role
if (!empty($role)) {
    $sql .= " AND roles.role_name = ?";
    $params[] = $role;
}

// Filter by role
if (!empty($role)) {
    $sql .= " AND roles.role_name = ?";
    $params[] = $role;
}

// Filter by search
if (!empty($search)) {
    $sql .= " AND (
        teachers.fname LIKE ? OR 
        teachers.mname LIKE ? OR 
        teachers.lname LIKE ? OR 
        teachers.email LIKE ?
    )";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Sorting logic
$sortOptions = [
    'name-asc'      => 'fullname ASC',
    'name-desc'     => 'fullname DESC',
    'email-asc'     => 'teachers.email ASC',
    'email-desc'    => 'teachers.email DESC',
    'created-asc'   => 'teachers.created_at ASC',
    'created-desc'  => 'teachers.created_at DESC',
    'modified-asc'  => 'teachers.updated_at ASC',
    'modified-desc' => 'teachers.updated_at DESC'
];

$orderBy = $sortOptions[$sort] ?? 'fullname ASC';
$sql .= " ORDER BY $orderBy";

// Prepare and execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Role counts for filter dropdown
$roleStmt = $pdo->query("
    SELECT roles.role_name, COUNT(users.id) AS user_count
    FROM roles
    LEFT JOIN users ON roles.id = users.role_id
    GROUP BY roles.role_name
");
$roleData = $roleStmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <title>Account Management</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Account Management</title>
</head>
<body>



<!-- Navigation Bar -->
<?php include '../../layoutnav/adminbar.php'; ?>


<div>
  <div style="
    background-color: #1a1a1a; 
    color: white; 
    padding: 20px 20px; 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    position: sticky; 
    top: 0; 
    z-index: 999; 
    min-height: 70px;
  ">
    <h2 class="m-0" style="position: absolute; left: 45%; transform: translateX(-50%) margin: left;">
      Account Management
    </h2>
    <?php if (isset($_SESSION['full_name'])): ?>
      <span style="position: absolute; right: 50px;">
        Hello Admin
      </span>
    <?php endif; ?> 
  </div>


    <!-- Modal for Create Account -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-lg">

        <form id="createUserForm">
          <div class="modal-content">
            <div class="modal-header text-center w-100 d-block">
              <h5 class="modal-title" id="createUserModalLabel">Create User</h5>
            </div>

            <div class="modal-body">
              <!-- This stays inside the modal -->
              <div id="createUserErrorAlert"></div>


              <!-- Row 1: First Name, Middle Name, Last Name -->
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label for="fname" class="form-label">First Name</label>
                  <input type="text" id="fname" name="fname" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label for="mname" class="form-label">Middle Name</label>
                  <input type="text" id="mname" name="mname" class="form-control">
                </div>
                <div class="col-md-4">
                  <label for="lname" class="form-label">Last Name</label>
                  <input type="text" id="lname" name="lname" class="form-control" required>
                </div>
              </div>

              <!-- Row 2: Email, Role, Username -->
              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label for="email" class="form-label">Gmail</label>
                  <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label for="role" class="form-label">Role</label>
                  <select id="role" name="role" class="form-select" required>
                    <option value="" selected disabled>Select Role</option>
                    <option value="masterteacher">masterteacher</option>
                    <option value="principal">Principal</option>
                    <option value="adviser">Adviser</option>
                    <option value="teacher">Teacher</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label for="username" class="form-label">Username</label>
                  <input type="text" id="username" name="username" class="form-control" required>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-danger" id="cancelCreateUser">Cancel</button>
              <button type="submit" class="btn btn-success" id="createBtn">
                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true" id="createSpinner"></span>
                <span>Create</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- CREATE ACCOUNT CONFIRMATION MODAL -->
    <div class="modal fade" id="confirmCreateUserModal" tabindex="-1" aria-labelledby="confirmCreateUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmCreateUserModalLabel">Confirm Account Creation</h5>
          </div>

          <div class="modal-body">
            Are you sure you want to create this user account?
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">No, Cancel</button>
            <button type="button" class="btn btn-success" id="confirmCreateUserBtn">Yes, Create</button>
          </div>
        </div>
      </div>
    </div>
 
    <!-- Cancel Confirmation Modal -->
    <div class="modal fade" id="cancelConfirmModal" tabindex="-1" aria-labelledby="cancelConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content"> <!-- Removed border-warning -->
          <div class="modal-header"> <!-- Removed bg-warning and text-dark -->
            <h5 class="modal-title" id="cancelConfirmModalLabel">Cancel Confirmation</h5>
          </div>
          <div class="modal-body">
            You have unsaved changes. Are you sure you want to cancel?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" id="cancelNo">No</button>
            <button type="button" class="btn btn-danger" id="cancelYes">Yes</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Edit Update Account -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-lg">

        <form id="editTeacherForm" novalidate>
          <div class="modal-content">
            <div class="modal-header text-center w-100 d-block">
              <h5 class="modal-title" id="editAccountModalLabel">Update Account</h5>
            </div>
            <div class="modal-body">
              <div id="editUserErrorAlert"></div>
              
              <input type="hidden" name="user_id" id="edit_user_id">

              <!-- First Row -->
              <div class="row mb-3">
                <div class="col-md-4">
                  <label for="edit_fname" class="form-label">First Name</label>
                  <input type="text" class="form-control" name="fname" id="edit_fname" required>
                </div>
                <div class="col-md-4">
                  <label for="edit_mname" class="form-label">Middle Name</label>
                  <input type="text" class="form-control" name="mname" id="edit_mname">
                </div>
                <div class="col-md-4">
                  <label for="edit_lname" class="form-label">Last Name</label>
                  <input type="text" class="form-control" name="lname" id="edit_lname" required>
                </div>
              </div>

              <!-- Second Row -->
              <div class="row mb-3">
                <div class="col-md-4">
                  <label for="edit_email" class="form-label">Email</label>
                  <input type="email" class="form-control" name="email" id="edit_email" required>
                </div>
                <div class="col-md-4">
                  <label for="edit_role" class="form-label">Role</label>
                  <select class="form-select" name="role_id" id="edit_role" required>
                    <option value="">Select role</option>
                    <!-- Dynamic roles here -->
                  </select>
                  <div class="invalid-feedback">Please select a role.</div>
                </div>
                <div class="col-md-4">
                  <label for="edit_username" class="form-label">Username</label>
                  <input type="text" class="form-control" name="username" id="edit_username" required>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger" id="editCloseBtn">Cancel</button>
              <button type="submit" class="btn btn-success" id="saveEditBtn">
                <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true" id="editSpinner"></span>
                <span id="saveEditText">Save Changes</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Display list -->
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-90">
          <div class="main-content">
            <!-- Search, Filters and Action Buttons -->
            <div class="search-and-buttons sticky-top bg-white py-3 z-1">
              <!-- Search Bar on Top -->
              <div class="row mb-3">
                <div class="col d-flex">
                  <form id="searchForm" class="d-flex w-100" method="GET">
                    <input type="text" name="search" id="searchInput" class="form-control rounded-pill me-2" 
                          placeholder="Search by name or email" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"  />
                    <button class="btn btn-primary rounded-pill d-flex align-items-center" type="submit">
                      <i class="fa-solid fa-magnifying-glass me-2"></i>
                      <span>Search</span>
                    </button>
                  </form>
                </div>
              </div>

              <!-- Filter & Sort Below -->
              <div class="row g-2 align-items-center mb-3">
                <form method="GET" class="d-flex flex-wrap gap-2">
                  <!-- Filter and sort below -->
                  <div class="col-auto">
                    <select name="role" id="roleFilter" class="form-select rounded-pill" onchange="this.form.submit()">
                      <option value="">All Roles</option>
                      <?php
                        $roles = ['admin', 'adviser', 'masterteacher', 'principal', 'teacher'];
                        foreach ($roles as $r) {
                            $count = $roleData[$r] ?? 0;
                            $selected = ($role === $r) ? 'selected' : '';
                            $disabled = ($count == 0) ? 'disabled' : '';
                            echo "<option value=\"$r\" $selected $disabled>" . ucfirst($r) . "</option>";
                        }
                      ?>
                    </select>
                  </div>

                  <!-- Sort Dropdown -->
                  <div class="col-auto">
                    <select name="sort" id="sortSelect" class="form-select rounded-pill" onchange="this.form.submit()"
                      style="width: 200px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                      <option value="name-asc" <?= ($_GET['sort'] ?? '')=== 'name-asc' ? 'selected' : '' ?>>Sort by Name (A–Z)</option>
                      <option value="name-desc" <?= ($_GET['sort'] ?? '') === 'name-desc' ? 'selected' : '' ?>>Sort by Name (Z–A)</option>
                      <option value="email-asc" <?= ($_GET['sort'] ?? '') === 'email-asc' ? 'selected' : '' ?>>Sort by Email (A–Z)</option>
                      <option value="email-desc" <?= ($_GET['sort'] ?? '') === 'email-desc' ? 'selected' : '' ?>>Sort by Email (Z–A)</option>
                      <option value="created-asc" <?= ($_GET['sort'] ?? '') === 'created-asc' ? 'selected' : '' ?>>Date Created (Oldest)</option>
                      <option value="created-desc" <?= ($_GET['sort'] ?? '') === 'created-desc' ? 'selected' : '' ?>>Date Created (Newest)</option>
                      <option value="modified-asc" <?= ($_GET['sort'] ?? '') === 'modified-asc' ? 'selected' : '' ?>>Date Modified (Oldest)</option>
                      <option value="modified-desc" <?= ($_GET['sort'] ?? '') === 'modified-desc' ? 'selected' : '' ?>>Date Modified (Newest)</option>
                    </select>
                  </div>
                </form>
              </div>

              <!-- Action Buttons -->
                <div class="action-buttons mb-3">
                    <button id="toggleEditMode" type="button" class="btn btn-dark rounded-pill">
                    <i class="fas fa-edit me-1"></i> <span>Edit</span>
                </button>

                <button type="button" class="btn btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#createUserModal">
                  <i class="fas fa-plus"></i> Create
                </button>
              </div>
            </div>
            
            <!-- Alert Container -->
            <div id="alertContainer"></div>
            

            <!-- Table scrollable container -->
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
              <table class="table table-hover table-bordered mb-0">
                <thead class="table-light sticky-top top-0">
                  <tr>
                    <th scope="col" class="edit-checkbox-column d-none">Select</th>
                    <th scope="col" class="d-none">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Role</th>
                    <th scope="col">Email</th>
                    <th scope="col">Created</th>
                    <th scope="col">Updated</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($accounts)): ?>
                    <?php foreach ($accounts as $account): ?>
                      <tr>
                        <td class="edit-checkbox-column d-none">
                          <input type="checkbox" class="row-checkbox" value="<?= htmlspecialchars($account['user_id']) ?>">
                        </td>
                        <td class="d-none"><?= htmlspecialchars($account['user_id']) ?></td>
                        <td><?= htmlspecialchars($account['fullname']) ?></td>
                        <td><?= htmlspecialchars($account['role']) ?></td>
                        <td><?= htmlspecialchars($account['email']) ?></td>
                        <td><?= htmlspecialchars($account['created_at']) ?></td>
                        <td><?= htmlspecialchars($account['updated_at']) ?></td>
                        <td><button class="btn btn-sm btn-success" disabled>Activate</button></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>

                  <!-- Always render the no-results row -->
                  <tr class="no-results" style="display: none;">
                    <td colspan="8" class="text-center text-danger table-row">User not found.</td>
                  </tr>

                </tbody>
              </table>
            </div>

              <!-- Edit account confirmation -->
              <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="editModalLabel">Edit Account</h5>
                    </div>
                    <div class="modal-body">
                      Are you sure you want to edit this account?
                    </div>
                    <div class="modal-footer">
                      <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-success" id="proceedEdit">Proceed</button>
                    </div>
                  </div>
                </div>
              </div>

            <!-- Pagination -->
            
          </div>
        </div>
      </div>
    </div>

</div>


<!-- SVG Icons -->
<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
  <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM6.97 
             11.03a.75.75 0 0 0 1.07 0l3.992-3.992a.75.75 
             0 1 0-1.06-1.06L7.5 9.44 6.03 
             7.97a.75.75 0 1 0-1.06 1.06l2 2z"/>
  </symbol>
  <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M8.982 1.566a1.13 1.13 0 0 
      0-1.964 0L.165 13.233c-.457.778.091 1.767.982 
      1.767h13.707c.89 0 1.438-.99.982-1.767L8.982 
      1.566zM8 5c.535 0 .954.462.9.995l-.35 
      3.507a.552.552 0 0 1-1.1 0L7.1 
      5.995A.905.905 0 0 1 8 
      5zm.002 6a1 1 0 1 1-2.002 
      0 1 1 0 0 1 2.002 0z"/>
  </symbol>
</svg>





<!-- Main script block for filters and search -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const sortSelect = document.getElementById('sortSelect');
    const roleFilter = document.getElementById('roleFilter');
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');

    // Handle sort dropdown change
    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        const sort = this.value;
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sort);
        window.location.href = url.toString();
      });
    }

    // Handle role filter dropdown change
    if (roleFilter) {
      roleFilter.addEventListener('change', function () {
        const role = this.value;
        const url = new URL(window.location.href);
        url.searchParams.set('role', role);
        window.location.href = url.toString();
      });
    }

    // Handle search form submission (client-side filtering)
    if (searchForm && searchInput) {
      searchForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const query = searchInput.value.trim().toLowerCase();
        const allRows = document.querySelectorAll("tbody tr");
        const noResultRow = document.querySelector("tbody tr.no-results");

        let found = false;

        allRows.forEach(row => {
          if (row.classList.contains("no-results")) return;

          const cells = row.querySelectorAll("td");
          const name = cells[2]?.textContent.toLowerCase().trim() || "";
          const role = cells[3]?.textContent.toLowerCase().trim() || "";
          const email = cells[4]?.textContent.toLowerCase().trim() || "";

          if (
            name.includes(query) ||
            role.includes(query) ||
            email.includes(query)
          ) {
            row.style.display = "";
            found = true;
          } else {
            row.style.display = "none";
          }
        });

        // Show/hide "no results" row
        if (noResultRow) {
          if (query !== "" && !found) {
            noResultRow.style.display = "table-row";
            noResultRow.querySelector("td").textContent = `User with "${query}" not found.`;
          } else {
            noResultRow.style.display = "none";
          }
        }
      });
    }
  });
</script>

<!-- Fix for page cache issue (prevents back button from showing outdated data) -->
<script>
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
</script>

<!-- Bootstrap Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- External Custom Scripts (for editing and creating accounts) -->
<script src="../../assets/js/admin/edit_acc.js"></script>
<script src="../../assets/js/admin/create_acc.js"></script>



</body>
</html>
