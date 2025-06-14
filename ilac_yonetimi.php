<?php
// Hata raporlamayı etkinleştir
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Veritabanı hatalarını istisna olarak yakala
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'data3.php';

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- YARDIMCI FONKSİYONLAR ---

/**
 * Session'a kullanıcıya gösterilecek bir mesaj ekler.
 * @param string $message Gösterilecek mesaj.
 * @param string $type Mesajın türü (success, danger, warning, info).
 */
function set_form_message($message, $type = 'danger') {
    $_SESSION['form_message'] = ['text' => $message, 'type' => $type];
}

/**
 * Session'da bekleyen bir mesaj varsa onu ekranda gösterir.
 */
function display_form_message() {
    if (isset($_SESSION['form_message'])) {
        $message = $_SESSION['form_message'];
        echo '<div class="alert alert-' . htmlspecialchars($message['type']) . ' alert-dismissible fade show" role="alert">';
        echo $message['text'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>';
        echo '</div>';
        unset($_SESSION['form_message']);
    }
}

/**
 * Veritabanı hatalarını yakalayıp kullanıcı dostu mesajlara dönüştürür.
 * @param mysqli_sql_exception $e Yakalanan istisna.
 * @param string $context Hatanın oluştuğu işlem (ekleme, silme vb.).
 */
function handle_db_exception(mysqli_sql_exception $e, $context = 'işlem') {
    $error_code = $e->getCode();
    $user_message = "Veritabanı $context sırasında bir hata oluştu.";

    switch ($error_code) {
        case 1062: // Duplicate entry
            $user_message = (strpos(strtolower($e->getMessage()), 'barkod') !== false)
                ? "Bu barkod numarası zaten kayıtlı."
                : "Benzersiz bir alana tekrar eden değer girdiniz.";
            break;
        case 1451: // Foreign key constraint (DELETE/UPDATE)
            $user_message = "Bu kayıt başka verilerle (örn: reçeteler) ilişkili olduğu için silinemiyor.";
            break;
        case 1452: // Foreign key constraint (INSERT/UPDATE)
            $user_message = "İlişkili veri bulunamadı (örn: geçersiz üretici seçimi).";
            break;
    }
    $technical_details = "<hr><strong>Teknik Detay:</strong> Kod: {$error_code} - " . htmlspecialchars($e->getMessage());
    set_form_message($user_message . $technical_details, 'danger');
}


// --- İŞLEM YÖNETİMİ ---

// GET İSTEKLERİ (Silme)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete_drug') {
    $id_to_delete = (int)($_GET['id'] ?? 0);
    if ($id_to_delete > 0) {
        try {
            $stmt = $conn->prepare("CALL sp_IlacSil(?)");
            $stmt->bind_param("i", $id_to_delete);
            $stmt->execute();
            $stmt->close();
            set_form_message("İlaç başarıyla silindi.", 'info');
        } catch (mysqli_sql_exception $e) {
            handle_db_exception($e, 'silme');
        }
    }
    header("Location: ilac_yonetimi.php");
    exit;
}

// POST İSTEKLERİ (Ekleme)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_drug') {
    $ilac_ad = trim($_POST['ilac_ad'] ?? '');
    $barkod = trim($_POST['barkod'] ?? '');
    $uretici_id = !empty($_POST['uretici_id']) ? (int)$_POST['uretici_id'] : null;
    $stok = isset($_POST['stok']) ? (int)$_POST['stok'] : 0;
    $alis_fiyat = !empty($_POST['alis_fiyat']) ? (float)$_POST['alis_fiyat'] : 0.0;
    $satis_fiyat = !empty($_POST['satis_fiyat']) ? (float)$_POST['satis_fiyat'] : 0.0;
    $anlasma_baslangic = !empty($_POST['anlasma_baslangic']) ? $_POST['anlasma_baslangic'] : null;
    $anlasma_bitis = !empty($_POST['anlasma_bitis']) ? $_POST['anlasma_bitis'] : null;
    $eczane_id = 1; // Sabit olarak varsayıldı

    if (empty($ilac_ad) || empty($barkod)) {
        set_form_message("İlaç adı ve barkod boş bırakılamaz.", 'warning');
    } else {
        try {
            $stmt = $conn->prepare("CALL sp_IlacEkle(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddiisss",
                $ilac_ad, $barkod, $alis_fiyat, $satis_fiyat,
                $stok, $uretici_id, $eczane_id, $anlasma_baslangic, $anlasma_bitis
            );
            $stmt->execute();
            $stmt->close();
            set_form_message("İlaç başarıyla eklendi.", 'success');
        } catch (mysqli_sql_exception $e) {
            handle_db_exception($e, 'ekleme');
        }
    }
    header("Location: ilac_yonetimi.php");
    exit;
}

// --- SAYFA İÇİN VERİLERİ ÇEK ---
$ureticiler_listesi = $ilaclar_listesi = []; // Hata durumunda boş olmalarını garanti et

try {
    // Üreticileri çek
    $result_ureticiler = $conn->query("CALL sp_GetUreticilerListesi()");
    $ureticiler_listesi = $result_ureticiler->fetch_all(MYSQLI_ASSOC);
    $result_ureticiler->close();
    $conn->next_result();

    // İlaçları çek
    $result_ilaclar = $conn->query("CALL sp_GetIlaclarListesi()");
    $ilaclar_listesi = $result_ilaclar->fetch_all(MYSQLI_ASSOC);
    $result_ilaclar->close();
    $conn->next_result();
} catch (mysqli_sql_exception $e) {
    handle_db_exception($e, 'veri listeleme');
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlaç Yönetimi - PharmAnalytics Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .main-sidebar { background-color: #c82333 !important; }
        .nav-sidebar .nav-link.active { background-color: #a71d2a !important; }
        .card-primary.card-outline { border-top-color: #c82333; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar ve Sidebar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li></ul>
        <span class="navbar-brand-custom mx-auto d-block text-center h5 mb-0 font-weight-bold">İlaç Yönetimi</span>
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
            <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1 class="m-0">İlaç Yönetim Paneli</h1></div></div></div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php display_form_message(); ?>

                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Yeni İlaç Ekle</h3></div>
                    <form method="POST" action="ilac_yonetimi.php">
                        <input type="hidden" name="action" value="add_drug">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 form-group"><label for="ilac_ad">İlaç Adı (*)</label><input type="text" class="form-control form-control-sm" id="ilac_ad" name="ilac_ad" required></div>
                                <div class="col-md-6 form-group"><label for="barkod">Barkod (*)</label><input type="text" class="form-control form-control-sm" id="barkod" name="barkod" required pattern="\d+"></div>
                            </div>
                             <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="uretici_id">Üretici</label>
                                    <select class="form-control form-control-sm" id="uretici_id" name="uretici_id">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach ($ureticiler_listesi as $uretici): ?>
                                            <option value="<?php echo $uretici['uretici_id']; ?>"><?php echo htmlspecialchars($uretici['uretici_ad']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group"><label for="eczane_id">Eczane (*)</label><select class="form-control form-control-sm" id="eczane_id" name="eczane_id" required><option value="1" selected>Merkez Eczane</option></select></div>
                                <div class="col-md-2 form-group"><label for="stok">Stok</label><input type="number" class="form-control form-control-sm" id="stok" name="stok" value="0" min="0"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group"><label for="alis_fiyat">Alış Fiyatı (TL)</label><input type="number" step="0.01" class="form-control form-control-sm" id="alis_fiyat" name="alis_fiyat" value="0.00" min="0"></div>
                                <div class="col-md-3 form-group"><label for="satis_fiyat">Satış Fiyatı (TL)</label><input type="number" step="0.01" class="form-control form-control-sm" id="satis_fiyat" name="satis_fiyat" value="0.00" min="0"></div>
                                <div class="col-md-3 form-group"><label for="anlasma_baslangic">Anlaşma Başlangıç</label><input type="date" class="form-control form-control-sm" id="anlasma_baslangic" name="anlasma_baslangic"></div>
                                <div class="col-md-3 form-group"><label for="anlasma_bitis">Anlaşma Bitiş</label><input type="date" class="form-control form-control-sm" id="anlasma_bitis" name="anlasma_bitis"></div>
                            </div>
                        </div>
                        <div class="card-footer text-right"><button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i> Yeni İlaç Ekle</button></div>
                    </form>
                </div>

                <div class="card card-info card-outline">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-list-ul mr-2"></i>Kayıtlı İlaçlar</h3></div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-sm table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th><th>İlaç Adı</th><th>Barkod</th><th>Üretici</th><th>Eczane</th>
                                    <th class="text-center">Stok</th><th class="text-right">Satış Fiyatı</th><th class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ilaclar_listesi)): ?>
                                    <?php foreach($ilaclar_listesi as $ilac): ?>
                                        <tr>
                                            <td><?php echo $ilac['ilac_id']; ?></td>
                                            <td><?php echo htmlspecialchars($ilac['ilac_ad']); ?></td>
                                            <td><?php echo htmlspecialchars($ilac['barkod']); ?></td>
                                            <td><?php echo htmlspecialchars($ilac['uretici_ad'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($ilac['eczane_ad'] ?? 'N/A'); ?></td>
                                            <td class="text-center"><span class="badge badge-<?php echo ($ilac['stok'] > 10) ? 'success' : (($ilac['stok'] > 0) ? 'warning' : 'danger'); ?>"><?php echo $ilac['stok']; ?></span></td>
                                            <td class="text-right"><?php echo number_format((float)$ilac['satis_fiyat'], 2, ',', '.'); ?> TL</td>
                                            <td class="text-center">
                                                <a href="ilac_guncelle.php?id=<?php echo $ilac['ilac_id']; ?>" class="btn btn-info btn-xs" title="Düzenle"><i class="fas fa-edit"></i></a>
                                                <a href="ilac_yonetimi.php?action=delete_drug&id=<?php echo $ilac['ilac_id']; ?>" class="btn btn-danger btn-xs" title="Sil"
                                                   onclick="return confirm('\'<?php echo htmlspecialchars(addslashes($ilac['ilac_ad'])); ?>\' adlı ilacı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!');">
                                                   <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center py-3">Kayıtlı ilaç bulunmamaktadır.</td></tr>
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
        // Alert mesajlarını 5 saniye sonra otomatik kapat
        window.setTimeout(function() {
            $(".alert-dismissible.show").fadeTo(500, 0).slideUp(500, function(){
                $(this).remove();
            });
        }, 5000);
    });
</script>
</body>
</html>