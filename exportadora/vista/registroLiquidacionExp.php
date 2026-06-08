<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();
$mensaje = "";
$IDICARGA = isset($_REQUEST['ICARGA']) ? (int)$_REQUEST['ICARGA'] : 0;
$AGRUPAR_FOLIO = isset($_REQUEST['AGRUPAR_FOLIO']);
$AGRUPAR_ESTANDAR = isset($_REQUEST['AGRUPAR_ESTANDAR']) || !isset($_REQUEST['ICARGA']);
$AGRUPAR_VARIEDAD = isset($_REQUEST['AGRUPAR_VARIEDAD']) || !isset($_REQUEST['ICARGA']);
$AGRUPAR_CALIBRE = isset($_REQUEST['AGRUPAR_CALIBRE']) || !isset($_REQUEST['ICARGA']);
if (!$AGRUPAR_FOLIO && !$AGRUPAR_ESTANDAR && !$AGRUPAR_VARIEDAD && !$AGRUPAR_CALIBRE) {
    $AGRUPAR_ESTANDAR = true;
}
$FECHA = date('Y-m-d');
$ITEMS_LIQUIDACION = [];
$GASTOS_VALOR = [];
$IDVALOR_ACTUAL = 0;
$MONEDA_ACTUAL = 'USD';
$TIPO_CAMBIO_ACTUAL = 1.0;
$CONDICION_FLETE_ACTUAL = 'COLLECT';

$clpDolar = (float)preg_replace('/[^0-9]/', '', $TMONEDA1 ?? '');
$clpEuro  = (float)preg_replace('/[^0-9]/', '', $TMONEDA2 ?? '');
$EUR_USD_DEFAULT = ($clpDolar > 0 && $clpEuro > 0) ? round($clpEuro / $clpDolar, 6) : 1.0;

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function numero($valor) {
    return number_format((float)$valor, 2, ',', '.');
}

function liquidacionTieneValores($db, $idIcarga) {
    $stmt = $db->prepare("
        SELECT MAX(CASE
            WHEN IFNULL(val.ESTADO_LIQUIDACION, 'LIQUIDADA') = 'REABIERTA' THEN 0
            WHEN IFNULL(det.FOB_REAL, 0) <> 0
                OR IFNULL(det.COSTO_COMISION, 0) <> 0
                OR IFNULL(det.COSTO_FLETE, 0) <> 0
                OR IFNULL(det.COSTO_OTROS, 0) <> 0
                OR IFNULL(gasto.VALOR_DVALOR, 0) <> 0
            THEN 1 ELSE 0
        END)
        FROM liquidacion_valor val
        LEFT JOIN liquidacion_detalle_exp det ON det.ID_VALOR = val.ID_VALOR
            AND det.ESTADO_REGISTRO = 1
        LEFT JOIN liquidacion_dvalor gasto ON gasto.ID_VALOR = val.ID_VALOR
            AND gasto.ESTADO_REGISTRO = 1
        WHERE val.ID_ICARGA = ?
        AND val.ESTADO_REGISTRO = 1
    ");
    $stmt->execute([$idIcarga]);
    return (int)$stmt->fetchColumn() === 1;
}

$stmtIcarga = $db->prepare("
    SELECT
        i.ID_ICARGA,
        i.NUMERO_ICARGA,
        i.NREFERENCIA_ICARGA,
        COALESCE(NULLIF(i.NCONTENEDOR_ICARGA, ''), dx.CONTENEDOR_DESPACHO, '') AS NCONTENEDOR_ICARGA,
        i.FECHA_ICARGA,
        IFNULL(tf.NOMBRE_TFLETE, '') AS TIPO_FLETE,
        CASE WHEN UPPER(IFNULL(tf.NOMBRE_TFLETE, '')) LIKE '%PREPAID%' THEN 'PREPAID' ELSE 'COLLECT' END AS CONDICION_FLETE_ICARGA,
        IFNULL(dx.DETALLE_EXPORTADO, 0) AS DETALLE_EXPORTADO,
        liq.ID_VALOR AS ID_VALOR_LIQUIDACION,
        liq.TIENE_VALORES AS TIENE_VALORES_LIQUIDACION,
        IFNULL(COALESCE(inv.TIENE_PRECIO_REFERENCIA, dic.TIENE_PRECIO_REFERENCIA), 0) AS TIENE_PRECIO_REFERENCIA
    FROM fruta_icarga i
    LEFT JOIN (
        SELECT
            d.ID_ICARGA,
            MIN(NULLIF(NULLIF(d.NUMERO_CONTENEDOR_DESPACHOEX, ''), '0')) AS CONTENEDOR_DESPACHO,
            COUNT(exi.ID_EXIEXPORTACION) AS DETALLE_EXPORTADO
        FROM fruta_despachoex d
        LEFT JOIN fruta_exiexportacion exi ON exi.ID_DESPACHOEX = d.ID_DESPACHOEX
            AND exi.ESTADO_REGISTRO = 1
        WHERE d.ESTADO_REGISTRO = 1
        GROUP BY d.ID_ICARGA
    ) dx ON dx.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN (
        SELECT
            val.ID_ICARGA,
            MAX(val.ID_VALOR) AS ID_VALOR,
            MAX(CASE
                WHEN IFNULL(val.ESTADO_LIQUIDACION, 'LIQUIDADA') = 'REABIERTA' THEN 0
                WHEN IFNULL(det.FOB_REAL, 0) <> 0
                    OR IFNULL(det.COSTO_COMISION, 0) <> 0
                    OR IFNULL(det.COSTO_FLETE, 0) <> 0
                    OR IFNULL(det.COSTO_OTROS, 0) <> 0
                    OR IFNULL(gasto.VALOR_DVALOR, 0) <> 0
                THEN 1 ELSE 0
            END) AS TIENE_VALORES
        FROM liquidacion_valor val
        LEFT JOIN liquidacion_detalle_exp det ON det.ID_VALOR = val.ID_VALOR
            AND det.ESTADO_REGISTRO = 1
        LEFT JOIN liquidacion_dvalor gasto ON gasto.ID_VALOR = val.ID_VALOR
            AND gasto.ESTADO_REGISTRO = 1
        WHERE val.ESTADO_REGISTRO = 1
        GROUP BY val.ID_ICARGA
    ) liq ON liq.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN (
        SELECT
            ID_ICARGA,
            MAX(CASE WHEN IFNULL(PRECIO_US_DICARGA, 0) <> 0 THEN 1 ELSE 0 END) AS TIENE_PRECIO_REFERENCIA
        FROM fruta_dicarga
        WHERE ESTADO_REGISTRO = 1
        GROUP BY ID_ICARGA
    ) dic ON dic.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN (
        SELECT
            inv.ID_ICARGA,
            MAX(CASE WHEN IFNULL(idet.PRECIO_CAJA, 0) <> 0 THEN 1 ELSE 0 END) AS TIENE_PRECIO_REFERENCIA
        FROM exportadora_invoice inv
        INNER JOIN exportadora_invoice_detalle idet ON idet.ID_INVOICE = inv.ID_INVOICE
            AND idet.ESTADO_REGISTRO = 1
        WHERE inv.ESTADO_REGISTRO = 1
        AND inv.ESTADO_INVOICE = 'CONFIRMADA'
        GROUP BY inv.ID_ICARGA
    ) inv ON inv.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
    WHERE i.ESTADO_REGISTRO = 1
    AND i.ID_EMPRESA = ?
    AND i.ID_TEMPORADA = ?
    ORDER BY i.ID_ICARGA DESC
");
$stmtIcarga->execute([$EMPRESAS, $TEMPORADAS]);
$INSTRUCTIVOS = $stmtIcarga->fetchAll(PDO::FETCH_ASSOC);

$stmtItems = $db->prepare("
    SELECT ID_TITEM, NOMBRE_TITEM, IFNULL(TIPO_GASTO, 'GASTO') AS TIPO_GASTO
    FROM liquidacion_titem
    WHERE ESTADO_REGISTRO = 1
    AND TAITEM = 1
    AND ID_EMPRESA = ?
    ORDER BY NUMERO_TITEM ASC, ID_TITEM ASC
");
$stmtItems->execute([$EMPRESAS]);
$ITEMS_LIQUIDACION = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['GUARDAR']) && $IDICARGA > 0) {
    if (liquidacionTieneValores($db, $IDICARGA)) {
        $mensaje = "La liquidacion ya esta liquidada y no se puede editar.";
    } else {
    $MONEDA_ORIGEN_POST = in_array($_POST['MONEDA_ORIGEN'] ?? '', ['USD', 'EUR']) ? $_POST['MONEDA_ORIGEN'] : 'USD';
    $TIPO_CAMBIO_POST = max(0.000001, (float)str_replace(',', '.', $_POST['TIPO_CAMBIO_USD'] ?? '1'));
    $FECHA_TIPO_CAMBIO_POST = $MONEDA_ORIGEN_POST === 'EUR' ? $FECHA : null;
    $stmtTipoFlete = $db->prepare("
        SELECT CASE WHEN UPPER(IFNULL(tf.NOMBRE_TFLETE, '')) LIKE '%PREPAID%' THEN 'PREPAID' ELSE 'COLLECT' END
        FROM fruta_icarga i
        LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
        WHERE i.ID_ICARGA = ?
        LIMIT 1
    ");
    $stmtTipoFlete->execute([$IDICARGA]);
    $CONDICION_FLETE_POST = $stmtTipoFlete->fetchColumn() ?: 'COLLECT';

    $stmtValor = $db->prepare("SELECT ID_VALOR FROM liquidacion_valor WHERE ID_ICARGA = ? AND ESTADO_REGISTRO = 1 ORDER BY ID_VALOR DESC LIMIT 1");
    $stmtValor->execute([$IDICARGA]);
    $IDVALOR = (int)$stmtValor->fetchColumn();

    if (!$IDVALOR) {
        $stmtNumero = $db->prepare("SELECT COUNT(*) + 1 FROM liquidacion_valor WHERE ID_EMPRESA = ? AND ID_TEMPORADA = ?");
        $stmtNumero->execute([$EMPRESAS, $TEMPORADAS]);
        $NUMERO = (int)$stmtNumero->fetchColumn();

        $stmtCrear = $db->prepare("
            INSERT INTO liquidacion_valor
                (NUMERO_VALOR, FECHA_VALOR, OBSERVACION_VALOR, MONEDA_ORIGEN, TIPO_CAMBIO_USD, FECHA_TIPO_CAMBIO, CONDICION_FLETE, ESTADO_LIQUIDACION,
                 ID_ICARGA, ID_EMPRESA, ID_TEMPORADA, ID_USUARIOI, ID_USUARIOM, INGRESO, MODIFICACION, ESTADO, ESTADO_REGISTRO)
            VALUES
                (?, ?, 'Liquidacion exportacion detallada', ?, ?, ?, ?, 'LIQUIDADA', ?, ?, ?, ?, ?, SYSDATE(), SYSDATE(), 1, 1)
        ");
        $stmtCrear->execute([
            $NUMERO, $FECHA,
            $MONEDA_ORIGEN_POST, $TIPO_CAMBIO_POST, $FECHA_TIPO_CAMBIO_POST, $CONDICION_FLETE_POST,
            $IDICARGA, $EMPRESAS, $TEMPORADAS, $IDUSUARIOS, $IDUSUARIOS
        ]);
        $IDVALOR = (int)$db->lastInsertId();
    } else {
        $db->prepare("
            UPDATE liquidacion_valor
            SET MONEDA_ORIGEN = ?, TIPO_CAMBIO_USD = ?, FECHA_TIPO_CAMBIO = ?, CONDICION_FLETE = ?, ESTADO_LIQUIDACION = 'LIQUIDADA', ID_USUARIOM = ?, MODIFICACION = SYSDATE()
            WHERE ID_VALOR = ?
        ")->execute([$MONEDA_ORIGEN_POST, $TIPO_CAMBIO_POST, $FECHA_TIPO_CAMBIO_POST, $CONDICION_FLETE_POST, $IDUSUARIOS, $IDVALOR]);
    }

    $stmtGuardar = $db->prepare("
        INSERT INTO liquidacion_detalle_exp
            (ID_VALOR, ID_ICARGA, ID_EXIEXPORTACION, FOLIO_EXIEXPORTACION, ID_PRODUCTOR, ID_VESPECIES, ID_ESTANDAR, ID_TCALIBRE,
             CANTIDAD_ENVASE, KILOS_NETO, KILOS_BRUTO, FOB_REAL, FOB_ORIGEN_KG, COSTO_COMISION, COSTO_FLETE, COSTO_OTROS, OBSERVACION,
             ID_USUARIOI, ID_USUARIOM)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            CANTIDAD_ENVASE = VALUES(CANTIDAD_ENVASE),
            KILOS_NETO = VALUES(KILOS_NETO),
            KILOS_BRUTO = VALUES(KILOS_BRUTO),
            FOB_REAL = VALUES(FOB_REAL),
            FOB_ORIGEN_KG = VALUES(FOB_ORIGEN_KG),
            COSTO_COMISION = VALUES(COSTO_COMISION),
            COSTO_FLETE = VALUES(COSTO_FLETE),
            COSTO_OTROS = VALUES(COSTO_OTROS),
            OBSERVACION = VALUES(OBSERVACION),
            ID_USUARIOM = VALUES(ID_USUARIOM),
            ESTADO_REGISTRO = 1,
            MODIFICACION = SYSDATE()
    ");

    $stmtDesactivarDetalleAnterior = $db->prepare("
        UPDATE liquidacion_detalle_exp
        SET ESTADO_REGISTRO = 0,
            ID_USUARIOM = ?,
            MODIFICACION = SYSDATE()
        WHERE ID_VALOR = ?
        AND ESTADO_REGISTRO = 1
    ");
    $stmtDesactivarDetalleAnterior->execute([$IDUSUARIOS, $IDVALOR]);

    foreach ($_POST['linea'] ?? [] as $linea) {
        $fobKgUsd    = (float)str_replace(',', '.', $linea['fob_kg']);
        $fobOrigenKg = (float)str_replace(',', '.', $linea['fob_origen_kg'] ?? $linea['fob_kg']);
        $stmtGuardar->execute([
            $IDVALOR,
            $IDICARGA,
            (int)$linea['exiexportacion'],
            (int)$linea['folio'],
            (int)$linea['productor'],
            (int)$linea['vespecies'],
            (int)$linea['estandar'],
            (int)$linea['calibre'],
            (int)$linea['envases'],
            (float)$linea['neto'],
            (float)$linea['bruto'],
            $fobKgUsd,
            $fobOrigenKg,
            0, 0, 0,
            $linea['observacion'],
            $IDUSUARIOS,
            $IDUSUARIOS
        ]);
    }

    $stmtBuscarGasto = $db->prepare("
        SELECT ID_DVALOR
        FROM liquidacion_dvalor
        WHERE ID_VALOR = ?
        AND ID_TITEM = ?
        AND ESTADO_REGISTRO = 1
        ORDER BY ID_DVALOR DESC
        LIMIT 1
    ");
    $stmtInsertarGasto = $db->prepare("
        INSERT INTO liquidacion_dvalor
            (VALOR_DVALOR, DETALLE, ID_TITEM, ID_USUARIOI, ID_USUARIOM, ID_VALOR, INGRESO, MODIFICACION, ESTADO, ESTADO_REGISTRO)
        VALUES
            (?, 0, ?, ?, ?, ?, SYSDATE(), SYSDATE(), 1, 1)
    ");
    $stmtActualizarGasto = $db->prepare("
        UPDATE liquidacion_dvalor
        SET VALOR_DVALOR = ?,
            ID_USUARIOM = ?,
            MODIFICACION = SYSDATE()
        WHERE ID_DVALOR = ?
    ");
    foreach ($_POST['gasto'] ?? [] as $idTitem => $valorGasto) {
        $idTitem = (int)$idTitem;
        $valorGasto = (float)str_replace(',', '.', $valorGasto);
        if ($MONEDA_ORIGEN_POST === 'EUR') {
            $valorGasto = $valorGasto * $TIPO_CAMBIO_POST;
        }
        $stmtBuscarGasto->execute([$IDVALOR, $idTitem]);
        $idDvalor = (int)$stmtBuscarGasto->fetchColumn();
        if ($idDvalor > 0) {
            $stmtActualizarGasto->execute([$valorGasto, $IDUSUARIOS, $idDvalor]);
        } elseif ($valorGasto != 0) {
            $stmtInsertarGasto->execute([$valorGasto, $idTitem, $IDUSUARIOS, $IDUSUARIOS, $IDVALOR]);
        }
    }

    $mensaje = "Liquidacion guardada correctamente.";
    }
}

$CABECERA = null;
$LINEAS = [];
$GRUPOS = [];
$TOTAL_ENVASES = 0;
$TOTAL_NETO = 0;
$TOTAL_ENVASES_EXPORTADO = 0;
$TOTAL_NETO_EXPORTADO = 0;
$TOTAL_VENTA = 0;
$TOTAL_COMISION = 0;
$TOTAL_FLETE = 0;
$TOTAL_OTROS = 0;
$TOTAL_GASTOS_ITEMS = 0;
$TOTAL_COMISION_ITEMS = 0;
$TOTAL_VENTA_NETO = 0;
$TOTAL_INVOICE_USD = 0;
$TOTAL_INVOICE_ORIGEN = 0;
$MONEDA_INVOICE_CONFIRMADA = '';
$TIPO_CAMBIO_INVOICE = 1;
$DIFERENCIA_INVOICE_LIQUIDACION = 0;
$DOCUMENTO_SUGERIDO = 'Sin invoice confirmada';
$TOLERANCIA_NOTA = 1.00;
$LIQUIDACION_BLOQUEADA = false;
if ($IDICARGA > 0) {
    $stmtValorActual = $db->prepare("
        SELECT val.ID_VALOR,
               IFNULL(val.MONEDA_ORIGEN, 'USD') AS MONEDA_ORIGEN,
               IFNULL(val.TIPO_CAMBIO_USD, 1) AS TIPO_CAMBIO_USD,
               IFNULL(val.ESTADO_LIQUIDACION, 'LIQUIDADA') AS ESTADO_LIQUIDACION,
               CASE WHEN UPPER(IFNULL(tf.NOMBRE_TFLETE, '')) LIKE '%PREPAID%' THEN 'PREPAID' ELSE 'COLLECT' END AS CONDICION_FLETE
        FROM fruta_icarga i
        LEFT JOIN liquidacion_valor val ON val.ID_ICARGA = i.ID_ICARGA
            AND val.ESTADO_REGISTRO = 1
        LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
        WHERE i.ID_ICARGA = ?
        ORDER BY val.ID_VALOR DESC LIMIT 1
    ");
    $stmtValorActual->execute([$IDICARGA]);
    $rowValorActual  = $stmtValorActual->fetch(PDO::FETCH_ASSOC);
    $IDVALOR_ACTUAL  = (int)($rowValorActual['ID_VALOR'] ?? 0);
    $MONEDA_ACTUAL   = $rowValorActual['MONEDA_ORIGEN'] ?? 'USD';
    $TIPO_CAMBIO_ACTUAL = (float)($rowValorActual['TIPO_CAMBIO_USD'] ?? 1.0);
    $CONDICION_FLETE_ACTUAL = $rowValorActual['CONDICION_FLETE'] ?? 'COLLECT';
    $LIQUIDACION_BLOQUEADA = liquidacionTieneValores($db, $IDICARGA);

    if ($IDVALOR_ACTUAL > 0) {
        $stmtGastos = $db->prepare("
            SELECT dv.ID_TITEM, dv.VALOR_DVALOR
            FROM liquidacion_dvalor dv
            INNER JOIN liquidacion_titem ti ON ti.ID_TITEM = dv.ID_TITEM
                AND ti.ESTADO_REGISTRO = 1
                AND ti.TAITEM = 1
                AND ti.ID_EMPRESA = ?
            WHERE dv.ID_VALOR = ?
            AND dv.ESTADO_REGISTRO = 1
        ");
        $stmtGastos->execute([$EMPRESAS, $IDVALOR_ACTUAL]);
        foreach ($stmtGastos->fetchAll(PDO::FETCH_ASSOC) as $gasto) {
            $GASTOS_VALOR[(int)$gasto['ID_TITEM']] = (float)$gasto['VALOR_DVALOR'];
        }
    }

    $stmtCabecera = $db->prepare("
        SELECT
            i.ID_ICARGA,
            i.NUMERO_ICARGA,
            i.NREFERENCIA_ICARGA,
            IFNULL(tf.NOMBRE_TFLETE, '') AS TIPO_FLETE,
            CASE WHEN UPPER(IFNULL(tf.NOMBRE_TFLETE, '')) LIKE '%PREPAID%' THEN 'PREPAID' ELSE 'COLLECT' END AS CONDICION_FLETE_ICARGA,
            COALESCE(NULLIF(i.NCONTENEDOR_ICARGA, ''), dx.CONTENEDOR_DESPACHO, '') AS NCONTENEDOR_ICARGA,
            i.FECHA_ICARGA
        FROM fruta_icarga i
        LEFT JOIN (
            SELECT
                d.ID_ICARGA,
                MIN(NULLIF(NULLIF(d.NUMERO_CONTENEDOR_DESPACHOEX, ''), '0')) AS CONTENEDOR_DESPACHO
            FROM fruta_despachoex d
            WHERE d.ESTADO_REGISTRO = 1
            GROUP BY d.ID_ICARGA
        ) dx ON dx.ID_ICARGA = i.ID_ICARGA
        LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
        WHERE i.ID_ICARGA = ?
    ");
    $stmtCabecera->execute([$IDICARGA]);
    $CABECERA = $stmtCabecera->fetch(PDO::FETCH_ASSOC);

    $stmtInvoiceConfirmada = $db->prepare("
        SELECT
            inv.MONEDA_INVOICE,
            IFNULL(inv.TIPO_CAMBIO_USD, 1) AS TIPO_CAMBIO_USD,
            SUM(IFNULL(det.TOTAL_LINEA, 0)) AS TOTAL_INVOICE_ORIGEN,
            SUM(IFNULL(det.TOTAL_LINEA, 0)) *
                CASE WHEN inv.MONEDA_INVOICE = 'EUR' THEN IFNULL(inv.TIPO_CAMBIO_USD, 1) ELSE 1 END AS TOTAL_INVOICE_USD
        FROM exportadora_invoice inv
        INNER JOIN exportadora_invoice_detalle det ON det.ID_INVOICE = inv.ID_INVOICE
            AND det.ESTADO_REGISTRO = 1
        WHERE inv.ID_ICARGA = ?
        AND inv.ESTADO_INVOICE = 'CONFIRMADA'
        AND inv.ESTADO_REGISTRO = 1
        GROUP BY inv.ID_INVOICE, inv.MONEDA_INVOICE, inv.TIPO_CAMBIO_USD
        ORDER BY inv.ID_INVOICE DESC
        LIMIT 1
    ");
    $stmtInvoiceConfirmada->execute([$IDICARGA]);
    $INVOICE_CONFIRMADA_RESUMEN = $stmtInvoiceConfirmada->fetch(PDO::FETCH_ASSOC);
    if ($INVOICE_CONFIRMADA_RESUMEN) {
        $TOTAL_INVOICE_USD = (float)$INVOICE_CONFIRMADA_RESUMEN['TOTAL_INVOICE_USD'];
        $TOTAL_INVOICE_ORIGEN = (float)$INVOICE_CONFIRMADA_RESUMEN['TOTAL_INVOICE_ORIGEN'];
        $MONEDA_INVOICE_CONFIRMADA = $INVOICE_CONFIRMADA_RESUMEN['MONEDA_INVOICE'] ?? 'USD';
        $TIPO_CAMBIO_INVOICE = (float)($INVOICE_CONFIRMADA_RESUMEN['TIPO_CAMBIO_USD'] ?? 1);
    }

    if ($CABECERA) {
        $stmtTotalesExportado = $db->prepare("
            SELECT
                SUM(IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)) AS ENVASES,
                SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0)) AS NETO
            FROM fruta_exiexportacion exi
            LEFT JOIN fruta_despachoex dex ON dex.ID_DESPACHOEX = exi.ID_DESPACHOEX
                AND dex.ESTADO_REGISTRO = 1
            WHERE exi.ESTADO_REGISTRO = 1
            AND exi.ID_EMPRESA = ?
            AND exi.ID_TEMPORADA = ?
            AND (
                exi.ID_ICARGA = ?
                OR dex.ID_ICARGA = ?
                OR exi.REFERENCIA = CONVERT(? USING utf8mb4) COLLATE utf8mb4_spanish_ci
                OR exi.REFERENCIA = CONVERT(? USING utf8mb4) COLLATE utf8mb4_spanish_ci
            )
        ");
        $stmtTotalesExportado->execute([
            $EMPRESAS,
            $TEMPORADAS,
            $IDICARGA,
            $IDICARGA,
            $IDICARGA,
            $CABECERA['NUMERO_ICARGA']
        ]);
        $TOTALES_EXPORTADO = $stmtTotalesExportado->fetch(PDO::FETCH_ASSOC);
        $TOTAL_ENVASES_EXPORTADO = (int)($TOTALES_EXPORTADO['ENVASES'] ?? 0);
        $TOTAL_NETO_EXPORTADO = (float)($TOTALES_EXPORTADO['NETO'] ?? 0);

        $mostrarProductor = $AGRUPAR_FOLIO;
        $selectFolio = $AGRUPAR_FOLIO ? "exi.ID_EXIEXPORTACION" : "0 AS ID_EXIEXPORTACION";
        $selectFolioNumero = $AGRUPAR_FOLIO ? "MAX(exi.FOLIO_EXIEXPORTACION) AS FOLIO_LINEA" : "0 AS FOLIO_LINEA";
        $selectProductor = $mostrarProductor ? "exi.ID_PRODUCTOR" : "0 AS ID_PRODUCTOR";
        $csgProductor = $mostrarProductor ? "IFNULL(prod.CSG_PRODUCTOR, '') AS CSG_PRODUCTOR" : "'' AS CSG_PRODUCTOR";
        $nombreProductor = $mostrarProductor ? "IFNULL(prod.NOMBRE_PRODUCTOR, 'Sin productor') AS NOMBRE_PRODUCTOR" : "'Todos' AS NOMBRE_PRODUCTOR";
        $selectEstandar = $AGRUPAR_ESTANDAR ? "exi.ID_ESTANDAR" : "0 AS ID_ESTANDAR";
        $selectVespecies = $AGRUPAR_VARIEDAD ? "exi.ID_VESPECIES" : "0 AS ID_VESPECIES";
        $selectCalibre = $AGRUPAR_CALIBRE ? "exi.ID_TCALIBRE" : "0 AS ID_TCALIBRE";
        $nombreEstandar = $AGRUPAR_ESTANDAR ? "IFNULL(est.NOMBRE_ESTANDAR, 'Sin estandar') AS NOMBRE_ESTANDAR" : "'Todos' AS NOMBRE_ESTANDAR";
        $nombreVespecies = $AGRUPAR_VARIEDAD ? "IFNULL(ves.NOMBRE_VESPECIES, 'Sin variedad') AS NOMBRE_VESPECIES" : "'Todas' AS NOMBRE_VESPECIES";
        $nombreCalibre = $AGRUPAR_CALIBRE ? "IFNULL(cal.NOMBRE_TCALIBRE, 'Sin calibre') AS NOMBRE_TCALIBRE" : "'Todos' AS NOMBRE_TCALIBRE";
        $dicUsaEstandar = ($AGRUPAR_FOLIO || $AGRUPAR_ESTANDAR);
        $dicUsaVespecies = ($AGRUPAR_FOLIO || $AGRUPAR_VARIEDAD);
        $dicUsaCalibre = ($AGRUPAR_FOLIO || $AGRUPAR_CALIBRE);
        $dicSelectEstandar = $dicUsaEstandar ? "ID_ESTANDAR" : "0";
        $dicSelectVespecies = $dicUsaVespecies ? "IFNULL(ID_VESPECIES, 0)" : "0";
        $dicSelectCalibre = $dicUsaCalibre ? "ID_TCALIBRE" : "0";
        $invSelectEstandar = $dicUsaEstandar ? "idet.ID_ESTANDAR" : "0";
        $invSelectVespecies = $dicUsaVespecies ? "IFNULL(idet.ID_VESPECIES, 0)" : "0";
        $invSelectCalibre = $dicUsaCalibre ? "idet.ID_TCALIBRE" : "0";
        $joinDicEstandar = $dicUsaEstandar ? "AND dic.ID_ESTANDAR_REF = exi.ID_ESTANDAR" : "AND dic.ID_ESTANDAR_REF = 0";
        $joinDicVespecies = $dicUsaVespecies ? "AND dic.ID_VESPECIES_REF = exi.ID_VESPECIES" : "AND dic.ID_VESPECIES_REF = 0";
        $joinDicCalibre = $dicUsaCalibre ? "AND dic.ID_TCALIBRE_REF = exi.ID_TCALIBRE" : "AND dic.ID_TCALIBRE_REF = 0";
        $joinInvEstandar = $dicUsaEstandar ? "AND invref.ID_ESTANDAR_REF = exi.ID_ESTANDAR" : "AND invref.ID_ESTANDAR_REF = 0";
        $joinInvVespecies = $dicUsaVespecies ? "AND invref.ID_VESPECIES_REF = exi.ID_VESPECIES" : "AND invref.ID_VESPECIES_REF = 0";
        $joinInvCalibre = $dicUsaCalibre ? "AND invref.ID_TCALIBRE_REF = exi.ID_TCALIBRE" : "AND invref.ID_TCALIBRE_REF = 0";
        $joinDetEstandar = $AGRUPAR_ESTANDAR ? "AND det.ID_ESTANDAR = exi.ID_ESTANDAR" : "";
        $joinDetVespecies = $AGRUPAR_VARIEDAD ? "AND det.ID_VESPECIES = exi.ID_VESPECIES" : "";
        $joinDetCalibre = $AGRUPAR_CALIBRE ? "AND det.ID_TCALIBRE = exi.ID_TCALIBRE" : "";
        $joinDetFolio = $AGRUPAR_FOLIO ? "AND det.ID_EXIEXPORTACION = exi.ID_EXIEXPORTACION" : "AND det.ID_EXIEXPORTACION = 0";
        $joinDetProductor = $mostrarProductor ? "AND det.ID_PRODUCTOR = exi.ID_PRODUCTOR" : "AND det.ID_PRODUCTOR = 0";
        $joinDetBaseProductor = "AND det_base.ID_PRODUCTOR = 0";
        $condDet = ($AGRUPAR_FOLIO ? "AND d.ID_EXIEXPORTACION = exi.ID_EXIEXPORTACION" : "AND d.ID_EXIEXPORTACION = 0")
            . ($mostrarProductor ? " AND d.ID_PRODUCTOR = exi.ID_PRODUCTOR" : " AND d.ID_PRODUCTOR = 0")
            . ($AGRUPAR_ESTANDAR ? " AND d.ID_ESTANDAR = exi.ID_ESTANDAR" : "")
            . ($AGRUPAR_VARIEDAD ? " AND d.ID_VESPECIES = exi.ID_VESPECIES" : "")
            . ($AGRUPAR_CALIBRE ? " AND d.ID_TCALIBRE = exi.ID_TCALIBRE" : "");
        $condDetBase = "AND db.ID_EXIEXPORTACION = 0"
            . " AND db.ID_PRODUCTOR = 0"
            . ($AGRUPAR_ESTANDAR ? " AND db.ID_ESTANDAR = exi.ID_ESTANDAR" : "")
            . ($AGRUPAR_VARIEDAD ? " AND db.ID_VESPECIES = exi.ID_VESPECIES" : "")
            . ($AGRUPAR_CALIBRE ? " AND db.ID_TCALIBRE = exi.ID_TCALIBRE" : "");
        $fallbackConds = [];
        if ($AGRUPAR_ESTANDAR && $AGRUPAR_VARIEDAD) {
            $fallbackConds[] = "AND fb.ID_ESTANDAR = exi.ID_ESTANDAR AND fb.ID_VESPECIES = exi.ID_VESPECIES AND fb.ID_TCALIBRE = 0";
        }
        if ($AGRUPAR_ESTANDAR && $AGRUPAR_CALIBRE) {
            $fallbackConds[] = "AND fb.ID_ESTANDAR = exi.ID_ESTANDAR AND fb.ID_VESPECIES = 0 AND fb.ID_TCALIBRE = exi.ID_TCALIBRE";
        }
        if ($AGRUPAR_VARIEDAD && $AGRUPAR_CALIBRE) {
            $fallbackConds[] = "AND fb.ID_ESTANDAR = 0 AND fb.ID_VESPECIES = exi.ID_VESPECIES AND fb.ID_TCALIBRE = exi.ID_TCALIBRE";
        }
        if ($AGRUPAR_ESTANDAR) {
            $fallbackConds[] = "AND fb.ID_ESTANDAR = exi.ID_ESTANDAR AND fb.ID_VESPECIES = 0 AND fb.ID_TCALIBRE = 0";
        }
        if ($AGRUPAR_VARIEDAD) {
            $fallbackConds[] = "AND fb.ID_ESTANDAR = 0 AND fb.ID_VESPECIES = exi.ID_VESPECIES AND fb.ID_TCALIBRE = 0";
        }
        if ($AGRUPAR_CALIBRE) {
            $fallbackConds[] = "AND fb.ID_ESTANDAR = 0 AND fb.ID_VESPECIES = 0 AND fb.ID_TCALIBRE = exi.ID_TCALIBRE";
        }
        $fallbackConds[] = "AND fb.ID_ESTANDAR = 0 AND fb.ID_VESPECIES = 0 AND fb.ID_TCALIBRE = 0";
        $fallbackSql = function ($campo) use ($fallbackConds) {
            $partes = [];
            foreach (array_unique($fallbackConds) as $condicion) {
                $partes[] = "(SELECT MAX(fb.$campo) FROM liquidacion_detalle_exp fb WHERE fb.ID_VALOR = val.ID_VALOR AND fb.ID_EXIEXPORTACION = 0 AND fb.ID_PRODUCTOR = 0 $condicion AND fb.ESTADO_REGISTRO = 1)";
            }
            return implode(",\n                    ", $partes);
        };
        $fobPorDetalle = "
                    COALESCE(
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = exi.ID_EXIEXPORTACION AND px.ID_PRODUCTOR = exi.ID_PRODUCTOR AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                        (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = val.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
                        0
                    )";
        $fallbackFob = $fallbackSql('FOB_REAL');
        $fallbackComision = $fallbackSql('COSTO_COMISION');
        $fallbackFlete = $fallbackSql('COSTO_FLETE');
        $fallbackOtros = $fallbackSql('COSTO_OTROS');
        $fallbackObs = $fallbackSql('OBSERVACION');
        $detBaseFob = $AGRUPAR_FOLIO ? "NULL" : "(SELECT MAX(db.FOB_REAL) FROM liquidacion_detalle_exp db WHERE db.ID_VALOR = val.ID_VALOR $condDetBase AND db.ESTADO_REGISTRO = 1)";
        $detBaseComision = $AGRUPAR_FOLIO ? "NULL" : "(SELECT MAX(db.COSTO_COMISION) FROM liquidacion_detalle_exp db WHERE db.ID_VALOR = val.ID_VALOR $condDetBase AND db.ESTADO_REGISTRO = 1)";
        $detBaseFlete = $AGRUPAR_FOLIO ? "NULL" : "(SELECT MAX(db.COSTO_FLETE) FROM liquidacion_detalle_exp db WHERE db.ID_VALOR = val.ID_VALOR $condDetBase AND db.ESTADO_REGISTRO = 1)";
        $detBaseOtros = $AGRUPAR_FOLIO ? "NULL" : "(SELECT MAX(db.COSTO_OTROS) FROM liquidacion_detalle_exp db WHERE db.ID_VALOR = val.ID_VALOR $condDetBase AND db.ESTADO_REGISTRO = 1)";
        $detBaseObs = $AGRUPAR_FOLIO ? "NULL" : "(SELECT MAX(db.OBSERVACION) FROM liquidacion_detalle_exp db WHERE db.ID_VALOR = val.ID_VALOR $condDetBase AND db.ESTADO_REGISTRO = 1)";
        $groupByParts = [];
        $orderByParts = [];
        if ($AGRUPAR_FOLIO) {
            $groupByParts[] = "exi.ID_EXIEXPORTACION";
            $groupByParts[] = "exi.ID_PRODUCTOR";
            $orderByParts[] = "exi.FOLIO_EXIEXPORTACION";
            $orderByParts[] = "prod.NOMBRE_PRODUCTOR";
        }
        if ($AGRUPAR_ESTANDAR) {
            $groupByParts[] = "exi.ID_ESTANDAR";
            $orderByParts[] = "est.NOMBRE_ESTANDAR";
        }
        if ($AGRUPAR_VARIEDAD) {
            $groupByParts[] = "exi.ID_VESPECIES";
            $orderByParts[] = "ves.NOMBRE_VESPECIES";
        }
        if ($AGRUPAR_CALIBRE) {
            $groupByParts[] = "exi.ID_TCALIBRE";
            $orderByParts[] = "cal.ORDEN";
            $orderByParts[] = "cal.NOMBRE_TCALIBRE";
        }
        $groupBy = implode(", ", $groupByParts);
        $orderBy = implode(", ", $orderByParts);

        $stmtLineas = $db->prepare("
            SELECT
                $selectProductor,
                $selectFolio,
                $selectFolioNumero,
                $selectEstandar,
                $selectVespecies,
                $selectCalibre,
                GROUP_CONCAT(DISTINCT exi.FOLIO_EXIEXPORTACION ORDER BY exi.FOLIO_EXIEXPORTACION SEPARATOR ', ') AS FOLIOS,
                $csgProductor,
                $nombreProductor,
                $nombreVespecies,
                $nombreEstandar,
                $nombreCalibre,
                SUM(IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)) AS ENVASES,
                SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0)) AS NETO,
                SUM(IFNULL(exi.KILOS_BRUTO_EXIEXPORTACION, 0)) AS BRUTO,
                IFNULL(COALESCE(MAX(invref.PRECIO_CAJA), NULLIF(MAX(dic.PRECIO_US_DICARGA), 0), MAX(pres.FOB_CAJA)), 0) AS FOB_REFERENCIA,
                IFNULL(COALESCE(
                    SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) * ($fobPorDetalle)) / NULLIF(SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0)), 0),
                    (SELECT MAX(d.FOB_REAL) FROM liquidacion_detalle_exp d WHERE d.ID_VALOR = val.ID_VALOR $condDet AND d.ESTADO_REGISTRO = 1),
                    $detBaseFob,
                    $fallbackFob
                ), 0) AS FOB_REAL,
                IFNULL(COALESCE(
                    (SELECT MAX(d.COSTO_COMISION) FROM liquidacion_detalle_exp d WHERE d.ID_VALOR = val.ID_VALOR $condDet AND d.ESTADO_REGISTRO = 1),
                    $detBaseComision,
                    $fallbackComision
                ), 0) AS COSTO_COMISION,
                IFNULL(COALESCE(
                    (SELECT MAX(d.COSTO_FLETE) FROM liquidacion_detalle_exp d WHERE d.ID_VALOR = val.ID_VALOR $condDet AND d.ESTADO_REGISTRO = 1),
                    $detBaseFlete,
                    $fallbackFlete
                ), 0) AS COSTO_FLETE,
                IFNULL(COALESCE(
                    (SELECT MAX(d.COSTO_OTROS) FROM liquidacion_detalle_exp d WHERE d.ID_VALOR = val.ID_VALOR $condDet AND d.ESTADO_REGISTRO = 1),
                    $detBaseOtros,
                    $fallbackOtros
                ), 0) AS COSTO_OTROS,
                IFNULL(COALESCE(
                    (SELECT MAX(d.OBSERVACION) FROM liquidacion_detalle_exp d WHERE d.ID_VALOR = val.ID_VALOR $condDet AND d.ESTADO_REGISTRO = 1),
                    $detBaseObs,
                    $fallbackObs
                ), '') AS OBSERVACION,
                IFNULL(
                    (SELECT MAX(d.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp d WHERE d.ID_VALOR = val.ID_VALOR $condDet AND d.ESTADO_REGISTRO = 1)
                , 0) AS FOB_ORIGEN_KG
            FROM fruta_exiexportacion exi
            LEFT JOIN fruta_despachoex dex ON dex.ID_DESPACHOEX = exi.ID_DESPACHOEX
                AND dex.ESTADO_REGISTRO = 1
            LEFT JOIN fruta_productor prod ON exi.ID_PRODUCTOR = prod.ID_PRODUCTOR
            LEFT JOIN fruta_vespecies ves ON exi.ID_VESPECIES = ves.ID_VESPECIES
            LEFT JOIN estandar_eexportacion est ON exi.ID_ESTANDAR = est.ID_ESTANDAR
            LEFT JOIN fruta_tcalibre cal ON exi.ID_TCALIBRE = cal.ID_TCALIBRE
            LEFT JOIN (
                SELECT
                    ID_ICARGA,
                    $dicSelectEstandar AS ID_ESTANDAR_REF,
                    $dicSelectVespecies AS ID_VESPECIES_REF,
                    $dicSelectCalibre AS ID_TCALIBRE_REF,
                    MAX(PRECIO_US_DICARGA) AS PRECIO_US_DICARGA
                FROM fruta_dicarga
                WHERE ESTADO_REGISTRO = 1
                GROUP BY ID_ICARGA, ID_ESTANDAR_REF, ID_VESPECIES_REF, ID_TCALIBRE_REF
            ) dic ON dic.ID_ICARGA = ?
                $joinDicEstandar
                $joinDicCalibre
                $joinDicVespecies
            LEFT JOIN (
                SELECT
                    inv.ID_ICARGA,
                    $invSelectEstandar AS ID_ESTANDAR_REF,
                    $invSelectVespecies AS ID_VESPECIES_REF,
                    $invSelectCalibre AS ID_TCALIBRE_REF,
                    MAX(idet.PRECIO_CAJA) AS PRECIO_CAJA
                FROM exportadora_invoice inv
                INNER JOIN exportadora_invoice_detalle idet ON idet.ID_INVOICE = inv.ID_INVOICE
                    AND idet.ESTADO_REGISTRO = 1
                WHERE inv.ESTADO_REGISTRO = 1
                AND inv.ESTADO_INVOICE = 'CONFIRMADA'
                GROUP BY inv.ID_ICARGA, ID_ESTANDAR_REF, ID_VESPECIES_REF, ID_TCALIBRE_REF
            ) invref ON invref.ID_ICARGA = ?
                $joinInvEstandar
                $joinInvCalibre
                $joinInvVespecies
            LEFT JOIN exportadora_presupuesto_fob pres ON pres.ID_EMPRESA = ?
                AND pres.ID_TEMPORADA = ?
                AND pres.ID_MERCADO = ?
                AND pres.ID_CONSIGNATARIO = ?
                AND pres.ID_ESTANDAR = exi.ID_ESTANDAR
                AND pres.ID_TCALIBRE = exi.ID_TCALIBRE
                AND pres.ESTADO_REGISTRO = 1
            LEFT JOIN liquidacion_valor val ON val.ID_VALOR = (
                SELECT lv.ID_VALOR
                FROM liquidacion_valor lv
                WHERE lv.ID_ICARGA = ?
                AND lv.ESTADO_REGISTRO = 1
                ORDER BY lv.ID_VALOR DESC
                LIMIT 1
            )
            WHERE exi.ESTADO_REGISTRO = 1
            AND exi.ID_EMPRESA = ?
            AND exi.ID_TEMPORADA = ?
            AND (
                exi.ID_ICARGA = ?
                OR dex.ID_ICARGA = ?
                OR exi.REFERENCIA = CONVERT(? USING utf8mb4) COLLATE utf8mb4_spanish_ci
                OR exi.REFERENCIA = CONVERT(? USING utf8mb4) COLLATE utf8mb4_spanish_ci
            )
            GROUP BY $groupBy
            ORDER BY $orderBy
        ");
        $stmtLineas->execute([
            $IDICARGA,
            $IDICARGA,
            $EMPRESAS,
            $TEMPORADAS,
            $CABECERA['ID_MERCADO'] ?? 0,
            $CABECERA['ID_CONSIGNATARIO'] ?? 0,
            $IDICARGA,
            $EMPRESAS,
            $TEMPORADAS,
            $IDICARGA,
            $IDICARGA,
            $IDICARGA,
            $CABECERA['NUMERO_ICARGA']
        ]);
        $LINEAS = $stmtLineas->fetchAll(PDO::FETCH_ASSOC);
    }
}

foreach ($LINEAS as $linea) {
    $grupo = $linea['NOMBRE_ESTANDAR'] . ' / ' . $linea['NOMBRE_PRODUCTOR'] . ' / ' . $linea['NOMBRE_VESPECIES'];
    if (!isset($GRUPOS[$grupo])) {
        $GRUPOS[$grupo] = [
            'envases' => 0,
            'neto' => 0,
            'lineas' => 0
        ];
    }
    $venta = (float)$linea['NETO'] * (float)$linea['FOB_REAL'];
    $GRUPOS[$grupo]['envases'] += (int)$linea['ENVASES'];
    $GRUPOS[$grupo]['neto'] += (float)$linea['NETO'];
    $GRUPOS[$grupo]['lineas']++;
    $TOTAL_ENVASES += (int)$linea['ENVASES'];
    $TOTAL_NETO += (float)$linea['NETO'];
    $TOTAL_VENTA += $venta;
    $TOTAL_COMISION += (float)$linea['COSTO_COMISION'];
    $TOTAL_FLETE += (float)$linea['COSTO_FLETE'];
    $TOTAL_OTROS += (float)$linea['COSTO_OTROS'];
}
$TOTAL_GASTOS_ITEMS = array_sum($GASTOS_VALOR);
foreach ($ITEMS_LIQUIDACION as $item) {
    $tipoGasto = $item['TIPO_GASTO'] ?? 'GASTO';
    $valorGastoItem = $GASTOS_VALOR[(int)$item['ID_TITEM']] ?? 0;
    if ($tipoGasto === 'COMISION' || stripos($item['NOMBRE_TITEM'], 'COMISI') !== false) {
        $TOTAL_COMISION_ITEMS += $valorGastoItem;
    }
    if ($CONDICION_FLETE_ACTUAL === 'PREPAID' && $tipoGasto === 'FLETE') {
        $TOTAL_GASTOS_ITEMS -= $valorGastoItem;
    }
}
$TOTAL_GASTOS_ITEMS = $TOTAL_GASTOS_ITEMS - $TOTAL_COMISION_ITEMS;
$TOTAL_VENTA_NETO = $TOTAL_VENTA - $TOTAL_COMISION_ITEMS - $TOTAL_GASTOS_ITEMS;
$DIFERENCIA_INVOICE_LIQUIDACION = $TOTAL_VENTA_NETO - $TOTAL_INVOICE_USD;
if ($TOTAL_INVOICE_USD > 0) {
    if ($DIFERENCIA_INVOICE_LIQUIDACION > $TOLERANCIA_NOTA) {
        $DOCUMENTO_SUGERIDO = 'Nota de Debito';
    } elseif ($DIFERENCIA_INVOICE_LIQUIDACION < ($TOLERANCIA_NOTA * -1)) {
        $DOCUMENTO_SUGERIDO = 'Nota de Credito';
    } else {
        $DOCUMENTO_SUGERIDO = 'Sin nota';
    }
}
$TNOTA_SUGERIDA = 0;
if ($DOCUMENTO_SUGERIDO === 'Nota de Debito') {
    $TNOTA_SUGERIDA = 1;
} elseif ($DOCUMENTO_SUGERIDO === 'Nota de Credito') {
    $TNOTA_SUGERIDA = 2;
}
$OBS_NOTADC_SUGERIDA = $TNOTA_SUGERIDA > 0
    ? 'Generada desde liquidacion ' . ($CABECERA['NREFERENCIA_ICARGA'] ?? '') . '. Diferencia neta vs invoice US$ ' . number_format(abs($DIFERENCIA_INVOICE_LIQUIDACION), 2, '.', '')
    : '';
$URL_NOTADC_SUGERIDA = $TNOTA_SUGERIDA > 0
    ? 'registroNotadc.php?ICARGAD=' . urlencode((string)$IDICARGA)
        . '&TNOTA=' . urlencode((string)$TNOTA_SUGERIDA)
        . '&OBSERVACIONINOTA=' . urlencode($OBS_NOTADC_SUGERIDA)
    : '#';

// ── Modo impresión / PDF ───────────────────────────────────────────────────
if (isset($_GET['IMPRIMIR']) && $IDICARGA > 0 && $CABECERA) {
    $arrEmp  = $EMPRESA_ADO->verEmpresa($EMPRESAS);
    $arrTemp = $TEMPORADA_ADO->verTemporada($TEMPORADAS);
    $nombreEmpresa   = $arrEmp  ? $arrEmp[0]['NOMBRE_EMPRESA']   : '';
    $nombreTemporada = $arrTemp ? $arrTemp[0]['NOMBRE_TEMPORADA'] : $TEMPORADAS;
    $refDoc    = $CABECERA['NREFERENCIA_ICARGA'];
    $titleDoc  = 'Liquidación - ' . $refDoc . ' - ' . $nombreTemporada;

    // Pre-compute line totals
    $lineasPrint = [];
    foreach ($LINEAS as $l) {
        $kgCaja      = (int)$l['ENVASES'] > 0 ? (float)$l['NETO'] / (int)$l['ENVASES'] : 0;
        $refKg       = $kgCaja > 0 ? (float)$l['FOB_REFERENCIA'] / $kgCaja : 0;
        $fobVentaKg  = (float)$l['FOB_REAL'];
        $fobVentaCja = $kgCaja > 0 ? $fobVentaKg * $kgCaja : 0;
        $venta       = (float)$l['NETO'] * $fobVentaKg;
        $comLin      = $TOTAL_VENTA > 0 ? $TOTAL_COMISION_ITEMS * ($venta / $TOTAL_VENTA) : 0;
        $gasLin      = $TOTAL_NETO  > 0 ? $TOTAL_GASTOS_ITEMS  * ((float)$l['NETO'] / $TOTAL_NETO) : 0;
        $retorno     = $venta - $comLin - $gasLin;
        $fobFinalKg  = (float)$l['NETO'] > 0 ? $retorno / (float)$l['NETO'] : 0;
        $fobFinalCja = $fobFinalKg * $kgCaja;
        $l['_venta']      = $venta;
        $l['_refKg']      = $refKg;
        $l['_fobVentaCja'] = $fobVentaCja;
        $l['_fobVentaKg'] = $fobVentaKg;
        $l['_retorno']    = $retorno;
        $l['_fobFinalKg'] = $fobFinalKg;
        $l['_fobFinalCja']= $fobFinalCja;
        $lineasPrint[]    = $l;
    }
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?php echo h($titleDoc); ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }
    .print-page { max-width: 1100px; margin: 0 auto; padding: 20px 24px 40px; }
    /* ── Header ── */
    .doc-header { display: flex; justify-content: space-between; align-items: flex-end;
                  border-bottom: 3px solid #0a3a6a; padding-bottom: 12px; margin-bottom: 16px; }
    .doc-header-left h1 { font-size: 18px; font-weight: 900; color: #0a3a6a; text-transform: uppercase; letter-spacing: .04em; }
    .doc-header-left p  { font-size: 11px; color: #555; margin-top: 2px; }
    .doc-header-right   { text-align: right; font-size: 10px; color: #666; line-height: 1.6; }
    .doc-header-right strong { font-size: 13px; color: #0a3a6a; display: block; }
    /* ── Info grid ── */
    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 14px; }
    .info-cell { background: #f4f7fb; border-left: 3px solid #0a3a6a; padding: 7px 10px; border-radius: 0 4px 4px 0; }
    .info-cell .lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #667085; margin-bottom: 2px; }
    .info-cell .val { font-size: 12px; font-weight: 700; color: #10233f; }
    /* ── Summary bar ── */
    .summary-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 18px; }
    .sum-cell { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; text-align: center; }
    .sum-cell .sum-lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #667085; margin-bottom: 4px; }
    .sum-cell .sum-val { font-size: 15px; font-weight: 900; color: #10233f; }
    .sum-cell.highlight { background: #0a3a6a; border-color: #0a3a6a; }
    .sum-cell.highlight .sum-lbl { color: rgba(255,255,255,.7); }
    .sum-cell.highlight .sum-val { color: #fff; }
    /* ── Section title ── */
    .section-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .07em;
                     color: #0a3a6a; border-bottom: 1px solid #c9d5e0; padding-bottom: 5px; margin-bottom: 8px; margin-top: 16px; }
    /* ── Table ── */
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th { background: #0a3a6a; color: #fff; font-size: 9px; font-weight: 700; text-transform: uppercase;
         letter-spacing: .04em; padding: 6px 7px; text-align: center; border: 1px solid #08305a; }
    td { padding: 5px 7px; border: 1px solid #dde3ec; vertical-align: middle; }
    tr:nth-child(even) td { background: #f9fbfd; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .total-row td { background: #e8f1fb !important; font-weight: 800; color: #0a3a6a; border-top: 2px solid #0a3a6a; }
    /* ── Gastos table ── */
    .gastos-table { width: 50%; font-size: 10.5px; margin-top: 4px; }
    .gastos-table td { padding: 4px 8px; border-bottom: 1px solid #eee; }
    .gastos-table .gasto-lbl { color: #555; }
    .gastos-table .gasto-val { text-align: right; font-weight: 600; color: #10233f; }
    /* ── Footer ── */
    .doc-footer { margin-top: 30px; border-top: 1px solid #c9d5e0; padding-top: 10px;
                  display: flex; justify-content: space-between; color: #999; font-size: 9px; }
    /* ── Print ── */
    @media print {
        body { font-size: 10px; }
        .no-print { display: none !important; }
        .print-page { padding: 0; }
        @page { size: A4 landscape; margin: 15mm 12mm; }
    }
</style>
</head>
<body onload="window.print()">
<div class="print-page">

    <!-- Header -->
    <div class="doc-header">
        <div class="doc-header-left">
            <h1>Liquidación de Exportación</h1>
            <p><?php echo h($nombreEmpresa); ?> &nbsp;·&nbsp; Temporada <?php echo h($nombreTemporada); ?></p>
        </div>
        <div class="doc-header-right">
            <strong><?php echo h($refDoc); ?></strong>
            <?php echo h($CABECERA['NCONTENEDOR_ICARGA']); ?><br>
            Instructivo <?php echo h($CABECERA['NUMERO_ICARGA']); ?><br>
            <?php echo h($CABECERA['FECHA_ICARGA']); ?>
        </div>
    </div>

    <!-- Info grid -->
    <div class="info-grid">
        <div class="info-cell"><div class="lbl">Referencia</div><div class="val"><?php echo h($CABECERA['NREFERENCIA_ICARGA']); ?></div></div>
        <div class="info-cell"><div class="lbl">Contenedor</div><div class="val"><?php echo h($CABECERA['NCONTENEDOR_ICARGA']); ?></div></div>
        <div class="info-cell"><div class="lbl">Instructivo</div><div class="val"><?php echo h($CABECERA['NUMERO_ICARGA']); ?></div></div>
        <div class="info-cell"><div class="lbl">Fecha</div><div class="val"><?php echo h($CABECERA['FECHA_ICARGA']); ?></div></div>
        <div class="info-cell"><div class="lbl">Total cajas</div><div class="val"><?php echo number_format((int)$TOTAL_ENVASES_EXPORTADO, 0, ',', '.'); ?></div></div>
        <div class="info-cell"><div class="lbl">Kg neto</div><div class="val"><?php echo numero($TOTAL_NETO_EXPORTADO); ?></div></div>
        <div class="info-cell"><div class="lbl">Moneda</div><div class="val"><?php echo h($MONEDA_ACTUAL); ?></div></div>
        <div class="info-cell"><div class="lbl">Flete</div><div class="val"><?php echo h($CONDICION_FLETE_ACTUAL); ?></div></div>
    </div>

    <!-- Summary bar -->
    <div class="summary-bar">
        <div class="sum-cell">
            <div class="sum-lbl">Total venta US$</div>
            <div class="sum-val"><?php echo numero($TOTAL_VENTA); ?></div>
        </div>
        <div class="sum-cell">
            <div class="sum-lbl">Comisión US$</div>
            <div class="sum-val"><?php echo numero($TOTAL_COMISION_ITEMS); ?></div>
        </div>
        <div class="sum-cell">
            <div class="sum-lbl">Gastos US$</div>
            <div class="sum-val"><?php echo numero($TOTAL_GASTOS_ITEMS); ?></div>
        </div>
        <div class="sum-cell highlight">
            <div class="sum-lbl">Retorno neto US$</div>
            <div class="sum-val"><?php echo numero($TOTAL_VENTA_NETO); ?></div>
        </div>
    </div>

    <!-- Line items -->
    <div class="section-title">Detalle de líneas</div>
    <table>
        <thead>
            <tr>
                <?php if ($AGRUPAR_FOLIO)    echo '<th>Folio</th>'; ?>
                <?php if ($AGRUPAR_ESTANDAR) echo '<th>Estándar</th>'; ?>
                <?php if ($AGRUPAR_VARIEDAD) echo '<th>Variedad</th>'; ?>
                <?php if ($AGRUPAR_CALIBRE)  echo '<th>Calibre</th>'; ?>
                <?php if ($mostrarProductor) echo '<th>Productor</th>'; ?>
                <th>Cajas</th>
                <th>Kg Neto</th>
                <th>Ref. Caja</th>
                <th>Ref. Kg</th>
                <th>FOB Venta Caja</th>
                <th>FOB Venta Kg</th>
                <th>Venta US$</th>
                <th>FOB Final Caja</th>
                <th>FOB Final Kg</th>
                <th>Obs.</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lineasPrint as $l): ?>
            <tr>
                <?php if ($AGRUPAR_FOLIO)    echo '<td class="text-center">' . h($l['FOLIO_LINEA']) . '</td>'; ?>
                <?php if ($AGRUPAR_ESTANDAR) echo '<td>' . h($l['NOMBRE_ESTANDAR']) . '</td>'; ?>
                <?php if ($AGRUPAR_VARIEDAD) echo '<td>' . h($l['NOMBRE_VESPECIES']) . '</td>'; ?>
                <?php if ($AGRUPAR_CALIBRE)  echo '<td class="text-center">' . h($l['NOMBRE_TCALIBRE']) . '</td>'; ?>
                <?php if ($mostrarProductor) echo '<td>' . h($l['CSG_PRODUCTOR'] . ' - ' . $l['NOMBRE_PRODUCTOR']) . '</td>'; ?>
                <td class="text-right"><?php echo number_format((int)$l['ENVASES'], 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo numero($l['NETO']); ?></td>
                <td class="text-right"><?php echo numero($l['FOB_REFERENCIA']); ?></td>
                <td class="text-right"><?php echo numero($l['_refKg']); ?></td>
                <td class="text-right"><?php echo numero($l['_fobVentaCja']); ?></td>
                <td class="text-right"><?php echo numero($l['_fobVentaKg']); ?></td>
                <td class="text-right"><?php echo numero($l['_venta']); ?></td>
                <td class="text-right"><?php echo numero($l['_fobFinalCja']); ?></td>
                <td class="text-right"><?php echo numero($l['_fobFinalKg']); ?></td>
                <td><?php echo h($l['OBSERVACION']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <?php
                $colsGrp = (int)$AGRUPAR_FOLIO + (int)$AGRUPAR_ESTANDAR + (int)$AGRUPAR_VARIEDAD + (int)$AGRUPAR_CALIBRE + (int)$mostrarProductor;
                echo '<td colspan="' . $colsGrp . '" class="text-right">TOTALES</td>';
                ?>
                <td class="text-right"><?php echo number_format((int)$TOTAL_ENVASES, 0, ',', '.'); ?></td>
                <td class="text-right"><?php echo numero($TOTAL_NETO); ?></td>
                <td class="text-right">—</td>
                <td class="text-right">—</td>
                <td class="text-right">—</td>
                <td class="text-right">—</td>
                <td class="text-right"><?php echo numero($TOTAL_VENTA); ?></td>
                <td class="text-right">—</td>
                <td class="text-right">—</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Gastos items -->
    <?php
    $gastosConValor = [];
    foreach ($ITEMS_LIQUIDACION as $item) {
        $val = $GASTOS_VALOR[(int)$item['ID_TITEM']] ?? 0;
        if ($MONEDA_ACTUAL === 'EUR' && $TIPO_CAMBIO_ACTUAL > 0) $val = $val / $TIPO_CAMBIO_ACTUAL;
        if (abs((float)$val) > 0) $gastosConValor[] = ['nombre' => $item['NOMBRE_TITEM'], 'val' => $val];
    }
    if (count($gastosConValor) > 0): ?>
    <div class="section-title">Items de gastos</div>
    <table class="gastos-table">
        <?php foreach ($gastosConValor as $g): ?>
        <tr>
            <td class="gasto-lbl"><?php echo h($g['nombre']); ?></td>
            <td class="gasto-val"><?php echo numero($g['val']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <!-- Footer -->
    <div class="doc-footer">
        <span>Generado: <?php echo date('d/m/Y H:i'); ?></span>
        <span><?php echo h($nombreEmpresa); ?> · Temporada <?php echo h($nombreTemporada); ?></span>
        <span class="no-print">
            <button onclick="window.print()" style="cursor:pointer;padding:4px 12px;background:#0a3a6a;color:#fff;border:none;border-radius:4px;">Imprimir</button>
            <button onclick="window.close()" style="cursor:pointer;padding:4px 12px;background:#e2e8f0;color:#333;border:none;border-radius:4px;margin-left:6px;">Cerrar</button>
        </span>
    </div>

</div>
</body>
</html>
<?php exit; } // end IMPRIMIR ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Liquidacion Exportacion</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
        .liqui-panel {
            border: 1px solid #d8d8d8;
            background: #f7f7f7;
            padding: 12px;
            min-height: 100%;
        }
        .liqui-panel-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1f2d3d;
        }
        .liqui-tabs {
            border-bottom: 1px solid #d8d8d8;
            margin-bottom: 12px;
        }
        .liqui-tabs .nav-link {
            padding: 8px 14px;
            border-radius: 0;
        }
        .liqui-tree {
            background: #fff;
            border: 1px solid #d8d8d8;
            height: 245px;
            overflow: auto;
            padding: 8px;
        }
        .liqui-tree-item {
            border-bottom: 1px solid #efefef;
            padding: 6px 4px;
            font-size: 12px;
        }
        .liqui-tree-item:last-child {
            border-bottom: 0;
        }
        .liqui-total {
            background: #fffde8;
            font-weight: 700;
            text-align: right;
        }
        .liqui-table input {
            min-width: 92px;
        }
        .liqui-table .obs-input {
            min-width: 160px;
        }
        .liqui-summary {
            border: 1px solid #d8d8d8;
            padding: 10px;
            background: #f7f7f7;
            margin-top: 10px;
        }
        @media print {
            .main-header, .main-sidebar, .content-header,
            .box-header .btn, .box-header a.btn,
            .liq-export-bar, footer { display: none !important; }
            .content-wrapper { margin-left: 0 !important; padding: 0 !important; }
            .box { border: none !important; box-shadow: none !important; }
            .liqui-table input { border: none !important; background: transparent !important; }
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
                        <h3 class="page-title">Liquidacion Exportacion</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item">Liquidacion</li>
                                    <li class="breadcrumb-item active">Detalle Exportado</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>
            <section class="content">
                <?php if ($mensaje) { ?>
                    <div class="alert alert-success"><?php echo h($mensaje); ?></div>
                <?php } ?>
                <?php if ($LIQUIDACION_BLOQUEADA) { ?>
                    <div class="alert alert-info">Liquidacion en estado liquidada. La informacion queda bloqueada para edicion.</div>
                <?php } ?>
                <?php if ($IDICARGA === 0) { ?>
                <div class="box">
                    <div class="box-header with-border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                            <h4 class="box-title">Liquidaciones de exportación</h4>
                            <div class="d-flex align-items-center" style="gap:6px;flex-wrap:wrap;">
                                <span class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Filtrar:</span>
                                <button class="btn btn-xs btn-default liq-filter active" data-estado="">Todos</button>
                                <button class="btn btn-xs btn-success liq-filter" data-estado="Liquidado">Liquidado</button>
                                <button class="btn btn-xs btn-info liq-filter" data-estado="En proceso">En proceso</button>
                                <button class="btn btn-xs btn-warning liq-filter" data-estado="Estimado">Estimado</button>
                                <button class="btn btn-xs btn-default liq-filter" data-estado="Pendiente">Pendiente</button>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-hover" id="liqTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Referencia</th>
                                    <th>Contenedor</th>
                                    <th>Fecha</th>
                                    <th>Folios</th>
                                    <th>Estado</th>
                                    <th class="no-export">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $liqIdx = 0; foreach ($INSTRUCTIVOS as $instr) {
                                $liqIdx++;
                                if ((int)$instr['TIENE_VALORES_LIQUIDACION'] === 1) {
                                    $estadoLiq = 'Liquidado';
                                    $badgeLiq  = 'badge-success';
                                } elseif (!empty($instr['ID_VALOR_LIQUIDACION'])) {
                                    $estadoLiq = 'En proceso';
                                    $badgeLiq  = 'badge-info';
                                } elseif ((int)$instr['TIENE_PRECIO_REFERENCIA'] === 1) {
                                    $estadoLiq = 'Estimado';
                                    $badgeLiq  = 'badge-warning';
                                } else {
                                    $estadoLiq = 'Pendiente';
                                    $badgeLiq  = 'badge-secondary';
                                }
                                $urlAbrir = 'registroLiquidacionExp.php?ICARGA=' . (int)$instr['ID_ICARGA'] . '&AGRUPAR_ESTANDAR=1';
                            ?>
                                <tr>
                                    <td><?php echo $liqIdx; ?></td>
                                    <td><strong><?php echo h($instr['NREFERENCIA_ICARGA']); ?></strong></td>
                                    <td><?php echo h($instr['NCONTENEDOR_ICARGA']); ?></td>
                                    <td><?php echo h($instr['FECHA_ICARGA']); ?></td>
                                    <td class="text-center"><?php echo (int)$instr['DETALLE_EXPORTADO']; ?></td>
                                    <td class="text-center"><?php echo $estadoLiq; ?></td>
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
                <script>
                $(document).ready(function(){
                    if (!$.fn.DataTable || !$('#liqTable').length) return;

                    var liqDT = $('#liqTable').DataTable({
                        pageLength: 25,
                        order: [[3, 'desc']],
                        columnDefs: [
                            { orderable: false, targets: [0, 6] },
                            { searchable: false, targets: [0, 4, 6] },
                            {
                                targets: 5,
                                render: function(data, type) {
                                    if (type !== 'display') return data;
                                    var map = {
                                        'Liquidado':  'badge-success',
                                        'En proceso': 'badge-info',
                                        'Estimado':   'badge-warning',
                                        'Pendiente':  'badge-secondary'
                                    };
                                    return '<span class="badge ' + (map[data] || 'badge-default') + '">' + data + '</span>';
                                }
                            }
                        ],
                        language: {
                            search:       'Buscar:',
                            lengthMenu:   'Mostrar _MENU_ registros',
                            info:         'Mostrando _START_ a _END_ de _TOTAL_ registros',
                            infoEmpty:    'Mostrando 0 a 0 de 0 registros',
                            infoFiltered: '(filtrado de _MAX_ registros totales)',
                            zeroRecords:  'No se encontraron resultados',
                            emptyTable:   'No hay datos disponibles',
                            paginate: {
                                first: 'Primero', last: 'Último',
                                next: 'Siguiente', previous: 'Anterior'
                            }
                        }
                    });

                    // State filter buttons
                    $('.liq-filter').on('click', function(){
                        $('.liq-filter').removeClass('active');
                        $(this).addClass('active');
                        liqDT.column(5).search($(this).data('estado')).draw();
                    });
                });
                </script>
                <?php } ?>

                <?php if ($CABECERA) {
                    // Build grouping toggle URLs (click to toggle each option)
                    $grpActive = [];
                    if ($AGRUPAR_FOLIO)    $grpActive[] = 'AGRUPAR_FOLIO';
                    if ($AGRUPAR_ESTANDAR) $grpActive[] = 'AGRUPAR_ESTANDAR';
                    if ($AGRUPAR_VARIEDAD) $grpActive[] = 'AGRUPAR_VARIEDAD';
                    if ($AGRUPAR_CALIBRE)  $grpActive[] = 'AGRUPAR_CALIBRE';
                    $grpOpts = [
                        'AGRUPAR_FOLIO'    => 'Folio',
                        'AGRUPAR_ESTANDAR' => 'Estándar',
                        'AGRUPAR_VARIEDAD' => 'Variedad',
                        'AGRUPAR_CALIBRE'  => 'Calibre',
                    ];
                    $grpToggleUrls = [];
                    foreach ($grpOpts as $gk => $glbl) {
                        $next = [];
                        if (in_array($gk, $grpActive)) {
                            foreach ($grpActive as $g) { if ($g !== $gk) $next[] = $g; }
                        } else {
                            $next = array_merge($grpActive, [$gk]);
                        }
                        if (empty($next)) $next = ['AGRUPAR_ESTANDAR'];
                        $qs = 'ICARGA=' . (int)$IDICARGA;
                        foreach ($next as $g) $qs .= '&' . $g . '=1';
                        $grpToggleUrls[$gk] = 'registroLiquidacionExp.php?' . $qs;
                    }
                ?>
                    <form method="post">
                        <input type="hidden" name="ICARGA" value="<?php echo (int)$IDICARGA; ?>">
                        <?php if ($AGRUPAR_FOLIO) { ?><input type="hidden" name="AGRUPAR_FOLIO" value="1"><?php } ?>
                        <?php if ($AGRUPAR_ESTANDAR) { ?><input type="hidden" name="AGRUPAR_ESTANDAR" value="1"><?php } ?>
                        <?php if ($AGRUPAR_VARIEDAD) { ?><input type="hidden" name="AGRUPAR_VARIEDAD" value="1"><?php } ?>
                        <?php if ($AGRUPAR_CALIBRE) { ?><input type="hidden" name="AGRUPAR_CALIBRE" value="1"><?php } ?>
                        <div class="box">
                            <div class="box-header with-border bg-info">
                                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        <a href="registroLiquidacionExp.php" class="btn btn-sm btn-default" title="Volver al listado" style="color:#333;">
                                            <i class="ti-angle-left"></i> Listado
                                        </a>
                                        <h4 class="box-title" style="margin:0;">
                                            <?php echo h($CABECERA['NREFERENCIA_ICARGA'] . ' / ' . $CABECERA['NCONTENEDOR_ICARGA']); ?>
                                        </h4>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap:6px;flex-wrap:wrap;">
                                        <a href="exportarLiquidacionExp.php?ICARGA=<?php echo (int)$IDICARGA; ?>&EXPORTAR=1"
                                           class="btn btn-xs btn-success" title="Descargar Excel de esta liquidación">
                                            <i class="ti-export"></i> Excel
                                        </a>
                                        <?php
                                        $pdfUrl = 'registroLiquidacionExp.php?ICARGA=' . (int)$IDICARGA . '&IMPRIMIR=1';
                                        foreach ($grpActive as $gk) $pdfUrl .= '&' . $gk . '=1';
                                        ?>
                                        <a href="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"
                                           class="btn btn-xs btn-warning" title="Ver e imprimir / Guardar como PDF">
                                            <i class="ti-printer"></i> PDF
                                        </a>
                                        <span style="width:1px;height:18px;background:rgba(255,255,255,.3);display:inline-block;margin:0 4px;"></span>
                                        <span style="font-size:11px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.04em;">Agrupar:</span>
                                        <?php foreach ($grpOpts as $gk => $glbl):
                                            $isOn = in_array($gk, $grpActive); ?>
                                        <a href="<?php echo htmlspecialchars($grpToggleUrls[$gk], ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-xs <?php echo $isOn ? 'btn-primary' : 'btn-default'; ?>"
                                           style="font-weight:<?php echo $isOn ? '700' : '400'; ?>;<?php echo $isOn ? '' : 'opacity:.7;'; ?>">
                                            <?php echo $glbl; ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="box-body">
                                <?php if (count($LINEAS) === 0) { ?>
                                    <div class="alert alert-warning">
                                        No hay detalle exportado asociado a esta referencia/contenedor.
                                    </div>
                                <?php } ?>
                                <div class="row mb-15">
                                    <div class="col-md-3">
                                        <label>Tipo de nave</label>
                                        <input type="text" class="form-control" value="MARITIMO" readonly>
                                    </div>
                                    <div class="col-md-5">
                                        <label>Nave / Referencia</label>
                                        <input type="text" class="form-control" value="<?php echo h($CABECERA['NUMERO_ICARGA'] . ' - ' . $CABECERA['NREFERENCIA_ICARGA']); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Contenedor</label>
                                        <input type="text" class="form-control" value="<?php echo h($CABECERA['NCONTENEDOR_ICARGA']); ?>" readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <label>Total cajas</label>
                                        <input type="text" class="form-control liqui-total" value="<?php echo (int)$TOTAL_ENVASES_EXPORTADO; ?>" readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <label>Kg neto</label>
                                        <input type="text" class="form-control liqui-total" value="<?php echo numero($TOTAL_NETO_EXPORTADO); ?>" readonly>
                                    </div>
                                </div>
                                <?php if (abs($TOTAL_NETO - $TOTAL_NETO_EXPORTADO) > 0.01 || (int)$TOTAL_ENVASES !== (int)$TOTAL_ENVASES_EXPORTADO) { ?>
                                    <div class="alert alert-warning">
                                        La agrupacion no coincide con el total exportado:
                                        tabla <?php echo (int)$TOTAL_ENVASES; ?> cajas / <?php echo numero($TOTAL_NETO); ?> kg,
                                        exportado <?php echo (int)$TOTAL_ENVASES_EXPORTADO; ?> cajas / <?php echo numero($TOTAL_NETO_EXPORTADO); ?> kg.
                                    </div>
                                <?php } ?>

                                <div class="row mb-15">
                                    <div class="col-lg-7">
                                        <div class="liqui-panel">
                                            <div class="liqui-panel-title">[+] Estandar / Productor / Variedad</div>
                                            <div class="liqui-tree">
                                                <?php foreach ($GRUPOS as $nombreGrupo => $grupo) { ?>
                                                    <div class="liqui-tree-item">
                                                        <strong><?php echo h($nombreGrupo); ?></strong><br>
                                                        <?php echo (int)$grupo['lineas']; ?> lineas -
                                                        <?php echo (int)$grupo['envases']; ?> cajas -
                                                        <?php echo numero($grupo['neto']); ?> kg neto
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="liqui-panel">
                                            <div class="liqui-panel-title">Precios - US$</div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Total venta</label>
                                                    <input type="text" class="form-control liqui-total" id="totalVentaTop" value="<?php echo numero($TOTAL_VENTA); ?>" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Venta neta final</label>
                                                    <input type="text" class="form-control liqui-total" id="totalVentaNetoTop" value="<?php echo numero($TOTAL_VENTA_NETO); ?>" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Cajas sin precio</label>
                                                    <input type="text" class="form-control liqui-total" value="<?php
                                                        $sinPrecio = 0;
                                                        foreach ($LINEAS as $linea) {
                                                            if ((float)$linea['FOB_REAL'] <= 0) {
                                                                $sinPrecio += (int)$linea['ENVASES'];
                                                            }
                                                        }
                                                        echo (int)$sinPrecio;
                                                    ?>" readonly>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="liqui-panel-title">Valores Nave</div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <label>Moneda liquidacion</label>
                                                    <select class="form-control" id="selectMoneda" name="MONEDA_ORIGEN" <?php echo $LIQUIDACION_BLOQUEADA ? 'disabled' : ''; ?>>
                                                        <option value="USD" <?php echo $MONEDA_ACTUAL === 'USD' ? 'selected' : ''; ?>>US$ (dólar)</option>
                                                        <option value="EUR" <?php echo $MONEDA_ACTUAL === 'EUR' ? 'selected' : ''; ?>>EUR (euro)</option>
                                                    </select>
                                                    <div id="panelEurUsd" style="<?php echo $MONEDA_ACTUAL === 'EUR' ? '' : 'display:none'; ?>margin-top:4px">
                                                        <label style="font-size:11px;margin-bottom:2px">Tipo EUR → USD</label>
                                                        <input type="number" step="0.000001" class="form-control form-control-sm"
                                                               id="eurUsdRate" name="TIPO_CAMBIO_USD"
                                                       value="<?php echo h(number_format($MONEDA_ACTUAL === 'EUR' ? $TIPO_CAMBIO_ACTUAL : $EUR_USD_DEFAULT, 6, '.', '')); ?>" <?php echo $LIQUIDACION_BLOQUEADA ? 'readonly' : ''; ?>>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Flete instructivo</label>
                                                    <select class="form-control" id="condicionFlete" disabled>
                                                        <option value="COLLECT" <?php echo $CONDICION_FLETE_ACTUAL === 'COLLECT' ? 'selected' : ''; ?>>Full Collect</option>
                                                        <option value="PREPAID" <?php echo $CONDICION_FLETE_ACTUAL === 'PREPAID' ? 'selected' : ''; ?>>Full Prepaid</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Comision</label>
                                                    <input type="text" class="form-control liqui-total" id="totalComisionTop" value="<?php echo numero($TOTAL_COMISION_ITEMS); ?>" readonly>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Gastos</label>
                                                    <input type="text" class="form-control liqui-total" id="totalGastosTop" value="<?php echo numero($TOTAL_GASTOS_ITEMS); ?>" readonly>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="liqui-panel-title">Items de Gastos</div>
                                            <?php foreach ($ITEMS_LIQUIDACION as $item) {
                                                $valorItem = $GASTOS_VALOR[(int)$item['ID_TITEM']] ?? 0;
                                                if ($MONEDA_ACTUAL === 'EUR' && $TIPO_CAMBIO_ACTUAL > 0) {
                                                    $valorItem = $valorItem / $TIPO_CAMBIO_ACTUAL;
                                                }
                                            ?>
                                                <div class="form-group row align-items-center mb-5">
                                                    <label class="col-md-7 col-form-label"><?php echo h($item['NOMBRE_TITEM']); ?></label>
                                                    <div class="col-md-5">
                                                        <input type="number" step="0.0001" class="form-control js-gasto-item" data-tipo-gasto="<?php echo h($item['TIPO_GASTO'] ?? 'GASTO'); ?>" name="gasto[<?php echo (int)$item['ID_TITEM']; ?>]" value="<?php echo h($valorItem); ?>" <?php echo $LIQUIDACION_BLOQUEADA ? 'readonly' : ''; ?>>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <?php if (count($ITEMS_LIQUIDACION) === 0) { ?>
                                                <div class="alert alert-warning mb-0">No hay items de liquidacion creados.</div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="liqui-panel mb-15">
                                    <div class="liqui-panel-title">Ingreso de Precios Masivo</div>
                                    <div class="row align-items-end">
                                        <div class="col-md-4">
                                            <label>FOB Venta Caja <span id="labelMonedaMasivo"><?php echo $MONEDA_ACTUAL === 'EUR' ? 'EUR' : 'US$'; ?></span></label>
                                            <input type="number" step="0.0001" class="form-control js-4dec" id="fobMasivoCaja" value="" <?php echo $LIQUIDACION_BLOQUEADA ? 'readonly' : ''; ?>>
                                        </div>
                                        <div class="col-md-8">
                                            <button type="button" class="btn btn-primary" id="aplicarFobMasivo" <?php echo $LIQUIDACION_BLOQUEADA ? 'disabled' : ''; ?>>
                                                <i class="ti-check"></i> Aplicar a todas las lineas
                                            </button>
                                            <button type="button" class="btn btn-secondary" id="copiarReferenciaCaja" <?php echo $LIQUIDACION_BLOQUEADA ? 'disabled' : ''; ?>>
                                                <i class="ti-layers"></i> Copiar referencia caja
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="listar" class="table table-bordered table-hover liqui-table" style="width:100%">
                                        <thead>
                                            <tr class="text-center">
                                                <?php if ($AGRUPAR_FOLIO) { ?><th>Folio</th><?php } ?>
                                                <?php if ($AGRUPAR_ESTANDAR) { ?><th>Estandar</th><?php } ?>
                                                <?php if ($AGRUPAR_VARIEDAD) { ?><th>Variedad</th><?php } ?>
                                                <?php if ($AGRUPAR_CALIBRE) { ?><th>Calibre</th><?php } ?>
                                                <?php if ($mostrarProductor) { ?><th>Productor</th><?php } ?>
                                                <th style="display:none"></th>
                                                <th>Envases</th>
                                                <th>Kg Neto</th>
                                                <th>Ref. Caja US$</th>
                                                <th>Ref. Kg US$</th>
                                                <th class="js-th-fob-caja">FOB Venta Caja <?php echo $MONEDA_ACTUAL === 'EUR' ? 'EUR' : 'US$'; ?></th>
                                                <th class="js-th-fob-kg">FOB Venta Kg <?php echo $MONEDA_ACTUAL === 'EUR' ? 'EUR' : 'US$'; ?></th>
                                                <th>Dif. Kg</th>
                                                <th>Venta US$</th>
                                                <th>FOB Final Caja</th>
                                                <th>FOB Final Kg</th>
                                                <th>Obs.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($LINEAS as $idx => $l) {
                                            $kgCaja = (int)$l['ENVASES'] > 0 ? (float)$l['NETO'] / (int)$l['ENVASES'] : 0;
                                            $referenciaKg = $kgCaja > 0 ? (float)$l['FOB_REFERENCIA'] / $kgCaja : 0;
                                            $fobOrigenKg = (float)($l['FOB_ORIGEN_KG'] ?? 0);
                                            if ($MONEDA_ACTUAL === 'EUR' && $fobOrigenKg > 0) {
                                                $fobVentaCaja  = $kgCaja > 0 ? $fobOrigenKg * $kgCaja : 0;
                                                $fobKgDisplay  = $fobOrigenKg;
                                            } else {
                                                $fobVentaCaja  = $kgCaja > 0 ? (float)$l['FOB_REAL'] * $kgCaja : 0;
                                                $fobKgDisplay  = (float)$l['FOB_REAL'];
                                            }
                                            $venta = (float)$l['NETO'] * (float)$l['FOB_REAL'];
                                            $diferencia = (float)$l['FOB_REAL'] - $referenciaKg;
                                            $comisionLinea = $TOTAL_VENTA > 0 ? $TOTAL_COMISION_ITEMS * ($venta / $TOTAL_VENTA) : 0;
                                            $gastoLinea = $TOTAL_NETO > 0 ? $TOTAL_GASTOS_ITEMS * ((float)$l['NETO'] / $TOTAL_NETO) : 0;
                                            $retornoFinal = $venta - $comisionLinea - $gastoLinea;
                                            $fobFinalKg = (float)$l['NETO'] > 0 ? $retornoFinal / (float)$l['NETO'] : 0;
                                            $fobFinalCaja = $fobFinalKg * $kgCaja;
                                        ?>
                                            <tr class="js-linea" data-neto="<?php echo h($l['NETO']); ?>" data-kg-caja="<?php echo h($kgCaja); ?>" data-fob-ref-caja="<?php echo h($l['FOB_REFERENCIA']); ?>" data-fob-ref-kg="<?php echo h($referenciaKg); ?>" data-fob-origen-kg="<?php echo h($fobOrigenKg); ?>">
                                                <?php if ($AGRUPAR_FOLIO) { ?><td><?php echo h($l['FOLIO_LINEA']); ?></td><?php } ?>
                                                <?php if ($AGRUPAR_ESTANDAR) { ?><td><?php echo h($l['NOMBRE_ESTANDAR']); ?></td><?php } ?>
                                                <?php if ($AGRUPAR_VARIEDAD) { ?><td><?php echo h($l['NOMBRE_VESPECIES']); ?></td><?php } ?>
                                                <?php if ($AGRUPAR_CALIBRE) { ?><td><?php echo h($l['NOMBRE_TCALIBRE']); ?></td><?php } ?>
                                                <?php if ($mostrarProductor) { ?>
                                                    <td><?php echo h($l['CSG_PRODUCTOR'] . ' - ' . $l['NOMBRE_PRODUCTOR']); ?></td>
                                                <?php } ?>
                                                <td style="display:none">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][exiexportacion]" value="<?php echo (int)$l['ID_EXIEXPORTACION']; ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][folio]" value="<?php echo (int)$l['FOLIO_LINEA']; ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][productor]" value="<?php echo (int)$l['ID_PRODUCTOR']; ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][vespecies]" value="<?php echo (int)$l['ID_VESPECIES']; ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][estandar]" value="<?php echo (int)$l['ID_ESTANDAR']; ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][calibre]" value="<?php echo (int)$l['ID_TCALIBRE']; ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][envases]" value="<?php echo (int)$l['ENVASES']; ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][neto]" value="<?php echo h($l['NETO']); ?>">
                                                    <input type="hidden" name="linea[<?php echo $idx; ?>][bruto]" value="<?php echo h($l['BRUTO']); ?>">
                                                </td>
                                                <td class="text-right"><?php echo (int)$l['ENVASES']; ?></td>
                                                <td class="text-right"><?php echo numero($l['NETO']); ?></td>
                                                <td class="text-right"><?php echo numero($l['FOB_REFERENCIA']); ?></td>
                                                <td class="text-right"><?php echo numero($referenciaKg); ?></td>
                                                <td><input type="number" step="0.0001" class="form-control js-fob-caja js-4dec" name="linea[<?php echo $idx; ?>][fob_caja]" value="<?php echo h(number_format($fobVentaCaja, 4, '.', '')); ?>" <?php echo $LIQUIDACION_BLOQUEADA ? 'readonly' : ''; ?>></td>
                                                <td class="text-right js-fob-kg"><?php echo numero($fobKgDisplay); ?></td>
                                                <input type="hidden" class="js-fob-kg-input" name="linea[<?php echo $idx; ?>][fob_kg]" value="<?php echo h($l['FOB_REAL']); ?>">
                                                <input type="hidden" class="js-fob-origen-kg-input" name="linea[<?php echo $idx; ?>][fob_origen_kg]" value="<?php echo h($fobOrigenKg > 0 ? $fobOrigenKg : $l['FOB_REAL']); ?>">
                                                <td class="text-right js-diferencia"><?php echo numero($diferencia); ?></td>
                                                <td class="text-right js-retorno"><?php echo numero($venta); ?></td>
                                                <td class="text-right js-fob-final-caja"><?php echo numero($fobFinalCaja); ?></td>
                                                <td class="text-right js-fob-final-kg"><?php echo numero($fobFinalKg); ?></td>
                                                <td><input type="text" class="form-control obs-input" name="linea[<?php echo $idx; ?>][observacion]" value="<?php echo h($l['OBSERVACION']); ?>" <?php echo $LIQUIDACION_BLOQUEADA ? 'readonly' : ''; ?>></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row liqui-summary">
                                    <div class="col-md-2">
                                        <strong>Resumen Precio - US$</strong>
                                    </div>
                                    <div class="col-md-2">
                                        Ingreso
                                        <input type="text" class="form-control liqui-total" id="totalVentaBottom" value="<?php echo numero($TOTAL_VENTA); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        Comision
                                        <input type="text" class="form-control liqui-total" id="totalComisionBottom" value="<?php echo numero($TOTAL_COMISION_ITEMS); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        Gastos
                                        <input type="text" class="form-control liqui-total" id="totalGastosBottom" value="<?php echo numero($TOTAL_GASTOS_ITEMS); ?>" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        Venta neta final
                                        <input type="text" class="form-control liqui-total" id="totalVentaNetoBottom" value="<?php echo numero($TOTAL_VENTA_NETO); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row liqui-summary">
                                    <div class="col-md-3">
                                        <strong>Comparacion Invoice</strong>
                                    </div>
                                    <div class="col-md-3">
                                        Invoice confirmada US$
                                        <input type="text" class="form-control liqui-total" id="totalInvoiceUsd" value="<?php echo numero($TOTAL_INVOICE_USD); ?>" readonly data-invoice-usd="<?php echo h($TOTAL_INVOICE_USD); ?>">
                                        <?php if ($MONEDA_INVOICE_CONFIRMADA === 'EUR') { ?>
                                            <small>Origen EUR <?php echo numero($TOTAL_INVOICE_ORIGEN); ?> | TC <?php echo h(number_format($TIPO_CAMBIO_INVOICE, 6, '.', '')); ?></small>
                                        <?php } ?>
                                    </div>
                                    <div class="col-md-3">
                                        Diferencia neta vs invoice
                                        <input type="text" class="form-control liqui-total" id="diferenciaInvoiceLiquidacion" value="<?php echo numero($DIFERENCIA_INVOICE_LIQUIDACION); ?>" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        Documento sugerido
                                        <input type="text" class="form-control liqui-total" id="documentoSugerido" value="<?php echo h($DOCUMENTO_SUGERIDO); ?>" readonly data-tolerancia-nota="<?php echo h($TOLERANCIA_NOTA); ?>">
                                        <a class="btn btn-sm btn-primary mt-5" id="btnCrearNotaDc" href="<?php echo h($URL_NOTADC_SUGERIDA); ?>" style="<?php echo $TNOTA_SUGERIDA > 0 ? '' : 'display:none'; ?>">
                                            <i class="ti-pencil-alt"></i> Ingresar nota D/C
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <?php if (!$LIQUIDACION_BLOQUEADA) { ?>
                                <button type="submit" class="btn btn-success" name="GUARDAR" value="GUARDAR">
                                    <i class="ti-save-alt"></i> Guardar liquidacion
                                </button>
                                <?php } else { ?>
                                <a class="btn btn-warning" href="registroAPExportadora.php?MODULO=LIQUIDACION&ID=<?php echo (int)$IDVALOR_ACTUAL; ?>&ICARGA=<?php echo (int)$IDICARGA; ?>&NUMERO=<?php echo urlencode($CABECERA['NREFERENCIA_ICARGA'] ?? $IDICARGA); ?>&RETORNO=registroLiquidacionExp">
                                    <i class="fa fa-folder-open"></i> Solicitar reapertura
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
<script>
    (function () {
        function valor(input) {
            return parseFloat(String(input.value || '0').replace(',', '.')) || 0;
        }
        function formato(n) {
            return n.toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function setValor(id, v) {
            var el = document.getElementById(id);
            if (el) el.value = formato(v);
        }
        function setTexto(id, v) {
            var el = document.getElementById(id);
            if (el) el.value = v;
        }
        function getMoneda() {
            var s = document.getElementById('selectMoneda');
            return s ? s.value : 'USD';
        }
        function getEurUsd() {
            var i = document.getElementById('eurUsdRate');
            return i ? (parseFloat(i.value) || 1) : 1;
        }
        function getCondicionFlete() {
            var s = document.getElementById('condicionFlete');
            return s ? s.value : 'COLLECT';
        }

        function recalcular() {
            var moneda   = getMoneda();
            var eurUsd   = getEurUsd();
            var condicionFlete = getCondicionFlete();
            var totalVenta = 0, totalNeto = 0;
            var filas = document.querySelectorAll('.js-linea');

            filas.forEach(function (fila) {
                var neto     = parseFloat(fila.getAttribute('data-neto'))      || 0;
                var kgCaja   = parseFloat(fila.getAttribute('data-kg-caja'))   || 0;
                var fobRefKg = parseFloat(fila.getAttribute('data-fob-ref-kg'))|| 0;
                var fobCaja  = valor(fila.querySelector('.js-fob-caja'));
                var fobKg    = kgCaja > 0 ? fobCaja / kgCaja : 0;          // en moneda seleccionada
                var fobKgUsd = moneda === 'EUR' ? fobKg * eurUsd : fobKg;   // siempre USD
                var venta    = neto * fobKgUsd;
                totalVenta  += venta;
                totalNeto   += neto;
                fila.setAttribute('data-venta', venta);
                fila.querySelector('.js-fob-kg').textContent            = formato(fobKg);
                fila.querySelector('.js-fob-kg-input').value            = fobKgUsd.toFixed(8);
                fila.querySelector('.js-fob-origen-kg-input').value     = fobKg.toFixed(8);
                fila.querySelector('.js-diferencia').textContent        = formato(fobKgUsd - fobRefKg);
                fila.querySelector('.js-retorno').textContent           = formato(venta);
            });

            var totalComision = 0, totalGastos = 0;
            document.querySelectorAll('.js-gasto-item').forEach(function (input) {
                var gasto    = valor(input);
                var gastoUsd = moneda === 'EUR' ? gasto * eurUsd : gasto;
                var nombre   = input.closest('.form-group').querySelector('label').textContent.toUpperCase();
                var tipoGasto = input.getAttribute('data-tipo-gasto') || 'GASTO';
                if (tipoGasto === 'COMISION' || nombre.indexOf('COMISI') !== -1) {
                    totalComision += gastoUsd;
                } else if (!(condicionFlete === 'PREPAID' && tipoGasto === 'FLETE')) {
                    totalGastos += gastoUsd;
                }
            });

            filas.forEach(function (fila) {
                var neto   = parseFloat(fila.getAttribute('data-neto'))   || 0;
                var kgCaja = parseFloat(fila.getAttribute('data-kg-caja'))|| 0;
                var venta  = parseFloat(fila.getAttribute('data-venta'))  || 0;
                var comisionLinea  = totalVenta > 0 ? totalComision * (venta / totalVenta) : 0;
                var gastoLinea     = totalNeto  > 0 ? totalGastos   * (neto  / totalNeto)  : 0;
                var retornoFinal   = venta - comisionLinea - gastoLinea;
                var fobFinalKg     = neto > 0 ? retornoFinal / neto : 0;
                fila.querySelector('.js-fob-final-caja').textContent = formato(fobFinalKg * kgCaja);
                fila.querySelector('.js-fob-final-kg').textContent   = formato(fobFinalKg);
            });

            var totalVentaNeto = totalVenta - totalComision - totalGastos;
            var invoiceEl = document.getElementById('totalInvoiceUsd');
            var documentoEl = document.getElementById('documentoSugerido');
            var totalInvoice = invoiceEl ? (parseFloat(invoiceEl.getAttribute('data-invoice-usd')) || 0) : 0;
            var tolerancia = documentoEl ? (parseFloat(documentoEl.getAttribute('data-tolerancia-nota')) || 1) : 1;
            var diferenciaInvoice = totalVentaNeto - totalInvoice;
            var documentoSugerido = 'Sin invoice confirmada';
            if (totalInvoice > 0) {
                if (diferenciaInvoice > tolerancia) {
                    documentoSugerido = 'Nota de Debito';
                } else if (diferenciaInvoice < (tolerancia * -1)) {
                    documentoSugerido = 'Nota de Credito';
                } else {
                    documentoSugerido = 'Sin nota';
                }
            }
            setValor('totalVentaTop',      totalVenta);
            setValor('totalVentaNetoTop',  totalVentaNeto);
            setValor('totalComisionTop',   totalComision);
            setValor('totalGastosTop',     totalGastos);
            setValor('totalVentaBottom',   totalVenta);
            setValor('totalVentaNetoBottom', totalVentaNeto);
            setValor('totalComisionBottom',totalComision);
            setValor('totalGastosBottom',  totalGastos);
            setValor('diferenciaInvoiceLiquidacion', diferenciaInvoice);
            setTexto('documentoSugerido', documentoSugerido);

            var btnNota = document.getElementById('btnCrearNotaDc');
            if (btnNota) {
                if (documentoSugerido === 'Nota de Debito' || documentoSugerido === 'Nota de Credito') {
                    var tipoNota = documentoSugerido === 'Nota de Debito' ? '1' : '2';
                    var referenciaNota = <?php echo json_encode((string)($CABECERA['NREFERENCIA_ICARGA'] ?? ''), JSON_UNESCAPED_UNICODE); ?>;
                    var obs = 'Generada desde liquidacion ' + referenciaNota + '. Diferencia neta vs invoice US$ ' + Math.abs(diferenciaInvoice).toFixed(2);
                    btnNota.href = 'registroNotadc.php?ICARGAD=<?php echo (int)$IDICARGA; ?>&TNOTA=' + encodeURIComponent(tipoNota) + '&OBSERVACIONINOTA=' + encodeURIComponent(obs);
                    btnNota.style.display = '';
                } else {
                    btnNota.href = '#';
                    btnNota.style.display = 'none';
                }
            }
        }

        function actualizarEtiquetas() {
            var moneda   = getMoneda();
            var etiqueta = moneda === 'EUR' ? 'EUR' : 'US$';
            var thCaja   = document.querySelector('.js-th-fob-caja');
            var thKg     = document.querySelector('.js-th-fob-kg');
            var labMasivo= document.getElementById('labelMonedaMasivo');
            var panel    = document.getElementById('panelEurUsd');
            if (thCaja)    thCaja.textContent    = 'FOB Venta Caja ' + etiqueta;
            if (thKg)      thKg.textContent      = 'FOB Venta Kg '   + etiqueta;
            if (labMasivo) labMasivo.textContent = etiqueta;
            if (panel)     panel.style.display   = moneda === 'EUR' ? '' : 'none';
        }

        document.querySelectorAll('.js-fob-caja, .js-gasto-item').forEach(function (input) {
            input.addEventListener('input', recalcular);
        });
        document.querySelectorAll('.js-4dec').forEach(function (input) {
            input.addEventListener('blur', function () {
                if (input.value !== '') {
                    var partes = String(input.value).replace(',', '.').split('.');
                    if (partes.length > 1 && partes[1].length > 4) {
                        input.value = partes[0] + '.' + partes[1].slice(0, 4);
                    }
                    recalcular();
                }
            });
        });

        var selectMoneda = document.getElementById('selectMoneda');
        if (selectMoneda) {
            selectMoneda.addEventListener('change', function () {
                var moneda = this.value;
                var eurUsd = getEurUsd();
                actualizarEtiquetas();
                // Convierte los valores del input a la moneda recién seleccionada
                document.querySelectorAll('.js-linea').forEach(function (fila) {
                    var input = fila.querySelector('.js-fob-caja');
                    var v = parseFloat(input.value) || 0;
                    if (v !== 0) {
                        input.value = moneda === 'EUR'
                            ? (v / eurUsd).toFixed(4)   // USD → EUR
                            : (v * eurUsd).toFixed(4);  // EUR → USD
                    }
                });
                recalcular();
            });
        }

        var eurUsdInput = document.getElementById('eurUsdRate');
        if (eurUsdInput) {
            eurUsdInput.addEventListener('input', recalcular);
        }
        var condicionFlete = document.getElementById('condicionFlete');
        if (condicionFlete) {
            condicionFlete.addEventListener('change', recalcular);
        }

        var botonMasivo = document.getElementById('aplicarFobMasivo');
        if (botonMasivo) {
            botonMasivo.addEventListener('click', function () {
                var fobCaja = valor(document.getElementById('fobMasivoCaja'));
                document.querySelectorAll('.js-fob-caja').forEach(function (input) {
                    input.value = fobCaja.toFixed(4);
                });
                recalcular();
            });
        }

        var botonReferencia = document.getElementById('copiarReferenciaCaja');
        if (botonReferencia) {
            botonReferencia.addEventListener('click', function () {
                var moneda = getMoneda();
                var eurUsd = getEurUsd();
                document.querySelectorAll('.js-linea').forEach(function (fila) {
                    var refCaja = parseFloat(fila.getAttribute('data-fob-ref-caja')) || 0;
                    // La referencia es USD; si estamos en EUR, convertir para mostrar
                    fila.querySelector('.js-fob-caja').value = moneda === 'EUR'
                        ? (refCaja / eurUsd).toFixed(4)
                        : refCaja.toFixed(4);
                });
                recalcular();
            });
        }
    })();
</script>
</body>
</html>
