<?php
include 'koneksi.php';
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = trim($_POST['password']); // tidak perlu di-escape

    $sql = "SELECT user_id, password, role FROM user WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // 🔒 Cek password dengan password_verify()
        if (password_verify($password, $row['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];

            // 🔀 Redirect berdasarkan role
            switch ($row['role']) {
                case 'admin':
                    header("Location: admin/dashboardadmin.php");
                    break;
                case 'nakes':
                    header("Location: nakes/dashboardnakes.php");
                    break;
                case 'dokter':
                    header("Location: dokter/dashboarddokter.php");
                    break;
                default:
                    header("Location: index.php");
                    break;
            }
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MCU Haji</title>
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
            overflow: hidden;
        }

        /* Decorative Background Elements */
        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(174, 195, 176, 0.15), transparent);
            pointer-events: none;
        }

        .bg-decoration-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
        }

        .bg-decoration-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
        }

        .bg-decoration-3 {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 10%;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Login Container */
        .login-container {
            background: rgba(227, 238, 212, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            max-width: 450px;
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

        .login-container h1 {
            color: #0F2A1D;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-container .subtitle {
            color: #375534;
            font-size: 0.95rem;
            font-weight: 400;
            opacity: 0.8;
        }

        /* Error Message */
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: #0F2A1D;
            font-weight: 600;
            font-size: 0.95rem;
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
            font-size: 1.2rem;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #AEC3B0;
            border-radius: 15px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            background: white;
            color: #0F2A1D;
            transition: all 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #6B9071;
            box-shadow: 0 0 0 4px rgba(107, 144, 113, 0.1);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #375534, #6B9071);
            color: #E3EED4;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
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

        /* Links Section */
        .links-section {
            margin-top: 2rem;
            text-align: center;
        }

        .links-section a {
            color: #375534;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 0.5rem 0;
        }

        .links-section a:hover {
            color: #0F2A1D;
            transform: translateX(5px);
        }

        .divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, transparent, #AEC3B0, transparent);
            margin: 1.5rem 0;
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #6B9071;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 500px) {
            .login-container {
                padding: 2rem 1.5rem;
                border-radius: 25px;
            }

            .login-container h1 {
                font-size: 1.75rem;
            }

            .logo-icon {
                width: 70px;
                height: 70px;
                font-size: 2rem;
            }
        }

        /* Icons using CSS */
        .icon-user::before {
            content: '👤';
        }

        .icon-lock::before {
            content: '🔒';
        }

        .icon-arrow::before {
            content: '←';
        }
    </style>
</head>
<body>
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>

    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">🕌</div>
            <h1>Masuk MCU Haji</h1>
            <p class="subtitle">Silakan masuk untuk melanjutkan</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                ⚠️ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon icon-user"></span>
                    <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon icon-lock"></span>
                    <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Masuk</button>
        </form>

        <div class="links-section">
            <div>
                <a href="daftar.php">Belum punya akun? Daftar disini →</a>
            </div>
            
            <div class="divider"></div>
            
            <a href="index.php" class="back-link">
                <span class="icon-arrow"></span>
                Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>