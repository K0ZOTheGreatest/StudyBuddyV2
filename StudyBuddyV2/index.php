<?php
// index.php - Halaman Utama Terintegrasi Database
session_start();
include('config.php');

// 1. Logika Proses Log Masuk / Registrasi Otomatis
if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$user'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['password'] == $pass) { // Untuk kesederhanaan tutorial
            $_SESSION['studybuddy_session'] = $user;
        } else {
            echo "<script>alert('Kata sandi salah!');</script>";
        }
    } else {
        // Jika user belum ada, otomatis daftarkan
        $conn->query("INSERT INTO users (username, password) VALUES ('$user', '$pass')");
        $_SESSION['studybuddy_session'] = $user;
    }
    header("Location: index.php");
    exit();
}

// 2. Logika Log Keluar
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// 3. Logika Proses Muat Naik Nota ke Database
if (isset($_POST['upload']) && isset($_SESSION['studybuddy_session'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $currentUser = $_SESSION['studybuddy_session'];

    if (!empty($_FILES['noteFile']['name'])) {
        $fileName = $_FILES['noteFile']['name'];
        $fileTmp  = $_FILES['noteFile']['tmp_name'];
        $fileData = addslashes(file_get_contents($fileTmp)); // Membaca file menjadi binary

        $sql = "INSERT INTO notes (title, subject, file_name, file_data, uploaded_by) 
                VALUES ('$title', '$subject', '$fileName', '$fileData', '$currentUser')";
        
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Nota berhasil dibagikan ke database!'); window.location='index.php';</script>";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// 4. Logika Hapus Nota
if (isset($_GET['delete']) && isset($_SESSION['studybuddy_session'])) {
    $id = $_GET['delete'];
    $currentUser = $_SESSION['studybuddy_session'];
    // Memastikan hanya pemilik yang bisa menghapus
    $conn->query("DELETE FROM notes WHERE id=$id AND uploaded_by='$currentUser'");
    header("Location: index.php");
    exit();
}

// 5. Logika Download / Unduh File dari Database
if (isset($_GET['download'])) {
    $id = $_GET['download'];
    $result = $conn->query("SELECT * FROM notes WHERE id=$id");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=" . $row['file_name']);
        echo $row['file_data'];
        exit();
    }
}

// 6. Ambil Data Nota untuk Ditampilkan (Termasuk Fungsi Pencarian)
$search = "";
if (isset($_POST['searchInp'])) {
    $search = mysqli_real_escape_string($conn, $_POST['searchInp']);
    $query = "SELECT * FROM notes WHERE title LIKE '%$search%' OR subject LIKE '%$search%' ORDER BY id DESC";
} else {
    $query = "SELECT * FROM notes ORDER BY id DESC";
}
$notes_result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy - Hab Perkongsian Nota Moden</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">

    <?php if (!isset($_SESSION['studybuddy_session'])): ?>
    <div id="loginSection" style="display: block;">
        <h2>🔒 Log Masuk</h2>
        <p>Akses akun Anda atau daftar masuk secara otomatis.</p>
        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Pengguna</label>
                <input type="text" name="username" placeholder="Masukkan nama Anda" required>
            </div>
            <div class="form-group">
                <label>Kata Sandi</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" style="width: 100%;" class="btn-accent">Masuk Ke Sistem</button>
        </form>
    </div>

    <?php else: ?>
    <div id="mainSection" style="display: block;">
        
        <div class="header-status">
            <div>Akun Aktif: <b style="color: var(--accent);"><?php echo $_SESSION['studybuddy_session']; ?></b></div>
            <a href="index.php?logout=true"><button class="btn-danger" style="padding: 6px 14px; font-size: 0.85rem;">Log Keluar</button></a>
        </div>

        <div class="hero-banner">
            <h1>📚 StudyBuddy</h1>
            <p>Platform perkongsian berpusat. Permudahkan pencarian nota kuliah, slaid, dan bahan rujukan akademik bersama rakan sekelas anda.</p>
        </div>

        <div class="main-grid">
            
            <div class="card">
                <h3>✨ Muat Naik Nota</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Tajuk Nota</label>
                        <input type="text" name="title" placeholder="Contoh: Bab 1 Pengenalan PHP" required>
                    </div>
                    <div class="form-group">
                        <label>Subjek / Kursus</label>
                        <input type="text" name="subject" placeholder="Contoh: Pembangunan Web" required>
                    </div>
                    <div class="form-group">
                        <label>Fail Nota (PDF, TXT, DOCX)</label>
                        <input type="file" name="noteFile" required>
                    </div>
                    <button type="submit" name="upload" style="width: 100%;">Kongsi Sekarang</button>
                </form>
            </div>

            <div class="card">
                <h3>🔍 Hab Eksplorasi Nota</h3>
                <div class="search-box">
                    <form method="POST" action="">
                        <input type="text" name="searchInp" placeholder="Tekan Enter untuk mencari tajuk atau subjek..." value="<?php echo $search; ?>">
                    </form>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Bahan & Subjek</th>
                            <th>Pemilik</th>
                            <th>Akses</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($notes_result->num_rows > 0): ?>
                            <?php while($row = $notes_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 2px;"><?php echo $row['title']; ?></div>
                                    <div style="font-size: 0.8rem; color: #64748b;">📚 <?php echo $row['subject']; ?></div>
                                </td>
                                <td><mark><?php echo $row['uploaded_by']; ?></mark></td>
                                <td>
                                    <a href="index.php?download=<?php echo $row['id']; ?>"><button class="btn-success" style="padding: 6px 12px; font-size: 0.85rem;">⬇️ Ambil Fail</button></a>
                                </td>
                                <td>
                                    <?php if ($row['uploaded_by'] == $_SESSION['studybuddy_session']): ?>
                                        <a href="index.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Hapus nota ini?')"><button class="btn-danger" style="padding: 6px 12px; font-size: 0.85rem;">Padam</button></a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:0.8rem;">🔒 Terkunci</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="no-data">Tiada rekod rujukan ditemui. Sila kongsi nota pertama anda!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>