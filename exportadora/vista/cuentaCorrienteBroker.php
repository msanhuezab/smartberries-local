<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmt($n, $dec = 2) { return number_format((float)$n, $dec, ',', '.'); }

$FECHA_HOY = date('Y-m-d');
$mensaje   = '';

/* categorías válidas → dirección contable automática */
$CATEGORIAS = [
    'Anticipo'     => 'DEBE',
    'Pago'         => 'DEBE',
    'Nota Crédito' => 'DEBE',
    'Nota Débito'  => 'HABER',
    'Ajuste DEBE'  => 'DEBE',
    'Ajuste HABER' => 'HABER',
];

/* ── Guardar movimiento ────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['GUARDAR_MOVIMIENTO'])) {
    $idBroker   = (int)$_POST['ID_BROKER'];
    $fecha      = $_POST['FECHA_MOVIMIENTO'] ?? $FECHA_HOY;
    $categoria  = $_POST['CATEGORIA'] ?? '';
    $tipo       = $CATEGORIAS[$categoria] ?? 'DEBE';
    $concepto   = trim($_POST['CONCEPTO'] ?? '');
    $moneda     = in_array($_POST['MONEDA_ORIGEN'], ['USD','EUR']) ? $_POST['MONEDA_ORIGEN'] : 'USD';
    $tc         = max(0.000001, (float)str_replace(',', '.', $_POST['TIPO_CAMBIO_USD'] ?? '1'));
    $montoOrig  = max(0, (float)str_replace(',', '.', $_POST['MONTO_ORIGEN'] ?? '0'));
    $montoUsd   = $moneda === 'EUR' ? round($montoOrig * $tc, 2) : $montoOrig;
    $ref        = trim($_POST['REFERENCIA'] ?? '');

    if ($idBroker > 0 && $montoUsd > 0 && $concepto !== '' && isset($CATEGORIAS[$categoria])) {
        $db->prepare("
            INSERT INTO cuenta_corriente_broker
                (ID_BROKER, FECHA_MOVIMIENTO, TIPO_MOVIMIENTO, CATEGORIA, CONCEPTO,
                 MONTO_USD, MONEDA_ORIGEN, TIPO_CAMBIO_USD, REFERENCIA,
                 ESTADO_REGISTRO, ID_EMPRESA, ID_TEMPORADA, ID_USUARIOI, ID_USUARIOM)
            VALUES (?,?,?,?,?, ?,?,?,?, 1,?,?,?,?)
        ")->execute([
            $idBroker, $fecha, $tipo, $categoria, $concepto,
            $montoUsd, $moneda, $tc, $ref,
            $EMPRESAS, $TEMPORADAS, $IDUSUARIOS, $IDUSUARIOS
        ]);
        $mensaje = 'ok:Movimiento registrado.';
    } else {
        $mensaje = 'err:Faltan datos obligatorios.';
    }
}

/* ── Eliminar movimiento manual ────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ELIMINAR_MOVIMIENTO'])) {
    $idCC = (int)$_POST['ID_CCBROKER'];
    if ($idCC > 0) {
        $db->prepare("UPDATE cuenta_corriente_broker SET ESTADO_REGISTRO=0, ID_USUARIOM=?, MODIFICACION=SYSDATE() WHERE ID_CCBROKER=? AND ID_EMPRESA=?")
           ->execute([$IDUSUARIOS, $idCC, $EMPRESAS]);
        $mensaje = 'ok:Movimiento eliminado.';
    }
}

/* ── Filtros GET ───────────────────────────────────────────────────── */
$filterBroker  = isset($_GET['ID_BROKER'])    ? (int)$_GET['ID_BROKER']    : 0;
$filterDesde   = $_GET['FECHA_DESDE'] ?? '';
$filterHasta   = $_GET['FECHA_HASTA'] ?? '';

/* ── Tasa EUR→USD (necesaria antes de construir el SQL) ────────────── */
$clpDolar    = (float)preg_replace('/[^0-9]/', '', $TMONEDA1 ?? '');
$clpEuro     = (float)preg_replace('/[^0-9]/', '', $TMONEDA2 ?? '');
$EUR_USD_DEF = ($clpDolar > 0 && $clpEuro > 0) ? round($clpEuro / $clpDolar, 6) : 1.0;

/* ── Catálogo de brokers ───────────────────────────────────────────── */
$stmtBrokers = $db->prepare("SELECT ID_BROKER, NOMBRE_BROKER FROM fruta_broker WHERE ESTADO_REGISTRO=1 AND ID_EMPRESA=? ORDER BY NOMBRE_BROKER");
$stmtBrokers->execute([$EMPRESAS]);
$BROKERS = $stmtBrokers->fetchAll(PDO::FETCH_ASSOC);

/* ── Movimientos (solo si hay broker seleccionado) ─────────────────── */
$MOVIMIENTOS = [];
$BROKER_NOMBRE = '';
if ($filterBroker > 0) {
    foreach ($BROKERS as $b) {
        if ((int)$b['ID_BROKER'] === $filterBroker) { $BROKER_NOMBRE = $b['NOMBRE_BROKER']; break; }
    }

    $condFecha = '';
    $params    = [$filterBroker, $EMPRESAS];
    if ($filterDesde !== '') { $condFecha .= " AND fecha >= ?"; $params[] = $filterDesde; }
    if ($filterHasta !== '') { $condFecha .= " AND fecha <= ?"; $params[] = $filterHasta; }

    $sql = "
    SELECT fecha, tipo, concepto, referencia, debe, haber, id_doc, es_manual
    FROM (

        /* 1. Liquidaciones → HABER (retorno neto por liquidación) */
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

        /* 2. Anticipos → DEBE (dinero ya recibido del broker) */
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

        /* 3. Pagos (liquidacion_dvalorp) → DEBE */
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

        /* 4. Estimados: cargas con precio referencia pero sin liquidación, con conversión EUR→USD */
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

        /* 5. Pendientes: cargas sin precio referencia y sin liquidación */
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

        /* 6. Movimientos de cuenta corriente (anticipo, pago, ajuste, etc.) */
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

    /* bind: 6 fuentes × 3 params (broker + empresa + temporada) = 18, más los de fecha */
    $bindParams = [];
    for ($i = 0; $i < 6; $i++) {
        $bindParams[] = $filterBroker;
        $bindParams[] = $EMPRESAS;
        $bindParams[] = $TEMPORADAS;
    }
    $bindParams = array_merge($bindParams, array_slice($params, 2));

    $stmt = $db->prepare($sql);
    $stmt->execute($bindParams);
    $MOVIMIENTOS = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

/* ── Datos para formulario de movimiento manual ────────────────────── */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Cuenta Corriente Broker</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
    @media print {
        /* ocultar todo excepto la sección de impresión */
        body * { visibility: hidden; }
        #print-area, #print-area * { visibility: visible; }
        #print-area {
            position: absolute;
            left: 0; top: 0;
            width: 100%;
        }
        /* sin bordes de box del tema */
        #print-area .box { box-shadow: none !important; border: none !important; }
        /* ocultar botones de acción en la tabla */
        #print-area .no-print { display: none !important; }
        /* tabla más compacta */
        #print-area table { font-size: 11px; }
        #print-area th, #print-area td { padding: 4px 6px !important; }
        /* encabezado de impresión */
        #print-header { display: block !important; }
        /* forzar colores en impresión */
        #print-area .text-danger { color: #c0392b !important; -webkit-print-color-adjust: exact; }
        #print-area .text-success { color: #27ae60 !important; -webkit-print-color-adjust: exact; }
        #print-area thead { background-color: #f5f5f5 !important; -webkit-print-color-adjust: exact; }
        @page { margin: 15mm; size: A4 landscape; }
    }
    #print-header { display: none; }
    </style>
</head>
<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
<div class="wrapper">
    <?php include_once "../../assest/config/menuExpo.php"; ?>

    <div class="content-wrapper">
        <div class="container-full">

            <!-- Header -->
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="mr-auto">
                        <h3 class="page-title">Cuenta Corriente por Broker</h3>
                        <nav><ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                            <li class="breadcrumb-item">Liquidación</li>
                            <li class="breadcrumb-item active">Cuenta Corriente Broker</li>
                        </ol></nav>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>

            <section class="content">

                <?php if ($mensaje !== ''): ?>
                    <?php [$tipo_msg, $texto_msg] = explode(':', $mensaje, 2); ?>
                    <div class="alert alert-<?php echo $tipo_msg === 'ok' ? 'success' : 'danger'; ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo h($texto_msg); ?>
                    </div>
                <?php endif; ?>

                <!-- ─── Filtros ─── -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h4 class="box-title">Filtros</h4>
                    </div>
                    <div class="box-body">
                        <form method="GET" class="form-inline flex-wrap" style="gap:8px;">
                            <div class="form-group">
                                <label class="mr-1">Broker</label>
                                <select name="ID_BROKER" class="form-control select2" style="min-width:220px;" required>
                                    <option value="">— Seleccione —</option>
                                    <?php foreach ($BROKERS as $b): ?>
                                        <option value="<?php echo h($b['ID_BROKER']); ?>"
                                            <?php echo (int)$b['ID_BROKER'] === $filterBroker ? 'selected' : ''; ?>>
                                            <?php echo h($b['NOMBRE_BROKER']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="mr-1">Desde</label>
                                <input type="date" name="FECHA_DESDE" class="form-control" value="<?php echo h($filterDesde); ?>">
                            </div>
                            <div class="form-group">
                                <label class="mr-1">Hasta</label>
                                <input type="date" name="FECHA_HASTA" class="form-control" value="<?php echo h($filterHasta); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Consultar
                            </button>
                            <?php if ($filterBroker > 0): ?>
                                <a href="?ID_BROKER=<?php echo $filterBroker; ?>" class="btn btn-default">
                                    <i class="fa fa-times"></i> Limpiar fechas
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <?php if ($filterBroker > 0): ?>

                <a id="resultados"></a>
                <div id="print-area">

                <!-- encabezado visible solo al imprimir -->
                <div id="print-header" style="margin-bottom:16px;">
                    <table style="width:100%;border-bottom:2px solid #333;padding-bottom:8px;">
                        <tr>
                            <td>
                                <div style="font-size:18px;font-weight:700;">Cuenta Corriente Broker</div>
                                <div style="font-size:14px;margin-top:4px;"><strong>Broker:</strong> <?php echo h($BROKER_NOMBRE); ?></div>
                                <?php if ($filterDesde || $filterHasta): ?>
                                <div style="font-size:12px;color:#555;">
                                    Período:
                                    <?php echo $filterDesde ? 'desde ' . h($filterDesde) : ''; ?>
                                    <?php echo $filterHasta ? ' hasta ' . h($filterHasta) : ''; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;vertical-align:top;">
                                <div style="font-size:11px;color:#555;">Fecha impresión: <?php echo date('d/m/Y H:i'); ?></div>
                                <div style="margin-top:8px;display:inline-flex;gap:20px;">
                                    <div style="text-align:center;">
                                        <div style="font-size:10px;color:#555;">TOTAL HABER</div>
                                        <div style="font-size:14px;font-weight:700;color:#1a7abf;">$ <?php echo fmt($totalHaber); ?></div>
                                    </div>
                                    <div style="text-align:center;">
                                        <div style="font-size:10px;color:#555;">TOTAL DEBE</div>
                                        <div style="font-size:14px;font-weight:700;color:#c0392b;">$ <?php echo fmt($totalDebe); ?></div>
                                    </div>
                                    <div style="text-align:center;">
                                        <div style="font-size:10px;color:#555;">SALDO</div>
                                        <div style="font-size:14px;font-weight:700;color:<?php echo $saldo >= 0 ? '#27ae60' : '#c0392b'; ?>;">$ <?php echo fmt($saldo); ?></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- ─── Resumen ─── -->
                <div class="row mb-10">
                    <div class="col-md-4">
                        <div class="box box-body text-center py-10">
                            <div class="text-muted" style="font-size:12px;">TOTAL HABER (USD)</div>
                            <div style="font-size:22px;font-weight:700;color:#1a7abf;">
                                $ <?php echo fmt($totalHaber); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="box box-body text-center py-10">
                            <div class="text-muted" style="font-size:12px;">TOTAL DEBE (USD)</div>
                            <div style="font-size:22px;font-weight:700;color:#c0392b;">
                                $ <?php echo fmt($totalDebe); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="box box-body text-center py-10">
                            <div class="text-muted" style="font-size:12px;">SALDO PENDIENTE (USD)</div>
                            <div style="font-size:22px;font-weight:700;color:<?php echo $saldo >= 0 ? '#27ae60' : '#c0392b'; ?>;">
                                $ <?php echo fmt($saldo); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── Tabla movimientos ─── -->
                <div class="box">
                    <div class="box-header with-border">
                        <h4 class="box-title">
                            <?php echo h($BROKER_NOMBRE); ?>
                            <?php if ($filterDesde || $filterHasta): ?>
                                <small class="text-muted">
                                    <?php echo $filterDesde ? 'desde ' . h($filterDesde) : ''; ?>
                                    <?php echo $filterHasta ? ' hasta ' . h($filterHasta) : ''; ?>
                                </small>
                            <?php endif; ?>
                        </h4>
                        <div class="box-tools">
                            <button class="btn btn-default btn-sm mr-5" type="button" onclick="window.print();">
                                <i class="fa fa-print"></i> Imprimir
                            </button>
                            <?php if ($filterBroker > 0): ?>
                            <a class="btn btn-danger btn-sm mr-5"
                               href="pdfCartolaBroker.php?ID_BROKER=<?= $filterBroker ?>&FECHA_DESDE=<?= urlencode($filterDesde) ?>&FECHA_HASTA=<?= urlencode($filterHasta) ?>"
                               target="_blank">
                                <i class="fa fa-file-pdf-o"></i> Descargar PDF
                            </a>
                            <?php endif; ?>
                            <button class="btn btn-success btn-sm" type="button"
                                    data-toggle="collapse" data-target="#panelMovManual">
                                <i class="fa fa-plus"></i> Agregar movimiento
                            </button>
                        </div>
                    </div>

                    <!-- ─── Formulario movimiento manual ─── -->
                    <div class="collapse" id="panelMovManual">
                        <div class="box-body border-bottom bg-light">
                            <form method="POST">
                                <input type="hidden" name="GUARDAR_MOVIMIENTO" value="1">
                                <input type="hidden" name="ID_BROKER" value="<?php echo $filterBroker; ?>">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Fecha <span class="text-danger">*</span></label>
                                            <input type="date" name="FECHA_MOVIMIENTO" class="form-control" value="<?php echo $FECHA_HOY; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Categoría <span class="text-danger">*</span></label>
                                            <select name="CATEGORIA" class="form-control" id="selectCategoria" required>
                                                <optgroup label="— DEBE (recibido del broker)">
                                                    <option value="Anticipo">Anticipo</option>
                                                    <option value="Pago">Pago</option>
                                                    <option value="Nota Crédito">Nota Crédito</option>
                                                    <option value="Ajuste DEBE">Ajuste DEBE</option>
                                                </optgroup>
                                                <optgroup label="— HABER (a favor de la empresa)">
                                                    <option value="Nota Débito">Nota Débito</option>
                                                    <option value="Ajuste HABER">Ajuste HABER</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Moneda</label>
                                            <select name="MONEDA_ORIGEN" class="form-control" id="selectMonedaMov">
                                                <option value="USD">USD</option>
                                                <option value="EUR">EUR</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2" id="panelTcMov" style="display:none;">
                                        <div class="form-group">
                                            <label>T/C EUR→USD</label>
                                            <input type="number" step="0.000001" name="TIPO_CAMBIO_USD"
                                                   id="tcMovInput" class="form-control"
                                                   value="<?php echo h(number_format($EUR_USD_DEF, 6, '.', '')); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Monto <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" min="0" name="MONTO_ORIGEN"
                                                   class="form-control" placeholder="0.00" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Referencia</label>
                                            <input type="text" name="REFERENCIA" class="form-control" placeholder="Doc., factura…">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Concepto <span class="text-danger">*</span></label>
                                            <input type="text" name="CONCEPTO" class="form-control"
                                                   placeholder="Descripción del movimiento" required maxlength="300">
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary mr-5">
                                            <i class="fa fa-save"></i> Guardar
                                        </button>
                                        <button type="button" class="btn btn-default"
                                                data-toggle="collapse" data-target="#panelMovManual">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="box-body p-0">
                        <?php if (count($MOVIMIENTOS) === 0): ?>
                            <div class="text-center text-muted py-30">
                                No hay movimientos para este broker<?php echo ($filterDesde || $filterHasta) ? ' en el rango de fechas seleccionado' : ''; ?>.
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table id="cc-movimientos" class="table table-hover table-sm mb-0" style="font-size:13px;width:100%;">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width:95px;">Fecha</th>
                                        <th style="width:110px;">Tipo</th>
                                        <th>Concepto</th>
                                        <th>Referencia</th>
                                        <th class="text-right" style="width:120px;">Debe (USD)</th>
                                        <th class="text-right" style="width:120px;">Haber (USD)</th>
                                        <th class="text-right" style="width:130px;">Saldo (USD)</th>
                                        <th class="no-print" style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($MOVIMIENTOS as $mov): ?>
                                    <?php
                                        $debe  = (float)$mov['debe'];
                                        $haber = (float)$mov['haber'];
                                        $sal   = (float)$mov['saldo_acum'];
                                        $tipoClass = match(true) {
                                            str_contains($mov['tipo'], 'Liquidaci')  => 'badge-primary',
                                            $mov['tipo'] === 'Estimado'              => 'badge-warning',
                                            $mov['tipo'] === 'Pendiente'             => 'badge-default',
                                            str_contains($mov['tipo'], 'Anticipo')   => 'badge-info',
                                            str_contains($mov['tipo'], 'Pago')       => 'badge-info',
                                            str_contains($mov['tipo'], 'Crédito')    => 'badge-success',
                                            str_contains($mov['tipo'], 'Débito')     => 'badge-danger',
                                            str_contains($mov['tipo'], 'Ajuste')     => 'badge-secondary',
                                            default => 'badge-secondary',
                                        };
                                    ?>
                                    <tr>
                                        <td><?php echo h($mov['fecha']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $tipoClass; ?>" style="font-size:11px;">
                                                <?php echo h($mov['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo h($mov['concepto']); ?></td>
                                        <td><?php echo h($mov['referencia']); ?></td>
                                        <td class="text-right text-danger">
                                            <?php echo $debe > 0 ? '$ ' . fmt($debe) : '—'; ?>
                                        </td>
                                        <td class="text-right text-success">
                                            <?php echo $haber > 0 ? '$ ' . fmt($haber) : '—'; ?>
                                        </td>
                                        <td class="text-right font-weight-bold"
                                            style="color:<?php echo $sal >= 0 ? '#27ae60' : '#c0392b'; ?>">
                                            $ <?php echo fmt($sal); ?>
                                        </td>
                                        <td class="text-center no-print" style="white-space:nowrap;">
                                            <?php if ($mov['tipo'] === 'Estimado' || $mov['tipo'] === 'Pendiente'): ?>
                                                <a href="registroLiquidacionExp.php?ICARGA=<?php echo (int)$mov['id_doc']; ?>"
                                                   class="btn btn-xs btn-outline-warning mr-2"
                                                   title="Ir a liquidación IC#<?php echo h($mov['id_doc']); ?>">
                                                    <i class="fa fa-file-text-o"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ((int)$mov['es_manual'] === 1): ?>
                                                <form method="POST" style="margin:0;display:inline;">
                                                    <input type="hidden" name="ELIMINAR_MOVIMIENTO" value="1">
                                                    <input type="hidden" name="ID_CCBROKER" value="<?php echo h($mov['id_doc']); ?>">
                                                    <input type="hidden" name="ID_BROKER" value="<?php echo $filterBroker; ?>">
                                                    <button type="submit" class="btn btn-xs btn-danger"
                                                            title="Eliminar"
                                                            onclick="return confirm('¿Eliminar este movimiento?');">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="4" class="text-right">TOTALES</td>
                                        <td class="text-right text-danger">$ <?php echo fmt($totalDebe); ?></td>
                                        <td class="text-right text-success">$ <?php echo fmt($totalHaber); ?></td>
                                        <td class="text-right"
                                            style="color:<?php echo $saldo >= 0 ? '#27ae60' : '#c0392b'; ?>">
                                            $ <?php echo fmt($saldo); ?>
                                        </td>
                                        <td class="no-print"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                </div><!-- /print-area -->

                <?php endif; /* filterBroker > 0 */ ?>

            </section>
        </div>
    </div>

    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>

<script>
$(function () {
    <?php if ($filterBroker > 0 && count($MOVIMIENTOS) > 0): ?>
    $('#cc-movimientos').DataTable({
        scrollY:      '450px',
        scrollX:      true,
        scrollCollapse: true,
        paging:       false,
        ordering:     false,
        searching:    false,
        info:         false,
        autoWidth:    false,
        language: {
            zeroRecords:  'Sin movimientos',
            emptyTable:   'Sin movimientos'
        }
    });
    <?php endif; ?>
});

(function () {
    /* Scroll automático a resultados cuando hay broker seleccionado */
    <?php if ($filterBroker > 0): ?>
    setTimeout(function () {
        var el = document.getElementById('resultados');
        if (!el) return;
        var header = document.querySelector('.main-header') || document.querySelector('.navbar');
        var offset = header ? header.offsetHeight : 60;
        var top = el.getBoundingClientRect().top + window.pageYOffset - offset - 10;
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    }, 150);
    <?php endif; ?>

    /* Mostrar/ocultar panel tipo de cambio según moneda seleccionada */
    var selMoneda = document.getElementById('selectMonedaMov');
    var panelTc   = document.getElementById('panelTcMov');
    if (selMoneda) {
        selMoneda.addEventListener('change', function () {
            panelTc.style.display = this.value === 'EUR' ? '' : 'none';
        });
    }
})();
</script>
</body>
</html>
