<?php
// admin.php
// Admin Dashboard for managing invitations and attendance

require_once 'config.php';
session_start();

// Initialize alerts
$alert = '';
$alertType = 'success';

// Handle unauthorized error parameter
if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
  $alert = "Akses ditolak: Anda tidak memiliki wewenang untuk tindakan tersebut.";
  $alertType = 'error';
}

// Authentication Handling
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
  unset($_SESSION['admin_logged']);
  unset($_SESSION['admin_username']);
  unset($_SESSION['admin_classroom']);
  session_destroy();
  header('Location: admin.php');
  exit;
}

if (isset($_POST['login'])) {
  $usernameInput = trim($_POST['username'] ?? '');
  $passwordInput = trim($_POST['password'] ?? '');

  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->execute([$usernameInput]);
  $user = $stmt->fetch();

  if ($user && password_verify($passwordInput, $user['password'])) {
    $_SESSION['admin_logged'] = true;
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_classroom'] = $user['classroom']; // Null if super admin
    header('Location: admin.php');
    exit;
  } else {
    $loginError = "Username atau password salah.";
  }
}

// Check Login Status
$isLogged = isset($_SESSION['admin_logged']);
$adminClassroom = $_SESSION['admin_classroom'] ?? null;
$isAdmin = ($adminClassroom === null);

// If not logged in, render Login View
if (!$isLogged):
?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Admin Login | GCP Award 2026</title>
    <link href="img/metschoo/Metschoo.png" rel="icon" />
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
      body {
        background-color: #1C3319;
        color: #ECE8D2;
        font-family: 'Poppins', sans-serif;
      }
    </style>
  </head>

  <body class="min-h-screen flex items-center justify-center p-5 selection:bg-[#E2C12F] selection:text-[#0D1F0C]">
    <div class="w-full max-w-md bg-[#4A2A12] border border-[#E2C12F]/25 rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">

      <!-- Ornamental border -->
      <div class="absolute inset-2 border border-[#E2C12F]/10 rounded-2xl pointer-events-none"></div>

      <div class="text-center mb-8 relative z-10">
        <img src="img/metschoo/Metschoo.png" alt="Logo" class="w-14 h-14 object-contain mx-auto mb-4" />
        <h1 class="font-display font-bold text-[#F5F0DC] text-xl uppercase tracking-wider">GCP Award 2026</h1>
        <p class="text-xs text-[#E2C12F] tracking-widest uppercase mt-1">Admin Dashboard Portal</p>
      </div>

      <?php if (!empty($loginError)): ?>
        <div class="bg-red-600/10 border border-red-600/30 text-red-500 text-xs rounded-xl p-3 mb-6 text-center">
          <?= htmlspecialchars($loginError) ?>
        </div>
      <?php endif; ?>

      <form action="admin.php" method="POST" class="space-y-5 relative z-10">
        <div>
          <label class="block text-[#F5F0DC]/70 text-xs font-semibold uppercase tracking-wider mb-2 px-1">Username</label>
          <input type="text" name="username" required placeholder="Enter username"
            class="w-full bg-[#0D1F0C] border border-[#E2C12F]/25 rounded-2xl px-4 py-3.5 text-[#F5F0DC] text-sm focus:border-[#E2C12F] focus:ring-1 focus:ring-[#E2C12F]/30 transition-all outline-none" />
        </div>

        <div>
          <label class="block text-[#F5F0DC]/70 text-xs font-semibold uppercase tracking-wider mb-2 px-1">Password</label>
          <input type="password" name="password" required placeholder="Enter password"
            class="w-full bg-[#0D1F0C] border border-[#E2C12F]/25 rounded-2xl px-4 py-3.5 text-[#F5F0DC] text-sm focus:border-[#E2C12F] focus:ring-1 focus:ring-[#E2C12F]/30 transition-all outline-none" />
        </div>

        <button type="submit" name="login" class="w-full bg-[#E2C12F] text-[#0D1F0C] font-bold py-4 rounded-2xl text-xs tracking-widest uppercase hover:bg-[#E2C12F]/90 active:scale-95 transition-all shadow-lg mt-2">
          LOG IN ADMIN
        </button>
      </form>
    </div>
  </body>

  </html>
<?php
  exit;
endif;

// ==========================================
// ADMIN LOGGED IN CODE BELOW
// ==========================================

// 1. Add student
if (isset($_POST['add_student'])) {
  $name = trim($_POST['name'] ?? '');
  $classroom = $isAdmin ? trim($_POST['classroom'] ?? '') : $adminClassroom;

  if (!empty($name) && !empty($classroom)) {
    try {
      $code = generateUniqueCode($pdo);
      $stmt = $pdo->prepare("INSERT INTO students (code, name, classroom) VALUES (?, ?, ?)");
      $stmt->execute([$code, $name, $classroom]);
      $alert = "Siswa '$name' berhasil ditambahkan dengan kode: $code";
      $alertType = 'success';
    } catch (PDOException $e) {
      $alert = "Gagal menambah siswa: " . $e->getMessage();
      $alertType = 'error';
    }
  } else {
    $alert = "Nama dan Kelas wajib diisi.";
    $alertType = 'error';
  }
}

// 2. Edit student
if (isset($_POST['edit_student'])) {
  $id = intval($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $classroom = $isAdmin ? trim($_POST['classroom'] ?? '') : $adminClassroom;
  $whatsapp = trim($_POST['whatsapp'] ?? '');
  $rsvp_status = trim($_POST['rsvp_status'] ?? 'Pending');
  $companion_type = trim($_POST['companion_type'] ?? 'none');

  // Verify authorization
  $authorized = true;
  if (!$isAdmin) {
    $check = $pdo->prepare("SELECT classroom FROM students WHERE id = ?");
    $check->execute([$id]);
    $studentClass = $check->fetchColumn();
    if ($studentClass !== $adminClassroom) {
      $authorized = false;
    }
  }

  if ($authorized && !empty($name) && !empty($classroom)) {
    try {
      $stmt = $pdo->prepare("UPDATE students SET name = ?, classroom = ?, whatsapp = ?, rsvp_status = ?, companion_type = ? WHERE id = ?");
      $stmt->execute([$name, $classroom, $whatsapp, $rsvp_status, $companion_type, $id]);
      $alert = "Data siswa '$name' berhasil diperbarui.";
      $alertType = 'success';
    } catch (PDOException $e) {
      $alert = "Gagal memperbarui data siswa: " . $e->getMessage();
      $alertType = 'error';
    }
  } else {
    $alert = "Akses ditolak atau data tidak lengkap.";
    $alertType = 'error';
  }
}

// 3. Bulk Import student
if (isset($_POST['bulk_import'])) {
  $bulkText = trim($_POST['bulk_text'] ?? '');
  $defaultClass = $isAdmin ? trim($_POST['default_classroom'] ?? 'Guest') : $adminClassroom;

  if (!empty($bulkText)) {
    $lines = explode("\n", $bulkText);
    $count = 0;
    $pdo->beginTransaction();
    try {
      foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $parts = explode("\t", $line);
        if (count($parts) < 2) {
          $parts = explode(",", $line);
        }

        $stdName = trim($parts[0]);
        $stdClass = $isAdmin ? (isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : $defaultClass) : $adminClassroom;

        if (!empty($stdName)) {
          $code = generateUniqueCode($pdo);
          $stmt = $pdo->prepare("INSERT INTO students (code, name, classroom) VALUES (?, ?, ?)");
          $stmt->execute([$code, $stdName, $stdClass]);
          $count++;
        }
      }
      $pdo->commit();
      $alert = "Berhasil mengimpor $count siswa/tamu.";
      $alertType = 'success';
    } catch (PDOException $e) {
      $pdo->rollBack();
      $alert = "Gagal mengimpor data: " . $e->getMessage();
      $alertType = 'error';
    }
  } else {
    $alert = "Mohon input data teks terlebih dahulu.";
    $alertType = 'error';
  }
}

// 4. Reset RSVP/Checkin
if (isset($_GET['action']) && $_GET['action'] === 'reset' && isset($_GET['id'])) {
  $id = intval($_GET['id']);

  $authorized = true;
  if (!$isAdmin) {
    $check = $pdo->prepare("SELECT classroom FROM students WHERE id = ?");
    $check->execute([$id]);
    $studentClass = $check->fetchColumn();
    if ($studentClass !== $adminClassroom) {
      $authorized = false;
    }
  }

  if ($authorized) {
    $stmt = $pdo->prepare("UPDATE students SET rsvp_status = 'Pending', companion_type = 'none', whatsapp = NULL, checked_in = 0, checked_in_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin.php');
    exit;
  } else {
    header('Location: admin.php?error=unauthorized');
    exit;
  }
}

// 5. Delete Student
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
  $id = intval($_GET['id']);

  $authorized = true;
  if (!$isAdmin) {
    $check = $pdo->prepare("SELECT classroom FROM students WHERE id = ?");
    $check->execute([$id]);
    $studentClass = $check->fetchColumn();
    if ($studentClass !== $adminClassroom) {
      $authorized = false;
    }
  }

  if ($authorized) {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin.php');
    exit;
  } else {
    header('Location: admin.php?error=unauthorized');
    exit;
  }
}

// 6. User Management Actions (Super Admin Only)
if ($isAdmin) {
  // Add user account
  if (isset($_POST['add_user'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    $newClassroom = trim($_POST['classroom'] ?? '');

    if (!empty($newUsername) && !empty($newPassword) && !empty($newClassroom)) {
      try {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, classroom) VALUES (?, ?, ?)");
        $stmt->execute([$newUsername, $hashed, $newClassroom]);
        $alert = "User Wali Kelas '$newUsername' berhasil dibuat untuk kelas $newClassroom.";
        $alertType = 'success';
      } catch (PDOException $e) {
        $alert = "Gagal membuat user: " . $e->getMessage();
        $alertType = 'error';
      }
    } else {
      $alert = "Semua field user wajib diisi.";
      $alertType = 'error';
    }
  }

  // Edit user account
  if (isset($_POST['edit_user'])) {
    $userId = intval($_POST['id'] ?? 0);
    $newUsername = trim($_POST['username'] ?? '');
    $newClassroom = trim($_POST['classroom'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');

    if (!empty($newUsername) && !empty($newClassroom) && $userId > 0) {
      try {
        // Check if target username already exists in another user account
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $check->execute([$newUsername, $userId]);
        if ($check->fetchColumn() > 0) {
          $alert = "Username '$newUsername' sudah digunakan oleh akun lain.";
          $alertType = 'error';
        } else {
          if (!empty($newPassword)) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, classroom = ? WHERE id = ?");
            $stmt->execute([$newUsername, $hashed, $newClassroom, $userId]);
          } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, classroom = ? WHERE id = ?");
            $stmt->execute([$newUsername, $newClassroom, $userId]);
          }

          // If editing ourselves, update session variables
          if (isset($_SESSION['admin_username']) && $_SESSION['admin_username'] === $newUsername) {
            $_SESSION['admin_classroom'] = $newClassroom;
          }

          $alert = "Akun '$newUsername' berhasil diperbarui.";
          $alertType = 'success';
        }
      } catch (PDOException $e) {
        $alert = "Gagal memperbarui akun: " . $e->getMessage();
        $alertType = 'error';
      }
    } else {
      $alert = "Field username dan kelas wajib diisi.";
      $alertType = 'error';
    }
  }

  // Delete user account
  if (isset($_GET['action']) && $_GET['action'] === 'delete_user' && isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);

    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $targetUsername = $stmt->fetchColumn();

    if ($targetUsername && $targetUsername !== ($_SESSION['admin_username'] ?? '') && $targetUsername !== 'admin') {
      $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
      $stmt->execute([$userId]);
      $alert = "User '$targetUsername' berhasil dihapus.";
      $alertType = 'success';
    } else {
      $alert = "Tidak dapat menghapus user admin default atau user Anda sendiri.";
      $alertType = 'error';
    }
  }
}

// -------------------------------------------------------------
// Fetch Stats (Restricted to classroom if Wali Kelas)
// -------------------------------------------------------------
$whereClause = "";
$statParams = [];
if (!$isAdmin) {
  $whereClause = " WHERE classroom = ?";
  $statParams = [$adminClassroom];
}

// Total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . $whereClause);
$stmt->execute($statParams);
$totalCount = $stmt->fetchColumn();

// Attending
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . ($whereClause ? $whereClause . " AND rsvp_status = 'Attending'" : " WHERE rsvp_status = 'Attending'"));
$stmt->execute($statParams);
$attendingCount = $stmt->fetchColumn();

// Absent
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . ($whereClause ? $whereClause . " AND rsvp_status = 'Absent'" : " WHERE rsvp_status = 'Absent'"));
$stmt->execute($statParams);
$absentCount = $stmt->fetchColumn();

// Pending
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . ($whereClause ? $whereClause . " AND rsvp_status = 'Pending'" : " WHERE rsvp_status = 'Pending'"));
$stmt->execute($statParams);
$pendingCount = $stmt->fetchColumn();

// Present (checked-in)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . ($whereClause ? $whereClause . " AND checked_in = 1" : " WHERE checked_in = 1"));
$stmt->execute($statParams);
$presentCount = $stmt->fetchColumn();

// Companion breakdown
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . ($whereClause ? $whereClause . " AND checked_in = 1 AND companion_type = 'parents'" : " WHERE checked_in = 1 AND companion_type = 'parents'"));
$stmt->execute($statParams);
$companionParents = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . ($whereClause ? $whereClause . " AND checked_in = 1 AND companion_type = 'sibling'" : " WHERE checked_in = 1 AND companion_type = 'sibling'"));
$stmt->execute($statParams);
$companionSibling = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM students" . ($whereClause ? $whereClause . " AND checked_in = 1 AND companion_type = 'none'" : " WHERE checked_in = 1 AND companion_type = 'none'"));
$stmt->execute($statParams);
$companionNone = $stmt->fetchColumn();

// -------------------------------------------------------------
// Get search and filters
// -------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$filterClass = trim($_GET['filter_class'] ?? '');
$filterRsvp = trim($_GET['filter_rsvp'] ?? '');
$filterCheckin = trim($_GET['filter_checkin'] ?? '');

$queryStr = "SELECT * FROM students WHERE 1=1";
$params = [];

if (!$isAdmin) {
  $queryStr .= " AND classroom = ?";
  $params[] = $adminClassroom;
} else {
  if ($filterClass !== '') {
    $queryStr .= " AND classroom = ?";
    $params[] = $filterClass;
  }
}

if ($search !== '') {
  $queryStr .= " AND (name LIKE ? OR code LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}
if ($filterRsvp !== '') {
  $queryStr .= " AND rsvp_status = ?";
  $params[] = $filterRsvp;
}
if ($filterCheckin !== '') {
  $queryStr .= " AND checked_in = ?";
  $params[] = ($filterCheckin === '1' ? 1 : 0);
}

$queryStr .= " ORDER BY classroom ASC, name ASC";
$stmt = $pdo->prepare($queryStr);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get list of distinct classes for filter dropdown
$classes = $pdo->query("SELECT DISTINCT classroom FROM students ORDER BY classroom ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch all user accounts (Super Admin only)
$allUsers = [];
if ($isAdmin) {
  $allUsers = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
}

// Build absolute base URL for invitation links
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Admin Dashboard | GCP Award 2026</title>
  <link href="img/metschoo/Metschoo.png" rel="icon" />
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
  <script id="tailwind-config">
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            secondary: "#E2C12F", // Bright Moss Yellow — hero accent
            primary: "#0D1F0C", // Near-black forest
            cardBg: "#4A2A12", // Rich warm brown
          },
          fontFamily: {
            display: ["Playfair Display", "serif"],
            body: ["Poppins", "sans-serif"],
          },
        },
      },
    };
  </script>
  <style>
    body {
      background-color: #0D1F0C;
      color: #F5F0DC;
      font-family: 'Poppins', sans-serif;
    }

    .custom-scrollbar::-webkit-scrollbar {
      height: 6px;
      width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
      background: rgba(13, 31, 12, 0.5);
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #E2C12F;
      border-radius: 3px;
    }
  </style>
</head>

<body class="min-h-screen flex flex-col selection:bg-secondary selection:text-primary">

  <!-- Top Nav -->
  <header class="border-b border-secondary/25 bg-[#0D1F0C]/95 sticky top-0 z-40 backdrop-blur-md">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <img src="img/metschoo/Metschoo.png" alt="Logo" class="w-8 h-8 object-contain" />
        <div>
          <h1 class="font-display font-bold text-[#F5F0DC] text-base tracking-wide leading-none">GCP Award 2026</h1>
          <span class="text-[9px] uppercase tracking-widest text-secondary font-semibold">
            <?= $isAdmin ? 'Super Admin Panel' : 'Wali Kelas ' . htmlspecialchars($adminClassroom) ?>
          </span>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <?php if ($isAdmin): ?>
          <a href="checkin.php" target="_blank" class="text-xs font-semibold bg-secondary text-primary px-3 py-1.5 rounded-lg hover:scale-105 transition-transform flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">qr_code_scanner</span> Buka Scanner
          </a>
        <?php endif; ?>
        <a href="admin.php?action=logout" class="text-xs font-semibold text-red-500 hover:text-red-400 flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">logout</span> Logout
        </a>
      </div>
    </div>
  </header>

  <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-6">

    <!-- Notifications -->
    <?php if (!empty($alert)): ?>
      <div class="border rounded-2xl p-4 flex items-center gap-3 <?= $alertType === 'success' ? 'bg-[#25D366]/10 border-[#25D366]/30 text-[#25D366]' : 'bg-red-600/10 border-red-600/30 text-red-500' ?>">
        <span class="material-symbols-outlined"><?= $alertType === 'success' ? 'check_circle' : 'error' ?></span>
        <p class="text-xs font-medium"><?= htmlspecialchars($alert) ?></p>
      </div>
    <?php endif; ?>

    <!-- Tabs Navigation (Super Admin Only) -->
    <?php if ($isAdmin): ?>
      <div class="flex border-b border-secondary/25 gap-2">
        <button id="btn-tab-students" onclick="switchTab('students')" class="px-5 py-3 border-b-2 border-secondary font-display font-bold text-sm text-secondary hover:text-secondary transition-all outline-none">
          Siswa & Undangan
        </button>
        <button id="btn-tab-users" onclick="switchTab('users')" class="px-5 py-3 border-b-2 border-transparent font-display text-sm text-[#F5F0DC]/60 hover:text-[#F5F0DC] transition-all outline-none">
          Kelola Wali Kelas
        </button>
      </div>
    <?php endif; ?>

    <!-- STUDENTS TAB CONTAINER -->
    <div id="container-tab-students" class="space-y-6">

      <!-- 1. Statistics Cards -->
      <section class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Card 1: Total -->
        <div class="bg-[#4A2A12] border border-secondary/25 rounded-2xl p-4 shadow-lg text-center">
          <p class="text-[10px] uppercase tracking-wider text-[#F5F0DC]/50 font-medium">Total Undangan</p>
          <p class="text-3xl font-display font-bold text-[#F5F0DC] mt-1"><?= $totalCount ?></p>
        </div>
        <!-- Card 2: Attending -->
        <div class="bg-[#4A2A12] border border-[#25D366]/20 rounded-2xl p-4 shadow-lg text-center">
          <p class="text-[10px] uppercase tracking-wider text-[#F5F0DC]/50 font-medium">Hadir (RSVP)</p>
          <p class="text-3xl font-display font-bold text-[#25D366] mt-1"><?= $attendingCount ?></p>
        </div>
        <!-- Card 3: Absent -->
        <div class="bg-[#4A2A12] border border-red-600/20 rounded-2xl p-4 shadow-lg text-center">
          <p class="text-[10px] uppercase tracking-wider text-[#F5F0DC]/50 font-medium">Absen (RSVP)</p>
          <p class="text-3xl font-display font-bold text-red-400 mt-1"><?= $absentCount ?></p>
        </div>
        <!-- Card 4: Pending -->
        <div class="bg-[#4A2A12] border border-[#E2C12F]/25 rounded-2xl p-4 shadow-lg text-center">
          <p class="text-[10px] uppercase tracking-wider text-[#F5F0DC]/50 font-medium">Pending (RSVP)</p>
          <p class="text-3xl font-display font-bold text-[#E2C12F] mt-1"><?= $pendingCount ?></p>
        </div>
        <!-- Card 5: Present Checked In -->
        <div class="bg-[#4A2A12] border border-secondary/35 rounded-2xl p-4 shadow-lg text-center col-span-2 md:col-span-1">
          <p class="text-[10px] uppercase tracking-wider text-secondary font-semibold">Telah Check-In (Hari H)</p>
          <p class="text-3xl font-display font-bold text-secondary mt-1"><?= $presentCount ?></p>
          <!-- Companion Substats -->
          <div class="text-[8px] text-[#F5F0DC]/50 mt-1 flex justify-center gap-2">
            <span>Ortu: <b><?= $companionParents ?></b></span>
            <span>Saudara: <b><?= $companionSibling ?></b></span>
            <span>Sendiri: <b><?= $companionNone ?></b></span>
          </div>
        </div>
      </section>

      <!-- 2. Management Controls -->
      <?php if ($isAdmin): ?>
        <section class="grid md:grid-cols-2 gap-6">

          <!-- Add Single Student Form -->
          <div class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-5 shadow-xl">
            <h3 class="font-display font-semibold text-[#F5F0DC] mb-4 flex items-center gap-2 border-b border-[#F5F0DC]/5 pb-2">
              <span class="material-symbols-outlined text-secondary text-base">person_add</span>
              Tambah Siswa / Undangan Baru
            </h3>
            <form action="admin.php" method="POST" class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Nama Lengkap</label>
                <input type="text" name="name" required placeholder="Nama Siswa"
                  class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
              </div>
              <div>
                <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Kelas / Afiliasi</label>
                <?php if ($isAdmin): ?>
                  <input type="text" name="classroom" required placeholder="e.g. XII PPLG 1" list="classes-list"
                    class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
                  <datalist id="classes-list">
                    <?php foreach ($classes as $cl): ?>
                      <option value="<?= htmlspecialchars($cl) ?>">
                      <?php endforeach; ?>
                  </datalist>
                <?php else: ?>
                  <input type="text" value="<?= htmlspecialchars($adminClassroom) ?>" disabled
                    class="w-full bg-[#0D1F0C]/50 border border-secondary/15 rounded-xl px-3.5 py-2 text-[#F5F0DC]/50 text-xs outline-none cursor-not-allowed" />
                <?php endif; ?>
              </div>
              <div class="flex items-end">
                <button type="submit" name="add_student" class="w-full bg-secondary text-primary font-bold py-2.5 rounded-xl text-xxs tracking-widest uppercase hover:bg-secondary/90 transition-all shadow-md">
                  Simpan
                </button>
              </div>
            </form>
          </div>

          <!-- Bulk Import Form -->
          <div class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-5 shadow-xl">
            <h3 class="font-display font-semibold text-[#F5F0DC] mb-4 flex items-center gap-2 border-b border-[#F5F0DC]/5 pb-2">
              <span class="material-symbols-outlined text-secondary text-base">group_add</span>
              Bulk Importer (Salin Tempel)
            </h3>
            <form action="admin.php" method="POST" class="flex flex-col gap-3">
              <div>
                <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Teks Data (Format: Nama [Tab atau Koma] Kelas)</label>
                <textarea name="bulk_text" required placeholder="Budi Santoso&#9;XII PPLG 1&#10;Ani Herawati&#9;XII AKT" rows="2"
                  class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none resize-none custom-scrollbar"></textarea>
              </div>
              <div class="flex justify-between items-center gap-3">
                <?php if ($isAdmin): ?>
                  <div class="flex-1 flex items-center gap-2">
                    <span class="text-[#F5F0DC]/60 text-xxs uppercase tracking-wider whitespace-nowrap">Default Kelas:</span>
                    <input type="text" name="default_classroom" value="Guest"
                      class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-1 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
                  </div>
                <?php else: ?>
                  <div class="flex-1 flex items-center gap-2">
                    <span class="text-[#F5F0DC]/60 text-xxs uppercase tracking-wider whitespace-nowrap">Kelas Tujuan:</span>
                    <span class="text-secondary font-bold text-xs uppercase"><?= htmlspecialchars($adminClassroom) ?></span>
                  </div>
                <?php endif; ?>
                <button type="submit" name="bulk_import" class="bg-secondary text-primary font-bold py-2.5 px-6 rounded-xl text-xxs tracking-widest uppercase hover:bg-secondary/90 transition-all shadow-md">
                  Impor Data
                </button>
              </div>
            </form>
          </div>

        </section>
      <?php endif; ?>

      <!-- 3. Student Lists & Search filters -->
      <section class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-5 shadow-2xl space-y-4">

        <!-- Search and filters -->
        <form method="GET" action="admin.php" class="grid grid-cols-2 <?= $isAdmin ? 'md:grid-cols-5' : 'md:grid-cols-4' ?> gap-3 items-end">
          <div class="col-span-2 md:col-span-2">
            <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Cari Nama / Kode</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan kata kunci..."
              class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none" />
          </div>

          <?php if ($isAdmin): ?>
            <div>
              <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Filter Kelas</label>
              <select name="filter_class" class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none appearance-none">
                <option value="">Semua Kelas</option>
                <?php foreach ($classes as $cl): ?>
                  <option value="<?= htmlspecialchars($cl) ?>" <?= $filterClass === $cl ? 'selected' : '' ?>><?= htmlspecialchars($cl) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div>
            <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Filter RSVP</label>
            <select name="filter_rsvp" class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none appearance-none">
              <option value="">Semua RSVP</option>
              <option value="Pending" <?= $filterRsvp === 'Pending' ? 'selected' : '' ?>>Pending</option>
              <option value="Attending" <?= $filterRsvp === 'Attending' ? 'selected' : '' ?>>Attending</option>
              <option value="Absent" <?= $filterRsvp === 'Absent' ? 'selected' : '' ?>>Absent</option>
            </select>
          </div>

          <div class="flex gap-2">
            <div class="flex-1">
              <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Filter Kehadiran</label>
              <select name="filter_checkin" class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none appearance-none">
                <option value="">Semua Hadir</option>
                <option value="1" <?= $filterCheckin === '1' ? 'selected' : '' ?>>Sudah Hadir</option>
                <option value="0" <?= $filterCheckin === '0' ? 'selected' : '' ?>>Belum Hadir</option>
              </select>
            </div>
            <button type="submit" class="bg-secondary text-primary font-bold p-2.5 rounded-xl flex items-center justify-center hover:scale-105 active:scale-95 transition-transform" title="Search">
              <span class="material-symbols-outlined text-sm">search</span>
            </button>
          </div>
        </form>

        <!-- Main table -->
        <div class="overflow-x-auto custom-scrollbar border border-[#ECE8D2]/5 rounded-2xl">
          <table class="w-full text-left border-collapse min-w-[900px]">
            <thead>
              <tr class="bg-[#0D1F0C] text-[10px] text-[#F5F0DC]/50 font-semibold uppercase tracking-wider border-b border-secondary/25">
                <th class="py-3 px-4 w-24">Kode</th>
                <th class="py-3 px-4">Nama Siswa / Tamu</th>
                <th class="py-3 px-4 w-28">Kelas</th>
                <th class="py-3 px-4 w-32">WhatsApp</th>
                <th class="py-3 px-4 w-24">RSVP</th>
                <th class="py-3 px-4 w-28">Pendamping</th>
                <th class="py-3 px-4 w-24">Check-in</th>
                <th class="py-3 px-4 w-40 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#F5F0DC]/5 text-xs">
              <?php if (empty($students)): ?>
                <tr>
                  <td colspan="8" class="text-center py-12 text-[#F5F0DC]/40 italic">
                    Tidak ada data siswa ditemukan.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $row): ?>
                  <?php
                  $inviteLink = $baseUrl . "?code=" . $row['code'];
                  $message = <<<TEXT
Halo *{$row['name']}*,

Dengan hormat, kami mengundang Anda untuk menghadiri acara *GCP Award 2026 – Generasi Cinta Prestasi* SMK Pariwisata Metland School.

Silakan melihat undangan personal Anda melalui tautan berikut:
{$inviteLink}

Mohon berkenan melakukan konfirmasi kehadiran melalui tautan tersebut.

*Informasi Penting*
Setiap siswa hanya diperkenankan membawa *1 (satu) orang pendamping*.

Atas perhatian dan kehadiran Anda, kami ucapkan terima kasih.
TEXT;

                  $waUrl = "https://api.whatsapp.com/send?text=" . rawurlencode($message);
                  if (!empty($row['whatsapp'])) {
                    $cleanWA = preg_replace('/[^0-9]/', '', $row['whatsapp']);
                    if (strpos($cleanWA, '0') === 0) {
                      $cleanWA = '62' . substr($cleanWA, 1);
                    }
                    $waUrl = "https://api.whatsapp.com/send?phone=" . $cleanWA . "&text=" . rawurlencode($message);
                  }
                  ?>
                  <tr class="hover:bg-[#F5F0DC]/5 transition-colors duration-200">
                    <!-- Code -->
                    <td class="py-3.5 px-4 font-bold text-secondary font-mono tracking-wider"><?= htmlspecialchars($row['code']) ?></td>
                    <!-- Name -->
                    <td class="py-3.5 px-4 font-medium text-[#F5F0DC] uppercase"><?= htmlspecialchars($row['name']) ?></td>
                    <!-- Classroom -->
                    <td class="py-3.5 px-4 text-[#F5F0DC]/75"><?= htmlspecialchars($row['classroom']) ?></td>
                    <!-- Whatsapp -->
                    <td class="py-3.5 px-4 text-[#F5F0DC]/50 font-mono text-xxs truncate max-w-[120px]" title="<?= htmlspecialchars($row['whatsapp'] ?? '') ?>">
                      <?= htmlspecialchars($row['whatsapp'] ?: '-') ?>
                    </td>
                    <!-- RSVP badge -->
                    <td class="py-3.5 px-4">
                      <?php if ($row['rsvp_status'] === 'Attending'): ?>
                        <span class="bg-[#25D366]/10 text-[#25D366] border border-[#25D366]/20 px-2 py-0.5 rounded-full text-[9px] font-semibold tracking-wider uppercase">Attending</span>
                      <?php elseif ($row['rsvp_status'] === 'Absent'): ?>
                        <span class="bg-red-500/10 text-red-500 border border-red-500/20 px-2 py-0.5 rounded-full text-[9px] font-semibold tracking-wider uppercase">Absent</span>
                      <?php else: ?>
                        <span class="bg-yellow-500/10 text-yellow-500 border border-yellow-500/20 px-2 py-0.5 rounded-full text-[9px] font-semibold tracking-wider uppercase">Pending</span>
                      <?php endif; ?>
                    </td>
                    <!-- Companion type -->
                    <td class="py-3.5 px-4">
                      <?php if ($row['companion_type'] === 'parents'): ?>
                        <span class="text-[#F5F0DC] text-[10px] bg-[#F5F0DC]/5 border border-[#F5F0DC]/10 px-2 py-0.5 rounded">Ortu</span>
                      <?php elseif ($row['companion_type'] === 'sibling'): ?>
                        <span class="text-[#F5F0DC] text-[10px] bg-[#F5F0DC]/5 border border-[#F5F0DC]/10 px-2 py-0.5 rounded">Saudara</span>
                      <?php else: ?>
                        <span class="text-[#F5F0DC]/40 text-[10px] italic">Sendiri</span>
                      <?php endif; ?>
                    </td>
                    <!-- Check-in status -->
                    <td class="py-3.5 px-4">
                      <?php if ($row['checked_in'] == 1): ?>
                        <div class="flex flex-col items-start">
                          <span class="bg-secondary/15 text-secondary border border-secondary/35 px-2 py-0.5 rounded-full text-[9px] font-semibold tracking-wider uppercase">Hadir</span>
                          <span class="text-[8px] text-[#F5F0DC]/40 mt-0.5"><?= date('H:i:s', strtotime($row['checked_in_at'])) ?></span>
                        </div>
                      <?php else: ?>
                        <span class="text-[#F5F0DC]/40 font-medium italic">Belum</span>
                      <?php endif; ?>
                    </td>
                    <!-- Actions -->
                    <td class="py-3.5 px-4 text-center space-x-1 whitespace-nowrap">
                      <!-- WA Invite -->
                      <a href="<?= $waUrl ?>" target="_blank" class="inline-flex items-center justify-center w-7 h-7 bg-[#25D366]/10 text-[#25D366] hover:bg-[#25D366] hover:text-black border border-[#25D366]/20 rounded-lg transition-all" title="Share via WhatsApp">
                        <span class="material-symbols-outlined text-sm">send</span>
                      </a>
                      <!-- Copy invite Link -->
                      <button onclick="copyToClipboard('<?= $inviteLink ?>', this)" class="inline-flex items-center justify-center w-7 h-7 bg-[#F5F0DC]/5 text-[#F5F0DC] hover:bg-[#F5F0DC]/15 border border-[#F5F0DC]/10 rounded-lg transition-all" title="Copy Link Undangan">
                        <span class="material-symbols-outlined text-sm">content_copy</span>
                      </button>
                      <!-- Edit -->
                      <button onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                                        'id' => $row['id'],
                                                        'name' => $row['name'],
                                                        'classroom' => $row['classroom'],
                                                        'whatsapp' => $row['whatsapp'] ?? '',
                                                        'rsvp_status' => $row['rsvp_status'],
                                                        'companion_type' => $row['companion_type']
                                                      ])) ?>)" class="inline-flex items-center justify-center w-7 h-7 bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 rounded-lg transition-all" title="Edit Siswa">
                        <span class="material-symbols-outlined text-sm">edit</span>
                      </button>
                      <!-- Reset -->
                      <a href="admin.php?action=reset&id=<?= $row['id'] ?>" onclick="return confirm('Reset status RSVP & check-in siswa ini?')"
                        class="inline-flex items-center justify-center w-7 h-7 bg-yellow-500/10 text-yellow-500 hover:bg-yellow-500 hover:text-black border border-yellow-500/20 rounded-lg transition-all" title="Reset Status">
                        <span class="material-symbols-outlined text-sm">restart_alt</span>
                      </a>
                      <!-- Delete -->
                      <?php if ($isAdmin): ?>
                        <a href="admin.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Hapus siswa ini secara permanen dari database?')"
                          class="inline-flex items-center justify-center w-7 h-7 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/20 rounded-lg transition-all" title="Delete">
                          <span class="material-symbols-outlined text-sm">delete</span>
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </section>
    </div>

    <!-- USER MANAGEMENT TAB CONTAINER (Super Admin Only) -->
    <?php if ($isAdmin): ?>
      <div id="container-tab-users" class="hidden space-y-6">
        <div class="grid md:grid-cols-3 gap-6">

          <!-- Create User Form (1 col) -->
          <div class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-5 shadow-xl md:col-span-1">
            <h3 class="font-display font-semibold text-[#F5F0DC] mb-4 flex items-center gap-2 border-b border-[#F5F0DC]/5 pb-2">
              <span class="material-symbols-outlined text-secondary text-base">person_add</span>
              Tambah Akun Wali Kelas
            </h3>

            <form action="admin.php" method="POST" class="space-y-4">
              <div>
                <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Username</label>
                <input type="text" name="username" required placeholder="e.g. pak_budi"
                  class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
              </div>
              <div>
                <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Password</label>
                <input type="password" name="password" required placeholder="Password"
                  class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
              </div>
              <div>
                <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Kelas yang Diampu</label>
                <input type="text" name="classroom" required placeholder="e.g. XII PPLG 1" list="classes-list"
                  class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
              </div>

              <button type="submit" name="add_user" class="w-full bg-secondary text-primary font-bold py-2.5 rounded-xl text-xxs tracking-widest uppercase hover:bg-secondary/90 transition-all shadow-md mt-2">
                Buat Akun
              </button>
            </form>
          </div>

          <!-- Users List Table (2 cols) -->
          <div class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-5 shadow-xl md:col-span-2 space-y-4">
            <h3 class="font-display font-semibold text-[#F5F0DC] flex items-center gap-2 border-b border-[#F5F0DC]/5 pb-2">
              <span class="material-symbols-outlined text-secondary text-base">manage_accounts</span>
              Daftar Akun Pengguna
            </h3>

            <div class="overflow-x-auto custom-scrollbar border border-[#ECE8D2]/5 rounded-2xl">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-[#0D1F0C] text-[10px] text-[#F5F0DC]/50 font-semibold uppercase tracking-wider border-b border-secondary/25">
                    <th class="py-3 px-4">Username</th>
                    <th class="py-3 px-4">Role / Kelas</th>
                    <th class="py-3 px-4 w-28 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#F5F0DC]/5 text-xs">
                  <?php foreach ($allUsers as $u): ?>
                    <tr class="hover:bg-[#F5F0DC]/5 transition-colors duration-200">
                      <td class="py-3 px-4 font-semibold text-[#F5F0DC]"><?= htmlspecialchars($u['username']) ?></td>
                      <td class="py-3 px-4 text-[#F5F0DC]/75">
                        <?php if ($u['classroom'] === null): ?>
                          <span class="bg-secondary/15 text-secondary border border-secondary/35 px-2 py-0.5 rounded-full text-[9px] font-semibold uppercase tracking-wider">Super Admin</span>
                        <?php else: ?>
                          Wali Kelas <span class="font-bold text-[#F5F0DC]"><?= htmlspecialchars($u['classroom']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="py-3 px-4 text-center space-x-1 whitespace-nowrap">
                        <?php if ($u['username'] !== 'admin'): ?>
                          <!-- Edit User -->
                          <button onclick="openEditUserModal(<?= htmlspecialchars(json_encode([
                                                                'id' => $u['id'],
                                                                'username' => $u['username'],
                                                                'classroom' => $u['classroom'] ?? ''
                                                              ])) ?>)" class="inline-flex items-center justify-center w-7 h-7 bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 rounded-lg transition-all" title="Edit Akun">
                            <span class="material-symbols-outlined text-sm">edit</span>
                          </button>

                          <?php if ($u['username'] !== ($_SESSION['admin_username'] ?? '')): ?>
                            <!-- Delete User -->
                            <a href="admin.php?action=delete_user&user_id=<?= $u['id'] ?>" onclick="return confirm('Hapus akun ini?')"
                              class="inline-flex items-center justify-center w-7 h-7 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/20 rounded-lg transition-all" title="Hapus User">
                              <span class="material-symbols-outlined text-sm">delete</span>
                            </a>
                          <?php else: ?>
                            <span class="text-[#F5F0DC]/30 text-xxs italic px-1">Active</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="text-[#F5F0DC]/30 text-xxs italic">System Locked</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    <?php endif; ?>

  </main>

  <!-- ----------------------------------------------------------- -->
  <!-- Edit Student Modal Overlay -->
  <!-- ----------------------------------------------------------- -->
  <div id="edit-modal" class="fixed inset-0 bg-[#0D1F0C]/95 z-50 hidden items-center justify-center p-5 backdrop-blur-md">
    <div id="edit-card" class="bg-[#4A2A12] border border-secondary/35 max-w-md w-full p-6 sm:p-8 rounded-3xl shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
      <h3 class="font-display font-bold text-xl text-[#F5F0DC] mb-4 flex items-center gap-2 border-b border-[#F5F0DC]/5 pb-2">
        <span class="material-symbols-outlined text-secondary">edit</span> Edit Data Siswa
      </h3>

      <form action="admin.php" method="POST" class="space-y-4 text-left">
        <input type="hidden" name="id" id="edit-id" />

        <div>
          <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Nama Lengkap</label>
          <input type="text" name="name" id="edit-name" required placeholder="Nama Siswa"
            class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
        </div>

        <div>
          <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Kelas / Afiliasi</label>
          <?php if ($isAdmin): ?>
            <input type="text" name="classroom" id="edit-classroom" required placeholder="e.g. XII PPLG 1" list="classes-list"
              class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
          <?php else: ?>
            <input type="text" id="edit-classroom-disabled" disabled
              class="w-full bg-[#0D1F0C]/50 border border-secondary/10 rounded-xl px-3.5 py-2 text-[#F5F0DC]/50 text-xs outline-none cursor-not-allowed" />
          <?php endif; ?>
        </div>

        <div>
          <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Nomor WhatsApp</label>
          <input type="text" name="whatsapp" id="edit-whatsapp" placeholder="e.g. 08123456789"
            class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Status RSVP</label>
            <select name="rsvp_status" id="edit-rsvp-status" class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none appearance-none">
              <option value="Pending">Pending</option>
              <option value="Attending">Attending</option>
              <option value="Absent">Absent</option>
            </select>
          </div>
          <div>
            <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Pendamping</label>
            <select name="companion_type" id="edit-companion-type" class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none appearance-none">
              <option value="none">Sendiri</option>
              <option value="parents">Orang Tua / Wali</option>
              <option value="sibling">Saudara / Kerabat</option>
            </select>
          </div>
        </div>

        <div class="flex gap-3 pt-4">
          <button type="button" id="btn-close-edit-modal" class="flex-1 bg-transparent text-[#F5F0DC]/70 border border-[#F5F0DC]/15 hover:bg-[#F5F0DC]/5 py-3 rounded-xl text-xs tracking-wider font-bold uppercase transition-all">
            Batal
          </button>
          <button type="submit" name="edit_student" class="flex-1 bg-secondary text-primary font-bold py-3 rounded-xl text-xs tracking-wider uppercase hover:bg-secondary/90 transition-all shadow-lg">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit User Modal Overlay -->
  <div id="edit-user-modal" class="fixed inset-0 bg-[#0D1F0C]/95 z-50 hidden items-center justify-center p-5 backdrop-blur-md">
    <div id="edit-user-card" class="bg-[#4A2A12] border border-secondary/35 max-w-md w-full p-6 sm:p-8 rounded-3xl shadow-2xl transition-all duration-300 transform scale-95 opacity-0">
      <h3 class="font-display font-bold text-xl text-[#F5F0DC] mb-4 flex items-center gap-2 border-b border-[#F5F0DC]/5 pb-2">
        <span class="material-symbols-outlined text-secondary">manage_accounts</span> Edit Akun Wali Kelas
      </h3>

      <form action="admin.php" method="POST" class="space-y-4 text-left">
        <input type="hidden" name="id" id="edit-user-id" />

        <div>
          <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Username</label>
          <input type="text" name="username" id="edit-user-username" required placeholder="Username"
            class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
        </div>

        <div>
          <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Password Baru (Kosongkan jika tidak diubah)</label>
          <input type="password" name="password" id="edit-user-password" placeholder="Password Baru"
            class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
        </div>

        <div>
          <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Kelas yang Diampu</label>
          <input type="text" name="classroom" id="edit-user-classroom" required placeholder="e.g. XII PPLG 1" list="classes-list"
            class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
        </div>

        <div class="flex gap-3 pt-4">
          <button type="button" id="btn-close-edit-user-modal" class="flex-1 bg-transparent text-[#F5F0DC]/70 border border-[#F5F0DC]/15 hover:bg-[#F5F0DC]/5 py-3 rounded-xl text-xs tracking-wider font-bold uppercase transition-all">
            Batal
          </button>
          <button type="submit" name="edit_user" class="flex-1 bg-secondary text-primary font-bold py-3 rounded-xl text-xs tracking-wider uppercase hover:bg-secondary/90 transition-all shadow-lg">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>

  <footer class="bg-[#060F06] py-6 border-t border-secondary/20 text-center text-[10px] tracking-widest text-[#F5F0DC]/40 uppercase mt-12">
    SMK PARIWISATA METLAND SCHOOL • ADMIN CONTROL PANEL
  </footer>

  <script>
    function copyToClipboard(text, btn) {
      navigator.clipboard.writeText(text).then(() => {
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-outlined text-sm">check</span>';
        btn.classList.add('bg-secondary/20', 'text-secondary');
        setTimeout(() => {
          btn.innerHTML = origHtml;
          btn.classList.remove('bg-secondary/20', 'text-secondary');
        }, 1200);
      }).catch(err => {
        console.error("Failed to copy link:", err);
        alert("Gagal menyalin link. Salin manual: " + text);
      });
    }

    // Tab Switch Logic
    function switchTab(tab) {
      const tabStudents = document.getElementById('container-tab-students');
      const tabUsers = document.getElementById('container-tab-users');
      const btnStudents = document.getElementById('btn-tab-students');
      const btnUsers = document.getElementById('btn-tab-users');

      if (tab === 'students') {
        tabStudents.classList.remove('hidden');
        tabUsers.classList.add('hidden');

        btnStudents.classList.replace('border-transparent', 'border-secondary');
        btnStudents.classList.replace('text-[#F5F0DC]/60', 'text-secondary');
        btnStudents.classList.add('font-bold');

        btnUsers.classList.replace('border-secondary', 'border-transparent');
        btnUsers.classList.replace('text-secondary', 'text-[#F5F0DC]/60');
        btnUsers.classList.remove('font-bold');
      } else {
        tabStudents.classList.add('hidden');
        tabUsers.classList.remove('hidden');

        btnUsers.classList.replace('border-transparent', 'border-secondary');
        btnUsers.classList.replace('text-[#F5F0DC]/60', 'text-secondary');
        btnUsers.classList.add('font-bold');

        btnStudents.classList.replace('border-secondary', 'border-transparent');
        btnStudents.classList.replace('text-secondary', 'text-[#F5F0DC]/60');
        btnStudents.classList.remove('font-bold');
      }
    }

    // Edit Student Modal Logic
    function openEditModal(student) {
      document.getElementById('edit-id').value = student.id;
      document.getElementById('edit-name').value = student.name;

      const classInput = document.getElementById('edit-classroom');
      if (classInput) {
        classInput.value = student.classroom;
      } else {
        document.getElementById('edit-classroom-disabled').value = student.classroom;
      }

      document.getElementById('edit-whatsapp').value = student.whatsapp || '';
      document.getElementById('edit-rsvp-status').value = student.rsvp_status;
      document.getElementById('edit-companion-type').value = student.companion_type;

      const modal = document.getElementById("edit-modal");
      const card = document.getElementById("edit-card");

      modal.classList.remove("hidden");
      modal.classList.add("flex");
      setTimeout(() => {
        card.classList.replace("scale-95", "scale-100");
        card.classList.replace("opacity-0", "opacity-100");
      }, 50);
    }

    document.getElementById("btn-close-edit-modal").addEventListener("click", () => {
      const modal = document.getElementById("edit-modal");
      const card = document.getElementById("edit-card");

      card.classList.replace("scale-100", "scale-95");
      card.classList.replace("opacity-100", "opacity-0");

      setTimeout(() => {
        modal.classList.remove("flex");
        modal.classList.add("hidden");
      }, 150);
    });

    // Edit User Modal Logic
    function openEditUserModal(user) {
      document.getElementById('edit-user-id').value = user.id;
      document.getElementById('edit-user-username').value = user.username;
      document.getElementById('edit-user-password').value = '';
      document.getElementById('edit-user-classroom').value = user.classroom;

      const modal = document.getElementById("edit-user-modal");
      const card = document.getElementById("edit-user-card");

      modal.classList.remove("hidden");
      modal.classList.add("flex");
      setTimeout(() => {
        card.classList.replace("scale-95", "scale-100");
        card.classList.replace("opacity-0", "opacity-100");
      }, 50);
    }

    document.getElementById("btn-close-edit-user-modal").addEventListener("click", () => {
      const modal = document.getElementById("edit-user-modal");
      const card = document.getElementById("edit-user-card");

      card.classList.replace("scale-100", "scale-95");
      card.classList.replace("opacity-100", "opacity-0");

      setTimeout(() => {
        modal.classList.remove("flex");
        modal.classList.add("hidden");
      }, 150);
    });
  </script>
</body>

</html>