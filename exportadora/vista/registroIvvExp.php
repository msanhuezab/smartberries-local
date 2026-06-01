<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();
$IDICARGA = isset($_REQUEST['ICARGA']) ? (int)$_REQUEST['ICARGA'] : 0;
$mensaje = "";
$error = "";
$TOLERANCIA = 1.00;

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function numero($valor, $decimales = 2) {
    return number_format((float)$valor, $decimales, ',', '.');
}

function documentoSugerido($diferencia, $tolerancia = 1.00) {
    if (abs((float)$diferencia) <= $tolerancia) {
        return 'SIN_NOTA';
    }
    return (float)$diferencia > 0 ? 'NOTA_DEBITO' : 'NOTA_CREDITO';
}

function nombreDocumento($codigo) {
    if ($codigo === 'NOTA_DEBITO') {
        return 'Nota de Debito';
    }
    if ($codigo === 'NOTA_CREDITO') {
        return 'Nota de Credito';
    }
    return 'Sin nota';
}

function obtenerResumenReferencia($db, $empresa, $temporada, $idIcarga) {
    $stmt = $db->prepare("
        SELECT
            i.ID_ICARGA,
            i.NUMERO_ICARGA,
            i.NREFERENCIA_ICARGA,
            COALESCE(NULLIF(i.NCONTENEDOR_ICARGA, ''), dx.CONTENEDOR_DESPACHO, '') AS NCONTENEDOR_ICARGA,
            i.DUS_ICARGA,
            i.BOLAWBCRT_ICARGA,
            i.BOOKING_ICARGA,
            IFNULL(merc.NOMBRE_MERCADO, '') AS NOMBRE_MERCADO,
            IFNULL(cons.NOMBRE_CONSIGNATARIO, '') AS NOMBRE_CONSIGNATARIO,
            inv.ID_INVOICE,
            inv.NUMERO_INVOICE,
            inv.FECHA_INVOICE,
            inv.MONEDA_INVOICE,
            inv.TIPO_CAMBIO_USD,
            inv.TOTAL_INVOICE_USD,
            liq.ID_VALOR,
            liq.NUMERO_VALOR,
            liq.FECHA_VALOR,
            liq.ESTADO_LIQUIDACION,
            liq.TOTAL_LIQUIDACION_USD,
            ivv.ID_IVV,
            ivv.NUMERO_IVV,
            ivv.FECHA_IVV,
            ivv.ESTADO_IVV,
            ivv.OBSERVACION_IVV
        FROM fruta_icarga i
        LEFT JOIN fruta_mercado merc ON merc.ID_MERCADO = i.ID_MERCADO
        LEFT JOIN fruta_consignatario cons ON cons.ID_CONSIGNATARIO = i.ID_CONSIGNATARIO
        LEFT JOIN (
            SELECT d.ID_ICARGA, MIN(NULLIF(NULLIF(d.NUMERO_CONTENEDOR_DESPACHOEX, ''), '0')) AS CONTENEDOR_DESPACHO
            FROM fruta_despachoex d
            WHERE d.ESTADO_REGISTRO = 1
            GROUP BY d.ID_ICARGA
        ) dx ON dx.ID_ICARGA = i.ID_ICARGA
        LEFT JOIN (
            SELECT
                inv.ID_ICARGA,
                inv.ID_INVOICE,
                inv.NUMERO_INVOICE,
                inv.FECHA_INVOICE,
                inv.MONEDA_INVOICE,
                inv.TIPO_CAMBIO_USD,
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
            GROUP BY inv.ID_ICARGA, inv.ID_INVOICE, inv.NUMERO_INVOICE, inv.FECHA_INVOICE, inv.MONEDA_INVOICE, inv.TIPO_CAMBIO_USD
        ) inv ON inv.ID_ICARGA = i.ID_ICARGA
        LEFT JOIN (
            SELECT
                ID_ICARGA,
                MAX(ID_VALOR) AS ID_VALOR,
                MAX(NUMERO_VALOR) AS NUMERO_VALOR,
                MAX(FECHA_VALOR) AS FECHA_VALOR,
                MAX(ESTADO_LIQUIDACION) AS ESTADO_LIQUIDACION,
                SUM(IFNULL(VENTA_USD_NETO, 0)) AS TOTAL_LIQUIDACION_USD
            FROM view_liquidacion_exportacion
            WHERE ID_EMPRESA = ?
            AND ID_TEMPORADA = ?
            GROUP BY ID_ICARGA
        ) liq ON liq.ID_ICARGA = i.ID_ICARGA
        LEFT JOIN exportadora_ivv ivv ON ivv.ID_ICARGA = i.ID_ICARGA
            AND ivv.ESTADO_REGISTRO = 1
        WHERE i.ID_EMPRESA = ?
        AND i.ID_TEMPORADA = ?
        AND i.ESTADO_REGISTRO = 1
        AND i.ID_ICARGA = ?
        LIMIT 1
    ");
    $stmt->execute([$empresa, $temporada, $empresa, $temporada, $idIcarga]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerReferencias($db, $empresa, $temporada) {
    $stmt = $db->prepare("
        SELECT
            i.ID_ICARGA,
            i.NUMERO_ICARGA,
            i.NREFERENCIA_ICARGA,
            COALESCE(NULLIF(i.NCONTENEDOR_ICARGA, ''), dx.CONTENEDOR_DESPACHO, '') AS NCONTENEDOR_ICARGA,
            IFNULL(merc.NOMBRE_MERCADO, '') AS NOMBRE_MERCADO,
            IFNULL(cons.NOMBRE_CONSIGNATARIO, '') AS NOMBRE_CONSIGNATARIO,
            IFNULL(inv.TOTAL_INVOICE_USD, 0) AS TOTAL_INVOICE_USD,
            IFNULL(liq.TOTAL_LIQUIDACION_USD, 0) AS TOTAL_LIQUIDACION_USD,
            IFNULL(ivv.ESTADO_IVV, 'PENDIENTE') AS ESTADO_IVV
        FROM fruta_icarga i
        LEFT JOIN fruta_mercado merc ON merc.ID_MERCADO = i.ID_MERCADO
        LEFT JOIN fruta_consignatario cons ON cons.ID_CONSIGNATARIO = i.ID_CONSIGNATARIO
        LEFT JOIN (
            SELECT d.ID_ICARGA, MIN(NULLIF(NULLIF(d.NUMERO_CONTENEDOR_DESPACHOEX, ''), '0')) AS CONTENEDOR_DESPACHO
            FROM fruta_despachoex d
            WHERE d.ESTADO_REGISTRO = 1
            GROUP BY d.ID_ICARGA
        ) dx ON dx.ID_ICARGA = i.ID_ICARGA
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
        WHERE i.ID_EMPRESA = ?
        AND i.ID_TEMPORADA = ?
        AND i.ESTADO_REGISTRO = 1
        ORDER BY i.ID_ICARGA DESC
    ");
    $stmt->execute([$empresa, $temporada, $empresa, $temporada]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerDetalleLiquidacion($db, $empresa, $temporada, $idIcarga) {
    $stmt = $db->prepare("
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
    $stmt->execute([$empresa, $temporada, $idIcarga]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function guardarIvv($db, $resumen, $empresa, $temporada, $usuario, $estado, $observacion, $tolerancia) {
    $totalInvoice = (float)($resumen['TOTAL_INVOICE_USD'] ?? 0);
    $totalLiquidacion = (float)($resumen['TOTAL_LIQUIDACION_USD'] ?? 0);
    $diferencia = $totalLiquidacion - $totalInvoice;
    $documento = documentoSugerido($diferencia, $tolerancia);
    $idIvv = (int)($resumen['ID_IVV'] ?? 0);
    $fecha = $estado === 'EMITIDO' ? date('Y-m-d') : ($resumen['FECHA_IVV'] ?? null);

    if ($idIvv > 0) {
        $stmt = $db->prepare("
            UPDATE exportadora_ivv
            SET ID_INVOICE = ?,
                ID_VALOR = ?,
                FECHA_IVV = ?,
                ESTADO_IVV = ?,
                TOTAL_INVOICE_USD = ?,
                TOTAL_LIQUIDACION_USD = ?,
                DIFERENCIA_USD = ?,
                DOCUMENTO_SUGERIDO = ?,
                OBSERVACION_IVV = ?,
                ID_USUARIOM = ?,
                MODIFICACION = SYSDATE()
            WHERE ID_IVV = ?
        ");
        $stmt->execute([
            $resumen['ID_INVOICE'] ?: null,
            $resumen['ID_VALOR'] ?: null,
            $fecha,
            $estado,
            $totalInvoice,
            $totalLiquidacion,
            $diferencia,
            $documento,
            $observacion,
            $usuario,
            $idIvv
        ]);
        return $idIvv;
    }

    $stmtNumero = $db->prepare("SELECT COUNT(*) + 1 FROM exportadora_ivv WHERE ID_EMPRESA = ? AND ID_TEMPORADA = ?");
    $stmtNumero->execute([$empresa, $temporada]);
    $numero = (int)$stmtNumero->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO exportadora_ivv
            (ID_ICARGA, ID_INVOICE, ID_VALOR, NUMERO_IVV, FECHA_IVV, ESTADO_IVV, TOTAL_INVOICE_USD, TOTAL_LIQUIDACION_USD,
             DIFERENCIA_USD, DOCUMENTO_SUGERIDO, OBSERVACION_IVV, ID_EMPRESA, ID_TEMPORADA, ID_USUARIOI, ID_USUARIOM,
             INGRESO, MODIFICACION, ESTADO_REGISTRO)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, SYSDATE(), SYSDATE(), 1)
    ");
    $stmt->execute([
        $resumen['ID_ICARGA'],
        $resumen['ID_INVOICE'] ?: null,
        $resumen['ID_VALOR'] ?: null,
        $numero,
        $fecha,
        $estado,
        $totalInvoice,
        $totalLiquidacion,
        $diferencia,
        $documento,
        $observacion,
        $empresa,
        $temporada,
        $usuario,
        $usuario
    ]);
    return (int)$db->lastInsertId();
}

if ($IDICARGA > 0 && isset($_POST['ACCION_IVV'])) {
    $RESUMEN_POST = obtenerResumenReferencia($db, $EMPRESAS, $TEMPORADAS, $IDICARGA);
    if (!$RESUMEN_POST) {
        $error = "No se encontro la referencia seleccionada.";
    } elseif ((float)($RESUMEN_POST['TOTAL_INVOICE_USD'] ?? 0) <= 0) {
        $error = "La referencia no tiene invoice confirmada con valor.";
    } elseif ((float)($RESUMEN_POST['TOTAL_LIQUIDACION_USD'] ?? 0) <= 0) {
        $error = "La referencia no tiene liquidacion final con valor.";
    } else {
        $accion = $_POST['ACCION_IVV'];
        $estado = 'BORRADOR';
        if ($accion === 'EMITIR') {
            $estado = 'EMITIDO';
        } elseif ($accion === 'RECTIFICAR') {
            $estado = 'RECTIFICADO';
        } elseif ($accion === 'ANULAR') {
            $estado = 'ANULADO';
        }
        guardarIvv($db, $RESUMEN_POST, $EMPRESAS, $TEMPORADAS, $IDUSUARIOS, $estado, $_POST['OBSERVACION_IVV'] ?? '', $TOLERANCIA);
        $mensaje = "IVV guardado correctamente.";
    }
}

$REFERENCIAS = obtenerReferencias($db, $EMPRESAS, $TEMPORADAS);
$RESUMEN = $IDICARGA > 0 ? obtenerResumenReferencia($db, $EMPRESAS, $TEMPORADAS, $IDICARGA) : null;
$DETALLE = $IDICARGA > 0 ? obtenerDetalleLiquidacion($db, $EMPRESAS, $TEMPORADAS, $IDICARGA) : [];
$TOTAL_INVOICE = (float)($RESUMEN['TOTAL_INVOICE_USD'] ?? 0);
$TOTAL_LIQUIDACION = (float)($RESUMEN['TOTAL_LIQUIDACION_USD'] ?? 0);
$DIFERENCIA = $TOTAL_LIQUIDACION - $TOTAL_INVOICE;
$DOCUMENTO = documentoSugerido($DIFERENCIA, $TOLERANCIA);
$TNOTA = $DOCUMENTO === 'NOTA_DEBITO' ? 1 : ($DOCUMENTO === 'NOTA_CREDITO' ? 2 : 0);
$OBS_NOTA = $TNOTA > 0 && $RESUMEN
    ? 'Generada desde IVV ' . ($RESUMEN['NREFERENCIA_ICARGA'] ?? '') . '. Diferencia neta vs invoice US$ ' . number_format(abs($DIFERENCIA), 2, '.', '')
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Registro IVV</title>
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
                        <h3 class="page-title">Registro IVV</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item">Exportacion</li>
                                    <li class="breadcrumb-item active">IVV</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>

            <section class="content">
                <?php if ($mensaje) { ?><div class="alert alert-success"><?php echo h($mensaje); ?></div><?php } ?>
                <?php if ($error) { ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php } ?>

                <div class="box">
                    <div class="box-header with-border bg-primary">
                        <h4 class="box-title">Seleccion de referencia</h4>
                    </div>
                    <form method="get">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-9">
                                    <label>Referencia</label>
                                    <select class="form-control select2" name="ICARGA" style="width:100%">
                                        <option></option>
                                        <?php foreach ($REFERENCIAS as $ref) {
                                            $difRef = (float)$ref['TOTAL_LIQUIDACION_USD'] - (float)$ref['TOTAL_INVOICE_USD'];
                                            $docRef = nombreDocumento(documentoSugerido($difRef, $TOLERANCIA));
                                        ?>
                                            <option value="<?php echo (int)$ref['ID_ICARGA']; ?>" <?php echo $IDICARGA === (int)$ref['ID_ICARGA'] ? 'selected' : ''; ?>>
                                                <?php echo h($ref['NUMERO_ICARGA'] . ' - ' . $ref['NREFERENCIA_ICARGA'] . ' - ' . $ref['NOMBRE_MERCADO'] . ' - ' . $ref['NOMBRE_CONSIGNATARIO'] . ' - ' . $ref['ESTADO_IVV'] . ' - ' . $docRef); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-3 pt-30">
                                    <button class="btn btn-primary" type="submit">Cargar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($RESUMEN) { ?>
                    <form method="post">
                        <div class="box">
                            <div class="box-header with-border bg-info">
                                <h4 class="box-title">IVV referencia <?php echo h($RESUMEN['NREFERENCIA_ICARGA']); ?></h4>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label>N IVV</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NUMERO_IVV'] ?? 'Pendiente'); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Estado IVV</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['ESTADO_IVV'] ?? 'PENDIENTE'); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Instructivo</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NUMERO_ICARGA']); ?>" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Referencia</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NREFERENCIA_ICARGA']); ?>" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Contenedor</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NCONTENEDOR_ICARGA']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row mt-10">
                                    <div class="col-md-3">
                                        <label>Mercado</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NOMBRE_MERCADO']); ?>" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Cliente</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NOMBRE_CONSIGNATARIO']); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>DUS</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['DUS_ICARGA']); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>BL/AWB/CRT</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['BOLAWBCRT_ICARGA']); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Booking</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['BOOKING_ICARGA']); ?>" readonly>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-2">
                                        <label>Invoice</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NUMERO_INVOICE'] ?? 'Sin invoice'); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Total invoice US$</label>
                                        <input class="form-control text-right" value="<?php echo numero($TOTAL_INVOICE); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Liquidacion</label>
                                        <input class="form-control" value="<?php echo h($RESUMEN['NUMERO_VALOR'] ?? 'Sin liquidacion'); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Total liquidacion US$</label>
                                        <input class="form-control text-right" value="<?php echo numero($TOTAL_LIQUIDACION); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Diferencia US$</label>
                                        <input class="form-control text-right" value="<?php echo numero($DIFERENCIA); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Documento</label>
                                        <input class="form-control" value="<?php echo h(nombreDocumento($DOCUMENTO)); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row mt-10">
                                    <div class="col-md-12">
                                        <label>Observacion IVV</label>
                                        <input class="form-control" name="OBSERVACION_IVV" value="<?php echo h($RESUMEN['OBSERVACION_IVV'] ?? ''); ?>">
                                    </div>
                                </div>
                                <hr>
                                <div class="table-responsive">
                                    <table id="listar" class="table table-bordered table-hover" style="width:100%">
                                        <thead>
                                            <tr class="text-center">
                                                <th>Productor</th>
                                                <th>Estandar</th>
                                                <th>Variedad</th>
                                                <th>Calibre</th>
                                                <th>Cajas</th>
                                                <th>Kg Neto</th>
                                                <th>Venta Bruta US$</th>
                                                <th>Comision</th>
                                                <th>Gastos</th>
                                                <th>Venta Neta US$</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totCajas = 0;
                                            $totKg = 0;
                                            $totBruta = 0;
                                            $totComision = 0;
                                            $totGastos = 0;
                                            $totNeta = 0;
                                            foreach ($DETALLE as $d) {
                                                $totCajas += (float)$d['CAJAS'];
                                                $totKg += (float)$d['KILOS_NETO'];
                                                $totBruta += (float)$d['VENTA_USD_BRUTO'];
                                                $totComision += (float)$d['COMISION'];
                                                $totGastos += (float)$d['GASTOS'];
                                                $totNeta += (float)$d['VENTA_USD_NETO'];
                                            ?>
                                                <tr>
                                                    <td><?php echo h($d['NOMBRE_PRODUCTOR']); ?></td>
                                                    <td><?php echo h($d['NOMBRE_ESTANDAR']); ?></td>
                                                    <td><?php echo h($d['NOMBRE_VESPECIES']); ?></td>
                                                    <td><?php echo h($d['NOMBRE_TCALIBRE']); ?></td>
                                                    <td class="text-right"><?php echo numero($d['CAJAS'], 0); ?></td>
                                                    <td class="text-right"><?php echo numero($d['KILOS_NETO']); ?></td>
                                                    <td class="text-right"><?php echo numero($d['VENTA_USD_BRUTO']); ?></td>
                                                    <td class="text-right"><?php echo numero($d['COMISION']); ?></td>
                                                    <td class="text-right"><?php echo numero($d['GASTOS']); ?></td>
                                                    <td class="text-right"><?php echo numero($d['VENTA_USD_NETO']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-right">Total</th>
                                                <th class="text-right"><?php echo numero($totCajas, 0); ?></th>
                                                <th class="text-right"><?php echo numero($totKg); ?></th>
                                                <th class="text-right"><?php echo numero($totBruta); ?></th>
                                                <th class="text-right"><?php echo numero($totComision); ?></th>
                                                <th class="text-right"><?php echo numero($totGastos); ?></th>
                                                <th class="text-right"><?php echo numero($totNeta); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary" name="ACCION_IVV" value="BORRADOR">
                                    <i class="ti-save-alt"></i> Guardar borrador
                                </button>
                                <button type="submit" class="btn btn-success" name="ACCION_IVV" value="EMITIR">
                                    <i class="ti-check"></i> Marcar emitido
                                </button>
                                <?php if ((int)($RESUMEN['ID_IVV'] ?? 0) > 0) { ?>
                                    <button type="submit" class="btn btn-warning" name="ACCION_IVV" value="RECTIFICAR">
                                        <i class="ti-pencil-alt"></i> Rectificar
                                    </button>
                                    <button type="submit" class="btn btn-danger" name="ACCION_IVV" value="ANULAR" onclick="return confirm('Desea anular este IVV?');">
                                        <i class="ti-trash"></i> Anular
                                    </button>
                                <?php } ?>
                                <a class="btn btn-info" href="exportarIvvExp.php?ICARGA=<?php echo (int)$IDICARGA; ?>">
                                    <i class="ti-download"></i> Exportar Excel
                                </a>
                                <?php if ($TNOTA > 0) { ?>
                                    <a class="btn btn-secondary" href="registroNotadc.php?ICARGAD=<?php echo (int)$IDICARGA; ?>&TNOTA=<?php echo (int)$TNOTA; ?>&OBSERVACIONINOTA=<?php echo urlencode($OBS_NOTA); ?>">
                                        <i class="ti-pencil-alt"></i> Ingresar nota D/C
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </form>
                <?php } ?>
            </section>
        </div>
    </div>
    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>
</body>
</html>
