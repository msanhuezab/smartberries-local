<?php

include_once __DIR__ . '/../config/BDCONFIG.php';

$IDCONTROL = $_GET['parametro'] ?? '';
$IDUSUARIO = $_GET['usuario'] ?? '';
if ($IDCONTROL === '') die('Parametro no informado.');

/* ── utilidades ───────────────────────────────────────────── */
function pdfT($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
function pdfN($v, $dec = 2) {
    if ($v === null || $v === '') return '—';
    return number_format((float) $v, $dec, ',', '.');
}
function pdfPorc($gramos, $muestra, $dec = 2) {
    $m = (float) $muestra;
    if ($m <= 0) return '0,00 %';
    return number_format(((float) $gramos / $m) * 100, $dec, ',', '.') . ' %';
}
function pdfColorResolucion($resultado) {
    switch (strtoupper((string) $resultado)) {
        case 'APROBADO': return '#2e7d32';
        case 'OBJETADO': return '#f9a825';
        case 'RECHAZADO': return '#c62828';
        default: return '#607d8b';
    }
}

date_default_timezone_set('America/Santiago');
$fechaDoc = date('d/m/Y');
$horaDoc  = date('H:i:s');

/* ── datos ────────────────────────────────────────────────── */
try {
    $cx = BDCONFIG::conectar();

    $s = $cx->prepare("
        SELECT C.*,
               I.NOMBRE_INSPECTOR,
               EMP.RAZON_SOCIAL_EMPRESA, EMP.NOMBRE_EMPRESA,
               PLA.NOMBRE_PLANTA,
               TEM.NOMBRE_TEMPORADA,
               ESP.NOMBRE_ESPECIES,
               R.NUMERO_GUIA_RECEPCION, R.FECHA_RECEPCION,
               GROUP_CONCAT(DISTINCT F.FOLIO_AUXILIAR   ORDER BY F.FOLIO_AUXILIAR   SEPARATOR ', ') AS FOLIOS,
               GROUP_CONCAT(DISTINCT P.NOMBRE_PRODUCTOR ORDER BY P.NOMBRE_PRODUCTOR SEPARATOR ', ') AS PRODUCTORES,
               GROUP_CONCAT(DISTINCT P.CSG_PRODUCTOR    ORDER BY P.CSG_PRODUCTOR    SEPARATOR ', ') AS CSG
        FROM fruta_calidad_control C
        LEFT JOIN fruta_calidad_inspector I   ON I.ID_CALIDAD_INSPECTOR = C.ID_CALIDAD_INSPECTOR
        LEFT JOIN principal_empresa       EMP ON EMP.ID_EMPRESA         = C.ID_EMPRESA
        LEFT JOIN principal_planta        PLA ON PLA.ID_PLANTA          = C.ID_PLANTA
        LEFT JOIN principal_temporada     TEM ON TEM.ID_TEMPORADA       = C.ID_TEMPORADA
        LEFT JOIN fruta_especies          ESP ON ESP.ID_ESPECIES        = C.ID_ESPECIES
        LEFT JOIN fruta_recepcionmp       R   ON R.ID_RECEPCION         = C.ID_OPERACION
        LEFT JOIN fruta_calidad_control_folio F ON F.ID_CALIDAD_CONTROL = C.ID_CALIDAD_CONTROL AND F.ESTADO_REGISTRO = 1
        LEFT JOIN fruta_eximateriaprima   EX  ON EX.ID_EXIMATERIAPRIMA  = F.ID_EXISTENCIA
        LEFT JOIN fruta_productor         P   ON P.ID_PRODUCTOR         = EX.ID_PRODUCTOR
        WHERE C.ID_CALIDAD_CONTROL = ? AND C.ESTADO_REGISTRO = 1
        GROUP BY C.ID_CALIDAD_CONTROL LIMIT 1");
    $s->execute([$IDCONTROL]);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) die('Control no encontrado.');
    $c = $rows[0];
    if ($c['ESTADO_CONTROL'] !== 'CERRADO') die('Solo disponible para controles cerrados.');

    $ds = $cx->prepare("
        SELECT * FROM fruta_calidad_control_detalle
        WHERE ID_CALIDAD_CONTROL = ? AND ESTADO_REGISTRO = 1
        ORDER BY TIPO_PARAMETRO ASC, ID_CALIDAD_CONTROL_DETALLE ASC");
    $ds->execute([$IDCONTROL]);
    $detalles = $ds->fetchAll(PDO::FETCH_ASSOC);

    $nombreUsuario = '';
    if ($IDUSUARIO !== '') {
        $us = $cx->prepare("SELECT TRIM(IFNULL(CONCAT(PNOMBRE_USUARIO,' ',SNOMBRE_USUARIO,' ',PAPELLIDO_USUARIO,' ',SAPELLIDO_USUARIO),'')) AS NC FROM usuario_usuario WHERE ID_USUARIO = ? LIMIT 1");
        $us->execute([$IDUSUARIO]);
        $ur = $us->fetch(PDO::FETCH_ASSOC);
        if ($ur) $nombreUsuario = $ur['NC'];
    }
} catch (Exception $e) { die($e->getMessage()); }

$empresa = $c['RAZON_SOCIAL_EMPRESA'] ?: $c['NOMBRE_EMPRESA'];
$folios  = $c['MODO_INGRESO'] === 'AGRUPADO' ? 'Recepcion completa' : $c['FOLIOS'];
$muestra = (float) ($c['MUESTRA_GRAMOS'] ?? 1000);

/* ── separar detalles por grupo ───────────────────────────── */
$detCalibres  = [];
$detPresiones = [];
$detParams    = [];
$detCondicion = [];
$detCalidad   = [];
foreach ($detalles as $d) {
    switch ($d['TIPO_PARAMETRO']) {
        case 'CALIBRES':          $detCalibres[]  = $d; break;
        case 'PRESIONES':         $detPresiones[] = $d; break;
        case 'PARAMETROS':        $detParams[]    = $d; break;
        case 'DEFECTOS_CONDICION':$detCondicion[] = $d; break;
        case 'DEFECTOS_CALIDAD':  $detCalidad[]   = $d; break;
    }
}

/* extraer temperatura y brix de PARAMETROS para mostrar en resolucion */
$valorTemp = null;
$valorBrix = null;
foreach ($detParams as $d) {
    $n = strtoupper(trim($d['NOMBRE_PARAMETRO']));
    if ($valorTemp === null && strpos($n, 'TEMP') !== false) $valorTemp = $d['VALOR_NUMERICO'];
    elseif ($valorBrix === null && strpos($n, 'BRIX') !== false) $valorBrix = $d['VALOR_NUMERICO'];
}

/* ── constantes de estilo ─────────────────────────────────── */
$COL1   = '#393764'; // azul oscuro
$COL2   = '#555580'; // azul medio
$COLFIL = pdfColorResolucion($c['RESULTADO_GENERAL']); // color resolucion

$S_TH1 = "background:{$COL1};color:#fff;padding:4px 6px;text-align:left;font-size:10px;font-weight:bold;";
$S_TH2 = "background:{$COL2};color:#fff;padding:3px 5px;text-align:left;font-size:9px;font-weight:bold;";
$S_TH2C= "background:{$COL2};color:#fff;padding:3px 5px;text-align:center;font-size:9px;font-weight:bold;width:68px;";
$S_TD  = "background:#f4f4f4;padding:3px 5px;font-size:9px;border-bottom:1px solid #fff;";
$S_TDA = "background:#ebebeb;padding:3px 5px;font-size:9px;border-bottom:1px solid #fff;";
$S_TDC = "padding:3px 5px;font-size:9px;text-align:center;border-bottom:1px solid #fff;width:68px;";
$S_TOT = "background:#ccc;font-weight:bold;padding:4px 6px;font-size:9px;border-top:2px solid #888;";
$S_TOTC= "background:#ccc;font-weight:bold;padding:4px 6px;font-size:9px;text-align:center;border-top:2px solid #888;width:68px;";

$S_KEY = "background:#e0e0e0;font-weight:bold;padding:3px 5px;font-size:9px;border-bottom:1px solid #fff;width:44%;";
$S_VAL = "background:#f4f4f4;padding:3px 5px;font-size:9px;border-bottom:1px solid #fff;";

function filaKV($k, $v) {
    global $S_KEY, $S_VAL;
    return "<tr><td style=\"{$S_KEY}\">" . pdfT($k) . "</td><td style=\"{$S_VAL}\">" . pdfT($v) . "</td></tr>\n";
}

/* ── generadores de tablas ────────────────────────────────── */
function tablaDet($titulo, $rows, $muestra, $esGramos, $unidad = '%') {
    global $S_TH1,$S_TH2,$S_TH2C,$S_TD,$S_TDA,$S_TDC,$S_TOT,$S_TOTC;
    $filas = '';
    $totalG = 0;
    $par = false;
    foreach ($rows as $d) {
        $g = (float) $d['VALOR_NUMERICO'];
        if ($esGramos) { $totalG += $g; $val = pdfPorc($g, $muestra); }
        else           { $val = pdfN($g, 2); }
        $bg  = $par ? $S_TDA : $S_TD;
        $bgC = $par ? str_replace('#ebebeb','#ebebeb',$S_TDC) : $S_TDC;
        $filas .= "<tr>
          <td style=\"{$bg}\">" . pdfT($d['NOMBRE_PARAMETRO']) . "</td>
          <td style=\"background:" . ($par ? '#ebebeb' : '#f4f4f4') . ";{$bgC}\">{$val}</td>
        </tr>\n";
        $par = !$par;
    }
    if ($filas === '') $filas = "<tr><td colspan=\"2\" style=\"{$S_TD}text-align:center;color:#999;\">Sin datos</td></tr>\n";

    $totalHtml = '';
    if ($esGramos && $totalG !== null && count($rows) > 0) {
        $totalHtml = "<tr><td style=\"{$S_TOT}\">TOTAL</td><td style=\"{$S_TOTC}\">" . pdfPorc($totalG, $muestra) . "</td></tr>\n";
    }

    $u = $esGramos ? '%' : $unidad;
    return "<table style=\"width:100%;border-collapse:collapse;margin-bottom:0;\">
      <thead>
        <tr><th colspan=\"2\" style=\"{$S_TH1}\">" . pdfT($titulo) . " ({$u})</th></tr>
        <tr>
          <th style=\"{$S_TH2}\">Parametro</th>
          <th style=\"{$S_TH2C}\">Valor</th>
        </tr>
      </thead>
      <tbody>{$filas}{$totalHtml}</tbody>
    </table>";
}

function tablaCalibres($rows, $muestra) {
    global $S_TH1;
    if (empty($rows)) return '';

    $colores = ['#393764','#4a4a80','#5c5c9c','#6e6eb8','#8484cc','#9696d4','#a8a8dc'];

    $filas = '';
    $i = 0;
    foreach ($rows as $d) {
        $g    = max(0, (float) $d['VALOR_NUMERICO']);
        $porc = $muestra > 0 ? ($g / $muestra) * 100 : 0;
        $w    = min(100, round($porc, 1));
        $resto = max(0, 100 - $w);
        $col  = $colores[$i % count($colores)];
        // barra: tabla anidada de 2 celdas (relleno + vacío) — compatible mPDF
        $barra = "<table style=\"width:100%;border-collapse:collapse;height:13px;\">
          <tr>
            <td style=\"background:{$col};width:{$w}%;height:13px;\"></td>
            <td style=\"background:#e0e0ea;width:{$resto}%;height:13px;\"></td>
          </tr>
        </table>";
        $filas .= "<tr>
          <td style=\"background:#fff;padding:2px 6px 2px 0;font-size:9px;text-align:right;width:115px;border-bottom:1px solid #f0f0f0;\">
            " . pdfT($d['NOMBRE_PARAMETRO']) . "
          </td>
          <td style=\"background:#fff;padding:2px 4px;border-bottom:1px solid #f0f0f0;\">{$barra}</td>
          <td style=\"background:#fff;padding:2px 0 2px 6px;font-size:9px;font-weight:bold;text-align:left;width:50px;border-bottom:1px solid #f0f0f0;color:{$col};\">
            " . number_format($porc, 1, ',', '.') . " %
          </td>
        </tr>\n";
        $i++;
    }

    // eje X simple
    $eje = "<tr>
      <td style=\"background:#fff;\"></td>
      <td style=\"background:#fff;padding:1px 4px 3px 4px;\">
        <table style=\"width:100%;border-collapse:collapse;\">
          <tr>
            <td style=\"font-size:7px;color:#999;text-align:left;\">0%</td>
            <td style=\"font-size:7px;color:#999;text-align:center;\">25%</td>
            <td style=\"font-size:7px;color:#999;text-align:center;\">50%</td>
            <td style=\"font-size:7px;color:#999;text-align:center;\">75%</td>
            <td style=\"font-size:7px;color:#999;text-align:right;\">100%</td>
          </tr>
        </table>
      </td>
      <td style=\"background:#fff;\"></td>
    </tr>\n";

    return "<table style=\"width:100%;border-collapse:collapse;margin-bottom:8px;\">
      <thead>
        <tr><th colspan=\"3\" style=\"{$S_TH1}\">DISTRIBUCION DE CALIBRES</th></tr>
      </thead>
      <tbody>{$filas}{$eje}</tbody>
    </table>";
}

/* ── build HTML ───────────────────────────────────────────── */
$calibresHtml = tablaCalibres($detCalibres, $muestra);

$html = '<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;font-size:9px;">

<!-- CABECERA -->
<table style="width:100%;border-collapse:collapse;border-bottom:2px solid #393764;margin-bottom:6px;">
  <tr>
    <td style="width:38%;padding:3px 0;vertical-align:middle;">
      <img src="../../assest/img/logo.png" style="max-height:42px;max-width:160px;">
    </td>
    <td style="text-align:right;vertical-align:middle;font-size:9px;color:#444;">
      <strong style="font-size:12px;color:#393764;">' . pdfT($empresa) . '</strong><br>
      ' . pdfT($c['NOMBRE_PLANTA']) . ' &nbsp;|&nbsp; Temporada: ' . pdfT($c['NOMBRE_TEMPORADA']) . '<br>
      Impresion: ' . $fechaDoc . '
    </td>
  </tr>
</table>

<!-- TITULO -->
<table style="width:100%;border-collapse:collapse;margin-bottom:7px;">
  <tr>
    <td style="background:#393764;color:#fff;text-align:center;padding:5px 8px;">
      <strong style="font-size:12px;">INFORME CONTROL DE CALIDAD &mdash; RECEPCION</strong><br>
      <span style="font-size:9px;">Control N&deg; ' . pdfT($IDCONTROL) . ' &nbsp;|&nbsp; Modo: ' . pdfT($c['MODO_INGRESO']) . ' &nbsp;|&nbsp; Muestra: ' . pdfN($muestra, 0) . ' g</span>
    </td>
  </tr>
</table>

<!-- PRODUCTOR + RESOLUCION -->
<table style="width:100%;border-collapse:collapse;margin-bottom:7px;">
  <thead>
    <tr>
      <th style="width:52%;' . $S_TH1 . '">DATOS DEL PRODUCTOR</th>
      <th style="width:48%;' . $S_TH1 . '">RESOLUCION</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="vertical-align:top;padding:0;border-right:3px solid #fff;">
        <table style="width:100%;border-collapse:collapse;">
          ' . filaKV('Nombre',          $c['PRODUCTORES']) . '
          ' . filaKV('CSG',             $c['CSG']) . '
          ' . filaKV('Especie',         $c['NOMBRE_ESPECIES']) . '
          ' . filaKV('Fecha recepcion', $c['FECHA_RECEPCION']) . '
          ' . filaKV('N° Guia',         $c['NUMERO_GUIA_RECEPCION']) . '
          ' . filaKV('Folio(s)',        $folios) . '
          ' . filaKV('Inspector',       $c['NOMBRE_INSPECTOR']) . '
          ' . filaKV('Fecha cierre',    $c['FECHA_CIERRE'] . ' ' . $c['HORA_CIERRE']) . '
        </table>
      </td>
      <td style="vertical-align:top;padding:0;">
        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <td colspan="2" style="background:' . $COLFIL . ';color:#fff;font-weight:bold;font-size:14px;padding:7px 6px;text-align:center;border-bottom:2px solid #fff;">
              ' . pdfT($c['RESULTADO_GENERAL']) . '
            </td>
          </tr>
          ' . filaKV('Score',              pdfN($c['SCORE_GENERAL'] ?? '', 0)) . '
          ' . filaKV('% Est. Exportacion', pdfN($c['PORC_ESTIMADO_EXPORTACION'], 2) . ' %') . '
          ' . filaKV('% Def. Condicion',   pdfN($c['PORC_DEFECTO_CONDICION'],    2) . ' %') . '
          ' . filaKV('% Def. Calidad',     pdfN($c['PORC_DEFECTO_CALIDAD'],      2) . ' %') . '
          ' . filaKV('% Firmeza',          pdfN($c['PORC_FIRMEZA'],              2) . ' %') . '
          ' . ($valorTemp !== null ? filaKV('Temperatura',     pdfN($valorTemp, 2) . ' °C')    : '') . '
          ' . ($valorBrix !== null ? filaKV('Solidos Solubles',pdfN($valorBrix, 2) . ' °Brix') : '') . '
        </table>
      </td>
    </tr>
  </tbody>
</table>

' . ($calibresHtml !== '' ? $calibresHtml : '') . '

<!-- PRESIONES (fila completa) -->
' . tablaDet('Presiones', $detPresiones, $muestra, true) . '

<!-- DEFECTOS (dos columnas) + firma embebida en columna derecha -->
' . '<table style="width:100%;border-collapse:collapse;margin-top:7px;"><tbody><tr style="vertical-align:top;"><td style="width:50%;padding-right:3px;vertical-align:top;">'
. tablaDet('Defectos de Condicion', $detCondicion, $muestra, true)
. ($c['OBSERVACION_CIERRE'] ? '<p style="font-size:8px;color:#666;margin-top:4px;"><em>Obs: ' . pdfT($c['OBSERVACION_CIERRE']) . '</em></p>' : '')
. '</td><td style="width:50%;padding-left:3px;vertical-align:top;">'
. tablaDet('Defectos de Calidad', $detCalidad, $muestra, true)
. '<table style="width:100%;border-collapse:collapse;margin-top:10px;"><tr>
    <td style="border-top:1px solid #555;padding-top:40px;padding-bottom:4px;text-align:center;font-size:9px;background:#fff;">
      Firma Inspector &mdash; ' . pdfT($c['NOMBRE_INSPECTOR']) . '
    </td>
  </tr></table>'
. '</td></tr></tbody></table></body></html>';

/* ── mPDF ─────────────────────────────────────────────────── */
$NOMBREARCHIVOFINAL = 'InformeCalidadRecepcion_' . $IDCONTROL . '.pdf';

require_once __DIR__ . '/../../api/mpdf/mpdf/autoload.php';
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smartberries_mpdf_calidad';
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

$PDF = new \Mpdf\Mpdf([
    'format'          => 'letter',
    'tempDir'         => $tempDir,
    'default_font'    => 'Arial',
    'margin_top'      => 16,
    'margin_bottom'   => 14,
    'margin_left'     => 12,
    'margin_right'    => 12,
    'autoScriptToLang'=> false,
    'autoLangToFont'  => false,
    'useSubstitutions'=> false,
]);
$PDF->allow_charset_conversion = true;
$PDF->charset_in = 'UTF-8';

$PDF->SetHTMLHeader('<table width="100%"><tr>
    <td style="font-size:8px;">' . pdfT($empresa) . ' &mdash; Control N&deg; ' . pdfT($IDCONTROL) . '</td>
    <td style="font-size:8px;text-align:right;">' . $fechaDoc . ' &nbsp; {PAGENO}/{nbpg}</td>
</tr></table>');

$PDF->SetHTMLFooter('<table width="100%" style="border-top:1px solid #bbb;"><tr>
    <td style="font-size:7px;color:#777;">Smart Berries &mdash; Control Calidad Recepcion'
    . ($nombreUsuario !== '' ? ' &mdash; Impreso por: ' . pdfT($nombreUsuario) : '')
    . '</td>
    <td style="font-size:7px;color:#777;text-align:right;">Hora: ' . $horaDoc . '</td>
</tr></table>');

$PDF->SetTitle('Informe Control Calidad Recepcion');
$PDF->SetCreator('Smart Berries');

$PDF->WriteHTML(file_get_contents(__DIR__ . '/../css/stylePdf.css'), 1);
$PDF->WriteHTML(file_get_contents(__DIR__ . '/../css/reset.css'), 1);
$PDF->WriteHTML($html);
$PDF->Output($NOMBREARCHIVOFINAL, \Mpdf\Output\Destination::INLINE);
?>
