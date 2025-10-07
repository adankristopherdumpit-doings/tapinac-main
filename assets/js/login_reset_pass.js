fetch('login.php', {
  method: 'POST',
  body: new FormData(document.getElementById('loginForm'))
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    if (data.force_reset) {
      const modal = new bootstrap.Modal(document.getElementById('forcePasswordModal'));
      modal.show();
    } else {
      window.location.href = 'dashboard.php';
    }
  } else {
    alert(data.message);
  }
});






document.getElementById('forcePasswordForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const newPassword = document.getElementById('newPassword').value;
  const confirmPassword = document.getElementById('confirmPassword').value;

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  fetch('../../update_password_reset.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ newPassword })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Password changed successfully.");
      window.location.href = 'dashboard.php';
    } else {
      alert(data.message);
    }
  });
});
