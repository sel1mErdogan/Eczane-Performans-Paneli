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
function set_form_message($message, $type = 'danger')
{
    $_SESSION['form_message'] = $message;
    $_SESSION['form_message_type'] = $type;
}

function display_form_message()
{
    if (isset($_SESSION['form_message']) && !empty($_SESSION['form_message'])) {
        echo '<div class="alert alert-' . htmlspecialchars($_SESSION['form_message_type'] ?? 'danger') . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['form_message'];
        echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>';
        echo '</div>';
        unset($_SESSION['form_message'], $_SESSION['form_message_type']);
    }
}

// --- FORM VERİLERİ ---
$form_data_add = ['doktor_ad_soyad' => '', 'diploma_no' => '', 'uzmanlik' => ''];
$doktorToEdit = null;
$show_edit_form = false;

// --- İŞLEM YÖNETİMİ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_doktor') {
        $form_data_add['doktor_ad_soyad'] = trim($_POST['doktor_ad_soyad'] ?? '');
        $form_data_add['diploma_no'] = trim($_POST['diploma_no'] ?? '');
        $form_data_add['uzmanlik'] = trim($_POST['uzmanlik'] ?? '');

        if (empty($form_data_add['doktor_ad_soyad']) || empty($form_data_add['diploma_no']) || empty($form_data_add['uzmanlik'])) {
            set_form_message("Tüm alanların doldurulması zorunludur.", 'warning');
        } elseif (!is_numeric($form_data_add['diploma_no'])) {
            set_form_message("Diploma numarası sadece rakamlardan oluşmalıdır.", 'warning');
        } else {
            try {
                // YENİ: Saklı yordam çağrısı
                $stmt = $conn->prepare("CALL sp_DoktorEkle(?, ?, ?)");
                $stmt->bind_param("sss", $form_data_add['doktor_ad_soyad'], $form_data_add['diploma_no'], $form_data_add['uzmanlik']);
                if ($stmt->execute()) {
                    set_form_message("Doktor başarıyla eklendi.", 'success');
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062)
                    $user_message = "Bu Diploma Numarası zaten kayıtlı.";
                else
                    $user_message = "Doktor eklenirken bir hata oluştu.";
                set_form_message($user_message . "<hr>Teknik Detay: " . htmlspecialchars($e->getMessage()), 'danger');
            }
        }
        header("Location: doktor_yonetimi.php");
        exit;
    }

    if ($_POST['action'] == 'edit_doktor') {
        $doktor_id = (int) ($_POST['doktor_id'] ?? 0);
        $doktor_ad_soyad = trim($_POST['doktor_ad_soyad'] ?? '');
        $diploma_no = trim($_POST['diploma_no'] ?? '');
        $uzmanlik = trim($_POST['uzmanlik'] ?? '');

        if ($doktor_id <= 0 || empty($doktor_ad_soyad) || empty($diploma_no) || empty($uzmanlik)) {
            set_form_message("Tüm alanların doldurulması zorunludur.", 'warning');
        } elseif (!is_numeric($diploma_no)) {
            set_form_message("Diploma numarası sadece rakamlardan oluşmalıdır.", 'warning');
        } else {
            try {
                // YENİ: Saklı yordam çağrısı
                $stmt = $conn->prepare("CALL sp_DoktorGuncelle(?, ?, ?, ?)");
                $stmt->bind_param("sssi", $doktor_ad_soyad, $diploma_no, $uzmanlik, $doktor_id);
                if ($stmt->execute()) {
                    set_form_message("Doktor başarıyla güncellendi.", 'success');
                    header("Location: doktor_yonetimi.php");
                    exit;
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062)
                    set_form_message("Bu Diploma Numarası başka bir doktora ait.", 'danger');
                else
                    set_form_message("Doktor güncellenirken bir hata oluştu.", 'danger');
            }
        }
        header("Location: doktor_yonetimi.php?action=edit_doktor_form&id=" . $doktor_id);
        exit;
    }
}

// GET İSTEKLERİ
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id_param = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($action == 'delete_doktor' && $id_param > 0) {
        try {
            // YENİ YÖNTEM: Sadece doktoru silen basit yordamı çağırıyor.
            // Reçeteleri silme işini arka planda trigger hallediyor.
            $stmt = $conn->prepare("CALL sp_DoktorSil(?)");
            $stmt->bind_param("i", $id_param);
            $stmt->execute();

            // Kullanıcıya gösterilecek mesajı trigger'ın işini de yansıtacak şekilde yazabiliriz.
            set_form_message("Doktor kaydı başarıyla silindi. (İlişkili reçeteler de otomatik olarak silindi).", 'info');
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            set_form_message("Doktor silinirken bir veritabanı hatası oluştu.<hr>Teknik Detay: " . htmlspecialchars($e->getMessage()), 'danger');
        }
        header("Location: doktor_yonetimi.php");
        exit;
    } elseif ($action == 'edit_doktor_form' && $id_param > 0) {
        try {
            // YENİ: Saklı yordam çağrısı
            $stmt = $conn->prepare("CALL sp_GetDoktorById(?)");
            $stmt->bind_param("i", $id_param);
            $stmt->execute();
            $result = $stmt->get_result();
            $doktorToEdit = $result->fetch_assoc();
            $show_edit_form = $doktorToEdit ? true : false;
            if (!$show_edit_form)
                set_form_message("Düzenlenecek doktor bulunamadı.", 'warning');
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            set_form_message("Doktor verileri çekilirken hata: " . htmlspecialchars($e->getMessage()), 'danger');
        }
    }
}

// --- MEVCUT DOKTORLARI LİSTELEME ---
$doktorlar_listesi = [];
try {
    // YENİ: Saklı yordam çağrısı
    $result_doktorlar = $conn->query("CALL sp_ListDoktorlar()");
    if ($result_doktorlar) {
        $doktorlar_listesi = $result_doktorlar->fetch_all(MYSQLI_ASSOC);
        $result_doktorlar->close();
    }
} catch (mysqli_sql_exception $e) {
    set_form_message("Doktor listesi yüklenirken bir hata oluştu: " . htmlspecialchars($e->getMessage()), "danger");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doktor Yönetimi - PharmAnalytics Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        :root {
            --accent-color: #c82333;
            /* Doktor için accent rengi (teal) */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
            color: #2c3e50;
        }

        .main-header {
            background-color: #ffffff;
        }

        .main-sidebar {
            background-color: var(--accent-color);
        }

        .content-wrapper {
            padding: 25px;
        }

        .form-control-sm {
            height: calc(1.8125rem + 2px);
        }

        .btn-xs {
            padding: .125rem .25rem;
            font-size: .75rem;
            line-height: 1.5;
            border-radius: .15rem;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a></li>
            </ul>
            <span class="navbar-brand-custom mx-auto d-block text-center h5 mb-0">Doktor Yönetimi</span>
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
                        <div class="col-sm-6">
                            <h1 class="m-0">Doktor Yönetim Paneli</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <?php display_form_message(); ?>

                    <!-- Yeni Doktor Ekleme / Düzenleme Formu -->
                    <?php if ($show_edit_form && $doktorToEdit): ?>
                        <div class="card card-warning card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-edit"></i> Doktor Düzenle (ID:
                                    <?php echo htmlspecialchars($doktorToEdit['doktor_id']); ?>)</h3>
                            </div>
                            <form method="POST" action="doktor_yonetimi.php">
                                <input type="hidden" name="action" value="edit_doktor">
                                <input type="hidden" name="doktor_id"
                                    value="<?php echo htmlspecialchars($doktorToEdit['doktor_id']); ?>">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="doktor_ad_soyad_edit">Adı Soyadı (*)</label>
                                        <input type="text" class="form-control form-control-sm" id="doktor_ad_soyad_edit"
                                            name="doktor_ad_soyad"
                                            value="<?php echo htmlspecialchars($doktorToEdit['doktor_ad_soyad']); ?>"
                                            required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label for="diploma_no_edit">Diploma No (*)</label>
                                            <input type="text" class="form-control form-control-sm" id="diploma_no_edit"
                                                name="diploma_no"
                                                value="<?php echo htmlspecialchars($doktorToEdit['diploma_no']); ?>"
                                                required pattern="\d+">
                                            <small class="form-text text-muted">Sadece rakam giriniz.</small>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label for="uzmanlik_edit">Uzmanlık Alanı (*)</label>
                                            <input type="text" class="form-control form-control-sm" id="uzmanlik_edit"
                                                name="uzmanlik"
                                                value="<?php echo htmlspecialchars($doktorToEdit['uzmanlik']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <a href="doktor_yonetimi.php" class="btn btn-sm btn-secondary"><i
                                            class="fas fa-times mr-1"></i> İptal</a>
                                    <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-save mr-1"></i>
                                        Doktoru Güncelle</button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-plus"></i> Yeni Doktor Ekle</h3>
                            </div>
                            <form method="POST" action="doktor_yonetimi.php">
                                <input type="hidden" name="action" value="add_doktor">
                                <div class="card-body">
                                    <?php
                                    // EĞER doktor_id AUTO_INCREMENT DEĞİLSE, aşağıdaki inputu aktif edin:
                                    /*
                                    <div class="form-group">
                                        <label for="doktor_id_add">Doktor ID (*)</label>
                                        <input type="number" class="form-control form-control-sm" id="doktor_id_add" name="doktor_id" value="<?php echo htmlspecialchars($form_data_add['doktor_id'] ?? ''); ?>" required>
                                    </div>
                                    */
                                    ?>
                                    <div class="form-group">
                                        <label for="doktor_ad_soyad">Adı Soyadı (*)</label>
                                        <input type="text" class="form-control form-control-sm" id="doktor_ad_soyad"
                                            name="doktor_ad_soyad"
                                            value="<?php echo htmlspecialchars($form_data_add['doktor_ad_soyad']); ?>"
                                            required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label for="diploma_no">Diploma No (*)</label>
                                            <input type="text" class="form-control form-control-sm" id="diploma_no"
                                                name="diploma_no"
                                                value="<?php echo htmlspecialchars($form_data_add['diploma_no']); ?>"
                                                required pattern="\d+">
                                            <small class="form-text text-muted">Sadece rakam giriniz.</small>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label for="uzmanlik">Uzmanlık Alanı (*)</label>
                                            <input type="text" class="form-control form-control-sm" id="uzmanlik"
                                                name="uzmanlik"
                                                value="<?php echo htmlspecialchars($form_data_add['uzmanlik']); ?>"
                                                required>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i>
                                        Yeni Doktor Ekle</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Mevcut Doktorlar Tablosu -->
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list-ul"></i> Kayıtlı Doktorlar</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover table-sm table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Adı Soyadı</th>
                                        <th>Diploma No</th>
                                        <th>Uzmanlık Alanı</th>
                                        <th class="text-center" style="width: 100px;">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($doktorlar_listesi)): ?>
                                        <?php foreach ($doktorlar_listesi as $doktor): ?>
                                            <tr>
                                                <td><?php echo $doktor['doktor_id']; ?></td>
                                                <td><?php echo htmlspecialchars($doktor['doktor_ad_soyad']); ?></td>
                                                <td><?php echo htmlspecialchars($doktor['diploma_no']); ?></td>
                                                <td><?php echo htmlspecialchars($doktor['uzmanlik']); ?></td>
                                                <td class="text-center">
                                                    <a href="doktor_yonetimi.php?action=edit_doktor_form&id=<?php echo $doktor['doktor_id']; ?>"
                                                        class="btn btn-info btn-xs" title="Düzenle"><i
                                                            class="fas fa-edit"></i></a>
                                                    <a href="doktor_yonetimi.php?action=delete_doktor&id=<?php echo $doktor['doktor_id']; ?>"
                                                        class="btn btn-danger btn-xs" title="Sil"
                                                        onclick="return confirm('\'<?php echo htmlspecialchars(addslashes($doktor['doktor_ad_soyad'])); ?>\' adlı doktoru silmek istediğinizden emin misiniz?\n\nUYARI: Bu doktorun yazdığı TÜM REÇETELER de kalıcı olarak silinecektir! Bu işlem geri alınamaz.');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">Kayıtlı doktor bulunmamaktadır.</td>
                                        </tr>
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
            window.setTimeout(function () {
                $(".alert-dismissible.show").fadeTo(500, 0).slideUp(500, function () {
                    $(this).removeClass('show').addClass('d-none');
                });
            }, 7000);
        });
    </script>
</body>

</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>