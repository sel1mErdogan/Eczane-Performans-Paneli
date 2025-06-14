<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Selim Soft</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">

  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script src="https://code.highcharts.com/highcharts-more.js"></script>
  <script src="https://code.highcharts.com/modules/exporting.js"></script>
  <script src="https://code.highcharts.com/modules/accessibility.js"></script>
  <script type="module" src="https://cdn.jsdelivr.net/npm/@ionic/core/dist/ionic/ionic.esm.js"></script>
  <script nomodule src="https://cdn.jsdelivr.net/npm/@ionic/core/dist/ionic/ionic.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ionic/core/css/ionic.bundle.css" />


  <style>
    .small-box-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      padding: 15px;
    }

    .small-box {
      flex: 1;
      min-width: 250px;
    }

    .chart-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin: 20px 0;
    }

    .chart-box {
      flex: 1;
      min-width: 400px;
    }
  </style>
</head>

<STyle>
  .highcharts-figure,
  .highcharts-data-table table {
    min-width: 320px;
    max-width: 800px;
    margin: 1em auto;
  }

  .highcharts-data-table table {
    font-family: Verdana, sans-serif;
    border-collapse: collapse;
    border: 1px solid #ebebeb;
    margin: 10px auto;
    text-align: center;
    width: 100%;
    max-width: 500px;
  }

  .highcharts-data-table caption {
    padding: 1em 0;
    font-size: 1.2em;
    color: #555;
  }

  .highcharts-data-table th {
    font-weight: 600;
    padding: 0.5em;
  }

  .highcharts-data-table td,
  .highcharts-data-table th,
  .highcharts-data-table caption {
    padding: 0.5em;
  }

  .highcharts-data-table thead tr,
  .highcharts-data-table tr:nth-child(even) {
    background: #f8f8f8;
  }

  .highcharts-data-table tr:hover {
    background: #f1f7ff;
  }

  input[type="number"] {
    min-width: 50px;
  }

  .highcharts-description {
    margin: 0.3rem 10px;
  }

  #container {
    height: 400px;
  }

  .highcharts-figure,
  .highcharts-data-table table {
    min-width: 310px;
    max-width: 800px;
    margin: 1em auto;
  }

  .highcharts-data-table table {
    font-family: Verdana, sans-serif;
    border-collapse: collapse;
    border: 1px solid #ebebeb;
    margin: 10px auto;
    text-align: center;
    width: 100%;
    max-width: 500px;
  }

  .highcharts-data-table caption {
    padding: 1em 0;
    font-size: 1.2em;
    color: #555;
  }

  .highcharts-data-table th {
    font-weight: 600;
    padding: 0.5em;
  }

  .highcharts-data-table td,
  .highcharts-data-table th,
  .highcharts-data-table caption {
    padding: 0.5em;
  }

  .highcharts-data-table thead tr,
  .highcharts-data-table tbody tr:nth-child(even) {
    background: #f8f8f8;
  }

  .highcharts-data-table tr:hover {
    background: #f1f7ff;
  }

  .highcharts-description {
    margin: 0.3rem 10px;
  }
</STyle>

<body class="hold-transition sidebar-mini">
  <?php
  $servername = 'localhost';         // ya da $host yerine bunu kullan
  $dbname = 'elektirikliaraclar';    // Veritabanı adı
  $username = 'root';                // Kullanıcı adı
  $password = 'selim5353';           // Şifre
  
  // Bağlantı oluştur
  $conn = new mysqli($servername, $username, $password, $dbname);


  // Bağlantı kontrol
  if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
  }
  ?>
  <div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a href="index3.html" class="nav-link">Anasayfa</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a href="#" class="nav-link">İletişim</a>
        </li>
      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">
          <a class="nav-link" data-widget="navbar-search" href="#" role="button">
            <i class="fas fa-search"></i>
          </a>
          <div class="navbar-search-block">
            <form class="form-inline">
              <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                  <button class="btn btn-navbar" type="submit">
                    <i class="fas fa-search"></i>
                  </button>
                  <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </li>

        <!-- Messages Dropdown Menu -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-comments"></i>
            <span class="badge badge-danger navbar-badge">3</span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <a href="#" class="dropdown-item">
              <!-- Message Start -->
              <div class="media">
                <img src="dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                <div class="media-body">
                  <h3 class="dropdown-item-title">
                    Brad Diesel
                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                  </h3>
                  <p class="text-sm">Call me whenever you can...</p>
                  <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                </div>
              </div>
              <!-- Message End -->
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <!-- Message Start -->
              <div class="media">
                <img src="dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                <div class="media-body">
                  <h3 class="dropdown-item-title">
                    John Pierce
                    <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                  </h3>
                  <p class="text-sm">I got your message bro</p>
                  <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                </div>
              </div>
              <!-- Message End -->
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <!-- Message Start -->
              <div class="media">
                <img src="dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
                <div class="media-body">
                  <h3 class="dropdown-item-title">
                    Nora Silvester
                    <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                  </h3>
                  <p class="text-sm">The subject goes here</p>
                  <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
                </div>
              </div>
              <!-- Message End -->
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
          </div>
        </li>
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <span class="badge badge-warning navbar-badge">15</span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-header">15 Notifications</span>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <i class="fas fa-envelope mr-2"></i> 4 new messages
              <span class="float-right text-muted text-sm">3 mins</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <i class="fas fa-users mr-2"></i> 8 friend requests
              <span class="float-right text-muted text-sm">12 hours</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
              <i class="fas fa-file mr-2"></i> 3 new reports
              <span class="float-right text-muted text-sm">2 days</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-widget="fullscreen" href="#" role="button">
            <i class="fas fa-expand-arrows-alt"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
            <i class="fas fa-th-large"></i>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <!-- Brand Logo -->
      <a href="index3.html" class="brand-link">
        <img src="dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
          style="opacity: .8">
        <span class="brand-text font-weight-light">selim Soft</span>
      </a>

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="image">
          </div>
          <div class="info">
            <a href="#" class="d-block">selim erdogan</a>
          </div>
        </div>

        <!-- SidebarSearch Form -->
        <div class="form-inline">
          <div class="input-group" data-widget="sidebar-search">
            <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-sidebar">
                <i class="fas fa-search fa-fw"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
            <li class="nav-item menu-open">
              <a href="dashboard.php" class="nav-link">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>
                  Gösterge Paneli
                </p>
              </a>
            </li>
          </ul>
        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Gösterge Paneli</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="#">Ana Sayfa</a></li>
                <li class="breadcrumb-item active">Gösterge Paneli</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>

      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">
          <div class="small-box-container">
            <!-- Hizmet Veren Şirket Sayısı -->
            <div class="small-box bg-info">
              <div class="inner">
                <h3>
                  <?php
                  $veriler = include('data.php');
                  $hizmetVerenSayisi = count($veriler['hizmet_veren_sirketler']);
                  echo $hizmetVerenSayisi;
                  ?>
                </h3>
                <p>Hizmet Veren Şirket Sayısı</p>
              </div>
              <div class="icon">
                <i class="fa fa-building"></i>
              </div>
              <a href="#" class="small-box-footer">
                Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>

            <!-- Benzersiz Marka Sayısı -->
            <div class="small-box bg-danger">
              <div class="inner">
                <h3>
                  <?php
                  $benzersizMarkalar = array_unique($veriler['markalar']);
                  $benzersizMarkaSayisi = count($benzersizMarkalar);
                  echo $benzersizMarkaSayisi;
                  ?>
                </h3>
                <p>Benzersiz Marka Sayısı</p>
              </div>
              <div class="icon">
                <i class="fa fa-tags"></i>
              </div>
              <a href="#" class="small-box-footer">
                Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>

            <!-- Ortalama Fiyat -->
            <div class="small-box bg-warning">
              <div class="inner">
                <h3>
                  <?php
                  $toplamFiyat = 0;
                  $toplamSayisi = 0;
                  foreach ($veriler['fiyatlar'] as $fiyat) {
                    if ($fiyat > 0) {
                      $toplamFiyat += $fiyat;
                      $toplamSayisi++;
                    }
                  }
                  $ortalamaFiyat = ($toplamSayisi > 0) ? $toplamFiyat / $toplamSayisi : 0;
                  echo number_format($ortalamaFiyat, 2);
                  ?>
                </h3>
                <p>Ortalama Fiyat</p>
              </div>
              <div class="icon">
                <i class="fa fa-money-bill-wave"></i>
              </div>
              <a href="#" class="small-box-footer">
                Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>

            <!-- Toplam Araç Sayısı -->
            <div class="small-box bg-success">
              <div class="inner">
                <h3>
                  <?php
                  $aracSayisi = $veriler['toplam'];
                  echo $aracSayisi;
                  ?>
                </h3>
                <p>Toplam Araç Sayısı</p>
              </div>
              <div class="icon">
                <i class="fa fa-car"></i>
              </div>
              <a href="#" class="small-box-footer">
                Daha fazla bilgi <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>


          <?php
          // First query for pie chart
          $sql_pie = "SELECT elektirik_arac_tipi, b.arac_tipi, COUNT(elektirik_arac_tipi) as aracTipiSayisi 
                     FROM araclar2 AS a 
                     INNER JOIN elektiricli_arac_tipi AS b ON a.elektirik_arac_tipi = b.no 
                     GROUP BY elektirik_arac_tipi";
          $pie_result = $conn->query($sql_pie);

          // Second query for column chart (example with different data)
          $sql_column = "SELECT cafv, b.Cafvs, COUNT(cafv) as aracCafv FROM araclar2 AS a 
                          inner join cafv AS b on a.cafv = b.no GROUP BY cafv;";
          $column_result = $conn->query($sql_column);
          ?>


          <?php
                        $yilmaxmin = mysqli_query($conn, "select MIN(model_yili) min_deger, MAX(model_yili) max_deger from araclar;");
                        $rowyilmaxmin = mysqli_fetch_object($yilmaxmin);
                        ?>
          <Div class="row">
            <div class="col-md-12">
              <div class="card card-info card-outline">
                <div class="card-header">
                  MODEL YILI
                </div>


                <div class="card-body">
                  <div style="display:flex; align-items: center; gap: 10; margin-bottom: 20px;">
                    <input type="number" id="minDeger" style="margin-right:20px ;">
                    <ion-range id="range" dual-knobs="true" min="0" max="100"></ion-range>
                    <input type="number" id="maksDeger" style="margin-right:20px ;">
                  </div>


                </div>
              </div>
            </Div>
            <script>
              const range = document.getElementById('range');
              const maksdeger = document.getElementById('maksDeger');
              const mindeger = document.getElementById('minDeger');

              const baslangicRange = { minD: 1999, maksD: 2025 };

              window.addEventListener('DOMContentLoaded', () => {
                maksdeger.value = baslangicRange.maksD;
                mindeger.value = baslangicRange.minD;

                // İlk range değeri ayarlanıyor
                range.value = {
                  lower: baslangicRange.minD,
                  upper: baslangicRange.maksD
                };
              });

              range.addEventListener('ionChange', (ev) => {
                const { lower, upper } = ev.detail.value;
                maksdeger.value = upper;
                mindeger.value = lower;
              });

              const updateRange = () => {
                range.value = {
                  lower: parseInt(mindeger.value) || 0,
                  upper: parseInt(maksdeger.value) || 0
                };
              };

              mindeger.addEventListener('input', updateRange);
              maksdeger.addEventListener('input', updateRange);
            </script>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card card-danger cart-outline">
                <div class="card-header">
                  <h5 class="m-0">Araç İstatistikleri</h5>
                </div>
                <div class="card-body">
                  <div class="chart-container">
                    <!-- Pie Chart -->
                    <div class="chart-box">
                      <figure class="highcharts-figure">
                        <div id="pie-container"></div>
                        <p class="highcharts-description">
                          Araç tiplerine göre dağılımı gösteren pasta grafiği.
                        </p>
                      </figure>
                    </div>

                    <!-- Column Chart -->
                    <div class="chart-box">
                      <figure class="highcharts-figure">
                        <div id="column-container"></div>
                        <p class="highcharts-description">
                          Araç tiplerine göre ortalama fiyatları gösteren sütun grafiği.
                        </p>
                      </figure>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div><!-- /.container-fluid -->
      </div>
      <figure class="highcharts-figure">
        <div id="price-container"></div>
        <p class="highcharts-description">
          Chart demonstrating using an arearange series in combination with a line
          series. In this case, the arearange series is used to visualize the
          temperature range per day, while the line series shows the average
          temperature.
        </p>
      </figure>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
      <!-- To the right -->
      <div class="float-right d-none d-sm-inline">
        Anything you want
      </div>
      <!-- Default to the left -->
      <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">SELİM Soft</a>.</strong> All rights reserved.
    </footer>
  </div>
  <!-- ./wrapper -->

  <!-- REQUIRED SCRIPTS -->
  <!-- jQuery -->
  <script>
    // Pie Chart
    Highcharts.chart('pie-container', {
      chart: {
        type: 'pie'
      },
      title: {
        text: 'Araç Tipi Dağılımı'
      },
      tooltip: {
        valueSuffix: ' adet'
      },
      subtitle: {
        text: 'Elektrikli araç tiplerinin dağılımı'
      },
      plotOptions: {
        pie: {
          allowPointSelect: true,
          cursor: 'pointer',
          dataLabels: [{
            enabled: true,
            distance: 20
          }, {
            enabled: true,
            distance: -40,
            format: '{point.percentage:.1f}%',
            style: {
              fontSize: '1.2em',
              textOutline: 'none',
              opacity: 0.7
            },
            filter: {
              operator: '>',
              property: 'percentage',
              value: 10
            }
          }]
        }
      },
      series: [
        {
          name: 'Araç Sayısı',
          colorByPoint: true,
          data:
            [
              <?php while ($satir = $pie_result->fetch_assoc()) { ?>
                      { name: '<?php echo $satir["arac_tipi"]; ?>', y: <?php echo $satir["aracTipiSayisi"]; ?> },
              <?php } ?>
            ]
        }
      ]
    });

    // Column Chart
    Highcharts.chart('column-container', {
      chart: {
        type: 'column'
      },
      title: {
        text: 'CAFV Dağılımı'
      },
      subtitle: {
        text: 'Farklı CAFV tiplerinin dağılımı'
      },
      xAxis: {
        type: 'category',
        labels: {
          rotation: -45,
          style: {
            fontSize: '13px',
            fontFamily: 'Verdana, sans-serif'
          }
        }
      },
      yAxis: {
        min: 0,
        title: {
          text: 'Araç Sayısı'
        }
      },
      legend: {
        enabled: false
      },
      tooltip: {
        pointFormat: 'Araç Sayısı: <b>{point.y}</b>'
      },
      series: [{
        name: 'Araç Sayısı',
        colorByPoint: true,
        colors: ['#FF5733', '#33FF57', '#3357FF', '#F3FF33', '#FF33F3'],
        data: [
          <?php
          $column_result = $conn->query($sql_column);
          while ($row = $column_result->fetch_assoc()) {
            ?>
            ['<?php echo $row["Cafvs"]; ?>', <?php echo $row["aracCafv"]; ?>],
          <?php } ?>
        ],
        dataLabels: {
          enabled: true,
          rotation: -90,
          color: '#FFFFFF',
          align: 'right',
          format: '{point.y}',
          y: 10,
          style: {
            fontSize: '13px',
            fontFamily: 'Verdana, sans-serif'
          }
        }
      }]
    });

    // Vehicle Price Range Chart (using your SQL query)
    // Vehicle Price Range Chart (using your SQL query)
    <?php
    $price_result = $conn->query("
    SELECT model_yili, MAX(fiyat) AS max_fiyat, MIN(fiyat) AS min_fiyat, ROUND(AVG(fiyat)) AS ortalama_fiyat 
    FROM araclar2 
    WHERE fiyat > 0 
    GROUP BY model_yili 
    ORDER BY model_yili
");

    $model_yillari = [];
    $ranges = [];
    $averages = [];

    while ($row = $price_result->fetch_assoc()) {
      $model_yillari[] = $row['model_yili'];
      $ranges[] = [$row['min_fiyat'], $row['max_fiyat']];
      $averages[] = (int) $row['ortalama_fiyat'];
    }
    ?>

    Highcharts.chart('price-container', {
      title: {
        text: 'Araç Fiyat Dağılımı',
        align: 'left'
      },
      subtitle: {
        text: 'Model yıllarına göre maksimum, minimum ve ortalama fiyatlar',
        align: 'left'
      },
      xAxis: {
        title: {
          text: 'Model Yılı'
        },
        categories: [
          <?php
          $price_result = $conn->query("SELECT model_yili, MAX(fiyat) AS max_fiyat, MIN(fiyat) AS min_fiyat, ROUND(AVG(fiyat)) AS ortalama_fiyat FROM araclar2 WHERE fiyat > 0 GROUP BY model_yili");
          while ($row = $price_result->fetch_assoc()) {
            echo "'" . $row['model_yili'] . "', ";
          }
          ?>
        ]
      },
      yAxis: {
        title: {
          text: 'Fiyat (₺)'
        }
      },
      tooltip: {
        shared: true,
        valueSuffix: ' ₺'
      },
      series: [{
        name: 'Maksimum Fiyat',
        data: [
          <?php
          $price_result->data_seek(0);
          while ($row = $price_result->fetch_assoc()) {
            echo $row['max_fiyat'] . ", ";
          }
          ?>
        ],
        color: '#FF5733',
        type: 'column'
      }, {
        name: 'Ortalama Fiyat',
        data: [
          <?php
          $price_result->data_seek(0);
          while ($row = $price_result->fetch_assoc()) {
            echo $row['ortalama_fiyat'] . ", ";
          }
          ?>
        ],
        color: '#33FF57',
        type: 'spline'
      }, {
        name: 'Minimum Fiyat',
        data: [
          <?php
          $price_result->data_seek(0);
          while ($row = $price_result->fetch_assoc()) {
            echo $row['min_fiyat'] . ", ";
          }
          ?>
        ],
        color: '#3357FF',
        type: 'column'
      }]
    });
    Highcharts.chart('container', {

      title: {
        text: 'April temperatures in Nesbyen, 2024',
        align: 'left'
      },

      subtitle: {
        text: 'Source: ' +
          '<a href="https://www.yr.no/nb/historikk/graf/1-113585/Norge/Buskerud/Nesbyen/Nesbyen?q=2024-04"' +
          'target="_blank">YR</a>',
        align: 'left'
      },

      xAxis: {
        type: 'datetime',
        accessibility: {
          rangeDescription: 'Range: April 1st 2022 to April 30th 2024.'
        }
      },

      yAxis: {
        title: {
          text: null
        }
      },

      tooltip: {
        crosshairs: true,
        shared: true,
        valueSuffix: '°C'
      },

      plotOptions: {
        series: {
          pointStart: '2024-05-01',
          pointIntervalUnit: 'day'
        }
      },

      series: [{
        name: 'Temperature',
        data: averages,
        zIndex: 1,
        marker: {
          fillColor: 'white',
          lineWidth: 2,
          lineColor: Highcharts.getOptions().colors[0]
        }
      }, {
        name: 'Range',
        data: ranges,
        type: 'arearange',
        lineWidth: 0,
        linkedTo: ':previous',
        color: Highcharts.getOptions().colors[0],
        fillOpacity: 0.3,
        zIndex: 0,
        marker: {
          enabled: false
        }
      }]
    });

  </script>
  <script src="plugins/jquery/jquery.min.js"></script>
  <!-- Bootstrap 4 -->
  <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
  <!-- AdminLTE App -->
  <script src="dist/js/adminlte.min.js"></script>
</body>

</html>