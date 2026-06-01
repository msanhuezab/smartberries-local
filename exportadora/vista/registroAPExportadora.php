<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";
include_once '../../assest/controlador/MAPERTURA_ADO.php';
include_once '../../assest/modelo/MAPERTURA.php';

$db = BDCONFIG::conectar();
$MAPERTURA_ADO = new MAPERTURA_ADO();
$MAPERTURA = new MAPERTURA();

$MODULO = $_REQUEST['MODULO'] ?? '';
$ID = isset($_REQUEST['ID']) ? (int)$_REQUEST['ID'] : 0;
$NUMERO = $_REQUEST['NUMERO'] ?? '';
$ICARGA = isset($_REQUEST['ICARGA']) ? (int)$_REQUEST['ICARGA'] : 0;
$RETORNO = $_REQUEST['RETORNO'] ?? 'index';
$TITULO = $MODULO === 'INVOICE' ? 'Invoice Exportadora' : 'Liquidacion Exportadora';
$mensaje = '';
$mensajeOk = '';
$motivoActual = trim($_POST['MOTIVO'] ?? '');

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function volverUrl($retorno, $icarga) {
    $url = $retorno . '.php';
    if ($icarga > 0) {
        $url .= '?ICARGA=' . (int)$icarga;
    }
    return $url;
}

function generarCodigoAutorizacionExportadora()
{
    if (function_exists('random_int')) {
        return random_int(100000, 999999);
    }
    return mt_rand(100000, 999999);
}

function obtenerDestinatariosAutorizacionExportadora($correoSolicitante)
{
    $correosBase = ['maperez@fvolcan.cl', 'eisla@fvolcan.cl', 'msanhueza@fvolcan.cl'];
    $correoSolicitante = trim((string)$correoSolicitante);

    if ($correoSolicitante !== '') {
        $correosBase = array_filter($correosBase, function ($correo) use ($correoSolicitante) {
            return strcasecmp($correo, $correoSolicitante) !== 0;
        });
    }

    return array_values(array_filter(array_unique($correosBase)));
}

function enviarCorreoSMTPExportadora($destinatarios, $asunto, $mensaje, $remitente, $usuario, $contrasena, $host, $puerto, $timeout = 30)
{
    $destinatarios = array_values(array_filter((array)$destinatarios));
    if (!$destinatarios) {
        return [false, 'No hay destinatarios configurados para enviar el codigo.'];
    }

    $contextoSSL = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        ]
    ]);

    $conexion = @stream_socket_client("ssl://{$host}:{$puerto}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $contextoSSL);
    if (!$conexion) {
        return [false, "No se pudo conectar al servidor SMTP ({$errstr})"];
    }

    if (function_exists('stream_set_timeout')) {
        stream_set_timeout($conexion, $timeout);
    }

    $leerRespuesta = function () use ($conexion) {
        $respuesta = '';
        while ($linea = fgets($conexion, 515)) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }
        return $respuesta;
    };

    $comando = function ($instruccion, $codigoEsperado) use ($conexion, $leerRespuesta) {
        fwrite($conexion, $instruccion . "\r\n");
        $respuesta = $leerRespuesta();
        if (substr($respuesta, 0, 3) !== $codigoEsperado) {
            throw new Exception("Error SMTP en '{$instruccion}': {$respuesta}");
        }
        return $respuesta;
    };

    $respuestaInicial = $leerRespuesta();
    if (substr($respuestaInicial, 0, 3) !== '220') {
        fclose($conexion);
        return [false, "El servidor SMTP no respondio correctamente: {$respuestaInicial}"];
    }

    $hostEhlo = $host ?: 'localhost';
    try {
        $comando('EHLO ' . $hostEhlo, '250');
    } catch (Exception $e) {
        $comando('HELO ' . $hostEhlo, '250');
    }

    try {
        $comando('AUTH LOGIN', '334');
        $comando(base64_encode($usuario), '334');
        $comando(base64_encode($contrasena), '235');
        $comando("MAIL FROM:<{$remitente}>", '250');
        foreach ($destinatarios as $correo) {
            $comando("RCPT TO:<{$correo}>", '250');
        }
        $comando('DATA', '354');

        $cabeceras = "Date: " . date('r') . "\r\n" .
            "Message-ID: <" . uniqid() . "@" . ($hostEhlo ?: 'localhost') . ">\r\n" .
            "From: {$remitente}\r\n" .
            "Return-Path: {$remitente}\r\n" .
            "Reply-To: {$remitente}\r\n" .
            "To: " . implode(', ', $destinatarios) . "\r\n" .
            "Subject: {$asunto}\r\n" .
            "MIME-Version: 1.0\r\n" .
            "X-Mailer: PHP/" . phpversion() . "\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n\r\n";

        $mensajeNormalizado = str_replace(["\r\n", "\n"], "\r\n", $mensaje);
        fwrite($conexion, $cabeceras . $mensajeNormalizado . "\r\n.\r\n");
        $respuestaData = $leerRespuesta();
        if (substr($respuestaData, 0, 3) !== '250') {
            throw new Exception("Error SMTP tras DATA: {$respuestaData}");
        }
        $comando('QUIT', '221');
    } catch (Exception $e) {
        fclose($conexion);
        return [false, "Error al enviar correo: " . $e->getMessage()];
    }

    fclose($conexion);
    return [true, null];
}

function obtenerSolicitanteExportadora($db, $idUsuario)
{
    $datos = [
        'correo' => '',
        'nombre' => $_SESSION['NOMBRE_USUARIO'] ?? '',
    ];

    if (!$db || !$idUsuario) {
        return $datos;
    }

    $stmt = $db->prepare("
        SELECT EMAIL_USUARIO, PNOMBRE_USUARIO, SNOMBRE_USUARIO, PAPELLIDO_USUARIO, SAPELLIDO_USUARIO
        FROM usuario_usuario
        WHERE ID_USUARIO = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$idUsuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usuario) {
        $datos['correo'] = trim($usuario['EMAIL_USUARIO'] ?? '');
        $nombre = trim(
            ($usuario['PNOMBRE_USUARIO'] ?? '') . ' ' .
            ($usuario['SNOMBRE_USUARIO'] ?? '') . ' ' .
            ($usuario['PAPELLIDO_USUARIO'] ?? '') . ' ' .
            ($usuario['SAPELLIDO_USUARIO'] ?? '')
        );
        $datos['nombre'] = $nombre !== '' ? $nombre : $datos['nombre'];
    }

    return $datos;
}

function limpiarSesionReaperturaExportadora()
{
    unset($_SESSION['EXPORTADORA_REAPERTURA_CODIGO']);
    unset($_SESSION['EXPORTADORA_REAPERTURA_ID']);
    unset($_SESSION['EXPORTADORA_REAPERTURA_MODULO']);
    unset($_SESSION['EXPORTADORA_REAPERTURA_TIEMPO']);
}

$solicitante = obtenerSolicitanteExportadora($db, $IDUSUARIOS ?? 0);
$correoUsuario = $solicitante['correo'];
$nombreUsuario = $solicitante['nombre'];

if (isset($_POST['SOLICITAR_CODIGO'])) {
    $motivo = $motivoActual;
    if ($motivo === '') {
        $mensaje = 'Debe ingresar un motivo de apertura.';
    } elseif (!in_array($MODULO, ['INVOICE', 'LIQUIDACION'], true) || $ID <= 0) {
        $mensaje = 'No fue posible identificar el registro a reabrir.';
    } else {
        $codigoAutorizacion = generarCodigoAutorizacionExportadora();
        $_SESSION['EXPORTADORA_REAPERTURA_CODIGO'] = $codigoAutorizacion;
        $_SESSION['EXPORTADORA_REAPERTURA_ID'] = $ID;
        $_SESSION['EXPORTADORA_REAPERTURA_MODULO'] = $MODULO;
        $_SESSION['EXPORTADORA_REAPERTURA_TIEMPO'] = time();

        $destinatarios = obtenerDestinatariosAutorizacionExportadora($correoUsuario);
        $asunto = 'Autorizacion reapertura ' . $TITULO . ' ' . $NUMERO;
        $mensajeCorreo = "Se solicito la reapertura de un registro de exportadora." . "\r\n\r\n" .
            "Modulo: " . $TITULO . "\r\n" .
            "Registro: " . $NUMERO . "\r\n" .
            "ID interno: " . $ID . "\r\n" .
            "Motivo: " . $motivo . "\r\n" .
            "Solicitado por: " . $nombreUsuario . "\r\n" .
            "Codigo de autorizacion: " . $codigoAutorizacion . "\r\n\r\n" .
            "El codigo tiene validez de 15 minutos.";

        [$envioOk, $errorEnvio] = enviarCorreoSMTPExportadora(
            $destinatarios,
            $asunto,
            $mensajeCorreo,
            'informes@volcanfoods.cl',
            'informes@volcanfoods.cl',
            '1z=EWfu0026k',
            'mail.volcanfoods.cl',
            465
        );

        if ($envioOk) {
            $mensajeOk = 'Codigo de autorizacion enviado correctamente. Ingrese el codigo recibido para confirmar la reapertura.';
        } else {
            limpiarSesionReaperturaExportadora();
            $mensaje = $errorEnvio ?: 'No fue posible enviar el correo de autorizacion.';
        }
    }
}

if (isset($_POST['CONFIRMAR_REAPERTURA'])) {
    $motivo = $motivoActual;
    $codigoIngresado = trim($_POST['CODIGO_APERTURA'] ?? '');
    $codigoSesion = $_SESSION['EXPORTADORA_REAPERTURA_CODIGO'] ?? null;
    $idSesion = $_SESSION['EXPORTADORA_REAPERTURA_ID'] ?? null;
    $moduloSesion = $_SESSION['EXPORTADORA_REAPERTURA_MODULO'] ?? null;
    $tiempoSesion = $_SESSION['EXPORTADORA_REAPERTURA_TIEMPO'] ?? 0;

    if ($motivo === '') {
        $mensaje = 'Debe ingresar un motivo de apertura.';
    } elseif (!in_array($MODULO, ['INVOICE', 'LIQUIDACION'], true) || $ID <= 0) {
        $mensaje = 'No fue posible identificar el registro a reabrir.';
    } elseif (!$codigoSesion || !$idSesion || !$moduloSesion || (int)$idSesion !== $ID || $moduloSesion !== $MODULO) {
        $mensaje = 'No hay una solicitud de reapertura vigente para este registro.';
    } elseif ((time() - (int)$tiempoSesion) > 900) {
        limpiarSesionReaperturaExportadora();
        $mensaje = 'El codigo de autorizacion ha expirado.';
    } elseif ($codigoIngresado === '' || (string)$codigoSesion !== $codigoIngresado) {
        $mensaje = 'El codigo ingresado no es valido.';
    } elseif ($MODULO === 'INVOICE') {
        $MAPERTURA->__SET('MOTIVO_MAPERTURA', $motivo);
        $MAPERTURA->__SET('TABLA', 'exportadora_invoice');
        $MAPERTURA->__SET('ID_REGISTRO', $ID);
        $MAPERTURA->__SET('ID_USUARIO', $IDUSUARIOS);
        $MAPERTURA_ADO->agregarMapertura($MAPERTURA);

        $stmt = $db->prepare("
            UPDATE exportadora_invoice
            SET ESTADO_INVOICE = 'REABIERTA',
                ID_USUARIOM = ?,
                MODIFICACION = SYSDATE()
            WHERE ID_INVOICE = ?
            AND ESTADO_REGISTRO = 1
        ");
        $stmt->execute([$IDUSUARIOS, $ID]);

        $destinatarios = obtenerDestinatariosAutorizacionExportadora($correoUsuario);
        $asunto = 'Confirmacion reapertura ' . $TITULO . ' ' . $NUMERO;
        $mensajeCorreo = "Se confirmo la reapertura de un registro de exportadora." . "\r\n\r\n" .
            "Modulo: " . $TITULO . "\r\n" .
            "Registro: " . $NUMERO . "\r\n" .
            "ID interno: " . $ID . "\r\n" .
            "Motivo: " . $motivo . "\r\n" .
            "Confirmado por: " . $nombreUsuario . "\r\n\r\n" .
            "El registro quedo en estado reabierto.";
        enviarCorreoSMTPExportadora($destinatarios, $asunto, $mensajeCorreo, 'informes@volcanfoods.cl', 'informes@volcanfoods.cl', '1z=EWfu0026k', 'mail.volcanfoods.cl', 465);
        if ($correoUsuario !== '') {
            enviarCorreoSMTPExportadora($correoUsuario, $asunto, $mensajeCorreo, 'informes@volcanfoods.cl', 'informes@volcanfoods.cl', '1z=EWfu0026k', 'mail.volcanfoods.cl', 465);
        }
        limpiarSesionReaperturaExportadora();
        echo "<script>location.href='" . volverUrl($RETORNO, $ICARGA) . "';</script>";
        exit;
    } elseif ($MODULO === 'LIQUIDACION') {
        $MAPERTURA->__SET('MOTIVO_MAPERTURA', $motivo);
        $MAPERTURA->__SET('TABLA', 'liquidacion_valor');
        $MAPERTURA->__SET('ID_REGISTRO', $ID);
        $MAPERTURA->__SET('ID_USUARIO', $IDUSUARIOS);
        $MAPERTURA_ADO->agregarMapertura($MAPERTURA);

        $stmt = $db->prepare("
            UPDATE liquidacion_valor
            SET ESTADO_LIQUIDACION = 'REABIERTA',
                ID_USUARIOM = ?,
                MODIFICACION = SYSDATE()
            WHERE ID_VALOR = ?
            AND ESTADO_REGISTRO = 1
        ");
        $stmt->execute([$IDUSUARIOS, $ID]);

        $destinatarios = obtenerDestinatariosAutorizacionExportadora($correoUsuario);
        $asunto = 'Confirmacion reapertura ' . $TITULO . ' ' . $NUMERO;
        $mensajeCorreo = "Se confirmo la reapertura de un registro de exportadora." . "\r\n\r\n" .
            "Modulo: " . $TITULO . "\r\n" .
            "Registro: " . $NUMERO . "\r\n" .
            "ID interno: " . $ID . "\r\n" .
            "Motivo: " . $motivo . "\r\n" .
            "Confirmado por: " . $nombreUsuario . "\r\n\r\n" .
            "El registro quedo en estado reabierto.";
        enviarCorreoSMTPExportadora($destinatarios, $asunto, $mensajeCorreo, 'informes@volcanfoods.cl', 'informes@volcanfoods.cl', '1z=EWfu0026k', 'mail.volcanfoods.cl', 465);
        if ($correoUsuario !== '') {
            enviarCorreoSMTPExportadora($correoUsuario, $asunto, $mensajeCorreo, 'informes@volcanfoods.cl', 'informes@volcanfoods.cl', '1z=EWfu0026k', 'mail.volcanfoods.cl', 465);
        }
        limpiarSesionReaperturaExportadora();
        echo "<script>location.href='" . volverUrl($RETORNO, $ICARGA) . "';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Reapertura Exportadora</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <script type="text/javascript">
        function validarMotivo() {
            var motivo = document.getElementById('MOTIVO').value;
            if (!motivo || /^\s+$/.test(motivo)) {
                document.getElementById('val_motivo').innerHTML = 'NO A INGRESADO DATO';
                document.getElementById('MOTIVO').focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
<div class="wrapper">
    <?php include_once "../../assest/config/menuExpo.php"; ?>
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="mr-auto">
                        <h3 class="page-title">Reapertura Registro</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item">Exportadora</li>
                                    <li class="breadcrumb-item active"><?php echo h($TITULO); ?></li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>
            <section class="content">
                <?php if ($mensaje !== '') { ?><div class="alert alert-warning"><?php echo h($mensaje); ?></div><?php } ?>
                <?php if ($mensajeOk !== '') { ?><div class="alert alert-success"><?php echo h($mensajeOk); ?></div><?php } ?>
                <div class="box">
                    <div class="box-header with-border bg-warning">
                        <h4 class="box-title">Registro Motivo Reapertura</h4>
                    </div>
                    <form method="post" onsubmit="return validarMotivo()">
                        <div class="box-body">
                            <input type="hidden" name="MODULO" value="<?php echo h($MODULO); ?>">
                            <input type="hidden" name="ID" value="<?php echo (int)$ID; ?>">
                            <input type="hidden" name="ICARGA" value="<?php echo (int)$ICARGA; ?>">
                            <input type="hidden" name="RETORNO" value="<?php echo h($RETORNO); ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Registro</label>
                                    <input type="text" class="form-control" value="<?php echo h($NUMERO); ?>" readonly>
                                    <input type="hidden" name="NUMERO" value="<?php echo h($NUMERO); ?>">
                                </div>
                            </div>
                            <div class="row mt-10">
                                <div class="col-md-12">
                                    <label>Motivo</label>
                                    <textarea class="form-control" rows="3" id="MOTIVO" name="MOTIVO"><?php echo h($motivoActual); ?></textarea>
                                    <label id="val_motivo" class="validacion"></label>
                                </div>
                            </div>
                            <div class="row mt-10">
                                <div class="col-md-4">
                                    <label>Codigo autorizacion</label>
                                    <input type="text" class="form-control" name="CODIGO_APERTURA" maxlength="6" autocomplete="off">
                                    <small>Solicite el codigo por correo antes de confirmar.</small>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a class="btn btn-success" href="<?php echo h(volverUrl($RETORNO, $ICARGA)); ?>"><i class="ti-back-left"></i> Volver</a>
                            <button type="submit" class="btn btn-info" name="SOLICITAR_CODIGO" value="SOLICITAR_CODIGO">
                                <i class="ti-email"></i> Solicitar codigo
                            </button>
                            <button type="submit" class="btn btn-warning" name="CONFIRMAR_REAPERTURA" value="CONFIRMAR_REAPERTURA">
                                <i class="ti-save-alt"></i> Confirmar reapertura
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>
</body>
</html>
