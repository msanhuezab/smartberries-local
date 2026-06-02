<?php

include_once __DIR__ . '/../config/BDCONFIG.php';

$IDCONTROL = $_GET['parametro'] ?? '';
if ($IDCONTROL === '') {
    die('Parametro no informado.');
}

function calidadPdfTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function calidadPdfNumero($valor, $decimales = 1) {
    if ($valor === null || $valor === '') {
        return '';
    }
    return number_format((float) $valor, $decimales, ',', '.');
}

function calidadPdfTablaDetalle($titulo, $detalles, $grupo, $unidad = '%') {
    $filas = '';
    $total = 0;
    foreach ($detalles as $detalle) {
        if ($detalle['TIPO_PARAMETRO'] !== $grupo) {
            continue;
        }
        $valor = (float) $detalle['VALOR_NUMERICO'];
        $total += $valor;
        $filas .= '<tr>
            <td>' . calidadPdfTexto($detalle['NOMBRE_PARAMETRO']) . '</td>
            <td class="center">' . calidadPdfNumero($valor, 1) . '</td>
        </tr>';
    }
    if ($filas === '') {
        $filas = '<tr><td colspan="2" class="center muted">Sin datos</td></tr>';
    }
    $totalHtml = '';
    if ($grupo === 'DEFECTOS_CONDICION' || $grupo === 'DEFECTOS_CALIDAD') {
        $totalHtml = '<tr class="total"><td>TOTAL</td><td class="center">' . calidadPdfNumero($total, 1) . '</td></tr>';
    }
    return '<table class="section-table">
        <thead>
            <tr><th>' . calidadPdfTexto($titulo) . '</th><th class="center">' . $unidad . '</th></tr>
        </thead>
        <tbody>' . $filas . $totalHtml . '</tbody>
    </table>';
}

function calidadPdfBarrasCalibre($detalles) {
    $html = '';
    foreach ($detalles as $detalle) {
        if ($detalle['TIPO_PARAMETRO'] !== 'CALIBRES') {
            continue;
        }
        $valor = max(0, (float) $detalle['VALOR_NUMERICO']);
        $ancho = min(100, $valor);
        $html .= '<tr>
            <td class="label-bar">' . calidadPdfTexto($detalle['NOMBRE_PARAMETRO']) . ' : ' . calidadPdfNumero($valor, 1) . ' %</td>
            <td><div class="bar"><span style="width:' . $ancho . '%"></span></div></td>
        </tr>';
    }
    if ($html === '') {
        $html = '<tr><td colspan="2" class="center muted">Sin datos</td></tr>';
    }
    return '<table class="bars-table">
        <thead><tr><th colspan="2">DISTRIBUCION DE CALIBRES</th></tr></thead>
        <tbody>' . $html . '</tbody>
    </table>';
}

try {
    $conexion = BDCONFIG::conectar();
    $controlStmt = $conexion->prepare("SELECT
            C.*,
            I.NOMBRE_INSPECTOR,
            EMP.RAZON_SOCIAL_EMPRESA,
            EMP.NOMBRE_EMPRESA,
            PLA.NOMBRE_PLANTA,
            ESP.NOMBRE_ESPECIES,
            R.NUMERO_GUIA_RECEPCION,
            R.FECHA_RECEPCION,
            GROUP_CONCAT(DISTINCT F.FOLIO_AUXILIAR ORDER BY F.FOLIO_AUXILIAR ASC SEPARATOR ', ') AS FOLIOS,
            GROUP_CONCAT(DISTINCT P.NOMBRE_PRODUCTOR ORDER BY P.NOMBRE_PRODUCTOR ASC SEPARATOR ', ') AS PRODUCTORES,
            GROUP_CONCAT(DISTINCT P.CSG_PRODUCTOR ORDER BY P.CSG_PRODUCTOR ASC SEPARATOR ', ') AS CSG
        FROM fruta_calidad_control C
        LEFT JOIN fruta_calidad_inspector I ON I.ID_CALIDAD_INSPECTOR = C.ID_CALIDAD_INSPECTOR
        LEFT JOIN principal_empresa EMP ON EMP.ID_EMPRESA = C.ID_EMPRESA
        LEFT JOIN principal_planta PLA ON PLA.ID_PLANTA = C.ID_PLANTA
        LEFT JOIN fruta_especies ESP ON ESP.ID_ESPECIES = C.ID_ESPECIES
        LEFT JOIN fruta_recepcionmp R ON R.ID_RECEPCION = C.ID_OPERACION
        LEFT JOIN fruta_calidad_control_folio F ON F.ID_CALIDAD_CONTROL = C.ID_CALIDAD_CONTROL AND F.ESTADO_REGISTRO = 1
        LEFT JOIN fruta_eximateriaprima EX ON EX.ID_EXIMATERIAPRIMA = F.ID_EXISTENCIA
        LEFT JOIN fruta_productor P ON P.ID_PRODUCTOR = EX.ID_PRODUCTOR
        WHERE C.ID_CALIDAD_CONTROL = ?
        AND C.ESTADO_REGISTRO = 1
        GROUP BY C.ID_CALIDAD_CONTROL
        LIMIT 1;");
    $controlStmt->execute([$IDCONTROL]);
    $controles = $controlStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($controles)) {
        die('Control de calidad no encontrado.');
    }
    $control = $controles[0];
    if ($control['ESTADO_CONTROL'] !== 'CERRADO') {
        die('El informe PDF solo esta disponible para controles cerrados.');
    }

    $detalleStmt = $conexion->prepare("SELECT *
        FROM fruta_calidad_control_detalle
        WHERE ID_CALIDAD_CONTROL = ?
        AND ESTADO_REGISTRO = 1
        ORDER BY TIPO_PARAMETRO ASC, ID_CALIDAD_CONTROL_DETALLE ASC;");
    $detalleStmt->execute([$IDCONTROL]);
    $detalles = $detalleStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die($e->getMessage());
}

$empresa = $control['RAZON_SOCIAL_EMPRESA'] ?: $control['NOMBRE_EMPRESA'];
$fechaInforme = date('d-m-Y H:i:s');
$folios = $control['MODO_INGRESO'] === 'AGRUPADO' ? 'Recepcion completa' : $control['FOLIOS'];

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #000; }
.top { width: 100%; margin-bottom: 14px; }
.brand { font-size: 18px; font-weight: bold; color: #0b3768; }
.subtitle { color: #555; font-size: 10px; }
.two-col { width: 100%; border-collapse: separate; border-spacing: 14px 0; }
.box-title { font-size: 12px; font-weight: bold; text-align: center; border-bottom: 3px solid #30305f; padding: 3px; }
.data-table, .section-table, .bars-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
.data-table td { background: #eeeeee; border: 1px solid #fff; padding: 4px; }
.data-table td:first-child { width: 38%; font-weight: bold; }
.section-table th, .bars-table th { font-size: 12px; text-align: center; border-bottom: 3px solid #30305f; padding: 4px; }
.section-table td { background: #eeeeee; border: 1px solid #fff; padding: 7px 5px; }
.section-table .total td { border-top: 1px solid #30305f; font-weight: bold; }
.center { text-align: center; }
.right { text-align: right; }
.muted { color: #777; }
.bars-table td { padding: 6px 4px; }
.label-bar { width: 120px; font-size: 11px; }
.bar { height: 18px; width: 280px; background: #fff; }
.bar span { display: block; height: 18px; background: #393764; }
.grid { width: 100%; border-collapse: separate; border-spacing: 14px 0; }
.grid td { vertical-align: top; width: 50%; }
.resolution { font-size: 13px; font-weight: bold; }
</style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="brand">VOLCAN FOODS</div>
                <div class="subtitle">Control de Calidad Recepcion</div>
            </td>
            <td class="right">
                <div>Informe: ' . calidadPdfTexto($fechaInforme) . '</div>
                <div>Control: ' . calidadPdfTexto($control['ID_CALIDAD_CONTROL']) . '</div>
            </td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <div class="box-title">DATOS DEL PRODUCTOR</div>
                <table class="data-table">
                    <tr><td>NOMBRE:</td><td>' . calidadPdfTexto($control['PRODUCTORES']) . '</td></tr>
                    <tr><td>CSG:</td><td>' . calidadPdfTexto($control['CSG']) . '</td></tr>
                    <tr><td>ESPECIE:</td><td>' . calidadPdfTexto($control['NOMBRE_ESPECIES']) . '</td></tr>
                    <tr><td>FECHA / GUIA:</td><td>' . calidadPdfTexto($control['FECHA_RECEPCION']) . ' / ' . calidadPdfTexto($control['NUMERO_GUIA_RECEPCION']) . '</td></tr>
                    <tr><td>FOLIO:</td><td>' . calidadPdfTexto($folios) . '</td></tr>
                    <tr><td>PLANTA:</td><td>' . calidadPdfTexto($control['NOMBRE_PLANTA']) . '</td></tr>
                </table>
            </td>
            <td>
                <div class="box-title">RESOLUCION</div>
                <table class="data-table">
                    <tr><td>STATUS:</td><td class="resolution">' . calidadPdfTexto($control['RESULTADO_GENERAL']) . '</td></tr>
                    <tr><td>% ESTIMADO EXPORTACION:</td><td>' . calidadPdfNumero($control['PORC_ESTIMADO_EXPORTACION'], 2) . '%</td></tr>
                    <tr><td>% DEF. CONDICION:</td><td>' . calidadPdfNumero($control['PORC_DEFECTO_CONDICION'], 2) . '%</td></tr>
                    <tr><td>% DEF. CALIDAD:</td><td>' . calidadPdfNumero($control['PORC_DEFECTO_CALIDAD'], 2) . '%</td></tr>
                    <tr><td>% FIRMEZA:</td><td>' . calidadPdfNumero($control['PORC_FIRMEZA'], 2) . '%</td></tr>
                    <tr><td>INSPECTOR:</td><td>' . calidadPdfTexto($control['NOMBRE_INSPECTOR']) . '</td></tr>
                    <tr><td>CIERRE:</td><td>' . calidadPdfTexto($control['FECHA_CIERRE']) . ' ' . calidadPdfTexto($control['HORA_CIERRE']) . '</td></tr>
                </table>
            </td>
        </tr>
    </table>

    ' . calidadPdfBarrasCalibre($detalles) . '

    <table class="grid">
        <tr>
            <td>' . calidadPdfTablaDetalle('PRESIONES', $detalles, 'PRESIONES') . '</td>
            <td>' . calidadPdfTablaDetalle('PARAMETROS', $detalles, 'PARAMETROS', '&deg; BRIX') . '</td>
        </tr>
        <tr>
            <td>' . calidadPdfTablaDetalle('DEFECTOS DE CONDICION', $detalles, 'DEFECTOS_CONDICION') . '</td>
            <td>' . calidadPdfTablaDetalle('DEFECTOS DE CALIDAD', $detalles, 'DEFECTOS_CALIDAD') . '</td>
        </tr>
    </table>
</body>
</html>';

$NOMBREARCHIVOFINAL = 'InformeCalidadRecepcion_' . $IDCONTROL . '.pdf';

require_once __DIR__ . '/../../api/mpdf/mpdf/autoload.php';
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smartberries_mpdf_calidad';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}
$PDF = new \Mpdf\Mpdf([
    'format' => 'letter',
    'tempDir' => $tempDir,
    'default_font' => 'Arial',
    'autoScriptToLang' => false,
    'autoLangToFont' => false,
    'useSubstitutions' => false
]);
$PDF->allow_charset_conversion = true;
$PDF->charset_in = 'UTF-8';
$PDF->SetHTMLHeader('<table width="100%"><tr><td style="font-size:10px;">' . calidadPdfTexto($empresa) . '</td><td style="font-size:10px;text-align:right;">{PAGENO}/{nbpg}</td></tr></table>');
$PDF->SetHTMLFooter('<div style="font-size:9px;text-align:center;">Informe generado por Smart Berries - Calidad</div>');
$PDF->SetTitle('Informe Calidad Recepcion');
$PDF->WriteHTML($html);
$PDF->Output($NOMBREARCHIVOFINAL, \Mpdf\Output\Destination::INLINE);
?>
