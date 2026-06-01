<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();

function fmt($n, $dec = 2) { return number_format((float)$n, $dec, ',', '.'); }

/* ── Tasa EUR→USD ──────────────────────────────────────────────────── */
$clpDolar    = (float)preg_replace('/[^0-9]/', '', $TMONEDA1 ?? '');
$clpEuro     = (float)preg_replace('/[^0-9]/', '', $TMONEDA2 ?? '');
$EUR_USD_DEF = ($clpDolar > 0 && $clpEuro > 0) ? round($clpEuro / $clpDolar, 6) : 1.0;

/* ── KPI 1: Total cargas activas (sin liquidar o en proceso) ──────── */
$stmtCargas = $db->prepare("
    SELECT COUNT(DISTINCT ID_ICARGA) AS total
    FROM view_liquidacion_exportacion
    WHERE ID_EMPRESA = ?
      AND ID_TEMPORADA = ?
      AND ESTADO_LIQUIDACION IN ('Pendiente','Estimado','En proceso')
");
$stmtCargas->execute([$EMPRESAS, $TEMPORADAS]);
$KPI_CARGAS = (int)$stmtCargas->fetchColumn();

/* ── KPI 2: Total liquidado temporada (USD) ───────────────────────── */
$stmtLiquidado = $db->prepare("
    SELECT COALESCE(SUM(vle.RETORNO_NETO), 0) AS total
    FROM liquidacion_valor val
    JOIN fruta_icarga i ON i.ID_ICARGA = val.ID_ICARGA
    JOIN view_liquidacion_exportacion vle ON vle.ID_VALOR = val.ID_VALOR
    WHERE val.ESTADO_REGISTRO = 1
      AND i.ID_EMPRESA = ?
      AND i.ID_TEMPORADA = ?
");
$stmtLiquidado->execute([$EMPRESAS, $TEMPORADAS]);
$KPI_LIQUIDADO = (float)$stmtLiquidado->fetchColumn();

/* ── KPI 3: Saldo pendiente total de brokers (haber - debe) ────────── */
$stmtSaldoBrokers = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN tipo_mov='HABER' THEN monto ELSE 0 END), 0)
      - COALESCE(SUM(CASE WHEN tipo_mov='DEBE'  THEN monto ELSE 0 END), 0) AS saldo
    FROM (
        /* Liquidaciones → HABER */
        SELECT 'HABER' AS tipo_mov, GREATEST(0, ROUND(SUM(vle.RETORNO_NETO), 2)) AS monto
        FROM liquidacion_valor val
        JOIN fruta_icarga i ON i.ID_ICARGA = val.ID_ICARGA
        JOIN view_liquidacion_exportacion vle ON vle.ID_VALOR = val.ID_VALOR
        WHERE val.ESTADO_REGISTRO = 1 AND i.ID_EMPRESA = ? AND i.ID_TEMPORADA = ?
        GROUP BY val.ID_VALOR
        UNION ALL
        /* Anticipos → DEBE */
        SELECT 'DEBE', ROUND(ABS(da.valor_anticipo), 2)
        FROM detalle_anticipo da
        JOIN liquidacion_anticipo a ON a.id_anticipo = da.id_anticipo
        WHERE a.estado_registro = 1 AND a.id_empresa = ? AND a.id_temporada = ?
        UNION ALL
        /* Pagos → DEBE */
        SELECT 'DEBE', ROUND(ABS(p.VALOR_DVALOR), 2)
        FROM liquidacion_dvalorp p
        WHERE p.ESTADO_REGISTRO = 1 AND p.ID_EMPRESA = ? AND p.ID_TEMPORADA = ?
        UNION ALL
        /* Manuales */
        SELECT c.TIPO_MOVIMIENTO, c.MONTO_USD
        FROM cuenta_corriente_broker c
        WHERE c.ESTADO_REGISTRO = 1 AND c.ID_EMPRESA = ? AND c.ID_TEMPORADA = ?
    ) t
");
$stmtSaldoBrokers->execute([$EMPRESAS, $TEMPORADAS, $EMPRESAS, $TEMPORADAS, $EMPRESAS, $TEMPORADAS, $EMPRESAS, $TEMPORADAS]);
$KPI_SALDO = (float)$stmtSaldoBrokers->fetchColumn();

/* ── KPI 4: Brokers activos en la temporada ───────────────────────── */
$stmtBrokers = $db->prepare("
    SELECT COUNT(DISTINCT i.ID_BROKER) AS total
    FROM fruta_icarga i
    WHERE i.ESTADO_REGISTRO = 1
      AND i.ID_EMPRESA = ?
      AND i.ID_TEMPORADA = ?
");
$stmtBrokers->execute([$EMPRESAS, $TEMPORADAS]);
$KPI_BROKERS = (int)$stmtBrokers->fetchColumn();

/* ── Donut: cargas por estado ─────────────────────────────────────── */
$stmtEstados = $db->prepare("
    SELECT ESTADO_LIQUIDACION AS estado, COUNT(DISTINCT ID_ICARGA) AS total
    FROM view_liquidacion_exportacion
    WHERE ID_EMPRESA = ? AND ID_TEMPORADA = ?
    GROUP BY ESTADO_LIQUIDACION
    ORDER BY total DESC
");
$stmtEstados->execute([$EMPRESAS, $TEMPORADAS]);
$ESTADOS = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

/* ── Bar: RETORNO_NETO por mes (temporada actual) ─────────────────── */
$stmtMensual = $db->prepare("
    SELECT DATE_FORMAT(val.FECHA_VALOR, '%Y-%m') AS mes,
           ROUND(SUM(vle.RETORNO_NETO), 2) AS total
    FROM liquidacion_valor val
    JOIN fruta_icarga i ON i.ID_ICARGA = val.ID_ICARGA
    JOIN view_liquidacion_exportacion vle ON vle.ID_VALOR = val.ID_VALOR
    WHERE val.ESTADO_REGISTRO = 1
      AND i.ID_EMPRESA = ?
      AND i.ID_TEMPORADA = ?
    GROUP BY mes
    ORDER BY mes
");
$stmtMensual->execute([$EMPRESAS, $TEMPORADAS]);
$MENSUAL = $stmtMensual->fetchAll(PDO::FETCH_ASSOC);

/* ── Top brokers: saldo pendiente ─────────────────────────────────── */
$stmtTopBrokers = $db->prepare("
    SELECT * FROM (
        SELECT
            b.ID_BROKER,
            b.NOMBRE_BROKER,
            COUNT(DISTINCT i.ID_ICARGA) AS nro_cargas,
            COALESCE(SUM(CASE WHEN t.tipo_mov='HABER' THEN t.monto ELSE 0 END), 0)
          - COALESCE(SUM(CASE WHEN t.tipo_mov='DEBE'  THEN t.monto ELSE 0 END), 0) AS saldo,
            COALESCE(SUM(CASE WHEN t.tipo_mov='HABER' THEN t.monto ELSE 0 END), 0) AS total_haber
        FROM fruta_broker b
        JOIN fruta_icarga i ON i.ID_BROKER = b.ID_BROKER
        LEFT JOIN (
            SELECT i2.ID_BROKER AS broker_id, 'HABER' AS tipo_mov, GREATEST(0, ROUND(SUM(vle.RETORNO_NETO),2)) AS monto
            FROM liquidacion_valor val
            JOIN fruta_icarga i2 ON i2.ID_ICARGA = val.ID_ICARGA
            JOIN view_liquidacion_exportacion vle ON vle.ID_VALOR = val.ID_VALOR
            WHERE val.ESTADO_REGISTRO = 1 AND i2.ID_EMPRESA = ? AND i2.ID_TEMPORADA = ?
            GROUP BY val.ID_VALOR, i2.ID_BROKER
            UNION ALL
            SELECT a.id_broker, 'DEBE', ROUND(ABS(da.valor_anticipo),2)
            FROM detalle_anticipo da
            JOIN liquidacion_anticipo a ON a.id_anticipo = da.id_anticipo
            WHERE a.estado_registro = 1 AND a.id_empresa = ? AND a.id_temporada = ?
            UNION ALL
            SELECT p.ID_BROKER, 'DEBE', ROUND(ABS(p.VALOR_DVALOR),2)
            FROM liquidacion_dvalorp p
            WHERE p.ESTADO_REGISTRO = 1 AND p.ID_EMPRESA = ? AND p.ID_TEMPORADA = ?
            UNION ALL
            SELECT c.ID_BROKER, c.TIPO_MOVIMIENTO, c.MONTO_USD
            FROM cuenta_corriente_broker c
            WHERE c.ESTADO_REGISTRO = 1 AND c.ID_EMPRESA = ? AND c.ID_TEMPORADA = ?
        ) t ON t.broker_id = b.ID_BROKER
        WHERE b.ESTADO_REGISTRO = 1
          AND i.ID_EMPRESA = ?
          AND i.ID_TEMPORADA = ?
        GROUP BY b.ID_BROKER, b.NOMBRE_BROKER
        HAVING nro_cargas > 0
    ) sub
    ORDER BY ABS(sub.saldo) DESC
    LIMIT 10
");
$stmtTopBrokers->execute([$EMPRESAS, $TEMPORADAS, $EMPRESAS, $TEMPORADAS, $EMPRESAS, $TEMPORADAS, $EMPRESAS, $TEMPORADAS, $EMPRESAS, $TEMPORADAS]);
$TOP_BROKERS = $stmtTopBrokers->fetchAll(PDO::FETCH_ASSOC);

/* ── Últimas liquidaciones ─────────────────────────────────────────── */
$stmtUltimas = $db->prepare("
    SELECT
        val.NUMERO_VALOR,
        val.FECHA_VALOR,
        i.NUMERO_ICARGA,
        i.NREFERENCIA_ICARGA,
        b.NOMBRE_BROKER,
        i.ID_BROKER,
        ROUND(SUM(vle.RETORNO_NETO), 2) AS retorno_neto,
        MAX(vle.ESTADO_LIQUIDACION) AS estado_liquidacion
    FROM liquidacion_valor val
    JOIN fruta_icarga i ON i.ID_ICARGA = val.ID_ICARGA
    JOIN view_liquidacion_exportacion vle ON vle.ID_VALOR = val.ID_VALOR
    LEFT JOIN fruta_broker b ON b.ID_BROKER = i.ID_BROKER
    WHERE val.ESTADO_REGISTRO = 1
      AND i.ID_EMPRESA = ?
    GROUP BY val.ID_VALOR
    ORDER BY val.FECHA_VALOR DESC, val.ID_VALOR DESC
    LIMIT 10
");
$stmtUltimas->execute([$EMPRESAS]);
$ULTIMAS_LIQ = $stmtUltimas->fetchAll(PDO::FETCH_ASSOC);

/* ── Datos para C3 ─────────────────────────────────────────────────── */
$donutLabels = [];
$donutData   = [];
foreach ($ESTADOS as $e) {
    $donutLabels[] = ucfirst($e['estado']);
    $donutData[]   = (int)$e['total'];
}

$barMeses   = [];
$barTotales = [];
foreach ($MENSUAL as $m) {
    // formato "Ene 2025"
    $dt = DateTime::createFromFormat('Y-m', $m['mes']);
    $barMeses[]   = $dt ? $dt->format('M Y') : $m['mes'];
    $barTotales[] = (float)$m['total'];
}

$estadoLabel = [
    'Pendiente'  => ['badge-warning',  'Pendiente'],
    'En proceso' => ['badge-info',     'En proceso'],
    'Estimado'   => ['badge-warning',  'Estimado'],
    'Liquidado'  => ['badge-success',  'Liquidado'],
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Dashboard Exportadora</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
</head>
<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
<div class="wrapper">
    <?php include_once "../../assest/config/menuExpo.php"; ?>

    <div class="content-wrapper">
        <div class="container-full">

            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="mr-auto">
                        <h3 class="page-title">Dashboard Exportadora</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item active">Inicio</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>

            <section class="content">
                <div class="row">

                    <!-- KPI 1: Cargas activas -->
                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="box pull-up" style="border-left:4px solid #0080ff;">
                            <div class="box-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-5 text-fade font-size-13 text-uppercase">Cargas activas</p>
                                        <h2 class="mb-0 font-weight-700 text-primary"><?php echo $KPI_CARGAS; ?></h2>
                                        <small class="text-fade">Pendientes + estimadas</small>
                                    </div>
                                    <div style="width:50px;height:50px;border-radius:50%;background:#e6f2ff;display:flex;align-items:center;justify-content:center;">
                                        <i class="mdi mdi-truck-fast font-size-22 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Total liquidado temporada -->
                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="box pull-up" style="border-left:4px solid #18d26b;">
                            <div class="box-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-5 text-fade font-size-13 text-uppercase">Liquidado temporada</p>
                                        <h2 class="mb-0 font-weight-700 text-success">USD <?php echo fmt($KPI_LIQUIDADO); ?></h2>
                                        <small class="text-fade">Retorno neto acumulado</small>
                                    </div>
                                    <div style="width:50px;height:50px;border-radius:50%;background:#e8fdf1;display:flex;align-items:center;justify-content:center;">
                                        <i class="mdi mdi-currency-usd font-size-22 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Saldo brokers -->
                    <?php
                    $saldoColor   = $KPI_SALDO >= 0 ? '#00b8d4'  : '#ff3f3f';
                    $saldoBgIcon  = $KPI_SALDO >= 0 ? '#e0f9fd'  : '#ffe5e5';
                    $saldoClass   = $KPI_SALDO >= 0 ? 'text-info' : 'text-danger';
                    $saldoLabel   = $KPI_SALDO >= 0 ? 'A favor'   : 'A deber';
                    ?>
                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="box pull-up" style="border-left:4px solid <?php echo $saldoColor; ?>;">
                            <div class="box-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-5 text-fade font-size-13 text-uppercase">Saldo brokers</p>
                                        <h2 class="mb-0 font-weight-700 <?php echo $saldoClass; ?>">USD <?php echo fmt($KPI_SALDO); ?></h2>
                                        <small class="text-fade"><?php echo $saldoLabel; ?></small>
                                    </div>
                                    <div style="width:50px;height:50px;border-radius:50%;background:<?php echo $saldoBgIcon; ?>;display:flex;align-items:center;justify-content:center;">
                                        <i class="mdi mdi-scale-balance font-size-22 <?php echo $saldoClass; ?>"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Brokers activos -->
                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="box pull-up" style="border-left:4px solid #ffa800;">
                            <div class="box-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="mb-5 text-fade font-size-13 text-uppercase">Brokers activos</p>
                                        <h2 class="mb-0 font-weight-700 text-warning"><?php echo $KPI_BROKERS; ?></h2>
                                        <small class="text-fade">En la temporada</small>
                                    </div>
                                    <div style="width:50px;height:50px;border-radius:50%;background:#fff4e0;display:flex;align-items:center;justify-content:center;">
                                        <i class="mdi mdi-account-group font-size-22 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /row KPIs -->

                <div class="row">

                    <!-- Donut: estados de cargas -->
                    <div class="col-xl-5 col-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">Estado de cargas</h4>
                                <span class="text-muted font-size-13">Temporada actual</span>
                            </div>
                            <div class="box-body">
                                <?php if (empty($ESTADOS)): ?>
                                    <p class="text-muted text-center py-30">Sin datos en esta temporada.</p>
                                <?php else: ?>
                                    <div id="chart-estados" style="height:260px;"></div>
                                    <div class="mt-10" id="legend-estados"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Bar: liquidado por mes -->
                    <div class="col-xl-7 col-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">Retorno neto por mes (USD)</h4>
                                <span class="text-muted font-size-13">Temporada actual</span>
                            </div>
                            <div class="box-body">
                                <?php if (empty($MENSUAL)): ?>
                                    <p class="text-muted text-center py-30">Sin liquidaciones en esta temporada.</p>
                                <?php else: ?>
                                    <div id="chart-mensual" style="height:260px;"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div><!-- /row charts -->

                <div class="row">

                    <!-- Top brokers -->
                    <div class="col-xl-5 col-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">Saldo por broker</h4>
                                <span class="text-muted font-size-13">Temporada actual · Top 10</span>
                            </div>
                            <div class="box-body p-0">
                                <?php if (empty($TOP_BROKERS)): ?>
                                    <p class="text-muted text-center py-30">Sin datos.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Broker</th>
                                            <th class="text-right">Cargas</th>
                                            <th class="text-right">Liquidado USD</th>
                                            <th class="text-right">Saldo USD</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($TOP_BROKERS as $b): ?>
                                        <?php $saldo = (float)$b['saldo']; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($b['NOMBRE_BROKER']); ?></td>
                                            <td class="text-right"><?php echo (int)$b['nro_cargas']; ?></td>
                                            <td class="text-right"><?php echo fmt($b['total_haber']); ?></td>
                                            <td class="text-right <?php echo $saldo >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <strong><?php echo fmt($saldo); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <a href="cuentaCorrienteBroker.php?ID_BROKER=<?php echo (int)$b['ID_BROKER']; ?>"
                                                   class="btn btn-xs btn-outline-info" title="Ver cuenta corriente">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Últimas liquidaciones -->
                    <div class="col-xl-7 col-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">Últimas liquidaciones</h4>
                                <span class="text-muted font-size-13">10 más recientes</span>
                            </div>
                            <div class="box-body p-0">
                                <?php if (empty($ULTIMAS_LIQ)): ?>
                                    <p class="text-muted text-center py-30">Sin liquidaciones registradas.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>N° Liqui.</th>
                                            <th>Fecha</th>
                                            <th>IC#</th>
                                            <th>Broker</th>
                                            <th class="text-right">Retorno USD</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($ULTIMAS_LIQ as $liq):
                                        $estadoBadge = $estadoLabel[$liq['estado_liquidacion']] ?? ['badge-secondary', $liq['estado_liquidacion']];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($liq['NUMERO_VALOR']); ?></td>
                                            <td><?php echo htmlspecialchars($liq['FECHA_VALOR']); ?></td>
                                            <td>
                                                IC#<?php echo htmlspecialchars($liq['NUMERO_ICARGA']); ?>
                                                <?php if ($liq['NREFERENCIA_ICARGA']): ?>
                                                    <small class="text-muted">(<?php echo htmlspecialchars($liq['NREFERENCIA_ICARGA']); ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($liq['NOMBRE_BROKER'] ?? '—'); ?></td>
                                            <td class="text-right <?php echo (float)$liq['retorno_neto'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <strong><?php echo fmt($liq['retorno_neto']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $estadoBadge[0]; ?>">
                                                    <?php echo $estadoBadge[1]; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div><!-- /row tables -->

            </section>
        </div>
    </div>

    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>

<script>
<?php if (!empty($ESTADOS)): ?>
(function () {
    var labels  = <?php echo json_encode($donutLabels); ?>;
    var valores = <?php echo json_encode($donutData); ?>;
    var columns = labels.map(function(l, i) { return [l, valores[i]]; });

    var chart = c3.generate({
        bindto: '#chart-estados',
        data: { columns: columns, type: 'donut' },
        donut: { label: { format: function(v) { return v; } } },
        legend: { show: false }
    });

    /* leyenda manual */
    var legend = document.getElementById('legend-estados');
    var colors = chart.color();
    labels.forEach(function(l, i) {
        var d = document.createElement('span');
        d.className = 'mr-15 font-size-13';
        d.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' + colors[l] + ';margin-right:4px;"></span>' + l + ' (' + valores[i] + ')';
        legend.appendChild(d);
    });
})();
<?php endif; ?>

<?php if (!empty($MENSUAL)): ?>
c3.generate({
    bindto: '#chart-mensual',
    data: {
        x: 'x',
        columns: [
            <?php
            $xCol = array_merge(['x'], $barMeses);
            $yCol = array_merge(['Retorno USD'], $barTotales);
            echo json_encode($xCol) . ',';
            echo json_encode($yCol);
            ?>
        ],
        type: 'bar'
    },
    axis: {
        x: { type: 'category', tick: { rotate: -30, multiline: false } },
        y: { tick: { format: function(v) { return '$' + v.toLocaleString('es-CL', {minimumFractionDigits:0}); } } }
    },
    bar: { width: { ratio: 0.5 } },
    legend: { show: false }
});
<?php endif; ?>
</script>

</body>
</html>
