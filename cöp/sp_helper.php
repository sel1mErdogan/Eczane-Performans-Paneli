<?php
/**
 * sp_helper.php
 * Stored Procedure'lerden veri çekmek için yardımcı fonksiyonlar.
 */

/**
 * Tek bir değer döndüren bir Stored Procedure'ü çağırır.
 *
 * @param mysqli $conn Veritabanı bağlantı nesnesi.
 * @param string $procedure_call Çağrılacak prosedür (örn: "GetKpi_ToplamEczane()").
 * @param string $column_name Sonuç setindeki sütun adı.
 * @return mixed Dönen değer veya 0.
 */
function fetch_single_value($conn, $procedure_call, $column_name = 'value') {
    // Önceki sonuç setlerini temizle
    while($conn->more_results() && $conn->next_result()) {;}

    if ($result = $conn->query("CALL " . $procedure_call)) {
        $row = $result->fetch_assoc();
        $result->free();
        return $row[$column_name] ?? 0;
    }
    return 0;
}

/**
 * Çoklu satır döndüren bir Stored Procedure'ü çağırır.
 *
 * @param mysqli $conn Veritabanı bağlantı nesnesi.
 * @param string $procedure_call Çağrılacak prosedür (örn: "GetTable_SonReceteler(5)").
 * @return array Sonuçları içeren bir dizi.
 */
function fetch_multiple_rows($conn, $procedure_call) {
    // Önceki sonuçları temizle
    while($conn->more_results() && $conn->next_result()) {;}
    
    $data = [];
    if ($result = $conn->query("CALL " . $procedure_call)) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $result->free();
    }
    return $data;
}
?>