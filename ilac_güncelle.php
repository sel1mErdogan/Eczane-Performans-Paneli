<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'data3.php';

// Session başlat (mesajları taşımak için)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- YARDIMCI FONKSİYONLAR ---
function set_update_form_message($message, $type = 'danger') {
    $_SESSION['update_form_message'] = $message;
    $_SESSION['update_form_message_type'] = $type;
}

function display_update_form_message() {
    if (isset($_SESSION['update_form_message']) && !empty($_SESSION['update_form_message'])) {
        echo '<div class="alert alert-' . ($_SESSION['update_form_message_type'] ?? 'danger') . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['update_form_message'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>';
        echo '</div>';
        unset($_SESSION['update_form_message']);
        unset($_SESSION['update_form_message_type']);
    }
}

// --- YARDIMCI FONKSİYONLAR (SAKLI YORDAM KULLANAN HALİ) ---
function get_ureticiler_for_update($conn) {
    $ureticiler = [];
    try {
        // YENİ: Saklı yordam çağrısı
        $result = $conn->query("CALL sp_GetUreticilerListesi()");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ureticiler[] = $row;
            }
            $result->close();
            $conn->next_result();
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Güncelleme için Üretici listesi çekilemedi: " . $e->getMessage());
    }
    return $ureticiler;
}

function get_drug_for_update($conn, $id) {
    try {
        // YENİ: Saklı yordam çağrısı
        $stmt = $conn->prepare("CALL sp_GetIlacById(?)");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = ($result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $data;
    } catch (mysqli_sql_exception $e) {
        error_log("Güncellenecek ilaç bilgisi getirilemedi: " . $e->getMessage());
    }
    return null;
}

function handle_drug_update_exception(mysqli_sql_exception $e) {
    $error_code = $e->getCode();
    $error_message = htmlspecialchars($e->getMessage());
    $user_message = "İlaç güncelleme sırasında bir hata oluştu.";

    if ($error_code == 1062) {
        $user_message = (strpos(strtolower($error_message), 'barkod') !== false) ?
            "Bu barkod numarası zaten başka bir ilaca ait." :
            "Benzersiz olması gereken bir alanda tekrar eden bir değer girdiniz.";
    } elseif ($error_code == 1452) {
         $user_message = "İlişkili bir veri bulunamadığı için (örn: geçersiz üretici) ilaç güncellenemedi.";
    }
    $technical_details = "<hr><strong>Teknik Detay:</strong> Kod: {$error_code}";
    set_update_form_message($user_message . $technical_details, 'danger');
}

// --- İŞLEM YÖNETİMİ ---
$ilac_id_to_edit = 0;
$edit_drug_data = null; 

if (isset($_GET['id'])) {
    $ilac_id_to_edit = (int)$_GET['id'];
    if ($ilac_id_to_edit > 0) {
        $edit_drug_data = get_drug_for_update($conn, $ilac_id_to_edit);
        if (!$edit_drug_data) {
            set_update_form_message("Güncellenecek ilaç bulunamadı veya geçersiz ID.", 'warning');
        }
    } else {
        set_update_form_message("Geçersiz ilaç ID'si.", 'warning');
    }
}

// POST isteği ile güncelleme
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_drug') {
    $ilac_id_update = isset($_POST['ilac_id_update']) ? (int)$_POST['ilac_id_update'] : 0;
    
    $edit_drug_data = $_POST; 
    $edit_drug_data['ilac_id'] = $ilac_id_update;

    if ($ilac_id_update == 0) {
        set_update_form_message("Güncellenecek ilaç ID'si bulunamadı.", 'danger');
    } elseif (empty($_POST['ilac_ad']) || empty($_POST['barkod'])) {
        set_update_form_message("İlaç adı ve barkod boş bırakılamaz.", 'danger');
    } else {
        try {
            // YENİ: Saklı yordam çağrısı
            $stmt = $conn->prepare("CALL sp_IlacGuncelle(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddiisssi", 
                $_POST['ilac_ad'], 
                $_POST['barkod'], 
                $_POST['alis_fiyat'], 
                $_POST['satis_fiyat'], 
                $_POST['stok'], 
                $_POST['uretici_id'], 
                $_POST['eczane_id'], 
                $_POST['anlasma_baslangic'], 
                $_POST['anlasma_bitis'], 
                $ilac_id_update
            );
            
            if ($stmt->execute()) {
                set_update_form_message("İlaç başarıyla güncellendi.", 'success');
                // Formu güncel verilerle tazelemek için veriyi tekrar çek
                $edit_drug_data = get_drug_for_update($conn, $ilac_id_update); 
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            handle_drug_update_exception($e);
        }
    }
} elseif (!$edit_drug_data && $ilac_id_to_edit == 0 && $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ilac_yonetimi.php");
    exit;
}

// --- VERİLERİ ÇEK (Form için) ---
$ureticiler_listesi_update = get_ureticiler_for_update($conn);

if ($edit_drug_data === null) {
    $edit_drug_data = ['ilac_ad' => '', 'barkod' => '', 'uretici_id' => null, 'stok' => 0, 'alis_fiyat' => 0.0, 'satis_fiyat' => 0.0, 'eczane_id' => 1, 'anlasma_baslangic' => '', 'anlasma_bitis' => ''];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlaç Güncelle - <?php echo htmlspecialchars($edit_drug_data['ilac_ad'] ?? 'Bulunamadı'); ?> - PharmAnalytics Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        :root { /* CSS Değişkenleri */
            --bg-main: #f4f7f9; --bg-card: #ffffff; --text-primary: #2c3e50;
            --accent-color: #ffc107; /* Güncelleme için sarı tema */
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); color: var(--text-primary); }
        .main-header { background-color: var(--bg-card); }
        .main-sidebar { background-color: #c82333; /* Ana tema rengi */ } 
        .content-wrapper { padding: 25px; }
        .card-outline-tabs > .card-header a.active { border-top: 3px solid var(--accent-color); }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li></ul>
        <span class="navbar-brand-custom mx-auto d-block text-center h5 mb-0">İlaç Güncelle</span>
    </nav>
   <aside class="main-sidebar sidebar-dark-danger elevation-4">
    <a href="eczane.php" class="brand-link"> <!-- Ana dashboard linki eczane.php olarak varsayıyorum -->
        <img src="dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">PharmAnalytics</span>
    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image"><img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image"></div>
            <div class="info"><a href="eczane.php" class="d-block">Yönetici Panel</a></div>
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
                    <div class="col-sm-6">
                        <h1 class="m-0">İlaç Güncelle: <?php echo htmlspecialchars($edit_drug_data['ilac_ad'] ?? 'Bulunamadı'); ?></h1>
                    </div>
                     <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="ilac_yonetimi.php">İlaç Listesi</a></li>
                            <li class="breadcrumb-item active">Güncelle</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php display_update_form_message(); ?>

                <?php if ($ilac_id_to_edit > 0 && $edit_drug_data && isset($edit_drug_data['ilac_id'])): // İlaç varsa formu göster ?>
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit"></i> İlaç Bilgileri</h3>
                    </div>
                    <form method="POST" action="ilac_güncelle.php?id=<?php echo $ilac_id_to_edit; ?>">
                        <input type="hidden" name="action" value="update_drug">
                        <input type="hidden" name="ilac_id_update" value="<?php echo $ilac_id_to_edit; ?>">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="ilac_ad">İlaç Adı (*)</label>
                                    <input type="text" class="form-control form-control-sm" id="ilac_ad" name="ilac_ad" value="<?php echo htmlspecialchars($edit_drug_data['ilac_ad']); ?>" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="barkod">Barkod (*)</label>
                                    <input type="text" class="form-control form-control-sm" id="barkod" name="barkod" value="<?php echo htmlspecialchars($edit_drug_data['barkod']); ?>" required pattern="\d+">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label for="uretici_id">Üretici</label>
                                    <select class="form-control form-control-sm" id="uretici_id" name="uretici_id">
                                        <option value="">Seçiniz...</option>
                                        <?php foreach ($ureticiler_listesi_update as $uretici): ?>
                                            <option value="<?php echo $uretici['uretici_id']; ?>" <?php if ($edit_drug_data['uretici_id'] == $uretici['uretici_id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($uretici['uretici_ad']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                 <div class="col-md-4 form-group">
                                    <label for="eczane_id">Eczane (*)</label>
                                    <select class="form-control form-control-sm" id="eczane_id" name="eczane_id" required>
                                        <option value="1" <?php if ($edit_drug_data['eczane_id'] == 1) echo 'selected'; ?>>Merkez Eczane (ID:1)</option>
                                    </select>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label for="stok">Stok</label>
                                    <input type="number" class="form-control form-control-sm" id="stok" name="stok" value="<?php echo htmlspecialchars($edit_drug_data['stok']); ?>" min="0">
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="alis_fiyat">Alış Fiyatı (TL)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" id="alis_fiyat" name="alis_fiyat" value="<?php echo number_format((float)$edit_drug_data['alis_fiyat'], 2, '.', ''); ?>" min="0">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="satis_fiyat">Satış Fiyatı (TL)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" id="satis_fiyat" name="satis_fiyat" value="<?php echo number_format((float)$edit_drug_data['satis_fiyat'], 2, '.', ''); ?>" min="0">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="anlasma_baslangic">Anlaşma Başlangıç</label>
                                    <input type="date" class="form-control form-control-sm" id="anlasma_baslangic" name="anlasma_baslangic" value="<?php echo htmlspecialchars($edit_drug_data['anlasma_baslangic']); ?>">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label for="anlasma_bitis">Anlaşma Bitiş</label>
                                    <input type="date" class="form-control form-control-sm" id="anlasma_bitis" name="anlasma_bitis" value="<?php echo htmlspecialchars($edit_drug_data['anlasma_bitis']); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <a href="ilac_yonetimi.php" class="btn btn-sm btn-secondary mr-2">İptal / Listeye Dön</a>
                            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save mr-1"></i> Değişiklikleri Kaydet</button>
                        </div>
                    </form>
                </div>
                <?php elseif ($ilac_id_to_edit > 0 && !$edit_drug_data): ?>
                    <div class="alert alert-warning">Belirtilen ID ile ilaç bulunamadı. <a href="ilac_yonetimi.php">Listeye dönün.</a></div>
                <?php endif; ?>
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
        }, 5000);
    });
</script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
?>