<?php
// config.php
// Database configuration and auto-initialization script

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'gcp_metland';

try {
    // 1. Connect to MySQL server (without specifying DB first, in case it doesn't exist)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // 2. Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. Create tables if they do not exist
try {
    // Students table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `students` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `classroom` VARCHAR(50) NOT NULL,
        `whatsapp` VARCHAR(20) DEFAULT NULL,
        `rsvp_status` VARCHAR(20) DEFAULT 'Pending',
        `companion_type` VARCHAR(50) DEFAULT 'none',
        `checked_in` TINYINT(1) DEFAULT 0,
        `checked_in_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Users table for Admin
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

} catch (PDOException $e) {
    die("Failed to create tables: " . $e->getMessage());
}

// 4. Helper function to generate unique codes
function generateUniqueCode($pdo) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Avoid confusing characters like I, O, 0, 1
    do {
        $code = 'GCP-';
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    return $code;
}

// 5. Seed default admin if table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $insertAdmin = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $insertAdmin->execute(['admin', $hashedPassword]);
}

// 6. Seed the 45 students from the user list if table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM students");
if ($stmt->fetchColumn() == 0) {
    $studentsToSeed = [
        // XII AKT
        ['classroom' => 'XII AKT', 'name' => 'NAYLA ZAHRATUSSHITA HERMAWAN'],
        ['classroom' => 'XII AKT', 'name' => 'ANISA HERPIAH'],
        ['classroom' => 'XII AKT', 'name' => 'KALYCA SASHIKIRANA'],
        ['classroom' => 'XII AKT', 'name' => 'ADE GUSTI PRIYATNA'],
        ['classroom' => 'XII AKT', 'name' => 'MICHELLE GRACELINA JESLIN'],
        ['classroom' => 'XII AKT', 'name' => 'REIVADISTY AZKIA FAIRUZ'],
        ['classroom' => 'XII AKT', 'name' => 'Kenzie Lauwijaya'],
        ['classroom' => 'XII AKT', 'name' => 'TIARA NUR FEBRIANA'],

        // XII HOS 1
        ['classroom' => 'XII HOS 1', 'name' => 'Chelsea Viorenza'],
        ['classroom' => 'XII HOS 1', 'name' => 'MELIANA GRACELLA'],
        ['classroom' => 'XII HOS 1', 'name' => 'JENNIFER'],
        ['classroom' => 'XII HOS 1', 'name' => 'MUHAMMAD RASYA ARAFI'],
        ['classroom' => 'XII HOS 1', 'name' => 'ALISHA NURHIDAYAH'],
        ['classroom' => 'XII HOS 1', 'name' => 'Abshar Nabil Kurniawan'],
        ['classroom' => 'XII HOS 1', 'name' => 'Nadine Nakeisha Aurel Haloana\'a'],
        ['classroom' => 'XII HOS 1', 'name' => 'NARENDRAPUTRA AKBAR MAULANA IBROHIM'],
        ['classroom' => 'XII HOS 1', 'name' => 'YEREMIA DANIEL LIKUMAHUA'],

        // XII HOS 2
        ['classroom' => 'XII HOS 2', 'name' => 'SYAHLA SALWA NAFEEZA'],
        ['classroom' => 'XII HOS 2', 'name' => 'Helceira Gracia Rhemrev'],
        ['classroom' => 'XII HOS 2', 'name' => 'ABIGAEL JOCELYN NAINGGOLAN'],
        ['classroom' => 'XII HOS 2', 'name' => 'GAFFARA DIANDRA AL JERBI'],
        ['classroom' => 'XII HOS 2', 'name' => 'Citra Putri Lestari'],
        ['classroom' => 'XII HOS 2', 'name' => 'LOVELY PERMATA HALLATU'],

        // XII HOS 3
        ['classroom' => 'XII HOS 3', 'name' => 'TATA BANGSA PUTRA VIDYA'],
        ['classroom' => 'XII HOS 3', 'name' => 'DEWI SETIANINGSIH'],
        ['classroom' => 'XII HOS 3', 'name' => 'CALLISTA OCTAVIA ANGELINE'],
        ['classroom' => 'XII HOS 3', 'name' => 'AFGAN SYAHREZA'],
        ['classroom' => 'XII HOS 3', 'name' => 'Azra Ryan Setiansyah'],
        ['classroom' => 'XII HOS 3', 'name' => 'Muhamad Alfarizi Singgih'],

        // XII DKV 1
        ['classroom' => 'XII DKV 1', 'name' => 'IGNASIUS JULIO ABIGEL AUDYANTORO'],
        ['classroom' => 'XII DKV 1', 'name' => 'NATHANAEL BAGAWANTA'],
        ['classroom' => 'XII DKV 1', 'name' => 'MUHAMMAD FADILLAH RASYID'],

        // XII DKV 2
        ['classroom' => 'XII DKV 2', 'name' => 'M DAFFA HABIBI HARAHAP'],
        ['classroom' => 'XII DKV 2', 'name' => 'SYAFIQA NAILINA ZAHWA'],
        ['classroom' => 'XII DKV 2', 'name' => 'KALINDA TUNGGA FENURA PRAKOSO'],
        ['classroom' => 'XII DKV 2', 'name' => 'BILQIS NINDIRA SYAKILA'],

        // XII PPLG 1
        ['classroom' => 'XII PPLG 1', 'name' => 'Clarissa Feodora Tanjaya'],
        ['classroom' => 'XII PPLG 1', 'name' => 'ALIF NURHIDAYAT'],
        ['classroom' => 'XII PPLG 1', 'name' => 'BAMBANG WIJAYA PRINARI'],
        ['classroom' => 'XII PPLG 1', 'name' => 'JONATHAN SAMUEL'],
        ['classroom' => 'XII PPLG 1', 'name' => 'Jibril Nasrullah'],

        // XII PPLG 2
        ['classroom' => 'XII PPLG 2', 'name' => 'BILLY IBRAHIM JOUAQIN'],
        ['classroom' => 'XII PPLG 2', 'name' => 'Valentino Setiawan'],
        ['classroom' => 'XII PPLG 2', 'name' => 'ARDENTA PRADA NIRVANA'],
        ['classroom' => 'XII PPLG 2', 'name' => 'SAMAHITA YUMNA ACINTYA'],
    ];

    $insertStmt = $pdo->prepare("INSERT INTO students (code, name, classroom) VALUES (?, ?, ?)");
    foreach ($studentsToSeed as $student) {
        $code = generateUniqueCode($pdo);
        $insertStmt->execute([$code, $student['name'], $student['classroom']]);
    }
}
?>
