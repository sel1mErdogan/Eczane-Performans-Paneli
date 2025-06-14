<?php
// --- Hata Raporlama Ayarları (Geliştirme Ortamı İçin) ---
error_reporting(E_ALL); // Tüm PHP hatalarını gösterir. Geliştirme sırasında sorunları tespit etmek için kullanışlıdır.
ini_set('display_errors', 1); // Hataları doğrudan tarayıcıda gösterir. Geliştirme dışında kapatılmalıdır.

// --- Veritabanı Bağlantısı ---
include 'data2.php'; // Veritabanı bağlantı bilgilerini ve $conn mysqli bağlantı nesnesini içeren dosyayı dahil eder.

// --- Filtreleme, Sıralama ve Sayfalama İçin Kullanılacak Değişkenlerin Başlatılması ---
$filters = []; // Uygulanan tüm filtreleri (GET'ten gelenler ve sıralama bilgileri) tutacak dizi.
$where_clauses = []; // SQL sorgusunun WHERE bölümünü oluşturacak koşul ifadelerini (örn: "Age >= ?") tutacak dizi.
$params_for_where = []; // Hazırlanmış sorgular (prepared statements) için WHERE koşullarına karşılık gelen parametre değerlerini tutacak dizi.
$param_types_for_where = ""; // Hazırlanmış sorgular için WHERE parametrelerinin türlerini belirten string (örn: "isd" -> integer, string, double).

// --- Sayfalama Ayarları ---
$records_per_page = 15; // Her sayfada gösterilecek kayıt sayısı.
// Mevcut sayfa numarasını GET isteğinden alır. Eğer 'page' parametresi yoksa veya geçerli bir sayı değilse, varsayılan olarak 1. sayfa kabul edilir.
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
// SQL sorgusundaki LIMIT için başlangıç kaydını (offset) hesaplar.
// Örneğin, 1. sayfa için offset 0, 2. sayfa için 15 (eğer records_per_page 15 ise).
$offset = ($page - 1) * $records_per_page;

// --- Sıralama Ayarları ---
// Sıralama yapılabilecek geçerli veritabanı sütunlarının listesi.
$sortable_columns = ['Age', 'Pregnancies', 'Glucose', 'BloodPressure', 'SkinThickness', 'Insulin', 'BMI', 'DiabetesPedigreeFunction'];
// GET isteğinden 'sort_by' parametresini alarak hangi sütuna göre sıralama yapılacağını belirler.
// Eğer parametre yoksa veya geçersizse, varsayılan olarak 'Age' sütununa göre sıralama yapılır.
$sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $sortable_columns) ? $_GET['sort_by'] : 'Age';
// GET isteğinden 'sort_dir' parametresini alarak sıralama yönünü (artan 'asc' veya azalan 'desc') belirler.
// Eğer parametre yoksa veya geçersizse, varsayılan olarak azalan ('desc') yönde sıralama yapılır.
$sort_dir = isset($_GET['sort_dir']) && in_array(strtolower($_GET['sort_dir']), ['asc', 'desc']) ? strtolower($_GET['sort_dir']) : 'desc';

// Sıralama bilgilerini genel $filters dizisine ekler. Bu, sıralama linkleri oluşturulurken ve form gönderildiğinde mevcut sıralamanın korunmasına yardımcı olur.
$filters['sort_by'] = $sort_by;
$filters['sort_dir'] = $sort_dir;

// --- Filtreleri GET İsteğinden Alma ve İşleme ---
// Formdan gelebilecek filtre alanlarını ve beklenen veri tiplerini tanımlar.
$filter_fields = [
    'min_age' => 'int', 'max_age' => 'int', 'pregnancies' => 'int',
    'min_glucose' => 'float', 'max_glucose' => 'float',
    'min_bp' => 'int', 'max_bp' => 'int',
    'min_skin' => 'int', 'max_skin' => 'int',
    'min_insulin' => 'int', 'max_insulin' => 'int',
    'min_dpf' => 'float', 'max_dpf' => 'float',
    'bmi_category' => 'string'
];

// Tanımlanan her filtre alanı için döngüye girer.
foreach ($filter_fields as $field_name => $type) {
    // Eğer GET isteğinde bu alan varsa ve değeri boş değilse (boşluklar temizlendikten sonra)
    if (isset($_GET[$field_name]) && trim($_GET[$field_name]) !== '') {
        $value = trim($_GET[$field_name]); // Değeri alır ve başındaki/sonundaki boşlukları temizler.
        // Veri tipine göre değeri dönüştürür ve $filters dizisine atar.
        if ($type === 'int') {
            $filters[$field_name] = (int)$value;
        } elseif ($type === 'float') {
            // Ondalık sayılarda virgül yerine nokta kullanılmasını sağlar (Türkçe formatından SQL uyumlu formata).
            $filters[$field_name] = (float)str_replace(',', '.', $value);
        } else {
            $filters[$field_name] = (string)$value;
        }
    }
}

// --- SQL WHERE Kriterlerini Oluşturma (Hazırlanmış Sorgular İçin) ---
// Her bir olası filtre için, eğer filtre değeri $filters dizisinde mevcutsa:
// 1. Uygun SQL WHERE koşulunu (placeholder '?' ile) $where_clauses dizisine ekler.
// 2. Filtre değerini $params_for_where dizisine ekler.
// 3. Parametrenin tipini (i: integer, d: double/float, s: string) $param_types_for_where string'ine ekler.

if (isset($filters['min_age'])) { $where_clauses[] = "Age >= ?"; $params_for_where[] = $filters['min_age']; $param_types_for_where .= "i"; }
if (isset($filters['max_age'])) { $where_clauses[] = "Age <= ?"; $params_for_where[] = $filters['max_age']; $param_types_for_where .= "i"; }
if (isset($filters['pregnancies'])) { $where_clauses[] = "Pregnancies = ?"; $params_for_where[] = $filters['pregnancies']; $param_types_for_where .= "i"; }
if (isset($filters['min_glucose'])) { $where_clauses[] = "Glucose >= ?"; $params_for_where[] = $filters['min_glucose']; $param_types_for_where .= "d"; }
if (isset($filters['max_glucose'])) { $where_clauses[] = "Glucose <= ?"; $params_for_where[] = $filters['max_glucose']; $param_types_for_where .= "d"; }
if (isset($filters['min_bp'])) { $where_clauses[] = "BloodPressure >= ?"; $params_for_where[] = $filters['min_bp']; $param_types_for_where .= "i"; }
if (isset($filters['max_bp'])) { $where_clauses[] = "BloodPressure <= ?"; $params_for_where[] = $filters['max_bp']; $param_types_for_where .= "i"; }
if (isset($filters['min_skin'])) { $where_clauses[] = "SkinThickness >= ?"; $params_for_where[] = $filters['min_skin']; $param_types_for_where .= "i"; }
if (isset($filters['max_skin'])) { $where_clauses[] = "SkinThickness <= ?"; $params_for_where[] = $filters['max_skin']; $param_types_for_where .= "i"; }
if (isset($filters['min_insulin'])) { $where_clauses[] = "Insulin >= ?"; $params_for_where[] = $filters['min_insulin']; $param_types_for_where .= "i"; }
if (isset($filters['max_insulin'])) { $where_clauses[] = "Insulin <= ?"; $params_for_where[] = $filters['max_insulin']; $param_types_for_where .= "i"; }
if (isset($filters['min_dpf'])) { $where_clauses[] = "DiabetesPedigreeFunction >= ?"; $params_for_where[] = $filters['min_dpf']; $param_types_for_where .= "d"; }
if (isset($filters['max_dpf'])) { $where_clauses[] = "DiabetesPedigreeFunction <= ?"; $params_for_where[] = $filters['max_dpf']; $param_types_for_where .= "d"; }

// BMI kategorisi için özel filtreleme. Bu koşullar doğrudan SQL'e yazılır, parametre gerektirmez.
if (isset($filters['bmi_category']) && $filters['bmi_category'] !== '') {
    switch ($filters['bmi_category']) {
        case 'zayif': $where_clauses[] = "BMI < 18.5"; break;
        case 'normal': $where_clauses[] = "BMI >= 18.5 AND BMI < 25"; break;
        case 'fazla_kilolu': $where_clauses[] = "BMI >= 25 AND BMI < 30"; break;
        case 'obez': $where_clauses[] = "BMI >= 30"; break;
    }
}

// --- Toplam Kayıt Sayısını Hesaplama (Filtrelenmiş) ---
$sql_count_base = "SELECT COUNT(*) as total FROM diabetes_new"; // Toplam kayıt sayısını almak için temel SQL sorgusu.
$sql_count = $sql_count_base; // Sayım sorgusunu temel sorguyla başlatır.

// Eğer herhangi bir WHERE koşulu ($where_clauses) oluşturulmuşsa, bunları 'AND' ile birleştirerek sorguya ekler.
if (!empty($where_clauses)) {
    $sql_count .= " WHERE " . implode(" AND ", $where_clauses);
}

// Sayım sorgusunu veritabanında çalıştırmak için hazırlar (SQL injection'a karşı koruma sağlar).
$stmt_count = $conn->prepare($sql_count);
if ($stmt_count === false) { // Hazırlama başarısız olursa hatayı gösterir ve scripti durdurur.
    die("Hata (sayım sorgusu hazırlama): " . $conn->error."<br>SQL: ".$sql_count);
}

// Eğer WHERE koşulları için parametreler varsa (yani $param_types_for_where boş değilse),
// bu parametreleri hazırlanmış sorguya bağlar.
if (!empty($param_types_for_where)) {
    // '...' (splat operatörü) $params_for_where dizisindeki elemanları ayrı argümanlar olarak bind_param fonksiyonuna geçirir.
    $stmt_count->bind_param($param_types_for_where, ...$params_for_where);
}

$stmt_count->execute(); // Hazırlanmış sayım sorgusunu çalıştırır.
$result_count = $stmt_count->get_result(); // Sorgu sonucunu alır.
$total_records_row = $result_count->fetch_assoc(); // Sonuç satırını bir ilişkisel dizi olarak alır.
$total_records = $total_records_row ? $total_records_row['total'] : 0; // 'total' sütunundaki değeri (toplam kayıt sayısı) alır. Eğer sonuç yoksa 0 olarak ayarlar.
$stmt_count->close(); // Hazırlanmış sorguyu kapatır ve kaynakları serbest bırakır.

// Toplam sayfa sayısını, toplam kayıt sayısını sayfa başına kayıt sayısına bölerek hesaplar (yukarı yuvarlar).
$total_pages = ceil($total_records / $records_per_page);

// Sayfa numarası geçerliliğini kontrol eder ve düzeltir.
if ($page > $total_pages && $total_pages > 0) { // Eğer mevcut sayfa, toplam sayfa sayısından büyükse ve en az bir sayfa varsa,
    $page = $total_pages; // Mevcut sayfayı son sayfaya ayarlar.
    $offset = ($page - 1) * $records_per_page; // Offset'i yeniden hesaplar.
} elseif ($total_pages == 0 && $page > 1) { // Eğer hiç kayıt yoksa (toplam sayfa 0) ve mevcut sayfa 1'den büyükse,
    $page = 1; // Mevcut sayfayı 1'e ayarlar.
    $offset = 0; // Offset'i 0 yapar.
}


// --- Asıl Verileri Çekme (Filtrelenmiş, Sıralanmış, Sayfalanmış) ---
$sql_data_base = "SELECT * FROM diabetes_new"; // Verileri çekmek için temel SQL sorgusu.
$sql_data = $sql_data_base; // Veri sorgusunu temel sorguyla başlatır.

// Eğer WHERE koşulları oluşturulmuşsa, bunları 'AND' ile birleştirerek sorguya ekler.
if (!empty($where_clauses)) {
    $sql_data .= " WHERE " . implode(" AND ", $where_clauses);
}
// Sıralama koşulunu sorguya ekler. $sort_by ve $sort_dir değerleri kullanıcı girdisi olabileceğinden,
// SQL injection'a karşı $conn->real_escape_string ile güvenli hale getirilir (ancak ideal olanı placeholder kullanmaktır).
// ORDER BY için placeholder kullanımı doğrudan desteklenmez, bu yüzden sütun adları beyaz listeye alınır ($sortable_columns).
$sql_data .= " ORDER BY " . $conn->real_escape_string($sort_by) . " " . $conn->real_escape_string(strtoupper($sort_dir));
// Sayfalama için LIMIT ve OFFSET'i sorguya ekler (placeholder kullanarak).
$sql_data .= " LIMIT ?, ?";

// Veri sorgusu için parametreleri ve tiplerini hazırlar.
// Önce WHERE koşulları için olanları kopyalar.
$current_params_for_data = $params_for_where;
$current_param_types_for_data = $param_types_for_where;

// Sonra LIMIT ve OFFSET için parametreleri ve tiplerini ekler.
$current_params_for_data[] = $offset; // offset (integer)
$current_param_types_for_data .= "i";
$current_params_for_data[] = $records_per_page; // records_per_page (integer)
$current_param_types_for_data .= "i";

// Veri sorgusunu veritabanında çalıştırmak için hazırlar.
$stmt_data = $conn->prepare($sql_data);
if ($stmt_data === false) { // Hazırlama başağrısız olursa hatayı gösterir ve scripti durdurur.
    die("Hata (veri sorgusu hazırlama): " . $conn->error."<br>SQL: ".$sql_data);
}
// Eğer parametreler varsa (WHERE veya LIMIT/OFFSET için), bunları sorguya bağlar.
if (!empty($current_param_types_for_data)) {
    $stmt_data->bind_param($current_param_types_for_data, ...$current_params_for_data);
}

$stmt_data->execute(); // Hazırlanmış veri sorgusunu çalıştırır.
$result_data = $stmt_data->get_result(); // Sorgu sonucunu alır.
$patients = []; // Hastaların listesini tutacak boş bir dizi başlatır.

// Eğer sorgu sonuç döndürdüyse ve en az bir satır varsa,
if ($result_data && $result_data->num_rows > 0) {
    // Her bir sonuç satırını döngüyle alır ve $patients dizisine ekler.
    while ($row = $result_data->fetch_assoc()) {
        $patients[] = $row;
    }
}
$stmt_data->close(); // Hazırlanmış sorguyu kapatır.
$conn->close(); // Tüm veritabanı işlemleri bittiği için bağlantıyı kapatır.

// --- Yardımcı Fonksiyonlar ---

/**
 * Verilen BMI (Vücut Kitle İndeksi) değerine göre metinsel bir kategori etiketi döndürür.
 * @param mixed $bmi_value BMI değeri (string veya float olabilir, virgül veya nokta içerebilir).
 * @return string BMI kategorisi etiketi.
 */
function get_bmi_category_label($bmi_value) {
    if ($bmi_value === null || $bmi_value === '') return 'Bilinmiyor'; // Değer yoksa 'Bilinmiyor' döndür.
    // BMI değerini sayısal (float) bir değere dönüştürür, virgülü noktaya çevirir.
    $bmi_val_numeric = is_string($bmi_value) ? (float)str_replace(',', '.', $bmi_value) : (float)$bmi_value;

    if ($bmi_val_numeric < 18.5) return 'Zayıf';
    if ($bmi_val_numeric >= 18.5 && $bmi_val_numeric < 25) return 'Normal';
    if ($bmi_val_numeric >= 25 && $bmi_val_numeric < 30) return 'Fazla Kilolu';
    if ($bmi_val_numeric >= 30) return 'Obez';
    return 'Bilinmiyor'; // Hiçbir kategoriye uymuyorsa.
}

/**
 * Tablo başlıkları için sıralama linkleri oluşturur.
 * Tıklandığında sıralama yönünü tersine çevirir veya yeni bir sütuna göre sıralama başlatır.
 * @param string $column_name Veritabanı sütununun adı.
 * @param string $display_text Linkte görünecek metin.
 * @param string $current_sort_by Mevcut sıralanan sütun.
 * @param string $current_sort_dir Mevcut sıralama yönü ('asc' veya 'desc').
 * @param array $current_filters Mevcut tüm filtreleri içeren dizi (sayfalama ve diğer filtreler dahil).
 * @return string Oluşturulan HTML <a> etiketi.
 */
function get_sort_link($column_name, $display_text, $current_sort_by, $current_sort_dir, $current_filters) {
    $link_sort_dir = 'asc'; // Varsayılan olarak, bir sütuna ilk kez tıklandığında artan sırada sıralar.
    $icon = ' <i class="fas fa-sort text-muted" style="opacity:0.5;"></i>'; // Varsayılan, sırasız ikon.

    // Eğer tıklanan sütun zaten mevcut sıralanan sütunsa, sıralama yönünü tersine çevir.
    if ($column_name == $current_sort_by) {
        $link_sort_dir = ($current_sort_dir == 'asc') ? 'desc' : 'asc';
        // Mevcut sıralama yönüne göre uygun ikonu seç.
        $icon = ($current_sort_dir == 'asc') ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
    }

    // Link için query parametrelerini hazırlar. Mevcut filtreleri korur.
    $query_params = $current_filters;
    $query_params['sort_by'] = $column_name; // Yeni sıralama sütununu ayarlar.
    $query_params['sort_dir'] = $link_sort_dir; // Yeni sıralama yönünü ayarlar.

    // Not: Sıralama değiştiğinde kullanıcıyı genellikle ilk sayfaya yönlendirmek daha iyi bir kullanıcı deneyimi sunar.
    // $query_params['page'] = 1; // Bu satır aktif edilirse, sıralama linkine tıklandığında 1. sayfaya gidilir.
    // Şimdilik mevcut sayfada kalacak şekilde ayarlı.

    // HTML linkini oluşturur. http_build_query, query parametre dizisini URL uyumlu bir string'e dönüştürür.
    // htmlspecialchars, XSS saldırılarına karşı metni güvenli hale getirir.
    return '<a href="?' . http_build_query($query_params) . '">' . htmlspecialchars($display_text) . $icon . '</a>';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasta Kayıtları - Diyabet Paneli</title>
    <!-- Font ve İkon Kütüphaneleri -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE Teması CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">

    <style>
        /* Özel CSS Değişkenleri ve Stil Tanımlamaları */
        :root {
            --bg-main: #f4f7f9;
            --bg-card: #ffffff;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --border-color: #e0e6ed;
            --border-focus-color: #3498db;
            --shadow-color: rgba(44, 62, 80, 0.08);
            --accent-color: #17a2b8; /* Bilgi Mavisi */
            --accent-color-darker: #138496;
            --font-family-sans-serif: 'Inter', sans-serif;
            --accent-color-rgb: 23, 162, 184;
        }
        body { font-family: var(--font-family-sans-serif); background-color: var(--bg-main); color: var(--text-primary); -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .wrapper { background-color: var(--bg-main); }
        .main-header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); box-shadow: 0 1px 3px var(--shadow-color); }
        .main-header .navbar-nav .nav-link { color: var(--text-secondary); }
        .main-header .navbar-nav .nav-link:hover { color: var(--accent-color); }
        .navbar-brand { color: var(--text-primary) !important; font-weight: 600; }
        .navbar-brand .fa-list-alt { color: var(--accent-color); }
        .main-sidebar { background-color: #263238; box-shadow: 2px 0 5px var(--shadow-color); }
        .main-sidebar .brand-link { border-bottom: 1px solid rgba(255,255,255,0.05);text-decoration: none; }
        .main-sidebar .brand-text { color: #eceff1; }
        .main-sidebar .user-panel .info a { color: #cfd8dc; text-decoration: none;}
        .nav-sidebar .nav-item > .nav-link { color: #b0bec5; padding: .7rem 1rem; transition: background-color 0.2s ease, color 0.2s ease; }
        .nav-sidebar .nav-item > .nav-link.active { background-color: var(--accent-color); color: #fff; } /* Bu sayfanın accent rengiyle uyumlu */
        .nav-sidebar .nav-item > .nav-link:not(.active):hover { background-color: rgba(255,255,255,0.05); color: #ffffff; }
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
        .custom-data-card { background-color: var(--bg-card); border: none; border-radius: 10px; box-shadow: 0 6px 18px var(--shadow-color); margin-bottom: 25px; }
        .custom-data-card .card-header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; }
        .custom-data-card .card-title { font-size: 1.15rem; font-weight: 600; color: var(--text-primary); }
        .custom-data-card .card-title .fas { margin-right: 10px; color: var(--accent-color); }
        .custom-data-card .card-body { padding: 20px 25px; }
        .custom-data-card .card-footer { background-color: #fcfdff; border-top: 1px solid var(--border-color); padding: 15px 25px; }
        .filter-form .form-group { margin-bottom: 1rem; }
        .filter-form label { font-weight: 500; color: var(--text-secondary); margin-bottom: .3rem; font-size: 0.85rem; display: block; }
        .filter-form .form-control, .filter-form .form-control-sm { border-radius: 5px; border: 1px solid #ced4da; padding: .45rem .75rem; font-size: 0.875rem; color: var(--text-primary); background-color: #fff; transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out; }
        .filter-form .form-control-sm { height: auto; padding: .35rem .6rem; font-size: 0.82rem; }
        .filter-form .form-control:focus, .filter-form .form-control-sm:focus { border-color: var(--border-focus-color); box-shadow: 0 0 0 .2rem rgba(var(--accent-color-rgb), 0.25); background-color: #fff; }
        .filter-form .btn { font-size: 0.875rem; padding: .45rem 1rem; border-radius: 5px; }
        .filter-form .btn-primary { background-color: var(--accent-color); border-color: var(--accent-color); color: #fff; }
        .filter-form .btn-primary:hover { background-color: var(--accent-color-darker); border-color: var(--accent-color-darker); }
        .filter-form .btn-secondary { background-color: #6c757d; border-color: #6c757d; color: #fff; }
        .filter-form .btn-secondary:hover { background-color: #5a6268; border-color: #545b62; }
        .filter-form .filter-buttons .form-group { margin-bottom: 0; display: flex; align-items: flex-end; }
        .filter-form .filter-buttons .btn-block { width: 100%; }
        .table-modern-container { overflow-x: auto; } /* Tablonun yatayda kaydırılabilir olmasını sağlar */
        .table-modern { width: 100%; margin-bottom: 0; color: var(--text-primary); border-collapse: collapse; }
        .table-modern th, .table-modern td { padding: .8rem 1rem; vertical-align: middle; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; white-space: nowrap; } /* nowrap ile hücre içeriğinin alt satıra kaymasını engeller */
        .table-modern thead th { background-color: #f8f9fa; border-bottom-width: 2px; font-weight: 600; color: var(--text-primary); text-align: left; }
        .table-modern thead th a { color: var(--text-primary); text-decoration: none; transition: color 0.15s ease; }
        .table-modern thead th a:hover { color: var(--accent-color); }
        .table-modern thead th .fas { margin-left: .4rem; font-size: 0.8em; opacity: 0.8; }
        .table-modern tbody tr:nth-of-type(even) { background-color: #fdfdff; }
        .table-modern tbody tr:hover { background-color: #f1f5f9; }
        .pagination { margin-top: 1rem; }
        .pagination .page-item .page-link { color: var(--accent-color); border-radius: .3rem; margin: 0 .25rem; border: 1px solid var(--border-color); font-size: 0.9rem; padding: .45rem .85rem; transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease; }
        .pagination .page-item.active .page-link { background-color: var(--accent-color); border-color: var(--accent-color); color: #fff; box-shadow: 0 2px 4px rgba(var(--accent-color-rgb), 0.3); }
        .pagination .page-item.disabled .page-link { color: #adb5bd; background-color: var(--bg-card); border-color: var(--border-color); }
        .pagination .page-item .page-link:hover:not(.active) { background-color: #eef6fc; border-color: var(--border-focus-color); color: var(--accent-color-darker); }
        .pagination .page-item.active .page-link:hover { background-color: var(--accent-color-darker); border-color: var(--accent-color-darker); }
        .alert-warning { background-color: #fff8e1; border-color: #ffecb3; color: #8d6e63; border-radius: .5rem; }
        .alert-warning h5 .fas { margin-right: .5rem; }
        .main-footer { background-color: var(--bg-card); border-top: 1px solid var(--border-color); color: var(--text-secondary); padding: 1.3rem; font-size: 0.88rem; margin-top: 25px; text-align: center; }
        .main-footer a { color: var(--accent-color); font-weight: 500; text-decoration: none; }
        .main-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <!-- Üst Navigasyon Çubuğu -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
            </ul>
            <span class="navbar-brand mx-auto d-block text-center">
                <i class="fas fa-list-alt mr-2"></i> Hasta Kayıt Listesi
            </span>
            <ul class="navbar-nav">
                 <li class="nav-item"><a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a></li>
            </ul>
        </nav>

        <!-- Ana Yan Çubuk (Sidebar) -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
             <a href="diabets.php" class="brand-link"> <!-- Logo ve Panel Adı Linki -->
                <img src="dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Diyabet Paneli V2</span>
            </a>
            <div class="sidebar">
                <!-- Kullanıcı Paneli (Opsiyonel) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image"><img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image"></div>
                    <div class="info"><a href="#" class="d-block">Dr. Can Yılmaz</a></div>
                </div>
                <!-- Yan Çubuk Menüsü -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item"><a href="index.php" class="nav-link"><i class="nav-icon fas fa-chart-pie"></i><p>Genel İstatistikler</p></a></li>
                        <li class="nav-item"><a href="hasta_listesi.php " class="nav-link active"><i class="nav-icon fas fa-notes-medical"></i><p>Hasta Kayıtları </p></a></li>
                        <li class="nav-item"><a href="kayit_ekle.php" class="nav-link "><i class="nav-icon fas fa-user-plus"></i><p>Yeni Kayıt Ekle</p></a></li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- İçerik Sarmalayıcı. Sayfa içeriğini barındırır -->
        <div class="content-wrapper">
            <!-- İçerik Başlığı (Sayfa başlığı) -->
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

            <!-- Ana İçerik -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Filtreleme Formu Kartı -->
                    <div class="custom-data-card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter"></i> Kayıtları Filtrele & Sırala</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="hasta_listesi.php" class="filter-form">
                                <!-- Gizli inputlar, form gönderildiğinde mevcut sıralama bilgilerini korur -->
                                <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                                <input type="hidden" name="sort_dir" value="<?php echo htmlspecialchars($sort_dir); ?>">

                                <!-- Filtreleme Alanları -->
                                <div class="row">
                                    <!-- Min Yaş -->
                                    <div class="col-lg-2 col-md-4 col-sm-6"><div class="form-group"><label for="min_age">Min Yaş</label><input type="number" class="form-control form-control-sm" name="min_age" placeholder="Örn: 20" value="<?php echo htmlspecialchars($filters['min_age'] ?? ''); ?>"></div></div>
                                    <!-- Maks Yaş -->
                                    <div class="col-lg-2 col-md-4 col-sm-6"><div class="form-group"><label for="max_age">Maks Yaş</label><input type="number" class="form-control form-control-sm" name="max_age" placeholder="Örn: 50" value="<?php echo htmlspecialchars($filters['max_age'] ?? ''); ?>"></div></div>
                                    <!-- Gebelik Sayısı -->
                                    <div class="col-lg-2 col-md-4 col-sm-6"><div class="form-group"><label for="pregnancies">Gebelik Say.</label><input type="number" class="form-control form-control-sm" name="pregnancies" placeholder="Örn: 1" value="<?php echo htmlspecialchars($filters['pregnancies'] ?? ''); ?>"></div></div>
                                    <!-- Min Glikoz -->
                                    <div class="col-lg-3 col-md-6 col-sm-6"><div class="form-group"><label for="min_glucose">Min Glikoz</label><input type="number" step="any" class="form-control form-control-sm" name="min_glucose" placeholder="Örn: 70" value="<?php echo htmlspecialchars($filters['min_glucose'] ?? ''); ?>"></div></div>
                                    <!-- Maks Glikoz -->
                                    <div class="col-lg-3 col-md-6 col-sm-6"><div class="form-group"><label for="max_glucose">Maks Glikoz</label><input type="number" step="any" class="form-control form-control-sm" name="max_glucose" placeholder="Örn: 140" value="<?php echo htmlspecialchars($filters['max_glucose'] ?? ''); ?>"></div></div>
                                </div>
                                 <div class="row">
                                    <!-- Min Kan Basıncı -->
                                    <div class="col-lg-3 col-md-6 col-sm-6"><div class="form-group"><label for="min_bp">Min Kan Basıncı</label><input type="number" class="form-control form-control-sm" name="min_bp" placeholder="Örn: 60" value="<?php echo htmlspecialchars($filters['min_bp'] ?? ''); ?>"></div></div>
                                    <!-- Maks Kan Basıncı -->
                                    <div class="col-lg-3 col-md-6 col-sm-6"><div class="form-group"><label for="max_bp">Maks Kan Basıncı</label><input type="number" class="form-control form-control-sm" name="max_bp" placeholder="Örn: 90" value="<?php echo htmlspecialchars($filters['max_bp'] ?? ''); ?>"></div></div>
                                    <!-- Min Deri Kalınlığı -->
                                    <div class="col-lg-3 col-md-4 col-sm-6"><div class="form-group"><label for="min_skin">Min Deri Kalın.</label><input type="number" class="form-control form-control-sm" name="min_skin" placeholder="Örn: 10" value="<?php echo htmlspecialchars($filters['min_skin'] ?? ''); ?>"></div></div>
                                    <!-- Maks Deri Kalınlığı -->
                                    <div class="col-lg-3 col-md-4 col-sm-6"><div class="form-group"><label for="max_skin">Maks Deri Kalın.</label><input type="number" class="form-control form-control-sm" name="max_skin" placeholder="Örn: 35" value="<?php echo htmlspecialchars($filters['max_skin'] ?? ''); ?>"></div></div>
                                </div>
                                <div class="row">
                                    <!-- Min İnsülin -->
                                    <div class="col-lg-3 col-md-4 col-sm-6"><div class="form-group"><label for="min_insulin">Min İnsülin</label><input type="number" class="form-control form-control-sm" name="min_insulin" placeholder="Örn: 15" value="<?php echo htmlspecialchars($filters['min_insulin'] ?? ''); ?>"></div></div>
                                    <!-- Maks İnsülin -->
                                    <div class="col-lg-3 col-md-4 col-sm-6"><div class="form-group"><label for="max_insulin">Maks İnsülin</label><input type="number" class="form-control form-control-sm" name="max_insulin" placeholder="Örn: 200" value="<?php echo htmlspecialchars($filters['max_insulin'] ?? ''); ?>"></div></div>
                                    <!-- Min DPF (Diabetes Pedigree Function) -->
                                    <div class="col-lg-3 col-md-6 col-sm-6"><div class="form-group"><label for="min_dpf">Min DPF</label><input type="number" step="any" class="form-control form-control-sm" name="min_dpf" placeholder="Örn: 0.1" value="<?php echo htmlspecialchars($filters['min_dpf'] ?? ''); ?>"></div></div>
                                    <!-- Maks DPF -->
                                    <div class="col-lg-3 col-md-6 col-sm-6"><div class="form-group"><label for="max_dpf">Maks DPF</label><input type="number" step="any" class="form-control form-control-sm" name="max_dpf" placeholder="Örn: 1.5" value="<?php echo htmlspecialchars($filters['max_dpf'] ?? ''); ?>"></div></div>
                                </div>
                                <div class="row">
                                    <!-- BMI Kategorisi -->
                                    <div class="col-lg-3 col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label for="bmi_category">BMI Kategori</label>
                                            <select class="form-control form-control-sm" name="bmi_category">
                                                <option value="">Tümü</option>
                                                <option value="zayif" <?php echo (($filters['bmi_category'] ?? '') === 'zayif') ? 'selected' : ''; ?>>Zayıf (<18.5)</option>
                                                <option value="normal" <?php echo (($filters['bmi_category'] ?? '') === 'normal') ? 'selected' : ''; ?>>Normal (18.5-24.9)</option>
                                                <option value="fazla_kilolu" <?php echo (($filters['bmi_category'] ?? '') === 'fazla_kilolu') ? 'selected' : ''; ?>>Fazla Kilolu (25-29.9)</option>
                                                <option value="obez" <?php echo (($filters['bmi_category'] ?? '') === 'obez') ? 'selected' : ''; ?>>Obez (≥30)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Filtrele Butonu -->
                                    <div class="col-lg-3 col-md-3 col-sm-6 filter-buttons"><div class="form-group"><button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Filtrele</button></div></div>
                                    <!-- Temizle Butonu (Tüm filtreleri sıfırlar ve sayfayı yeniden yükler) -->
                                    <div class="col-lg-3 col-md-3 col-sm-6 filter-buttons"><div class="form-group"><a href="hasta_listesi.php" class="btn btn-secondary btn-block"><i class="fas fa-eraser"></i> Temizle</a></div></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Hasta Listesi Kartı -->
                    <div class="custom-data-card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clipboard-list"></i>Hasta Listesi (Toplam: <?php echo $total_records; // Filtrelenmiş toplam kayıt sayısını gösterir ?>)</h3>
                             <div class="card-tools">
                                <a href="kayit_ekle.php" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Yeni Kayıt Ekle</a>
                            </div>
                        </div>
                        <div class="card-body p-0"> <!-- p-0 padding'i sıfırlar, tablo kenarlara yapışır -->
                            <?php if (!empty($patients)): // Eğer hasta kaydı varsa tabloyu göster ?>
                                <div class="table-modern-container"> <!-- Tabloyu sarmalayan ve yatay kaydırma sağlayan div -->
                                    <table class="table table-modern table-hover">
                                        <thead>
                                            <tr>
                                                <!-- Tablo başlıkları. get_sort_link fonksiyonu ile sıralama linkleri oluşturulur -->
                                                <th><?php echo get_sort_link('Age', 'Yaş', $sort_by, $sort_dir, $filters); ?></th>
                                                <th><?php echo get_sort_link('Pregnancies', 'Gebelik', $sort_by, $sort_dir, $filters); ?></th>
                                                <th><?php echo get_sort_link('Glucose', 'Glikoz', $sort_by, $sort_dir, $filters); ?></th>
                                                <th><?php echo get_sort_link('BloodPressure', 'Kan Basıncı', $sort_by, $sort_dir, $filters); ?></th>
                                                <th><?php echo get_sort_link('SkinThickness', 'Deri Kalın.', $sort_by, $sort_dir, $filters); ?></th>
                                                <th><?php echo get_sort_link('Insulin', 'İnsülin', $sort_by, $sort_dir, $filters); ?></th>
                                                <th><?php echo get_sort_link('BMI', 'BMI', $sort_by, $sort_dir, $filters); ?></th>
                                                <th><?php echo get_sort_link('DiabetesPedigreeFunction', 'DPF', $sort_by, $sort_dir, $filters); ?></th>
                                                <th>BMI Kat.</th> <!-- BMI Kategorisi sıralanamaz, bu yüzden statik başlık -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($patients as $patient): // Her bir hasta kaydı için döngü ?>
                                                <tr>
                                                    <!-- Hasta verileri htmlspecialchars ile XSS'e karşı güvenli hale getirilerek yazdırılır -->
                                                    <td><?php echo htmlspecialchars($patient['Age']); ?></td>
                                                    <td><?php echo htmlspecialchars($patient['Pregnancies']); ?></td>
                                                    <td><?php echo htmlspecialchars($patient['Glucose']); ?></td>
                                                    <td><?php echo htmlspecialchars($patient['BloodPressure']); ?></td>
                                                    <td><?php echo htmlspecialchars($patient['SkinThickness']); ?></td>
                                                    <td><?php echo htmlspecialchars($patient['Insulin']); ?></td>
                                                    <!-- BMI ve DPF değerleri float'a çevrilip, yuvarlanıp, sonra stringe çevrilerek gösterilir -->
                                                    <td><?php echo htmlspecialchars(round((float)str_replace(',', '.', $patient['BMI']), 1)); ?></td>
                                                    <td><?php echo htmlspecialchars(round((float)str_replace(',', '.', $patient['DiabetesPedigreeFunction']), 3)); ?></td>
                                                    <td><?php echo htmlspecialchars(get_bmi_category_label($patient['BMI'])); // BMI kategorisi yardımcı fonksiyonla alınır ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: // Eğer hiç hasta kaydı bulunamadıysa uyarı mesajı gösterilir ?>
                                <div class="alert alert-warning m-3 text-center">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Kayıt Bulunamadı</h5>
                                    Belirtilen filtre kriterlerine uygun hasta kaydı bulunmamaktadır. Filtrelerinizi kontrol edin veya temizleyin.
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($total_pages > 1): // Eğer birden fazla sayfa varsa sayfalama linklerini göster ?>
                        <div class="card-footer clearfix">
                            <nav aria-label="Hasta Kayıtları Sayfalaması">
                                <ul class="pagination pagination-sm m-0 float-right">
                                    <?php
                                        // Sayfalama linkleri için $filters (mevcut filtre ve sıralamayı içeren) kullanılacak.
                                        // Bu sayede sayfa değiştirildiğinde mevcut filtreler ve sıralama korunur.
                                        $pagination_filters = $filters;
                                    ?>
                                    <!-- İlk Sayfa ve Önceki Sayfa Linkleri -->
                                    <?php if ($page > 1): // Eğer mevcut sayfa 1'den büyükse aktif linkler ?>
                                        <li class="page-item"><a class="page-link" href="?page=1&<?php echo http_build_query($pagination_filters); ?>" aria-label="İlk Sayfa">««</a></li>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($pagination_filters); ?>" aria-label="Önceki">«</a></li>
                                    <?php else: // Değilse pasif (disabled) linkler ?>
                                        <li class="page-item disabled"><span class="page-link" aria-hidden="true">««</span></li>
                                        <li class="page-item disabled"><span class="page-link" aria-hidden="true">«</span></li>
                                    <?php endif; ?>

                                    <?php
                                    // Sayfa Numarası Linkleri (Ortada gösterilecek belirli sayıda sayfa)
                                    $visible_pages = 5; // Ortada gösterilecek sayfa sayısı (genellikle tek sayı tercih edilir).
                                    // Başlangıç ve bitiş sayfalarını hesaplar, böylece mevcut sayfa ortada kalır.
                                    $start_page = max(1, $page - floor($visible_pages / 2));
                                    $end_page = min($total_pages, $start_page + $visible_pages - 1);
                                    
                                    // Eğer $end_page $total_pages'a ulaştıysa ve hala yeterli $visible_pages gösterilmiyorsa $start_page'i sola doğru ayarla.
                                    if ($end_page == $total_pages && ($end_page - $start_page + 1) < $visible_pages) {
                                        $start_page = max(1, $end_page - $visible_pages + 1);
                                    }
                                    // Eğer $start_page 1 ise ve hala yeterli $visible_pages gösterilmiyorsa $end_page'i sağa doğru ayarla.
                                    if ($start_page == 1 && ($end_page - $start_page + 1) < $visible_pages) {
                                        $end_page = min($total_pages, $start_page + $visible_pages - 1);
                                    }

                                    // Eğer başlangıç sayfası 1'den büyükse, "1" ve "..." linklerini göster.
                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1&' . http_build_query($pagination_filters) . '">1</a></li>';
                                        if ($start_page > 2) { // Eğer arada birden fazla sayfa varsa "..." göster.
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    // Belirlenen aralıktaki sayfa numaralarını listeler.
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; // Mevcut sayfa ise 'active' class'ı ekle ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($pagination_filters); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor;

                                    // Eğer bitiş sayfası toplam sayfa sayısından küçükse, "..." ve son sayfa linkini göster.
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) { // Eğer arada birden fazla sayfa varsa "..." göster.
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&' . http_build_query($pagination_filters) . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>

                                    <!-- Sonraki Sayfa ve Son Sayfa Linkleri -->
                                    <?php if ($page < $total_pages): // Eğer mevcut sayfa son sayfadan küçükse aktif linkler ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($pagination_filters); ?>" aria-label="Sonraki">»</a></li>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?>&<?php echo http_build_query($pagination_filters); ?>" aria-label="Son Sayfa">»»</a></li>
                                    <?php else: // Değilse pasif (disabled) linkler ?>
                                        <li class="page-item disabled"><span class="page-link" aria-hidden="true">»</span></li>
                                        <li class="page-item disabled"><span class="page-link" aria-hidden="true">»»</span></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Altbilgi (Footer) -->
        <footer class="main-footer">
            <strong>Telif Hakkı © <?php echo date("Y"); ?> <a href="#">Sağlık Veri Analitiği A.Ş.</a></strong> Tüm hakları saklıdır.
             <br> Fuay Hastanesi Katkılarıyla
        </footer>
    </div>
<!-- Gerekli JavaScript Dosyaları -->
<script src="plugins/jquery/jquery.min.js"></script> <!-- jQuery Kütüphanesi -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap JS (Popper.js içerir) -->
<script src="dist/js/adminlte.min.js"></script> <!-- AdminLTE Tema JS -->
</body>
</html>