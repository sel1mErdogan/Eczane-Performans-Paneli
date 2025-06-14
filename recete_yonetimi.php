<?php
// Geliştirme için hata raporlamayı açabilirsiniz
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'data3.php'; 

if (!$conn || $conn->connect_error) {
    die("Kritik bir sistem hatası oluştu: Veritabanı bağlantısı kurulamadı.");
}
$conn->set_charset("utf8mb4");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- YARDIMCI FONKSİYONLAR ---
function set_form_message($message, $type = 'danger') {
    $_SESSION['form_message'] = $message;
    $_SESSION['form_message_type'] = $type;
}

function display_form_message() {
    if (isset($_SESSION['form_message']) && !empty($_SESSION['form_message'])) {
        echo '<div class="alert alert-' . htmlspecialchars($_SESSION['form_message_type'] ?? 'danger') . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['form_message'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>';
        echo '</div>';
        unset($_SESSION['form_message']);
        unset($_SESSION['form_message_type']);
    }
}

$form_data_add = ['recete_tarih' => date('Y-m-d'), 'hasta_tckn' => '', 'ilac_id' => '', 'doktor_id' => '', 'ilac_adet' => '', 'kullanim_doz' => ''];

// --- İŞLEM YÖNETİMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_recete') {
        $form_data_add['recete_tarih'] = trim($_POST['recete_tarih'] ?? date('Y-m-d'));
        $form_data_add['hasta_tckn'] = trim($_POST['hasta_tckn'] ?? '');
        $form_data_add['ilac_id'] = !empty($_POST['ilac_id']) ? (int)$_POST['ilac_id'] : null;
        $form_data_add['doktor_id'] = !empty($_POST['doktor_id']) ? (int)$_POST['doktor_id'] : null;
        $form_data_add['ilac_adet'] = !empty($_POST['ilac_adet']) ? (int)$_POST['ilac_adet'] : null;
        $form_data_add['kullanim_doz'] = trim($_POST['kullanim_doz'] ?? '');

        if (empty($form_data_add['recete_tarih']) || empty($form_data_add['hasta_tckn']) || $form_data_add['ilac_id'] === null || $form_data_add['doktor_id'] === null || $form_data_add['ilac_adet'] === null || empty($form_data_add['kullanim_doz'])) {
            set_form_message("Lütfen tüm zorunlu alanları doldurun.", 'warning');
        } elseif ($form_data_add['ilac_adet'] <= 0) {
            set_form_message("İlaç adeti 0'dan büyük olmalıdır.", 'warning');
        } else {
            try {
                // YENİ: Saklı yordam çağrısı
                $stmt = $conn->prepare("CALL sp_ReceteEkle(?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiiis", $form_data_add['recete_tarih'], $form_data_add['hasta_tckn'], $form_data_add['ilac_id'], $form_data_add['doktor_id'], $form_data_add['ilac_adet'], $form_data_add['kullanim_doz']);
                if ($stmt->execute()) {
                    set_form_message("Reçete başarıyla eklendi.", 'success');
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                 if ($e->getCode() == 1452) {
                    set_form_message("Geçersiz hasta, ilaç veya doktor seçimi yaptınız. Reçete eklenemedi.", 'danger');
                } else {
                    set_form_message("Reçete eklenirken bir hata oluştu.<hr>Teknik Detay: " . htmlspecialchars($e->getMessage()), 'danger');
                }
            }
        }
        header("Location: recete_yonetimi.php");
        exit;
    }

    if ($_POST['action'] == 'edit_recete') {
        $recete_id = (int)($_POST['recete_id'] ?? 0);
        // Diğer POST verilerini al...
        if ($recete_id <= 0 /* ... diğer kontroller ... */) {
            set_form_message("Lütfen tüm zorunlu alanları doldurun.", 'warning');
            header("Location: recete_yonetimi.php?action=edit_recete_form&id=" . $recete_id);
            exit;
        } else {
            try {
                // YENİ: Saklı yordam çağrısı
                $stmt = $conn->prepare("CALL sp_ReceteGuncelle(?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiiisi", $_POST['recete_tarih'], $_POST['hasta_tckn'], $_POST['ilac_id'], $_POST['doktor_id'], $_POST['ilac_adet'], $_POST['kullanim_doz'], $recete_id);
                if ($stmt->execute()) {
                    set_form_message("Reçete başarıyla güncellendi.", 'success');
                    header("Location: recete_yonetimi.php");
                    exit;
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                 if ($e->getCode() == 1452) {
                    set_form_message("Geçersiz hasta, ilaç veya doktor seçimi yaptınız. Reçete güncellenemedi.", 'danger');
                } else {
                    set_form_message("Reçete güncellenirken bir hata oluştu.<hr>Teknik Detay: " . htmlspecialchars($e->getMessage()), 'danger');
                }
                header("Location: recete_yonetimi.php?action=edit_recete_form&id=" . $recete_id);
                exit;
            }
        }
    }
}

// --- GET İSTEKLERİ ---
$receteToEdit = null;
$show_edit_form = false;
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $recete_id_get = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action == 'delete_recete' && $recete_id_get > 0) {
        try {
            // YENİ: Saklı yordam çağrısı
            $stmt = $conn->prepare("CALL sp_ReceteSil(?)");
            $stmt->bind_param("i", $recete_id_get);
            if ($stmt->execute()) {
                set_form_message("Reçete başarıyla silindi.", 'info');
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            set_form_message("Reçete silinemedi: " . htmlspecialchars($e->getMessage()), 'danger');
        }
        header("Location: recete_yonetimi.php");
        exit;
    } elseif ($action == 'edit_recete_form' && $recete_id_get > 0) {
        try {
            // YENİ: Saklı yordam çağrısı
            $stmt = $conn->prepare("CALL sp_GetReceteById(?)");
            $stmt->bind_param("i", $recete_id_get);
            $stmt->execute();
            $result = $stmt->get_result();
            $receteToEdit = $result->fetch_assoc();
            $show_edit_form = $receteToEdit ? true : false;
            if (!$show_edit_form) set_form_message("Düzenlenecek reçete bulunamadı.", 'warning');
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            set_form_message("Reçete verileri çekilirken hata oluştu: " . htmlspecialchars($e->getMessage()), 'danger');
        }
    }
}

// --- GEREKLİ DİĞER VERİLERİ ÇEKME ---
$hastalar = []; $ilaclar = []; $doktorlar = [];
try {
    $result_hastalar = $conn->query("CALL sp_GetHastalarListesi()");
    if ($result_hastalar) { $hastalar = $result_hastalar->fetch_all(MYSQLI_ASSOC); $result_hastalar->close(); $conn->next_result(); }

    $result_ilaclar = $conn->query("CALL sp_GetIlaclarDropdownListesi()");
    if ($result_ilaclar) { $ilaclar = $result_ilaclar->fetch_all(MYSQLI_ASSOC); $result_ilaclar->close(); $conn->next_result(); }

    $result_doktorlar = $conn->query("CALL sp_GetDoktorlarListesi()");
    if ($result_doktorlar) { $doktorlar = $result_doktorlar->fetch_all(MYSQLI_ASSOC); $result_doktorlar->close(); $conn->next_result(); }
} catch (mysqli_sql_exception $e) {
    set_form_message("Form listeleri yüklenirken hata oluştu: " . htmlspecialchars($e->getMessage()), "danger");
}

// --- MEVCUT REÇETELERİ LİSTELEME ---
$sortable_columns = ['id', 'tarih', 'hasta', 'ilac', 'doktor', 'adet'];
$sort_by = in_array($_GET['sort'] ?? '', $sortable_columns) ? $_GET['sort'] : 'tarih';
$order = (strtolower($_GET['order'] ?? '') === 'asc') ? 'asc' : 'desc';

$receteler = [];
try {
    // YENİ: Sıralama parametrelerini saklı yordama gönder
    $stmt = $conn->prepare("CALL sp_ListReceteler(?, ?)");
    $stmt->bind_param("ss", $sort_by, $order);
    $stmt->execute();
    $result_receteler = $stmt->get_result();
    if ($result_receteler) $receteler = $result_receteler->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    set_form_message("Reçeteler yüklenirken hata oluştu: " . htmlspecialchars($e->getMessage()), "danger");
}

function get_sort_link($column_name, $display_text, $current_sort, $current_order) {
    $next_order = ($current_sort == $column_name && $current_order == 'asc') ? 'desc' : 'asc';
    $icon = ($current_sort == $column_name) ? ($current_order == 'asc' ? 'up' : 'down') : 'sort';
    return '<a href="?sort=' . $column_name . '&order=' . $next_order . '">' . htmlspecialchars($display_text) . '<i class="fas fa-sort-' . $icon . ' ml-1"></i></a>';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçete Yönetimi - PharmAnalytics Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        :root { --accent-color: #c82333; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; color: #2c3e50; }
        .main-header { background-color: #ffffff; }
        .main-sidebar { background-color: var(--accent-color); }
        .content-wrapper { padding: 25px; }
        .card-outline-tabs > .card-header a.active { border-top: 3px solid var(--accent-color); }
        .form-control-sm { height: calc(1.8125rem + 2px); }
        .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .15rem;}

        /* ** 3. ADIM: GÖRSEL İYİLEŞTİRME (CSS) ** */
        .table thead th a { color: inherit; text-decoration: none; }
        .table thead th a:hover { color: #000; }
        .table thead .fa-sort, .table thead .fa-sort-up, .table thead .fa-sort-down {
            transition: color 0.2s;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li></ul>
        <span class="navbar-brand-custom mx-auto d-block text-center h5 mb-0">Reçete Yönetimi</span>
    </nav>
     <!-- Sol Kenar Çubuğu (Sidebar) -->
        <aside class="main-sidebar sidebar-dark-danger elevation-4">
    <a href="eczane.php" class="brand-link"> <!-- Ana dashboard linki eczane.php olarak varsayıyorum -->
        <img src="dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">PharmAnalytics</span>
    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image"><img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image"></div>
            <div class="info"><a href="#" class="d-block">Yönetici Panel</a></div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="eczane.php" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i> <!-- Genel bakış için daha uygun bir ikon -->
                        <p>Genel Bakış</p>
                    </a>
                </li>

                <li class="nav-header">TEMEL OPERASYONLAR</li>

                <li class="nav-item">
                    <a href="ilac_yonetimi.php" class="nav-link">
                        <i class="nav-icon fas fa-pills"></i> <!-- İlaçları temsil eder -->
                        <p>İlaç Yönetimi</p> <!-- Linke uygun isim -->
                    </a>
                </li>
                <li class="nav-item">
                    <a href="recete_yonetimi.php" class="nav-link">
                        <i class="nav-icon fas fa-file-medical-alt"></i> <!-- Reçeteyi temsil eder -->
                        <p>Reçete Yönetimi</p> <!-- Linke uygun isim -->
                    </a>
                </li>
                <!-- Eğer Hasta Yönetimi için ayrı bir sayfanız varsa (örneğin hasta_yonetimi.php):
                <li class="nav-item">
                    <a href="hasta_yonetimi.php" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Hasta Yönetimi</p>
                    </a>
                </li>
                -->
                <li class="nav-item">
                    <a href="doktor_yonetimi.php" class="nav-link"> <!-- DOKTOR yönetimi linki tedarikci_yonetimi.php değil, doktor_yonetimi.php olmalı -->
                        <i class="nav-icon fas fa-user-md"></i> <!-- Doktoru temsil eder -->
                        <p>Doktor Yönetimi</p> <!-- Linke uygun isim -->
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tedarikci_yonetimi.php" class="nav-link"> <!-- TEDARİKÇİ yönetimi linki doktor_yonetimi.php değil, tedarikci_yonetimi.php olmalı -->
                        <i class="nav-icon fas fa-truck"></i> <!-- Tedarikçiyi/taşımacılığı temsil eder -->
                        <p>Tedarikçi Yönetimi</p> <!-- Linke uygun isim -->
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="hasta_yonetimi.php" class="nav-link"> <!-- TEDARİKÇİ yönetimi linki doktor_yonetimi.php değil, tedarikci_yonetimi.php olmalı -->
                        <i class="nav-icon fas fa-users"></i> <!-- Tedarikçiyi/taşımacılığı temsil eder -->
                        <p>Hatsta Yönetimi</p> <!-- Linke uygun isim -->
                    </a>
                </li>
                
                <!-- Opsiyonel: Eğer satışlar için ayrı bir sayfa varsa -->
                <!--
                <li class="nav-item">
                    <a href="satis_yonetimi.php" class="nav-link">
                        <i class="nav-icon fas fa-cash-register"></i>
                        <p>Satış İşlemleri</p>
                    </a>
                </li>
                -->

               
            </ul>
        </nav>
    </div>
</aside>


<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><h1 class="m-0">Reçete Yönetim Paneli</h1></div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php display_form_message(); ?>

            <!-- Yeni Reçete Ekleme / Düzenleme Formu (Değişiklik yok) -->
            <?php if ($show_edit_form && $receteToEdit): ?>
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit"></i> Reçete Düzenle (ID: <?php echo htmlspecialchars($receteToEdit['recete_id']); ?>)</h3>
                </div>
                <form method="POST" action="recete_yonetimi.php">
                    <input type="hidden" name="action" value="edit_recete">
                    <input type="hidden" name="recete_id" value="<?php echo htmlspecialchars($receteToEdit['recete_id']); ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="recete_tarih_edit">Reçete Tarihi (*)</label>
                                <input type="date" class="form-control form-control-sm" id="recete_tarih_edit" name="recete_tarih" value="<?php echo htmlspecialchars($receteToEdit['recete_tarih']); ?>" required>
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="hasta_tckn_edit">Hasta (*)</label>
                                <select class="form-control form-control-sm" id="hasta_tckn_edit" name="hasta_tckn" required>
                                    <option value="">Seçiniz...</option>
                                    <?php foreach ($hastalar as $hasta): ?>
                                        <option value="<?php echo htmlspecialchars($hasta['hasta_tckn']); ?>" <?php if ($receteToEdit['hasta_tckn'] == $hasta['hasta_tckn']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($hasta['hasta_ad_soyad']); ?> (TCKN: <?php echo htmlspecialchars($hasta['hasta_tckn']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                             <div class="col-md-6 form-group">
                                <label for="ilac_id_edit">İlaç (*)</label>
                                <select class="form-control form-control-sm" id="ilac_id_edit" name="ilac_id" required>
                                    <option value="">Seçiniz...</option>
                                    <?php foreach ($ilaclar as $ilac): ?>
                                        <option value="<?php echo htmlspecialchars($ilac['ilac_id']); ?>" <?php if ($receteToEdit['ilac_id'] == $ilac['ilac_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($ilac['ilac_ad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="doktor_id_edit">Doktor (*)</label>
                                <select class="form-control form-control-sm" id="doktor_id_edit" name="doktor_id" required>
                                    <option value="">Seçiniz...</option>
                                    <?php foreach ($doktorlar as $doktor): ?>
                                        <option value="<?php echo htmlspecialchars($doktor['doktor_id']); ?>" <?php if ($receteToEdit['doktor_id'] == $doktor['doktor_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($doktor['doktor_ad_soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="ilac_adet_edit">İlaç Adet (*)</label>
                                <input type="number" class="form-control form-control-sm" id="ilac_adet_edit" name="ilac_adet" value="<?php echo htmlspecialchars($receteToEdit['ilac_adet']); ?>" required min="1">
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="kullanim_doz_edit">Kullanım Dozu (*)</label>
                                <input type="text" class="form-control form-control-sm" id="kullanim_doz_edit" name="kullanim_doz" value="<?php echo htmlspecialchars($receteToEdit['kullanim_doz']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="recete_yonetimi.php" class="btn btn-sm btn-secondary"><i class="fas fa-times mr-1"></i> İptal</a>
                        <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save mr-1"></i> Reçeteyi Güncelle</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus-circle"></i> Yeni Reçete Ekle</h3>
                </div>
                <form method="POST" action="recete_yonetimi.php">
                    <input type="hidden" name="action" value="add_recete">
                    <div class="card-body">
                         <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="recete_tarih">Reçete Tarihi (*)</label>
                                <input type="date" class="form-control form-control-sm" id="recete_tarih" name="recete_tarih" value="<?php echo htmlspecialchars($form_data_add['recete_tarih']); ?>" required>
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="hasta_tckn">Hasta (*)</label>
                                <select class="form-control form-control-sm" id="hasta_tckn" name="hasta_tckn" required>
                                    <option value="">Seçiniz...</option>
                                    <?php foreach ($hastalar as $hasta): ?>
                                        <option value="<?php echo htmlspecialchars($hasta['hasta_tckn']); ?>" <?php if ($form_data_add['hasta_tckn'] == $hasta['hasta_tckn']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($hasta['hasta_ad_soyad']); ?> (TCKN: <?php echo htmlspecialchars($hasta['hasta_tckn']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                         <div class="row">
                             <div class="col-md-6 form-group">
                                <label for="ilac_id">İlaç (*)</label>
                                <select class="form-control form-control-sm" id="ilac_id" name="ilac_id" required>
                                    <option value="">Seçiniz...</option>
                                    <?php foreach ($ilaclar as $ilac): ?>
                                        <option value="<?php echo htmlspecialchars($ilac['ilac_id']); ?>" <?php if ($form_data_add['ilac_id'] == $ilac['ilac_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($ilac['ilac_ad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="doktor_id">Doktor (*)</label>
                                <select class="form-control form-control-sm" id="doktor_id" name="doktor_id" required>
                                     <option value="">Seçiniz...</option>
                                    <?php foreach ($doktorlar as $doktor): ?>
                                        <option value="<?php echo htmlspecialchars($doktor['doktor_id']); ?>" <?php if ($form_data_add['doktor_id'] == $doktor['doktor_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($doktor['doktor_ad_soyad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="ilac_adet">İlaç Adet (*)</label>
                                <input type="number" class="form-control form-control-sm" id="ilac_adet" name="ilac_adet" value="<?php echo htmlspecialchars($form_data_add['ilac_adet']); ?>" required min="1">
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="kullanim_doz">Kullanım Dozu (*)</label>
                                <input type="text" class="form-control form-control-sm" id="kullanim_doz" name="kullanim_doz" value="<?php echo htmlspecialchars($form_data_add['kullanim_doz']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i> Yeni Reçete Ekle</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Mevcut Reçeteler Tablosu -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-ul"></i> Kayıtlı Reçeteler</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-sm table-striped">
                        <thead class="thead-light">
                            <!-- ** 2. ADIM: TABLO BAŞLIKLARINI LİNKE ÇEVİRME ** -->
                            <tr>
                                <th><?php echo get_sort_link('id', 'ID', $sort_by, $order); ?></th>
                                <th><?php echo get_sort_link('tarih', 'Tarih', $sort_by, $order); ?></th>
                                <th><?php echo get_sort_link('hasta', 'Hasta', $sort_by, $order); ?></th>
                                <th><?php echo get_sort_link('ilac', 'İlaç', $sort_by, $order); ?></th>
                                <th><?php echo get_sort_link('doktor', 'Doktor', $sort_by, $order); ?></th>
                                <th class="text-center"><?php echo get_sort_link('adet', 'Adet', $sort_by, $order); ?></th>
                                <th>Kullanım Dozu</th> <!-- Doza göre sıralama genellikle mantıklı olmadığı için eklenmedi -->
                                <th class="text-center" style="width: 100px;">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($receteler)): ?>
                                <?php foreach($receteler as $recete): ?>
                                    <tr>
                                        <td><?php echo $recete['recete_id']; ?></td>
                                        <td><?php echo htmlspecialchars(date("d.m.Y", strtotime($recete['recete_tarih']))); ?></td>
                                        <td><?php echo htmlspecialchars($recete['hasta_ad']); ?></td>
                                        <td><?php echo htmlspecialchars($recete['ilac_ad']); ?></td>
                                        <td><?php echo htmlspecialchars($recete['doktor_ad']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($recete['ilac_adet']); ?></td>
                                        <td><?php echo htmlspecialchars($recete['kullanim_doz']); ?></td>
                                        <td class="text-center">
                                            <a href="recete_yonetimi.php?action=edit_recete_form&id=<?php echo $recete['recete_id']; ?>" class="btn btn-info btn-xs" title="Düzenle"><i class="fas fa-edit"></i></a>
                                            <a href="recete_yonetimi.php?action=delete_recete&id=<?php echo $recete['recete_id']; ?>" class="btn btn-danger btn-xs" title="Sil"
                                               onclick="return confirm('ID: <?php echo $recete['recete_id']; ?> numaralı reçeteyi silmek istediğinizden emin misiniz?');">
                                               <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center py-3">Kayıtlı reçete bulunmamaktadır.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
<footer class="main-footer text-sm"><strong>© <?php echo date("Y"); ?> PharmAnalytics Pro</strong></footer>
</div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script>
    $(function () {
        // Alert mesajlarını otomatik kapatma
        window.setTimeout(function() {
            $(".alert-dismissible.show").fadeTo(500, 0).slideUp(500, function(){
                $(this).removeClass('show').addClass('d-none'); // Tamamen gizle
            });
        }, 5000); // 5 saniye sonra
    });
</script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
?>