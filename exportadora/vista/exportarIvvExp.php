<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();
$IDICARGA = isset($_GET['ICARGA']) ? (int)$_GET['ICARGA'] : 0;

function xmlTexto($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function excelNumero($valor, $decimales = 2) {
    return number_format((float)$valor, $decimales, '.', '');
}

function celdaTexto($valor) {
    return '<Cell><Data ss:Type="String">' . xmlTexto($valor) . '</Data></Cell>';
}

function celdaNumero($valor, $decimales = 2) {
    return '<Cell><Data ss:Type="Number">' . excelNumero($valor, $decimales) . '</Data></Cell>';
}

if ($IDICARGA <= 0) {
    die('Referencia no indicada');
}

$stmtCab = $db->prepare("
    SELECT
        i.ID_ICARGA,
        i.NUMERO_ICARGA,
        i.NREFERENCIA_ICARGA,
        i.NCONTENEDOR_ICARGA,
        i.DUS_ICARGA,
        IFNULL(merc.NOMBRE_MERCADO, '') AS NOMBRE_MERCADO,
        IFNULL(cons.NOMBRE_CONSIGNATARIO, '') AS NOMBRE_CONSIGNATARIO,
        IFNULL(ivv.NUMERO_IVV, '') AS NUMERO_IVV,
        IFNULL(ivv.ESTADO_IVV, 'PENDIENTE') AS ESTADO_IVV,
        COALESCE(ivv.TOTAL_INVOICE_USD, inv.TOTAL_INVOICE_USD, 0) AS TOTAL_INVOICE_USD,
        COALESCE(ivv.TOTAL_LIQUIDACION_USD, liq.TOTAL_LIQUIDACION_USD, 0) AS TOTAL_LIQUIDACION_USD,
        COALESCE(ivv.DIFERENCIA_USD, IFNULL(liq.TOTAL_LIQUIDACION_USD, 0) - IFNULL(inv.TOTAL_INVOICE_USD, 0), 0) AS DIFERENCIA_USD,
        IFNULL(ivv.DOCUMENTO_SUGERIDO, '') AS DOCUMENTO_SUGERIDO
    FROM fruta_icarga i
    LEFT JOIN fruta_mercado merc ON merc.ID_MERCADO = i.ID_MERCADO
    LEFT JOIN fruta_consignatario cons ON cons.ID_CONSIGNATARIO = i.ID_CONSIGNATARIO
    LEFT JOIN (
        SELECT
            inv.ID_ICARGA,
            SUM(IFNULL(det.TOTAL_LINEA, 0)) *
                CASE WHEN inv.MONEDA_INVOICE = 'EUR' THEN IFNULL(inv.TIPO_CAMBIO_USD, 1) ELSE 1 END AS TOTAL_INVOICE_USD
        FROM exportadora_invoice inv
        INNER JOIN (
            SELECT ID_ICARGA, MAX(ID_INVOICE) AS ID_INVOICE
            FROM exportadora_invoice
            WHERE ESTADO_REGISTRO = 1
            AND ESTADO_INVOICE = 'CONFIRMADA'
            GROUP BY ID_ICARGA
        ) ult ON ult.ID_INVOICE = inv.ID_INVOICE
        INNER JOIN exportadora_invoice_detalle det ON det.ID_INVOICE = inv.ID_INVOICE
            AND det.ESTADO_REGISTRO = 1
        GROUP BY inv.ID_ICARGA, inv.ID_INVOICE, inv.MONEDA_INVOICE, inv.TIPO_CAMBIO_USD
    ) inv ON inv.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN (
        SELECT ID_ICARGA, SUM(IFNULL(VENTA_USD_NETO, 0)) AS TOTAL_LIQUIDACION_USD
        FROM view_liquidacion_exportacion
        WHERE ID_EMPRESA = ?
        AND ID_TEMPORADA = ?
        GROUP BY ID_ICARGA
    ) liq ON liq.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN exportadora_ivv ivv ON ivv.ID_ICARGA = i.ID_ICARGA
        AND ivv.ESTADO_REGISTRO = 1
    WHERE i.ID_ICARGA = ?
    AND i.ID_EMPRESA = ?
    AND i.ID_TEMPORADA = ?
    LIMIT 1
");
$stmtCab->execute([$EMPRESAS, $TEMPORADAS, $IDICARGA, $EMPRESAS, $TEMPORADAS]);
$cabecera = $stmtCab->fetch(PDO::FETCH_ASSOC);
if (!$cabecera) {
    die('Referencia no encontrada');
}
$diferencia = (float)$cabecera['DIFERENCIA_USD'];
if ($cabecera['DOCUMENTO_SUGERIDO'] === '') {
    if (abs($diferencia) <= 1.00) {
        $cabecera['DOCUMENTO_SUGERIDO'] = 'SIN_NOTA';
    } else {
        $cabecera['DOCUMENTO_SUGERIDO'] = $diferencia > 0 ? 'NOTA_DEBITO' : 'NOTA_CREDITO';
    }
}

$stmtDet = $db->prepare("
    SELECT
        NOMBRE_PRODUCTOR,
        NOMBRE_ESTANDAR,
        NOMBRE_VESPECIES,
        NOMBRE_TCALIBRE,
        SUM(CANTIDAD_ENVASE) AS CAJAS,
        SUM(KILOS_NETO) AS KILOS_NETO,
        SUM(VENTA_USD_BRUTO) AS VENTA_USD_BRUTO,
        SUM(COMISION_PRORRATEADA) AS COMISION,
        SUM(GASTOS_PRORRATEADOS) AS GASTOS,
        SUM(VENTA_USD_NETO) AS VENTA_USD_NETO
    FROM view_liquidacion_exportacion
    WHERE ID_EMPRESA = ?
    AND ID_TEMPORADA = ?
    AND ID_ICARGA = ?
    GROUP BY ID_PRODUCTOR, ID_ESTANDAR, ID_VESPECIES, ID_TCALIBRE
    ORDER BY NOMBRE_PRODUCTOR, NOMBRE_ESTANDAR, NOMBRE_VESPECIES, NOMBRE_TCALIBRE
");
$stmtDet->execute([$EMPRESAS, $TEMPORADAS, $IDICARGA]);
$detalle = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="ivv_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $cabecera['NREFERENCIA_ICARGA'] ?? $IDICARGA) . '.xls"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fwrite($out, '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n");
fwrite($out, '<?mso-application progid="Excel.Sheet"?>' . "\r\n");
fwrite($out, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\r\n");
fwrite($out, '<Worksheet ss:Name="IVV"><Table>' . "\r\n");

foreach ([
    ['IVV', $cabecera['NUMERO_IVV'] ?? ''],
    ['Estado', $cabecera['ESTADO_IVV'] ?? 'PENDIENTE'],
    ['Referencia', $cabecera['NREFERENCIA_ICARGA'] ?? ''],
    ['Instructivo', $cabecera['NUMERO_ICARGA'] ?? ''],
    ['Mercado', $cabecera['NOMBRE_MERCADO'] ?? ''],
    ['Cliente', $cabecera['NOMBRE_CONSIGNATARIO'] ?? ''],
    ['DUS', $cabecera['DUS_ICARGA'] ?? ''],
    ['Total invoice US$', $cabecera['TOTAL_INVOICE_USD'] ?? 0],
    ['Total liquidacion US$', $cabecera['TOTAL_LIQUIDACION_USD'] ?? 0],
    ['Diferencia US$', $cabecera['DIFERENCIA_USD'] ?? 0],
    ['Documento sugerido', $cabecera['DOCUMENTO_SUGERIDO'] ?? '']
] as $row) {
    fwrite($out, '<Row>' . celdaTexto($row[0]));
    if (is_numeric($row[1])) {
        fwrite($out, celdaNumero($row[1], 2));
    } else {
        fwrite($out, celdaTexto($row[1]));
    }
    fwrite($out, "</Row>\r\n");
}

fwrite($out, '<Row></Row>' . "\r\n");
fwrite($out, '<Row>');
foreach (['Productor', 'Estandar', 'Variedad', 'Calibre', 'Cajas', 'Kg Neto', 'Venta Bruta US$', 'Comision', 'Gastos', 'Venta Neta US$'] as $titulo) {
    fwrite($out, celdaTexto($titulo));
}
fwrite($out, "</Row>\r\n");

foreach ($detalle as $d) {
    fwrite($out, '<Row>');
    fwrite($out, celdaTexto($d['NOMBRE_PRODUCTOR']));
    fwrite($out, celdaTexto($d['NOMBRE_ESTANDAR']));
    fwrite($out, celdaTexto($d['NOMBRE_VESPECIES']));
    fwrite($out, celdaTexto($d['NOMBRE_TCALIBRE']));
    fwrite($out, celdaNumero($d['CAJAS'], 0));
    fwrite($out, celdaNumero($d['KILOS_NETO'], 2));
    fwrite($out, celdaNumero($d['VENTA_USD_BRUTO'], 2));
    fwrite($out, celdaNumero($d['COMISION'], 2));
    fwrite($out, celdaNumero($d['GASTOS'], 2));
    fwrite($out, celdaNumero($d['VENTA_USD_NETO'], 2));
    fwrite($out, "</Row>\r\n");
}

fwrite($out, '</Table></Worksheet></Workbook>');
fclose($out);
exit;
