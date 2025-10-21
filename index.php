<?php
include 'koneksi.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index - Sistem Informasi Medical Check Up Hajj</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Navigation Bar */
        nav {
            background: rgba(15, 42, 29, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.2rem 5%;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #E3EED4;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6B9071, #AEC3B0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            align-items: center;
        }

        .nav-links a {
            color: #E3EED4;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #AEC3B0;
            transition: width 0.3s ease;
        }

        .nav-links a:hover:after {
            width: 100%;
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            flex: 1;
            display: flex;
            align-items: center;
            padding: 4rem 5%;
            position: relative;
        }

        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-content {
            color: #E3EED4;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #E3EED4, #AEC3B0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #AEC3B0;
        }

        .hero-content p {
            font-size: 1.2rem;
            line-height: 1.8;
            margin-bottom: 2.5rem;
            color: #E3EED4;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
        }

        .btn {
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6B9071, #AEC3B0);
            color: #0F2A1D;
            box-shadow: 0 8px 25px rgba(107, 144, 113, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(107, 144, 113, 0.6);
        }

        .btn-secondary {
            background: transparent;
            color: #E3EED4;
            border: 2px solid #AEC3B0;
        }

        .btn-secondary:hover {
            background: rgba(174, 195, 176, 0.1);
            transform: translateY(-3px);
        }

        /* Hero Image */
        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-illustration {
            width: 100%;
            max-width: 500px;
            height: auto;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.3));
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        /* Decorative Elements */
        .decoration {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(174, 195, 176, 0.2), transparent);
            pointer-events: none;
        }

        .decoration-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            right: 10%;
        }

        .decoration-2 {
            width: 200px;
            height: 200px;
            bottom: 20%;
            left: 5%;
        }

        /* Footer */
        footer {
            background: rgba(15, 42, 29, 0.95);
            backdrop-filter: blur(10px);
            color: #E3EED4;
            text-align: center;
            padding: 2rem 5%;
            margin-top: auto;
        }

        footer p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content h2 {
                font-size: 1.5rem;
            }

            .nav-links {
                gap: 1.5rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 600px) {
            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content h2 {
                font-size: 1.2rem;
            }

            .hero-content p {
                font-size: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon">🕌</div>
                <span>MCU Haji</span>
            </div>
            <div class="nav-links">
                <a href="#home">Beranda</a>
                <a href="#tentang">Tentang</a>
                <a href="#layanan">Layanan</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="decoration decoration-1"></div>
        <div class="decoration decoration-2"></div>
        
        <div class="hero-container">
            <div class="hero-content">
                <h1>Selamat Datang</h1>
                <h2>Sistem Informasi Medical Check Up Hajj</h2>
                <p>Kesehatan Tercatat, Ibadah Terjaga. Platform digital untuk memudahkan pendaftaran dan monitoring kesehatan calon jamaah haji.</p>
                
                <div class="cta-buttons">
                    <a href="login.php" class="btn btn-primary">Masuk</a>
                    <a href="daftar.php" class="btn btn-secondary">Daftar Akun</a>
                </div>
            </div>

            <div class="hero-image">
                <svg class="hero-illustration" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
                    <!-- Medical Cross -->
                    <g transform="translate(250, 200)">
                        <rect x="-15" y="-50" width="30" height="100" fill="#AEC3B0" rx="5"/>
                        <rect x="-50" y="-15" width="100" height="30" fill="#AEC3B0" rx="5"/>
                    </g>
                    
                    <!-- Stethoscope -->
                    <path d="M 150 250 Q 150 200 200 200 L 250 200" 
                          stroke="#6B9071" stroke-width="8" fill="none" stroke-linecap="round"/>
                    <circle cx="150" cy="270" r="25" fill="#E3EED4"/>
                    <circle cx="150" cy="270" r="15" fill="#375534"/>
                    
                    <!-- Kaaba Symbol -->
                    <g transform="translate(350, 300)">
                        <rect x="-30" y="-30" width="60" height="60" fill="#0F2A1D" rx="3"/>
                        <rect x="-25" y="-15" width="50" height="5" fill="#E3EED4"/>
                    </g>
                    
                    <!-- Heartbeat Line -->
                    <path d="M 100 380 L 150 380 L 170 360 L 190 400 L 210 380 L 400 380" 
                          stroke="#AEC3B0" stroke-width="4" fill="none" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Dibuat Oleh Kelompok 4 - RMIK. Semua Hak Dilindungi.</p>
    </footer>
</body>
</html>