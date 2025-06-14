<?php
// Geliştirme için hata raporlamayı açabilirsiniz
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'data3.php';

if (!$conn || $conn->connect_error) {
    die("Veritabanı bağlantısı kurulamadı.");
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
        unset($_SESSION['form_message'], $_SESSION['form_message_type']);
    }
}


// --- FORM VERİLERİ ---
$form_data_add = [
    'uretici_ad' => '', 'hesap_no' => '',
    'yeni_adres_il' => '', 'yeni_adres_ilce' => '', 'yeni_adres_mahalle' => '',
    'yeni_adres_cadde_sokak' => '', 'yeni_adres_kapi_no' => '', 'yeni_adres_posta_kodu' => ''
];
$tedarikciToEdit = null;
$show_edit_form = false;

// --- İŞLEM YÖNETİMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_tedarikci') {
        $uretici_ad = trim($_POST['uretici_ad'] ?? '');
        $hesap_no = !empty($_POST['hesap_no']) ? trim($_POST['hesap_no']) : null;
        $adres_id = null; // Her zaman yeni adres ekleneceği için null
        $yeni_il = trim($_POST['yeni_adres_il'] ?? '');
        $yeni_ilce = trim($_POST['yeni_adres_ilce'] ?? '');
        $yeni_mahalle = trim($_POST['yeni_adres_mahalle'] ?? '');
        $yeni_cadde_sokak = trim($_POST['yeni_adres_cadde_sokak'] ?? '');
        $yeni_kapi_no = !empty($_POST['yeni_adres_kapi_no']) ? (int)$_POST['yeni_adres_kapi_no'] : null;
        $yeni_posta_kodu = !empty($_POST['yeni_adres_posta_kodu']) ? (int)$_POST['yeni_adres_posta_kodu'] : null;

        if (empty($uretici_ad) || empty($yeni_il)) {
            set_form_message("Tedarikçi adı ve İl bilgisi boş bırakılamaz.", 'warning');
        } else {
            try {
                $stmt = $conn->prepare("CALL sp_TedarikciVeAdresEkle(?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisssiii", $uretici_ad, $hesap_no, $adres_id, $yeni_il, $yeni_ilce, $yeni_mahalle, $yeni_cadde_sokak, $yeni_kapi_no, $yeni_posta_kodu);
                if ($stmt->execute()) {
                    set_form_message("Tedarikçi ve yeni adresi başarıyla eklendi.", 'success');
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) $user_message = "Bu tedarikçi adı zaten kayıtlı.";
                else $user_message = "Tedarikçi eklenirken bir hata oluştu.";
                set_form_message($user_message . "<hr>Teknik Detay: " . htmlspecialchars($e->getMessage()), 'danger');
            }
        }
        header("Location: tedarikci_yonetimi.php");
        exit;
    }

    // YENİ: Tamamen değiştirilmiş güncelleme mantığı
    if ($_POST['action'] == 'edit_tedarikci') {
        // Tüm form verilerini al
        $uretici_id = (int)($_POST['uretici_id'] ?? 0);
        $adres_id = (int)($_POST['adres_id'] ?? 0);
        $uretici_ad = trim($_POST['uretici_ad'] ?? '');
        $hesap_no = !empty($_POST['hesap_no']) ? trim($_POST['hesap_no']) : null;
        $il = trim($_POST['il'] ?? '');
        $ilce = trim($_POST['ilce'] ?? '');
        $mahalle = trim($_POST['mahalle'] ?? '');
        $cadde_sokak = trim($_POST['cadde_sokak'] ?? '');
        $kapi_no = !empty($_POST['kapi_no']) ? (int)$_POST['kapi_no'] : null;
        $posta_kodu = !empty($_POST['posta_kodu']) ? (int)$_POST['posta_kodu'] : null;

        if ($uretici_id <= 0 || $adres_id <= 0 || empty($uretici_ad) || empty($il)) {
            set_form_message("Tedarikçi ID, Adres ID, Tedarikçi Adı ve İl bilgileri boş bırakılamaz.", 'warning');
        } else {
            try {
                // Yeni stored procedure'ü çağır
                $stmt = $conn->prepare("CALL sp_TedarikciVeAdresGuncelle(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssssiii", $uretici_id, $adres_id, $uretici_ad, $hesap_no, $il, $ilce, $mahalle, $cadde_sokak, $kapi_no, $posta_kodu);
                if ($stmt->execute()) {
                    set_form_message("Tedarikçi başarıyla güncellendi.", 'success');
                    header("Location: tedarikci_yonetimi.php");
                    exit;
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                set_form_message("Tedarikçi güncellenirken hata: " . htmlspecialchars($e->getMessage()), 'danger');
            }
        }
        // Hata durumunda düzenleme formuna geri yönlendir
        header("Location: tedarikci_yonetimi.php?action=edit_tedarikci_form&id=" . $uretici_id);
        exit;
    }
}

// GET İSTEKLERİ
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id_param = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action == 'delete_tedarikci' && $id_param > 0) {
        try {
            $stmt = $conn->prepare("CALL sp_TedarikciSil(?)");
            $stmt->bind_param("i", $id_param);
            $stmt->execute();
            set_form_message("Tedarikçi ve ilişkili tüm ilaçlar başarıyla silindi.", 'info');
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
             if ($e->getCode() == 1451) $user_message = "Bu tedarikçi silinemiyor çünkü başka kayıtlarla ilişkili.";
             else $user_message = "Tedarikçi silinirken bir veritabanı hatası oluştu.";
            set_form_message($user_message . "<hr>Teknik Detay: " . htmlspecialchars($e->getMessage()), 'danger');
        }
        header("Location: tedarikci_yonetimi.php");
        exit;
    } elseif ($action == 'edit_tedarikci_form' && $id_param > 0) {
        try {
            // YENİ: Güncellenmiş SP'yi çağırıyoruz. Artık adres detayları da geliyor.
            $stmt = $conn->prepare("CALL sp_GetTedarikciById(?)");
            $stmt->bind_param("i", $id_param);
            $stmt->execute();
            $result = $stmt->get_result();
            $tedarikciToEdit = $result->fetch_assoc();
            $show_edit_form = $tedarikciToEdit ? true : false;
            if (!$show_edit_form) set_form_message("Düzenlenecek tedarikçi bulunamadı.", 'warning');
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            set_form_message("Tedarikçi verileri çekilirken hata: " . htmlspecialchars($e->getMessage()), 'danger');
        }
    }
}

// --- MEVCUT TEDARİKÇİLERİ LİSTELEME ---
$tedarikciler_listesi = [];
try {
    $result_tedarikciler = $conn->query("CALL sp_ListTedarikciler()");
    if ($result_tedarikciler) {
        $tedarikciler_listesi = $result_tedarikciler->fetch_all(MYSQLI_ASSOC);
        $result_tedarikciler->close();
    }
} catch (mysqli_sql_exception $e) {
    set_form_message("Tedarikçi listesi yüklenirken bir hata oluştu: " . htmlspecialchars($e->getMessage()), "danger");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tedarikçi Yönetimi - PharmAnalytics Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        :root { --accent-color: #c82333; }
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; color: #2c3e50; }
        .main-header { background-color: #ffffff; }
        .main-sidebar { background-color: var(--accent-color); }
        .content-wrapper { padding: 25px; }
        .form-control-sm { height: calc(1.8125rem + 2px); }
        .btn-xs { padding: .125rem .25rem; font-size: .75rem; line-height: 1.5; border-radius: .15rem;}
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <!-- Navbar ve Sidebar (Değişiklik yok) -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light"><ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li></ul><span class="navbar-brand-custom mx-auto d-block text-center h5 mb-0">Tedarikçi Yönetimi</span></nav>
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
    <section class="content-header"><div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1 class="m-0">Tedarikçi Yönetim Paneli</h1></div></div></div></section>

    <section class="content">
        <div class="container-fluid">
            <?php display_form_message(); ?>

            <!-- Yeni Tedarikçi Ekleme / Düzenleme Formu -->
            <?php if ($show_edit_form && $tedarikciToEdit): ?>
            <!-- YENİ: Tamamen değiştirilmiş Düzenleme Formu -->
            <div class="card card-warning card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-edit"></i> Tedarikçi Düzenle (ID: <?php echo htmlspecialchars($tedarikciToEdit['uretici_id']); ?>)</h3></div>
                <form method="POST" action="tedarikci_yonetimi.php">
                    <input type="hidden" name="action" value="edit_tedarikci">
                    <input type="hidden" name="uretici_id" value="<?php echo htmlspecialchars($tedarikciToEdit['uretici_id']); ?>">
                    <input type="hidden" name="adres_id" value="<?php echo htmlspecialchars($tedarikciToEdit['adres_id']); ?>">
                    <div class="card-body">
                        <h5 class="mb-3">Tedarikçi Bilgileri</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="uretici_ad_edit">Tedarikçi Adı (*)</label>
                                <input type="text" class="form-control form-control-sm" id="uretici_ad_edit" name="uretici_ad" value="<?php echo htmlspecialchars($tedarikciToEdit['uretici_ad']); ?>" required>
                            </div>
                             <div class="col-md-6 form-group">
                                <label for="hesap_no_edit">Hesap No</label>
                                <input type="text" class="form-control form-control-sm" id="hesap_no_edit" name="hesap_no" value="<?php echo htmlspecialchars($tedarikciToEdit['hesap_no'] ?? ''); ?>" pattern="\d*">
                                <small class="form-text text-muted">Sadece rakam giriniz.</small>
                            </div>
                        </div>
                        <hr>
                        <h5 class="mb-3">Adres Bilgileri</h5>
                         <div class="row">
                            <div class="col-md-6 form-group"><label for="il_edit">İl (*)</label><input type="text" class="form-control form-control-sm" id="il_edit" name="il" value="<?php echo htmlspecialchars($tedarikciToEdit['il'] ?? ''); ?>" required></div>
                            <div class="col-md-6 form-group"><label for="ilce_edit">İlçe</label><input type="text" class="form-control form-control-sm" id="ilce_edit" name="ilce" value="<?php echo htmlspecialchars($tedarikciToEdit['ilce'] ?? ''); ?>"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group"><label for="mahalle_edit">Mahalle</label><input type="text" class="form-control form-control-sm" id="mahalle_edit" name="mahalle" value="<?php echo htmlspecialchars($tedarikciToEdit['mahalle'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label for="cadde_sokak_edit">Cadde/Sokak</label><input type="text" class="form-control form-control-sm" id="cadde_sokak_edit" name="cadde_sokak" value="<?php echo htmlspecialchars($tedarikciToEdit['cadde_sokak'] ?? ''); ?>"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group"><label for="kapi_no_edit">Kapı No</label><input type="number" class="form-control form-control-sm" id="kapi_no_edit" name="kapi_no" value="<?php echo htmlspecialchars($tedarikciToEdit['kapi_no'] ?? ''); ?>"></div>
                            <div class="col-md-6 form-group"><label for="posta_kodu_edit">Posta Kodu</label><input type="number" class="form-control form-control-sm" id="posta_kodu_edit" name="posta_kodu" value="<?php echo htmlspecialchars($tedarikciToEdit['posta_kodu'] ?? ''); ?>"></div>
                        </div>
                    </div>
                    <div class="card-footer text-right"><a href="tedarikci_yonetimi.php" class="btn btn-sm btn-secondary"><i class="fas fa-times mr-1"></i> İptal</a><button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save mr-1"></i> Tedarikçiyi Güncelle</button></div>
                </form>
            </div>
            <?php else: ?>
            <!-- Yeni Tedarikçi Ekleme Formu (Değişiklik yok) -->
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle"></i> Yeni Tedarikçi Ekle</h3></div>
                <form method="POST" action="tedarikci_yonetimi.php">
                    <input type="hidden" name="action" value="add_tedarikci">
                    <div class="card-body">
                        <h5 class="mb-3">Tedarikçi Bilgileri</h5>
                        <div class="row">
                            <div class="col-md-6 form-group"><label for="uretici_ad">Tedarikçi Adı (*)</label><input type="text" class="form-control form-control-sm" id="uretici_ad" name="uretici_ad" value="<?php echo htmlspecialchars($form_data_add['uretici_ad']); ?>" required></div>
                            <div class="col-md-6 form-group"><label for="hesap_no">Hesap No</label><input type="text" class="form-control form-control-sm" id="hesap_no" name="hesap_no" value="<?php echo htmlspecialchars($form_data_add['hesap_no']); ?>" pattern="\d*"><small class="form-text text-muted">Sadece rakam giriniz.</small></div>
                        </div>
                        <hr>
                        <h5 class="mb-3">Adres Bilgileri</h5>
                        <div class="row">
                            <div class="col-md-6 form-group"><label for="yeni_adres_il">İl (*)</label><input type="text" class="form-control form-control-sm" id="yeni_adres_il" name="yeni_adres_il" required></div>
                            <div class="col-md-6 form-group"><label for="yeni_adres_ilce">İlçe</label><input type="text" class="form-control form-control-sm" id="yeni_adres_ilce" name="yeni_adres_ilce"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group"><label for="yeni_adres_mahalle">Mahalle</label><input type="text" class="form-control form-control-sm" id="yeni_adres_mahalle" name="yeni_adres_mahalle"></div>
                            <div class="col-md-6 form-group"><label for="yeni_adres_cadde_sokak">Cadde/Sokak</label><input type="text" class="form-control form-control-sm" id="yeni_adres_cadde_sokak" name="yeni_adres_cadde_sokak"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group"><label for="yeni_adres_kapi_no">Kapı No</label><input type="number" class="form-control form-control-sm" id="yeni_adres_kapi_no" name="yeni_adres_kapi_no"></div>
                            <div class="col-md-6 form-group"><label for="yeni_adres_posta_kodu">Posta Kodu</label><input type="number" class="form-control form-control-sm" id="yeni_adres_posta_kodu" name="yeni_adres_posta_kodu"></div>
                        </div>
                    </div>
                    <div class="card-footer text-right"><button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i> Yeni Tedarikçi Ekle</button></div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Mevcut Tedarikçiler Tablosu (Değişiklik yok) -->
            <div class="card card-info card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-list-ul"></i> Kayıtlı Tedarikçiler</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-sm table-striped">
                        <thead class="thead-light"><tr><th>ID</th><th>Tedarikçi Adı</th><th>Adres (Kısa)</th><th>Hesap No</th><th class="text-center" style="width: 100px;">İşlemler</th></tr></thead>
                        <tbody>
                            <?php if (!empty($tedarikciler_listesi)): ?>
                                <?php foreach($tedarikciler_listesi as $tedarikci): ?>
                                    <tr>
                                        <td><?php echo $tedarikci['uretici_id']; ?></td>
                                        <td><?php echo htmlspecialchars($tedarikci['uretici_ad']); ?></td>
                                        <td><?php echo htmlspecialchars($tedarikci['adres_kisa'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($tedarikci['hesap_no'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <a href="tedarikci_yonetimi.php?action=edit_tedarikci_form&id=<?php echo $tedarikci['uretici_id']; ?>" class="btn btn-info btn-xs" title="Düzenle"><i class="fas fa-edit"></i></a>
                                            <a href="tedarikci_yonetimi.php?action=delete_tedarikci&id=<?php echo $tedarikci['uretici_id']; ?>" class="btn btn-danger btn-xs" title="Sil" onclick="return confirm('\'<?php echo htmlspecialchars(addslashes($tedarikci['uretici_ad'])); ?>\' adlı tedarikçiyi ve ilişkili tüm ilaçlarını silmek istediğinizden emin misiniz?');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-3">Kayıtlı tedarikçi bulunmamaktadır.</td></tr>
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
        window.setTimeout(function() {
            $(".alert-dismissible.show").fadeTo(500, 0).slideUp(500, function(){
                $(this).removeClass('show').addClass('d-none');
            });
        }, 7000);
    });
</script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
?>