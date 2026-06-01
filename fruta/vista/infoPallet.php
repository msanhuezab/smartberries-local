<?php
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../assest/config/BDCONFIG.php';

function h($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function obtenerParametroQr($clave)
{
    if (isset($_GET[$clave])) {
        return trim((string) $_GET[$clave]);
    }

    $qr = isset($_GET['qr']) ? trim((string) $_GET['qr']) : '';
    if ($qr === '') {
        return '';
    }

    $partes = preg_split('/[|;&]/', $qr);
    foreach ($partes as $parte) {
        $piezas = explode('=', $parte, 2);
        if (count($piezas) === 2 && strtoupper(trim($piezas[0])) === strtoupper($clave)) {
            return trim($piezas[1]);
        }
    }

    return '';
}

function formatearFechaHora($fecha, $hora = '')
{
    if (!$fecha || $fecha === '0000-00-00') {
        return 'Pendiente';
    }

    $timestamp = strtotime($fecha);
    if (!$timestamp) {
        return 'Pendiente';
    }

    $texto = date('d-m-Y', $timestamp);
    if ($hora === '' && preg_match('/\d{2}:\d{2}/', (string) $fecha)) {
        $texto .= ' ' . date('H:i', $timestamp);
        return $texto;
    }

    if ($hora !== '') {
        $texto .= ' ' . $hora;
    }
    return $texto;
}

function estadoExistencia($existencia, $detalle)
{
    if ($detalle && (int) $detalle['D_ESTADO_REGISTRO'] === 0) {
        return ['Anulado', 'danger'];
    }

    if ($existencia && despachoCerrado($existencia)) {
        return ['Despachado', 'dark'];
    }

    if ($existencia && procesoCerrado($existencia)) {
        return ['Procesado', 'success'];
    }

    if ($detalle && (int) $detalle['R_ESTADO'] === 0) {
        return ['Recepcionado', 'primary'];
    }

    if ($detalle) {
        return ['En recepcion', 'warning'];
    }

    if ($existencia && (int) $existencia['ESTADO'] === 2) {
        return ['En existencia', 'success'];
    }

    return ['Pendiente de recepcion', 'secondary'];
}

function procesoCerrado($existencia)
{
    if (!$existencia) {
        return false;
    }

    return (!empty($existencia['PROC1_ID']) && (int) $existencia['PROC1_ESTADO'] === 0)
        || (!empty($existencia['PROC2_ID']) && (int) $existencia['PROC2_ESTADO'] === 0);
}

function fechaProcesoCerrado($existencia)
{
    if (!$existencia) {
        return '';
    }

    if (!empty($existencia['PROC1_ID']) && (int) $existencia['PROC1_ESTADO'] === 0) {
        return $existencia['PROC1_MODIFICACION'] ?? ($existencia['PROC1_FECHA'] ?? ($existencia['FECHA_PROCESO'] ?? ''));
    }

    if (!empty($existencia['PROC2_ID']) && (int) $existencia['PROC2_ESTADO'] === 0) {
        return $existencia['PROC2_MODIFICACION'] ?? ($existencia['PROC2_FECHA'] ?? ($existencia['FECHA_PROCESO'] ?? ''));
    }

    return '';
}

function despachoCerrado($existencia)
{
    if (!$existencia) {
        return false;
    }

    return (!empty($existencia['DESP1_ID']) && (int) $existencia['DESP1_ESTADO'] === 0)
        || (!empty($existencia['DESP2_ID']) && (int) $existencia['DESP2_ESTADO'] === 0)
        || (!empty($existencia['DESP3_ID']) && (int) $existencia['DESP3_ESTADO'] === 0);
}

function fechaDespachoCerrado($existencia)
{
    if (!$existencia) {
        return '';
    }

    if (!empty($existencia['DESP1_ID']) && (int) $existencia['DESP1_ESTADO'] === 0) {
        return $existencia['DESP1_FECHA'] ?? ($existencia['FECHA_DESPACHO'] ?? '');
    }

    if (!empty($existencia['DESP2_ID']) && (int) $existencia['DESP2_ESTADO'] === 0) {
        return $existencia['DESP2_FECHA'] ?? ($existencia['FECHA_DESPACHO'] ?? '');
    }

    if (!empty($existencia['DESP3_ID']) && (int) $existencia['DESP3_ESTADO'] === 0) {
        return $existencia['DESP3_FECHA'] ?? ($existencia['FECHA_DESPACHO'] ?? '');
    }

    return '';
}

function tokenPallet($p, $v, $f)
{
    return 'P' . $p . '-V' . $v . '-F' . $f;
}

function usuarioLogueado()
{
    return isset($_SESSION['ID_USUARIO']) && isset($_SESSION['NOMBRE_USUARIO']);
}

function iniciarSesionPallet($conexion, $nombre, $contrasena)
{
    $stmt = $conexion->prepare("
        SELECT ID_USUARIO, NOMBRE_USUARIO, ID_TUSUARIO
        FROM usuario_usuario
        WHERE ESTADO_REGISTRO = 1
          AND NOMBRE_USUARIO = ?
          AND (
              CONTRASENA_USUARIO = SHA2(?, 512)
              OR CONTRASENA_USUARIO = ?
          )
        LIMIT 1
    ");
    $stmt->execute([$nombre, $contrasena, $contrasena]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        return false;
    }

    if (PHP_SAPI !== 'cli') {
        session_regenerate_id(true);
    }

    $_SESSION['ID_USUARIO'] = $usuario['ID_USUARIO'];
    $_SESSION['NOMBRE_USUARIO'] = $usuario['NOMBRE_USUARIO'];
    $_SESSION['TIPO_USUARIO'] = $usuario['ID_TUSUARIO'];

    return true;
}

function redirigirMismaPagina($parametro, $valor)
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $ruta = strtok($_SERVER['REQUEST_URI'] ?? 'infoPallet.php', '?');
    $params = $_GET;
    unset($params['evento'], $params['login']);
    $params[$parametro] = $valor;
    header('Location: ' . $ruta . '?' . http_build_query($params));
    exit;
}

function etiquetaEvento($tipo)
{
    $etiquetas = [
        'ARMADO_CAMPO' => 'Armado campo',
        'EN_RECEPCION' => 'En recepcion',
        'RECEPCIONADO' => 'Recepcionado planta',
        'PREFRIO' => 'Pre-frio',
        'EN_PROCESO' => 'En proceso',
        'PROCESADO' => 'Procesado',
        'DESPACHADO' => 'Despachado',
        'ANULADO' => 'Anulado',
    ];
    return $etiquetas[$tipo] ?? $tipo;
}

function estadoDesdeEvento($tipo)
{
    $estados = [
        'ARMADO_CAMPO' => ['Armado campo', 'primary'],
        'EN_RECEPCION' => ['En recepcion', 'warning'],
        'RECEPCIONADO' => ['Recepcionado', 'primary'],
        'PREFRIO' => ['En pre-frio', 'warning'],
        'EN_PROCESO' => ['En proceso', 'warning'],
        'PROCESADO' => ['Procesado', 'success'],
        'DESPACHADO' => ['Despachado', 'dark'],
        'ANULADO' => ['Anulado', 'danger'],
    ];
    return $estados[$tipo] ?? [etiquetaEvento($tipo), 'secondary'];
}

function buscarPallet($conexion, $token)
{
    $stmt = $conexion->prepare("SELECT * FROM fruta_pallet WHERE TOKEN_QR = ? LIMIT 1");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function asegurarPallet($conexion, $token, $p, $v, $f, $productor, $detalle, $existencia)
{
    $pallet = buscarPallet($conexion, $token);
    $idProductor = $detalle['ID_PRODUCTOR'] ?? ($productor['ID_PRODUCTOR'] ?? null);
    $idEmpresa = $detalle['ID_EMPRESA'] ?? ($existencia['ID_EMPRESA'] ?? ($productor['ID_EMPRESA'] ?? ($_SESSION['ID_EMPRESA'] ?? null)));
    $idPlanta = $detalle['ID_PLANTA'] ?? ($existencia['ID_PLANTA'] ?? ($_SESSION['ID_PLANTA'] ?? null));
    $idTemporada = $detalle['ID_TEMPORADA'] ?? ($existencia['ID_TEMPORADA'] ?? ($_SESSION['ID_TEMPORADA'] ?? null));
    $idDetalle = $detalle['ID_DRECEPCION'] ?? null;
    $idExistencia = $existencia['ID_EXIMATERIAPRIMA'] ?? null;

    if ($pallet) {
        $stmt = $conexion->prepare("
            UPDATE fruta_pallet
            SET ID_PRODUCTOR = COALESCE(?, ID_PRODUCTOR),
                ID_DRECEPCIONMP = COALESCE(?, ID_DRECEPCIONMP),
                ID_EXIMATERIAPRIMA = COALESCE(?, ID_EXIMATERIAPRIMA),
                ID_EMPRESA = COALESCE(?, ID_EMPRESA),
                ID_PLANTA = COALESCE(?, ID_PLANTA),
                ID_TEMPORADA = COALESCE(?, ID_TEMPORADA)
            WHERE ID_PALLET = ?
        ");
        $stmt->execute([$idProductor, $idDetalle, $idExistencia, $idEmpresa, $idPlanta, $idTemporada, $pallet['ID_PALLET']]);
        return buscarPallet($conexion, $token);
    }

    $stmt = $conexion->prepare("
        INSERT INTO fruta_pallet (
            TOKEN_QR, CSG_PRODUCTOR, ID_PRODUCTOR, ID_VESPECIES, FOLIO_PRODUCTOR,
            ID_DRECEPCIONMP, ID_EXIMATERIAPRIMA, ID_EMPRESA, ID_PLANTA, ID_TEMPORADA,
            ESTADO_REGISTRO
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$token, $p, $idProductor, $v, $f, $idDetalle, $idExistencia, $idEmpresa, $idPlanta, $idTemporada]);

    return buscarPallet($conexion, $token);
}

function registrarEventoPallet($conexion, $pallet, $tipo, $observacion)
{
    $stmt = $conexion->prepare("
        INSERT INTO fruta_pallet_evento (
            ID_PALLET, TIPO_EVENTO, FECHA_HORA, ID_USUARIO, ID_EMPRESA, ID_PLANTA,
            ID_TEMPORADA, OBSERVACION, ORIGEN, ESTADO_REGISTRO
        ) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 'WEB', 1)
    ");
    $stmt->execute([
        $pallet['ID_PALLET'],
        $tipo,
        $_SESSION['ID_USUARIO'] ?? null,
        $_SESSION['ID_EMPRESA'] ?? ($pallet['ID_EMPRESA'] ?? null),
        $_SESSION['ID_PLANTA'] ?? ($pallet['ID_PLANTA'] ?? null),
        $_SESSION['ID_TEMPORADA'] ?? ($pallet['ID_TEMPORADA'] ?? null),
        $observacion,
    ]);
}

function obtenerEventosPallet($conexion, $idPallet)
{
    if (!$idPallet) {
        return [];
    }

    $stmt = $conexion->prepare("
        SELECT E.*, U.NOMBRE_USUARIO
        FROM fruta_pallet_evento E
        LEFT JOIN usuario_usuario U ON U.ID_USUARIO = E.ID_USUARIO
        WHERE E.ID_PALLET = ?
          AND E.ESTADO_REGISTRO = 1
        ORDER BY E.FECHA_HORA ASC, E.ID_EVENTO ASC
    ");
    $stmt->execute([$idPallet]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$p = obtenerParametroQr('P');
$v = obtenerParametroQr('V');
$f = obtenerParametroQr('F');
$error = '';
$productor = null;
$variedad = null;
$detalle = null;
$existencia = null;
$pallet = null;
$eventos = [];
$mensajeEvento = '';
$mensajeLogin = '';
$token = ($p !== '' && $v !== '' && $f !== '') ? tokenPallet($p, $v, $f) : '';

try {
    $conexion = BDCONFIG::conectar();

    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['LOGIN_PALLET'])) {
        $nombreLogin = trim((string) ($_POST['NOMBRE_LOGIN'] ?? ''));
        $contrasenaLogin = (string) ($_POST['CONTRASENA_LOGIN'] ?? '');

        if ($nombreLogin === '' || $contrasenaLogin === '') {
            $mensajeLogin = 'Debe ingresar usuario y contrasena.';
        } elseif (!iniciarSesionPallet($conexion, $nombreLogin, $contrasenaLogin)) {
            $mensajeLogin = 'Usuario o contrasena incorrectos.';
        } else {
            redirigirMismaPagina('login', 'ok');
        }
    }

    if ($p !== '') {
        $stmt = $conexion->prepare("
            SELECT ID_PRODUCTOR, CSG_PRODUCTOR, NOMBRE_PRODUCTOR, ID_EMPRESA
            FROM fruta_productor
            WHERE CSG_PRODUCTOR = ?
            ORDER BY ESTADO_REGISTRO DESC, ID_PRODUCTOR ASC
            LIMIT 1
        ");
        $stmt->execute([$p]);
        $productor = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($v !== '') {
        $stmt = $conexion->prepare("
            SELECT ID_VESPECIES, CODIGO_SAG_VESPECIES, NOMBRE_VESPECIES
            FROM fruta_vespecies
            WHERE ID_VESPECIES = ?
            ORDER BY ESTADO_REGISTRO DESC
            LIMIT 1
        ");
        $stmt->execute([$v]);
        $variedad = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($p !== '' && $v !== '' && $f !== '') {
        $stmt = $conexion->prepare("
            SELECT
                D.ID_DRECEPCION,
                D.FOLIO_DRECEPCION,
                D.FECHA_COSECHA_DRECEPCION,
                D.CANTIDAD_ENVASE_DRECEPCION,
                D.KILOS_NETO_DRECEPCION,
                D.KILOS_BRUTO_DRECEPCION,
                D.ESTADO_REGISTRO AS D_ESTADO_REGISTRO,
                R.ID_RECEPCION,
                R.NUMERO_RECEPCION,
                R.FECHA_RECEPCION,
                R.HORA_RECEPCION,
                R.ESTADO AS R_ESTADO,
                R.ESTADO_REGISTRO AS R_ESTADO_REGISTRO,
                R.ID_EMPRESA,
                R.ID_PLANTA,
                R.ID_TEMPORADA,
                PR.ID_PRODUCTOR,
                PR.CSG_PRODUCTOR,
                PR.NOMBRE_PRODUCTOR,
                VE.NOMBRE_VESPECIES
            FROM fruta_drecepcionmp D
            INNER JOIN fruta_recepcionmp R ON R.ID_RECEPCION = D.ID_RECEPCION
            INNER JOIN fruta_productor PR ON PR.ID_PRODUCTOR = D.ID_PRODUCTOR
            LEFT JOIN fruta_vespecies VE ON VE.ID_VESPECIES = D.ID_VESPECIES
            WHERE PR.CSG_PRODUCTOR = ?
              AND D.ID_VESPECIES = ?
              AND D.FOLIO_DRECEPCION = ?
            ORDER BY D.ESTADO_REGISTRO DESC, R.FECHA_RECEPCION DESC, D.ID_DRECEPCION DESC
            LIMIT 1
        ");
        $stmt->execute([$p, $v, $f]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $conexion->prepare("
            SELECT
                EXI.ID_EXIMATERIAPRIMA,
                EXI.FOLIO_EXIMATERIAPRIMA,
                EXI.FECHA_RECEPCION,
                EXI.FECHA_PROCESO,
                EXI.FECHA_DESPACHO,
                EXI.KILOS_NETO_EXIMATERIAPRIMA,
                EXI.CANTIDAD_ENVASE_EXIMATERIAPRIMA,
                EXI.ESTADO,
                EXI.ESTADO_REGISTRO,
                EXI.ID_RECEPCION,
                EXI.ID_EMPRESA,
                EXI.ID_PLANTA,
                EXI.ID_TEMPORADA,
                EXI.ID_PROCESO,
                EXI.ID_PROCESO2,
                EXI.ID_DESPACHO,
                EXI.ID_DESPACHO2,
                EXI.ID_DESPACHO3,
                PROC1.ID_PROCESO AS PROC1_ID,
                PROC1.FECHA_PROCESO AS PROC1_FECHA,
                PROC1.MODIFICACION AS PROC1_MODIFICACION,
                PROC1.ESTADO AS PROC1_ESTADO,
                PROC2.ID_PROCESO AS PROC2_ID,
                PROC2.FECHA_PROCESO AS PROC2_FECHA,
                PROC2.MODIFICACION AS PROC2_MODIFICACION,
                PROC2.ESTADO AS PROC2_ESTADO,
                DESP1.ID_DESPACHO AS DESP1_ID,
                DESP1.FECHA_DESPACHO AS DESP1_FECHA,
                DESP1.ESTADO AS DESP1_ESTADO,
                DESP2.ID_DESPACHO AS DESP2_ID,
                DESP2.FECHA_DESPACHO AS DESP2_FECHA,
                DESP2.ESTADO AS DESP2_ESTADO,
                DESP3.ID_DESPACHO AS DESP3_ID,
                DESP3.FECHA_DESPACHO AS DESP3_FECHA,
                DESP3.ESTADO AS DESP3_ESTADO
            FROM fruta_eximateriaprima EXI
            INNER JOIN fruta_productor PR ON PR.ID_PRODUCTOR = EXI.ID_PRODUCTOR
            LEFT JOIN fruta_proceso PROC1 ON PROC1.ID_PROCESO = EXI.ID_PROCESO
            LEFT JOIN fruta_proceso PROC2 ON PROC2.ID_PROCESO = EXI.ID_PROCESO2
            LEFT JOIN fruta_despachomp DESP1 ON DESP1.ID_DESPACHO = EXI.ID_DESPACHO
            LEFT JOIN fruta_despachomp DESP2 ON DESP2.ID_DESPACHO = EXI.ID_DESPACHO2
            LEFT JOIN fruta_despachomp DESP3 ON DESP3.ID_DESPACHO = EXI.ID_DESPACHO3
            WHERE PR.CSG_PRODUCTOR = ?
              AND EXI.ID_VESPECIES = ?
              AND EXI.FOLIO_EXIMATERIAPRIMA = ?
              AND EXI.ESTADO_REGISTRO = 1
            ORDER BY EXI.ID_EXIMATERIAPRIMA DESC
            LIMIT 1
        ");
        $stmt->execute([$p, $v, $f]);
        $existencia = $stmt->fetch(PDO::FETCH_ASSOC);

        $pallet = buscarPallet($conexion, $token);
        if ($pallet) {
            $pallet = asegurarPallet($conexion, $token, $p, $v, $f, $productor, $detalle, $existencia);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['EVENTO_PALLET'])) {
            if (!usuarioLogueado()) {
                $mensajeEvento = 'Debe iniciar sesion para registrar eventos operativos.';
            } else {
                $eventosPermitidos = ['ARMADO_CAMPO', 'PREFRIO', 'EN_PROCESO'];
                $eventoPost = strtoupper(trim((string) $_POST['EVENTO_PALLET']));
                if (in_array($eventoPost, $eventosPermitidos, true)) {
                    $pallet = asegurarPallet($conexion, $token, $p, $v, $f, $productor, $detalle, $existencia);
                    registrarEventoPallet($conexion, $pallet, $eventoPost, trim((string) ($_POST['OBSERVACION_EVENTO'] ?? '')));
                    redirigirMismaPagina('evento', 'ok');
                }
                $mensajeEvento = 'Evento no permitido.';
            }
        }

        $eventos = obtenerEventosPallet($conexion, $pallet['ID_PALLET'] ?? null);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

$nombreProductor = $detalle['NOMBRE_PRODUCTOR'] ?? ($productor['NOMBRE_PRODUCTOR'] ?? 'Sin productor');
$nombreVariedad = $detalle['NOMBRE_VESPECIES'] ?? ($variedad['NOMBRE_VESPECIES'] ?? 'Sin variedad');
$kilos = $detalle['KILOS_NETO_DRECEPCION'] ?? ($existencia['KILOS_NETO_EXIMATERIAPRIMA'] ?? '');
$envases = $detalle['CANTIDAD_ENVASE_DRECEPCION'] ?? ($existencia['CANTIDAD_ENVASE_EXIMATERIAPRIMA'] ?? '');

$eventosPorTipo = [];
foreach ($eventos as $evento) {
    $eventosPorTipo[$evento['TIPO_EVENTO']] = $evento;
}

[$estadoActual, $estadoColor] = estadoExistencia($existencia, $detalle);
if ($existencia && despachoCerrado($existencia)) {
    [$estadoActual, $estadoColor] = ['Despachado', 'dark'];
} elseif ($existencia && procesoCerrado($existencia)) {
    [$estadoActual, $estadoColor] = ['Procesado', 'success'];
} elseif (isset($eventosPorTipo['EN_PROCESO'])) {
    [$estadoActual, $estadoColor] = estadoDesdeEvento('EN_PROCESO');
} elseif ($detalle) {
    [$estadoActual, $estadoColor] = estadoExistencia($existencia, $detalle);
} elseif (isset($eventosPorTipo['PREFRIO'])) {
    [$estadoActual, $estadoColor] = estadoDesdeEvento('PREFRIO');
} elseif (isset($eventosPorTipo['ARMADO_CAMPO'])) {
    [$estadoActual, $estadoColor] = estadoDesdeEvento('ARMADO_CAMPO');
}

$timeline = [
    [
        'tipo' => 'ARMADO_CAMPO',
        'nombre' => 'Armado campo',
        'fecha' => $eventosPorTipo['ARMADO_CAMPO']['FECHA_HORA'] ?? '',
        'usuario' => $eventosPorTipo['ARMADO_CAMPO']['NOMBRE_USUARIO'] ?? '',
    ],
    [
        'tipo' => 'PREFRIO',
        'nombre' => 'Pre-frio',
        'fecha' => $eventosPorTipo['PREFRIO']['FECHA_HORA'] ?? '',
        'usuario' => $eventosPorTipo['PREFRIO']['NOMBRE_USUARIO'] ?? '',
    ],
    [
        'tipo' => 'EN_RECEPCION',
        'nombre' => 'En recepcion',
        'fecha' => $detalle ? trim(($detalle['FECHA_RECEPCION'] ?? '') . ' ' . ($detalle['HORA_RECEPCION'] ?? '')) : '',
        'usuario' => '',
    ],
    [
        'tipo' => 'RECEPCIONADO',
        'nombre' => 'Recepcionado',
        'fecha' => ($detalle && (int) $detalle['R_ESTADO'] === 0) ? trim(($detalle['FECHA_RECEPCION'] ?? '') . ' ' . ($detalle['HORA_RECEPCION'] ?? '')) : '',
        'usuario' => '',
    ],
    [
        'tipo' => 'EN_PROCESO',
        'nombre' => 'En proceso',
        'fecha' => $eventosPorTipo['EN_PROCESO']['FECHA_HORA'] ?? '',
        'usuario' => $eventosPorTipo['EN_PROCESO']['NOMBRE_USUARIO'] ?? '',
    ],
    [
        'tipo' => 'PROCESADO',
        'nombre' => 'Procesado',
        'fecha' => fechaProcesoCerrado($existencia),
        'usuario' => '',
    ],
];

$fechaDespacho = fechaDespachoCerrado($existencia);
if ($fechaDespacho !== '') {
    $timeline[] = [
        'tipo' => 'DESPACHADO',
        'nombre' => 'Despachado',
        'fecha' => $fechaDespacho,
        'usuario' => '',
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Info Pallet <?php echo h($f); ?></title>
    <style>
        :root { --ink:#172033; --muted:#667085; --line:#e5e7eb; --bg:#f6f8fb; --brand:#1167b1; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, Helvetica, sans-serif; background:var(--bg); color:var(--ink); }
        .wrap { max-width: 860px; margin: 0 auto; padding: 18px; }
        .panel { background:#fff; border:1px solid var(--line); border-radius:8px; box-shadow:0 8px 24px rgba(16,24,40,.06); overflow:hidden; }
        .head { padding:18px; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .title { margin:0; font-size:24px; }
        .sub { color:var(--muted); margin-top:4px; font-size:14px; }
        .badge { display:inline-block; padding:7px 10px; border-radius:6px; color:#fff; font-weight:700; font-size:13px; }
        .secondary { background:#667085; } .primary { background:#1167b1; } .success { background:#16803c; }
        .warning { background:#b7791f; } .dark { background:#344054; } .danger { background:#c0392b; }
        .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; padding:18px; border-bottom:1px solid var(--line); }
        .dato { border:1px solid var(--line); border-radius:6px; padding:12px; min-height:72px; }
        .label { color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
        .value { margin-top:6px; font-size:18px; font-weight:700; overflow-wrap:anywhere; }
        .section { padding:18px; border-bottom:1px solid var(--line); }
        .section:last-child { border-bottom:0; }
        .section h2 { margin:0 0 12px; font-size:18px; }
        .timeline { list-style:none; margin:0; padding:0; }
        .timeline li { display:flex; gap:10px; padding:9px 0; border-bottom:1px dashed var(--line); }
        .timeline li:last-child { border-bottom:0; }
        .dot { width:12px; height:12px; border-radius:50%; margin-top:3px; border:2px solid var(--brand); flex:0 0 auto; }
        .done .dot { background:var(--brand); }
        .event { font-weight:700; }
        .time { color:var(--muted); font-size:13px; margin-top:2px; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn { border:1px solid var(--brand); color:var(--brand); background:#fff; padding:10px 12px; border-radius:6px; text-decoration:none; font-weight:700; cursor:pointer; }
        .btn.primary-btn { background:var(--brand); color:#fff; }
        .notice { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; padding:12px; border-radius:6px; margin:18px; }
        .ok { background:#ecfdf3; color:#027a48; border-color:#abefc6; }
        .event-form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .event-form input { flex:1 1 240px; border:1px solid var(--line); border-radius:6px; padding:10px 12px; }
        .userbar { margin-top:8px; color:var(--muted); font-size:13px; }
        .modal-overlay { position:fixed; inset:0; background:rgba(17,24,39,.58); display:none; align-items:center; justify-content:center; padding:18px; z-index:10; }
        .modal-overlay.open { display:flex; }
        .modal { width:min(380px, 100%); background:#fff; border-radius:8px; border:1px solid var(--line); box-shadow:0 20px 45px rgba(16,24,40,.22); padding:18px; }
        .modal-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; }
        .modal h3 { margin:0; font-size:18px; }
        .close { width:34px; height:34px; border:1px solid var(--line); border-radius:6px; background:#fff; cursor:pointer; font-size:20px; line-height:1; }
        .login-form { display:grid; gap:10px; }
        .login-form input { width:100%; border:1px solid var(--line); border-radius:6px; padding:11px 12px; font-size:15px; }
        @media (max-width: 720px) { .grid { grid-template-columns:1fr; } .title { font-size:21px; } }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="panel">
            <div class="head">
                <div>
                    <h1 class="title">Pallet / Folio <?php echo h($f ?: 'sin folio'); ?></h1>
                    <div class="sub">QR: P=<?php echo h($p); ?> | V=<?php echo h($v); ?> | F=<?php echo h($f); ?></div>
                </div>
                <div><span class="badge <?php echo h($estadoColor); ?>"><?php echo h($estadoActual); ?></span></div>
            </div>

            <?php if ($error !== '') { ?>
                <div class="notice"><?php echo h($error); ?></div>
            <?php } elseif ($p === '' || $v === '' || $f === '') { ?>
                <div class="notice">Faltan parametros del QR. Use formato: infoPallet.php?P=153306&V=31&F=12345</div>
            <?php } ?>
            <?php if ($mensajeEvento !== '') { ?>
                <div class="notice"><?php echo h($mensajeEvento); ?></div>
            <?php } elseif (isset($_GET['evento']) && $_GET['evento'] === 'ok') { ?>
                <div class="notice ok">Evento registrado correctamente.</div>
            <?php } ?>
            <?php if ($mensajeLogin !== '') { ?>
                <div class="notice"><?php echo h($mensajeLogin); ?></div>
            <?php } elseif (isset($_GET['login']) && $_GET['login'] === 'ok') { ?>
                <div class="notice ok">Sesion iniciada correctamente.</div>
            <?php } ?>

            <div class="grid">
                <div class="dato">
                    <div class="label">Productor</div>
                    <div class="value"><?php echo h($nombreProductor); ?></div>
                </div>
                <div class="dato">
                    <div class="label">Variedad</div>
                    <div class="value"><?php echo h($nombreVariedad); ?></div>
                </div>
                <div class="dato">
                    <div class="label">Recepcion</div>
                    <div class="value"><?php echo h($detalle['NUMERO_RECEPCION'] ?? 'Pendiente'); ?></div>
                </div>
                <div class="dato">
                    <div class="label">Kilos netos</div>
                    <div class="value"><?php echo $kilos !== '' ? h(number_format((float) $kilos, 2, ',', '.')) . ' kg' : 'Pendiente'; ?></div>
                </div>
                <div class="dato">
                    <div class="label">Envases</div>
                    <div class="value"><?php echo $envases !== '' ? h($envases) : 'Pendiente'; ?></div>
                </div>
                <div class="dato">
                    <div class="label">ID detalle</div>
                    <div class="value"><?php echo h($detalle['ID_DRECEPCION'] ?? 'Pendiente'); ?></div>
                </div>
            </div>

            <section class="section">
                <h2>Timeline</h2>
                <ul class="timeline">
                    <?php foreach ($timeline as $item) { ?>
                        <li class="<?php echo $item['fecha'] !== '' ? 'done' : ''; ?>">
                            <span class="dot"></span>
                            <div>
                                <div class="event"><?php echo h($item['nombre']); ?></div>
                                <div class="time">
                                    <?php echo $item['fecha'] !== '' ? h(formatearFechaHora($item['fecha'])) : 'Pendiente'; ?>
                                    <?php echo $item['usuario'] !== '' ? ' - ' . h($item['usuario']) : ''; ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </section>

            <section class="section">
                <h2>Acciones operativas</h2>
                <?php if (usuarioLogueado()) { ?>
                    <div class="userbar">Sesion activa: <?php echo h($_SESSION['NOMBRE_USUARIO']); ?></div>
                    <form method="post" class="event-form">
                        <input type="text" name="OBSERVACION_EVENTO" placeholder="Observacion opcional">
                        <button class="btn" type="submit" name="EVENTO_PALLET" value="ARMADO_CAMPO">Marcar armado</button>
                        <button class="btn" type="submit" name="EVENTO_PALLET" value="PREFRIO">Marcar pre-frio</button>
                        <button class="btn primary-btn" type="submit" name="EVENTO_PALLET" value="EN_PROCESO">Marcar en proceso</button>
                    </form>
                <?php } else { ?>
                    <p class="sub">Vista publica solo lectura. Para registrar eventos debe iniciar sesion en Smartberries.</p>
                    <div class="actions">
                        <button class="btn primary-btn" type="button" id="abrirLogin">Iniciar sesion</button>
                    </div>
                <?php } ?>
            </section>

            <section class="section">
                <h2>Control de calidad</h2>
                <p class="sub">Modulo pendiente. Aqui se mostraran defectos, condicion, temperatura, calibre, color, brix y firmeza.</p>
                <div class="actions">
                    <a class="btn" href="#">Ver control de calidad</a>
                </div>
            </section>
        </div>
    </main>
    <div class="modal-overlay <?php echo $mensajeLogin !== '' ? 'open' : ''; ?>" id="modalLogin" aria-hidden="<?php echo $mensajeLogin !== '' ? 'false' : 'true'; ?>">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="tituloLogin">
            <div class="modal-head">
                <h3 id="tituloLogin">Iniciar sesion</h3>
                <button class="close" type="button" id="cerrarLogin" aria-label="Cerrar">&times;</button>
            </div>
            <form method="post" class="login-form">
                <input type="text" name="NOMBRE_LOGIN" placeholder="Usuario" autocomplete="username" required>
                <input type="password" name="CONTRASENA_LOGIN" placeholder="Contrasena" autocomplete="current-password" required>
                <button class="btn primary-btn" type="submit" name="LOGIN_PALLET" value="1">Entrar</button>
            </form>
        </div>
    </div>
    <script>
        (function () {
            var modal = document.getElementById('modalLogin');
            var abrir = document.getElementById('abrirLogin');
            var cerrar = document.getElementById('cerrarLogin');

            function mostrarLogin() {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                var campo = modal.querySelector('input[name="NOMBRE_LOGIN"]');
                if (campo) {
                    campo.focus();
                }
            }

            function ocultarLogin() {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }

            if (abrir) {
                abrir.addEventListener('click', mostrarLogin);
            }
            if (cerrar) {
                cerrar.addEventListener('click', ocultarLogin);
            }
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    ocultarLogin();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    ocultarLogin();
                }
            });
        })();
    </script>
</body>
</html>
