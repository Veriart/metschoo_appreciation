<?php
// config.php
// Database configuration and auto-initialization script

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'appr_metschoo';

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
        `classroom` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Check and add classroom column to users table if not exists
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'classroom'")->fetch();
        if (!$checkColumn) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `classroom` VARCHAR(50) DEFAULT NULL AFTER `password`");
        }
    } catch (PDOException $e) {
        // Silence column already exists error
    }

} catch (PDOException $e) {
    die("Failed to create tables: " . $e->getMessage());
}

// 4. Helper function to generate unique codes
function generateUniqueCode($pdo) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Avoid confusing characters like I, O, 0, 1
    do {
        $code = 'MS-';
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
        ['id' => 1, 'classroom' => 'XII AKUNTANSI', 'name' => 'ADE GUSTI PRIYATNA'],
        ['id' => 2, 'classroom' => 'XII AKUNTANSI', 'name' => 'KENZIE LAUWIJAYA'],
        ['id' => 3, 'classroom' => 'XII AKUNTANSI', 'name' => 'MUHAMMAD IHSAN ANSHORI'],
        ['id' => 4, 'classroom' => 'XII AKUNTANSI', 'name' => 'RAFFI AZKA GERRARD AMERAL'],
        ['id' => 5, 'classroom' => 'XII AKUNTANSI', 'name' => 'ANISA HERPIAH'],
        ['id' => 6, 'classroom' => 'XII AKUNTANSI', 'name' => 'ANISA NAELATUL IZZAH'],
        ['id' => 7, 'classroom' => 'XII AKUNTANSI', 'name' => 'DESVITA MAHARANI'],
        ['id' => 8, 'classroom' => 'XII AKUNTANSI', 'name' => 'IMELDA ANATASYA FELISHA'],
        ['id' => 9, 'classroom' => 'XII AKUNTANSI', 'name' => 'INDRI ARIANI PUTRI'],
        ['id' => 10, 'classroom' => 'XII AKUNTANSI', 'name' => 'JEEHAN KHAIRUNNISA'],
        ['id' => 11, 'classroom' => 'XII AKUNTANSI', 'name' => 'JESSICA THALIA SAPULETTE'],
        ['id' => 12, 'classroom' => 'XII AKUNTANSI', 'name' => 'JIHAN FIRDAUSI AHLA'],
        ['id' => 13, 'classroom' => 'XII AKUNTANSI', 'name' => 'KALYCA SASHIKIRANA'],
        ['id' => 14, 'classroom' => 'XII AKUNTANSI', 'name' => 'KHANSAA NAYLA WIBOWO'],
        ['id' => 15, 'classroom' => 'XII AKUNTANSI', 'name' => 'MASSYITOH NASYIFA WARDA BACHMID'],
        ['id' => 16, 'classroom' => 'XII AKUNTANSI', 'name' => 'MICHELLE GRACELINA JESLIN'],
        ['id' => 17, 'classroom' => 'XII AKUNTANSI', 'name' => 'NAYLA ZAHRATUSSHITA HERMAWAN'],
        ['id' => 18, 'classroom' => 'XII AKUNTANSI', 'name' => 'NIKEISHA AQILAH NADZHEFA KHOLIK'],
        ['id' => 19, 'classroom' => 'XII AKUNTANSI', 'name' => 'ONG DEVINA'],
        ['id' => 20, 'classroom' => 'XII AKUNTANSI', 'name' => 'REIVADISTY AZKIA FAIRUZ'],
        ['id' => 21, 'classroom' => 'XII AKUNTANSI', 'name' => 'SHEIRA CLARA ANNICATI'],
        ['id' => 22, 'classroom' => 'XII AKUNTANSI', 'name' => 'TIARA NUR FEBRIANA'],
        ['id' => 23, 'classroom' => 'XII AKUNTANSI', 'name' => 'WINDY RAMADHANI'],
        ['id' => 24, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'ALILA AZAHRA'],
        ['id' => 25, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'ALISHA NURHIDAYAH'],
        ['id' => 26, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'CHELSEA VIORENZA'],
        ['id' => 27, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'FELICIA NAOMI'],
        ['id' => 28, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'JENI VALENCIA'],
        ['id' => 29, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'JENNIFER'],
        ['id' => 30, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'JESICA LAURA SITORUS'],
        ['id' => 31, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'JUWITA ANASTASYA SASI'],
        ['id' => 32, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'KALILA OKTARIANI'],
        ['id' => 33, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'KATHERINA ANGIE GUNAWAN SUJANA'],
        ['id' => 34, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'MELIANA GRACELLA'],
        ['id' => 35, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'NABILAH ALMUGHNIY HAMDINI'],
        ['id' => 36, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'NADINE NAKEISHA AUREL HALOANA`A'],
        ['id' => 37, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'RADISTY ACHMAD AYUDIA PUTRI'],
        ['id' => 38, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'SOFI ANITA DWI ANGRAINI'],
        ['id' => 39, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'TANIA'],
        ['id' => 40, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'TANZILLA ASYAFFA'],
        ['id' => 41, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'TRICIA CONG'],
        ['id' => 42, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'ABSHAR NABIL KURNIAWAN'],
        ['id' => 43, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'ANDREAS INESTA PERANGIN ANGIN'],
        ['id' => 44, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'HENGKY'],
        ['id' => 45, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'JUSTIN ALEXANDER O\'REILLY'],
        ['id' => 46, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'MUHAMMAD KHARISH AL- FARELL LESMANA'],
        ['id' => 47, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'MUHAMMAD RASYA ARAFI'],
        ['id' => 48, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'NARENDRAPUTRA AKBAR MAULANA IBROHIM'],
        ['id' => 49, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'NELSON EDRIC TAN COLLIN'],
        ['id' => 50, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'RAFAEL ADI PUTRA PRATOMO'],
        ['id' => 51, 'classroom' => 'XII HOSPITALITY 1', 'name' => 'YEREMIA DANIEL LIKUMAHUA'],
        ['id' => 52, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'ABIE ANANDA SITEPU'],
        ['id' => 53, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'FARRELLY XAVIOR HARVEY'],
        ['id' => 54, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'GAFFARA DIANDRA ALJERBI'],
        ['id' => 55, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'LEXI VHETO CARLOTTA'],
        ['id' => 56, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'ORVALA RAVAN AREZA'],
        ['id' => 57, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'RADITYA DANDI MORELO'],
        ['id' => 58, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'RAFAEL GIVENDRA CHATRA'],
        ['id' => 59, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'RAFAEL SIMON HASIAN TAMBUNAN'],
        ['id' => 60, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'RIZIEQ MAULANA'],
        ['id' => 61, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'TLAGA RENA MAHA WIJAYA'],
        ['id' => 62, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'YESAYA KESLY ADHIGUNA RUMBARAR'],
        ['id' => 63, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'ABIGAEL JOCELYN NAINGGOLAN'],
        ['id' => 64, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'AZZAHRA SYABILA ALMAQVHIRA'],
        ['id' => 65, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'CITRA PUTRI LESTARI'],
        ['id' => 66, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'FITA PRAMESTI'],
        ['id' => 67, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'GHISSYA TITIE AISYANI'],
        ['id' => 68, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'HELCEIRA GRACIA RHEMREV'],
        ['id' => 69, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'LOVELY PERMATA HALLATU'],
        ['id' => 70, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'NADINE ARLITA PUTRI'],
        ['id' => 71, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'RAISA TRI AMANDA'],
        ['id' => 72, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'ROSALIA PANCA HANDAYANI'],
        ['id' => 73, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'ROSLINA DWI PUTRI'],
        ['id' => 74, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'SYAHLA SALWA NAFEEZA'],
        ['id' => 75, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'THALITA AULIA'],
        ['id' => 76, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'YUESA CHIN'],
        ['id' => 77, 'classroom' => 'XII HOSPITALITY 2', 'name' => 'ZAHRA NURUL ANGGRAENI'],
        ['id' => 78, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'ADRIANO GARCIA'],
        ['id' => 79, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'AFGAN SYAHREZA'],
        ['id' => 80, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'AZRA RYAN SETIANSYAH'],
        ['id' => 81, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'FERY FERDIAN'],
        ['id' => 82, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'JONATHAN RIZKI BUTAR BUTAR'],
        ['id' => 83, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'MICHAEL DAVE REYVAN PATTIASINA'],
        ['id' => 84, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'MUHAMAD ALFARIZI SINGGIH'],
        ['id' => 85, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'PANJI NITOLO ZENDRATO'],
        ['id' => 86, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'QALBIE ADHA AL BARAKI'],
        ['id' => 87, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'RIQUELO ALBERTO MENY'],
        ['id' => 88, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'SAMUEL ARAN TAMPUBOLON'],
        ['id' => 89, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'TATA BANGSA PUTRA VIDYA'],
        ['id' => 90, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'ADELIA PERMATA SARI'],
        ['id' => 91, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'ALYA SUNGKAR'],
        ['id' => 92, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'ARKALUNA LAKSMITHA RINOLA'],
        ['id' => 93, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'CAHAYA ZASKIYA VIOLA KHASANAH'],
        ['id' => 94, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'CALLISTA OCTAVIA ANGELINE'],
        ['id' => 95, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'DANIA NIDAUR RAHMA'],
        ['id' => 96, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'DEWI SETIANINGSIH'],
        ['id' => 97, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'JESSICA SHAMMAH PANGARIBUAN'],
        ['id' => 98, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'KESYA PUTRI SUNDARI'],
        ['id' => 99, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'NIKEISHA ANINDYA'],
        ['id' => 100, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'RACHEL MARCELIA KEIKO'],
        ['id' => 101, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'RAFITA AMANDA PUTI'],
        ['id' => 102, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'RAYA HAYA ALIFAH'],
        ['id' => 103, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'REISYA AZZAHRA FAUZIAH'],
        ['id' => 104, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'SASKIA MAULIDINA SETIAWAN'],
        ['id' => 105, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'TIARA RAMANDHA SYAFITRI'],
        ['id' => 106, 'classroom' => 'XII HOSPITALITY 3', 'name' => 'WARDAH NABILA SHAFAA'],
        ['id' => 107, 'classroom' => 'XII DKV I', 'name' => 'AURELIO ANAUSKA ARIOWIBAWA'],
        ['id' => 108, 'classroom' => 'XII DKV I', 'name' => 'DIMAS RAIHAN HADIYANTO'],
        ['id' => 109, 'classroom' => 'XII DKV I', 'name' => 'FAISAL SUBOWO'],
        ['id' => 110, 'classroom' => 'XII DKV I', 'name' => 'FAVIAN MAHARDIKA PRATAMA'],
        ['id' => 111, 'classroom' => 'XII DKV I', 'name' => 'GREGORY CORNELIS NATHANIEL MALELAK'],
        ['id' => 112, 'classroom' => 'XII DKV I', 'name' => 'HASAN'],
        ['id' => 113, 'classroom' => 'XII DKV I', 'name' => 'IGNASIUS JULIO ABIGEL AUDYANTORO'],
        ['id' => 114, 'classroom' => 'XII DKV I', 'name' => 'KELVIN FRANCAIS'],
        ['id' => 115, 'classroom' => 'XII DKV I', 'name' => 'MUHAMMAD ADIEN GUMILANG'],
        ['id' => 116, 'classroom' => 'XII DKV I', 'name' => 'MUHAMMAD FADILLAH RASYID'],
        ['id' => 117, 'classroom' => 'XII DKV I', 'name' => 'NATHANAEL BAGAWANTA'],
        ['id' => 118, 'classroom' => 'XII DKV I', 'name' => 'RHESA BAGUS BUNTARA'],
        ['id' => 119, 'classroom' => 'XII DKV I', 'name' => 'VINCENT STEVEN'],
        ['id' => 120, 'classroom' => 'XII DKV I', 'name' => 'AGATHA DEVI KINARDYASARI'],
        ['id' => 121, 'classroom' => 'XII DKV I', 'name' => 'ANISA GLORIA SIBURIAN'],
        ['id' => 122, 'classroom' => 'XII DKV I', 'name' => 'AWANADA YURO VINORIA'],
        ['id' => 123, 'classroom' => 'XII DKV I', 'name' => 'DHIYA DEVINA DYANAMITHA'],
        ['id' => 124, 'classroom' => 'XII DKV I', 'name' => 'DZIKRA FITRI CHANDRA'],
        ['id' => 125, 'classroom' => 'XII DKV I', 'name' => 'GABRIELA KAORI PUTRI SASONO'],
        ['id' => 126, 'classroom' => 'XII DKV I', 'name' => 'JOSEPHINE CALISTA LIMBUNAN'],
        ['id' => 127, 'classroom' => 'XII DKV I', 'name' => 'KIORA NUR AZKIA KUSNADI'],
        ['id' => 128, 'classroom' => 'XII DKV I', 'name' => 'LIFIKA APRILIZ'],
        ['id' => 129, 'classroom' => 'XII DKV I', 'name' => 'MARGARETH CHELSEA GENEVA OSE'],
        ['id' => 130, 'classroom' => 'XII DKV I', 'name' => 'PELANGI MIREKEL BUNGAS'],
        ['id' => 131, 'classroom' => 'XII DKV I', 'name' => 'RAIZEL LESMANA AL GHANIYAH'],
        ['id' => 132, 'classroom' => 'XII DKV I', 'name' => 'RIFKA PUTRI FELINDA'],
        ['id' => 133, 'classroom' => 'XII DKV I', 'name' => 'SAFINA FELICIA PUTRI ANDINI'],
        ['id' => 134, 'classroom' => 'XII DKV II', 'name' => 'AIKEN IJAZ'],
        ['id' => 135, 'classroom' => 'XII DKV II', 'name' => 'AKBAR RIZAL ADHITAMA'],
        ['id' => 136, 'classroom' => 'XII DKV II', 'name' => 'ALVIN AGANTHA'],
        ['id' => 137, 'classroom' => 'XII DKV II', 'name' => 'BAMAPRIMA MANGGALA UTOMO'],
        ['id' => 138, 'classroom' => 'XII DKV II', 'name' => 'DIMAS AZKA ANADYA'],
        ['id' => 139, 'classroom' => 'XII DKV II', 'name' => 'EDWARD CHEN'],
        ['id' => 140, 'classroom' => 'XII DKV II', 'name' => 'HAIDAR ARIB GHULAM WIBAWA'],
        ['id' => 141, 'classroom' => 'XII DKV II', 'name' => 'JECHONIAH BENAFIRI RUMAHORBO'],
        ['id' => 142, 'classroom' => 'XII DKV II', 'name' => 'JONATHAN MOZART WARANEY WENAS'],
        ['id' => 143, 'classroom' => 'XII DKV II', 'name' => 'M. DAFFA HABIBI HARAHAP'],
        ['id' => 144, 'classroom' => 'XII DKV II', 'name' => 'MICHAEL ALEXANDER HERMANTO'],
        ['id' => 145, 'classroom' => 'XII DKV II', 'name' => 'MOH. FARELIO ALSADIN'],
        ['id' => 146, 'classroom' => 'XII DKV II', 'name' => 'MUHAMMAD HATTA AL ATABY'],
        ['id' => 147, 'classroom' => 'XII DKV II', 'name' => 'REZA FADIL AKBAR'],
        ['id' => 148, 'classroom' => 'XII DKV II', 'name' => 'YOGI MUHAMMAD RAMDHANY'],
        ['id' => 149, 'classroom' => 'XII DKV II', 'name' => 'ANDHINI HUMAIRA'],
        ['id' => 150, 'classroom' => 'XII DKV II', 'name' => 'BILQIS NINDIRA SYAKILA'],
        ['id' => 151, 'classroom' => 'XII DKV II', 'name' => 'CHARISA JONES'],
        ['id' => 152, 'classroom' => 'XII DKV II', 'name' => 'CLARISA YUNADI'],
        ['id' => 153, 'classroom' => 'XII DKV II', 'name' => 'DARLEEN MACYA PASCAL KURNIAWAN'],
        ['id' => 154, 'classroom' => 'XII DKV II', 'name' => 'JEAN AURA THERESIA WIBOWO'],
        ['id' => 155, 'classroom' => 'XII DKV II', 'name' => 'KALINDA TUNGGA FENURA PRAKOSO'],
        ['id' => 156, 'classroom' => 'XII DKV II', 'name' => 'NI NYOMAN DEVINA'],
        ['id' => 157, 'classroom' => 'XII DKV II', 'name' => 'SHAREN DOMINIQUE'],
        ['id' => 158, 'classroom' => 'XII DKV II', 'name' => 'STARLIN NATASHA KODYAT'],
        ['id' => 159, 'classroom' => 'XII DKV II', 'name' => 'SUCI NUR RAMADHANI'],
        ['id' => 160, 'classroom' => 'XII DKV II', 'name' => 'SYAFIQA NAILINA ZAHWA'],
        ['id' => 161, 'classroom' => 'XII DKV II', 'name' => 'TIFFANY WILSON'],
        ['id' => 162, 'classroom' => 'XII DKV II', 'name' => 'TSABITAH MELIANI PUTRI'],
        ['id' => 163, 'classroom' => 'XII DKV II', 'name' => 'VRIESKA AUDREY RASYLLARTI'],
        ['id' => 164, 'classroom' => 'XII PPLG I', 'name' => 'ALIF NURHIDAYAT'],
        ['id' => 165, 'classroom' => 'XII PPLG I', 'name' => 'ARKAN AMANATUL WAHAB'],
        ['id' => 166, 'classroom' => 'XII PPLG I', 'name' => 'BAMBANG WIJAYA PRINARI'],
        ['id' => 167, 'classroom' => 'XII PPLG I', 'name' => 'BERGAS AHZA MARGOSHA'],
        ['id' => 168, 'classroom' => 'XII PPLG I', 'name' => 'DAFFA ADITYA PRATAMA JATI'],
        ['id' => 169, 'classroom' => 'XII PPLG I', 'name' => 'ERIK RAJA LORENSO HUTASOIT'],
        ['id' => 170, 'classroom' => 'XII PPLG I', 'name' => 'FAREL PRADITYA EFFENDI'],
        ['id' => 171, 'classroom' => 'XII PPLG I', 'name' => 'FERRY MAULANA MALIK IBRAHIM'],
        ['id' => 172, 'classroom' => 'XII PPLG I', 'name' => 'JIBRIL NASRULLAH'],
        ['id' => 173, 'classroom' => 'XII PPLG I', 'name' => 'JONATHAN SAMUEL'],
        ['id' => 174, 'classroom' => 'XII PPLG I', 'name' => 'MAHER SYALAL JOSE INDIARTO'],
        ['id' => 175, 'classroom' => 'XII PPLG I', 'name' => 'MARVEL CHANDRA'],
        ['id' => 176, 'classroom' => 'XII PPLG I', 'name' => 'MATTHEW SEQUAIA MARVINO METEKOHY'],
        ['id' => 177, 'classroom' => 'XII PPLG I', 'name' => 'NEMO ARJUNA SETA'],
        ['id' => 178, 'classroom' => 'XII PPLG I', 'name' => 'REVHAN SHIDQI RIEFANIPUTRA'],
        ['id' => 179, 'classroom' => 'XII PPLG I', 'name' => 'YOEL ALBERTH MANALU'],
        ['id' => 180, 'classroom' => 'XII PPLG I', 'name' => 'YUDE ADY HARIZHKY'],
        ['id' => 181, 'classroom' => 'XII PPLG I', 'name' => 'CLARISSA FEODORA TANJAYA'],
        ['id' => 182, 'classroom' => 'XII PPLG I', 'name' => 'SYIFA RAHMADANI'],
        ['id' => 183, 'classroom' => 'XII PPLG II', 'name' => 'ANTONIUS ADI NUGROHO'],
        ['id' => 184, 'classroom' => 'XII PPLG II', 'name' => 'ARDENTA PRADA NIRVANA'],
        ['id' => 185, 'classroom' => 'XII PPLG II', 'name' => 'BILLY IBRAHIM JOUAQIN'],
        ['id' => 186, 'classroom' => 'XII PPLG II', 'name' => 'CERGIO CAESAR SHAQUELLE AL ZAIDDANE'],
        ['id' => 187, 'classroom' => 'XII PPLG II', 'name' => 'DAFFA DZULHILMI'],
        ['id' => 188, 'classroom' => 'XII PPLG II', 'name' => 'DEVANS VICTORINUS'],
        ['id' => 189, 'classroom' => 'XII PPLG II', 'name' => 'DIONISIUS DESTA PUTRANTO'],
        ['id' => 190, 'classroom' => 'XII PPLG II', 'name' => 'FIRTSAN ROMARIO KARDONA'],
        ['id' => 191, 'classroom' => 'XII PPLG II', 'name' => 'GREGORIUS CALVIN JABAR'],
        ['id' => 192, 'classroom' => 'XII PPLG II', 'name' => 'IGNACIO RICARDO WATA'],
        ['id' => 193, 'classroom' => 'XII PPLG II', 'name' => 'LIONEL GERRARD'],
        ['id' => 194, 'classroom' => 'XII PPLG II', 'name' => 'M. AL MUHAIMIN RAZAQ'],
        ['id' => 195, 'classroom' => 'XII PPLG II', 'name' => 'MAULIDAN NADZAR RUSANO'],
        ['id' => 196, 'classroom' => 'XII PPLG II', 'name' => 'MUHAMMAD SYAIFULLAH AL GHIFARI'],
        ['id' => 197, 'classroom' => 'XII PPLG II', 'name' => 'MUHAMMAD VITO ANDRYAN'],
        ['id' => 198, 'classroom' => 'XII PPLG II', 'name' => 'QRI KADENIS SANTARA'],
        ['id' => 199, 'classroom' => 'XII PPLG II', 'name' => 'ROBBY PUTRA ADINATA'],
        ['id' => 200, 'classroom' => 'XII PPLG II', 'name' => 'SEPTIAN FAUZI'],
        ['id' => 201, 'classroom' => 'XII PPLG II', 'name' => 'VALENTINO SETIAWAN'],
        ['id' => 202, 'classroom' => 'XII PPLG II', 'name' => 'ANYELIR SASKY KIRANA'],
        ['id' => 203, 'classroom' => 'XII PPLG II', 'name' => 'NAILAH ZAHRAH HAFRIYANI'],
        ['id' => 204, 'classroom' => 'XII PPLG II', 'name' => 'SALWA DESWINTA SARI'],
        ['id' => 205, 'classroom' => 'XII PPLG II', 'name' => 'SAMAHITA YUMNA ACINTYA'],
    ];

    $insertStmt = $pdo->prepare("INSERT INTO students (id, code, name, classroom) VALUES (?, ?, ?, ?)");
    foreach ($studentsToSeed as $student) {
        $code = generateUniqueCode($pdo);
        $insertStmt->execute([$student['id'], $code, $student['name'], $student['classroom']]);
    }
}
?>
