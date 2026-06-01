<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();
$IDICARGA = isset($_REQUEST['ICARGA']) ? (int)$_REQUEST['ICARGA'] : 0;
$AGRUPAR_ESTANDAR = isset($_REQUEST['AGRUPAR_ESTANDAR']) || !isset($_REQUEST['ICARGA']);
$AGRUPAR_VARIEDAD = isset($_REQUEST['AGRUPAR_VARIEDAD']) || !isset($_REQUEST['ICARGA']);
$AGRUPAR_CALIBRE = isset($_REQUEST['AGRUPAR_CALIBRE']) || !isset($_REQUEST['ICARGA']);
if (!$AGRUPAR_ESTANDAR && !$AGRUPAR_VARIEDAD && !$AGRUPAR_CALIBRE) {
    $AGRUPAR_ESTANDAR = true;
}
$mensaje = "";

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function numero($valor, $decimales = 2) {
    return number_format((float)$valor, $decimales, ',', '.');
}

function monedaCodigo($nombre) {
    $n = strtoupper(trim((string)$nombre));
    if (strpos($n, 'EURO') !== false) {
        return 'EUR';
    }
    return 'USD';
}

function notaPreferencialEuropa($db, $idIcarga) {
    $nota = "The exporter of the products covered by this document (77.223.122-9) declares that, except where otherwise clearly indicated, these products are of CHILE preferential origin Product Description: Fresh Blueberries 0810.40";
    $stmt = $db->prepare("
        SELECT
            UPPER(IFNULL(merc.NOMBRE_MERCADO, '')) AS MERCADO,
            UPPER(IFNULL(pais.NOMBRE_PAIS, '')) AS PAIS
        FROM fruta_icarga i
        LEFT JOIN fruta_mercado merc ON merc.ID_MERCADO = i.ID_MERCADO
        LEFT JOIN ubicacion_pais pais ON pais.ID_PAIS = i.ID_PAIS
        WHERE i.ID_ICARGA = ?
        LIMIT 1
    ");
    $stmt->execute([$idIcarga]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return "";
    }

    $mercado = $row['MERCADO'];
    $pais = $row['PAIS'];
    $esUk = strpos($pais, 'REINO UNIDO') !== false
        || strpos($pais, 'INGLATERRA') !== false
        || strpos($pais, 'UNITED KINGDOM') !== false
        || preg_match('/\bUK\b/', $pais);
    $paisesEuropa = [
        'ALEMANIA', 'BELGICA', 'DINAMARCA', 'ESPANA', 'ESPA', 'FRANCIA', 'HOLANDA',
        'IRLANDA', 'ITALIA', 'NORUEGA', 'PAISES BAJOS', 'POLONIA', 'PORTUGAL',
        'SUECIA', 'SUIZA', 'UNION EUROPEA'
    ];
    $esEuropa = strpos($mercado, 'EURO') !== false || strpos($mercado, 'UNION EUROPEA') !== false;
    foreach ($paisesEuropa as $paisEuropa) {
        if (strpos($pais, $paisEuropa) !== false) {
            $esEuropa = true;
            break;
        }
    }

    return ($esEuropa && !$esUk) ? $nota : "";
}

function esNotaPreferencialAntigua($observacion) {
    return trim((string)$observacion) === "The exporter of the products covered by this document (77.223.122-9) declares that, where otherwise clearly indicated, these products are of CHILE preferential origin Product Description: Fresh Blueberries 0810.40";
}

function obtenerInvoice($db, $idIcarga) {
    $stmt = $db->prepare("
        SELECT *
        FROM exportadora_invoice
        WHERE ID_ICARGA = ?
        AND ESTADO_REGISTRO = 1
        ORDER BY ID_INVOICE DESC
        LIMIT 1
    ");
    $stmt->execute([$idIcarga]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function crearInvoiceDesdeDespacho($db, $idIcarga, $empresa, $temporada, $usuario, $forzarDetalle = false) {
    $invoice = obtenerInvoice($db, $idIcarga);
    if ($invoice && !$forzarDetalle) {
        return (int)$invoice['ID_INVOICE'];
    }

    $stmtMoneda = $db->prepare("
        SELECT IFNULL(tm.NOMBRE_TMONEDA, 'Dolar') AS MONEDA
        FROM fruta_dicarga dic
        LEFT JOIN fruta_tmoneda tm ON tm.ID_TMONEDA = dic.ID_TMONEDA
        WHERE dic.ID_ICARGA = ?
        AND dic.ESTADO_REGISTRO = 1
        ORDER BY dic.ID_DICARGA ASC
        LIMIT 1
    ");
    $stmtMoneda->execute([$idIcarga]);
    $moneda = monedaCodigo($stmtMoneda->fetchColumn());

    if ($invoice) {
        $idInvoice = (int)$invoice['ID_INVOICE'];
        $db->prepare("DELETE FROM exportadora_invoice_detalle WHERE ID_INVOICE = ?")->execute([$idInvoice]);
    } else {
        $stmtNumero = $db->prepare("SELECT COUNT(*) + 1 FROM exportadora_invoice WHERE ID_EMPRESA = ? AND ID_TEMPORADA = ?");
        $stmtNumero->execute([$empresa, $temporada]);
        $numero = (int)$stmtNumero->fetchColumn();

        $observacionInicial = notaPreferencialEuropa($db, $idIcarga);
        $stmtCrear = $db->prepare("
            INSERT INTO exportadora_invoice
                (ID_ICARGA, NUMERO_INVOICE, FECHA_INVOICE, MONEDA_INVOICE, TIPO_CAMBIO_USD, ESTADO_INVOICE, OBSERVACION_INVOICE, ID_EMPRESA, ID_TEMPORADA, ID_USUARIOI, ID_USUARIOM, INGRESO, MODIFICACION, ESTADO_REGISTRO)
            VALUES
                (?, ?, CURDATE(), ?, 1, 'BORRADOR', ?, ?, ?, ?, ?, SYSDATE(), SYSDATE(), 1)
        ");
        $stmtCrear->execute([$idIcarga, $numero, $moneda, $observacionInicial, $empresa, $temporada, $usuario, $usuario]);
        $idInvoice = (int)$db->lastInsertId();
    }

    $stmtDetalle = $db->prepare("
        INSERT INTO exportadora_invoice_detalle
            (ID_INVOICE, ID_DICARGA, ID_ESTANDAR, ID_VESPECIES, ID_TCALIBRE, CANTIDAD_ENVASE, KILOS_NETO, KILOS_BRUTO, PRECIO_CAJA, TOTAL_LINEA, ORIGEN, ID_USUARIOI, ID_USUARIOM, INGRESO, MODIFICACION, ESTADO_REGISTRO)
        SELECT
            ?,
            IFNULL(MIN(dic.ID_DICARGA), 0),
            exi.ID_ESTANDAR,
            IFNULL(exi.ID_VESPECIES, 0),
            exi.ID_TCALIBRE,
            SUM(IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)),
            SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0)),
            SUM(IFNULL(exi.KILOS_BRUTO_EXIEXPORTACION, 0)),
            COALESCE(NULLIF(MAX(dic.PRECIO_US_DICARGA), 0), MAX(pres.FOB_CAJA), 0),
            SUM(IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)) * COALESCE(NULLIF(MAX(dic.PRECIO_US_DICARGA), 0), MAX(pres.FOB_CAJA), 0),
            'DESPACHO',
            ?,
            ?,
            SYSDATE(),
            SYSDATE(),
            1
        FROM fruta_icarga i
        INNER JOIN fruta_exiexportacion exi ON exi.ESTADO_REGISTRO = 1
            AND exi.ID_EMPRESA = i.ID_EMPRESA
            AND exi.ID_TEMPORADA = i.ID_TEMPORADA
        LEFT JOIN fruta_despachoex dex ON dex.ID_DESPACHOEX = exi.ID_DESPACHOEX
            AND dex.ESTADO_REGISTRO = 1
        LEFT JOIN fruta_dicarga dic ON dic.ID_ICARGA = i.ID_ICARGA
            AND dic.ID_ESTANDAR = exi.ID_ESTANDAR
            AND dic.ID_TCALIBRE = exi.ID_TCALIBRE
            AND (dic.ID_VESPECIES IS NULL OR dic.ID_VESPECIES = exi.ID_VESPECIES)
            AND dic.ESTADO_REGISTRO = 1
        LEFT JOIN exportadora_presupuesto_fob pres ON pres.ID_EMPRESA = i.ID_EMPRESA
            AND pres.ID_TEMPORADA = i.ID_TEMPORADA
            AND pres.ID_MERCADO = i.ID_MERCADO
            AND pres.ID_CONSIGNATARIO = i.ID_CONSIGNATARIO
            AND pres.ID_ESTANDAR = exi.ID_ESTANDAR
            AND pres.ID_TCALIBRE = exi.ID_TCALIBRE
            AND pres.ESTADO_REGISTRO = 1
        WHERE i.ID_ICARGA = ?
        AND i.ESTADO_REGISTRO = 1
        AND (
            exi.ID_ICARGA = i.ID_ICARGA
            OR dex.ID_ICARGA = i.ID_ICARGA
            OR exi.REFERENCIA = CONVERT(i.ID_ICARGA USING utf8mb4) COLLATE utf8mb4_spanish_ci
            OR exi.REFERENCIA = CONVERT(i.NUMERO_ICARGA USING utf8mb4) COLLATE utf8mb4_spanish_ci
        )
        GROUP BY exi.ID_ESTANDAR, exi.ID_VESPECIES, exi.ID_TCALIBRE
    ");
    $stmtDetalle->execute([$idInvoice, $usuario, $usuario, $idIcarga]);

    $stmtCantidad = $db->prepare("
        SELECT COUNT(*)
        FROM exportadora_invoice_detalle
        WHERE ID_INVOICE = ?
        AND ESTADO_REGISTRO = 1
    ");
    $stmtCantidad->execute([$idInvoice]);
    if ((int)$stmtCantidad->fetchColumn() === 0) {
        $stmtDetalleInstructivo = $db->prepare("
            INSERT INTO exportadora_invoice_detalle
                (ID_INVOICE, ID_DICARGA, ID_ESTANDAR, ID_VESPECIES, ID_TCALIBRE, CANTIDAD_ENVASE, KILOS_NETO, KILOS_BRUTO, PRECIO_CAJA, TOTAL_LINEA, ORIGEN, ID_USUARIOI, ID_USUARIOM, INGRESO, MODIFICACION, ESTADO_REGISTRO)
            SELECT
                ?,
                dic.ID_DICARGA,
                dic.ID_ESTANDAR,
                IFNULL(dic.ID_VESPECIES, 0),
                dic.ID_TCALIBRE,
                IFNULL(dic.CANTIDAD_ENVASE_DICARGA, 0),
                IFNULL(dic.KILOS_NETO_DICARGA, 0),
                IFNULL(dic.KILOS_BRUTO_DICARGA, 0),
                COALESCE(NULLIF(dic.PRECIO_US_DICARGA, 0), pres.FOB_CAJA, 0),
                IFNULL(dic.CANTIDAD_ENVASE_DICARGA, 0) * COALESCE(NULLIF(dic.PRECIO_US_DICARGA, 0), pres.FOB_CAJA, 0),
                'INSTRUCTIVO',
                ?,
                ?,
                SYSDATE(),
                SYSDATE(),
                1
            FROM fruta_dicarga dic
            INNER JOIN fruta_icarga i ON i.ID_ICARGA = dic.ID_ICARGA
            LEFT JOIN exportadora_presupuesto_fob pres ON pres.ID_EMPRESA = i.ID_EMPRESA
                AND pres.ID_TEMPORADA = i.ID_TEMPORADA
                AND pres.ID_MERCADO = i.ID_MERCADO
                AND pres.ID_CONSIGNATARIO = i.ID_CONSIGNATARIO
                AND pres.ID_ESTANDAR = dic.ID_ESTANDAR
                AND pres.ID_TCALIBRE = dic.ID_TCALIBRE
                AND pres.ESTADO_REGISTRO = 1
            WHERE dic.ID_ICARGA = ?
            AND dic.ESTADO_REGISTRO = 1
        ");
        $stmtDetalleInstructivo->execute([$idInvoice, $usuario, $usuario, $idIcarga]);
    }

    return $idInvoice;
}

$stmtIcarga = $db->prepare("
    SELECT
        i.ID_ICARGA,
        i.NUMERO_ICARGA,
        i.NREFERENCIA_ICARGA,
        COALESCE(NULLIF(i.NCONTENEDOR_ICARGA, ''), dx.CONTENEDOR_DESPACHO, '') AS NCONTENEDOR_ICARGA,
        IFNULL(inv.ESTADO_INVOICE, 'SIN INVOICE') AS ESTADO_INVOICE,
        IFNULL(inv.TOTAL_INVOICE, 0) AS TOTAL_INVOICE
    FROM fruta_icarga i
    LEFT JOIN (
        SELECT d.ID_ICARGA, MIN(NULLIF(NULLIF(d.NUMERO_CONTENEDOR_DESPACHOEX, ''), '0')) AS CONTENEDOR_DESPACHO
        FROM fruta_despachoex d
        WHERE d.ESTADO_REGISTRO = 1
        GROUP BY d.ID_ICARGA
    ) dx ON dx.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN (
        SELECT inv.ID_ICARGA, MAX(inv.ESTADO_INVOICE) AS ESTADO_INVOICE, SUM(det.TOTAL_LINEA) AS TOTAL_INVOICE
        FROM exportadora_invoice inv
        LEFT JOIN exportadora_invoice_detalle det ON det.ID_INVOICE = inv.ID_INVOICE
            AND det.ESTADO_REGISTRO = 1
        WHERE inv.ESTADO_REGISTRO = 1
        GROUP BY inv.ID_ICARGA
    ) inv ON inv.ID_ICARGA = i.ID_ICARGA
    WHERE i.ESTADO_REGISTRO = 1
    AND i.ID_EMPRESA = ?
    AND i.ID_TEMPORADA = ?
    ORDER BY i.ID_ICARGA DESC
");
$stmtIcarga->execute([$EMPRESAS, $TEMPORADAS]);
$INSTRUCTIVOS = $stmtIcarga->fetchAll(PDO::FETCH_ASSOC);

if ($IDICARGA > 0 && isset($_POST['GENERAR'])) {
    crearInvoiceDesdeDespacho($db, $IDICARGA, $EMPRESAS, $TEMPORADAS, $IDUSUARIOS);
    $mensaje = "Invoice generada desde el despacho.";
}

if ($IDICARGA > 0 && isset($_POST['SINCRONIZAR'])) {
    $invoiceActual = obtenerInvoice($db, $IDICARGA);
    if ($invoiceActual && $invoiceActual['ESTADO_INVOICE'] === 'CONFIRMADA') {
        $mensaje = "La invoice ya esta confirmada y no se puede sincronizar.";
    } else {
    crearInvoiceDesdeDespacho($db, $IDICARGA, $EMPRESAS, $TEMPORADAS, $IDUSUARIOS, true);
    $mensaje = "Detalle de invoice sincronizado desde el despacho.";
    }
}

if ($IDICARGA > 0 && isset($_POST['GUARDAR'])) {
    $idInvoice = crearInvoiceDesdeDespacho($db, $IDICARGA, $EMPRESAS, $TEMPORADAS, $IDUSUARIOS);
    $invoiceActual = obtenerInvoice($db, $IDICARGA);
    if ($invoiceActual && $invoiceActual['ESTADO_INVOICE'] === 'CONFIRMADA') {
        $mensaje = "La invoice ya esta confirmada y no se puede editar.";
    } else {
    $estado = !empty($_POST['CONFIRMAR']) ? 'CONFIRMADA' : 'BORRADOR';
    $moneda = in_array($_POST['MONEDA_INVOICE'] ?? '', ['USD', 'EUR']) ? $_POST['MONEDA_INVOICE'] : 'USD';
    $tipoCambio = max(0.000001, (float)str_replace(',', '.', $_POST['TIPO_CAMBIO_USD'] ?? '1'));
    $observacion = $_POST['OBSERVACION_INVOICE'] ?? '';

    $db->prepare("
        UPDATE exportadora_invoice
        SET MONEDA_INVOICE = ?, TIPO_CAMBIO_USD = ?, ESTADO_INVOICE = ?, OBSERVACION_INVOICE = ?, ID_USUARIOM = ?, MODIFICACION = SYSDATE()
        WHERE ID_INVOICE = ?
    ")->execute([$moneda, $tipoCambio, $estado, $observacion, $IDUSUARIOS, $idInvoice]);

    $stmtUpd = $db->prepare("
        UPDATE exportadora_invoice_detalle
        SET CANTIDAD_ENVASE = ?,
            KILOS_NETO = ?,
            KILOS_BRUTO = ?,
            PRECIO_CAJA = ?,
            TOTAL_LINEA = ?,
            ORIGEN = 'MANUAL',
            OBSERVACION = ?,
            ID_USUARIOM = ?,
            MODIFICACION = SYSDATE()
        WHERE ID_DINVOICE = ?
        AND ID_INVOICE = ?
    ");
    foreach ($_POST['linea'] ?? [] as $idDinvoice => $linea) {
        $envases = (float)str_replace(',', '.', $linea['envases'] ?? 0);
        $neto = (float)str_replace(',', '.', $linea['neto'] ?? 0);
        $bruto = (float)str_replace(',', '.', $linea['bruto'] ?? 0);
        $precio = (float)str_replace(',', '.', $linea['precio'] ?? 0);
        $total = $envases * $precio;
        $stmtUpd->execute([$envases, $neto, $bruto, $precio, $total, $linea['obs'] ?? '', $IDUSUARIOS, (int)$idDinvoice, $idInvoice]);
    }
    foreach ($_POST['lineagrupo'] ?? [] as $linea) {
        $precio = (float)str_replace(',', '.', $linea['precio'] ?? 0);
        $obs = $linea['obs'] ?? '';
        $sql = "
            UPDATE exportadora_invoice_detalle
            SET PRECIO_CAJA = ?,
                TOTAL_LINEA = CANTIDAD_ENVASE * ?,
                ORIGEN = 'MANUAL',
                OBSERVACION = ?,
                ID_USUARIOM = ?,
                MODIFICACION = SYSDATE()
            WHERE ID_INVOICE = ?
            AND ESTADO_REGISTRO = 1
        ";
        $params = [$precio, $precio, $obs, $IDUSUARIOS, $idInvoice];
        if ((int)($linea['estandar'] ?? 0) > 0) {
            $sql .= " AND ID_ESTANDAR = ?";
            $params[] = (int)$linea['estandar'];
        }
        if ((int)($linea['vespecies'] ?? 0) > 0) {
            $sql .= " AND ID_VESPECIES = ?";
            $params[] = (int)$linea['vespecies'];
        }
        if ((int)($linea['calibre'] ?? 0) > 0) {
            $sql .= " AND ID_TCALIBRE = ?";
            $params[] = (int)$linea['calibre'];
        }
        $db->prepare($sql)->execute($params);
    }
    $mensaje = $estado === 'CONFIRMADA' ? "Invoice confirmada correctamente." : "Invoice guardada como borrador.";
    }
}

$INVOICE = $IDICARGA > 0 ? obtenerInvoice($db, $IDICARGA) : null;
if ($INVOICE && (trim((string)$INVOICE['OBSERVACION_INVOICE']) === '' || esNotaPreferencialAntigua($INVOICE['OBSERVACION_INVOICE']))) {
    $notaDefault = notaPreferencialEuropa($db, $IDICARGA);
    if ($notaDefault !== '') {
        $db->prepare("
            UPDATE exportadora_invoice
            SET OBSERVACION_INVOICE = ?,
                ID_USUARIOM = ?,
                MODIFICACION = SYSDATE()
            WHERE ID_INVOICE = ?
        ")->execute([$notaDefault, $IDUSUARIOS, (int)$INVOICE['ID_INVOICE']]);
        $INVOICE = obtenerInvoice($db, $IDICARGA);
    }
}
$DETALLE = [];
$CABECERA = null;
$INVOICE_CONFIRMADA = $INVOICE && $INVOICE['ESTADO_INVOICE'] === 'CONFIRMADA';
if ($IDICARGA > 0) {
    $stmtCab = $db->prepare("
        SELECT i.*, IFNULL(tf.NOMBRE_TFLETE, '') AS TIPO_FLETE
        FROM fruta_icarga i
        LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
        WHERE i.ID_ICARGA = ?
    ");
    $stmtCab->execute([$IDICARGA]);
    $CABECERA = $stmtCab->fetch(PDO::FETCH_ASSOC);
}
if ($INVOICE) {
    $selectEstandar = $AGRUPAR_ESTANDAR ? "det.ID_ESTANDAR" : "0 AS ID_ESTANDAR";
    $selectVespecies = $AGRUPAR_VARIEDAD ? "det.ID_VESPECIES" : "0 AS ID_VESPECIES";
    $selectCalibre = $AGRUPAR_CALIBRE ? "det.ID_TCALIBRE" : "0 AS ID_TCALIBRE";
    $nombreEstandar = $AGRUPAR_ESTANDAR ? "IFNULL(est.NOMBRE_ESTANDAR, 'Sin estandar') AS NOMBRE_ESTANDAR" : "'Todos' AS NOMBRE_ESTANDAR";
    $nombreVespecies = $AGRUPAR_VARIEDAD ? "IFNULL(ves.NOMBRE_VESPECIES, 'Sin variedad') AS NOMBRE_VESPECIES" : "'Todas' AS NOMBRE_VESPECIES";
    $nombreCalibre = $AGRUPAR_CALIBRE ? "IFNULL(cal.NOMBRE_TCALIBRE, 'Sin calibre') AS NOMBRE_TCALIBRE" : "'Todos' AS NOMBRE_TCALIBRE";
    $groupByParts = [];
    $orderByParts = [];
    if ($AGRUPAR_ESTANDAR) {
        $groupByParts[] = "det.ID_ESTANDAR";
        $orderByParts[] = "est.NOMBRE_ESTANDAR";
    }
    if ($AGRUPAR_VARIEDAD) {
        $groupByParts[] = "det.ID_VESPECIES";
        $orderByParts[] = "ves.NOMBRE_VESPECIES";
    }
    if ($AGRUPAR_CALIBRE) {
        $groupByParts[] = "det.ID_TCALIBRE";
        $orderByParts[] = "cal.ORDEN";
        $orderByParts[] = "cal.NOMBRE_TCALIBRE";
    }
    $groupBy = implode(", ", $groupByParts);
    $orderBy = implode(", ", $orderByParts);
    $stmtDet = $db->prepare("
        SELECT
            $selectEstandar,
            $selectVespecies,
            $selectCalibre,
            $nombreEstandar,
            $nombreVespecies,
            $nombreCalibre,
            SUM(IFNULL(det.CANTIDAD_ENVASE, 0)) AS CANTIDAD_ENVASE,
            SUM(IFNULL(det.KILOS_NETO, 0)) AS KILOS_NETO,
            SUM(IFNULL(det.KILOS_BRUTO, 0)) AS KILOS_BRUTO,
            CASE
                WHEN SUM(IFNULL(det.CANTIDAD_ENVASE, 0)) > 0
                    THEN SUM(IFNULL(det.TOTAL_LINEA, 0)) / SUM(IFNULL(det.CANTIDAD_ENVASE, 0))
                ELSE MAX(IFNULL(det.PRECIO_CAJA, 0))
            END AS PRECIO_CAJA,
            SUM(IFNULL(det.TOTAL_LINEA, 0)) AS TOTAL_LINEA,
            GROUP_CONCAT(DISTINCT det.ORIGEN ORDER BY det.ORIGEN SEPARATOR ', ') AS ORIGEN,
            MAX(IFNULL(det.OBSERVACION, '')) AS OBSERVACION
        FROM exportadora_invoice_detalle det
        LEFT JOIN estandar_eexportacion est ON est.ID_ESTANDAR = det.ID_ESTANDAR
        LEFT JOIN fruta_vespecies ves ON ves.ID_VESPECIES = det.ID_VESPECIES
        LEFT JOIN fruta_tcalibre cal ON cal.ID_TCALIBRE = det.ID_TCALIBRE
        WHERE det.ID_INVOICE = ?
        AND det.ESTADO_REGISTRO = 1
        GROUP BY $groupBy
        ORDER BY $orderBy
    ");
    $stmtDet->execute([(int)$INVOICE['ID_INVOICE']]);
    $DETALLE = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Invoice Editable</title>
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
                        <h3 class="page-title">Invoice Editable</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item">Exportacion</li>
                                    <li class="breadcrumb-item active">Invoice Editable</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>
            <section class="content">
                <?php if ($mensaje) { ?><div class="alert alert-success"><?php echo h($mensaje); ?></div><?php } ?>
                <?php if ($INVOICE_CONFIRMADA) { ?><div class="alert alert-info">Invoice confirmada. La informacion queda bloqueada para edicion.</div><?php } ?>
                <div class="box">
                    <div class="box-header with-border bg-primary"><h4 class="box-title">Seleccion de instructivo</h4></div>
                    <form method="get">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-9">
                                    <label>Instructivo / Referencia</label>
                                    <select class="form-control select2" name="ICARGA" style="width:100%">
                                        <option></option>
                                        <?php foreach ($INSTRUCTIVOS as $i) { ?>
                                            <option value="<?php echo (int)$i['ID_ICARGA']; ?>" <?php echo $IDICARGA === (int)$i['ID_ICARGA'] ? 'selected' : ''; ?>>
                                                <?php echo h($i['NUMERO_ICARGA'] . ' - ' . $i['NREFERENCIA_ICARGA'] . ' - ' . $i['NCONTENEDOR_ICARGA'] . ' - ' . $i['ESTADO_INVOICE'] . ' - US$ ' . numero($i['TOTAL_INVOICE'])); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-3 pt-30">
                                    <button class="btn btn-primary" type="submit">Cargar</button>
                                </div>
                            </div>
                            <div class="row mt-10">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <input type="checkbox" id="AGRUPAR_ESTANDAR" name="AGRUPAR_ESTANDAR" value="1" <?php echo $AGRUPAR_ESTANDAR ? 'checked' : ''; ?>>
                                        <label for="AGRUPAR_ESTANDAR">Estandar</label>
                                    </div>
                                    <div class="checkbox">
                                        <input type="checkbox" id="AGRUPAR_VARIEDAD" name="AGRUPAR_VARIEDAD" value="1" <?php echo $AGRUPAR_VARIEDAD ? 'checked' : ''; ?>>
                                        <label for="AGRUPAR_VARIEDAD">Variedad</label>
                                    </div>
                                    <div class="checkbox">
                                        <input type="checkbox" id="AGRUPAR_CALIBRE" name="AGRUPAR_CALIBRE" value="1" <?php echo $AGRUPAR_CALIBRE ? 'checked' : ''; ?>>
                                        <label for="AGRUPAR_CALIBRE">Calibre</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($IDICARGA > 0 && !$INVOICE) { ?>
                    <div class="box">
                        <div class="box-body">
                            <form method="post">
                                <button class="btn btn-success" name="GENERAR" value="1">Generar invoice desde despacho</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>

                <?php if ($INVOICE) { ?>
                    <form method="post">
                        <?php if ($AGRUPAR_ESTANDAR) { ?><input type="hidden" name="AGRUPAR_ESTANDAR" value="1"><?php } ?>
                        <?php if ($AGRUPAR_VARIEDAD) { ?><input type="hidden" name="AGRUPAR_VARIEDAD" value="1"><?php } ?>
                        <?php if ($AGRUPAR_CALIBRE) { ?><input type="hidden" name="AGRUPAR_CALIBRE" value="1"><?php } ?>
                        <div class="box">
                            <div class="box-header with-border bg-info">
                                <h4 class="box-title">Invoice <?php echo h($INVOICE['NUMERO_INVOICE']); ?> - <?php echo h($INVOICE['ESTADO_INVOICE']); ?></h4>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Referencia</label>
                                        <input class="form-control" value="<?php echo h(($CABECERA['NUMERO_ICARGA'] ?? '') . ' - ' . ($CABECERA['NREFERENCIA_ICARGA'] ?? '')); ?>" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Tipo flete</label>
                                        <input class="form-control" value="<?php echo h($CABECERA['TIPO_FLETE'] ?? ''); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Moneda</label>
                                        <select class="form-control" name="MONEDA_INVOICE" <?php echo $INVOICE_CONFIRMADA ? 'disabled' : ''; ?>>
                                            <option value="USD" <?php echo $INVOICE['MONEDA_INVOICE'] === 'USD' ? 'selected' : ''; ?>>USD</option>
                                            <option value="EUR" <?php echo $INVOICE['MONEDA_INVOICE'] === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Tipo cambio USD</label>
                                        <input type="number" step="0.000001" class="form-control" name="TIPO_CAMBIO_USD" value="<?php echo h($INVOICE['TIPO_CAMBIO_USD']); ?>" <?php echo $INVOICE_CONFIRMADA ? 'readonly' : ''; ?>>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Fecha</label>
                                        <input class="form-control" value="<?php echo h($INVOICE['FECHA_INVOICE']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row mt-10">
                                    <div class="col-md-12">
                                        <label>Observacion</label>
                                        <input class="form-control" name="OBSERVACION_INVOICE" value="<?php echo h($INVOICE['OBSERVACION_INVOICE']); ?>" <?php echo $INVOICE_CONFIRMADA ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <hr>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="listar">
                                        <thead>
                                            <tr class="text-center">
                                                <?php if ($AGRUPAR_ESTANDAR) { ?><th>Estandar</th><?php } ?>
                                                <?php if ($AGRUPAR_VARIEDAD) { ?><th>Variedad</th><?php } ?>
                                                <?php if ($AGRUPAR_CALIBRE) { ?><th>Calibre</th><?php } ?>
                                                <th>Cajas</th>
                                                <th>Kg Neto</th>
                                                <th>Kg Bruto</th>
                                                <th>Precio Caja</th>
                                                <th>Total</th>
                                                <th>Origen</th>
                                                <th>Obs.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $totalInvoice = 0; $columnasGrupo = ($AGRUPAR_ESTANDAR ? 1 : 0) + ($AGRUPAR_VARIEDAD ? 1 : 0) + ($AGRUPAR_CALIBRE ? 1 : 0); foreach ($DETALLE as $idx => $d) { $totalInvoice += (float)$d['TOTAL_LINEA']; ?>
                                                <tr>
                                                    <?php if ($AGRUPAR_ESTANDAR) { ?><td><?php echo h($d['NOMBRE_ESTANDAR']); ?></td><?php } ?>
                                                    <?php if ($AGRUPAR_VARIEDAD) { ?><td><?php echo h($d['NOMBRE_VESPECIES']); ?></td><?php } ?>
                                                    <?php if ($AGRUPAR_CALIBRE) { ?><td><?php echo h($d['NOMBRE_TCALIBRE']); ?></td><?php } ?>
                                                    <td class="text-right js-envases-text"><?php echo numero($d['CANTIDAD_ENVASE'], 2); ?></td>
                                                    <td class="text-right"><?php echo numero($d['KILOS_NETO'], 2); ?></td>
                                                    <td class="text-right"><?php echo numero($d['KILOS_BRUTO'], 2); ?></td>
                                                    <td>
                                                        <input type="hidden" name="lineagrupo[<?php echo $idx; ?>][estandar]" value="<?php echo (int)$d['ID_ESTANDAR']; ?>">
                                                        <input type="hidden" name="lineagrupo[<?php echo $idx; ?>][vespecies]" value="<?php echo (int)$d['ID_VESPECIES']; ?>">
                                                        <input type="hidden" name="lineagrupo[<?php echo $idx; ?>][calibre]" value="<?php echo (int)$d['ID_TCALIBRE']; ?>">
                                                        <input type="hidden" class="js-envases" value="<?php echo h($d['CANTIDAD_ENVASE']); ?>">
                                                        <input type="number" step="0.0001" class="form-control js-precio" name="lineagrupo[<?php echo $idx; ?>][precio]" value="<?php echo h(number_format((float)$d['PRECIO_CAJA'], 4, '.', '')); ?>" <?php echo $INVOICE_CONFIRMADA ? 'readonly' : ''; ?>>
                                                    </td>
                                                    <td class="text-right js-total"><?php echo numero($d['TOTAL_LINEA'], 2); ?></td>
                                                    <td><?php echo h($d['ORIGEN']); ?></td>
                                                    <td><input class="form-control" name="lineagrupo[<?php echo $idx; ?>][obs]" value="<?php echo h($d['OBSERVACION']); ?>" <?php echo $INVOICE_CONFIRMADA ? 'readonly' : ''; ?>></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="<?php echo $columnasGrupo + 4; ?>" class="text-right">Total</th>
                                                <th class="text-right" id="totalInvoice"><?php echo numero($totalInvoice, 2); ?></th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="box-footer">
                                <?php if (!$INVOICE_CONFIRMADA) { ?>
                                <button class="btn btn-primary" name="GUARDAR" value="1">Guardar borrador</button>
                                <button class="btn btn-success" name="GUARDAR" value="1" onclick="document.getElementById('confirmarInvoice').value='1';">Confirmar invoice</button>
                                <button class="btn btn-warning" name="SINCRONIZAR" value="1" onclick="return confirm('Esto reemplazara el detalle actual por las cajas y kilos del despacho. ¿Continuar?');">Sincronizar desde despacho</button>
                                <?php } else { ?>
                                <a class="btn btn-warning" href="registroAPExportadora.php?MODULO=INVOICE&ID=<?php echo (int)$INVOICE['ID_INVOICE']; ?>&ICARGA=<?php echo (int)$IDICARGA; ?>&NUMERO=<?php echo urlencode('Invoice ' . $INVOICE['NUMERO_INVOICE']); ?>&RETORNO=registroInvoiceExp">
                                    <i class="fa fa-folder-open"></i> Solicitar reapertura
                                </a>
                                <?php } ?>
                                <input type="hidden" id="confirmarInvoice" name="CONFIRMAR" value="">
                                <a class="btn btn-info" target="_blank" href="../../assest/documento/informeIcargaInvoice.php?parametro=<?php echo $IDICARGA; ?>&&usuario=<?php echo $IDUSUARIOS; ?>">Ver PDF actual</a>
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
<script>
document.querySelectorAll('.js-envases, .js-precio').forEach(function (input) {
    input.addEventListener('input', function () {
        var totalGeneral = 0;
        document.querySelectorAll('#listar tbody tr').forEach(function (tr) {
            var envases = parseFloat((tr.querySelector('.js-envases') || {}).value || 0) || 0;
            var precio = parseFloat((tr.querySelector('.js-precio') || {}).value || 0) || 0;
            var total = envases * precio;
            totalGeneral += total;
            var td = tr.querySelector('.js-total');
            if (td) {
                td.textContent = total.toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        });
        var tg = document.getElementById('totalInvoice');
        if (tg) {
            tg.textContent = totalGeneral.toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
});
</script>
</body>
</html>
