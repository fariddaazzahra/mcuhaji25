<?php
include 'koneksi.php';
session_start();

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = trim($_POST['password']); // tidak perlu escape password mentah
    $no_hp = trim(mysqli_real_escape_string($conn, $_POST['no_hp']));
    $nik = trim(mysqli_real_escape_string($conn, $_POST['nik']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Validasi panjang dan format NIK (harus 16 digit angka)
    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $error = "NIK harus terdiri dari tepat 16 digit angka!";
    } else {
        // Cek apakah username sudah terdaftar
        $check_sql = "SELECT user_id FROM user WHERE username='$username'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $error = "Username sudah terdaftar! Silakan gunakan username lain.";
        } else {
            // 🔒 Enkripsi password sebelum disimpan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Simpan user baru ke database
            $sql = "INSERT INTO user (username, password, no_hp, nik, role) 
                    VALUES ('$username', '$hashed_password', '$no_hp', '$nik', '$role')";

            if ($conn->query($sql) === TRUE) {
                $success = "Registrasi berhasil! Silakan login dengan akun baru.";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - MCU Haji</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0F2A1D 0%, #375534 50%, #6B9071 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Decorative Background Elements */
        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(174, 195, 176, 0.15), transparent);
            pointer-events: none;
        }

        .bg-decoration-1 {
            width: 500px;
            height: 500px;
            top: -150px;
            right: -150px;
        }

        .bg-decoration-2 {
            width: 350px;
            height: 350px;
            bottom: -100px;
            left: -100px;
        }

        .bg-decoration-3 {
            width: 250px;
            height: 250px;
            top: 40%;
            right: 15%;
            animation: float 5s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-25px) rotate(5deg); }
        }

        /* Register Container */
        .register-container {
            background: rgba(227, 238, 212, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            max-width: 500px;
            width: 100%;
            padding: 3rem;
            position: relative;
            z-index: 10;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0F2A1D, #375534);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 20px rgba(15, 42, 29, 0.3);
        }

        .register-container h1 {
            color: #0F2A1D;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .register-container .subtitle {
            color: #375534;
            font-size: 0.95rem;
            font-weight: 400;
            opacity: 0.8;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .success-message {
            background: linear-gradient(135deg, #51cf66, #37b24d);
            color: white;
            box-shadow: 0 4px 15px rgba(81, 207, 102, 0.3);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            color: #0F2A1D;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6B9071;
            font-size: 1.1rem;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group select {
            width: 100%;
            padding: 0.9rem 0.9rem 0.9rem 2.8rem;
            border: 2px solid #AEC3B0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            background: white;
            color: #0F2A1D;
            transition: all 0.3s ease;
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B9071' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6B9071;
            box-shadow: 0 0 0 4px rgba(107, 144, 113, 0.1);
        }

        .form-group input::placeholder {
            color: #AEC3B0;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #375534, #6B9071);
            color: #E3EED4;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(55, 85, 52, 0.3);
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(55, 85, 52, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Success State */
        .success-state {
            text-align: center;
            padding: 2rem 1rem;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #51cf66, #37b24d);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 1.5rem;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-state h2 {
            color: #0F2A1D;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .success-state p {
            color: #375534;
            margin-bottom: 1.5rem;
        }

        .btn-login {
            display: inline-block;
            padding: 0.9rem 2rem;
            background: linear-gradient(135deg, #375534, #6B9071);
            color: #E3EED4;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(55, 85, 52, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(55, 85, 52, 0.4);
        }

        /* Links Section */
        .links-section {
            margin-top: 1.5rem;
            text-align: center;
        }

        .links-section a {
            color: #375534;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 0.4rem 0;
        }

        .links-section a:hover {
            color: #0F2A1D;
            transform: translateX(3px);
        }

        .divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent, #AEC3B0, transparent);
            margin: 1.2rem 0;
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #6B9071;
            font-size: 0.85rem;
        }

        /* Helper Text */
        .helper-text {
            font-size: 0.75rem;
            color: #6B9071;
            margin-top: 0.3rem;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 500px) {
            .register-container {
                padding: 2rem 1.5rem;
                border-radius: 25px;
            }

            .register-container h1 {
                font-size: 1.75rem;
            }

            .logo-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }
        }

        /* Icons */
        .icon-user::before { content: '👤'; }
        .icon-lock::before { content: '🔒'; }
        .icon-phone::before { content: '📱'; }
        .icon-id::before { content: '🪪'; }
        .icon-role::before { content: '👔'; }
        .icon-arrow::before { content: '←'; }
        .icon-check::before { content: '✓'; }
    </style>
</head>
<body>
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>

    <div class="register-container">
        <?php if (!empty($success)): ?>
            <!-- Success State -->
            <div class="success-state">
                <div class="success-icon">✓</div>
                <h2>Registrasi Berhasil!</h2>
                <p>Akun Anda telah dibuat. Silakan login dengan kredensial baru Anda.</p>
                <a href="login.php" class="btn-login">Login Sekarang</a>
                <div class="divider"></div>
                <a href="index.php" class="back-link">
                    <span class="icon-arrow"></span>
                    Kembali ke Halaman Utama
                </a>
            </div>
        <?php else: ?>
            <!-- Registration Form -->
            <div class="logo-section">
                <div class="logo-icon">🕌</div>
                <h1>Daftar Akun</h1>
                <p class="subtitle">Buat akun baru untuk mengakses sistem</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="message error-message">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon icon-user"></span>
                        <input type="text" id="username" name="username" placeholder="Masukan username unik" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon icon-lock"></span>
                        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="no_hp">No. HP</label>
                    <div class="input-wrapper">
                        <span class="input-icon icon-phone"></span>
                        <input type="text" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="nik">NIK</label>
                    <div class="input-wrapper">
                        <span class="input-icon icon-id"></span>
                        <input type="text" id="nik" name="nik" 
                               pattern="\d{16}" 
                               minlength="16" maxlength="16" 
                               placeholder="16 digit angka"
                               title="NIK harus terdiri dari 16 digit angka"
                               required>
                    </div>
                    <small class="helper-text">* Masukkan 16 digit NIK sesuai KTP</small>
                </div>

                <div class="form-group">
                    <label for="role">Peran</label>
                    <div class="input-wrapper">
                        <span class="input-icon icon-role"></span>
                        <select id="role" name="role" required>
                            <option value="">-- Pilih Peran --</option>
                            <option value="admin">Admin</option>
                            <option value="dokter">Dokter</option>
                            <option value="nakes">Nakes</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Daftar Sekarang</button>
            </form>

            <div class="links-section">
                <div>
                    <a href="login.php">Sudah punya akun? Login disini →</a>
                </div>
                
                <div class="divider"></div>
                
                <a href="index.php" class="back-link">
                    <span class="icon-arrow"></span>
                    Kembali ke Halaman Utama
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>