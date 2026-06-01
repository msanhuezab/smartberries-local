<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();

function fmt2($n, $dec = 2) { return number_format((float)$n, $dec, ',', '.'); }

/* ── Parámetros ────────────────────────────────────────────────────── */
$filterBroker = isset($_GET['ID_BROKER']) ? (int)$_GET['ID_BROKER'] : 0;
$filterDesde  = $_GET['FECHA_DESDE'] ?? '';
$filterHasta  = $_GET['FECHA_HASTA'] ?? '';

if ($filterBroker <= 0) {
    die('Broker no especificado.');
}

/* ── Tasa EUR→USD ──────────────────────────────────────────────────── */
$clpDolar    = (float)preg_replace('/[^0-9]/', '', $TMONEDA1 ?? '');
$clpEuro     = (float)preg_replace('/[^0-9]/', '', $TMONEDA2 ?? '');
$EUR_USD_DEF = ($clpDolar > 0 && $clpEuro > 0) ? round($clpEuro / $clpDolar, 6) : 1.0;

/* ── Datos del broker ──────────────────────────────────────────────── */
$stmtB = $db->prepare("SELECT NOMBRE_BROKER FROM fruta_broker WHERE ID_BROKER = ? AND ID_EMPRESA = ?");
$stmtB->execute([$filterBroker, $EMPRESAS]);
$brokerRow = $stmtB->fetch(PDO::FETCH_ASSOC);
if (!$brokerRow) die('Broker no encontrado.');
$BROKER_NOMBRE = $brokerRow['NOMBRE_BROKER'];

/* ── Datos de la empresa ───────────────────────────────────────────── */
$stmtE = $db->prepare("SELECT NOMBRE_EMPRESA FROM principal_empresa WHERE ID_EMPRESA = ?");
$stmtE->execute([$EMPRESAS]);
$empresaRow  = $stmtE->fetch(PDO::FETCH_ASSOC);
$NOMBRE_EMPRESA = $empresaRow['NOMBRE_EMPRESA'] ?? '';

/* ── Query movimientos (igual que cuentaCorrienteBroker) ───────────── */
$condFecha = '';
$params    = [$filterBroker, $EMPRESAS, $TEMPORADAS];
if ($filterDesde !== '') { $condFecha .= " AND fecha >= ?"; $params[] = $filterDesde; }
if ($filterHasta !== '') { $condFecha .= " AND fecha <= ?"; $params[] = $filterHasta; }

$sql = "
SELECT fecha, tipo, concepto, referencia, debe, haber, id_doc, es_manual
FROM (

    SELECT
        val.FECHA_VALOR                                         AS fecha,
        'Liquidación'                                           AS tipo,
        CONCAT('Liq. #', val.NUMERO_VALOR,
               ' — IC#', i.NUMERO_ICARGA,
               IF(i.NREFERENCIA_ICARGA<>'' AND i.NREFERENCIA_ICARGA IS NOT NULL,
                  CONCAT(' (', i.NREFERENCIA_ICARGA, ')'), '')) AS concepto,
        ''                                                      AS referencia,
        0                                                       AS debe,
        GREATEST(0, ROUND(SUM(vle.RETORNO_NETO), 2))           AS haber,
        val.ID_VALOR                                            AS id_doc,
        0                                                       AS es_manual
    FROM liquidacion_valor val
    JOIN fruta_icarga i       ON i.ID_ICARGA  = val.ID_ICARGA
    JOIN view_liquidacion_exportacion vle ON vle.ID_VALOR = val.ID_VALOR
    WHERE val.ESTADO_REGISTRO = 1
      AND i.ID_BROKER    = ?
      AND i.ID_EMPRESA   = ?
      AND i.ID_TEMPORADA = ?
    GROUP BY val.ID_VALOR

    UNION ALL

    SELECT
        da.fecha_anticipo                                       AS fecha,
        'Anticipo'                                              AS tipo,
        CONCAT(IFNULL(da.nombre_anticipo,'Anticipo'),
               ' — ', IFNULL(a.observacion,''))                 AS concepto,
        ''                                                      AS referencia,
        ROUND(ABS(da.valor_anticipo), 2)                        AS debe,
        0                                                       AS haber,
        a.id_anticipo                                           AS id_doc,
        0                                                       AS es_manual
    FROM liquidacion_anticipo a
    JOIN detalle_anticipo da ON da.id_anticipo = a.id_anticipo
    WHERE a.estado_registro = 1
      AND a.id_broker    = ?
      AND a.id_empresa   = ?
      AND a.id_temporada = ?

    UNION ALL

    SELECT
        p.FECHA_DVALOR                                          AS fecha,
        'Pago'                                                  AS tipo,
        CONCAT('Pago Liq. #', val.NUMERO_VALOR)                 AS concepto,
        ''                                                      AS referencia,
        ROUND(ABS(p.VALOR_DVALOR), 2)                           AS debe,
        0                                                       AS haber,
        p.ID_DVALOR                                             AS id_doc,
        0                                                       AS es_manual
    FROM liquidacion_dvalorp p
    JOIN liquidacion_valor val ON val.ID_VALOR = p.ID_VALOR
    WHERE p.ESTADO_REGISTRO = 1
      AND p.ID_BROKER    = ?
      AND p.ID_EMPRESA   = ?
      AND p.ID_TEMPORADA = ?

    UNION ALL

    SELECT
        i.FECHA_ICARGA                                          AS fecha,
        'Estimado'                                              AS tipo,
        CONCAT('Estimado IC#', i.NUMERO_ICARGA,
               IF(i.NREFERENCIA_ICARGA IS NOT NULL AND i.NREFERENCIA_ICARGA <> '',
                  CONCAT(' (', i.NREFERENCIA_ICARGA, ')'), '')) AS concepto,
        ''                                                      AS referencia,
        0                                                       AS debe,
        ROUND(SUM(
            CASE WHEN tm.NOMBRE_TMONEDA LIKE '%uro%'
                 THEN dic.PRECIO_US_DICARGA * $EUR_USD_DEF * IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)
                 ELSE dic.PRECIO_US_DICARGA * IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)
            END
        ), 2)                                                   AS haber,
        i.ID_ICARGA                                             AS id_doc,
        0                                                       AS es_manual
    FROM fruta_icarga i
    JOIN fruta_despachoex dex  ON dex.ID_ICARGA   = i.ID_ICARGA  AND dex.ESTADO_REGISTRO = 1
    JOIN fruta_exiexportacion exi ON exi.ID_DESPACHOEX = dex.ID_DESPACHOEX AND exi.ESTADO_REGISTRO = 1
    JOIN fruta_dicarga dic     ON dic.ID_ICARGA   = i.ID_ICARGA
                              AND dic.ID_ESTANDAR = exi.ID_ESTANDAR
                              AND dic.ID_TCALIBRE = exi.ID_TCALIBRE
                              AND (dic.ID_VESPECIES IS NULL OR dic.ID_VESPECIES = exi.ID_VESPECIES)
                              AND dic.ESTADO_REGISTRO = 1
                              AND IFNULL(dic.PRECIO_US_DICARGA, 0) > 0
    LEFT JOIN fruta_tmoneda tm ON tm.ID_TMONEDA   = dic.ID_TMONEDA
    LEFT JOIN liquidacion_valor lv ON lv.ID_ICARGA = i.ID_ICARGA AND lv.ESTADO_REGISTRO = 1
    WHERE i.ESTADO_REGISTRO = 1
      AND i.ID_BROKER    = ?
      AND i.ID_EMPRESA   = ?
      AND i.ID_TEMPORADA = ?
      AND lv.ID_VALOR    IS NULL
    GROUP BY i.ID_ICARGA
    HAVING haber > 0

    UNION ALL

    SELECT
        i.FECHA_ICARGA                                          AS fecha,
        'Pendiente'                                             AS tipo,
        CONCAT('Pendiente IC#', i.NUMERO_ICARGA,
               IF(i.NREFERENCIA_ICARGA IS NOT NULL AND i.NREFERENCIA_ICARGA <> '',
                  CONCAT(' (', i.NREFERENCIA_ICARGA, ')'), '')) AS concepto,
        ''                                                      AS referencia,
        0                                                       AS debe,
        0                                                       AS haber,
        i.ID_ICARGA                                             AS id_doc,
        0                                                       AS es_manual
    FROM fruta_icarga i
    LEFT JOIN liquidacion_valor lv  ON lv.ID_ICARGA  = i.ID_ICARGA AND lv.ESTADO_REGISTRO = 1
    LEFT JOIN fruta_dicarga dic     ON dic.ID_ICARGA = i.ID_ICARGA AND dic.ESTADO_REGISTRO = 1
                                  AND IFNULL(dic.PRECIO_US_DICARGA, 0) > 0
    WHERE i.ESTADO_REGISTRO = 1
      AND i.ID_BROKER    = ?
      AND i.ID_EMPRESA   = ?
      AND i.ID_TEMPORADA = ?
      AND lv.ID_VALOR    IS NULL
      AND dic.ID_DICARGA IS NULL
    GROUP BY i.ID_ICARGA

    UNION ALL

    SELECT
        c.FECHA_MOVIMIENTO                                      AS fecha,
        c.CATEGORIA                                             AS tipo,
        c.CONCEPTO                                              AS concepto,
        IFNULL(c.REFERENCIA,'')                                 AS referencia,
        IF(c.TIPO_MOVIMIENTO='DEBE',  c.MONTO_USD, 0)           AS debe,
        IF(c.TIPO_MOVIMIENTO='HABER', c.MONTO_USD, 0)           AS haber,
        c.ID_CCBROKER                                           AS id_doc,
        1                                                       AS es_manual
    FROM cuenta_corriente_broker c
    WHERE c.ESTADO_REGISTRO = 1
      AND c.ID_BROKER    = ?
      AND c.ID_EMPRESA   = ?
      AND c.ID_TEMPORADA = ?

) mov
WHERE 1=1 $condFecha
ORDER BY fecha ASC, tipo ASC
";

$bindParams = [];
for ($i = 0; $i < 6; $i++) {
    $bindParams[] = $filterBroker;
    $bindParams[] = $EMPRESAS;
    $bindParams[] = $TEMPORADAS;
}
$bindParams = array_merge($bindParams, array_slice($params, 3));

$stmt = $db->prepare($sql);
$stmt->execute($bindParams);
$MOVIMIENTOS = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Totales y saldo acumulado ─────────────────────────────────────── */
$totalDebe  = 0;
$totalHaber = 0;
$saldo      = 0;
foreach ($MOVIMIENTOS as &$mov) {
    $totalDebe  += (float)$mov['debe'];
    $totalHaber += (float)$mov['haber'];
    $saldo      += (float)$mov['haber'] - (float)$mov['debe'];
    $mov['saldo_acum'] = $saldo;
}
unset($mov);

/* ── Config visual ─────────────────────────────────────────────────── */
$C_NAVY   = '#0f2545';
$C_BLUE   = '#1a6fbf';
$C_GOLD   = '#e8a020';
$C_GREEN  = '#1a7e3a';
$C_RED    = '#c0392b';
$C_GRAY   = '#6b7280';

function tipoColor(string $tipo): array {
    return match(true) {
        str_contains($tipo, 'Liquidaci') => ['#1a5fa8', '#dbeafe'],
        $tipo === 'Estimado'             => ['#b45309', '#fef3c7'],
        $tipo === 'Pendiente'            => ['#4b5563', '#f3f4f6'],
        str_contains($tipo, 'Anticipo')  => ['#0369a1', '#e0f2fe'],
        str_contains($tipo, 'Pago')      => ['#0369a1', '#e0f2fe'],
        str_contains($tipo, 'Crédito')   => ['#15803d', '#dcfce7'],
        str_contains($tipo, 'Débito')    => ['#b91c1c', '#fee2e2'],
        default                          => ['#374151', '#f3f4f6'],
    };
}

/* ── Filas de la tabla ─────────────────────────────────────────────── */
$filas  = '';
$rowNum = 0;
foreach ($MOVIMIENTOS as $mov) {
    $rowNum++;
    $bg        = $rowNum % 2 === 0 ? '#f8fafc' : '#ffffff';
    $debe      = (float)$mov['debe'];
    $haber     = (float)$mov['haber'];
    $sal       = (float)$mov['saldo_acum'];
    [$tc, $bc] = tipoColor($mov['tipo']);
    $salColor  = $sal >= 0 ? $C_GREEN : $C_RED;
    $salBg     = $sal >= 0 ? '#f0fdf4' : '#fff1f2';
    $salSign   = $sal >= 0 ? '' : '−';

    $filas .= '
    <tr style="background:' . $bg . ';border-bottom:1px solid #e5e7eb;">
        <td style="padding:5px 8px;font-size:8.5px;white-space:nowrap;color:#374151;">'
            . htmlspecialchars($mov['fecha']) . '</td>
        <td style="padding:5px 8px;font-size:8.5px;">
            <span style="display:inline-block;background:' . $bc . ';color:' . $tc . ';
                         border:1px solid ' . $tc . ';padding:1px 7px;border-radius:20px;
                         font-size:7.5px;font-weight:700;white-space:nowrap;">'
                . htmlspecialchars($mov['tipo']) . '</span>
        </td>
        <td style="padding:5px 8px;font-size:8.5px;color:#1e293b;">'
            . htmlspecialchars($mov['concepto']) . '</td>
        <td style="padding:5px 8px;font-size:8px;text-align:center;color:#6b7280;">'
            . htmlspecialchars($mov['referencia']) . '</td>
        <td style="padding:5px 8px;font-size:8.5px;text-align:right;color:' . $C_RED . ';font-weight:600;">'
            . ($debe > 0 ? 'USD&nbsp;' . fmt2($debe) : '<span style="color:#d1d5db;">—</span>') . '</td>
        <td style="padding:5px 8px;font-size:8.5px;text-align:right;color:' . $C_GREEN . ';font-weight:600;">'
            . ($haber > 0 ? 'USD&nbsp;' . fmt2($haber) : '<span style="color:#d1d5db;">—</span>') . '</td>
        <td style="padding:5px 8px;font-size:8.5px;text-align:right;font-weight:700;
                   background:' . $salBg . ';color:' . $salColor . ';">'
            . $salSign . 'USD&nbsp;' . fmt2(abs($sal)) . '</td>
    </tr>';
}

/* ── Período en texto ──────────────────────────────────────────────── */
$periodoTexto = 'Temporada completa';
if ($filterDesde || $filterHasta) {
    $periodoTexto = trim(
        ($filterDesde ? 'Desde ' . date('d/m/Y', strtotime($filterDesde)) : '') . '  ' .
        ($filterHasta ? 'hasta ' . date('d/m/Y', strtotime($filterHasta)) : '')
    );
}

$fechaEmision = date('d/m/Y  H:i');
$logoPath     = realpath(__DIR__ . '/../../assest/img/logo.png');
$logoTag      = $logoPath
    ? '<img src="' . $logoPath . '" style="height:44px;vertical-align:middle;">'
    : '<span style="font-size:18px;font-weight:900;color:#ffffff;letter-spacing:-1px;">SB</span>';

$nMov         = count($MOVIMIENTOS);
$saldoColor   = $saldo >= 0 ? $C_GREEN : $C_RED;
$saldoBg      = $saldo >= 0 ? '#f0fdf4' : '#fff1f2';
$saldoSign    = $saldo >= 0 ? '' : '−';
$saldoLabel   = $saldo >= 0 ? 'A favor empresa' : 'Adeuda broker';

/* ── HTML del PDF ──────────────────────────────────────────────────── */
$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: Arial, sans-serif; margin:0; padding:0; color:#1e293b; }
  table { border-collapse: collapse; }
</style>
</head>
<body>

<!-- ═══ HEADER BAND ═══ -->
<table width="100%" style="background:{$C_NAVY};margin:0;padding:0;">
  <tr>
    <td style="padding:14px 18px 10px 18px;vertical-align:middle;width:30%;">
      {$logoTag}
    </td>
    <td style="padding:14px 18px 10px 18px;vertical-align:middle;text-align:right;">
      <div style="font-size:7px;color:#94a3b8;text-transform:uppercase;letter-spacing:2px;margin-bottom:3px;">Documento Oficial</div>
      <div style="font-size:20px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">Cartola Cuenta Corriente</div>
      <div style="font-size:9px;color:#93c5fd;margin-top:2px;">
HTML;

$html .= htmlspecialchars($NOMBRE_EMPRESA) . '</div>
    </td>
  </tr>
</table>

<!-- GOLD ACCENT LINE -->
<div style="background:' . $C_GOLD . ';height:3px;margin:0;"></div>

<!-- ═══ BROKER + META ═══ -->
<table width="100%" style="margin-top:14px;margin-bottom:14px;">
  <tr>
    <!-- Broker info -->
    <td width="55%" style="vertical-align:top;padding-right:10px;">
      <table style="border-left:4px solid ' . $C_BLUE . ';width:100%;">
        <tr>
          <td style="padding:6px 0 6px 12px;">
            <div style="font-size:7.5px;color:' . $C_GRAY . ';text-transform:uppercase;letter-spacing:1.5px;margin-bottom:3px;">Broker</div>
            <div style="font-size:17px;font-weight:700;color:' . $C_NAVY . ';line-height:1.1;">' . htmlspecialchars($BROKER_NOMBRE) . '</div>
            <div style="font-size:8px;color:' . $C_GRAY . ';margin-top:4px;">' . htmlspecialchars($periodoTexto) . '</div>
          </td>
        </tr>
      </table>
    </td>
    <!-- Meta -->
    <td width="45%" style="vertical-align:top;text-align:right;">
      <table style="width:100%;">
        <tr>
          <td style="text-align:right;padding-bottom:3px;">
            <span style="font-size:7.5px;color:' . $C_GRAY . ';text-transform:uppercase;letter-spacing:1px;">Fecha emisión:</span>
            <span style="font-size:8.5px;font-weight:600;color:' . $C_NAVY . ';margin-left:4px;">' . $fechaEmision . '</span>
          </td>
        </tr>
        <tr>
          <td style="text-align:right;">
            <span style="font-size:7.5px;color:' . $C_GRAY . ';text-transform:uppercase;letter-spacing:1px;">N° movimientos:</span>
            <span style="font-size:8.5px;font-weight:600;color:' . $C_NAVY . ';margin-left:4px;">' . $nMov . '</span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- ═══ KPI CARDS ═══ -->
<table width="100%" style="margin-bottom:16px;">
  <tr>
    <!-- HABER -->
    <td width="31%" style="vertical-align:top;">
      <table width="100%" style="background:#eff6ff;border:1px solid #bfdbfe;border-top:3px solid ' . $C_BLUE . ';">
        <tr>
          <td style="padding:10px 14px 8px 14px;">
            <div style="font-size:7px;color:#3b82f6;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:5px;">&#9650; Total Haber</div>
            <div style="font-size:16px;font-weight:700;color:' . $C_BLUE . ';">USD ' . fmt2($totalHaber) . '</div>
            <div style="font-size:7.5px;color:#6b7280;margin-top:4px;">Retornos liquidados</div>
          </td>
        </tr>
      </table>
    </td>
    <td width="3%"></td>
    <!-- DEBE -->
    <td width="31%" style="vertical-align:top;">
      <table width="100%" style="background:#fff1f2;border:1px solid #fecdd3;border-top:3px solid ' . $C_RED . ';">
        <tr>
          <td style="padding:10px 14px 8px 14px;">
            <div style="font-size:7px;color:#ef4444;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:5px;">&#9660; Total Debe</div>
            <div style="font-size:16px;font-weight:700;color:' . $C_RED . ';">USD ' . fmt2($totalDebe) . '</div>
            <div style="font-size:7.5px;color:#6b7280;margin-top:4px;">Anticipos y pagos</div>
          </td>
        </tr>
      </table>
    </td>
    <td width="3%"></td>
    <!-- SALDO -->
    <td width="31%" style="vertical-align:top;">
      <table width="100%" style="background:' . $saldoBg . ';border:1px solid ' . ($saldo >= 0 ? '#bbf7d0' : '#fecdd3') . ';border-top:3px solid ' . $saldoColor . ';">
        <tr>
          <td style="padding:10px 14px 8px 14px;">
            <div style="font-size:7px;color:' . $saldoColor . ';text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:5px;">&#9654; Saldo Final</div>
            <div style="font-size:16px;font-weight:700;color:' . $saldoColor . ';">' . $saldoSign . 'USD ' . fmt2(abs($saldo)) . '</div>
            <div style="font-size:7.5px;color:#6b7280;margin-top:4px;">' . $saldoLabel . '</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- ═══ SECTION TITLE ═══ -->
<table width="100%" style="margin-bottom:6px;">
  <tr>
    <td style="border-bottom:2px solid ' . $C_NAVY . ';padding-bottom:5px;">
      <span style="font-size:9px;font-weight:700;color:' . $C_NAVY . ';text-transform:uppercase;letter-spacing:1.5px;">Detalle de Movimientos</span>
      <span style="font-size:8px;color:' . $C_GRAY . ';margin-left:8px;">' . $nMov . ' registros</span>
    </td>
  </tr>
</table>

<!-- ═══ TABLA MOVIMIENTOS ═══ -->
<table width="100%" style="border-collapse:collapse;">
  <thead>
    <tr style="background:' . $C_NAVY . ';">
      <th style="padding:7px 8px;text-align:left;font-size:7.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap;width:8%;">Fecha</th>
      <th style="padding:7px 8px;text-align:left;font-size:7.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:10%;">Tipo</th>
      <th style="padding:7px 8px;text-align:left;font-size:7.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;">Concepto</th>
      <th style="padding:7px 8px;text-align:center;font-size:7.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;width:10%;">Referencia</th>
      <th style="padding:7px 8px;text-align:right;font-size:7.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap;width:12%;">Debe (USD)</th>
      <th style="padding:7px 8px;text-align:right;font-size:7.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap;width:12%;">Haber (USD)</th>
      <th style="padding:7px 8px;text-align:right;font-size:7.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;white-space:nowrap;width:12%;">Saldo (USD)</th>
    </tr>
  </thead>
  <tbody>' . $filas . '</tbody>
  <tfoot>
    <tr style="background:' . $C_NAVY . ';">
      <td colspan="4" style="padding:7px 10px;text-align:right;font-size:8.5px;color:#e2e8f0;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Totales</td>
      <td style="padding:7px 8px;text-align:right;font-size:9px;font-weight:700;color:#fca5a5;">USD ' . fmt2($totalDebe) . '</td>
      <td style="padding:7px 8px;text-align:right;font-size:9px;font-weight:700;color:#86efac;">USD ' . fmt2($totalHaber) . '</td>
      <td style="padding:7px 8px;text-align:right;font-size:9px;font-weight:700;color:' . ($saldo >= 0 ? '#86efac' : '#fca5a5') . ';">' . $saldoSign . 'USD ' . fmt2(abs($saldo)) . '</td>
    </tr>
  </tfoot>
</table>

</body>
</html>
';

/* ── Generar PDF con mPDF ──────────────────────────────────────────── */
require_once '../../api/mpdf/mpdf/autoload.php';

$PDF = new \Mpdf\Mpdf([
    'format'        => 'A4',
    'orientation'   => 'L',
    'margin_top'    => 0,
    'margin_bottom' => 14,
    'margin_left'   => 14,
    'margin_right'  => 14,
]);

$PDF->SetHTMLFooter('
<table width="100%" style="border-top:1px solid #cbd5e1;padding-top:4px;">
  <tr>
    <td style="text-align:left;font-size:7.5px;color:#94a3b8;">'
        . htmlspecialchars($NOMBRE_EMPRESA) . '
        <span style="color:#cbd5e1;margin:0 5px;">|</span>
        Cartola Broker: <strong>' . htmlspecialchars($BROKER_NOMBRE) . '</strong>
        <span style="color:#cbd5e1;margin:0 5px;">|</span>
        ' . htmlspecialchars($periodoTexto) . '
    </td>
    <td style="text-align:right;font-size:7.5px;color:#94a3b8;">
      Página <strong>{PAGENO}</strong> de <strong>{nbpg}</strong>
    </td>
  </tr>
</table>
');

$PDF->SetHTMLHeader('', 'O');
$PDF->SetTitle('Cartola Cuenta Corriente - ' . $BROKER_NOMBRE);
$PDF->SetAuthor($NOMBRE_EMPRESA);

$PDF->WriteHTML($html);
$PDF->Output(
    'Cartola_' . preg_replace('/[^a-zA-Z0-9]/', '_', $BROKER_NOMBRE) . '_' . date('Ymd') . '.pdf',
    \Mpdf\Output\Destination::INLINE
);
