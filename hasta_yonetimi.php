<?php
// Hata raporlamayı aç
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'data3.php';

if (!$conn || $conn->connect_error) { die("Veritabanı bağlantısı kurulamadı."); }
$conn->set_charset("utf8mb4");

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// --- YARDIMCI FONKSİYONLAR ---
function set_form_message($message, $type = 'danger') {
    $_SESSION['form_message'] = $message;
    $_SESSION['form_message_type'] = $type;
}

function display_form_message() {
    if (isset($_SESSION['form_message']) && !empty($_SESSION['form_message'])) {
        echo '<div class="alert alert-' . htmlspecialchars($_SESSION['form_message_type'] ?? 'danger') . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['form_message'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>';
        unset($_SESSION['form_message'], $_SESSION['form_message_type']);
    }
}

// --- FORM VERİLERİ İÇİN BAŞLANGIÇ DEĞERLERİ ---
$form_data_add = ['hasta_tckn' => '', 'hasta_ad_soyad' => '', 'cinsiyet' => '', 'dog_tar' => '', 'telefon' => '', 'adres_id' => ''];
$hastaToEdit = null;
$show_edit_form = false;

// --- İŞLEM YÖNETİMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_hasta') {
        $form_data_add = $_POST;
        if (empty($_POST['hasta_tckn']) || empty($_POST['hasta_ad_soyad']) || empty($_POST['adres_id'])) {
            set_form_message("TC Kimlik Numarası, Ad Soyad ve Adres alanları zorunludur.", 'warning');
        } elseif (!is_numeric($_POST['hasta_tckn']) || strlen($_POST['hasta_tckn']) != 11) {
            set_form_message("TC Kimlik Numarası 11 haneli bir sayı olmalıdır.", 'warning');
        } else {
            try {
                $stmt = $conn->prepare("CALL sp_HastaEkle(?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssii", $_POST['hasta_tckn'], $_POST['hasta_ad_soyad'], $_POST['cinsiyet'], $_POST['dog_tar'], $_POST['telefon'], $_POST['adres_id']);
                if ($stmt->execute()) {
                    set_form_message("Hasta başarıyla eklendi.", 'success');
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) $user_message = "Bu TC Kimlik Numarası zaten kayıtlı.";
                else if ($e->getCode() == 1452) $user_message = "Geçersiz bir adres seçtiniz.";
                else $user_message = "Hasta eklenirken bir veritabanı hatası oluştu.";
                set_form_message($user_message, 'danger');
            }
        }
        header("Location: hasta_yonetimi.php");
        exit;
    }

    if ($_POST['action'] == 'edit_hasta') {
        $tckn = $_POST['hasta_tckn'] ?? 0;
        if (empty($tckn) || empty($_POST['hasta_ad_soyad']) || empty($_POST['adres_id'])) {
            set_form_message("Ad Soyad ve Adres alanları zorunludur.", 'warning');
        } else {
            try {
                $stmt = $conn->prepare("CALL sp_HastaGuncelle(?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiss", $_POST['hasta_ad_soyad'], $_POST['cinsiyet'], $_POST['dog_tar'], $_POST['telefon'], $_POST['adres_id'], $tckn);
                if ($stmt->execute()) {
                    set_form_message("Hasta bilgileri başarıyla güncellendi.", 'success');
                    header("Location: hasta_yonetimi.php");
                    exit;
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                // Hata yönetimi...
                set_form_message("Hasta güncellenirken bir hata oluştu.", 'danger');
            }
        }
        header("Location: hasta_yonetimi.php?action=edit_hasta_form&tckn=" . $tckn);
        exit;
    }
}

// GET İSTEKLERİ
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $tckn_param = $_GET['tckn'] ?? 0;

    if ($action == 'delete_hasta' && $tckn_param > 0) {
        try {
            $stmt = $conn->prepare("CALL sp_HastaSilVeReceteleri(?)");
            $stmt->bind_param("s", $tckn_param);
            $stmt->execute();
            set_form_message("Hasta ve ilişkili tüm reçeteleri başarıyla silindi.", 'info');
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            set_form_message("Hasta silinirken bir veritabanı hatası oluştu.", 'danger');
        }
        header("Location: hasta_yonetimi.php");
        exit;
    } elseif ($action == 'edit_hasta_form' && $tckn_param > 0) {
        try {
            $stmt = $conn->prepare("CALL sp_GetHastaByTckn(?)");
            $stmt->bind_param("s", $tckn_param);
            $stmt->execute();
            $result = $stmt->get_result();
            $hastaToEdit = $result->fetch_assoc();
            $show_edit_form = $hastaToEdit ? true : false;
            if (!$show_edit_form) set_form_message("Düzenlenecek hasta bulunamadı.", 'warning');
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            set_form_message("Hasta verileri çekilirken hata oluştu.", 'danger');
        }
    }
}

// --- SAYFA İÇİN GEREKLİ VERİLERİ ÇEKME ---
$adresler_listesi = [];
$hastalar_listesi = [];
try {
    $result_adresler = $conn->query("CALL sp_GetAdreslerListesi()");
    if ($result_adresler) {
        $adresler_listesi = $result_adresler->fetch_all(MYSQLI_ASSOC);
        $result_adresler->close();
        $conn->next_result();
    }

    $result_hastalar = $conn->query("CALL sp_ListHastalar()");
    if ($result_hastalar) {
        $hastalar_listesi = $result_hastalar->fetch_all(MYSQLI_ASSOC);
        $result_hastalar->close();
    }
} catch (mysqli_sql_exception $e) {
    set_form_message("Sayfa verileri yüklenirken bir hata oluştu.", "danger");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Yönetimi - PharmAnalytics Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        :root { --accent-color: #17a2b8; /* Hasta için accent rengi (info) */ }
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; }
        .main-sidebar { background-color: #c82333; }
        .btn-xs { padding: .125rem .25rem; font-size: .75rem; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar ve Sidebar HTML'i (Diğer sayfalardan kopyalanabilir) -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li></ul>
        <span class="navbar-brand-custom mx-auto d-block text-center h5 mb-0">Hasta Yönetimi</span>
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
        <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1 class="m-0">Hasta Yönetim Paneli</h1></div></div></div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php display_form_message(); ?>

            <!-- Ekleme veya Düzenleme Formu -->
            <?php if ($show_edit_form && $hastaToEdit): ?>
                <!-- DÜZENLEME FORMU -->
                <div class="card card-warning card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user-edit"></i> Hasta Düzenle</h3></div>
                <form method="POST" action="hasta_yonetimi.php">
                    <input type="hidden" name="action" value="edit_hasta">
                    <input type="hidden" name="hasta_tckn" value="<?php echo htmlspecialchars($hastaToEdit['hasta_tckn']); ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>TC Kimlik No</label>
                                <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hastaToEdit['hasta_tckn']); ?>" disabled>
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="hasta_ad_soyad_edit">Adı Soyadı (*)</label>
                                <input type="text" class="form-control form-control-sm" id="hasta_ad_soyad_edit" name="hasta_ad_soyad" value="<?php echo htmlspecialchars($hastaToEdit['hasta_ad_soyad']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="cinsiyet_edit">Cinsiyet</label>
                                <select class="form-control form-control-sm" id="cinsiyet_edit" name="cinsiyet">
                                    <option value="E" <?php if ($hastaToEdit['cinsiyet'] == 'E') echo 'selected'; ?>>Erkek</option>
                                    <option value="K" <?php if ($hastaToEdit['cinsiyet'] == 'K') echo 'selected'; ?>>Kadın</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="dog_tar_edit">Doğum Tarihi</label>
                                <input type="date" class="form-control form-control-sm" id="dog_tar_edit" name="dog_tar" value="<?php echo htmlspecialchars($hastaToEdit['dog_tar']); ?>">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="telefon_edit">Telefon</label>
                                <input type="tel" class="form-control form-control-sm" id="telefon_edit" name="telefon" value="<?php echo htmlspecialchars($hastaToEdit['telefon']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="adres_id_edit">Adres (*)</label>
                            <select class="form-control form-control-sm" id="adres_id_edit" name="adres_id" required>
                                <option value="">Seçiniz...</option>
                                <?php foreach ($adresler_listesi as $adres): ?>
                                    <option value="<?php echo $adres['adres_id']; ?>" <?php if ($hastaToEdit['adres_id'] == $adres['adres_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($adres['tam_adres']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="hasta_yonetimi.php" class="btn btn-sm btn-secondary">İptal</a>
                        <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save mr-1"></i> Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
                <!-- EKLEME FORMU -->
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user-plus"></i> Yeni Hasta Ekle</h3></div>
                <form method="POST" action="hasta_yonetimi.php">
                    <input type="hidden" name="action" value="add_hasta">
                    <div class="card-body">
                         <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="hasta_tckn">TC Kimlik No (*)</label>
                                <input type="text" class="form-control form-control-sm" id="hasta_tckn" name="hasta_tckn" value="<?php echo htmlspecialchars($form_data_add['hasta_tckn']); ?>" required pattern="[0-9]{11}">
                            </div>
                            <div class="col-md-8 form-group">
                                <label for="hasta_ad_soyad">Adı Soyadı (*)</label>
                                <input type="text" class="form-control form-control-sm" id="hasta_ad_soyad" name="hasta_ad_soyad" value="<?php echo htmlspecialchars($form_data_add['hasta_ad_soyad']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="cinsiyet">Cinsiyet</label>
                                <select class="form-control form-control-sm" id="cinsiyet" name="cinsiyet">
                                    <option value="E" selected>Erkek</option>
                                    <option value="K">Kadın</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 form-group">
                                <label for="dog_tar">Doğum Tarihi</label>
                                <input type="date" class="form-control form-control-sm" id="dog_tar" name="dog_tar">
                            </div>
                             <div class="col-md-4 form-group">
                                <label for="telefon">Telefon</label>
                                <input type="tel" class="form-control form-control-sm" id="telefon" name="telefon" placeholder="5XXXXXXXXX">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="adres_id">Adres (*)</label>
                            <select class="form-control form-control-sm" id="adres_id" name="adres_id" required>
                                <option value="">Seçiniz...</option>
                                <?php foreach ($adresler_listesi as $adres): ?>
                                <option value="<?php echo $adres['adres_id']; ?>"><?php echo htmlspecialchars($adres['tam_adres']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i> Yeni Hasta Ekle</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Mevcut Hastalar Tablosu -->
            <div class="card card-info card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-users"></i> Kayıtlı Hastalar</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-sm table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th>TCKN</th><th>Adı Soyadı</th><th>Cinsiyet</th><th class="text-center">Yaş</th><th>Telefon</th><th>Adres (İl - İlçe)</th><th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($hastalar_listesi)): ?>
                                <?php foreach($hastalar_listesi as $hasta): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($hasta['hasta_tckn']); ?></td>
                                        <td><?php echo htmlspecialchars($hasta['hasta_ad_soyad']); ?></td>
                                        <td><?php echo ($hasta['cinsiyet'] == 'K') ? 'Kadın' : 'Erkek'; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($hasta['yas']); ?></td>
                                        <td><?php echo htmlspecialchars($hasta['telefon'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($hasta['kisa_adres'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <a href="?action=edit_hasta_form&tckn=<?php echo $hasta['hasta_tckn']; ?>" class="btn btn-info btn-xs" title="Düzenle"><i class="fas fa-edit"></i></a>
                                            <a href="?action=delete_hasta&tckn=<?php echo $hasta['hasta_tckn']; ?>" class="btn btn-danger btn-xs" title="Sil" onclick="return confirm('\'<?php echo htmlspecialchars(addslashes($hasta['hasta_ad_soyad'])); ?>\' adlı hastayı silmek istediğinizden emin misiniz?\n\nUYARI: Bu hastaya ait TÜM REÇETELER de kalıcı olarak silinecektir!');">
                                               <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-3">Kayıtlı hasta bulunmamaktadır.</td></tr>
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
</body>
</html>