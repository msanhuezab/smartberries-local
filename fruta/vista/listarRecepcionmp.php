<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "includes/reporteRecepcionGranel.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES¿

include_once '../../assest/controlador/RECEPCIONMP_ADO.php';
include_once '../../assest/controlador/DRECEPCIONMP_ADO.php';

include_once '../../assest/controlador/CONDUCTOR_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/TRANSPORTE_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/EXIMATERIAPRIMA_ADO.php';
include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/PLANTA_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';
include_once '../../assest/modelo/RECEPCIONMP.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR¿

$RECEPCIONMP_ADO =  new RECEPCIONMP_ADO();
$DRECEPCIONMP_ADO =  new DRECEPCIONMP_ADO();

$PRODUCTOR_ADO =  new PRODUCTOR_ADO();
$TRANSPORTE_ADO =  new TRANSPORTE_ADO();
$CONDUCTOR_ADO =  new CONDUCTOR_ADO();
$PRODUCTOR_ADO =  new PRODUCTOR_ADO();
$EXIMATERIAPRIMA_ADO = new EXIMATERIAPRIMA_ADO();
$USUARIO_ADO = new USUARIO_ADO();
$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
$TEMPORADA_ADO = new TEMPORADA_ADO();
$RECEPCIONMP = new RECEPCIONMP();


//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD

$EMPRESA = "";
$PLANTA = "";
$TEMPORADA = "";

$TOTALGUIA = "";
$TOTALBRUTO = "";
$TOTALNETO = "";
$TOTALENVASE = "";
$MENSAJE = "";
$MENSAJEENVIO = "";

$CORREOUSUARIO = "";
$NOMBRECOMPLETOUSUARIO = $_SESSION['NOMBRE_USUARIO'] ?? '';

$FECHADESDE = "";
$FECHAHASTA = "";
$PRODUCTOR = "";

$NUMEROGUIA = "";

//INICIALIZAR ARREGLOS
$ARRAYRECEPCION = "";
$ARRAYRECEPCIONTOTALES = "";
$ARRAYVEREMPRESA = "";
$ARRAYVERPRODUCTOR = "";
$ARRAYVERTRANSPORTE = "";
$ARRAYVERCONDUCTOR = "";
$ARRAYFECHA = "";
$ARRAYPRODUCTOR = "";
$ARRAYUSUARIO = $USUARIO_ADO->verUsuario($_SESSION["ID_USUARIO"]);
if ($ARRAYUSUARIO) {
    $CORREOUSUARIO = trim($ARRAYUSUARIO[0]['EMAIL_USUARIO']);
    $NOMBRECOMPLETOUSUARIO = trim(
        ($ARRAYUSUARIO[0]['PNOMBRE_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['SNOMBRE_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['PAPELLIDO_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['SAPELLIDO_USUARIO'] ?? '')
    );
    $NOMBRECOMPLETOUSUARIO = trim($NOMBRECOMPLETOUSUARIO) ?: ($_SESSION['NOMBRE_USUARIO'] ?? '');
}

function generarCodigoAutorizacion()
{
    if (function_exists('random_int')) {
        return random_int(100000, 999999);
    }

    return mt_rand(100000, 999999);
}

function obtenerDestinatariosAutorizacion($correoSolicitante)
{
    $correosBase = ['maperez@fvolcan.cl', 'eisla@fvolcan.cl', 'msanhueza@fvolcan.cl'];
    $correoSolicitante = trim((string) $correoSolicitante);

    if ($correoSolicitante !== '') {
        $correosBase = array_filter(
            $correosBase,
            fn($correo) => strcasecmp($correo, $correoSolicitante) !== 0
        );
    }

    return array_values(array_filter(array_unique($correosBase)));
}

function enviarCorreoSMTP($destinatarios, $asunto, $mensaje, $remitente, $usuario, $contrasena, $host, $puerto, $timeout = 30)
{
    $destinatarios = (array) $destinatarios;
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
        return [false, "El servidor SMTP no respondió correctamente: {$respuestaInicial}"];
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
    } catch (Exception $e) {
        fclose($conexion);
        return [false, "Error de autenticación SMTP: " . $e->getMessage()];
    }

    try {
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

function obtenerDatosCorreoRecepcion($recepcion, $PRODUCTOR_ADO, $PLANTA_ADO, $EMPRESA_ADO, $TEMPORADA_ADO)
{
    $numero = $recepcion['NUMERO_RECEPCION'] ?? 'Sin datos';
    $fecha = $recepcion['FECHA'] ?? ($recepcion['FECHA_RECEPCION'] ?? 'Sin datos');
    $empresa = 'Sin datos';
    $planta = 'Sin datos';
    $temporada = 'Sin datos';
    $origen = 'Sin datos';

    if (!empty($recepcion['ID_EMPRESA'])) {
        $empresaData = $EMPRESA_ADO->verEmpresa($recepcion['ID_EMPRESA']);
        if ($empresaData) {
            $empresa = $empresaData[0]['NOMBRE_EMPRESA'];
        }
    }

    if (!empty($recepcion['ID_PLANTA'])) {
        $plantaData = $PLANTA_ADO->verPlanta($recepcion['ID_PLANTA']);
        if ($plantaData) {
            $planta = $plantaData[0]['NOMBRE_PLANTA'];
        }
    }

    if (!empty($recepcion['ID_TEMPORADA'])) {
        $temporadaData = $TEMPORADA_ADO->verTemporada($recepcion['ID_TEMPORADA']);
        if ($temporadaData) {
            $temporada = $temporadaData[0]['NOMBRE_TEMPORADA'];
        }
    }

    if (!empty($recepcion['TRECEPCION'])) {
        if ($recepcion['TRECEPCION'] == "1" || $recepcion['TRECEPCION'] == "3") {
            $productor = $PRODUCTOR_ADO->verProductor($recepcion['ID_PRODUCTOR'] ?? null);
            $origen = $productor ? $productor[0]['NOMBRE_PRODUCTOR'] : 'Sin datos';
        } elseif ($recepcion['TRECEPCION'] == "2") {
            $plantaOrigen = $PLANTA_ADO->verPlanta($recepcion['ID_PLANTA2'] ?? null);
            $origen = $plantaOrigen ? $plantaOrigen[0]['NOMBRE_PLANTA'] : 'Sin datos';
        }
    }

    return [
        'numero' => $numero,
        'fecha' => $fecha,
        'empresa' => $empresa,
        'planta' => $planta,
        'temporada' => $temporada,
        'origen' => $origen,
    ];
}
$ARRAYUSUARIO = $USUARIO_ADO->verUsuario($_SESSION["ID_USUARIO"]);
if ($ARRAYUSUARIO) {
    $CORREOUSUARIO = trim($ARRAYUSUARIO[0]['EMAIL_USUARIO']);
    $NOMBRECOMPLETOUSUARIO = trim(
        ($ARRAYUSUARIO[0]['PNOMBRE_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['SNOMBRE_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['PAPELLIDO_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['SAPELLIDO_USUARIO'] ?? '')
    );
    $NOMBRECOMPLETOUSUARIO = trim($NOMBRECOMPLETOUSUARIO) ?: ($_SESSION['NOMBRE_USUARIO'] ?? '');
}

//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES


include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";

if ($_POST) {
    $IDRECEPCION = $_REQUEST['ID'] ?? null;
    $CODIGOCIERRE = $_REQUEST['CODIGO_CERRAR'] ?? '';
    $CODIGOAPERTURA = $_REQUEST['CODIGO_ABRIR'] ?? '';

    $detalleRecepcion = $IDRECEPCION ? $RECEPCIONMP_ADO->verRecepcion2($IDRECEPCION) : [];
    $datosRecepcion = $detalleRecepcion ? $detalleRecepcion[0] : [];
    $datosCorreo = obtenerDatosCorreoRecepcion($datosRecepcion, $PRODUCTOR_ADO, $PLANTA_ADO, $EMPRESA_ADO, $TEMPORADA_ADO);

    if (isset($_REQUEST['SOLICITAR_CERRAR'])) {
        $foliosActivos = $IDRECEPCION ? $EXIMATERIAPRIMA_ADO->buscarPorRecepcionIngresado($IDRECEPCION) : [];
        $detallesActivos = $IDRECEPCION ? $DRECEPCIONMP_ADO->listarDrecepcionPorRecepcion($IDRECEPCION) : [];

        if ($foliosActivos || $detallesActivos) {
            unset($_SESSION['RECEPCION_CERRAR_CODIGO'], $_SESSION['RECEPCION_CERRAR_ID'], $_SESSION['RECEPCION_CERRAR_TIEMPO']);
            $MENSAJEENVIO = '';
            $MENSAJE = "La recepción tiene registros de materia prima asociados y no se envió código de autorización.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['RECEPCION_CERRAR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['RECEPCION_CERRAR_ID'] = $IDRECEPCION;
            $_SESSION['RECEPCION_CERRAR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización eliminación recepción #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó eliminar una recepción de materia prima." . "\r\n\r\n" .
                "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
                "Origen: " . $datosCorreo['origen'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Solicitado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n" .
                "Código de autorización: " . $codigoAutorizacion . "\r\n\r\n" .
                "El código tiene validez de 15 minutos.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if ($envioOk) {
                $MENSAJEENVIO = "Código de autorización enviado correctamente a Maria de los Ángeles y Erwin Isla.";
            } else {
                $MENSAJE = $errorEnvio ?: "No fue posible enviar el correo de autorización.";
            }
        }
    }

    if (isset($_REQUEST['CONFIRMAR_CERRAR'])) {
        $codigoSesion = $_SESSION['RECEPCION_CERRAR_CODIGO'] ?? null;
        $idSesion = $_SESSION['RECEPCION_CERRAR_ID'] ?? null;
        $tiempoSesion = $_SESSION['RECEPCION_CERRAR_TIEMPO'] ?? 0;

        $foliosActivos = $IDRECEPCION ? $EXIMATERIAPRIMA_ADO->buscarPorRecepcionIngresado($IDRECEPCION) : [];
        $detallesActivos = $IDRECEPCION ? $DRECEPCIONMP_ADO->listarDrecepcionPorRecepcion($IDRECEPCION) : [];

        if ($foliosActivos || $detallesActivos) {
            $MENSAJE = "No es posible eliminar la recepción porque existen folios de materia prima habilitados.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDRECEPCION) {
            $MENSAJE = "Debe solicitar un código de autorización antes de eliminar esta recepción.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOCIERRE || $CODIGOCIERRE != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $RECEPCIONMP->__SET('ID_RECEPCION', $IDRECEPCION);
            $RECEPCIONMP_ADO->cerrado($RECEPCIONMP);
            $RECEPCIONMP_ADO->deshabilitar($RECEPCIONMP);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación eliminación recepción #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la eliminación de la recepción." . "\r\n\r\n" .
                "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
                "Origen: " . $datosCorreo['origen'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado se marcó como eliminado.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Recepción eliminada correctamente." : ($errorEnvio ?: "La recepción se eliminó pero hubo un problema al enviar la notificación.");
            unset($_SESSION['RECEPCION_CERRAR_CODIGO']);
            unset($_SESSION['RECEPCION_CERRAR_ID']);
            unset($_SESSION['RECEPCION_CERRAR_TIEMPO']);
        }
    }

    if (isset($_REQUEST['SOLICITAR_ABRIR'])) {
        $codigoAutorizacion = generarCodigoAutorizacion();
        $_SESSION['RECEPCION_ABRIR_CODIGO'] = $codigoAutorizacion;
        $_SESSION['RECEPCION_ABRIR_ID'] = $IDRECEPCION;
        $_SESSION['RECEPCION_ABRIR_TIEMPO'] = time();

        $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
        $asunto = 'Autorización apertura recepción #' . $datosCorreo['numero'];
        $mensajeCorreo = "Se solicitó abrir una recepción de materia prima." . "\r\n\r\n" .
            "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
            "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
            "Origen: " . $datosCorreo['origen'] . "\r\n" .
            "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
            "Planta: " . $datosCorreo['planta'] . "\r\n" .
            "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
            "Solicitado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n" .
            "Código de autorización: " . $codigoAutorizacion . "\r\n\r\n" .
            "El código tiene validez de 15 minutos.";

        $remitente = 'informes@volcanfoods.cl';
        $usuarioSMTP = 'informes@volcanfoods.cl';
        $contrasenaSMTP = '1z=EWfu0026k';
        $hostSMTP = 'mail.volcanfoods.cl';
        $puertoSMTP = 465;

        [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
        if ($envioOk) {
            $MENSAJEENVIO = "Código de autorización enviado correctamente a Maria de los Ángeles y Erwin Isla.";
        } else {
            $MENSAJE = $errorEnvio ?: "No fue posible enviar el correo de autorización.";
        }
    }

    if (isset($_REQUEST['CONFIRMAR_ABRIR'])) {
        $codigoSesion = $_SESSION['RECEPCION_ABRIR_CODIGO'] ?? null;
        $idSesion = $_SESSION['RECEPCION_ABRIR_ID'] ?? null;
        $tiempoSesion = $_SESSION['RECEPCION_ABRIR_TIEMPO'] ?? 0;

        if (!$codigoSesion || !$idSesion || $idSesion != $IDRECEPCION) {
            $MENSAJE = "No hay una solicitud de apertura vigente para esta recepción.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOAPERTURA || $CODIGOAPERTURA != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $RECEPCIONMP->__SET('ID_RECEPCION', $IDRECEPCION);
            $RECEPCIONMP->__SET('ESTADO', 1);
            $RECEPCIONMP_ADO->abierto($RECEPCIONMP);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación apertura recepción #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la apertura de la recepción." . "\r\n\r\n" .
                "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
                "Origen: " . $datosCorreo['origen'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado se marcó como abierto.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Recepción abierta correctamente." : ($errorEnvio ?: "La recepción se abrió pero hubo un problema al enviar la notificación.");
            unset($_SESSION['RECEPCION_ABRIR_CODIGO']);
            unset($_SESSION['RECEPCION_ABRIR_ID']);
            unset($_SESSION['RECEPCION_ABRIR_TIEMPO']);
        }
    }
}

if ($EMPRESAS  && $PLANTAS && $TEMPORADAS) {
    $ARRAYRECEPCION = listarRecepcionGranelVista('vw_recepcion_mp_listado', $EMPRESAS, $PLANTAS, $TEMPORADAS);
}








?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Agrupado Recepción</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <!- LLAMADA DE LOS ARCHIVOS NECESARIOS PARA DISEÑO Y FUNCIONES BASE DE LA VISTA -!>
        <?php include_once "../../assest/config/urlHead.php"; ?>
        <!- FUNCIONES BASES -!>
            <script type="text/javascript">
                //REDIRECCIONAR A LA PAGINA SELECIONADA



                function irPagina(url) {
                    location.href = "" + url;
                }

              
                function refrescar() {
                    document.getElementById("form_reg_dato").submit();
                }

                //FUNCION PARA ABRIR VENTANA QUE SE ENCUENTRA LA OPERACIONES DE DETALLE DE RECEPCION
                function abrirVentana(url) {
                    var opciones =
                        "'directories=no, location=no, menubar=no, scrollbars=yes, statusbar=no, tittlebar=no, width=1000, height=800'";
                    window.open(url, 'window', opciones);
                }


                function abrirPestana(url) {
                    var win = window.open(url, '_blank');
                    win.focus();
                }
            </script>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary" >
    <div class="wrapper">
        <?php include_once "../../assest/config/menuFruta.php"; 
        ?>
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <div class="container-full">
                <!-- Content Header (Page header) -->
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Granel</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Granel</li>
                                        <li class="breadcrumb-item" aria-current="page">Recepción</li>
                                        <li class="breadcrumb-item" aria-current="page">Materia Prima</li>
                                        <li class="breadcrumb-item active" aria-current="page"> <a href="#"> Agrupado Recepción </a> </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>
                <!-- Main content -->
                <section class="content">
                    <div class="box">
                        <div class="box-body">
                            <?php if ($MENSAJE) { ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $MENSAJE; ?>
                                </div>
                            <?php } ?>
                            <?php if ($MENSAJEENVIO) { ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $MENSAJEENVIO; ?>
                                </div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 col-xs-12">
                                    <div class="table-responsive">
                                        <table id="recepcionmp" class="table-hover " style="width: 100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Numero Recepción </th>
                                                    <th>Estado</th>
                                                    <th class="text-center">Administración</th>
                                                    <th class="text-center">Autorizaciones</th>
                                                    <th>Origen Recepción</th>
                                                    <th>CSG/CSP Recepción</th>
                                                    <th>Numero Guía </th>
                                                    <th>Fecha Guía </th>
                                                    <th>Fecha Recepción </th>
                                                    <th>Hora Recepción </th>
                                                    <th>Tipo Recepción</th>
                                                    <th>Total Kilos Guía</th>
                                                    <th>Cantidad Envase</th>
                                                    <th>Total Kilos Neto</th>
                                                    <th>Total Kilos Bruto</th>
                                                    <th>Fecha Ingreso</th>
                                                    <th>Fecha Modificación</th>
                                                    <th>Transporte </th>
                                                    <th>Nombre Conductor </th>
                                                    <th>Patente Camión </th>
                                                    <th>Patente Carro </th>
                                                    <th>Semana Recepción </th>
                                                    <th>Semana Guía </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYRECEPCION as $r) : ?>
                                                    <?php
                                                        $TRECEPCION = $r['TIPO_RECEPCION'];
                                                        $CSGCSPORIGEN = $r['CSGCSP_ORIGEN'];
                                                        $ORIGEN = $r['ORIGEN_RECEPCION'];
                                                        $NOMBRETRANSPORTE = $r['NOMBRE_TRANSPORTE'];
                                                        $NOMBRECONDUCTOR = $r['NOMBRE_CONDUCTOR'];
                                                    ?>
                                                    <tr class="text-center">
                                                        <td>
                                                            <a href="#" class="text-warning hover-warning">
                                                                <?php echo $r['NUMERO_RECEPCION']; ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <?php if ($r['ESTADO'] == "0") { ?>
                                                                <button type="button" class="btn btn-block btn-danger">Cerrado</button>
                                                            <?php  }  ?>
                                                            <?php if ($r['ESTADO'] == "1") { ?>
                                                                <button type="button" class="btn btn-block btn-success">Abierto</button>
                                                            <?php  }  ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <form method="post" id="form1">
                                                                <div class="list-icons d-inline-flex">
                                                                    <div class="list-icons-item dropdown">
                                                                        <button class="btn btn-secondary" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                            <i class="glyphicon glyphicon-cog"></i>
                                                                        </button>
                                                                        <div class="dropdown-menu dropdown-menu-right">
                                                                            <button class="dropdown-menu" aria-labelledby="dropdownMenuButton"></button>
                                                                            <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_RECEPCION']; ?>" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroRecepcionmp" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URLO" name="URLO" value="listarRecepcionmp" />
                                                                            <?php if ($r['ESTADO'] == "0") { ?>

                                                                                <span href="#" class="dropdown-item" data-toggle="tooltip" title="Ver">
                                                                                    <button type="submit" class="btn btn-info btn-block " id="VERURL" name="VERURL">
                                                                                        <i class="ti-eye"></i> Ver
                                                                                    </button>
                                                                                </span>
                                                                            <?php } ?>
                                                                            <?php if ($r['ESTADO'] == "1") { ?>
                                                                                <span href="#" class="dropdown-item" data-toggle="tooltip" title="Editar">
                                                                                    <button type="submit" class="btn  btn-warning btn-block" id="EDITARURL" name="EDITARURL">
                                                                                        <i class="ti-pencil-alt"></i> Editar
                                                                                    </button>
                                                                                    <!--<a class="btn  btn-warning btn-block" href="./registroRecepcionmp.php?op&id=<?php echo $r['ID_RECEPCION']; ?>&a=editar">
                                                                                        <i class="ti-pencil-alt"></i> Editar
                                                                                    </a>-->
                                                                                </span>
                                                                            <?php } ?>
                                                                            <hr>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Informe">
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" name="informe" title="Informe" Onclick="abrirPestana('../../assest/documento/informeRecepcionmp.php?parametro=<?php echo $r['ID_RECEPCION']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Informe
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Tarjas">
                                                                                <button type="button" class="btn  btn-danger btn-block" id="defecto" name="tarjas" title="Tarjas" Onclick="abrirPestana('../../assest/documento/informeTarjasRecepcionmp.php?parametro=<?php echo $r['ID_RECEPCION']; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Tarjas
                                                                                </button>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-grid gap-1">
                                                                <?php if ($r['ESTADO'] == "1") { ?>
                                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-block" data-toggle="modal" data-target="#modalCerrarRecepcion" data-id="<?php echo $r['ID_RECEPCION']; ?>" data-numero="<?php echo $r['NUMERO_RECEPCION']; ?>">
                                                                        Eliminar recepción
                                                                    </button>
                                                                <?php } ?>
                                                                <?php if ($r['ESTADO'] == "0") { ?>
                                                                    <button type="button" class="btn btn-outline-success btn-sm btn-block" data-toggle="modal" data-target="#modalAbrirRecepcion" data-id="<?php echo $r['ID_RECEPCION']; ?>" data-numero="<?php echo $r['NUMERO_RECEPCION']; ?>">
                                                                        Abrir recepción
                                                                    </button>
                                                                <?php } ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $ORIGEN; ?></td>
                                                        <td><?php echo $CSGCSPORIGEN; ?></td>
                                                        <td><?php echo $r['NUMERO_GUIA_RECEPCION']; ?></td> 
                                                        <td><?php echo $r['FECHA_GUIA']; ?></td>        
                                                        <td><?php echo $r['FECHA']; ?></td>   
                                                        <td><?php echo $r['HORA_RECEPCION']; ?></td>
                                                        <td><?php echo $TRECEPCION; ?></td>                             
                                                        <td><?php echo $r['GUIA']; ?></td>                                                                            
                                                        <td><?php echo $r['ENVASE']; ?></td>
                                                        <td><?php echo $r['NETO']; ?></td>
                                                        <td><?php echo $r['BRUTO']; ?></td>
                                                        <td><?php echo $r['INGRESO_FORMATO']; ?></td>
                                                        <td><?php echo $r['MODIFICACION_FORMATO']; ?></td>
                                                        <td><?php echo $NOMBRETRANSPORTE; ?></td>           
                                                        <td><?php echo $NOMBRECONDUCTOR; ?></td>                                                         
                                                        <td><?php echo $r['PATENTE_CAMION']; ?></td>
                                                        <td><?php echo $r['PATENTE_CARRO']; ?></td>                                                
                                                        <td><?php echo $r['SEMANA']; ?></td>
                                                        <td><?php echo $r['SEMANAGUIA']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <div class="btn-toolbar mb-3" role="toolbar" aria-label="Datos generales">
                                    <div class="form-row align-items-center" role="group" aria-label="Datos">
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Envase</div>
                                                    <button class="btn   btn-default" id="TOTALENVASEV" name="TOTALENVASEV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Neto</div>
                                                    <button class="btn   btn-default" id="TOTALNETOV" name="TOTALNETOV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Bruto</div>
                                                    <button class="btn   btn-default" id="TOTALBRUTOV" name="TOTALBRUTOV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>  
                        </div>
                        <!-- /.box -->
                </section>
                <!-- /.content -->
            </div>
        </div>
        <?php include_once "../../assest/config/footer.php"; ?>
        <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>

        <!-- Modal Eliminar Recepción -->
        <div class="modal fade" id="modalCerrarRecepcion" tabindex="-1" role="dialog" aria-labelledby="modalCerrarRecepcionLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCerrarRecepcionLabel">Autorización para eliminar recepción</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">El código se envía a Maria de los Ángeles y Erwin Isla.</p>
                            <p class="font-weight-bold">Recepción N° <span class="numero-recepcion-cerrar"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroRecepcionmp">
                            <input type="hidden" name="URLO" value="listarRecepcionmp">
                            <div class="form-group">
                                <label for="codigoCerrar">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoCerrar" name="CODIGO_CERRAR" placeholder="Ingresa el código recibido">
                                <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-danger" name="SOLICITAR_CERRAR">Solicitar código</button>
                            <button type="submit" class="btn btn-danger" name="CONFIRMAR_CERRAR">Eliminar recepción</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Abrir Recepción -->
        <div class="modal fade" id="modalAbrirRecepcion" tabindex="-1" role="dialog" aria-labelledby="modalAbrirRecepcionLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAbrirRecepcionLabel">Autorización para abrir recepción</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">El código se envía a Maria de los Ángeles y Erwin Isla.</p>
                            <p class="font-weight-bold">Recepción N° <span class="numero-recepcion-abrir"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroRecepcionmp">
                            <input type="hidden" name="URLO" value="listarRecepcionmp">
                            <div class="form-group">
                                <label for="codigoAbrir">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoAbrir" name="CODIGO_ABRIR" placeholder="Ingresa el código recibido">
                                <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-success" name="SOLICITAR_ABRIR">Solicitar código</button>
                            <button type="submit" class="btn btn-success" name="CONFIRMAR_ABRIR">Abrir recepción</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                if ($.fn.DataTable.isDataTable('#recepcionmp')) {
                    $('#recepcionmp').DataTable().destroy();
                }

                $('#recepcionmp').DataTable({
                    order: [[0, 'desc']],
                    paging: false,
                    pageLength: 10,
                    scrollY: '60vh',
                    scrollX: true,
                    scrollCollapse: true,
                    lengthChange: false,
                    drawCallback: function () {
                        var api = this.api();
                        var totalenvaseconsolidado = new Intl.NumberFormat('de-DE').format(parseFloat(api.column(12, {page:'current'}).data().sum()).toFixed(0));
                        var totalnetoconsolidado = new Intl.NumberFormat('de-DE').format(parseFloat(api.column(13, {page:'current'}).data().sum()).toFixed(2));
                        var totalbrutoconsolidado = new Intl.NumberFormat('de-DE').format(parseFloat(api.column(14, {page:'current'}).data().sum()).toFixed(2));
                        $("#TOTALENVASEV").text(totalenvaseconsolidado);
                        $("#TOTALNETOV").text(totalnetoconsolidado);
                        $("#TOTALBRUTOV").text(totalbrutoconsolidado);
                    },
                    language: {
                        search: "Buscar:",
                        searchBuilder: {
                            add: 'Agregar filtro',
                            button: {
                                0: 'Filtros',
                                _: 'Filtros (%d)'
                            },
                            clearAll: 'Quitar todo',
                            condition: 'Condición',
                            data: 'Columna',
                            deleteTitle: 'Eliminar regla de filtrado',
                            leftTitle: 'Filtros agrupados',
                            logicAnd: 'Y',
                            logicOr: 'O',
                            rightTitle: 'Agrupación de filtros',
                            title: {
                                0: 'Filtros',
                                _: 'Filtros (%d)'
                            },
                            value: 'Valor'
                        }
                    },
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: 'Excel',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'searchBuilder',
                            text: 'Filtros'
                        }
                    ]
                });

                // IMPORTANTE: Ya no se elimina el buscador de DataTables
                // $('#recepcionmp_filter').remove();

                $('#modalCerrarRecepcion').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idRecepcion = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idRecepcion);
                    modal.find('.numero-recepcion-cerrar').text(numero || '');
                });

                $('#modalAbrirRecepcion').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idRecepcion = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idRecepcion);
                    modal.find('.numero-recepcion-abrir').text(numero || '');
                });
            });
        </script>
</body>

</html>
