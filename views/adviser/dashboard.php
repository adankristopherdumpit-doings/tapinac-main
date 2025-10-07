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

// Redirect if user is not an adviser
if (($_SESSION['role'] ?? '') !== 'adviser') {
    header("Location: ../security/unauthorized.php");
    exit();
}

try {
    // PDO used only for the count displayed earlier (keeps your original logic)
    $pdo = new PDO('mysql:host=localhost;dbname=grading_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$totalSubjects = 0;
$userId = intval($_SESSION['user_id']);
$stmt = $pdo->query("SELECT COUNT(*) FROM subject_assignments WHERE teacher_id = " . $userId);
if ($stmt) {
    $count = $stmt->fetchColumn();
    $totalSubjects = ($count !== false) ? (int)$count : 0;
}

// include mysqli connection used by many of your existing shared endpoints
include '../../database/db_connection.php'; // must define $conn (mysqli)

// Fetch the adviser assigned section (server-side). This is critical for the modal to work.
$grade_level_id = 0;
$section_id = 0;
$section_name = null;

if (isset($conn) && $userId > 0) {
    $ps = $conn->prepare("SELECT id, grade_level_id, section_name FROM sections WHERE teacher_id = ?");
    $ps->bind_param("i", $userId);
    $ps->execute();
    $gres = $ps->get_result();
    if ($gres && $row = $gres->fetch_assoc()) {
        $section_id = (int)$row['id'];
        $grade_level_id = (int)$row['grade_level_id'];
        $section_name = $row['section_name'];
    }
    $ps->close();
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
    <?php include '../../layoutnav/adviserbar.php'; ?>

    <div class="main-content">
        <div class="header-bar">
            <h2>Dashboard</h2>
            <?php if (isset($_SESSION['full_name'])):?>
            <span class="greeting">Hello Adviser <?= htmlspecialchars($_SESSION['full_name']); ?><small>, Advisory class of <?= htmlspecialchars($section_name) ?></small></span>
            <?php endif; ?>
        </div>

        <div class="container-fluid p-4" style="min-height: calc(100vh - 70px);">

            <!-- <?php if ($section_name): ?>
                <div class="mt-1"><small>Your assigned section: <strong><?= htmlspecialchars($section_name) ?></strong></small></div>
            <?php endif; ?> -->

            <div class="row g-4" style="max-width: 900px; margin: 0 auto;">
                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-auto">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assignSubjectTeacherModal">
                            <i class="bi bi-plus-circle"></i> Assign Subject Teacher
                        </button>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-warning reassign-subject-teacher-btn">
                            <i class="bi bi-arrow-repeat me-2"></i> Re-Assign Subject Teacher
                        </button>
                    </div>
                </div>

                <!-- Statistic Cards -->
                <div class="col-12 col-md-6">
                    <div class="card text-white text-center" style="background-color: #3b6ef5;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Student</h6>
                            <p class="card-text fs-3">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card text-dark text-center" style="background-color: #ffde59;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Subject</h6>
                            <p class="card-text fs-3"><?= $totalSubjects == 0 ? '0' : htmlspecialchars($totalSubjects) ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card text-white text-center" style="background-color: #29a329;">
                        <div class="card-body p-4">
                            <h6 class="card-title">Total Pass</h6>
                            <p class="card-text fs-3">0</p>
                        </div>
                    </div>
                </div>
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

<!-- Single modal (fixed: no nested modal) -->
<div class="modal fade" id="assignSubjectTeacherModal" tabindex="-1" aria-labelledby="assignSubjectTeacherLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignSubjectTeacherLabel">Assign Subject Teacher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label for="modal_subject" class="form-label">Subject</label>
          <select id="modal_subject" name="subject_id" class="form-control">
            <option>Loading subjects...</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="modal_teacher" class="form-label">Assign Teacher</label>
          <select id="modal_teacher" name="teacher_id" class="form-control">
            <option>Loading teachers...</option>
          </select>
        </div>

        <div id="assign_msg" style="display:none;" class="alert"></div>
      </div>

      <div class="modal-footer">
        <button type="button" id="confirmAssignSubjectBtn" class="btn btn-primary">Confirm</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<!-- Re-Assign Subject Teacher Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" role="dialog" aria-labelledby="reassignModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="reassignForm">
        <div class="modal-header">
          <h5 class="modal-title" id="reassignModalLabel">Re-Assign Subject Teacher</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="reassign_teacher_select">Select teacher to re-assign</label>
            <select id="reassign_teacher_select" class="form-control" name="teacher_id" required>
              <!-- options loaded from adviser/get_teachers.php -->
            </select>
          </div>

          <div id="reassign_subjects_container"></div>

          <div id="reassign_alert" class="text-danger mt-2" style="display:none;">Choose a subject to re-assigns</div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button id="reassign_confirm_btn" type="submit" class="btn btn-primary">Confirm Re-assignment</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Expose adviser section & grade to JS -->
<script>
  var advisorGradeId = <?= json_encode($grade_level_id) ?>;
  var advisorSectionId = <?= json_encode($section_id) ?>;
</script>

<!-- Bootstrap Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // If user clicks back, force page to reload from server
    window.addEventListener("pageshow", function(event) {
        if (event.persisted) window.location.reload();
    });
</script>

<!-- jQuery (used by the modal JS below) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function () {
  // sanity check: if variables are not set, warn in console but we keep behavior graceful
  if (typeof advisorGradeId === 'undefined' || typeof advisorSectionId === 'undefined') {
    console.warn('advisorGradeId / advisorSectionId are not defined. Make sure server-side assigned section exists.');
  }

  function loadSubjects() {
    $("#modal_subject").html('<option>Loading subjects...</option>');
    // your get_subjects.php expects POST and returns OPTION HTML — keep that behavior
    $.post('get_subjects.php', { grade_level_id: advisorGradeId }, function(html) {
      $("#modal_subject").html(html);
    }).fail(function() {
      $("#modal_subject").html('<option disabled>Error loading subjects</option>');
    });
  }

  function loadTeachers() {
    $("#modal_teacher").html('<option>Loading teachers...</option>');
    $.post('get_teachers.php', {}, function(html) {
      $("#modal_teacher").html(html);
    }).fail(function() {
      $("#modal_teacher").html('<option disabled>Error loading teachers</option>');
    });
  }

  // load dropdowns when modal opens
  $('#assignSubjectTeacherModal').on('show.bs.modal', function () {
    $("#assign_msg").hide().removeClass('alert-success alert-danger').text('');
    loadSubjects();
    loadTeachers();
  });

  // Confirm action
  $('#confirmAssignSubjectBtn').on('click', function () {
    var subjectId = $('#modal_subject').val();
    var teacherId = $('#modal_teacher').val();

    if (!subjectId || !teacherId) {
      $('#assign_msg').show().addClass('alert-danger').text('Please choose both subject and teacher.');
      return;
    }

    $('#confirmAssignSubjectBtn').prop('disabled', true);
    $.post('assign_subject_teacher.php', {
      subject_id: subjectId,
      teacher_id: teacherId
    }, function(response) {
      try {
        var obj = (typeof response === 'string') ? JSON.parse(response) : response;
      } catch (e) {
        $('#assign_msg').show().addClass('alert-danger').text('Unexpected response from server.');
        $('#confirmAssignSubjectBtn').prop('disabled', false);
        return;
      }

      if (obj.success) {
        $('#assign_msg').show().removeClass('alert-danger').addClass('alert-success').text(obj.message || 'Subject teacher successfully assign');
        setTimeout(function(){ location.reload(); }, 900);
      } else {
        $('#assign_msg').show().removeClass('alert-success').addClass('alert-danger').text(obj.message || 'Failed to assign');
        $('#confirmAssignSubjectBtn').prop('disabled', false);
      }
    }).fail(function(xhr){
      $('#assign_msg').show().removeClass('alert-success').addClass('alert-danger').text('Server error. Try again.');
      $('#confirmAssignSubjectBtn').prop('disabled', false);
    });
  });

});


$(function() {
  // load teacher options into select (get_teachers.php returns <option> HTML)
  function loadTeacherOptions(selectedId) {
    $.get('get_teachers.php', function(html) {
      $('#reassign_teacher_select').html(html);
      if (selectedId) $('#reassign_teacher_select').val(selectedId);
    });
  }

  // open modal on Re-Assign button click
  $(document).on('click', '.reassign-subject-teacher-btn', function() {
    var teacherId = $(this).data('teacher-id') || '';
    $('#reassign_subjects_container').empty();
    $('#reassign_alert').hide();
    loadTeacherOptions(teacherId);
    $('#reassignModal').modal('show');

    // if teacherId preselected, trigger change after load completes
    if (teacherId) {
      // wait a bit to allow loadTeacherOptions to set options
      setTimeout(function() {
        $('#reassign_teacher_select').val(teacherId).trigger('change');
      }, 150);
    }
  });

  // when teacher changes: fetch assigned and available
  $('#reassign_teacher_select').on('change', function() {
    var teacherId = $(this).val();
    var $container = $('#reassign_subjects_container');
    $container.empty();
    $('#reassign_alert').hide();

    if (!teacherId) return;

    $.getJSON('fetch_teacher_subjects.php', { teacher_id: teacherId }, function(resp) {
      if (resp.error) {
        $container.append('<div class="alert alert-danger">' + resp.error + '</div>');
        return;
      }
      var assigned = resp.assigned || [];
      var available = resp.available || {};
      var curMap = resp.currentSubjectsMap || {};

      if (assigned.length === 0) {
        $container.append('<div class="alert alert-info">This teacher currently has no assigned subjects for the active academic year.</div>');
        return;
      }

      assigned.forEach(function(asg, idx) {
        var gl = asg.grade_level_id || 0;
        var opts = '';
        opts += '<option value="">-- Select subject --</option>';
        opts += '<option value="remove">Remove</option>';
        // add current subject as selected choice
        opts += '<option value="'+asg.subject_id+'" selected>' + asg.subject_name + ' (current)</option>';
        // add available subjects for same grade level
        var av = available[gl] || [];
        av.forEach(function(s) {
          if (String(s.id) !== String(asg.subject_id)) {
            opts += '<option value="'+s.id+'">'+s.subject_name+'</option>';
          }
        });

        var block = $('<div class="form-group assigned-subject-block"></div>');
        block.append('<label>Assigned Subject #' + (idx+1) + '</label>');
        var select = $('<select class="form-control subject-select" required></select>');
        select.attr('data-assignment-id', asg.assignment_id);
        select.attr('data-original-subject-id', asg.subject_id);
        select.html(opts);
        block.append(select);
        $container.append(block);
      });
    }).fail(function() {
      $container.append('<div class="alert alert-danger">Failed to fetch data.</div>');
    });
  });

  // submit reassign form
  $('#reassignForm').on('submit', function(e) {
    e.preventDefault();
    $('#reassign_alert').hide();
    var teacherId = $('#reassign_teacher_select').val();
    if (!teacherId) {
      $('#reassign_alert').text('Please select a teacher.').show();
      return;
    }

    var changes = [];
    var somethingChanged = false;
    $('#reassign_subjects_container .subject-select').each(function() {
      var assignmentId = $(this).data('assignment-id');
      var original = String($(this).data('original-subject-id') || '');
      var val = String($(this).val() || '');

      if (val === '') {
        // untouched
        return;
      }
      if (val === 'remove') {
        if (original !== '') {
          somethingChanged = true;
          changes.push({ assignment_id: assignmentId, action: 'remove' });
        }
        return;
      }
      // new subject chosen
      if (val !== original) {
        somethingChanged = true;
        changes.push({ assignment_id: assignmentId, action: 'reassign', new_subject_id: parseInt(val,10) });
      }
    });

    if (!somethingChanged) {
      $('#reassign_alert').text('Choose a subject to re-assigns').show();
      return;
    }

    $.post('reassign_subject_teacher.php', { teacher_id: teacherId, changes: JSON.stringify(changes) }, function(resp) {
      if (resp.success) {
        $('#reassignModal').modal('hide');
        // refresh page or update UI row
        location.reload();
      } else {
        alert(resp.message || 'Failed to reassign');
      }
    }, 'json').fail(function(xhr) {
      alert('Server error: ' + xhr.responseText);
    });
  });

});

function loadTeacherOptions(selectedId) {
  $.get('get_teachers.php')
    .done(function(html) {
      console.log('get_teachers.php response:', html); // <- look at this in console
      $('#reassign_teacher_select').html(html);
      if (selectedId) $('#reassign_teacher_select').val(selectedId);
    })
    .fail(function(xhr) {
      console.error('Failed to load get_teachers.php', xhr.status, xhr.responseText);
      $('#reassign_teacher_select').html('<option disabled>Error loading teachers</option>');
    });
}

</script>

</body>
</html>
