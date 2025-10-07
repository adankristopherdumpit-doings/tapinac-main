<?php
session_start();

if (isset($_SESSION['temp_user_id'])) {
    // Just stay on login page — modal will handle reset
}


// Determine if user is logged in
$forceReset = isset($_SESSION['temp_user_id']);
$isLoggedIn = isset($_SESSION['user_id']) && !isset($_SESSION['force_password_reset']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/login.css">
  <link rel="icon" type="image/png" href="assets/image/logo/logo.png" />
</head>
<body style="background: linear-gradient(135deg, #44A344, #7ED957); min-height: 100vh;">


<style>
.alert {
    margin-top: 1rem;
}


.password-toggle {
  right: 1rem;
  top: 73%;
  transform: translateY(-50%);
  cursor: pointer;
  display: block; /* make it visible */
  position: absolute; /* ensure it sits correctly in input */
}





.toggle-password-icon {
  position: absolute;
  right: 1rem;
  top: 65%;             
  transform: translateY(-35%);
  cursor: pointer;
  color: #6c757d;
}




/* Password toggle icon for force reset modal */
.force-password-toggle {
  position: absolute;
  right: 1rem;
  top: 43%;
  transform: translateY(-35%);
  cursor: pointer;
  color: #6c757d; /* gray like bootstrap text-muted */
}



</style>




<section class="vh-100 custom-bg">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col-auto">
        <div class="card text-dark bg-white" style="border-radius: 1rem; width: 350px;">
          <div class="card-body p-4 text-center d-flex flex-column justify-content-between">

            <?php if (!$isLoggedIn): ?>
            <!-- LOGIN FORM -->
            <form id="loginForm" class="d-flex flex-column justify-content-between h-100">
              <div id="alertBox" class="d-none"></div>
              <div>
                <div class="mb-2"><i class="bi bi-person-circle" style="font-size: 3rem;"></i></div>
                <h2 class="fw-bold mb-3 text-uppercase">Login</h2>

                <div class="form-outline mb-4 input-group">
                  <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                  <input type="text" name="username" class="form-control" placeholder="Username" required />
                </div>

                <div class="form-outline mb-4 input-group position-relative">
                  <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                  <input type="password" name="password" id="typePasswordX" class="form-control pe-5" placeholder="Password" required />
                  <i class="bi bi-eye-slash" id="togglePassword"
                    style="cursor: pointer; position: absolute; top: 50%; right: 15px; transform: translateY(-50%); z-index: 10;"></i>
                </div>


                <div class="form-check text-start mb-3">
                  <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe" />
                  <label class="form-check-label" for="rememberMe">Remember Username</label>
                </div>

                <button class="btn btn-primary w-100 mb-1" type="submit">Login</button>

                <p class="small mb-3">
                  <a class="text-dark-50" href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                </p>
              </div>
            </form>
            <?php endif; ?>

            <!-- FORGOT PASSWORD MODAL -->
            <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true"
                data-bs-backdrop="static" data-bs-keyboard="false">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                  <form action="send_verification_code.php" method="POST">
                    
                    <!-- Header -->
                    <div class="modal-header bg-primary text-white rounded-top-4">
                      <h5 class="modal-title fw-bold w-100 text-start" id="forgotPasswordModalLabel">
                        <i class="bi bi-lock-fill me-2"></i>Forgot Password
                      </h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body px-4 text-center">

                      <p class="mb-4">Remember your password? <a href="login.php">Login Here</a>.</p>

                      <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger" id="resetAlert" role="alert">
                          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                      <?php endif; ?>

                      <!-- Email Input -->
                      <div class="mb-3">
                        <label for="forgotEmail" class="fw-semibold d-block text-center mb-2">
                          Enter your Email Address <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control rounded-3" name="email" id="forgotEmail" placeholder="Enter your email address" required>
                      </div>
                    </div>  

                    <!-- Footer -->
                    <div class="modal-footer border-0 pb-4">
                      <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">
                        Send Reset Link
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>


            <!-- OTP Modal: Enter 6-digit code -->
            <div class="modal fade" id="verificationCodeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="verificationCodeModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                  <form id="otpForm" method="POST">

                    <!-- Header -->
                    <div class="modal-header bg-primary text-white rounded-top-4">
                      <h5 class="modal-title fw-bold w-100 text-start" id="verificationCodeModalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i>Enter Verification Code
                      </h5>
                      <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body px-4 text-center">

                      <!-- Alert -->
                      <div id="otpAlert" class="alert alert-danger d-none mt-2" role="alert"></div>

                      <!-- OTP Input with Label -->
                      <div class="mb-3" style="max-width: 600px; margin: 0 auto;">
                        <label for="otpInput" class="fw-semibold d-block mb-2 text-center">
                          Enter OTP <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control text-center rounded-3 mx-auto" id="otpInput" name="code" maxlength="6" placeholder="Enter 6-digit code" required>
                      </div>

                      <!-- Resend Timer -->
                      <div class="mt-2">
                        <span id="resendTimer">You can resend code in 05:00</span>
                        <button type="button" class="btn btn-link d-none" id="resendBtn">Resend Code</button>
                      </div>

                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 pb-4">
                      <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">Verify</button>
                    </div>

                  </form>
                </div>
              </div>
            </div>


            <!-- Reset Password Modal -->
            <div class="modal fade" id="resetPasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                  <form action="reset_new_password_process.php" method="POST">

                    <!-- Header -->
                    <div class="modal-header bg-primary text-white rounded-top-4">
                      <h5 class="modal-title fw-bold w-100 text-center" id="resetPasswordModalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i>Reset Your Password
                      </h5>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body px-4 text-center">

                      <!-- Alert -->
                      <div id="alertReset" class="alert d-none text-center" role="alert"></div>

                      <!-- New Password -->
                      <div class="mb-3 position-relative" style="max-width: 450px; margin: 0 auto;">
                        <label for="new_password" class="fw-semibold d-block mb-2 text-center">
                          New Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" id="new_password" name="new_password" class="form-control rounded-3 pe-5" placeholder="Enter new password" required>
                        <i class="bi bi-eye-slash password-toggle" data-target="#new_password"
                          style="cursor:pointer; position:absolute; top:60; right:1rem; transform:translateY(-50%); font-size: 1.1rem;"></i>
                      </div>

                      <!-- Confirm Password -->
                      <div class="mb-3 position-relative" style="max-width: 450px; margin: 0 auto;">
                        <label for="confirm_password" class="fw-semibold d-block mb-2 text-center">
                          Confirm Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control rounded-3 pe-5" placeholder="Confirm password" required>
                        <i class="bi bi-eye-slash password-toggle" data-target="#confirm_password"
                          style="cursor:pointer; position:absolute; top:60; right:1rem; transform:translateY(-50%); font-size: 1.1rem;"></i>
                      </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 pb-4">
                      <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">
                        Reset Password
                      </button>
                    </div>

                  </form>
                </div>
              </div>
            </div>


            <!-- FORCE PASSWORD RESET MODAL -->
            <div class="modal fade" id="forcePasswordModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
              <div class="modal-dialog modal-dialog-centered">
                <form id="resetForm" method="POST" class="w-100">
                  <div class="modal-content border-0 shadow-lg rounded-4">

                    <!-- Header with X -->
                    <div class="modal-header bg-primary text-white rounded-top-4">
                      <h5 class="modal-title fw-bold">
                        <i class="bi bi-shield-lock-fill me-2"></i>Change Your Password
                      </h5>
                      <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body px-4">

                      <!-- Alert Container -->
                      <div id="forceAlert" class="alert d-none text-center" role="alert"></div>

                      <!-- New Password -->
                      <div class="mb-3">
                        <label for="newPassword" class="col-sm-4 col-form-label fw-semibold">
                          New Password <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                          <input type="password" class="form-control rounded-3 pe-5" id="newPassword" placeholder="Enter new password" required>
                          <i class="bi bi-eye-slash force-password-toggle" id="toggleNewPassword" style="cursor:pointer; position:absolute; top:50%; right:1rem; transform:translateY(-50%);"></i>
                        </div>
                      </div>

                      <!-- Confirm Password -->
                      <div class="mb-3">
                        <label for="confirmPassword" class="form-label fw-semibold">
                          Confirm Password <span class="text-danger">*</span> 
                        </label>
                        <div class="position-relative">
                          <input type="password" class="form-control rounded-3 pe-5" id="confirmPassword" placeholder="Confirm new password" required>
                          <i class="bi bi-eye-slash force-password-toggle" id="toggleConfirmPassword" style="cursor:pointer; position:absolute; top:50%; right:1rem; transform:translateY(-50%);"></i>
                        </div>
                      </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-0 pb-4">
                      <button type="submit" class="btn btn-primary w-100 rounded-3 fw-semibold">
                        Done
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>



            <!-- ALREADY LOGGED IN MODAL -->
            <?php if ($isLoggedIn): ?>
              <?php
              $homepage = 'index.php';
              switch ($_SESSION['role'] ?? '') {
                case 'admin': $homepage = 'views/admin/dashboard.php'; break;
                case 'masterteacher': $homepage = 'views/masterteacher/dashboard.php'; break;
                case 'adviser': $homepage = 'views/adviser/dashboard.php'; break;
                case 'teacher': $homepage = 'views/teacher/dashboard.php'; break;
                case 'principal': $homepage = 'views/principal/dashboard.php'; break;
              }
              ?>
              <div class="modal fade" id="alreadyLoggedInModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">You’re Currently Logged In</h5></div>
                    <div class="modal-body"><p>Do you want to logout or go back?</p></div>
                    <div class="modal-footer">
                      <a href="logout.php" class="btn btn-danger">Logout</a>
                      <a href="<?= $homepage ?>" class="btn btn-secondary">Go to Homepage</a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- Password Login Toggle Script -->
<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('typePasswordX');

  togglePassword.addEventListener('click', () => {
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      togglePassword.classList.remove('bi-eye-slash');
      togglePassword.classList.add('bi-eye');
    } else {
      passwordInput.type = 'password';
      togglePassword.classList.remove('bi-eye');
      togglePassword.classList.add('bi-eye-slash');
    }
  });
</script>


<!-- Toggle Password Script -->
<script>
  document.querySelectorAll('.password-toggle').forEach(icon => {
    const input = document.querySelector(icon.dataset.target);
    icon.addEventListener('click', () => {
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      }
    });
  });
</script>


<!-- Forgot Password Toggle Script -->
<script>
  // Toggle password visibility
  const toggleNew = document.getElementById('toggleNewPassword');
  const newPassword = document.getElementById('newPassword');

  toggleNew.addEventListener('click', () => {
    if (newPassword.type === 'password') {
      newPassword.type = 'text';
      toggleNew.classList.remove('bi-eye-slash');
      toggleNew.classList.add('bi-eye');
    } else {
      newPassword.type = 'password';
      toggleNew.classList.remove('bi-eye');
      toggleNew.classList.add('bi-eye-slash');
    }
  });

  const toggleConfirm = document.getElementById('toggleConfirmPassword');
  const confirmPassword = document.getElementById('confirmPassword');

  toggleConfirm.addEventListener('click', () => {
    if (confirmPassword.type === 'password') {
      confirmPassword.type = 'text';
      toggleConfirm.classList.remove('bi-eye-slash');
      toggleConfirm.classList.add('bi-eye');
    } else {
      confirmPassword.type = 'password';
      toggleConfirm.classList.remove('bi-eye');
      toggleConfirm.classList.add('bi-eye-slash');
    }
  });

  // Handle form submission via AJAX
  const resetForm = document.querySelector('#resetPasswordModal form');
  const alertBox = document.getElementById('alertReset');

  resetForm.addEventListener('submit', function(e) {
    e.preventDefault(); // prevent default form submit

    const formData = new FormData(resetForm);

    fetch('reset_new_password_process.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      alertBox.classList.remove('d-none');
      alertBox.classList.remove('alert-success', 'alert-danger');

      if (data.success) {
        alertBox.classList.add('alert-success');
        alertBox.textContent = data.message;

        // Optionally redirect after 2s
        setTimeout(() => {
          window.location.href = 'login.php';
        }, 2000);
      } else {
        alertBox.classList.add('alert-danger');
        alertBox.textContent = data.message;
      }
    })
    .catch(error => {
      alertBox.classList.remove('d-none');
      alertBox.classList.add('alert-danger');
      alertBox.textContent = 'An error occurred. Please try again.';
      console.error(error);
    });
  });
</script>


<!-- Alert for ForgotPassword -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    <?php if (isset($_SESSION['show_reset_modal']) && $_SESSION['show_reset_modal']): ?>
      var forgotModal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
      forgotModal.show();
      <?php unset($_SESSION['show_reset_modal']); ?>
    <?php endif; ?>

    // Hide the alert after 5 seconds
    var resetAlert = document.getElementById('resetAlert');
    if (resetAlert) {
      setTimeout(function () {
        resetAlert.classList.add('fade');
        resetAlert.style.opacity = '0';
        setTimeout(() => resetAlert.remove(), 500); // Remove it from DOM after fade out
      }, 5000); // 5 seconds
    }
  });
</script>




<script>
  document.addEventListener('DOMContentLoaded', () => {
    // OTP Input Navigation
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpForm = document.getElementById('otpForm');

    if (otpInputs.length > 0 && otpForm) {
      otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
          if (input.value.length === 1 && index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
          }
        });

        input.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace' && input.value === '' && index > 0) {
            otpInputs[index - 1].focus();
          }
        });
      });

      otpForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const code = [...otpInputs].map(input => input.value).join('');
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'code';
        hidden.value = code;
        otpForm.appendChild(hidden);

        const formData = new FormData(otpForm);

        fetch('process_verification.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const otpModal = bootstrap.Modal.getInstance(document.getElementById('verificationCodeModal'));
            otpModal?.hide();
            const resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            resetModal.show();
          } else {
            alert(data.message || 'Verification failed');
          }
        });
      });
    }

    // Show OTP Modal from Session
    <?php if (isset($_SESSION['show_otp_modal']) && $_SESSION['show_otp_modal']): ?>
    const otpModal = new bootstrap.Modal(document.getElementById('verificationCodeModal'));
    otpModal.show();
    <?php unset($_SESSION['show_otp_modal']); endif; ?>

    // Login and Reset Form
    const loginForm = document.getElementById('loginForm');
    const resetForm = document.getElementById('resetForm');
    const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
    const forceReset = <?= json_encode($forceReset) ?>;

    loginForm?.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(loginForm);

      fetch('login_process.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const normalizedRole = data.role?.toLowerCase().replace(/\s+/g, '');

          if (data.force_reset) {
            const resetModal = new bootstrap.Modal(document.getElementById('forcePasswordModal'));
            resetModal.show();
          } else {
            const validRoles = ['admin', 'masterteacher', 'adviser', 'principal', 'teacher'];
            if (validRoles.includes(normalizedRole)) {
              window.location.href = `views/${normalizedRole}/dashboard.php`;
            } else {
              showAlert('Unknown role. Please contact admin.', 'danger');
            }
          }
        } else {
          showAlert(data.message, 'danger');
        }
      })
      .catch(err => {
        console.error(err);
        showAlert('Login error occurred.', 'danger');
      });
    });

    resetForm?.addEventListener('submit', function (e) {
      e.preventDefault();

      const newPassword = document.getElementById('newPassword').value;
      const confirmPassword = document.getElementById('confirmPassword').value;

      if (newPassword !== confirmPassword) {
        showAlert('Passwords do not match.', 'danger', 'forceAlert'); // use forceAlert
        return;
      }

      fetch('update_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const redirectRole = data.role?.toLowerCase().replace(/\s+/g, '') || 'admin';
          showAlert('Password updated successfully. Redirecting...', 'success', 'forceAlert'); // use forceAlert
          setTimeout(() => {
            window.location.href = `views/${redirectRole}/dashboard.php`;
          }, 1500);
        } else {
          showAlert(data.message, 'danger', 'forceAlert'); // use forceAlert
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating the password.', 'danger', 'forceAlert'); // use forceAlert
      });
    });



    // Already logged in modal
    if (isLoggedIn) {
      const loggedInModal = new bootstrap.Modal(document.getElementById('alreadyLoggedInModal'));
      loggedInModal.show();
    }

    // Fix browser back navigation
    window.addEventListener('pageshow', event => {
      if (event.persisted) location.reload();
    });
  });





  function showAlert(message, type = 'danger', targetId = null) {
    // Determine which alert container to use
    let alertBox;
    if (targetId) {
      alertBox = document.getElementById(targetId);
    } else {
      // fallback: check if login form alert exists
      alertBox = document.getElementById('alertBox');
    }

    if (!alertBox) return;

    // Apply classes and text
    alertBox.textContent = message;
    alertBox.className = `alert alert-${type} text-center mt-2 mb-0`;
    alertBox.classList.remove('d-none');

    // Hide after 3 seconds
    setTimeout(() => {
      alertBox.classList.add('d-none');
    }, 3000);
  }

</script>



<script>
  document.addEventListener('DOMContentLoaded', () => {
    const otpForm = document.getElementById('otpForm');
    const otpInput = document.getElementById('otpInput');
    const otpAlert = document.getElementById('otpAlert');
    const resendBtn = document.getElementById('resendBtn');
    const resendTimer = document.getElementById('resendTimer');

    let timeLeft = 5 * 60;
    let countdown;

    function startCountdown() {
      resendBtn.classList.add('d-none');
      resendTimer.classList.remove('d-none');

      clearInterval(countdown);
      timeLeft = 5 * 60;

      countdown = setInterval(() => {
        const minutes = String(Math.floor(timeLeft / 60)).padStart(2, '0');
        const seconds = String(timeLeft % 60).padStart(2, '0');
        resendTimer.textContent = `You can resend code in ${minutes}:${seconds}`;

        if (timeLeft <= 0) {
          clearInterval(countdown);
          resendTimer.classList.add('d-none');
          resendBtn.classList.remove('d-none');
        } else {
          timeLeft--;
        }
      }, 1000);
    }

    // Initialize countdown on page load
    startCountdown();

    // Resend button click
    resendBtn.addEventListener('click', () => {
      fetch('resend_code.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showOtpAlert(data.message);
            otpInput.value = '';
            startCountdown(); // restart countdown
          } else {
            showOtpAlert(data.message || 'Failed to resend code.');
          }
        })
        .catch(() => showOtpAlert('Error resending code.'));
    });

    otpForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const code = otpInput.value.trim();

      if (!/^\d{6}$/.test(code)) {
        showOtpAlert('Enter a valid 6-digit code.');
        return;
      }

      const formData = new FormData();
      formData.append('code', code);

      fetch('process_verification.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('verificationCodeModal'));
            modal?.hide();

            // Show reset password modal
            const resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            resetModal.show();
          } else {
            showOtpAlert(data.message || 'Invalid verification code.');
          }
        })
        .catch(() => showOtpAlert('Error verifying code.'));
    });

    function showOtpAlert(msg) {
      otpAlert.textContent = msg;
      otpAlert.classList.remove('d-none');
      setTimeout(() => otpAlert.classList.add('d-none'), 3000);
    }
  });
</script>


<!-- JS Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/remember_me.js"></script>


</body>
</html>
