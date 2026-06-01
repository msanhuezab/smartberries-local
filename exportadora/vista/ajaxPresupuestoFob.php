<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

header('Content-Type: application/json; charset=utf-8');

$db = BDCONFIG::conectar();
$accion = $_GET['accion'] ?? '';

function responderPresupuesto($datos)
{
    echo json_encode($datos);
    exit;
}

function presupuestoVigente($db, $empresa, $temporada, $mercado, $consignatario, $estandar = 0, $calibre = 0)
{
    $params = [(int)$empresa, (int)$temporada, (int)$mercado, (int)$consignatario];
    $filtroProducto = '';
    if ((int)$estandar > 0 && (int)$calibre > 0) {
        $filtroProducto = ' AND p.ID_ESTANDAR = ? AND p.ID_TCALIBRE = ? ';
        $params[] = (int)$estandar;
        $params[] = (int)$calibre;
    }

    $stmt = $db->prepare("
        SELECT
            p.*,
            IFNULL(tm.NOMBRE_TMONEDA, '') AS NOMBRE_TMONEDA,
            IFNULL(cv.NOMBRE_CVENTA, '') AS NOMBRE_CVENTA,
            IFNULL(mv.NOMBRE_MVENTA, '') AS NOMBRE_MVENTA,
            IFNULL(tf.NOMBRE_TFLETE, '') AS NOMBRE_TFLETE
        FROM exportadora_presupuesto_fob p
        LEFT JOIN fruta_tmoneda tm ON tm.ID_TMONEDA = p.ID_TMONEDA
        LEFT JOIN fruta_cventa cv ON cv.ID_CVENTA = p.ID_CVENTA
        LEFT JOIN fruta_mventa mv ON mv.ID_MVENTA = p.ID_MVENTA
        LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = p.ID_TFLETE
        WHERE p.ID_EMPRESA = ?
        AND p.ID_TEMPORADA = ?
        AND p.ID_MERCADO = ?
        AND p.ID_CONSIGNATARIO = ?
        AND p.ESTADO_REGISTRO = 1
        {$filtroProducto}
        ORDER BY p.MODIFICACION DESC, p.ID_PRESUPUESTO_FOB DESC
        LIMIT 1
    ");
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$db) {
    responderPresupuesto(['ok' => false, 'mensaje' => 'Sin conexion a base de datos.']);
}

if ($accion === 'comercial') {
    $mercado = (int)($_GET['mercado'] ?? 0);
    $consignatario = (int)($_GET['consignatario'] ?? 0);
    if ($mercado <= 0 || $consignatario <= 0) {
        responderPresupuesto(['ok' => false, 'mensaje' => 'Debe seleccionar mercado y cliente.']);
    }

    $presupuesto = presupuestoVigente($db, $EMPRESAS, $TEMPORADAS, $mercado, $consignatario);
    if (!$presupuesto) {
        responderPresupuesto(['ok' => false, 'mensaje' => 'Sin presupuesto vigente para mercado y cliente.']);
    }

    responderPresupuesto([
        'ok' => true,
        'id_tmoneda' => (int)$presupuesto['ID_TMONEDA'],
        'id_cventa' => (int)$presupuesto['ID_CVENTA'],
        'id_mventa' => (int)$presupuesto['ID_MVENTA'],
        'id_tflete' => (int)$presupuesto['ID_TFLETE'],
        'moneda' => $presupuesto['NOMBRE_TMONEDA'],
        'clausula' => $presupuesto['NOMBRE_CVENTA'],
        'modalidad' => $presupuesto['NOMBRE_MVENTA'],
        'flete' => $presupuesto['NOMBRE_TFLETE'],
    ]);
}

if ($accion === 'precio') {
    $idIcarga = (int)($_GET['icarga'] ?? 0);
    $estandar = (int)($_GET['estandar'] ?? 0);
    $calibre = (int)($_GET['calibre'] ?? 0);
    if ($idIcarga <= 0 || $estandar <= 0 || $calibre <= 0) {
        responderPresupuesto(['ok' => false, 'mensaje' => 'Debe seleccionar instructivo, estandar y calibre.']);
    }

    $stmtIcarga = $db->prepare("
        SELECT ID_MERCADO, ID_CONSIGNATARIO, ID_EMPRESA, ID_TEMPORADA
        FROM fruta_icarga
        WHERE ID_ICARGA = ?
        AND ESTADO_REGISTRO = 1
        LIMIT 1
    ");
    $stmtIcarga->execute([$idIcarga]);
    $icarga = $stmtIcarga->fetch(PDO::FETCH_ASSOC);
    if (!$icarga) {
        responderPresupuesto(['ok' => false, 'mensaje' => 'Instructivo no encontrado.']);
    }

    $presupuesto = presupuestoVigente(
        $db,
        $icarga['ID_EMPRESA'],
        $icarga['ID_TEMPORADA'],
        $icarga['ID_MERCADO'],
        $icarga['ID_CONSIGNATARIO'],
        $estandar,
        $calibre
    );
    if (!$presupuesto) {
        responderPresupuesto(['ok' => false, 'mensaje' => 'Sin FOB sugerido para estandar y calibre.']);
    }

    responderPresupuesto([
        'ok' => true,
        'fob_caja' => number_format((float)$presupuesto['FOB_CAJA'], 4, '.', ''),
        'peso_caja' => number_format((float)$presupuesto['PESO_CAJA'], 4, '.', ''),
        'id_tmoneda' => (int)$presupuesto['ID_TMONEDA'],
        'moneda' => $presupuesto['NOMBRE_TMONEDA'],
    ]);
}

responderPresupuesto(['ok' => false, 'mensaje' => 'Accion no valida.']);
