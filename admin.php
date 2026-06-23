<?php
// admin.php
// Admin Dashboard for managing invitations and attendance

require_once 'config.php';
session_start();

// Authentication Handling
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged']);
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
        header('Location: admin.php');
        exit;
    } else {
        $loginError = "Username atau password salah.";
    }
}

// Check Login Status
$isLogged = isset($_SESSION['admin_logged']);

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
      body { background-color: #1C3319; color: #ECE8D2; font-family: 'Poppins', sans-serif; }
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

// Process student management actions
$alert = '';
$alertType = 'success'; // success, error

// 1. Add student
if (isset($_POST['add_student'])) {
    $name = trim($_POST['name'] ?? '');
    $classroom = trim($_POST['classroom'] ?? '');
    
    if (!empty($name) && !empty($classroom)) {
        try {
            $code = generateUniqueCode($pdo);
            $stmt = $pdo->prepare("INSERT INTO students (code, name, classroom) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $classroom]);
            $alert = "Siswa '$name' berhasil ditambahkan dengan kode: $code";
        } catch (PDOException $e) {
            $alert = "Gagal menambah siswa: " . $e->getMessage();
            $alertType = 'error';
        }
    } else {
        $alert = "Nama dan Kelas wajib diisi.";
        $alertType = 'error';
    }
}

// 2. Bulk Import student
if (isset($_POST['bulk_import'])) {
    $bulkText = trim($_POST['bulk_text'] ?? '');
    $defaultClass = trim($_POST['default_classroom'] ?? 'Guest');
    
    if (!empty($bulkText)) {
        $lines = explode("\n", $bulkText);
        $count = 0;
        $pdo->beginTransaction();
        try {
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Try splitting by Tab first, then Comma
                $parts = explode("\t", $line);
                if (count($parts) < 2) {
                    $parts = explode(",", $line);
                }
                
                $stdName = trim($parts[0]);
                $stdClass = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : $defaultClass;
                
                if (!empty($stdName)) {
                    $code = generateUniqueCode($pdo);
                    $stmt = $pdo->prepare("INSERT INTO students (code, name, classroom) VALUES (?, ?, ?)");
                    $stmt->execute([$code, $stdName, $stdClass]);
                    $count++;
                }
            }
            $pdo->commit();
            $alert = "Berhasil mengimpor $count siswa/tamu.";
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

// 3. Reset RSVP/Checkin
if (isset($_GET['action']) && $_GET['action'] === 'reset' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE students SET rsvp_status = 'Pending', companion_type = 'none', whatsapp = NULL, checked_in = 0, checked_in_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin.php');
    exit;
}

// 4. Delete Student
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin.php');
    exit;
}

// Fetch stats
$totalCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$attendingCount = $pdo->query("SELECT COUNT(*) FROM students WHERE rsvp_status = 'Attending'")->fetchColumn();
$absentCount = $pdo->query("SELECT COUNT(*) FROM students WHERE rsvp_status = 'Absent'")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM students WHERE rsvp_status = 'Pending'")->fetchColumn();
$presentCount = $pdo->query("SELECT COUNT(*) FROM students WHERE checked_in = 1")->fetchColumn();

// Fetch companion breakdown
$companionParents = $pdo->query("SELECT COUNT(*) FROM students WHERE checked_in = 1 AND companion_type = 'parents'")->fetchColumn();
$companionSibling = $pdo->query("SELECT COUNT(*) FROM students WHERE checked_in = 1 AND companion_type = 'sibling'")->fetchColumn();
$companionNone = $pdo->query("SELECT COUNT(*) FROM students WHERE checked_in = 1 AND companion_type = 'none'")->fetchColumn();

// Get search and filters
$search = trim($_GET['search'] ?? '');
$filterClass = trim($_GET['filter_class'] ?? '');
$filterRsvp = trim($_GET['filter_rsvp'] ?? '');
$filterCheckin = trim($_GET['filter_checkin'] ?? '');

$queryStr = "SELECT * FROM students WHERE 1=1";
$params = [];

if ($search !== '') {
    $queryStr .= " AND (name LIKE ? OR code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterClass !== '') {
    $queryStr .= " AND classroom = ?";
    $params[] = $filterClass;
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
              primary: "#0D1F0C",   // Near-black forest
              cardBg: "#4A2A12",    // Rich warm brown
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
      body { background-color: #0D1F0C; color: #F5F0DC; font-family: 'Poppins', sans-serif; }
      .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
      .custom-scrollbar::-webkit-scrollbar-track { background: rgba(13,31,12,0.5); }
      .custom-scrollbar::-webkit-scrollbar-thumb { background: #E2C12F; border-radius: 3px; }
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
            <span class="text-[9px] uppercase tracking-widest text-secondary font-semibold">Admin Panel</span>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <a href="checkin.php" target="_blank" class="text-xs font-semibold bg-secondary text-primary px-3 py-1.5 rounded-lg hover:scale-105 transition-transform flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">qr_code_scanner</span> Buka Scanner
          </a>
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
              <input type="text" name="classroom" required placeholder="e.g. XII PPLG 1" list="classes-list"
                class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3.5 py-2 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
              <datalist id="classes-list">
                <option value="XII AKT">
                <option value="XII HOS 1">
                <option value="XII HOS 2">
                <option value="XII HOS 3">
                <option value="XII DKV 1">
                <option value="XII DKV 2">
                <option value="XII PPLG 1">
                <option value="XII PPLG 2">
              </datalist>
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
              <div class="flex-1 flex items-center gap-2">
                <span class="text-[#F5F0DC]/60 text-xxs uppercase tracking-wider whitespace-nowrap">Default Kelas:</span>
                <input type="text" name="default_classroom" value="Guest" 
                  class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-1 text-[#F5F0DC] text-xs focus:border-secondary focus:ring-1 focus:ring-secondary/40 outline-none" />
              </div>
              <button type="submit" name="bulk_import" class="bg-secondary text-primary font-bold py-2.5 px-6 rounded-xl text-xxs tracking-widest uppercase hover:bg-secondary/90 transition-all shadow-md">
                Impor Data
              </button>
            </div>
          </form>
        </div>

      </section>

      <!-- 3. Student Lists & Search filters -->
      <section class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-5 shadow-2xl space-y-4">
        
        <!-- Search and filters -->
        <form method="GET" action="admin.php" class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
          <div class="col-span-2 md:col-span-2">
            <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Cari Nama / Kode</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan kata kunci..."
              class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-3 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none" />
          </div>
          <div>
            <label class="block text-[#F5F0DC]/60 text-xxs uppercase tracking-wider mb-1 px-1">Filter Kelas</label>
            <select name="filter_class" class="w-full bg-[#0D1F0C] border border-secondary/25 rounded-xl px-2 py-2 text-[#F5F0DC] text-xs focus:border-secondary outline-none appearance-none">
              <option value="">Semua Kelas</option>
              <?php foreach ($classes as $cl): ?>
                <option value="<?= htmlspecialchars($cl) ?>" <?= $filterClass === $cl ? 'selected' : '' ?>><?= htmlspecialchars($cl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
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
                <th class="py-3 px-4 w-36 text-center">Aksi</th>
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
                    // $waText = "Halo *" . urlencode($row['name']) . "*,\n\nKami mengundang Anda menghadiri acara *GCP Award 2026 - Generasi Cinta Prestasi* SMK Pariwisata Metland School.\n\nBuka undangan personal Anda melalui link berikut:\n" . urlencode($inviteLink) . "\n\nMohon lakukan konfirmasi kehadiran Anda pada link tersebut.\n\nTerima kasih!";
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
                        // strip clean whatsapp numbers
                        $cleanWA = preg_replace('/[^0-9]/', '', $row['whatsapp']);
                        if (strpos($cleanWA, '0') === 0) {
                            $cleanWA = '62' . substr($cleanWA, 1);
                        }
                        $waUrl = "https://api.whatsapp.com/send?phone=" . $cleanWA . "&text=" . $waText;
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
                        <span class="text-[#F5F0DC] text-[10px] bg-[#F5F0DC]/5 border border-[#F5F0DC]/10 px-2 py-0.5 rounded">Father/Mother</span>
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
                      <!-- Reset -->
                      <a href="admin.php?action=reset&id=<?= $row['id'] ?>" onclick="return confirm('Reset status RSVP & check-in siswa ini?')" 
                        class="inline-flex items-center justify-center w-7 h-7 bg-yellow-500/10 text-yellow-500 hover:bg-yellow-500 hover:text-black border border-yellow-500/20 rounded-lg transition-all" title="Reset Status">
                        <span class="material-symbols-outlined text-sm">restart_alt</span>
                      </a>
                      <!-- Delete -->
                      <a href="admin.php?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Hapus siswa ini secara permanen dari database?')" 
                        class="inline-flex items-center justify-center w-7 h-7 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/20 rounded-lg transition-all" title="Delete">
                        <span class="material-symbols-outlined text-sm">delete</span>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </section>

    </main>

    <footer class="bg-[#060F06] py-6 border-t border-secondary/20 text-center text-[10px] tracking-widest text-[#F5F0DC]/40 uppercase">
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
    </script>
  </body>
</html>
