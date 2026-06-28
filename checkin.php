<?php
// checkin.php
// Guest Attendance Check-in Page (No PIN Required)

require_once 'config.php';
session_start();

if (!isset($_SESSION['admin_logged']) || !$_SESSION['admin_logged']) {
  header('Location: admin.php');
  exit;
}

$code = $_GET['code'] ?? '';
$directCheckin = null;

if (!empty($code)) {
  // Process direct check-in on URL visit
  $stmt = $pdo->prepare("SELECT * FROM students WHERE code = ?");
  $stmt->execute([$code]);
  $student = $stmt->fetch();
  
  if ($student) {
        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d H:i:s');
        $update = $pdo->prepare("UPDATE students SET checked_in = 1, checked_in_at = ? WHERE code = ?");
        $update->execute([$now, $code]);

        $directCheckin = [
            'success' => true,
            'name' => $student['name'],
            'classroom' => $student['classroom'],
            'companion_type' => $student['companion_type'],
            'checked_in_at' => $now,
            'already_checked_in' => ($student['checked_in'] == 1)
        ];
    } else {
        $directCheckin = [
            'success' => false,
            'message' => 'Invalid or unknown student code.'
        ];
    }
}

// Fetch recent checked-in students (last 10)
$recentCheckins = $pdo->query("SELECT * FROM students WHERE checked_in = 1 ORDER BY checked_in_at DESC LIMIT 10")->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Check-in Attendance | GCP Award 2026</title>
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
    <!-- html5-qrcode scanner library -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
      body { background-color: #0D1F0C; color: #F5F0DC; font-family: 'Poppins', sans-serif; }
      .glass-card-gold {
        background: rgba(226, 193, 47, 0.1);
        backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(226, 193, 47, 0.35);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
      }
      .custom-scrollbar::-webkit-scrollbar { width: 6px; }
      .custom-scrollbar::-webkit-scrollbar-track { background: rgba(13,31,12,0.5); }
      .custom-scrollbar::-webkit-scrollbar-thumb { background: #E2C12F; border-radius: 3px; }
    </style>
  </head>
  <body class="min-h-screen flex flex-col selection:bg-secondary selection:text-primary">
    
    <!-- Top Nav -->
    <header class="border-b border-secondary/25 bg-[#0D1F0C]/95 sticky top-0 z-40 backdrop-blur-md">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <img src="img/metschoo/Metschoo.png" alt="Logo" class="w-8 h-8 object-contain" />
          <div>
            <h1 class="font-display font-bold text-[#F5F0DC] text-base tracking-wide leading-none">GCP Award 2026</h1>
            <span class="text-[9px] uppercase tracking-widest text-secondary font-semibold">Attendance Scanner</span>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <a href="admin.php" class="text-xs font-semibold bg-[#F5F0DC]/5 border border-[#F5F0DC]/15 hover:bg-[#F5F0DC]/10 text-[#F5F0DC] px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">dashboard</span> Admin Panel
          </a>
        </div>
      </div>
    </header>

    <main class="flex-1 max-w-6xl w-full mx-auto p-4 grid md:grid-cols-12 gap-6 items-start">
      
      <!-- Left Column: Scanner (7 cols) -->
      <section class="md:col-span-7 flex flex-col gap-6">
        
        <!-- Live Scanner -->
        <div class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-6 shadow-2xl">
          <div class="flex items-center justify-between mb-4 border-b border-secondary/10 pb-3">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-secondary animate-pulse">videocam</span>
              <h2 class="font-display font-semibold text-lg text-[#F5F0DC]">Live Camera QR Scanner</h2>
            </div>
            <button id="btn-camera-toggle" class="text-xs bg-secondary text-primary font-bold px-3 py-1.5 rounded-lg hover:scale-105 active:scale-95 transition-all">
              Mulai Kamera
            </button>
          </div>
          
          <!-- Scanner Display Area -->
          <div class="relative w-full aspect-video md:aspect-[4/3] bg-[#060F06] rounded-2xl overflow-hidden border border-[#F5F0DC]/5 flex flex-col items-center justify-center">
            <div id="reader" class="w-full h-full"></div>
            <div id="scanner-placeholder" class="absolute inset-0 flex flex-col items-center justify-center bg-[#060F06] p-4 text-center">
              <span class="material-symbols-outlined text-secondary text-5xl mb-3">qr_code_scanner</span>
              <p class="text-sm font-semibold text-[#F5F0DC]">Kamera Belum Aktif</p>
              <p class="text-xs text-[#F5F0DC]/50 mt-1 max-w-xs">Klik tombol "Mulai Kamera" di atas untuk menggunakan kamera device ini sebagai scanner ticket.</p>
            </div>
          </div>
          
          <!-- Manual Code Input -->
          <div class="mt-5 pt-4 border-t border-white/5 flex gap-3">
            <input type="text" id="manual-code" placeholder="Input kode manual (e.g. MS-A1B2C)" 
              class="flex-1 bg-[#0D1F0C] border border-secondary/25 rounded-xl px-4 py-2 text-[#F5F0DC] text-sm focus:border-secondary focus:ring-1 focus:ring-secondary/50 placeholder-[#F5F0DC]/30 uppercase" />
            <button id="btn-manual-submit" class="bg-secondary text-primary font-bold text-xs tracking-wider uppercase px-5 py-2.5 rounded-xl hover:bg-secondary/90 transition-colors">
              Check-in
            </button>
          </div>
        </div>

        <!-- Diagnostic / Scanning Logs -->
        <div class="bg-[#4A2A12] border border-secondary/20 rounded-2xl p-4">
          <p class="text-xs text-[#F5F0DC]/50 flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">info</span>
            Kamera memerlukan ijin akses media. Arahkan QR Code tiket siswa pada kotak scanner agar sistem dapat mendeteksi secara otomatis.
          </p>
        </div>

      </section>

      <!-- Right Column: Live History & Statistics (5 cols) -->
      <section class="md:col-span-5 flex flex-col gap-6">
        
        <!-- Live Statistics Counter -->
        <div class="grid grid-cols-2 gap-4">
          <?php
            $totalGuests = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            $totalPresent = $pdo->query("SELECT COUNT(*) FROM students WHERE checked_in = 1")->fetchColumn();
          ?>
          <div class="bg-[#4A2A12] border border-secondary/30 rounded-2xl p-4 text-center shadow-lg">
            <p class="text-xxs uppercase tracking-widest text-[#F5F0DC]/50 mb-1">Total Hadir</p>
            <p class="text-2xl font-bold font-display text-secondary" id="stat-present"><?= $totalPresent ?></p>
          </div>
          <div class="bg-[#4A2A12] border border-secondary/25 rounded-2xl p-4 text-center shadow-lg">
            <p class="text-xxs uppercase tracking-widest text-[#F5F0DC]/50 mb-1">Total Undangan</p>
            <p class="text-2xl font-bold font-display text-[#F5F0DC]" id="stat-total"><?= $totalGuests ?></p>
          </div>
        </div>

        <!-- Recent Checkins -->
        <div class="bg-[#4A2A12] border border-secondary/25 rounded-3xl p-5 shadow-2xl">
          <div class="flex items-center justify-between mb-4 border-b border-secondary/15 pb-3">
            <h3 class="font-display font-semibold text-base text-[#F5F0DC] flex items-center gap-2">
              <span class="material-symbols-outlined text-secondary text-lg">history</span>
              Check-in Terkini
            </h3>
            <span class="text-[10px] bg-secondary/10 text-secondary border border-secondary/20 px-2 py-0.5 rounded-full uppercase tracking-wider font-semibold">Live</span>
          </div>

          <div class="max-h-[360px] overflow-y-auto custom-scrollbar pr-1 flex flex-col gap-3" id="checkin-list">
            <?php if (empty($recentCheckins)): ?>
              <div id="no-checkins" class="text-center py-12 text-gray-600 text-xs">
                Belum ada tamu yang masuk.
              </div>
            <?php else: ?>
              <?php foreach ($recentCheckins as $check): ?>
                <?php
                  $comp = 'Tanpa Pendamping';
                  if ($check['companion_type'] === 'parents') $comp = 'Orang Tua';
                  elseif ($check['companion_type'] === 'sibling') $comp = 'Saudara';
                ?>
                <div class="bg-[#0D1F0C] border border-[#F5F0DC]/5 hover:border-secondary/25 rounded-xl p-3.5 flex justify-between items-center transition-all duration-300">
                  <div class="flex-1 min-w-0 pr-3">
                    <h4 class="text-[#F5F0DC] text-xs font-bold truncate uppercase"><?= htmlspecialchars($check['name']) ?></h4>
                    <p class="text-[10px] text-[#F5F0DC]/50 mt-0.5"><?= htmlspecialchars($check['classroom']) ?> • <span class="text-secondary/80"><?= $comp ?></span></p>
                  </div>
                  <div class="text-right flex flex-col items-end">
                    <span class="text-[10px] text-[#F5F0DC]/50 font-medium"><?= date('H:i:s', strtotime($check['checked_in_at'])) ?></span>
                    <span class="text-[9px] text-[#25D366] font-semibold uppercase tracking-wider mt-0.5 bg-[#25D366]/10 px-1.5 py-0.5 rounded-full">Hadir</span>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

      </section>

    </main>

    <!-- Scan Results Modal Overlay (Popup showing Checkin details) -->
    <div id="result-modal" class="fixed inset-0 bg-[#0D1F0C]/97 z-50 hidden items-center justify-center p-5 backdrop-blur-md">
      <div id="result-card" class="bg-[#4A2A12] border-2 max-w-sm w-full p-8 rounded-3xl text-center shadow-2xl transition-all duration-500 transform scale-95 opacity-0">
        <span id="result-icon" class="material-symbols-outlined text-6xl mb-4">check_circle</span>
        
        <h3 id="result-title" class="font-display font-bold text-2xl text-[#F5F0DC] mb-1">CHECK-IN BERHASIL</h3>
        <p id="result-subtitle" class="text-xs text-[#F5F0DC]/50 uppercase tracking-widest mb-6">Informasi Kehadiran</p>
        
        <!-- Details box -->
        <div class="bg-[#0D1F0C]/70 border border-[#F5F0DC]/8 rounded-2xl p-4 text-left mb-6 space-y-2">
          <div>
            <span class="text-[9px] text-[#F5F0DC]/50 uppercase tracking-wider">Nama Tamu</span>
            <p id="res-name" class="text-[#F5F0DC] font-bold text-sm uppercase break-words">-</p>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <span class="text-[9px] text-[#F5F0DC]/50 uppercase tracking-wider">Kelas</span>
              <p id="res-class" class="text-[#F5F0DC] font-bold text-xs uppercase">-</p>
            </div>
            <div>
              <span class="text-[9px] text-[#F5F0DC]/50 uppercase tracking-wider">Pendamping</span>
              <p id="res-companion" class="text-secondary font-bold text-xs uppercase">-</p>
            </div>
          </div>
          <div>
            <span class="text-[9px] text-[#F5F0DC]/50 uppercase tracking-wider">Waktu Masuk</span>
            <p id="res-time" class="text-[#F5F0DC] text-xs font-semibold">-</p>
          </div>
        </div>
        
        <button id="btn-close-modal" class="w-full bg-secondary text-primary font-bold py-3.5 rounded-xl text-xs tracking-widest uppercase hover:bg-secondary/90 transition-all shadow-lg">
          Lanjut Scan
        </button>
      </div>
    </div>

    <!-- Direct Check-in alert on load if visited via QR code directly -->
    <?php if ($directCheckin): ?>
      <script>
        window.addEventListener("DOMContentLoaded", () => {
          const directData = <?= json_encode($directCheckin) ?>;
          displayCheckinResult(directData);
          // Clean search parameters from URL without reloading
          const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
          window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
        });
      </script>
    <?php endif; ?>

    <script>
      let html5QrcodeScanner = null;
      let isScanning = false;
      const btnCameraToggle = document.getElementById("btn-camera-toggle");
      const scannerPlaceholder = document.getElementById("scanner-placeholder");

      // Synth Sound Effects using Web Audio API
      function playSuccessBeep() {
        try {
          const ctx = new (window.AudioContext || window.webkitAudioContext)();
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          
          osc.type = 'sine';
          osc.frequency.setValueAtTime(880, ctx.currentTime); // A5 note
          osc.frequency.exponentialRampToValueAtTime(1320, ctx.currentTime + 0.12); // Sweep to E6
          
          gain.gain.setValueAtTime(0.12, ctx.currentTime);
          gain.gain.exponentialRampToValueAtTime(0.005, ctx.currentTime + 0.25);
          
          osc.connect(gain);
          gain.connect(ctx.destination);
          
          osc.start();
          osc.stop(ctx.currentTime + 0.25);
        } catch(e) {
          console.error("Audio API error:", e);
        }
      }

      function playErrorBeep() {
        try {
          const ctx = new (window.AudioContext || window.webkitAudioContext)();
          const osc = ctx.createOscillator();
          const gain = ctx.createGain();
          
          osc.type = 'sawtooth';
          osc.frequency.setValueAtTime(180, ctx.currentTime); // low buzz
          osc.frequency.setValueAtTime(120, ctx.currentTime + 0.15); // lower buzz
          
          gain.gain.setValueAtTime(0.2, ctx.currentTime);
          gain.gain.exponentialRampToValueAtTime(0.005, ctx.currentTime + 0.45);
          
          osc.connect(gain);
          gain.connect(ctx.destination);
          
          osc.start();
          osc.stop(ctx.currentTime + 0.45);
        } catch(e) {
          console.error("Audio API error:", e);
        }
      }

      // Scanner Toggle Click
      btnCameraToggle.addEventListener("click", () => {
        if (!isScanning) {
          startScanner();
        } else {
          stopScanner();
        }
      });

      function startScanner() {
        scannerPlaceholder.classList.add("hidden");
        btnCameraToggle.disabled = true;
        
        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
          { facingMode: "environment" }, // Rear camera
          {
            fps: 10,
            qrbox: { width: 250, height: 250 }
          },
          onScanSuccess,
          onScanFailure
        ).then(() => {
          isScanning = true;
          btnCameraToggle.disabled = false;
          btnCameraToggle.innerText = "Matikan Kamera";
          btnCameraToggle.classList.replace("bg-secondary", "bg-red-600");
          btnCameraToggle.classList.replace("text-primary", "text-white");
        }).catch(err => {
          btnCameraToggle.disabled = false;
          scannerPlaceholder.classList.remove("hidden");
          console.error("Failed to start camera:", err);
          alert("Gagal mengakses kamera. Pastikan ijin kamera telah diaktifkan.");
        });
      }

      function stopScanner() {
        if (html5QrcodeScanner) {
          btnCameraToggle.disabled = true;
          html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            btnCameraToggle.disabled = false;
            btnCameraToggle.innerText = "Mulai Kamera";
            btnCameraToggle.classList.replace("bg-red-600", "bg-secondary");
            btnCameraToggle.classList.replace("text-white", "text-primary");
            scannerPlaceholder.classList.remove("hidden");
          }).catch(err => {
            console.error("Error stopping camera:", err);
          });
        }
      }

      function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning temporarily
        stopScanner();
        
        // QR Code will encode a URL like: http://domain/checkin.php?code=MS-XYZ
        let code = decodedText;
        if (decodedText.includes('code=')) {
          const urlParams = new URLSearchParams(decodedText.split('?')[1]);
          code = urlParams.get('code') || decodedText;
        }

        submitCheckin(code);
      }

      function onScanFailure(error) {
        // Silence scan errors, as they fire constantly when QR is not in view
      }

      // AJAX Attendance check-in submitter
      function submitCheckin(code) {
        fetch('api.php?action=checkin', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
          displayCheckinResult(data);
          if (data.success) {
            updateCheckinHistory();
          }
        })
        .catch(err => {
          console.error(err);
          playErrorBeep();
          alert("Koneksi gagal. Periksa koneksi internet Anda.");
          // Restart scanner
          startScanner();
        });
      }

      // Render Check-in response UI
      function displayCheckinResult(data) {
        const modal = document.getElementById("result-modal");
        const card = document.getElementById("result-card");
        const icon = document.getElementById("result-icon");
        const title = document.getElementById("result-title");
        const resName = document.getElementById("res-name");
        const resClass = document.getElementById("res-class");
        const resCompanion = document.getElementById("res-companion");
        const resTime = document.getElementById("res-time");

        if (data.success) {
          playSuccessBeep();
          
          title.innerText = data.student.already_checked_in ? "SUDAH CHECK-IN" : "CHECK-IN BERHASIL";
          title.classList.replace("text-red-500", "text-[#25D366]");
          icon.innerText = "check_circle";
          icon.className = "material-symbols-outlined text-6xl mb-4 text-[#25D366] bg-[#25D366]/5 rounded-full p-2";
          card.classList.replace("border-red-600/30", "border-[#25D366]/30");
          
          resName.innerText = data.student.name;
          resClass.innerText = data.student.classroom;
          
          let companionName = "Tanpa Pendamping";
          if (data.student.companion_type === 'parents') companionName = "Orang Tua / Wali";
          else if (data.student.companion_type === 'sibling') companionName = "Saudara / Kerabat";
          resCompanion.innerText = companionName;
          
          // Format current time
          const checkinTime = new Date(data.student.checked_in_at);
          resTime.innerText = checkinTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        } else {
          playErrorBeep();
          
          title.innerText = "SCAN GAGAL";
          title.classList.replace("text-[#25D366]", "text-red-500");
          icon.innerText = "error";
          icon.className = "material-symbols-outlined text-6xl mb-4 text-red-500 bg-red-500/5 rounded-full p-2";
          card.classList.replace("border-[#25D366]/30", "border-red-600/30");
          
          resName.innerText = data.message || "Kode tidak valid / tidak dikenal.";
          resClass.innerText = "UNKNOWN";
          resCompanion.innerText = "-";
          resTime.innerText = "-";
        }

        // Show Modal
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        setTimeout(() => {
          card.classList.replace("scale-95", "scale-100");
          card.classList.replace("opacity-0", "opacity-100");
        }, 50);
      }

      // Close modal & resume scanning
      document.getElementById("btn-close-modal").addEventListener("click", () => {
        const modal = document.getElementById("result-modal");
        const card = document.getElementById("result-card");
        
        card.classList.replace("scale-100", "scale-95");
        card.classList.replace("opacity-100", "opacity-0");
        
        setTimeout(() => {
          modal.classList.remove("flex");
          modal.classList.add("hidden");
          
          // Restart scanner if it was open before checkin
          startScanner();
        }, 150);
      });

      // Manual input submit
      document.getElementById("btn-manual-submit").addEventListener("click", () => {
        const codeInput = document.getElementById("manual-code");
        const code = codeInput.value.trim().toUpperCase();
        if (code !== "") {
          submitCheckin(code);
          codeInput.value = "";
        }
      });

      document.getElementById("manual-code").addEventListener("keypress", (e) => {
        if (e.key === 'Enter') {
          document.getElementById("btn-manual-submit").click();
        }
      });

      // Update UI History Logs & Stats Counters
      function updateCheckinHistory() {
        fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
          // Parse HTML to update list and counts
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          
          const newList = doc.getElementById('checkin-list').innerHTML;
          document.getElementById('checkin-list').innerHTML = newList;

          const newPresent = doc.getElementById('stat-present').innerText;
          document.getElementById('stat-present').innerText = newPresent;

          const newTotal = doc.getElementById('stat-total').innerText;
          document.getElementById('stat-total').innerText = newTotal;
        })
        .catch(err => console.error("Error refreshing lists:", err));
      }
    </script>
  </body>
</html>
