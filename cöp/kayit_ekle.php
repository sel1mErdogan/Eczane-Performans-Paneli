<?php
// error_reporting(E_ALL); // Geliştirme için yorum satırını kaldırın
// ini_set('display_errors', 1); // Geliştirme için yorum satırını kaldırın

include 'data2.php'; // Veritabanı bağlantı dosyanız

$success_message = '';
$error_message = '';
$form_values = $_POST; // Form değerlerini tutmak için
$errors = [];

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al ve temizle
    $pregnancies = isset($_POST['pregnancies']) ? filter_var($_POST['pregnancies'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]) : null;
    $glucose = isset($_POST['glucose']) ? filter_var($_POST['glucose'], FILTER_VALIDATE_FLOAT) : null;
    $blood_pressure = isset($_POST['blood_pressure']) ? filter_var($_POST['blood_pressure'], FILTER_VALIDATE_FLOAT) : null;
    $skin_thickness = isset($_POST['skin_thickness']) ? filter_var($_POST['skin_thickness'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0]]) : null;
    $insulin = isset($_POST['insulin']) ? filter_var($_POST['insulin'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0]]) : null;
    $bmi = isset($_POST['bmi']) ? filter_var($_POST['bmi'], FILTER_VALIDATE_FLOAT) : null;
    
    $dpf_input = isset($_POST['dpf']) ? str_replace(',', '.', $_POST['dpf']) : null; // Virgülü noktaya çevir
    $dpf = null;
    if ($dpf_input !== null && is_numeric($dpf_input)) {
        $dpf = (float)$dpf_input;
    }
    $age = isset($_POST['age']) ? filter_var($_POST['age'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) : null;

    // Temel Doğrulama
    if ($pregnancies === false || $pregnancies === null) { // filter_var int için false dönebilir
        $errors['pregnancies'] = "Gebelik sayısı geçerli bir tam sayı olmalıdır (0 veya daha fazla).";
    }
    if ($glucose === false || $glucose === null || $glucose <= 0) {
        $errors['glucose'] = "Glikoz değeri pozitif bir sayı olmalıdır.";
    }
    if ($blood_pressure === false || $blood_pressure === null || $blood_pressure <= 0) {
        $errors['blood_pressure'] = "Kan basıncı değeri pozitif bir sayı olmalıdır.";
    }
    // SkinThickness ve Insulin 0 olabilir, bu yüzden sadece null ve false kontrolü yeterli.
    if ($skin_thickness === false && $_POST['skin_thickness'] !== '' && $_POST['skin_thickness'] !== null ) { // Eğer boş değilse ve sayı değilse hata
        $errors['skin_thickness'] = "Deri kalınlığı geçerli bir sayı olmalıdır.";
    } elseif ($skin_thickness !== null && $skin_thickness < 0) {
        $errors['skin_thickness'] = "Deri kalınlığı 0 veya pozitif bir sayı olmalıdır.";
    }

    if ($insulin === false && $_POST['insulin'] !== '' && $_POST['insulin'] !== null) {
        $errors['insulin'] = "İnsülin değeri geçerli bir sayı olmalıdır.";
    } elseif ($insulin !== null && $insulin < 0) {
         $errors['insulin'] = "İnsülin değeri 0 veya pozitif bir sayı olmalıdır.";
    }

    if ($bmi === false || $bmi === null || $bmi <= 0) {
        $errors['bmi'] = "BMI değeri pozitif bir sayı olmalıdır.";
    }
    if ($dpf === null || $dpf <= 0 || $dpf > 3.0) { // Üst sınırı biraz genişlettim, veri setinize göre ayarlayın.
        $errors['dpf'] = "Diyabet Soyağacı Fonksiyonu 0 ile 3.0 arasında ondalıklı bir sayı olmalıdır (örn: 0.627).";
    }
    if ($age === false || $age === null || $age <= 0) {
        $errors['age'] = "Yaş pozitif bir tam sayı olmalıdır.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO diabetes_new (Pregnancies, Glucose, BloodPressure, SkinThickness, Insulin, BMI, DiabetesPedigreeFunction, Age) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // SkinThickness ve Insulin null olabilir, bu yüzden bind_param'da tipleri ona göre ayarlamalıyız.
            // Eğer bu alanlar her zaman bir değer alacaksa (örn: 0), o zaman 'd' kalabilir.
            // Ancak boş bırakılabiliyorsa ve DB'de NULL kabul ediyorsa, o zaman dinamik tip veya ayrı logic gerekebilir.
            // Şimdilik 0 olarak gönderileceğini varsayarak 'd' kullanıyorum. Eğer NULL olacaksa, bu kısım düzenlenmeli.
            $st_val = $skin_thickness === null ? 0.0 : $skin_thickness; // Boşsa 0 ata
            $in_val = $insulin === null ? 0.0 : $insulin; // Boşsa 0 ata

            $stmt->bind_param("iddddddi", 
                $pregnancies, 
                $glucose, 
                $blood_pressure, 
                $st_val, 
                $in_val, 
                $bmi, 
                $dpf, 
                $age
            );

            if ($stmt->execute()) {
                $success_message = "Yeni hasta kaydı başarıyla eklendi!";
                $form_values = []; // Formu temizle
            } else {
                $error_message = "Kayıt eklenirken bir hata oluştu: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "SQL sorgusu hazırlanırken bir hata oluştu: " . $conn->error;
        }
    } else {
        $error_message = "Lütfen formdaki işaretli alanları düzeltin.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Hasta Kaydı Ekle - Diyabet Paneli</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css"> {/* AdminLTE base for sidebar/navbar widgets */}

    <style>
        :root {
            --bg-main: #f4f7f9;
            --bg-card: #ffffff;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --border-color: #e0e6ed;
            --border-focus-color: #3498db; /* Form focus için ana renk */
            --shadow-color: rgba(44, 62, 80, 0.08);
            --accent-color: #3498db; /* Kayıt formu için mavi tema */
            --accent-color-darker: #2980b9;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --font-family-sans-serif: 'Inter', sans-serif;
            --accent-color-rgb: 52, 152, 219; /* Mavi için RGB */
        }

        body {
            font-family: var(--font-family-sans-serif);
            background-color: var(--bg-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .wrapper { background-color: var(--bg-main); }

        .main-header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); box-shadow: 0 1px 3px var(--shadow-color); }
        .main-header .navbar-nav .nav-link { color: var(--text-secondary); }
        .main-header .navbar-nav .nav-link:hover { color: var(--accent-color); }
        .navbar-brand { color: var(--text-primary) !important; font-weight: 600; }
        .navbar-brand .fa-notes-medical { color: var(--accent-color); } /* İkon rengi güncellendi */

        .main-sidebar { background-color: #263238; box-shadow: 2px 0 5px var(--shadow-color); }
        .main-sidebar .brand-link { border-bottom: 1px solid rgba(255,255,255,0.05);text-decoration: none; }
        .main-sidebar .brand-text { color: #eceff1; }
        .main-sidebar .user-panel .info a { color: #cfd8dc; text-decoration: none;}
        .nav-sidebar .nav-item > .nav-link { color: #b0bec5; padding: .7rem 1rem; transition: background-color 0.2s ease, color 0.2s ease; }
        .nav-sidebar .nav-item > .nav-link.active, .nav-sidebar .nav-item > .nav-link:hover { background-color: rgba(255,255,255,0.05); color: #ffffff; }
        .nav-sidebar .nav-item > .nav-link.active { background-color: var(--accent-color); color: #fff; } /* Sidebar active link rengi */
        .nav-sidebar .nav-item > .nav-link.active .nav-icon { color: #fff !important; }
        .nav-sidebar .nav-icon { color: #78909c; width: 1.6rem; margin-right: .5rem; transition: color 0.2s ease;}
        .brand-link .brand-image { float: none; line-height: .8; margin-left: .8rem; margin-right: .5rem; margin-top: -3px; max-height: 33px; width: auto; }
        .nav-sidebar .nav-link p { white-space: normal; }


        .content-wrapper { background-color: var(--bg-main); padding: 25px; }
        .content-header { padding: 15px 0px; margin-bottom: 20px; }
        .content-header h1 { font-size: 1.85rem; font-weight: 600; color: var(--text-primary); }
        .breadcrumb-item a { color: var(--accent-color); text-decoration: none; }
        .breadcrumb-item a:hover { text-decoration: underline; }
        .breadcrumb-item.active { color: var(--text-secondary); }

        .custom-form-card {
            background-color: var(--bg-card);
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.1);
            margin-top: 20px;
        }
        .custom-form-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 22px 30px; /* Biraz daha fazla padding */
        }
        .custom-form-card .card-title {
            font-size: 1.2rem; /* Başlık boyutu */
            font-weight: 600;
            color: var(--text-primary);
        }
        .custom-form-card .card-body {
            padding: 25px 30px; /* Body padding */
        }
        .custom-form-card .card-footer {
            background-color: #f9fafb; /* Hafif farklı bir footer rengi */
            border-top: 1px solid var(--border-color);
            padding: 20px 30px;
            text-align: right; /* Butonları sağa yasla */
        }
        .custom-form-card .card-footer .btn + .btn {
            margin-left: .75rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: .5rem;
            font-size: 0.9rem;
        }
        .form-control {
            border-radius: 6px;
            border: 1px solid var(--border-color);
            padding: .7rem 1rem; /* Input padding */
            font-size: 0.95rem;
            color: var(--text-primary);
            background-color: #fff; /* Input arka planı */
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }
        .form-control:focus {
            border-color: var(--border-focus-color);
            box-shadow: 0 0 0 .2rem rgba(var(--accent-color-rgb), 0.25);
            background-color: #fff; /* Focus'ta da arka plan beyaz kalsın */
        }
        .form-control::placeholder { /* Placeholder stili */
            color: #aab8c2;
            opacity: 1;
        }

        .form-group.has-error .form-control {
            border-color: var(--error-color) !important; /* Hata durumunda border rengi */
        }
        .form-group.has-error .form-control:focus {
            box-shadow: 0 0 0 .2rem rgba(231, 76, 60, 0.25) !important; /* Hata durumunda focus shadow */
        }
        .invalid-feedback { /* Hata mesajı için .invalid-feedback Bootstrap sınıfını kullanıyoruz */
            display: none; /* Varsayılan olarak gizli, PHP ile .has-error eklenince gösterilecek */
            width: 100%;
            margin-top: .25rem;
            font-size: .82rem; /* Hata mesajı boyutu */
            color: var(--error-color);
        }
        .form-group.has-error .invalid-feedback {
            display: block;
        }
        .form-text.text-muted {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .btn {
            border-radius: 6px;
            padding: .65rem 1.3rem; /* Buton padding */
            font-weight: 500;
            font-size: 0.95rem;
            transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            line-height: 1.5; /* Dikey hizalama için */
        }
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: #fff;
        }
        .btn-primary:hover {
            background-color: var(--accent-color-darker);
            border-color: var(--accent-color-darker);
            color: #fff;
        }
        .btn-primary:focus {
            box-shadow: 0 0 0 .2rem rgba(var(--accent-color-rgb), 0.5);
            background-color: var(--accent-color-darker); /* Focus'ta da hover rengi */
            border-color: var(--accent-color-darker);
        }

        .btn-secondary {
            background-color: #e9ecef; /* Daha açık ve modern bir gri */
            border-color: #ced4da;
            color: var(--text-primary);
        }
        .btn-secondary:hover {
            background-color: #d3d9df;
            border-color: #b9c2cb;
            color: var(--text-primary);
        }
         .btn-secondary:focus {
            box-shadow: 0 0 0 .2rem rgba(108, 117, 125, 0.5);
        }
        .btn .fas {
            margin-right: .5rem;
        }

        .alert {
            border-radius: 8px;
            padding: 1rem 1.25rem;
            border-width: 1px;
            border-style: solid;
            margin-bottom: 1.5rem; /* Alertler arası boşluk */
        }
        .alert strong { font-weight: 600; }
        .alert .close { /* Bootstrap 4 close button stili */
            padding: 1rem 1.25rem;
            color: inherit;
            background-color: transparent;
            border: 0;
            float: right;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            text-shadow: 0 1px 0 #fff;
            opacity: .5;
        }
        .alert .close:hover { opacity: .75; }

        .alert-success {
            background-color: #e6ffed; /* Daha yumuşak yeşil */
            border-color: #b3ffc6;
            color: #0f5132;
        }
        .alert-success .alert-link { color: #0a3622; }
        .alert-success .close { color: #0f5132; }

        .alert-danger {
            background-color: #ffebee; /* Daha yumuşak kırmızı */
            border-color: #ffcdd2;
            color: #b71c1c;
        }
        .alert-danger .alert-link { color: #7f0000; }
        .alert-danger .close { color: #b71c1c; }


        .main-footer { background-color: var(--bg-card); border-top: 1px solid var(--border-color); color: var(--text-secondary); padding: 1.3rem; font-size: 0.88rem; margin-top: 25px; text-align: center; }
        .main-footer a { color: var(--accent-color); font-weight: 500; text-decoration: none; }
        .main-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">

        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <!-- Orta kısım ortalanmış şekilde düzeltildi -->
    <div class="d-flex justify-content-center align-items-center position-absolute w-100" style="pointer-events: none; left: 0; top: 0; height: 100%;">
        <span class="navbar-brand mb-0 h5 text-center" style="pointer-events: auto;">
            <i class="fas fa-user-plus mr-2"></i> Yeni Hasta Kaydı
        </span>
    </div>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
    </ul>
</nav>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
             <a href="diabets.php" class="brand-link">
                <img src="dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Diyabet Paneli V2</span>
            </a>
            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image"><img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image"></div>
                    <div class="info"><a href="#" class="d-block">Dr. Can Yılmaz</a></div>
                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-chart-pie"></i><p>Genel İstatistikler</p></a></li>
                        <li class="nav-item"><a href="hasta_listesi.php" class="nav-link"><i class="nav-icon fas fa-notes-medical"></i><p>Hasta Kayıtları </p></a></li>
                        <li class="nav-item"><a href="kayit_ekle.php" class="nav-link active "><i class="nav-icon fas fa-user-plus "></i><p>Yeni Kayıt Ekle</p></a></li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6"><h1 class="m-0">Hasta Kayıtları Yönetimi</h1></div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="diabets.php">Ana Panel</a></li>
                                <li class="breadcrumb-item active">Hasta Kayıtları</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-10 offset-lg-1 col-md-12"> 
                            <div class="custom-form-card">
                                <div class="card-header">
                                    <h3 class="card-title">Hasta Bilgilerini Giriniz</h3>
                                </div>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                                    <div class="card-body">
                                        <?php if (!empty($success_message)): ?>
                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                <strong><i class="icon fas fa-check-circle mr-2"></i>Başarılı!</strong> <?php echo htmlspecialchars($success_message); ?>
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($error_message) && empty($errors)): // Sadece genel veritabanı hatası varsa ?>
                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                 <strong><i class="icon fas fa-times-circle mr-2"></i>Hata!</strong> <?php echo htmlspecialchars($error_message); ?>
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($errors)): // Form doğrulama hataları varsa ?>
                                             <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                <strong><i class="icon fas fa-exclamation-triangle mr-2"></i>Lütfen Hataları Düzeltin:</strong>
                                                <ul>
                                                    <?php foreach($errors as $error_field): ?>
                                                        <li><?php echo htmlspecialchars($error_field); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>


                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group <?php echo !empty($errors['pregnancies']) ? 'has-error' : ''; ?>">
                                                    <label for="pregnancies">Gebelik Sayısı <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="pregnancies" name="pregnancies" placeholder="Örn: 2" value="<?php echo htmlspecialchars($form_values['pregnancies'] ?? ''); ?>" min="0" required>
                                                    <div class="invalid-feedback"><?php echo $errors['pregnancies'] ?? ''; ?></div>
                                                </div>
                                                <div class="form-group <?php echo !empty($errors['glucose']) ? 'has-error' : ''; ?>">
                                                    <label for="glucose">Glikoz (mg/dL) <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.1" class="form-control" id="glucose" name="glucose" placeholder="Örn: 120.5" value="<?php echo htmlspecialchars($form_values['glucose'] ?? ''); ?>" min="0.1" required>
                                                    <div class="invalid-feedback"><?php echo $errors['glucose'] ?? ''; ?></div>
                                                </div>
                                                <div class="form-group <?php echo !empty($errors['blood_pressure']) ? 'has-error' : ''; ?>">
                                                    <label for="blood_pressure">Kan Basıncı (mmHg) <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.1" class="form-control" id="blood_pressure" name="blood_pressure" placeholder="Örn: 70.0" value="<?php echo htmlspecialchars($form_values['blood_pressure'] ?? ''); ?>" min="0.1" required>
                                                     <div class="invalid-feedback"><?php echo $errors['blood_pressure'] ?? ''; ?></div>
                                                </div>
                                                <div class="form-group <?php echo !empty($errors['skin_thickness']) ? 'has-error' : ''; ?>">
                                                    <label for="skin_thickness">Deri Kalınlığı (mm)</label>
                                                    <input type="number" step="0.1" class="form-control" id="skin_thickness" name="skin_thickness" placeholder="Örn: 20.0 (0 olabilir)" value="<?php echo htmlspecialchars($form_values['skin_thickness'] ?? ''); ?>" min="0">
                                                    <div class="invalid-feedback"><?php echo $errors['skin_thickness'] ?? ''; ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group <?php echo !empty($errors['insulin']) ? 'has-error' : ''; ?>">
                                                    <label for="insulin">İnsülin (mu U/ml)</label>
                                                    <input type="number" step="0.1" class="form-control" id="insulin" name="insulin" placeholder="Örn: 80.0 (0 olabilir)" value="<?php echo htmlspecialchars($form_values['insulin'] ?? ''); ?>" min="0">
                                                    <div class="invalid-feedback"><?php echo $errors['insulin'] ?? ''; ?></div>
                                                </div>
                                                <div class="form-group <?php echo !empty($errors['bmi']) ? 'has-error' : ''; ?>">
                                                    <label for="bmi">BMI (Vücut Kitle İndeksi) <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" class="form-control" id="bmi" name="bmi" placeholder="Örn: 25.55" value="<?php echo htmlspecialchars($form_values['bmi'] ?? ''); ?>" min="0.1" required>
                                                     <div class="invalid-feedback"><?php echo $errors['bmi'] ?? ''; ?></div>
                                                </div>
                                                <div class="form-group <?php echo !empty($errors['dpf']) ? 'has-error' : ''; ?>">
                                                    <label for="dpf">Diyabet Soyağacı Fonksiyonu <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="dpf" name="dpf" placeholder="Örn: 0.627 (Nokta kullanın)" value="<?php echo htmlspecialchars($form_values['dpf'] ?? ''); ?>" required pattern="^[0-2](\.\d{1,3})?$|^3(\.0{1,3})?$">
                                                    <div class="invalid-feedback"><?php echo $errors['dpf'] ?? 'Lütfen 0.001 ile 3.000 arasında bir değer girin (örn: 0.123).'; ?></div>
                                                    
                                                </div>
                                                <div class="form-group <?php echo !empty($errors['age']) ? 'has-error' : ''; ?>">
                                                    <label for="age">Yaş <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="age" name="age" placeholder="Örn: 30" value="<?php echo htmlspecialchars($form_values['age'] ?? ''); ?>" min="1" required>
                                                    <div class="invalid-feedback"><?php echo $errors['age'] ?? ''; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <a href="diabets.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panele Dön</a>
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Kaydı Ekle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <strong>Telif Hakkı © <?php echo date("Y"); ?> <a href="#">Sağlık Veri Analitiği A.Ş.</a></strong> Tüm hakları saklıdır.
             <br> Fuay Hastanesi Katkılarıyla
        </footer>
    </div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
    // Hata varsa ilk hatalı input'a odaklan
    if ($('.form-group.has-error').length > 0) {
        $('.form-group.has-error .form-control').first().focus();
    }

    // Başarı mesajını 5 saniye sonra otomatik gizle
    window.setTimeout(function() {
        $(".alert-success").fadeTo(500, 0).slideUp(500, function(){
            $(this).remove();
        });
    }, 5000);

    // DPF inputunda sadece sayı ve nokta (.) kabul etme (isteğe bağlı, tarayıcı `type=number` zaten yapar ama bu ek kontrol)
    $('#dpf').on('input', function (event) {
        this.value = this.value.replace(/[^0-9.]/g, ''); // Sadece sayı ve nokta
        this.value = this.value.replace(/(\..*)\./g, '$1'); // Birden fazla noktayı engelle
    });
});
</script>
</body>
</html>