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
    $seguroRaw = $_POST['SEGURO_INVOICE'] ?? '';
    $fleteRaw  = $_POST['FLETE_INVOICE']  ?? '';
    $seguro = $seguroRaw !== '' ? max(0, (float)str_replace(',', '.', $seguroRaw)) : null;
    $flete  = $fleteRaw  !== '' ? max(0, (float)str_replace(',', '.', $fleteRaw))  : null;

    $db->prepare("
        UPDATE exportadora_invoice
        SET MONEDA_INVOICE = ?, TIPO_CAMBIO_USD = ?, ESTADO_INVOICE = ?, OBSERVACION_INVOICE = ?,
            SEGURO_INVOICE = ?, FLETE_INVOICE = ?, ID_USUARIOM = ?, MODIFICACION = SYSDATE()
        WHERE ID_INVOICE = ?
    ")->execute([$moneda, $tipoCambio, $estado, $observacion, $seguro, $flete, $IDUSUARIOS, $idInvoice]);

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
        SELECT i.*, IFNULL(tf.NOMBRE_TFLETE, '') AS TIPO_FLETE,
               UPPER(IFNULL(cv.NOMBRE_CVENTA, '')) AS INCOTERM
        FROM fruta_icarga i
        LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
        LEFT JOIN fruta_cventa cv ON cv.ID_CVENTA = i.ID_CVENTA
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

// ── Grouping toggle URL builder ────────────────────────────────────────────
$grpOptsInv = ['AGRUPAR_ESTANDAR' => 'Estándar', 'AGRUPAR_VARIEDAD' => 'Variedad', 'AGRUPAR_CALIBRE' => 'Calibre'];
$grpActiveInv = [];
if ($AGRUPAR_ESTANDAR) $grpActiveInv[] = 'AGRUPAR_ESTANDAR';
if ($AGRUPAR_VARIEDAD) $grpActiveInv[] = 'AGRUPAR_VARIEDAD';
if ($AGRUPAR_CALIBRE)  $grpActiveInv[] = 'AGRUPAR_CALIBRE';
$grpToggleUrlsInv = [];
foreach ($grpOptsInv as $gk => $glbl) {
    $next = [];
    if (in_array($gk, $grpActiveInv)) {
        foreach ($grpActiveInv as $g) { if ($g !== $gk) $next[] = $g; }
    } else {
        $next = array_merge($grpActiveInv, [$gk]);
    }
    if (empty($next)) $next = ['AGRUPAR_ESTANDAR'];
    $qs = 'ICARGA=' . (int)$IDICARGA;
    foreach ($next as $g) $qs .= '&' . $g . '=1';
    $grpToggleUrlsInv[$gk] = 'registroInvoiceExp.php?' . $qs;
}

// ── Excel export ────────────────────────────────────────────────────────────
if (isset($_GET['EXPORTAR']) && $IDICARGA > 0 && $INVOICE && count($DETALLE) > 0) {
    $arrTemp   = $TEMPORADA_ADO->verTemporada($TEMPORADAS);
    $tempNombre = $arrTemp ? $arrTemp[0]['NOMBRE_TEMPORADA'] : $TEMPORADAS;
    $refNombre  = $CABECERA['NREFERENCIA_ICARGA'] ?? 'invoice';
    $safeFile   = preg_replace('/[^A-Za-z0-9_\-]/', '_', $refNombre . '_' . $tempNombre);
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safeFile . '.xls"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fwrite($out, '<?xml version="1.0" encoding="UTF-8"?>'."\r\n");
    fwrite($out, '<?mso-application progid="Excel.Sheet"?>'."\r\n");
    fwrite($out, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'."\r\n");
    fwrite($out, '<Worksheet ss:Name="Invoice"><Table>'."\r\n");
    $cols = [];
    if ($AGRUPAR_ESTANDAR) $cols[] = 'Estandar';
    if ($AGRUPAR_VARIEDAD) $cols[] = 'Variedad';
    if ($AGRUPAR_CALIBRE)  $cols[] = 'Calibre';
    array_push($cols, 'Cajas', 'Kg Neto', 'Kg Bruto', 'Precio Caja', 'Total', 'Moneda', 'Origen', 'Observacion');
    fwrite($out, '<Row>');
    foreach ($cols as $c) fwrite($out, '<Cell><Data ss:Type="String">'.htmlspecialchars($c, ENT_XML1).'</Data></Cell>');
    fwrite($out, "</Row>\r\n");
    foreach ($DETALLE as $d) {
        fwrite($out, '<Row>');
        if ($AGRUPAR_ESTANDAR) fwrite($out, '<Cell><Data ss:Type="String">'.htmlspecialchars($d['NOMBRE_ESTANDAR'], ENT_XML1).'</Data></Cell>');
        if ($AGRUPAR_VARIEDAD) fwrite($out, '<Cell><Data ss:Type="String">'.htmlspecialchars($d['NOMBRE_VESPECIES'], ENT_XML1).'</Data></Cell>');
        if ($AGRUPAR_CALIBRE)  fwrite($out, '<Cell><Data ss:Type="String">'.htmlspecialchars($d['NOMBRE_TCALIBRE'], ENT_XML1).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="Number">'.number_format((float)$d['CANTIDAD_ENVASE'],2,'.','' ).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="Number">'.number_format((float)$d['KILOS_NETO'],2,'.',''      ).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="Number">'.number_format((float)$d['KILOS_BRUTO'],2,'.',''    ).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="Number">'.number_format((float)$d['PRECIO_CAJA'],4,'.',''    ).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="Number">'.number_format((float)$d['TOTAL_LINEA'],2,'.',''    ).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="String">'.htmlspecialchars($INVOICE['MONEDA_INVOICE'], ENT_XML1).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="String">'.htmlspecialchars($d['ORIGEN'], ENT_XML1).'</Data></Cell>');
        fwrite($out, '<Cell><Data ss:Type="String">'.htmlspecialchars($d['OBSERVACION'], ENT_XML1).'</Data></Cell>');
        fwrite($out, "</Row>\r\n");
    }
    fwrite($out, '</Table></Worksheet></Workbook>');
    fclose($out);
    exit;
}

// ── Print / PDF view ────────────────────────────────────────────────────────
if (isset($_GET['IMPRIMIR']) && $IDICARGA > 0 && $INVOICE && $CABECERA) {
    $arrEmp   = $EMPRESA_ADO->verEmpresa($EMPRESAS);
    $arrTemp  = $TEMPORADA_ADO->verTemporada($TEMPORADAS);
    $nomEmp   = $arrEmp  ? $arrEmp[0]['NOMBRE_EMPRESA']   : '';
    $nomTemp  = $arrTemp ? $arrTemp[0]['NOMBRE_TEMPORADA'] : $TEMPORADAS;
    $refDoc   = $CABECERA['NREFERENCIA_ICARGA'];
    $totalInv = array_sum(array_column($DETALLE, 'TOTAL_LINEA'));
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?php echo h('Invoice ' . $INVOICE['NUMERO_INVOICE'] . ' - ' . $refDoc . ' - ' . $nomTemp); ?></title>
<style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size:11px; color:#1a1a2e; background:#fff; }
    .print-page { max-width:1000px; margin:0 auto; padding:20px 24px 40px; }
    .doc-header { display:flex; justify-content:space-between; align-items:flex-end;
                  border-bottom:3px solid #0a3a6a; padding-bottom:12px; margin-bottom:16px; }
    .doc-header-left h1 { font-size:18px; font-weight:900; color:#0a3a6a; text-transform:uppercase; letter-spacing:.04em; }
    .doc-header-left p  { font-size:11px; color:#555; margin-top:2px; }
    .doc-header-right   { text-align:right; font-size:10px; color:#666; line-height:1.7; }
    .doc-header-right strong { font-size:13px; color:#0a3a6a; display:block; }
    .info-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin-bottom:14px; }
    .info-cell { background:#f4f7fb; border-left:3px solid #0a3a6a; padding:7px 10px; border-radius:0 4px 4px 0; }
    .info-cell .lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#667085; margin-bottom:2px; }
    .info-cell .val { font-size:12px; font-weight:700; color:#10233f; }
    .summary-bar { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-bottom:18px; }
    .sum-cell { border:1px solid #e2e8f0; border-radius:6px; padding:10px 12px; text-align:center; }
    .sum-cell .sum-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#667085; margin-bottom:4px; }
    .sum-cell .sum-val { font-size:15px; font-weight:900; color:#10233f; }
    .sum-cell.highlight { background:#0a3a6a; border-color:#0a3a6a; }
    .sum-cell.highlight .sum-lbl { color:rgba(255,255,255,.7); }
    .sum-cell.highlight .sum-val { color:#fff; }
    .section-title { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.07em;
                     color:#0a3a6a; border-bottom:1px solid #c9d5e0; padding-bottom:5px; margin-bottom:8px; margin-top:16px; }
    table { width:100%; border-collapse:collapse; font-size:10px; }
    th { background:#0a3a6a; color:#fff; font-size:9px; font-weight:700; text-transform:uppercase;
         letter-spacing:.04em; padding:6px 7px; text-align:center; border:1px solid #08305a; }
    td { padding:5px 7px; border:1px solid #dde3ec; vertical-align:middle; }
    tr:nth-child(even) td { background:#f9fbfd; }
    .text-right { text-align:right; }
    .text-center { text-align:center; }
    .total-row td { background:#e8f1fb !important; font-weight:800; color:#0a3a6a; border-top:2px solid #0a3a6a; }
    .obs-cell { font-size:9px; color:#555; max-width:160px; }
    .doc-footer { margin-top:30px; border-top:1px solid #c9d5e0; padding-top:10px;
                  display:flex; justify-content:space-between; color:#999; font-size:9px; }
    @media print {
        body { font-size:10px; }
        .no-print { display:none !important; }
        .print-page { padding:0; }
        @page { size:A4 landscape; margin:15mm 12mm; }
    }
</style>
</head>
<body onload="window.print()">
<div class="print-page">

    <div class="doc-header">
        <div class="doc-header-left">
            <h1>Invoice de Exportación</h1>
            <p><?php echo h($nomEmp); ?> &nbsp;·&nbsp; Temporada <?php echo h($nomTemp); ?></p>
        </div>
        <div class="doc-header-right">
            <strong>Invoice N° <?php echo h($INVOICE['NUMERO_INVOICE']); ?></strong>
            <?php echo h($refDoc); ?><br>
            <?php echo h($CABECERA['NCONTENEDOR_ICARGA']); ?><br>
            Fecha <?php echo h($INVOICE['FECHA_INVOICE']); ?> &nbsp;·&nbsp; <?php echo h($INVOICE['ESTADO_INVOICE']); ?>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-cell"><div class="lbl">Referencia</div><div class="val"><?php echo h($CABECERA['NREFERENCIA_ICARGA']); ?></div></div>
        <div class="info-cell"><div class="lbl">Contenedor</div><div class="val"><?php echo h($CABECERA['NCONTENEDOR_ICARGA']); ?></div></div>
        <div class="info-cell"><div class="lbl">Moneda</div><div class="val"><?php echo h($INVOICE['MONEDA_INVOICE']); ?><?php if ($INVOICE['MONEDA_INVOICE'] === 'EUR') echo ' (TC: ' . h($INVOICE['TIPO_CAMBIO_USD']) . ')'; ?></div></div>
        <div class="info-cell"><div class="lbl">Estado</div><div class="val"><?php echo h($INVOICE['ESTADO_INVOICE']); ?></div></div>
    </div>

    <?php if (trim((string)$INVOICE['OBSERVACION_INVOICE']) !== ''): ?>
    <div style="background:#fffbea;border-left:3px solid #f5a623;padding:8px 12px;margin-bottom:14px;font-size:10px;color:#555;border-radius:0 4px 4px 0;">
        <?php echo h($INVOICE['OBSERVACION_INVOICE']); ?>
    </div>
    <?php endif; ?>

    <?php $totalCajas = array_sum(array_column($DETALLE,'CANTIDAD_ENVASE'));
          $totalNeto  = array_sum(array_column($DETALLE,'KILOS_NETO')); ?>
    <div class="summary-bar">
        <div class="sum-cell">
            <div class="sum-lbl">Total cajas</div>
            <div class="sum-val"><?php echo number_format((float)$totalCajas, 0, ',', '.'); ?></div>
        </div>
        <div class="sum-cell">
            <div class="sum-lbl">Total kg neto</div>
            <div class="sum-val"><?php echo numero($totalNeto); ?></div>
        </div>
        <div class="sum-cell highlight">
            <div class="sum-lbl">Total invoice <?php echo h($INVOICE['MONEDA_INVOICE']); ?></div>
            <div class="sum-val"><?php echo numero($totalInv); ?></div>
        </div>
    </div>

    <div class="section-title">Detalle de líneas</div>
    <table>
        <thead><tr>
            <?php if ($AGRUPAR_ESTANDAR) echo '<th>Estándar</th>'; ?>
            <?php if ($AGRUPAR_VARIEDAD) echo '<th>Variedad</th>'; ?>
            <?php if ($AGRUPAR_CALIBRE)  echo '<th>Calibre</th>'; ?>
            <th>Cajas</th><th>Kg Neto</th><th>Kg Bruto</th>
            <th>Precio Caja</th><th>Total <?php echo h($INVOICE['MONEDA_INVOICE']); ?></th>
            <th>Origen</th><th>Obs.</th>
        </tr></thead>
        <tbody>
        <?php foreach ($DETALLE as $d): ?>
        <tr>
            <?php if ($AGRUPAR_ESTANDAR) echo '<td>'.h($d['NOMBRE_ESTANDAR']).'</td>'; ?>
            <?php if ($AGRUPAR_VARIEDAD) echo '<td>'.h($d['NOMBRE_VESPECIES']).'</td>'; ?>
            <?php if ($AGRUPAR_CALIBRE)  echo '<td class="text-center">'.h($d['NOMBRE_TCALIBRE']).'</td>'; ?>
            <td class="text-right"><?php echo numero($d['CANTIDAD_ENVASE']); ?></td>
            <td class="text-right"><?php echo numero($d['KILOS_NETO']); ?></td>
            <td class="text-right"><?php echo numero($d['KILOS_BRUTO']); ?></td>
            <td class="text-right"><?php echo numero($d['PRECIO_CAJA'], 4); ?></td>
            <td class="text-right"><?php echo numero($d['TOTAL_LINEA']); ?></td>
            <td class="text-center"><?php echo h($d['ORIGEN']); ?></td>
            <td class="obs-cell"><?php echo h($d['OBSERVACION']); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="total-row">
            <?php $colsGrp = (int)$AGRUPAR_ESTANDAR + (int)$AGRUPAR_VARIEDAD + (int)$AGRUPAR_CALIBRE;
                  echo '<td colspan="'.$colsGrp.'" class="text-right">TOTALES</td>'; ?>
            <td class="text-right"><?php echo numero($totalCajas); ?></td>
            <td class="text-right"><?php echo numero($totalNeto); ?></td>
            <td class="text-right"><?php echo numero(array_sum(array_column($DETALLE,'KILOS_BRUTO'))); ?></td>
            <td class="text-right">—</td>
            <td class="text-right"><?php echo numero($totalInv); ?></td>
            <td colspan="2"></td>
        </tr></tfoot>
    </table>

    <div class="doc-footer">
        <span>Generado: <?php echo date('d/m/Y H:i'); ?></span>
        <span><?php echo h($nomEmp); ?> · Temporada <?php echo h($nomTemp); ?></span>
        <span class="no-print">
            <button onclick="window.print()" style="cursor:pointer;padding:4px 12px;background:#0a3a6a;color:#fff;border:none;border-radius:4px;">Imprimir</button>
            <button onclick="window.close()" style="cursor:pointer;padding:4px 12px;background:#e2e8f0;color:#333;border:none;border-radius:4px;margin-left:6px;">Cerrar</button>
        </span>
    </div>
</div>
</body>
</html>
<?php exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Invoice Editable</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
        @media print {
            .main-sidebar, .left-side, .main-header, .content-header,
            .box-footer, .no-print, .breadcrumb, .alert { display:none !important; }
            .content-wrapper, .wrapper { margin:0 !important; padding:0 !important; background:#fff !important; }
            .box { border:none !important; box-shadow:none !important; }
            @page { size:A4 landscape; margin:12mm 10mm; }
        }
    </style>
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

                <?php if ($IDICARGA === 0) { ?>
                <div class="box">
                    <div class="box-header with-border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                            <h4 class="box-title">Invoices de exportación</h4>
                            <div class="d-flex align-items-center" style="gap:6px;flex-wrap:wrap;">
                                <span class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Filtrar:</span>
                                <button class="btn btn-xs btn-default inv-filter active" data-estado="">Todos</button>
                                <button class="btn btn-xs btn-success inv-filter" data-estado="CONFIRMADA">Confirmada</button>
                                <button class="btn btn-xs btn-warning inv-filter" data-estado="BORRADOR">Borrador</button>
                                <button class="btn btn-xs btn-default inv-filter" data-estado="SIN INVOICE">Sin invoice</button>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-hover" id="invTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Referencia</th>
                                    <th>Contenedor</th>
                                    <th>Estado</th>
                                    <th>Total US$</th>
                                    <th class="no-export">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $invIdx = 0; foreach ($INSTRUCTIVOS as $i) {
                                $invIdx++;
                                $est = $i['ESTADO_INVOICE'];
                                $badgeEst = $est === 'CONFIRMADA' ? 'badge-success' : ($est === 'BORRADOR' ? 'badge-warning' : 'badge-secondary');
                                $urlAbrir = 'registroInvoiceExp.php?ICARGA=' . (int)$i['ID_ICARGA'] . '&AGRUPAR_ESTANDAR=1';
                            ?>
                                <tr>
                                    <td class="text-center text-muted" style="font-size:12px;"><?php echo $invIdx; ?></td>
                                    <td><strong><?php echo h($i['NREFERENCIA_ICARGA']); ?></strong></td>
                                    <td><?php echo h($i['NCONTENEDOR_ICARGA']); ?></td>
                                    <td class="text-center"><?php echo $est; ?></td>
                                    <td class="text-right"><?php echo (float)$i['TOTAL_INVOICE'] > 0 ? numero($i['TOTAL_INVOICE']) : '—'; ?></td>
                                    <td class="text-center no-export">
                                        <a href="<?php echo $urlAbrir; ?>" class="btn btn-sm btn-outline-primary">
                                            Abrir <i class="ti-angle-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php } ?>

                <?php if ($IDICARGA > 0 && !$INVOICE) { ?>
                    <div class="box">
                        <div class="box-header with-border">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <a href="registroInvoiceExp.php" class="btn btn-sm btn-default" style="color:#333;">
                                    <i class="ti-angle-left"></i> Listado
                                </a>
                                <h4 class="box-title" style="margin:0;"><?php echo h($CABECERA['NREFERENCIA_ICARGA'] ?? ''); ?> — Sin invoice</h4>
                            </div>
                        </div>
                        <div class="box-body">
                            <form method="post">
                                <input type="hidden" name="ICARGA" value="<?php echo (int)$IDICARGA; ?>">
                                <button class="btn btn-success" name="GENERAR" value="1">Generar invoice desde despacho</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>

                <?php if ($INVOICE) {
                    $pdfUrlInv = 'registroInvoiceExp.php?ICARGA=' . (int)$IDICARGA . '&IMPRIMIR=1';
                    foreach ($grpActiveInv as $gk) $pdfUrlInv .= '&' . $gk . '=1';
                    $xlsUrlInv = 'registroInvoiceExp.php?ICARGA=' . (int)$IDICARGA . '&EXPORTAR=1';
                    foreach ($grpActiveInv as $gk) $xlsUrlInv .= '&' . $gk . '=1';
                ?>
                    <form method="post">
                        <?php if ($AGRUPAR_ESTANDAR) { ?><input type="hidden" name="AGRUPAR_ESTANDAR" value="1"><?php } ?>
                        <?php if ($AGRUPAR_VARIEDAD) { ?><input type="hidden" name="AGRUPAR_VARIEDAD" value="1"><?php } ?>
                        <?php if ($AGRUPAR_CALIBRE) { ?><input type="hidden" name="AGRUPAR_CALIBRE" value="1"><?php } ?>
                        <input type="hidden" name="ICARGA" value="<?php echo (int)$IDICARGA; ?>">
                        <div class="box">
                            <div class="box-header with-border bg-info">
                                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        <a href="registroInvoiceExp.php" class="btn btn-sm btn-default" style="color:#333;">
                                            <i class="ti-angle-left"></i> Listado
                                        </a>
                                        <h4 class="box-title" style="margin:0;">
                                            Invoice <?php echo h($INVOICE['NUMERO_INVOICE']); ?> — <?php echo h($INVOICE['ESTADO_INVOICE']); ?>
                                        </h4>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap:6px;flex-wrap:wrap;">
                                        <a href="<?php echo htmlspecialchars($xlsUrlInv, ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-xs btn-success" title="Exportar Excel">
                                            <i class="ti-export"></i> Excel
                                        </a>
                                        <a href="<?php echo htmlspecialchars($pdfUrlInv, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"
                                           class="btn btn-xs btn-warning" title="Ver e imprimir PDF">
                                            <i class="ti-printer"></i> PDF
                                        </a>
                                        <span style="width:1px;height:18px;background:rgba(255,255,255,.3);display:inline-block;margin:0 4px;"></span>
                                        <span style="font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.04em;">Agrupar:</span>
                                        <?php foreach ($grpOptsInv as $gk => $glbl):
                                            $isOn = in_array($gk, $grpActiveInv); ?>
                                        <a href="<?php echo htmlspecialchars($grpToggleUrlsInv[$gk], ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-xs <?php echo $isOn ? 'btn-primary' : 'btn-default'; ?>"
                                           style="font-weight:<?php echo $isOn ? '700' : '400'; ?>;<?php echo $isOn ? '' : 'opacity:.7;'; ?>">
                                            <?php echo $glbl; ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
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
                                <?php
                                $incoterm = strtoupper(trim((string)($CABECERA['INCOTERM'] ?? '')));
                                $esCIF = strpos($incoterm, 'CIF') !== false;
                                $esDPU = strpos($incoterm, 'DPU') !== false;
                                if ($esCIF || $esDPU): ?>
                                <div class="row mt-10">
                                    <?php if ($esCIF): ?>
                                    <div class="col-md-3">
                                        <label>Seguro (<?php echo h($incoterm); ?>)</label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                               name="SEGURO_INVOICE"
                                               value="<?php echo h($INVOICE['SEGURO_INVOICE'] ?? ''); ?>"
                                               placeholder="0.00"
                                               <?php echo $INVOICE_CONFIRMADA ? 'readonly' : ''; ?>>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-3">
                                        <label>Flete (<?php echo h($incoterm); ?>)</label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                               name="FLETE_INVOICE"
                                               value="<?php echo h($INVOICE['FLETE_INVOICE'] ?? ''); ?>"
                                               placeholder="0.00"
                                               <?php echo $INVOICE_CONFIRMADA ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <?php endif; ?>
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
// DataTables listing
<?php if ($IDICARGA === 0): ?>
(function () {
    var invDT = $('#invTable').DataTable({
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 5] },
            { searchable: false, targets: [0, 5] },
            { targets: 3, render: function (data, type) {
                if (type !== 'display') return data;
                var map = { 'CONFIRMADA':'badge-success', 'BORRADOR':'badge-warning', 'SIN INVOICE':'badge-secondary' };
                return '<span class="badge ' + (map[data] || 'badge-default') + '">' + data + '</span>';
            }}
        ],
        language: {
            search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
            info: '_START_–_END_ de _TOTAL_', infoEmpty: 'Sin registros',
            paginate: { previous: '‹', next: '›' }, zeroRecords: 'Sin resultados'
        }
    });
    $('.inv-filter').on('click', function () {
        $('.inv-filter').removeClass('active');
        $(this).addClass('active');
        invDT.column(3).search($(this).data('estado')).draw();
    });
})();
<?php endif; ?>

// Cálculo en vivo de totales
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
