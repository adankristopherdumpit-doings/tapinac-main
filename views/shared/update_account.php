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

// Allowed roles
$allowed_roles = ['admin', 'masterteacher', 'adviser', 'teacher', 'principal'];
$role = $_SESSION['role'] ?? '';

if (!in_array($role, $allowed_roles)) {
    header("Location: ../../security/unauthorized.php");
    exit();
}

// Sidebar path
$sidebar_path = "../../layoutnav/{$role}bar.php";
if (!file_exists($sidebar_path)) {
    $sidebar_path = "../../layoutnav/defaultbar.php";
}

// Determine role for navigation and greeting
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'] ?? '';
$role_label = ucfirst($role);

$header_color = ($role === 'masterteacher' || $role === 'principal' || $role === 'admin') ? '#1a1a1a' : '#44A344';

// Stored user info
include '../../database/db_connection.php';
// Get user info
$user_id = $_SESSION['user_id'] ?? 0;

$current_username = '';
$current_email = '';

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT u.username, t.email
        FROM users AS u
        INNER JOIN teachers AS t 
            ON u.id = t.id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($current_username, $current_email);
    $stmt->fetch();
    $stmt->close();
}



// Fetch current teacher name using MySQLi
$stmt = $conn->prepare("
    SELECT t.fname, t.mname, t.lname
    FROM teachers t
    INNER JOIN users u ON u.teacher_id = t.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$stmt->close();

$current_first_name  = $teacher['fname'] ?? '';
$current_middle_name = $teacher['mname'] ?? '';
$current_last_name   = $teacher['lname'] ?? '';




?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/png" href="../../assets/image/logo/logo.png" />
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/all_role_style/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <title>Update Account Information</title>


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

<!-- Include the correct sidebar -->
<?php include $sidebar_path; ?>

<div class="main-content">

  <!-- Sticky or Fixed Header -->
  <div style="
    position: sticky;
    top: 0;
    z-index: 999;
    background-color: <?php echo $header_color; ?>;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 70px;
  ">
    <h2 class="m-0" style="position: absolute; left: 50%; transform: translateX(-50%);">Update Account Information</h2>
    <span style="position: absolute; right: 50px;">
    <?php 
      if ($role === 'admin') {
          echo 'Hello Admin';
      } elseif (!empty($full_name)) {
          echo 'Hello ' . htmlspecialchars($role_label . ' ' . $full_name);
      }
      ?>
    </span>

  </div>

  <!-- Main Content Section -->
  <section class="update-section py-5">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

          <!-- Header -->
          <div class="mb-4 text-center">
            <h2 style="color: black;">Choose what you want to update below.</h2>
          </div>

          <!-- Nav Tabs -->
          <ul class="nav nav-tabs mb-4" id="updateTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="password-tab" data-bs-toggle="tab" data-bs-target="#passwordTab" type="button" role="tab">Change Password</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="username-tab" data-bs-toggle="tab" data-bs-target="#usernameTab" type="button" role="tab">Change Username</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#emailTab" type="button" role="tab">Change Email</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="name-tab" data-bs-toggle="tab" data-bs-target="#nameTab" type="button" role="tab">Change Name</button>
            </li>
          </ul>

          <!-- Tab Content -->
          <div class="tab-content" id="updateTabsContent">

            <!-- Password Tab -->
            <div class="tab-pane fade show active" id="passwordTab" role="tabpanel">
              <div id="passwordAlert"></div>
              <form class="ajax-form" data-action="/tapinac/views/shared/multirole_backend/update_credentials.php" data-type="password">
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3 position-relative">
                  <label for="currentPassword1" class="form-label">Current Password</label>
                  <input type="password" class="form-control pe-5" id="currentPassword1" name="current_password" required>
                  <i class="bi bi-eye-slash toggle-password" data-target="currentPassword1" style="position: absolute; top: 38px; right: 15px; cursor: pointer;"></i>
                </div>

                <div class="mb-3 position-relative">
                  <label for="newPassword1" class="form-label">New Password</label>
                  <input type="password" class="form-control pe-5" id="newPassword1" name="new_password" required>
                  <i class="bi bi-eye-slash toggle-password" data-target="newPassword1" style="position: absolute; top: 38px; right: 15px; cursor: pointer;"></i>
                </div>

                <div class="mb-3 position-relative">
                  <label for="confirmPassword1" class="form-label">Confirm New Password</label>
                  <input type="password" class="form-control pe-5" id="confirmPassword1" name="confirm_password" required>
                  <i class="bi bi-eye-slash toggle-password" data-target="confirmPassword1" style="position: absolute; top: 38px; right: 15px; cursor: pointer;"></i>
                </div>

                <div class="text-end">
                  <button type="button" class="btn btn-danger ms-2 cancel-edit-btn" style="display: none;">Cancel Changes</button>
                  <button type="submit" class="btn btn-success submit-btn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Update Password
                  </button>
                </div>
              </form>
            </div>

            <!-- Username Tab -->
            <div class="tab-pane fade" id="usernameTab" role="tabpanel">
              <div id="usernameAlert"></div>
              <form class="ajax-form" data-action="/tapinac/views/shared/multirole_backend/update_credentials.php" data-type="username">
                <input type="hidden" name="action" value="change_username">

                <div class="mb-3">
                  <label for="newUsername" class="form-label">New Username</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="newUsername" 
                    name="new_username" 
                    value="<?php echo htmlspecialchars($current_username); ?>" 
                    required
                  >
                </div>


                <div class="mb-3 position-relative">
                  <label for="currentPassword2" class="form-label">Current Password</label>
                  <input type="password" class="form-control pe-5" id="currentPassword2" name="current_password" required>
                  <i class="bi bi-eye-slash toggle-password" data-target="currentPassword2" style="position: absolute; top: 38px; right: 15px; cursor: pointer;"></i>
                </div>

                <div class="text-end">
                  <button type="button" class="btn btn-danger ms-2 cancel-edit-btn" style="display: none;">Cancel Changes</button>
                  <button type="submit" class="btn btn-success submit-btn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Update Username
                  </button>
                </div>
              </form>
            </div>

            <!-- Email Tab -->
            <div class="tab-pane fade" id="emailTab" role="tabpanel">
              <div id="emailAlert"></div>
              <form class="ajax-form" data-action="/tapinac/views/shared/multirole_backend/update_credentials.php" data-type="email">
                <input type="hidden" name="action" value="change_email">

                <div class="mb-3">
                  <label for="newEmail" class="form-label">New Email</label>
                  <input 
                    type="email" 
                    class="form-control" 
                    id="newEmail" 
                    name="new_email" 
                    value="<?php echo htmlspecialchars($current_email); ?>" 
                    required
                  >
                </div>




                <div class="mb-3 position-relative">
                  <label for="currentPassword3" class="form-label">Current Password</label>
                  <input type="password" class="form-control pe-5" id="currentPassword3" name="current_password" required>
                  <i class="bi bi-eye-slash toggle-password" data-target="currentPassword3" style="position: absolute; top: 38px; right: 15px; cursor: pointer;"></i>
                </div>

                <div class="text-end">
                  <button type="button" class="btn btn-danger ms-2 cancel-edit-btn" style="display: none;">Cancel Changes</button>
                  <button type="submit" class="btn btn-success submit-btn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Update Email
                  </button>
                </div>
              </form>
            </div>
            <!-- Name Tab -->
            <div class="tab-pane fade" id="nameTab" role="tabpanel">
              <div id="nameAlert"></div>
              <form class="ajax-form" data-action="/tapinac/views/shared/multirole_backend/update_credentials.php" data-type="name">
                <input type="hidden" name="action" value="change_name">

                <div class="mb-3">
                  <label for="firstName" class="form-label">First Name</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="firstName" 
                    name="first_name" 
                    value="<?php echo htmlspecialchars($current_first_name); ?>" 
                    required
                  >
                </div>

                <div class="mb-3">
                  <label for="middleName" class="form-label">Middle Name</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="middleName" 
                    name="middle_name" 
                    value="<?php echo htmlspecialchars($current_middle_name); ?>"
                  >
                </div>

                <div class="mb-3">
                  <label for="lastName" class="form-label">Last Name</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="lastName" 
                    name="last_name" 
                    value="<?php echo htmlspecialchars($current_last_name); ?>" 
                    required
                  >
                </div>

                <div class="mb-3 position-relative">
                  <label for="currentPassword4" class="form-label">Current Password</label>
                  <input type="password" class="form-control pe-5" id="currentPassword4" name="current_password" required>
                  <i class="bi bi-eye-slash toggle-password" data-target="currentPassword4" style="position: absolute; top: 38px; right: 15px; cursor: pointer;"></i>
                </div>

                <div class="text-end">
                  <button type="button" class="btn btn-danger ms-2 cancel-edit-btn" style="display: none;">Cancel Changes</button>
                  <button type="submit" class="btn btn-success submit-btn">
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    Update Name
                  </button>
                </div>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let isFormDirty = false;

  const tabButtons = document.querySelectorAll('#updateTabs button[data-bs-toggle="tab"]');

  document.querySelectorAll('.ajax-form').forEach(form => {
    const cancelBtn = form.querySelector('.cancel-edit-btn');

    // Detect changes in form fields
    form.querySelectorAll('input, textarea, select').forEach(input => {
      input.addEventListener('input', () => {
        if (!isFormDirty) {
          isFormDirty = true;
          disableOtherTabs(form);
          if (cancelBtn) cancelBtn.style.display = 'inline-block';
        }
      });
    });

    // Handle form submission
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      const submitBtn = form.querySelector('.submit-btn');
      const spinner = submitBtn.querySelector('.spinner-border');
      const formData = new FormData(form);
      const action = form.dataset.action;
      const type = form.dataset.type;
      const alertBoxId = `${type}Alert`;

      submitBtn.disabled = true;
      spinner.classList.remove('d-none');

      try {
        const response = await fetch(action, {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        showAlert(alertBoxId, result.message, result.type);

        if (result.type === 'success') {
            // Optional: show a message first
            showAlert(alertBoxId, result.message, 'success');

            // Refresh the page para makita agad ang updated username/email
            setTimeout(() => {
                location.reload();
            }, 500); // 0.5s delay para makita muna yung alert
        }

      } catch (error) {
        showAlert(alertBoxId, 'Something went wrong. Please try again.', 'danger');
      } finally {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
      }
    });

    // Handle cancel button
    cancelBtn?.addEventListener('click', () => {
      form.reset();
      hideCancelButton(form);
      enableAllTabs();
      isFormDirty = false;
    });
  });

  // Show alert
  function showAlert(id, message, type = 'success') {
    const alertBox = document.getElementById(id);
    if (alertBox) {
      alertBox.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }
  }

  // Disable other tabs
  function disableOtherTabs(currentForm) {
    const currentPane = currentForm.closest('.tab-pane');
    const currentId = currentPane?.id;

    tabButtons.forEach(btn => {
      const targetId = btn.getAttribute('data-bs-target')?.replace('#', '');
      if (targetId !== currentId) {
        btn.setAttribute('disabled', true);
      }
    });
  }

  // Enable all tabs
  function enableAllTabs() {
    tabButtons.forEach(btn => {
      btn.removeAttribute('disabled');
    });
  }

  // Hide cancel button
  function hideCancelButton(form) {
    const btn = form.querySelector('.cancel-edit-btn');
    if (btn) btn.style.display = 'none';
  }

  // Password toggle (eye icon)
  document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', () => {
      const targetId = icon.getAttribute('data-target');
      const passwordInput = document.getElementById(targetId);
      if (!passwordInput) return;

      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';

      icon.classList.toggle('bi-eye');
      icon.classList.toggle('bi-eye-slash');
    });
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
