<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

include 'data3.php'; // Veritabanı bağlantı dosyanız

// --- Veri Toplama Dizileri ---
$kpi_data = [];
$chart_data = [];
$table_data = [];

// --- KPI Verileri (Saklı Yordamlar ile) ---
$result = $conn->query("CALL sp_GetTotalPharmacies()");
$kpi_data['total_pharmacies'] = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
$result->close(); $conn->next_result(); // Sonraki sorgu için bağlantıyı temizle

$result = $conn->query("CALL sp_GetTotalPatients()");
$kpi_data['total_patients'] = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetTotalDrugTypes()");
$kpi_data['total_drug_types'] = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetAverageStock()");
$kpi_data['avg_stock'] = $result ? $result->fetch_assoc()['avg_val'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetTotalPrescriptions()");
$kpi_data['total_prescriptions'] = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetTotalDoctors()");
$kpi_data['total_doctors'] = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetAverageDrugPrice()");
$kpi_data['avg_drug_price'] = $result ? $result->fetch_assoc()['avg_val'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetAverageItemsPerPrescription()");
$kpi_data['avg_items_per_prescription'] = $result ? $result->fetch_assoc()['avg_val'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetOutOfStockDrugsCount()");
$kpi_data['out_of_stock_drugs'] = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetRevenueLast30Days()");
$kpi_data['revenue_last_30_days'] = $result ? $result->fetch_assoc()['total_revenue'] ?? 0 : 0;
$result->close(); $conn->next_result();

$result = $conn->query("CALL sp_GetActivePersonnelCount()");
$kpi_data['active_personnel'] = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;
$result->close(); $conn->next_result();


// --- Grafik Verileri (Saklı Yordamlar ile) ---

// 1. Üreticiye Göre İlaç Sayısı (Pasta Grafik)
$result = $conn->query("CALL sp_GetDrugsPerManufacturer()");
$chart_data['manufacturer_labels'] = []; $chart_data['manufacturer_drug_counts'] = [];
if ($result) while ($row = $result->fetch_assoc()) {
    $chart_data['manufacturer_labels'][] = $row['uretici_ad'];
    $chart_data['manufacturer_drug_counts'][] = (int)$row['drug_count'];
}
$result->close(); $conn->next_result();

// 2. Hasta Yaş Dağılımı (Çubuk Grafik)
$result = $conn->query("CALL sp_GetPatientAgeDistribution()");
$chart_data['patient_age_labels'] = []; $chart_data['patient_age_counts'] = [];
if ($result) while ($row = $result->fetch_assoc()) {
    $chart_data['patient_age_labels'][] = $row['age_group'];
    $chart_data['patient_age_counts'][] = (int)$row['count'];
}
$result->close(); $conn->next_result();

// 3. İlaç Stok Seviye Kategorileri (Doughnut Grafik)
$result = $conn->query("CALL sp_GetDrugStockCategories()");
$chart_data['stock_category_labels'] = []; $chart_data['stock_category_counts'] = [];
if ($result) while ($row = $result->fetch_assoc()) {
    $chart_data['stock_category_labels'][] = $row['stock_category'];
    $chart_data['stock_category_counts'][] = (int)$row['count'];
}
$result->close(); $conn->next_result();

// 4. Hasta Cinsiyet Dağılımı (Pasta Grafik)
$result = $conn->query("CALL sp_GetPatientGenderDistribution()");
$chart_data['patient_gender_labels'] = []; $chart_data['patient_gender_counts'] = [];
if ($result) while ($row = $result->fetch_assoc()) {
    $chart_data['patient_gender_labels'][] = $row['gender'];
    $chart_data['patient_gender_counts'][] = (int)$row['count'];
}
$result->close(); $conn->next_result();

// 5. En Çok Ciro Getiren İlaçlar (Top 5) (Yatay Çubuk Grafik)
$result = $conn->query("CALL sp_GetTopDrugsByRevenue()");
$chart_data['top_drug_value_labels'] = []; $chart_data['top_drug_value_counts'] = [];
if ($result) while ($row = $result->fetch_assoc()) {
    $chart_data['top_drug_value_labels'][] = $row['ilac_ad'];
    $chart_data['top_drug_value_counts'][] = (float)$row['total_value'];
}
$result->close(); $conn->next_result();

// 6. Son 30 Günlük Reçete Sayısı Trendi (Çizgi Grafik için veri)
$result = $conn->query("CALL sp_GetPrescriptionTrendLast30Days()");
$temp_pres_labels = []; $temp_pres_counts_map = [];
for ($i = 29; $i >= 0; $i--) {
    $date_key = date('Y-m-d', strtotime("-$i days"));
    $temp_pres_labels[] = date('d M', strtotime("-$i days"));
    $temp_pres_counts_map[$date_key] = 0;
}
if ($result) { while ($row = $result->fetch_assoc()) { if (array_key_exists($row['prescription_date'], $temp_pres_counts_map)) { $temp_pres_counts_map[$row['prescription_date']] = (int)$row['count']; } } $result->close(); }
$conn->next_result();
$chart_data['prescription_30day_labels'] = $temp_pres_labels;
$chart_data['prescription_30day_counts'] = array_values($temp_pres_counts_map);

// 7. Üretici Anlaşma Durumları (Doughnut Grafik)
$result = $conn->query("CALL sp_GetAgreementStatus()");
$chart_data['agreement_status_labels'] = []; $chart_data['agreement_status_counts'] = [];
if ($result) while ($row = $result->fetch_assoc()) {
    $chart_data['agreement_status_labels'][] = $row['status'];
    $chart_data['agreement_status_counts'][] = (int)$row['count'];
}
$result->close(); $conn->next_result();

// 8. Doktor Uzmanlık Alanına Göre Reçete Yoğunluğu (Çubuk Grafik)
$result = $conn->query("CALL sp_GetPrescriptionsBySpecialization()");
$chart_data['doc_spec_labels'] = []; $chart_data['doc_spec_counts'] = [];
if ($result) while ($row = $result->fetch_assoc()) {
    $chart_data['doc_spec_labels'][] = $row['uzmanlik'];
    $chart_data['doc_spec_counts'][] = (int)$row['prescription_count'];
}
$result->close(); $conn->next_result();

// 9. Son 30 Günlük Ciro Trendi (Çizgi Grafik için veri)
$result = $conn->query("CALL sp_GetRevenueTrendLast30Days()");
$temp_rev_labels = []; $temp_rev_counts_map = [];
for ($i = 29; $i >= 0; $i--) {
    $date_key = date('Y-m-d', strtotime("-$i days"));
    $temp_rev_labels[] = date('d M', strtotime("-$i days"));
    $temp_rev_counts_map[$date_key] = 0;
}
if ($result) { while ($row = $result->fetch_assoc()) { if (array_key_exists($row['sale_date'], $temp_rev_counts_map)) { $temp_rev_counts_map[$row['sale_date']] = (float)$row['daily_revenue']; } } $result->close(); }
$conn->next_result();
$chart_data['revenue_30day_labels'] = $temp_rev_labels;
$chart_data['revenue_30day_counts'] = array_values($temp_rev_counts_map);

// 10. En Karlı İlaçlar (Top 5) (Yatay Çubuk Grafik)
$result = $conn->query("CALL sp_GetTopProfitableDrugs()");
$chart_data['top_profit_drug_labels'] = []; $chart_data['top_profit_drug_values'] = [];
if($result) { while($row = $result->fetch_assoc()){ $chart_data['top_profit_drug_labels'][] = $row['ilac_ad']; $chart_data['top_profit_drug_values'][] = (float)$row['total_profit']; } $result->close(); }
$conn->next_result();

// --- Tablo Verileri (Saklı Yordamlar ile) ---

// Kritik Stoklar (PARAMETRELİ SAKLI YORDAM ÇAĞRISI)
$low_stock_threshold = 20;
$stmt = $conn->prepare("CALL sp_GetLowStockDrugs(?)"); // Sorguyu hazırla
$stmt->bind_param("i", $low_stock_threshold); // Parametreyi bağla ('i' integer demek)
$stmt->execute(); // Çalıştır
$result = $stmt->get_result(); // Sonuçları al
$table_data['low_stock_drugs'] = [];
if ($result) while ($row = $result->fetch_assoc()) $table_data['low_stock_drugs'][] = $row;
$stmt->close(); // Hazırlanmış ifadeyi kapat

// Son Reçeteler
$result = $conn->query("CALL sp_GetRecentPrescriptions()");
$table_data['recent_prescriptions'] = [];
if ($result) { while ($row = $result->fetch_assoc()) $table_data['recent_prescriptions'][] = $row; $result->close(); }
$conn->next_result();

// En Değerli Hastalar
$result = $conn->query("CALL sp_GetTopValuablePatients()");
$table_data['top_valuable_patients'] = [];
if ($result) { while ($row = $result->fetch_assoc()) $table_data['top_valuable_patients'][] = $row; $result->close(); }
$conn->next_result();

// Yaklaşan Anlaşma Bitişleri
$result = $conn->query("CALL sp_GetExpiringAgreements()");
$table_data['expiring_agreements'] = [];
if($result) { while($row = $result->fetch_assoc()) $table_data['expiring_agreements'][] = $row; $result->close(); }
// $conn->next_result(); // Son sorgu olduğu için gerekli değil.

$conn->close();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kapsamlı Eczane Analiz ve Yönetim Paneli</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome (İkonlar için) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE Teması CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- AOS (Animasyon Kütüphanesi) CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Gerekli JavaScript Kütüphaneleri -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js (Grafik Kütüphanesi) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- Chart.js Veri Etiketleri Eklentisi -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
     <!-- Chart.js Zaman Ölçeği Adaptörü -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/date-fns/1.30.1/date_fns.min.js"></script>
    <!-- AdminLTE Teması JavaScript -->
    <script src="dist/js/adminlte.min.js"></script>

    <style>
        /* --- CSS Değişkenleri (Temanın renk ve font ayarları) --- */
        :root {
            --bg-main: #f4f7f9; /* Ana arka plan rengi */
            --bg-card: #ffffff; /* Kartların arka plan rengi */
            --text-primary: #2c3e50; /* Ana metin rengi */
            --text-secondary: #7f8c8d; /* İkincil metin rengi */
            --border-color: #e0e6ed; /* Kenarlık rengi */
            --shadow-color: rgba(44, 62, 80, 0.08); /* Gölgeler için renk */
            --accent-color: #c82333; /* Vurgu rengi (AdminLTE Kırmızı tonu) */
            --accent-color-darker: #a01c28; /* Vurgu renginin koyu tonu */
            --font-family-sans-serif: 'Inter', sans-serif; /* Ana yazı tipi */

            /* Grafik Renkleri (AdminLTE renklerine yakın tonlar) */
            --chart-color-red: #dc3545;    /* danger */
            --chart-color-green: #28a745;  /* success */
            --chart-color-blue: #007bff;   /* primary */
            --chart-color-yellow: #ffc107; /* warning */
            --chart-color-info: #17a2b8;   /* info */
            --chart-color-purple: #6f42c1; /* purple */
            --chart-color-teal: #20c997;   /* teal */
            --chart-color-orange: #fd7e14; /* orange */
            --chart-color-maroon: #d81b60; /* maroon */
            --chart-color-indigo: #6610f2; /* indigo */
            --chart-color-pink: #e83e8c;   /* pink */
            --chart-color-olive: #3d9970;  /* olive */
            --chart-color-fuchsia: #f012be;/* fuchsia */
            --chart-color-dark: #343a40;   /* dark */
        }

        /* --- Genel Sayfa Stilleri --- */
        body {
            font-family: var(--font-family-sans-serif);
            background-color: var(--bg-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .wrapper { background-color: var(--bg-main); }

        /* --- Üst Navigasyon Çubuğu (Header) --- */
        .main-header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); box-shadow: 0 1px 3px var(--shadow-color); }
        .main-header .navbar-nav .nav-link { color: var(--text-secondary); }
        .main-header .navbar-nav .nav-link:hover { color: var(--accent-color); }
        .navbar-brand-custom { color: var(--text-primary) !important; font-weight: 600; }
        .navbar-brand-custom .fa-laptop-medical { color: var(--accent-color); }

        /* --- Sol Kenar Çubuğu (Sidebar) --- */
        .main-sidebar { background-color: var(--accent-color); /* Ana vurgu rengi */ box-shadow: 2px 0 5px var(--shadow-color); } /* AdminLTE sidebar-dark-danger gibi */
        .main-sidebar .brand-link { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .main-sidebar .brand-text { color: #fff; }
        .main-sidebar .user-panel .info a { color: #f8f9fa; }
        .nav-sidebar .nav-item > .nav-link { color: #e9ecef; padding: .7rem 1rem; transition: background-color 0.2s ease, color 0.2s ease; }
        .nav-sidebar .nav-item > .nav-link.active, .nav-sidebar .nav-item > .nav-link:hover { background-color: rgba(255,255,255,0.15); color: #ffffff; }
        .nav-sidebar .nav-item > .nav-link.active { background-color: var(--accent-color-darker); /* Aktif link için biraz daha koyu */ color: #fff; }
        .nav-sidebar .nav-item > .nav-link.active .nav-icon { color: #fff !important; }
        .nav-sidebar .nav-icon { color: #ced4da; width: 1.6rem; margin-right: .5rem; transition: color 0.2s ease;}

        /* --- Ana İçerik Alanı --- */
        .content-wrapper { background-color: var(--bg-main); padding: 25px; }
        .content-header { padding: 15px 0px; margin-bottom: 15px; }
        .content-header h1 { font-size: 1.7rem; font-weight: 600; color: var(--text-primary); }
        .breadcrumb-item a { color: var(--accent-color); }
        .breadcrumb-item.active { color: var(--text-secondary); }

        /* --- KPI Kartları için Animasyon --- */
        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- KPI Kartları (Temel Performans Göstergeleri) --- */
        .kpi-box {
            background-color: var(--bg-card); border-radius: 10px; padding: 20px;
            margin-bottom: 22px; box-shadow: 0 4px 15px var(--shadow-color);
            display: flex; align-items: center; transition: transform 0.25s ease, box-shadow 0.25s ease;
            min-height: 100px;
            opacity: 0;
            animation: fadeInSlideUp 0.5s ease-out forwards;
        }
        .kpi-box:hover { transform: translateY(-4px); box-shadow: 0 7px 20px rgba(44, 62, 80, 0.12); }
        .kpi-icon {
            font-size: 1.8rem; margin-right: 18px; padding: 12px; border-radius: 50%;
            width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; color: #fff;
        }
        .kpi-content .kpi-text { font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .kpi-content .kpi-number { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
        .kpi-number small { font-size: 0.75rem; font-weight: 500; color: var(--text-secondary); margin-left: 3px; }

        /* KPI İkon Arka Plan Renkleri (CSS Değişkenlerinden) */
        .kpi-icon.bg-theme-red { background-color: var(--chart-color-red); }
        .kpi-icon.bg-theme-green { background-color: var(--chart-color-green); }
        .kpi-icon.bg-theme-blue { background-color: var(--chart-color-blue); }
        .kpi-icon.bg-theme-yellow { background-color: var(--chart-color-yellow); }
        .kpi-icon.bg-theme-info { background-color: var(--chart-color-info); }
        .kpi-icon.bg-theme-purple { background-color: var(--chart-color-purple); }
        .kpi-icon.bg-theme-orange { background-color: var(--chart-color-orange); }
        .kpi-icon.bg-theme-maroon { background-color: var(--chart-color-maroon); }
        .kpi-icon.bg-theme-teal { background-color: var(--chart-color-teal); }
         .kpi-icon.bg-theme-fuchsia { background-color: var(--chart-color-fuchsia); }


        /* --- Özel Kart Stilleri (Grafik ve Tablo Kartları) --- */
        .custom-card {
            background-color: var(--bg-card); border: none; border-radius: 12px;
            margin-bottom: 28px; box-shadow: 0 5px 18px var(--shadow-color);
            display: flex; flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .custom-card-header { padding: 18px 25px; border-bottom: 1px solid var(--border-color); }
        .custom-card-title { font-size: 1.05rem; font-weight: 600; color: var(--text-primary); margin: 0; }
        .custom-card-title .fas, .custom-card-title .far { margin-right: 10px; color: var(--accent-color); font-size: 1rem; }
        .custom-card-body { padding: 25px; flex-grow: 1; }
        .chart-container { position: relative; width: 100%; }
        .h-280 { height: 280px; } .h-300 { height: 300px; } .h-330 { height: 330px; } .h-350 { height: 350px; }


        /* --- Modern Tablo Stilleri --- */
        .table-modern { width: 100%; margin-bottom: 0; color: var(--text-primary); border-collapse: separate; border-spacing: 0; font-size: 0.88rem; }
        .table-modern th, .table-modern td { padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        .table-modern th { border-top: none; }
        .table-modern td:first-child, .table-modern th:first-child { border-left: none; }
        .table-modern td:last-child, .table-modern th:last-child { border-right: none; }
        .table-modern thead th { background-color: #f9fafb; border-bottom: 2px solid var(--border-color); font-weight: 600; color: var(--text-primary); text-align: left; }
        .table-modern tbody tr:hover { background-color: #f1f5f8; }
        .table-modern .font-weight-bold { font-weight: 600 !important; }
        .table-modern .badge { font-size: 0.75rem; padding: .3em .6em; }
        .table-responsive-wrapper { border-radius: 10px; box-shadow: 0 2px 8px var(--shadow-color); overflow: hidden; border: 1px solid var(--border-color); }
        .table-responsive-sm { max-height: 300px; border: none; } /* Kaydırılabilir tablo alanı */
         .table-responsive-sm.th-200 { max-height: 200px; }
         .table-responsive-sm.th-250 { max-height: 250px; }


        /* --- Altbilgi (Footer) --- */
        .main-footer { background-color: var(--bg-card); border-top: 1px solid var(--border-color); color: var(--text-secondary); padding: 1.3rem; font-size: 0.88rem; margin-top: 25px; text-align: center; }
        .main-footer a { color: var(--accent-color); font-weight: 500; }
        .main-footer a:hover { color: var(--accent-color-darker); }

        /* --- KPI Kartları için Kademeli Animasyon Gecikmesi --- */
        <?php
        $kpi_count = 11; // Toplam KPI sayısını buraya yazın
        for ($i = 0; $i < $kpi_count; $i++): ?>
        .kpi-box-wrapper:nth-child(<?php echo $i + 1; ?>) .kpi-box { animation-delay: <?php echo $i * 0.07; ?>s; }
        <?php endfor; ?>

    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <!-- Üst Navigasyon Çubuğu -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
            </ul>
            <span class="navbar-brand-custom mx-auto d-block text-center">
                <i class="fas fa-laptop-medical mr-2"></i>PharmAnalytics Pro
            </span>
            <ul class="navbar-nav">
                 <li class="nav-item"><a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a></li>
            </ul>
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

        <!-- Ana İçerik Alanı -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6"><h1 class="m-0">Eczane Performans Paneli</h1></div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Ana Panel</a></li>
                                <li class="breadcrumb-item active">Performans</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <!-- KPI Kartları Satırı -->
                    <div class="row justify-content-center">
                        <?php
                        // KPI öğeleri için bir dizi tanımla.
                        $kpi_items_config = [
                            ['key' => 'total_prescriptions', 'icon' => 'fa-prescription-bottle-alt', 'color' => 'theme-red', 'text' => 'Toplam Reçete', 'unit' => ''],
                            ['key' => 'revenue_last_30_days', 'icon' => 'fa-money-bill-wave', 'color' => 'theme-green', 'text' => 'Son 30G Ciro', 'unit' => 'TL', 'format' => 'currency'],
                            ['key' => 'total_patients', 'icon' => 'fa-users', 'color' => 'theme-info', 'text' => 'Toplam Hasta', 'unit' => ''],
                            ['key' => 'total_drug_types', 'icon' => 'fa-capsules', 'color' => 'theme-yellow', 'text' => 'İlaç Çeşidi', 'unit' => ''],
                            ['key' => 'total_doctors', 'icon' => 'fa-user-md', 'color' => 'theme-blue', 'text' => 'Kayıtlı Doktor', 'unit' => ''],
                            ['key' => 'avg_stock', 'icon' => 'fa-box-open', 'color' => 'theme-purple', 'text' => 'Ort. İlaç Stoğu', 'unit' => '', 'format' => 'decimal1'],
                            ['key' => 'avg_drug_price', 'icon' => 'fa-tags', 'color' => 'theme-orange', 'text' => 'Ort. İlaç Fiyatı', 'unit' => 'TL', 'format' => 'decimal2'],
                            ['key' => 'out_of_stock_drugs', 'icon' => 'fa-ban', 'color' => 'theme-maroon', 'text' => 'Stoğu Biten İlaç', 'unit' => ''],
                            ['key' => 'avg_items_per_prescription', 'icon' => 'fa-list-ol', 'color' => 'theme-teal', 'text' => 'Reçete Başına Ort. Ürün', 'unit' => '', 'format' => 'decimal1'],
                            ['key' => 'total_pharmacies', 'icon' => 'fa-hospital-alt', 'color' => 'theme-fuchsia', 'text' => 'Toplam Eczane', 'unit' => ''],
                            ['key' => 'active_personnel', 'icon' => 'fa-user-tie', 'color' => 'theme-blue', 'text' => 'Aktif Personel', 'unit' => '']
                        ];
                        ?>
                        <?php foreach ($kpi_items_config as $item): ?>
                        <div class="col-lg-3 col-md-6 col-sm-12 kpi-box-wrapper"> 
                            <div class="kpi-box">
                                <div class="kpi-icon bg-<?php echo htmlspecialchars($item['color']); ?>"><i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i></div>
                                <div class="kpi-content">
                                    <div class="kpi-text"><?php echo htmlspecialchars($item['text']); ?></div>
                                    <div class="kpi-number">
                                        <?php
                                        $value = $kpi_data[$item['key']];
                                        if (isset($item['format'])) {
                                            if ($item['format'] == 'currency') echo htmlspecialchars(number_format($value, 0));
                                            else if ($item['format'] == 'decimal1') echo htmlspecialchars(number_format($value, 1));
                                            else if ($item['format'] == 'decimal2') echo htmlspecialchars(number_format($value, 2));
                                            else echo htmlspecialchars($value);
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                        <small><?php echo htmlspecialchars($item['unit']); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Grafik Satırı 1: Ciro/Reçete Trendi, Hasta Cinsiyet ve Yaş Dağılımı -->
                    <div class="row">
                        <div class="col-lg-7" data-aos="fade-up">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-chart-line"></i> Son 30 Günlük Ciro ve Reçete Trendi</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-350"><canvas id="revenueAndPrescriptionTrendChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card mb-3">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-venus-mars"></i> Hasta Cinsiyet Dağılımı</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-280" style="max-height: 150px;"><canvas id="patientGenderChart"></canvas></div></div>
                            </div>
                             <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-birthday-cake"></i> Hasta Yaş Grupları</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-280" style="max-height: 150px;"><canvas id="patientAgeDistributionChart"></canvas></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik Satırı 2: Stok Seviyeleri ve En Çok Ciro Getiren İlaçlar -->
                    <div class="row">
                        <div class="col-md-5" data-aos="fade-up">
                             <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-cubes"></i> İlaç Stok Seviyeleri (Tür Bazlı)</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-330"><canvas id="drugStockCategoriesChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-md-7" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-hand-holding-usd"></i> En Çok Ciro Getiren İlaçlar (Top 5)</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-330"><canvas id="topDrugsByValueChart"></canvas></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik Satırı 3: Üreticiye Göre İlaç Çeşitliliği ve Doktor Uzmanlık Alanı -->
                    <div class="row">
                         <div class="col-md-6" data-aos="fade-up">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-industry"></i> Üreticiye Göre İlaç Çeşitliliği (Top 7)</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-330"><canvas id="drugsPerManufacturerChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-stethoscope"></i> Doktor Uzmanlık Alanına Göre Reçete Yoğunluğu</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-330"><canvas id="doctorSpecializationVolumeChart"></canvas></div></div>
                            </div>
                        </div>
                    </div>
                     <!-- Grafik Satırı 4: Anlaşma Durumları ve En Karlı İlaçlar -->
                     <div class="row">
                        <div class="col-md-6" data-aos="fade-up">
                             <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-file-signature"></i> Üretici Anlaşma Durumları</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-300"><canvas id="agreementStatusChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-hand-holding-medical"></i> En Karlı İlaçlar (Top 5)</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-300"><canvas id="topProfitableDrugsChart"></canvas></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tablo Satırı 1: Son Reçeteler ve Kritik Stoklar -->
                    <div class="row">
                        <div class="col-lg-7" data-aos="fade-up">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-receipt"></i> Son Reçeteler ve Değerleri</h3></div>
                                <div class="custom-card-body p-0">
                                    <div class="table-responsive-wrapper">
                                        <div class="table-responsive-sm th-250">
                                            <table class="table table-modern table-striped table-hover">
                                                <thead><tr><th>ID</th><th>Hasta</th><th>Doktor</th><th>İlaç</th><th>Adet</th><th>Tarih</th><th class="text-right">Değer (TL)</th></tr></thead>
                                                <tbody>
                                                    <?php if (!empty($table_data['recent_prescriptions'])): ?>
                                                        <?php foreach ($table_data['recent_prescriptions'] as $rec): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($rec['recete_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($rec['hasta_ad_soyad']); ?></td>
                                                            <td><?php echo htmlspecialchars($rec['doktor_ad_soyad']); ?></td>
                                                            <td><?php echo htmlspecialchars($rec['ilac_ad']); ?></td>
                                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($rec['ilac_adet']); ?></span></td>
                                                            <td><?php echo htmlspecialchars($rec['recete_tarih_formatted']); ?></td>
                                                            <td class="text-right font-weight-bold"><?php echo htmlspecialchars(number_format($rec['total_value'],2)); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="7" class="text-center py-3">Son reçete bulunmamaktadır.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card mb-3">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-exclamation-triangle text-danger"></i> Kritik Stok Seviyesindeki İlaçlar (< <?php echo $low_stock_threshold; ?>)</h3></div>
                                <div class="custom-card-body p-0">
                                     <div class="table-responsive-wrapper">
                                        <div class="table-responsive-sm th-200">
                                            <table class="table table-modern table-hover">
                                                <thead><tr><th>İlaç</th><th>Stok</th><th>Üretici</th><th>Sipariş</th></tr></thead>
                                                <tbody>
                                                    <?php if (!empty($table_data['low_stock_drugs'])): ?>
                                                        <?php foreach ($table_data['low_stock_drugs'] as $drug): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($drug['ilac_ad']); ?></td>
                                                            <td><span class="badge bg-danger font-weight-bold"><?php echo htmlspecialchars($drug['stok']); ?></span></td>
                                                            <td><?php echo htmlspecialchars($drug['uretici_ad'] ?? 'N/A'); ?></td>
                                                            <td><a href="#" class="btn btn-xs btn-outline-primary" title="Sipariş Oluştur"><i class="fas fa-plus-circle"></i></a></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="4" class="text-center py-3">Kritik stokta ilaç yok.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Tablo Satırı 2: En Değerli Hastalar ve Yaklaşan Anlaşma Bitişleri -->
                     <div class="row">
                        <div class="col-lg-7" data-aos="fade-up">
                             <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-medal"></i> En Değerli Hastalar (Harcama Bazlı Top 5)</h3></div>
                                 <div class="custom-card-body p-0">
                                     <div class="table-responsive-wrapper">
                                        <div class="table-responsive-sm th-250">
                                            <table class="table table-modern table-striped table-hover">
                                                <thead><tr><th>#</th><th>Hasta Adı Soyadı</th><th class="text-right">Toplam Harcama (TL)</th><th class="text-center">Reçete Sayısı</th></tr></thead>
                                                <tbody>
                                                    <?php if (!empty($table_data['top_valuable_patients'])): $rank = 1; ?>
                                                        <?php foreach ($table_data['top_valuable_patients'] as $patient): ?>
                                                        <tr>
                                                            <td><?php echo $rank++; ?>.</td>
                                                            <td><i class="fas fa-user-tag text-primary mr-2"></i><?php echo htmlspecialchars($patient['hasta_ad_soyad']); ?></td>
                                                            <td class="text-right font-weight-bold"><?php echo htmlspecialchars(number_format($patient['total_spent'], 2)); ?></td>
                                                            <td class="text-center"><span class="badge bg-success"><?php echo htmlspecialchars($patient['total_prescriptions']); ?></span></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="4" class="text-center py-3">Veri bulunamadı.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="far fa-calendar-times text-warning"></i> Yaklaşan Anlaşma Bitişleri (60 Gün)</h3></div>
                                <div class="custom-card-body p-0">
                                    <div class="table-responsive-wrapper">
                                        <div class="table-responsive-sm th-200">
                                            <table class="table table-modern table-hover">
                                                <thead><tr><th>Üretici</th><th>İlaç</th><th>Bitiş Tarihi</th></tr></thead>
                                                <tbody>
                                                    <?php if (!empty($table_data['expiring_agreements'])): ?>
                                                        <?php foreach ($table_data['expiring_agreements'] as $agree): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($agree['uretici_ad']); ?></td>
                                                            <td><?php echo htmlspecialchars($agree['ilac_ad']); ?></td>
                                                            <td><span class="badge bg-warning"><?php echo htmlspecialchars($agree['anlasma_bitis_formatted']); ?></span></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="3" class="text-center py-3">Yaklaşan anlaşma bitişi yok.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>

        <footer class="main-footer">
            <strong>Telif Hakkı © <?php echo date("Y"); ?> <a href="#">PharmAnalytics Pro Solutions</a>.</strong> Tüm hakları saklıdır.
            <div class="float-right d-none d-sm-inline">Versiyon 5.0 (Modern Tasarım)</div>
        </footer>
    </div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
$(function () {
    AOS.init({ duration: 600, once: true });
    Chart.register(ChartDataLabels);

    // --- Chart.js Genel Varsayılan Ayarları ---
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim();
    Chart.defaults.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim() + '80';
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.padding = 12;
    Chart.defaults.plugins.legend.labels.font = { size: 10 };
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0,0,0,0.85)';
    Chart.defaults.plugins.tooltip.titleFont = { size: 12, weight: 'bold' };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 10 };
    Chart.defaults.plugins.tooltip.padding = 8;
    Chart.defaults.plugins.tooltip.cornerRadius = 4;
    Chart.defaults.plugins.datalabels.font = { size: 9, weight: '500' };
    Chart.defaults.plugins.datalabels.color = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim();

    // --- Grafik Renkleri (CSS Değişkenlerinden) ---
    const appColors = {
        red: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-red').trim(),
        green: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-green').trim(),
        blue: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-blue').trim(),
        yellow: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-yellow').trim(),
        info: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-info').trim(),
        purple: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-purple').trim(),
        teal: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-teal').trim(),
        orange: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-orange').trim(),
        maroon: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-maroon').trim(),
        indigo: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-indigo').trim(),
        pink: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-pink').trim(),
        olive: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-olive').trim(),
        fuchsia: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-fuchsia').trim(),
        dark: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-dark').trim(),
        bgCard: getComputedStyle(document.documentElement).getPropertyValue('--bg-card').trim(),
        accent: getComputedStyle(document.documentElement).getPropertyValue('--accent-color').trim()
    };

    const chartPalette = [
        appColors.blue, appColors.green, appColors.yellow, appColors.info, appColors.purple,
        appColors.orange, appColors.teal, appColors.pink, appColors.maroon, appColors.olive,
        appColors.indigo, appColors.fuchsia
    ];
    const transparentChartPalette = chartPalette.map(color => color + 'B3'); // %70 opacity


    // --- Ortak Grafik Seçenekleri ---
    const commonChartOptions = {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 800, easing: 'easeInOutQuart' },
        plugins: { datalabels: { display: false } }
    };
    
    function formatCurrency(value) {
        return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
    }
    function formatNumber(value) {
        return new Intl.NumberFormat('tr-TR').format(value);
    }


    // --- Grafikler ---

    // 1. Ciro ve Reçete Trendi (Birleşik Çizgi ve Çubuk Grafik)
    const revenueAndPrescriptionTrendCtx = document.getElementById('revenueAndPrescriptionTrendChart')?.getContext('2d');
    if (revenueAndPrescriptionTrendCtx) {
        new Chart(revenueAndPrescriptionTrendCtx, {
            type: 'bar', // Ana tip çubuk, ciro için line override edilecek
            data: {
                labels: <?php echo json_encode($chart_data['revenue_30day_labels'] ?? []); ?>,
                datasets: [
                    {
                        label: 'Günlük Ciro (TL)',
                        data: <?php echo json_encode($chart_data['revenue_30day_counts'] ?? []); ?>,
                        type: 'line', // Bu dataset için tip line
                        borderColor: appColors.red,
                        backgroundColor: appColors.red + '1A',
                        yAxisID: 'yRevenue',
                        tension: 0.3,
                        pointRadius: 2.5,
                        pointBackgroundColor: appColors.red,
                        fill: true,
                        order: 0 // Çizginin çubukların üzerinde olması için
                    },
                    {
                        label: 'Günlük Reçete Sayısı',
                        data: <?php echo json_encode($chart_data['prescription_30day_counts'] ?? []); ?>,
                        backgroundColor: appColors.blue + 'B3', // %70 saydamlık
                        borderColor: appColors.blue,
                        yAxisID: 'yPrescriptions',
                        order: 1 // Çubukların çizginin altında olması için
                    }
                ]
            },
            options: {
                ...commonChartOptions,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { 
                        grid: { display: false },
                        ticks: { autoSkip: true, maxTicksLimit: 15 } // Etiket yoğunluğunu azalt
                    },
                    yRevenue: {
                        type: 'linear', position: 'left',
                        title: { display: true, text: 'Ciro (TL)', color: appColors.red, font:{size:10} },
                        ticks: { color: appColors.red, callback: function(value) { return formatCurrency(value); }, font:{size:9} },
                        grid: { drawOnChartArea: false }
                    },
                    yPrescriptions: {
                        type: 'linear', position: 'right',
                        title: { display: true, text: 'Reçete Sayısı', color: appColors.blue, font:{size:10} },
                        ticks: { color: appColors.blue, stepSize: 1, precision: 0, font:{size:9} },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 10, padding:10, font:{size:10} } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    if (context.dataset.yAxisID === 'yRevenue') {
                                        label += formatCurrency(context.parsed.y);
                                    } else {
                                        label += formatNumber(context.parsed.y);
                                    }
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Hasta Cinsiyet Dağılımı (Pasta)
    const patientGenderCtx = document.getElementById('patientGenderChart')?.getContext('2d');
    if (patientGenderCtx) { new Chart(patientGenderCtx, { type: 'pie', data: { labels: <?php echo json_encode($chart_data['patient_gender_labels'] ?? []); ?>, datasets: [{ data: <?php echo json_encode($chart_data['patient_gender_counts'] ?? []); ?>, backgroundColor: [appColors.info, appColors.pink, appColors.dark + '80'], borderWidth:2, borderColor: appColors.bgCard }] }, options: {...commonChartOptions, plugins: { legend: { position: 'right', labels:{padding:8, font:{size:9} } } } } }); }

    // 3. Hasta Yaş Dağılımı (Çubuk - dikey)
    const patientAgeCtx = document.getElementById('patientAgeDistributionChart')?.getContext('2d');
    if (patientAgeCtx) { new Chart(patientAgeCtx, { type: 'bar', data: { labels: <?php echo json_encode($chart_data['patient_age_labels'] ?? []); ?>, datasets: [{ label: 'Hasta Sayısı', data: <?php echo json_encode($chart_data['patient_age_counts'] ?? []); ?>, backgroundColor: appColors.yellow + 'B3', borderColor: appColors.yellow, borderWidth: 1, borderRadius:3 }] }, options: { ...commonChartOptions, scales: { y: { beginAtZero: true, ticks:{precision:0, font:{size:9}} }, x: {ticks:{font:{size:9}}} }, plugins: { legend: { display: false } } } }); }

    // 4. İlaç Stok Seviyeleri (Doughnut)
    const drugStockCtx = document.getElementById('drugStockCategoriesChart')?.getContext('2d');
    if (drugStockCtx) { new Chart(drugStockCtx, { type: 'doughnut', data: { labels: <?php echo json_encode($chart_data['stock_category_labels'] ?? []); ?>, datasets: [{ data: <?php echo json_encode($chart_data['stock_category_counts'] ?? []); ?>, backgroundColor: [appColors.maroon, appColors.red, appColors.orange, appColors.info, appColors.green], borderWidth:2, borderColor:appColors.bgCard }] }, options: { ...commonChartOptions, cutout: '65%', plugins: { legend: { position: 'right', labels:{padding:8, font:{size:9}} } } } }); }

    // 5. En Çok Ciro Getiren İlaçlar (Yatay Çubuk)
    const topDrugsByValueCtx = document.getElementById('topDrugsByValueChart')?.getContext('2d');
    if (topDrugsByValueCtx) { new Chart(topDrugsByValueCtx, { type: 'bar', data: { labels: <?php echo json_encode($chart_data['top_drug_value_labels'] ?? []); ?>, datasets: [{ label: 'Toplam Ciro (TL)', data: <?php echo json_encode($chart_data['top_drug_value_counts'] ?? []); ?>, backgroundColor: chartPalette.slice(0,5).map(c => c + 'B3'), borderColor: chartPalette.slice(0,5), borderWidth: 1, borderRadius:3 }] }, options: { ...commonChartOptions, indexAxis: 'y', scales: { x: { beginAtZero: true, title: { display: true, text: 'Toplam Ciro (TL)', font:{size:10} }, ticks: { callback: function(value){ return formatCurrency(value);}, font:{size:9}} }, y: {ticks:{font:{size:9}}} }, plugins: { legend: { display: false }, tooltip: {callbacks: {label: function(context){return ' Ciro: ' + formatCurrency(context.raw);}}}} } }); }

    // 6. Üreticiye Göre İlaç Çeşitliliği (Pasta)
    const drugsPerManCtx = document.getElementById('drugsPerManufacturerChart')?.getContext('2d');
    if (drugsPerManCtx) { new Chart(drugsPerManCtx, { type: 'pie', data: { labels: <?php echo json_encode($chart_data['manufacturer_labels'] ?? []); ?>, datasets: [{ data: <?php echo json_encode($chart_data['manufacturer_drug_counts'] ?? []); ?>, backgroundColor: chartPalette.slice(5, 12), borderWidth:2, borderColor:appColors.bgCard }] }, options: { ...commonChartOptions, plugins: { legend: { position: 'right', labels:{padding:8, font:{size:9}} } } } }); }

    // 7. Doktor Uzmanlık Alanına Göre Reçete Yoğunluğu (Çubuk - dikey)
    const docSpecVolumeCtx = document.getElementById('doctorSpecializationVolumeChart')?.getContext('2d');
    if (docSpecVolumeCtx) { new Chart(docSpecVolumeCtx, { type: 'bar', data: { labels: <?php echo json_encode($chart_data['doc_spec_labels'] ?? []); ?>, datasets: [{ label: 'Reçete Sayısı', data: <?php echo json_encode($chart_data['doc_spec_counts'] ?? []); ?>, backgroundColor: transparentChartPalette, borderColor: chartPalette, borderWidth: 1, borderRadius:3 }] }, options: { ...commonChartOptions, scales: { y: { beginAtZero: true, title: { display: true, text: 'Reçete Sayısı', font:{size:10} }, ticks:{precision:0, font:{size:9}} }, x: {ticks:{font:{size:9}}} }, plugins: { legend: { display: false } } } }); }

    // 8. Üretici Anlaşma Durumları (Doughnut)
    const agreementStatusCtx = document.getElementById('agreementStatusChart')?.getContext('2d');
    if (agreementStatusCtx) { new Chart(agreementStatusCtx, { type: 'doughnut', data: { labels: <?php echo json_encode($chart_data['agreement_status_labels'] ?? []); ?>, datasets: [{ data: <?php echo json_encode($chart_data['agreement_status_counts'] ?? []); ?>, backgroundColor: [appColors.green, appColors.orange, appColors.red, appColors.dark + '80', appColors.info], borderWidth:2, borderColor:appColors.bgCard }] }, options: { ...commonChartOptions, cutout: '65%', plugins: { legend: { position: 'bottom', labels:{padding:8, font:{size:9}} } } } }); }

    // 9. En Karlı İlaçlar (Yatay Çubuk)
    const topProfitableDrugsCtx = document.getElementById('topProfitableDrugsChart')?.getContext('2d');
    if (topProfitableDrugsCtx) {
        new Chart(topProfitableDrugsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_data['top_profit_drug_labels'] ?? []); ?>,
                datasets: [{
                    label: 'Toplam Kâr (TL)',
                    data: <?php echo json_encode($chart_data['top_profit_drug_values'] ?? []); ?>,
                    backgroundColor: appColors.indigo + 'B3',
                    borderColor: appColors.indigo,
                    borderWidth: 1, borderRadius:3
                }]
            },
            options: { ...commonChartOptions, indexAxis: 'y',
                scales: { x: { beginAtZero: true, title: { display: true, text: 'Toplam Kâr (TL)', font:{size:10} }, ticks: { callback: function(value){ return formatCurrency(value);}, font:{size:9}} }, y: {ticks:{font:{size:9}}} },
                plugins: { legend: { display: false }, tooltip: {callbacks: {label: function(context){return ' Kâr: ' + formatCurrency(context.raw);}}}}
            }
        });
    }
});
</script>
</body>
</html>