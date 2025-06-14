<?php
// --- Hata Raporlama Ayarları (Geliştirme için) ---
// error_reporting(E_ALL); // Geliştirme sırasında tüm PHP hatalarını gösterir.
// ini_set('display_errors', 1); // Hataların tarayıcıda görüntülenmesini sağlar.

// --- Veritabanı Bağlantısı ---
include 'data2.php'; // Veritabanı bağlantı bilgilerini ve $conn nesnesini içeren dosya.

// --- Veri Toplama Dizileri ---
// Bu diziler, PHP'de işlenen ve daha sonra HTML/JavaScript'e aktarılacak verileri tutar.
$kpi_data = []; // Temel Performans Göstergeleri (KPI) için veriler.
$chart_data = []; // Grafikler için veriler.
$table_data = []; // Tablolar için veriler.

// --- KPI Verileri ---
// Bu bölümde, gösterge panelinin üst kısmında yer alacak özet bilgiler (KPI'lar) hesaplanır.

// Toplam Hasta Sayısı
$sql_total_patients = "SELECT COUNT(*) AS toplam_hasta FROM diabetes_new";
$result = $conn->query($sql_total_patients); // Sorguyu çalıştır.
// Sonucu $kpi_data dizisine ata. Eğer sonuç yoksa veya sorgu başarısızsa 0 ata.
$kpi_data['total_patients'] = $result ? $result->fetch_assoc()['toplam_hasta'] ?? 0 : 0;

// YENİ: Toplam Gebe Sayısı Hesaplaması
$sql_total_pregnant = "SELECT COUNT(*) AS toplam_gebe FROM diabetes_new WHERE Pregnancies > 0";
$result_total_pregnant = $conn->query($sql_total_pregnant);
$kpi_data['total_pregnant_patients'] = $result_total_pregnant ? $result_total_pregnant->fetch_assoc()['toplam_gebe'] ?? 0 : 0;
// BİTTİ: Toplam Gebe Sayısı Hesaplaması

// Çeşitli Ölçümlerin Ortalamaları
$sql_averages = "SELECT
    AVG(Age) AS ortalama_age, AVG(Pregnancies) AS ortalama_pregnancies, AVG(Glucose) AS ortalama_glucose,
    AVG(BloodPressure) AS ortalama_blood_pressure, AVG(SkinThickness) AS ortalama_skin_thickness,
    AVG(Insulin) AS ortalama_insulin, AVG(BMI) AS ortalama_bmi,
    AVG(DiabetesPedigreeFunction) AS ortalama_dpf
    FROM diabetes_new";
$result = $conn->query($sql_averages);
$averages_row = $result ? $result->fetch_assoc() : []; // Sonucu diziye al, yoksa boş dizi ata.

// Hesaplanan ortalamaları $kpi_data dizisine yuvarlayarak ata.
$kpi_data['avg_age'] = round($averages_row['ortalama_age'] ?? 0, 1); // Ortalama Yaş
$kpi_data['avg_pregnancies'] = round($averages_row['ortalama_pregnancies'] ?? 0, 2); // Ortalama Gebelik Sayısı
$kpi_data['avg_glucose'] = round($averages_row['ortalama_glucose'] ?? 0, 2); // Ortalama Glikoz
$kpi_data['avg_blood_pressure'] = round($averages_row['ortalama_blood_pressure'] ?? 0, 2); // Ortalama Kan Basıncı (KPI kartında kullanılmıyor ama hesaplanıyor)
$kpi_data['avg_skin_thickness'] = round($averages_row['ortalama_skin_thickness'] ?? 0, 2); // Ortalama Deri Kalınlığı
$kpi_data['avg_bmi'] = round($averages_row['ortalama_bmi'] ?? 0, 2); // Ortalama BMI
$kpi_data['avg_insulin'] = round($averages_row['ortalama_insulin'] ?? 0, 2); // Ortalama İnsülin
$kpi_data['avg_dpf'] = round($averages_row['ortalama_dpf'] ?? 0, 3); // Ortalama Diyabet Soyağacı Fonksiyonu

// Minimum ve Maksimum Gebelik Sayısı (Bu veriler $table_data'ya atılıyor ancak şu anda HTML'de gösterilmiyor)
$query_min_max_preg = "SELECT MIN(Pregnancies) AS min_pregnancies, MAX(Pregnancies) AS max_pregnancies FROM diabetes_new";
$result_min_max_preg = $conn->query($query_min_max_preg);
$row_min_max_preg = $result_min_max_preg ? $result_min_max_preg->fetch_assoc() : [];
$table_data['min_pregnancies'] = $row_min_max_preg['min_pregnancies'] ?? 0;
$table_data['max_pregnancies'] = $row_min_max_preg['max_pregnancies'] ?? 0;


// --- Grafik Verileri ---
// Bu bölümde, gösterge panelinde kullanılacak çeşitli grafikler için veriler hazırlanır.

// 1. Yaş Grubu ve BMI'ye Göre Ortalama Kan Basıncı (Tablo Verisi)
$sql_bp_age_bmi = "
SELECT 
    CASE 
        WHEN Age BETWEEN 20 AND 39 THEN '20-39' 
        WHEN Age BETWEEN 40 AND 59 THEN '40-59' 
        ELSE '60+' 
    END AS age_group, -- Yaşı belirli gruplara ayır.
    CASE 
        WHEN BMI < 18.5 THEN 'Underweight' 
        WHEN BMI >= 18.5 AND BMI < 25 THEN 'Normal' 
        WHEN BMI >= 25 AND BMI < 30 THEN 'Overweight' 
        ELSE 'Obese' 
    END AS bmi_category, -- BMI'ı kategorilere ayır.
    AVG(BloodPressure) AS avg_bp -- Her grup için ortalama kan basıncını hesapla.
FROM diabetes_new 
GROUP BY age_group, bmi_category -- Yaş grubu ve BMI kategorisine göre grupla.
ORDER BY age_group, FIELD(bmi_category, 'Underweight', 'Normal', 'Overweight', 'Obese')"; // Sıralama yap.
$result_bp_age_bmi = $conn->query($sql_bp_age_bmi);
$table_data['bp_age_bmi_data'] = []; // Tablo için verileri tutacak dizi.
if ($result_bp_age_bmi) {
    while ($row = $result_bp_age_bmi->fetch_assoc()) {
        // Verileri yaş grubu ve BMI kategorisine göre iç içe bir dizide sakla.
        $table_data['bp_age_bmi_data'][$row['age_group']][$row['bmi_category']] = round($row['avg_bp'], 2);
    }
}
// Tabloda kullanılacak sabit kategori başlıkları.
$table_data['age_categories_table'] = ['20-39', '40-59', '60+'];
$table_data['bmi_categories_table'] = ['Underweight', 'Normal', 'Overweight', 'Obese'];


// 2. Detaylı Yaş Grubuna Göre Ortalama Kan Basıncı (Çubuk Grafik)
$sql_bp_age_group = "
SELECT 
    CASE 
        WHEN Age BETWEEN 20 AND 29 THEN '20-29' 
        WHEN Age BETWEEN 30 AND 39 THEN '30-39' 
        WHEN Age BETWEEN 40 AND 49 THEN '40-49' 
        WHEN Age BETWEEN 50 AND 59 THEN '50-59' 
        WHEN Age BETWEEN 60 AND 69 THEN '60-69' 
        WHEN Age BETWEEN 70 AND 79 THEN '70-79' 
        ELSE '80+' 
    END AS age_group, -- Yaşı 10 yıllık periyotlara ayır.
    AVG(BloodPressure) AS avg_bp -- Her yaş grubu için ortalama kan basıncını hesapla.
FROM diabetes_new 
WHERE Age >= 20 -- Sadece 20 yaş ve üzeri hastaları dahil et.
GROUP BY age_group -- Yaş grubuna göre grupla.
ORDER BY age_group"; // Yaş grubuna göre sırala.
$result_bp_age_group = $conn->query($sql_bp_age_group);
$chart_data['bp_age_labels'] = []; $chart_data['bp_age_averages'] = []; // Grafik etiketleri ve verileri için diziler.
if ($result_bp_age_group) {
    while ($row = $result_bp_age_group->fetch_assoc()) {
        $chart_data['bp_age_labels'][] = $row['age_group']; // Yaş grubu etiketlerini ekle.
        $chart_data['bp_age_averages'][] = round($row['avg_bp'], 2); // Ortalama kan basıncı değerlerini ekle.
    }
}

// 3. Glikoz Risk Seviyesi (Doughnut Grafik)
$sql_risk_level = "
SELECT 
    CASE 
        WHEN Glucose < 100 THEN 'Düşük Risk' 
        WHEN Glucose >= 100 AND Glucose < 140 THEN 'Orta Risk' 
        ELSE 'Yüksek Risk' 
    END AS risk_level, -- Glikoz değerine göre risk seviyesini belirle.
    COUNT(*) AS count -- Her risk seviyesindeki hasta sayısını say.
FROM diabetes_new 
GROUP BY risk_level -- Risk seviyesine göre grupla.
ORDER BY FIELD(risk_level, 'Düşük Risk', 'Orta Risk', 'Yüksek Risk')"; // Belirli bir sırada sırala.
$result_risk_level = $conn->query($sql_risk_level);
$chart_data['risk_labels'] = []; $chart_data['risk_counts'] = []; // Grafik etiketleri ve sayıları için diziler.
$total_for_risk_percentage = 0; // Yüzde hesaplaması için toplam hasta sayısı.
$high_risk_count = 0; // Yüksek riskli hasta sayısı.
if ($result_risk_level) {
    while ($row = $result_risk_level->fetch_assoc()) {
        $chart_data['risk_labels'][] = $row['risk_level']; // Risk seviyesi etiketlerini ekle.
        $chart_data['risk_counts'][] = (int)$row['count']; // Hasta sayılarını ekle.
        $total_for_risk_percentage += (int)$row['count']; // Toplam hasta sayısını güncelle.
        if ($row['risk_level'] == 'Yüksek Risk') {
            $high_risk_count = (int)$row['count']; // Yüksek riskli hasta sayısını güncelle.
        }
    }
}
// Yüksek riskli hasta yüzdesini hesapla ve KPI verisine ata.
$kpi_data['high_risk_percentage'] = ($total_for_risk_percentage > 0) ? round(($high_risk_count / $total_for_risk_percentage) * 100, 1) : 0;


// 4. BMI Kategorileri (Pasta Grafik)
$sql_bmi_category = "
SELECT 
    CASE 
        WHEN BMI < 18.5 THEN 'Zayıf' 
        WHEN BMI >= 18.5 AND BMI < 25 THEN 'Normal' 
        WHEN BMI >= 25 AND BMI < 30 THEN 'Fazla Kilolu' 
        ELSE 'Obez' 
    END AS bmi_category, -- BMI değerine göre kategorileri belirle.
    COUNT(*) AS count -- Her kategorideki hasta sayısını say.
FROM diabetes_new 
GROUP BY bmi_category -- BMI kategorisine göre grupla.
ORDER BY FIELD(bmi_category, 'Zayıf', 'Normal', 'Fazla Kilolu', 'Obez')"; // Belirli bir sırada sırala.
$result_bmi_category = $conn->query($sql_bmi_category);
$chart_data['bmi_labels'] = []; $chart_data['bmi_counts'] = []; // Grafik etiketleri ve sayıları için diziler.
if ($result_bmi_category) {
    while ($row = $result_bmi_category->fetch_assoc()) {
        $chart_data['bmi_labels'][] = $row['bmi_category']; // BMI kategorisi etiketlerini ekle.
        $chart_data['bmi_counts'][] = (int)$row['count']; // Hasta sayılarını ekle.
    }
}

// 5. Yaşa Göre Ortalama Glikoz (Çizgi Grafik)
$sql_glucose_age = "SELECT Age, AVG(Glucose) as avg_glucose FROM diabetes_new WHERE Age > 0 GROUP BY Age ORDER BY Age ASC";
// Her yaş için ortalama glikoz değerini hesapla. Sadece yaşı 0'dan büyük olanları al.
$result_glucose_age = $conn->query($sql_glucose_age);
$chart_data['glucose_age_labels'] = []; $chart_data['glucose_age_averages'] = []; // Grafik etiketleri ve verileri için diziler.
if ($result_glucose_age) {
    while ($row = $result_glucose_age->fetch_assoc()) {
        $chart_data['glucose_age_labels'][] = $row['Age']; // Yaş etiketlerini ekle.
        $chart_data['glucose_age_averages'][] = round($row['avg_glucose'], 2); // Ortalama glikoz değerlerini ekle.
    }
}

// 6. YENİ: İnsülin vs. Glikoz (Dağılım Grafiği - Scatter Plot)
$sql_insulin_glucose = "SELECT Glucose, Insulin FROM diabetes_new WHERE Glucose > 0 AND Insulin > 0 AND Insulin < 600 AND Glucose < 300"; 
// Glikoz ve İnsülin değerlerini seç.
// Aykırı değerleri filtrele (Insulin < 600, Glucose < 300) ve 0 olanları hariç tutarak daha iyi bir görselleştirme sağla.
$result_insulin_glucose = $conn->query($sql_insulin_glucose);
$chart_data['insulin_glucose_data'] = []; // Dağılım grafiği için veri noktalarını tutacak dizi.
if ($result_insulin_glucose) {
    while ($row = $result_insulin_glucose->fetch_assoc()) {
        // Her bir veri noktasını {x: Glikoz, y: İnsülin} formatında ekle.
        $chart_data['insulin_glucose_data'][] = ['x' => (float)$row['Glucose'], 'y' => (float)$row['Insulin']];
    }
}


// 7. YENİ: Kan Basıncı Dağılımı (Histogram/Çubuk Grafik)
$sql_bp_distribution = "
SELECT
    CASE
        WHEN BloodPressure = 0 THEN 'Bilinmiyor' -- Kan basıncı 0 ise 'Bilinmiyor' olarak kategorilendir.
        WHEN BloodPressure < 80 THEN 'Düşük (<80)'
        WHEN BloodPressure BETWEEN 80 AND 120 THEN 'Normal (80-120)'
        WHEN BloodPressure BETWEEN 121 AND 139 THEN 'Yüksek Normal (121-139)'
        WHEN BloodPressure BETWEEN 140 AND 159 THEN 'Evre 1 Hipertansiyon (140-159)'
        WHEN BloodPressure >= 160 THEN 'Evre 2+ Hipertansiyon (160+)'
        ELSE 'Diğer' -- Diğer durumlar (beklenmedik değerler)
    END AS bp_category, -- Kan basıncı değerine göre kategorileri belirle.
    COUNT(*) AS count -- Her kategorideki hasta sayısını say.
FROM diabetes_new
GROUP BY bp_category -- Kan basıncı kategorisine göre grupla.
ORDER BY FIELD(bp_category, 'Düşük (<80)', 'Normal (80-120)', 'Yüksek Normal (121-139)', 'Evre 1 Hipertansiyon (140-159)', 'Evre 2+ Hipertansiyon (160+)', 'Bilinmiyor', 'Diğer')"; // Belirli bir sırada sırala.
$result_bp_distribution = $conn->query($sql_bp_distribution);
$chart_data['bp_dist_labels'] = []; $chart_data['bp_dist_counts'] = []; // Grafik etiketleri ve sayıları için diziler.
if ($result_bp_distribution) {
    while ($row = $result_bp_distribution->fetch_assoc()) {
        $chart_data['bp_dist_labels'][] = $row['bp_category']; // Kan basıncı kategorisi etiketlerini ekle.
        $chart_data['bp_dist_counts'][] = (int)$row['count']; // Hasta sayılarını ekle.
    }
}

// "Diyabet Durumu" (Outcome) ile ilgili PHP kodları daha önceki bir adımda buradan kaldırılmıştı.

// --- Veritabanı Bağlantısını Kapat ---
$conn->close(); // Veritabanı bağlantısını sonlandırarak kaynakları serbest bırak.
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animasyonlu Modern Diyabet Analiz Paneli</title>
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
            --accent-color: #e74c3c; /* Vurgu rengi (örn: linkler, aktif ikonlar) */
            --accent-color-darker: #c0392b; /* Vurgu renginin koyu tonu */
            --font-family-sans-serif: 'Inter', sans-serif; /* Ana yazı tipi */

            /* Grafik Renkleri */
            --chart-color-1: #5dade2; --chart-color-2: #58d68d; --chart-color-3: #f5b041;
            --chart-color-4: #af7ac5; --chart-color-5: #ec7063; --chart-color-6: #5d6d7e;
            /* Risk Seviyesi Grafik Renkleri */
            --chart-color-risk-low: var(--chart-color-2); 
            --chart-color-risk-medium: var(--chart-color-3);
            --chart-color-risk-high: var(--chart-color-5);
        }

        /* --- Genel Sayfa Stilleri --- */
        body {
            font-family: var(--font-family-sans-serif);
            background-color: var(--bg-main);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased; /* Font yumuşatma (WebKit tarayıcılar) */
            -moz-osx-font-smoothing: grayscale; /* Font yumuşatma (Firefox) */
        }
        .wrapper { background-color: var(--bg-main); }

        /* --- Üst Navigasyon Çubuğu (Header) --- */
        .main-header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); box-shadow: 0 1px 3px var(--shadow-color); }
        .main-header .navbar-nav .nav-link { color: var(--text-secondary); }
        .main-header .navbar-nav .nav-link:hover { color: var(--accent-color); }
        .navbar-brand { color: var(--text-primary) !important; font-weight: 600; }
        .navbar-brand .fa-heartbeat { color: var(--accent-color); }

        /* --- Sol Kenar Çubuğu (Sidebar) --- */
        .main-sidebar { background-color: #263238; box-shadow: 2px 0 5px var(--shadow-color); }
        .main-sidebar .brand-link { border-bottom: 1px solid rgba(255,255,255,0.05); }
        .main-sidebar .brand-text { color: #eceff1; }
        .main-sidebar .user-panel .info a { color: #cfd8dc; }
        .nav-sidebar .nav-item > .nav-link { color: #b0bec5; padding: .7rem 1rem; transition: background-color 0.2s ease, color 0.2s ease; }
        .nav-sidebar .nav-item > .nav-link.active, .nav-sidebar .nav-item > .nav-link:hover { background-color: rgba(255,255,255,0.05); color: #ffffff; }
        .nav-sidebar .nav-item > .nav-link.active { background-color: var(--accent-color); color: #fff; }
        .nav-sidebar .nav-item > .nav-link.active .nav-icon { color: #fff !important; }
        .nav-sidebar .nav-icon { color: #78909c; width: 1.6rem; margin-right: .5rem; transition: color 0.2s ease;}

        /* --- Ana İçerik Alanı --- */
        .content-wrapper { background-color: var(--bg-main); padding: 25px; }
        .content-header { padding: 15px 0px; margin-bottom: 15px; }
        .content-header h1 { font-size: 1.85rem; font-weight: 600; color: var(--text-primary); }
        .breadcrumb-item a { color: var(--accent-color); }
        .breadcrumb-item.active { color: var(--text-secondary); }

        /* --- KPI Kartları için Animasyon --- */
        @keyframes fadeInSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- KPI Kartları (Temel Performans Göstergeleri) --- */
        .kpi-box {
            background-color: var(--bg-card); border-radius: 10px; padding: 22px;
            margin-bottom: 22px; box-shadow: 0 4px 15px var(--shadow-color);
            display: flex; align-items: center; transition: transform 0.25s ease, box-shadow 0.25s ease;
            min-height: 105px;
            opacity: 0; /* Animasyon için başlangıçta görünmez */
            animation: fadeInSlideUp 0.5s ease-out forwards; /* Animasyonu uygula */
        }
        .kpi-box:hover { transform: translateY(-4px); box-shadow: 0 7px 20px rgba(44, 62, 80, 0.12); }
        .kpi-icon {
            font-size: 1.9rem; margin-right: 20px; padding: 13px; border-radius: 50%;
            width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; color: #fff;
        }
        .kpi-content .kpi-text { font-size: 0.82rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 5px; font-weight: 600; }
        .kpi-content .kpi-number { font-size: 1.6rem; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
        .kpi-number small { font-size: 0.78rem; font-weight: 500; color: var(--text-secondary); margin-left: 4px; }

        /* KPI İkon Arka Plan Renkleri */
        .kpi-icon.bg-blue { background-color: var(--chart-color-1); } .kpi-icon.bg-green { background-color: var(--chart-color-2); }
        .kpi-icon.bg-orange { background-color: var(--chart-color-3); } .kpi-icon.bg-purple { background-color: var(--chart-color-4); }
        .kpi-icon.bg-red { background-color: var(--chart-color-5); } .kpi-icon.bg-gray { background-color: var(--chart-color-6); }
        .kpi-icon.bg-teal { background-color: #1abc9c; } .kpi-icon.bg-darkred { background-color: var(--accent-color); }

        /* --- Özel Kart Stilleri (Grafik ve Tablo Kartları) --- */
        .custom-card {
            background-color: var(--bg-card); border: none; border-radius: 12px;
            margin-bottom: 28px; box-shadow: 0 5px 18px var(--shadow-color);
            display: flex; flex-direction: column;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .custom-card-header { padding: 20px 28px; border-bottom: 1px solid var(--border-color); }
        .custom-card-title { font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin: 0; }
        .custom-card-title .fas { margin-right: 12px; color: var(--accent-color); font-size: 1.05rem; }
        .custom-card-body { padding: 28px; flex-grow: 1; }
        .chart-container { position: relative; width: 100%; } /* Grafiklerin kapsayıcısı */
        /* Grafik Yükseklikleri */
        .h-300 { height: 300px; } .h-340 { height: 340px; } .h-360 { height: 360px; }

        /* --- Modern Tablo Stilleri --- */
        .table-modern { width: 100%; margin-bottom: 0; color: var(--text-primary); border-collapse: separate; border-spacing: 0; }
        .table-modern th, .table-modern td { padding: 13px 16px; vertical-align: middle; border-bottom: 1px solid var(--border-color); }
        .table-modern th { border-top: none; }
        .table-modern td:first-child, .table-modern th:first-child { border-left: none; }
        .table-modern td:last-child, .table-modern th:last-child { border-right: none; }
        .table-modern thead th { background-color: #f9fafb; border-bottom: 2px solid var(--border-color); font-weight: 600; color: var(--text-primary); text-align: center; }
        .table-modern tbody tr:hover { background-color: #f1f5f8; }
        .table-modern .font-weight-bold { font-weight: 600 !important; }
        .table-responsive-wrapper { border-radius: 10px; box-shadow: 0 2px 8px var(--shadow-color); overflow: hidden; border: 1px solid var(--border-color); }
        .table-responsive-sm { max-height: 330px; border: none; } /* Kaydırılabilir tablo alanı */

        /* --- Gauge (Gösterge) Grafik Kapsayıcısı --- */
        .gauge-container { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; min-height: 270px; }
        .gauge-container p { font-size: 0.88rem; color: var(--text-secondary); }

        /* --- Altbilgi (Footer) --- */
        .main-footer { background-color: var(--bg-card); border-top: 1px solid var(--border-color); color: var(--text-secondary); padding: 1.3rem; font-size: 0.88rem; margin-top: 25px; }
        .main-footer a { color: var(--accent-color); font-weight: 500; }
        .main-footer a:hover { color: var(--accent-color-darker); }

        /* --- KPI Kartları için Kademeli Animasyon Gecikmesi --- */
        <?php for ($i = 0; $i < 9; $i++): // 9 KPI kartı için döngü ?>
        .kpi-box:nth-child(<?php echo $i + 1; ?>) { animation-delay: <?php echo $i * 0.07; ?>s; }
        <?php endfor; ?>

    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
    <div class="wrapper">
        <!-- Üst Navigasyon Çubuğu -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Sol menü açma/kapama butonu -->
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
            </ul>
            <!-- Panel Başlığı -->
            <span class="navbar-brand mx-auto d-block text-center">
                <i class="fas fa-heartbeat mr-2"></i>Diyabet İzleme ve Analiz Paneli
            </span>
            <!-- Tam ekran butonu -->
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a></li>
            </ul>
        </nav>

        <!-- Sol Kenar Çubuğu (Sidebar) -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Marka Logosu -->
            <a href="#" class="brand-link">
                <img src="dist/img/AdminLTELogo.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Diyabet Paneli V2</span>
            </a>
            <!-- Kenar Çubuğu İçeriği -->
            <div class="sidebar">
                <!-- Kullanıcı Paneli -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image"><img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image"></div>
                    <div class="info"><a href="#" class="d-block">Dr. Can Yılmaz</a></div>
                </div>
                <!-- Kenar Çubuğu Menüsü -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item"><a href="index.php" class="nav-link active"><i class="nav-icon fas fa-chart-pie"></i><p>Genel İstatistikler</p></a></li>
                        <li class="nav-item"><a href="hasta_listesi.php" class="nav-link"><i class="nav-icon fas fa-notes-medical"></i><p>Hasta Kayıtları</p></a></li>
                        <!-- Buraya daha fazla menü öğesi eklenebilir -->
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Ana İçerik Alanı -->
        <div class="content-wrapper">
            <!-- İçerik Başlığı (Sayfa başlığı) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6"><h1 class="m-0">Diyabet Veri Analizi</h1></div>
                        <div class="col-sm-6">
                            <!-- Breadcrumb (Sayfa konumu) -->
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="#">Ana Panel</a></li>
                                <li class="breadcrumb-item active">Analiz</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ana İçerik -->
            <section class="content">
                <div class="container-fluid">
                    <!-- KPI Kartları Satırı -->
                    <div class="row">
                        <?php
                        // KPI öğeleri için bir dizi tanımla. Her öğe ikon, renk, metin, değer ve birim içerir.
                        $kpi_items = [
                            ['icon' => 'fa-users', 'color' => 'blue', 'text' => 'Toplam Hasta', 'value' => $kpi_data['total_patients'], 'unit' => ''],
                            ['icon' => 'fa-tachometer-alt', 'color' => 'green', 'text' => 'Ort. Glikoz', 'value' => $kpi_data['avg_glucose'], 'unit' => 'mg/dL'],
                            ['icon' => 'fa-weight', 'color' => 'orange', 'text' => 'Ort. BMI', 'value' => $kpi_data['avg_bmi'], 'unit' => ''],
                            // ESKİ: Ort. Kan Basıncı KPI kartı kaldırılmıştı, ancak veri PHP'de hala hesaplanıyor.
                            // ['icon' => 'fa-heartbeat', 'color' => 'red', 'text' => 'Ort. Kan Basıncı', 'value' => $kpi_data['avg_blood_pressure'], 'unit' => 'mmHg'],
                            // YENİ: Toplam Gebe Sayısı KPI'ı
                            ['icon' => 'fa-female', 'color' => 'red', 'text' => 'Toplam Gebe Hasta', 'value' => $kpi_data['total_pregnant_patients'], 'unit' => 'kişi'],
                            ['icon' => 'fa-layer-group', 'color' => 'purple', 'text' => 'Ort. Deri Kalınlığı', 'value' => $kpi_data['avg_skin_thickness'], 'unit' => 'mm'],
                            ['icon' => 'fa-syringe', 'color' => 'teal', 'text' => 'Ort. İnsülin', 'value' => $kpi_data['avg_insulin'], 'unit' => 'mu U/ml'],
                            ['icon' => 'fa-baby', 'color' => 'gray', 'text' => 'Ort. Gebelik Sayısı', 'value' => $kpi_data['avg_pregnancies'], 'unit' => ''], // Hasta başı ort. gebelik
                            ['icon' => 'fa-dna', 'color' => 'green', 'text' => 'Ort. DPF', 'value' => $kpi_data['avg_dpf'], 'unit' => ''],
                            ['icon' => 'fa-exclamation-triangle', 'color' => 'darkred', 'text' => 'Yüksek Riskli Hasta %', 'value' => $kpi_data['high_risk_percentage'], 'unit' => '%'],
                        ];
                        ?>
                        <?php foreach ($kpi_items as $item): // Her bir KPI öğesi için bir kart oluştur. ?>
                        <div class="col-lg-4 col-md-6 col-sm-12">
                            <div class="kpi-box">
                                <div class="kpi-icon bg-<?php echo htmlspecialchars($item['color']); ?>"><i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i></div>
                                <div class="kpi-content">
                                    <div class="kpi-text"><?php echo htmlspecialchars($item['text']); ?></div>
                                    <div class="kpi-number"><?php echo htmlspecialchars($item['value']); ?> <small><?php echo htmlspecialchars($item['unit']); ?></small></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Grafik Satırı 1: Glikoz Risk Seviyesi ve BMI Kategorileri -->
                    <div class="row">
                        <!-- Glikoz Risk Seviyesi Grafik Kartı -->
                        <div class="col-lg-6 col-md-6 col-sm-12" data-aos="fade-up"> 
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-chart-pie"></i>Glikoz Risk Seviyesi</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-300"><canvas id="riskLevelChart"></canvas></div></div>
                            </div>
                        </div>
                        <!-- BMI Kategorileri Grafik Kartı -->
                        <div class="col-lg-6 col-md-6 col-sm-12" data-aos="fade-up" data-aos-delay="100"> 
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-weight-hanging"></i>BMI Kategorileri</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-300"><canvas id="bmiCategoryChart"></canvas></div></div>
                            </div>
                        </div>
                         <!-- "Diyabet Durumu" (Outcome) grafik kartı daha önceki bir adımda buradan kaldırılmıştı. -->
                    </div>

                    <!-- Grafik Satırı 2: Yaşa Göre Glikoz ve Kan Basıncı Dağılımı -->
                    <div class="row">
                        <!-- Yaşa Göre Ortalama Glikoz Grafik Kartı -->
                        <div class="col-lg-7 col-md-12" data-aos="fade-up">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-chart-line"></i>Yaşa Göre Ortalama Glikoz</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-340"><canvas id="glucoseAgeChart"></canvas></div></div>
                            </div>
                        </div>
                        <!-- Kan Basıncı Dağılımı Grafik Kartı -->
                        <div class="col-lg-5 col-md-12" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-stethoscope"></i>Kan Basıncı Dağılımı</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-340"><canvas id="bpDistributionChart"></canvas></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik Satırı 3: İnsülin vs. Glikoz ve Yaş Grubuna Göre Kan Basıncı -->
                    <div class="row">
                        <!-- İnsülin vs. Glikoz İlişkisi Grafik Kartı -->
                        <div class="col-lg-7 col-md-12" data-aos="fade-up">
                           <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-project-diagram"></i>İnsülin vs. Glikoz İlişkisi</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-360"><canvas id="insulinGlucoseScatterChart"></canvas></div></div>
                           </div>
                        </div>
                        <!-- Yaş Grubuna Göre Ortalama Kan Basıncı Grafik Kartı -->
                         <div class="col-lg-5 col-md-12" data-aos="fade-up" data-aos-delay="100">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-users-cog"></i>Yaş Grubuna Göre Ort. Kan Basıncı</h3></div>
                                <div class="custom-card-body"><div class="chart-container h-360"><canvas id="bpAgeGroupChart"></canvas></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tablo ve Gauge Grafik Satırı -->
                    <div class="row">
                        <!-- Yaş ve BMI'a Göre Ortalama Kan Basıncı Tablo Kartı -->
                        <div class="col-lg-8" data-aos="fade-up">
                            <div class="custom-card">
                                <div class="custom-card-header"><h3 class="custom-card-title"><i class="fas fa-table"></i>Yaş ve BMI'a Göre Ortalama Kan Basıncı</h3></div>
                                <div class="custom-card-body p-0"> 
                                    <div class="table-responsive-wrapper"> 
                                        <div class="table-responsive-sm">
                                            <table class="table table-modern">
                                                <thead>
                                                    <tr>
                                                        <th>Yaş Grubu</th>
                                                        <?php foreach ($table_data['bmi_categories_table'] as $bmi_cat): // BMI kategorilerini başlık olarak yazdır. ?>
                                                            <th><?php echo htmlspecialchars($bmi_cat); ?></th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($table_data['age_categories_table'] as $age_cat): // Her yaş grubu için bir satır oluştur. ?>
                                                        <tr>
                                                            <td class="font-weight-bold"><?php echo htmlspecialchars($age_cat); ?></td>
                                                            <?php foreach ($table_data['bmi_categories_table'] as $bmi_cat): // Her BMI kategorisi için bir hücre oluştur. ?>
                                                                <td class="text-center"><?php echo $table_data['bp_age_bmi_data'][$age_cat][$bmi_cat] ?? '-'; // Veri varsa yazdır, yoksa '-' yazdır. ?></td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Ortalama Diyabet Riski Gauge Grafik Kartı -->
                        <div class="col-lg-4">
                            <div data-aos="fade-up">
                                <div class="custom-card">
                                    <div class="custom-card-header"><h3 class="custom-card-title text-center w-100"><i class="fas fa-tachometer-alt"></i>Ortalama Diyabet Riski</h3></div>
                                    <div class="custom-card-body gauge-container">
                                        <canvas id="diabetesRiskGaugeChart" style="max-width:220px; max-height:150px;"></canvas>
                                        <p class="mt-2 text-center small">Genel popülasyon bazlı risk göstergesi.</p>
                                    </div>
                                </div>
                            </div>
                             <!-- Gebelik Sayısı Aralığı kartı buradan kaldırıldı. Veriler PHP'de hala mevcut ($table_data['min_pregnancies'], $table_data['max_pregnancies']) ancak gösterilmiyor. -->
                        </div>
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
<!-- AOS (Animasyon Kütüphanesi) JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
$(function () {
    // AOS Kütüphanesini Başlat
    AOS.init({
        duration: 600, // Animasyon süresi (milisaniye)
        once: true,    // Animasyonlar sadece bir kez çalışsın
    });

    // Chart.js için Veri Etiketleri Eklentisini Kaydet
    Chart.register(ChartDataLabels);

    // --- Chart.js Genel Varsayılan Ayarları ---
    Chart.defaults.font.family = "'Inter', sans-serif"; // Varsayılan yazı tipi
    Chart.defaults.font.size = 11; // Varsayılan yazı tipi boyutu
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim(); // Varsayılan metin rengi (CSS değişkeninden)
    Chart.defaults.borderColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim() + '80'; // Varsayılan kenarlık rengi (CSS değişkeninden, %50 saydam)

    // Varsayılan Legend (Açıklama Kutusu) Ayarları
    Chart.defaults.plugins.legend.position = 'bottom'; // Açıklama kutusunun konumu
    Chart.defaults.plugins.legend.labels.boxWidth = 10; // Açıklama kutusundaki renkli kutucukların genişliği
    Chart.defaults.plugins.legend.labels.padding = 12; // Açıklama kutusu etiketleri arasındaki boşluk
    Chart.defaults.plugins.legend.labels.font = { size: 10 }; // Açıklama kutusu etiket yazı tipi boyutu

    // Varsayılan Tooltip (İpucu) Ayarları
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0,0,0,0.85)'; // İpucu arka plan rengi
    Chart.defaults.plugins.tooltip.titleFont = { size: 12, weight: 'bold' }; // İpucu başlık yazı tipi
    Chart.defaults.plugins.tooltip.bodyFont = { size: 10 }; // İpucu içerik yazı tipi
    Chart.defaults.plugins.tooltip.padding = 8; // İpucu iç boşluğu
    Chart.defaults.plugins.tooltip.cornerRadius = 4; // İpucu köşe yuvarlaklığı

    // Varsayılan DataLabels (Veri Etiketleri) Ayarları
    Chart.defaults.plugins.datalabels.font = { size: 9, weight: '500' }; // Veri etiketi yazı tipi
    Chart.defaults.plugins.datalabels.color = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim(); // Veri etiketi rengi

    // --- Grafik Renkleri (CSS Değişkenlerinden Alınan) ---
    const chartColors = {
        c1: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-1').trim(),
        c2: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-2').trim(),
        c3: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-3').trim(),
        c4: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-4').trim(),
        c5: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-5').trim(),
        c6: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-6').trim(),
        riskLow: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-risk-low').trim(),
        riskMedium: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-risk-medium').trim(),
        riskHigh: getComputedStyle(document.documentElement).getPropertyValue('--chart-color-risk-high').trim(),
        accent: getComputedStyle(document.documentElement).getPropertyValue('--accent-color').trim(),
        bgCard: getComputedStyle(document.documentElement).getPropertyValue('--bg-card').trim(), // Kart arka planı (örn: doughnut grafik kenarlığı için)
        lightGrey: '#e9ecef' // Açık gri (örn: gauge grafikte boş kısım için)
    };

    // --- Ortak Grafik Seçenekleri (Tüm grafiklerde kullanılacak temel ayarlar) ---
    const commonChartOptions = {
        responsive: true, // Grafiğin kapsayıcısına göre boyutlanması
        maintainAspectRatio: false, // En-boy oranını koruma (false ise yükseklik ayarlanabilir)
        animation: {
            duration: 800, // Animasyon süresi
            easing: 'easeInOutQuart' // Animasyon yumuşatma efekti
        },
        plugins: {
            datalabels: {
                display: false // Veri etiketlerini varsayılan olarak gizle (gerekirse grafikte açılır)
            }
        }
    };

    // --- Grafik Oluşturma Bölümü ---

    // 1. Glikoz Risk Seviyesi Grafiği (Doughnut)
    const riskLevelCtx = document.getElementById('riskLevelChart')?.getContext('2d'); // Canvas elementini al
    if (riskLevelCtx) { // Eğer canvas elementi bulunduysa
        new Chart(riskLevelCtx, {
            type: 'doughnut', // Grafik türü
            data: {
                labels: <?php echo json_encode($chart_data['risk_labels'] ?? []); ?>, // PHP'den gelen etiketler
                datasets: [{
                    data: <?php echo json_encode($chart_data['risk_counts'] ?? []); ?>, // PHP'den gelen veriler
                    backgroundColor: [chartColors.riskLow, chartColors.riskMedium, chartColors.riskHigh], // Renkler
                    borderWidth: 3, // Kenarlık kalınlığı
                    borderColor: chartColors.bgCard // Kenarlık rengi (kart arka planı ile aynı)
                }]
            },
            options: {
                ...commonChartOptions, // Ortak seçenekleri dahil et
                cutout: '70%' // Doughnut grafiğin ortasındaki boşluk oranı
            }
        });
    }

    // 2. BMI Kategorileri Grafiği (Pie)
    const bmiCategoryCtx = document.getElementById('bmiCategoryChart')?.getContext('2d');
    if (bmiCategoryCtx) {
        new Chart(bmiCategoryCtx, {
            type: 'pie', // Grafik türü
            data: {
                labels: <?php echo json_encode($chart_data['bmi_labels'] ?? []); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_data['bmi_counts'] ?? []); ?>,
                    backgroundColor: [chartColors.c1, chartColors.c2, chartColors.c3, chartColors.c5], // Renkler
                    borderWidth: 3,
                    borderColor: chartColors.bgCard
                }]
            },
            options: commonChartOptions // Sadece ortak seçenekleri kullan
        });
    }

    // 3. Yaşa Göre Ortalama Glikoz Grafiği (Line)
    const glucoseAgeCtx = document.getElementById('glucoseAgeChart')?.getContext('2d');
    if (glucoseAgeCtx) {
        new Chart(glucoseAgeCtx, {
            type: 'line', // Grafik türü
            data: {
                labels: <?php echo json_encode($chart_data['glucose_age_labels'] ?? []); ?>, // X ekseni etiketleri (Yaşlar)
                datasets: [{
                    label: 'Ortalama Glikoz', // Veri seti etiketi
                    data: <?php echo json_encode($chart_data['glucose_age_averages'] ?? []); ?>, // Y ekseni verileri (Ortalama Glikoz)
                    borderColor: chartColors.c2, // Çizgi rengi
                    backgroundColor: chartColors.c2 + '33', // Alan dolgu rengi (saydam)
                    fill: true, // Çizginin altını doldur
                    tension: 0.4, // Çizgi eğriliği (0: düz, 1: çok eğri)
                    pointRadius: 2.5, // Veri noktalarının yarıçapı
                    pointBackgroundColor: chartColors.c2, // Nokta dolgu rengi
                    pointBorderColor: chartColors.bgCard, // Nokta kenarlık rengi
                    pointHoverRadius: 5, // Fare üzerine gelince nokta yarıçapı
                    pointHoverBorderWidth: 2 // Fare üzerine gelince nokta kenarlık kalınlığı
                }]
            },
            options: {
                ...commonChartOptions,
                scales: { // Eksen ayarları
                    y: { title: { display: true, text: 'Glikoz (mg/dL)' }, grid: { drawBorder: false } }, // Y ekseni başlığı ve grid ayarı
                    x: { title: { display: true, text: 'Yaş' }, grid: { display: false } } // X ekseni başlığı ve grid ayarı
                },
                plugins: {
                    ...commonChartOptions.plugins,
                    legend: { display: false } // Bu grafik için legend'ı gizle
                }
            }
        });
    }

    // 4. Yaş Grubuna Göre Ortalama Kan Basıncı Grafiği (Bar)
    const bpAgeGroupCtx = document.getElementById('bpAgeGroupChart')?.getContext('2d');
    if (bpAgeGroupCtx) {
        new Chart(bpAgeGroupCtx, {
            type: 'bar', // Grafik türü
            data: {
                labels: <?php echo json_encode($chart_data['bp_age_labels'] ?? []); ?>, // X ekseni etiketleri (Yaş Grupları)
                datasets: [{
                    label: 'Ortalama Kan Basıncı',
                    data: <?php echo json_encode($chart_data['bp_age_averages'] ?? []); ?>, // Y ekseni verileri
                    backgroundColor: chartColors.c1 + 'B3', // Çubuk rengi (saydam)
                    borderColor: chartColors.c1, // Çubuk kenarlık rengi
                    borderWidth: 1, // Çubuk kenarlık kalınlığı
                    borderRadius: 3 // Çubuk köşe yuvarlaklığı
                }]
            },
            options: {
                ...commonChartOptions,
                scales: {
                    y: { beginAtZero: false, title: { display: true, text: 'Kan Basıncı (mmHg)' }, grid: { drawBorder: false } }, // Y ekseni 0'dan başlamasın (kan basıncı genelde 0 olmaz)
                    x: { grid: { display: false } }
                },
                plugins: {
                    ...commonChartOptions.plugins,
                    legend: { display: false } // Legend'ı gizle
                }
            }
        });
    }

   // 5. Ortalama Diyabet Riski Gösterge Grafiği (Doughnut - Yarım Daire)
    const diabetesRiskGaugeCtx = document.getElementById('diabetesRiskGaugeChart')?.getContext('2d');
    if (diabetesRiskGaugeCtx) {
        const riskPercentage = <?php echo $kpi_data['high_risk_percentage'] ?? 0; ?>; // PHP'den gelen yüksek risk yüzdesi
        
        new Chart(diabetesRiskGaugeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Yüksek Risk', 'Düşük/Orta Risk'],
                datasets: [{
                    data: [riskPercentage, 100 - riskPercentage], // Veri dilimleri
                    backgroundColor: [chartColors.riskHigh, chartColors.lightGrey], // Renkler (riskli kısım ve boş kısım)
                    borderWidth: 0, // Kenarlık yok
                    circumference: 180, // Grafiği yarım daire yap (360'ın yarısı)
                    rotation: 270 // Grafiği döndürerek alt yarıda başlat
                }]
            },
            options: {
                ...commonChartOptions,
                cutout: '75%', // Ortadaki boşluk oranı
                plugins: {
                    ...commonChartOptions.plugins,
                    tooltip: { enabled: false }, // Bu grafik için tooltip'i kapat
                    legend: { display: false }, // Legend'ı kapat
                    // Özel plugin ile ortada metin gösterme konfigürasyonu (plugin'in kendisi aşağıda tanımlı)
                    centerText: { 
                        display: true, // Metni göster
                        text: riskPercentage + '%', // Ana metin (yüzde)
                        fontStyle: 'bold 24px Inter', // Ana metin stili
                        color: chartColors.riskHigh, // Ana metin rengi
                        subText: 'Yüksek Risk', // Alt metin
                        subTextStyle: '12px Inter', // Alt metin stili
                        subTextColor: '#6c757d' // Alt metin rengi
                    }
                }
            },
            // Özel plugin: Grafiğin ortasına metin ekler
            plugins: [{
                id: 'centerText', // Plugin ID'si
                beforeDraw: function(chart) {
                    // Eğer plugin seçeneklerde devre dışı bırakılmışsa, çizme
                    if (chart.config.options.plugins.centerText && chart.config.options.plugins.centerText.display === false) {
                        return;
                    }
                    const ctx = chart.ctx; // Canvas context'ini al
                    ctx.save(); // Mevcut context durumunu kaydet
                    
                    // Metnin konumunu hesapla (grafik alanının merkezi)
                    const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                    // Yarım daire için Y pozisyonunu ayarla
                    const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2 + (chart.config.type === 'doughnut' && chart.config.data.datasets[0].circumference === 180 ? 20 : 0); 
                    
                    const mainTextConfig = chart.config.options.plugins.centerText || {}; // Plugin konfigürasyonunu al

                    // Ana yüzde değerini çiz
                    ctx.font = mainTextConfig.fontStyle || 'bold 24px Inter';
                    ctx.fillStyle = mainTextConfig.color || chartColors.riskHigh;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle'; 
                    ctx.fillText(mainTextConfig.text || (riskPercentage + '%'), centerX, centerY -5); // Biraz yukarı al
                    
                    // Alt metni çiz
                    ctx.font = mainTextConfig.subTextStyle || '12px Inter';
                    ctx.fillStyle = mainTextConfig.subTextColor || '#6c757d';
                    ctx.fillText(mainTextConfig.subText || 'Yüksek Risk', centerX, centerY + 15); // Ana metne göre konumlandır
                    
                    ctx.restore(); // Kaydedilmiş context durumunu geri yükle
                }
            }]
        });
    }

    // 6. İnsülin vs Glikoz Dağılım Grafiği (Scatter)
    const insulinGlucoseCtx = document.getElementById('insulinGlucoseScatterChart')?.getContext('2d');
    if (insulinGlucoseCtx) {
        new Chart(insulinGlucoseCtx, {
            type: 'scatter', // Grafik türü
            data: {
                datasets: [{
                    label: 'İnsülin vs Glikoz',
                    data: <?php echo json_encode($chart_data['insulin_glucose_data'] ?? []); ?>, // PHP'den gelen {x, y} veri noktaları
                    backgroundColor: chartColors.c4 + '80', // Nokta rengi (saydam)
                    pointRadius: 3.5, // Nokta yarıçapı
                    pointHoverRadius: 5.5, // Fare üzerine gelince nokta yarıçapı
                    pointBorderColor: chartColors.c4, // Nokta kenarlık rengi
                    pointHoverBorderColor: chartColors.c4, // Fare üzerine gelince nokta kenarlık rengi
                    pointBorderWidth: 1,
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                ...commonChartOptions,
                scales: {
                    x: { title: { display: true, text: 'Glikoz (mg/dL)' }, beginAtZero: true, grid: { drawBorder: false } }, // X ekseni (Glikoz)
                    y: { title: { display: true, text: 'İnsülin (mu U/ml)' }, beginAtZero: true, grid: { drawBorder: false } } // Y ekseni (İnsülin)
                },
                plugins: {
                    ...commonChartOptions.plugins,
                    legend: { display: false }, // Legend'ı gizle
                    tooltip: { // Özel tooltip formatı
                        callbacks: {
                            label: function(context) { // Tooltip metnini özelleştir
                                return `${context.dataset.label}: (G: ${context.parsed.x}, İ: ${context.parsed.y})`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 7. Kan Basıncı Dağılımı Grafiği (Bar - Yatay)
    const bpDistributionCtx = document.getElementById('bpDistributionChart')?.getContext('2d');
    if (bpDistributionCtx) {
        const bpDistLabels = <?php echo json_encode($chart_data['bp_dist_labels'] ?? []); ?>; // Kategori etiketleri
        const bpDistCounts = <?php echo json_encode($chart_data['bp_dist_counts'] ?? []); ?>; // Kategori sayıları
        const totalBpDistPatients = bpDistCounts.reduce((sum, count) => sum + count, 0); // Yüzde hesaplaması için toplam

        // Her kategori için dinamik renkler belirle
        const bpDistBackgroundColors = { // Renk eşleşmeleri
            'Düşük (<80)': chartColors.c1, 'Normal (80-120)': chartColors.c2,
            'Yüksek Normal (121-139)': chartColors.c3, 'Evre 1 Hipertansiyon (140-159)': chartColors.c5,
            'Evre 2+ Hipertansiyon (160+)': chartColors.accent, 'Bilinmiyor': chartColors.c6, 'Diğer': chartColors.c4
        };
        // Etiketlere göre dinamik arka plan ve kenarlık renkleri oluştur
        const dynamicBackgroundColors = bpDistLabels.map(label => (bpDistBackgroundColors[label] || chartColors.c6) + 'B3'); // Saydam arka plan
        const dynamicBorderColors = bpDistLabels.map(label => bpDistBackgroundColors[label] || chartColors.c6);

        new Chart(bpDistributionCtx, {
            type: 'bar',
            data: {
                labels: bpDistLabels,
                datasets: [{
                    label: 'Hasta Sayısı', data: bpDistCounts, backgroundColor: dynamicBackgroundColors,
                    borderColor: dynamicBorderColors, borderWidth: 1, borderRadius: 3,
                }]
            },
            options: {
                ...commonChartOptions, 
                indexAxis: 'y', // Grafiği yatay yap (çubuklar Y ekseninde)
                scales: {
                    x: { beginAtZero: true, title: { display: true, text: 'Hasta Sayısı', font: { size: 10 } }, grid: { drawBorder: false }, ticks: { font: { size: 9 }, precision: 0 } }, // X ekseni (Hasta Sayısı)
                    y: { grid: { display: false }, ticks: { font: { size: 10 }, padding: 6, autoSkip: false } } // Y ekseni (Kategoriler)
                },
                plugins: {
                    ...commonChartOptions.plugins,
                    legend: { display: false }, // Legend'ı gizle
                    tooltip: { // Özel tooltip formatı
                        callbacks: {
                            label: function(context) { // Tooltip'te yüzdeyi de göster
                                let label = context.dataset.label || ''; if (label) { label += ': '; }
                                const value = context.parsed.x; // Yatay olduğu için x
                                if (value !== null) {
                                    label += value;
                                    if (totalBpDistPatients > 0) {
                                        const percentage = (value / totalBpDistPatients) * 100;
                                        label += ` (${percentage.toFixed(1)}%)`;
                                    }
                                } return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // "Diyabet Durumu" (Outcome) ile ilgili JavaScript kodları daha önceki bir adımda buradan kaldırılmıştı.

});
</script>
</body>
</html>