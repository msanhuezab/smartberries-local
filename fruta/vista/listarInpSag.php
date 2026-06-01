<?php

include_once "../../assest/config/validarUsuarioFruta.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES

include_once '../../assest/controlador/EXIEXPORTACION_ADO.php';
include_once '../../assest/controlador/VESPECIES_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';

include_once '../../assest/controlador/TINPSAG_ADO.php';
include_once '../../assest/controlador/INPSAG_ADO.php';
include_once '../../assest/modelo/INPSAG.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR
$TUSUARIO_ADO = new TUSUARIO_ADO();
$USUARIO_ADO = new USUARIO_ADO();
$EMPRESA_ADO =  new EMPRESA_ADO();
$PLANTA_ADO =  new PLANTA_ADO();
$TEMPORADA_ADO =  new TEMPORADA_ADO();


$VESPECIES_ADO =  new VESPECIES_ADO();
$PRODUCTOR_ADO = new PRODUCTOR_ADO();
$EXIEXPORTACION_ADO = new EXIEXPORTACION_ADO();

$INPSAG = new INPSAG();


$TINPSAG_ADO =  new TINPSAG_ADO();
$INPSAG_ADO =  new INPSAG_ADO();



//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD


$TOTALBRUTO = "";
$TOTALNETO = "";
$TOTALENVASE = "";
$FECHADESDE = "";
$FECHAHASTA = "";

$DISABLEDC="";
$DISABLEDT="";
$PRODUCTOR = "";
$NUMEROGUIA = "";

//INICIALIZAR ARREGLOS
$ARRAYDESPACHOEX = "";
$ARRAYDESPACHOEXTOTALES = "";
$ARRAYVEREMPRESA = "";
$ARRAYVERPRODUCTOR = "";
$ARRAYTINPSAG = "";
$ARRAYTESTADOSAG = "";
$MENSAJE = $_SESSION['MENSAJE_INPSAG'] ?? "";
$MENSAJEENVIO = $_SESSION['MENSAJEENVIO_INPSAG'] ?? "";
unset($_SESSION['MENSAJE_INPSAG'], $_SESSION['MENSAJEENVIO_INPSAG']);

//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES


if ( $PLANTAS && $TEMPORADAS) {
    $ARRAYDESPACHOEX = $INPSAG_ADO->listarInpsagPlantaTemporadaCBX( $PLANTAS, $TEMPORADAS);
}
include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";

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

function obtenerDatosCorreoInpsag($inpsag, $EMPRESA_ADO, $PLANTA_ADO, $TEMPORADA_ADO)
{
    $empresa = '';
    $planta = '';
    $temporada = '';

    if (!empty($inpsag['ID_EMPRESA'])) {
        $empresaData = $EMPRESA_ADO->verEmpresa($inpsag['ID_EMPRESA']);
        if ($empresaData) {
            $empresa = $empresaData[0]['NOMBRE_EMPRESA'];
        }
    }

    if (!empty($inpsag['ID_PLANTA'])) {
        $plantaData = $PLANTA_ADO->verPlanta($inpsag['ID_PLANTA']);
        if ($plantaData) {
            $planta = $plantaData[0]['NOMBRE_PLANTA'];
        }
    }

    if (!empty($inpsag['ID_TEMPORADA'])) {
        $temporadaData = $TEMPORADA_ADO->verTemporada($inpsag['ID_TEMPORADA']);
        if ($temporadaData) {
            $temporada = $temporadaData[0]['NOMBRE_TEMPORADA'];
        }
    }

    return [
        'numero' => $inpsag['NUMERO_INPSAG'] ?? '',
        'correlativo' => $inpsag['CORRELATIVO_INPSAG'] ?? '',
        'fecha' => $inpsag['FECHA_INPSAG'] ?? '',
        'empresa' => $empresa,
        'planta' => $planta,
        'temporada' => $temporada,
    ];
}

if ($_POST) {
    $IDINPSAG = $_REQUEST['ID'] ?? null;
    $CODIGOVERIFICACION = $_REQUEST['CODIGO_ELIMINAR'] ?? '';
    $CODIGOAPERTURA = $_REQUEST['CODIGO_ABRIR'] ?? '';

    $detalleInpsag = $IDINPSAG ? $INPSAG_ADO->verInpsag3($IDINPSAG) : [];
    $datosInpsag = $detalleInpsag ? $detalleInpsag[0] : [];
    $datosCorreo = obtenerDatosCorreoInpsag($datosInpsag, $EMPRESA_ADO, $PLANTA_ADO, $TEMPORADA_ADO);
    $estaCerrado = ($datosInpsag['ESTADO'] ?? null) == 0;

    if (isset($_REQUEST['SOLICITAR_ELIMINAR'])) {
        $foliosAsociados = $IDINPSAG ? $EXIEXPORTACION_ADO->buscarPorSag2($IDINPSAG) : [];

        if ($foliosAsociados) {
            $MENSAJE = "No es posible eliminar la inspección porque existen folios asociados.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['INPSAG_ELIMINAR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['INPSAG_ELIMINAR_ID'] = $IDINPSAG;
            $_SESSION['INPSAG_ELIMINAR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización eliminación inspección SAG #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó eliminar una inspección SAG." . "\r\n\r\n" .
                "Número interno: " . $datosCorreo['numero'] . "\r\n" .
                "Número inspección: " . $datosCorreo['correlativo'] . "\r\n" .
                "Fecha inspección: " . $datosCorreo['fecha'] . "\r\n" .
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
            $MENSAJEENVIO = $envioOk ? "Código de autorización enviado correctamente a Maria de los Ángeles y Erwin Isla." : ($errorEnvio ?: "No fue posible enviar el correo de autorización.");
        }
    }

    if (isset($_REQUEST['CONFIRMAR_ELIMINAR'])) {
        $codigoSesion = $_SESSION['INPSAG_ELIMINAR_CODIGO'] ?? null;
        $idSesion = $_SESSION['INPSAG_ELIMINAR_ID'] ?? null;
        $tiempoSesion = $_SESSION['INPSAG_ELIMINAR_TIEMPO'] ?? 0;

        $foliosAsociados = $IDINPSAG ? $EXIEXPORTACION_ADO->buscarPorSag2($IDINPSAG) : [];

        if ($foliosAsociados) {
            $MENSAJE = "La inspección tiene folios asociados y no puede eliminarse.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDINPSAG) {
            $MENSAJE = "No hay una solicitud de eliminación vigente para esta inspección.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOVERIFICACION || $CODIGOVERIFICACION != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $INPSAG->__SET('ID_INPSAG', $IDINPSAG);
            $INPSAG_ADO->deshabilitar($INPSAG);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación eliminación inspección SAG #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la eliminación de la inspección SAG." . "\r\n\r\n" .
                "Número interno: " . $datosCorreo['numero'] . "\r\n" .
                "Número inspección: " . $datosCorreo['correlativo'] . "\r\n" .
                "Fecha inspección: " . $datosCorreo['fecha'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado de registro fue desactivado.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Inspección SAG eliminada (estado de registro desactivado)." : ($errorEnvio ?: "La inspección se eliminó, pero hubo un problema al enviar la notificación.");
            $_SESSION['MENSAJEENVIO_INPSAG'] = $MENSAJEENVIO;
            unset($_SESSION['INPSAG_ELIMINAR_CODIGO'], $_SESSION['INPSAG_ELIMINAR_ID'], $_SESSION['INPSAG_ELIMINAR_TIEMPO']);
            header('Location: listarInpSag.php');
            exit;
        }
    }

    if (isset($_REQUEST['SOLICITAR_ABRIR'])) {
        if (!$estaCerrado) {
            $MENSAJE = "La inspección debe estar cerrada para solicitar apertura.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['INPSAG_ABRIR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['INPSAG_ABRIR_ID'] = $IDINPSAG;
            $_SESSION['INPSAG_ABRIR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización apertura inspección SAG #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó abrir una inspección SAG cerrada." . "\r\n\r\n" .
                "Número interno: " . $datosCorreo['numero'] . "\r\n" .
                "Número inspección: " . $datosCorreo['correlativo'] . "\r\n" .
                "Fecha inspección: " . $datosCorreo['fecha'] . "\r\n" .
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
            $MENSAJEENVIO = $envioOk ? "Código de autorización enviado correctamente a Maria de los Ángeles y Erwin Isla." : ($errorEnvio ?: "No fue posible enviar el correo de autorización.");
        }
    }

    if (isset($_REQUEST['CONFIRMAR_ABRIR'])) {
        $codigoSesion = $_SESSION['INPSAG_ABRIR_CODIGO'] ?? null;
        $idSesion = $_SESSION['INPSAG_ABRIR_ID'] ?? null;
        $tiempoSesion = $_SESSION['INPSAG_ABRIR_TIEMPO'] ?? 0;

        if (!$estaCerrado) {
            $MENSAJE = "Solo las inspecciones cerradas pueden solicitar apertura.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDINPSAG) {
            $MENSAJE = "No hay una solicitud de apertura vigente para esta inspección.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOAPERTURA || $CODIGOAPERTURA != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $INPSAG->__SET('ID_INPSAG', $IDINPSAG);
            $INPSAG_ADO->habilitar($INPSAG);
            $INPSAG_ADO->abierto($INPSAG);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación apertura inspección SAG #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la apertura de la inspección SAG." . "\r\n\r\n" .
                "Número interno: " . $datosCorreo['numero'] . "\r\n" .
                "Número inspección: " . $datosCorreo['correlativo'] . "\r\n" .
                "Fecha inspección: " . $datosCorreo['fecha'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado fue marcado como abierto.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Inspección SAG abierta correctamente." : ($errorEnvio ?: "La inspección se abrió, pero hubo un problema al enviar la notificación.");
            $_SESSION['MENSAJEENVIO_INPSAG'] = $MENSAJEENVIO;
            unset($_SESSION['INPSAG_ABRIR_CODIGO'], $_SESSION['INPSAG_ABRIR_ID'], $_SESSION['INPSAG_ABRIR_TIEMPO']);
            header('Location: listarInpSag.php');
            exit;
        }
    }
}







?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Agrupado Inspección</title>
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

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuFruta.php"; ?>
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <div class="container-full">
                <!-- Content Header (Page header) -->
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Inspección SAG</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Operaciones SAG</li>
                                        <li class="breadcrumb-item" aria-current="page">Inspección SAG</li>
                                        <li class="breadcrumb-item active" aria-current="page"> <a href="#"> Agrupado Inspección </a> </li>
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
                                        <table id="sag" class="table-hover " style="width: 100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Nº Interno</th>
                                                    <th>Nº Inspección</th>
                                                    <th>Conteo de folios</th>
                                                    <th>Estado</th>
                                                    <th class="text-center">Operaciónes</th>
                                                    <th class="text-center">Autorizaciones</th>
                                                    <th>Fecha Inspección </th>
                                                    <th>Tipo Inspección </th>
                                                    <th>Condicción </th>
                                                    <th>Valor CIF </th>
                                                    <th>Cantidad Envase</th>
                                                    <th>Total Kilos Neto</th>
                                                    <th>Total Kilos Bruto</th>
                                                    <th>Fecha Ingreso</th>
                                                    <th>Fecha Modificación</th>
                                                    <th>Planta</th>
                                                    <th>Temporada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYDESPACHOEX as $r) : ?>
                                                    <?php

                                                    $ARRAYTINPSAG = $TINPSAG_ADO->verTinpsag($r['ID_TINPSAG']);
                                                    if ($ARRAYTINPSAG) {
                                                        $NOMBRETINPSAG = $ARRAYTINPSAG[0]['NOMBRE_TINPSAG'];
                                                    } else {
                                                        $NOMBRETINPSAG = "Sin Datos";
                                                    }
                                                    if ($r['TESTADOSAG'] == null || $r['TESTADOSAG'] == "0") {
                                                        $TESTADOSAG = "Sin Condición";
                                                    }
                                                    if ($r['TESTADOSAG'] == "1") {
                                                        $TESTADOSAG = "En Inspección";
                                                    }
                                                    if ($r['TESTADOSAG'] == "2") {
                                                        $TESTADOSAG = "Aprobado Origen";
                                                    }
                                                    if ($r['TESTADOSAG'] == "3") {
                                                        $TESTADOSAG = "Aprobado USDA";
                                                    }
                                                    if ($r['TESTADOSAG'] == "4") {
                                                        $TESTADOSAG = "Fumigado";
                                                    }
                                                    if ($r['TESTADOSAG'] == "5") {
                                                        $TESTADOSAG = "Rechazado";
                                                    }                                                    
                                                    if( strlen($r['CORRELATIVO_INPSAG'])==0){
                                                        $DISABLEDC="disabled";
                                                    }else{
                                                        $DISABLEDC="";
                                                    }
                                                    $ARRAYTOMADO = $EXIEXPORTACION_ADO->buscarPorSag2($r['ID_INPSAG']);
                                                    $foliosAgrupados = [];
                                                    $estadoFoliosAgrupados = [
                                                        'Sin Condición' => [],
                                                        'En Inspección' => [],
                                                        'Aprobado Origen' => [],
                                                        'Aprobado USDA' => [],
                                                        'Fumigado' => [],
                                                        'Rechazado' => [],
                                                    ];
                                                    if (empty($ARRAYTOMADO)) {
                                                        $DISABLEDT = "disabled";
                                                    } else {
                                                        $DISABLEDT = "";
                                                        foreach ($ARRAYTOMADO as $folioRegistro) {
                                                            $numeroFolio = trim((string) ($folioRegistro['FOLIO_AUXILIAR_EXIEXPORTACION'] ?? $folioRegistro['FOLIO_EXIEXPORTACION'] ?? ''));
                                                            if ($numeroFolio === '') {
                                                                continue;
                                                            }
                                                            if (!isset($foliosAgrupados[$numeroFolio])) {
                                                                $foliosAgrupados[$numeroFolio] = [
                                                                    'aprobado' => false,
                                                                    'rechazado' => false,
                                                                ];
                                                            }
                                                            $estadoFolio = (int) ($folioRegistro['TESTADOSAG'] ?? 0);
                                                            switch ($estadoFolio) {
                                                                case 1:
                                                                    $estadoNombre = 'En Inspección';
                                                                    break;
                                                                case 2:
                                                                    $estadoNombre = 'Aprobado Origen';
                                                                    break;
                                                                case 3:
                                                                    $estadoNombre = 'Aprobado USDA';
                                                                    break;
                                                                case 4:
                                                                    $estadoNombre = 'Fumigado';
                                                                    break;
                                                                case 5:
                                                                    $estadoNombre = 'Rechazado';
                                                                    break;
                                                                default:
                                                                    $estadoNombre = 'Sin Condición';
                                                                    break;
                                                            }
                                                            $estadoFoliosAgrupados[$estadoNombre][$numeroFolio] = true;
                                                            if ($estadoFolio === 5) {
                                                                $foliosAgrupados[$numeroFolio]['rechazado'] = true;
                                                            } elseif (in_array($estadoFolio, [2, 3, 4], true)) {
                                                                $foliosAgrupados[$numeroFolio]['aprobado'] = true;
                                                            }
                                                        }
                                                    }
                                                
                                                
                                                    $ARRAYPLANTA = $PLANTA_ADO->verPlanta($r['ID_PLANTA']);
                                                    if ($ARRAYPLANTA) {
                                                        $NOMBREPLANTA = $ARRAYPLANTA[0]['NOMBRE_PLANTA'];
                                                    } else {
                                                        $NOMBREPLANTA = "Sin Datos";
                                                    }
                                                    $ARRAYTEMPORADA = $TEMPORADA_ADO->verTemporada($r['ID_TEMPORADA']);
                                                    if ($ARRAYTEMPORADA) {
                                                        $NOMBRETEMPORADA = $ARRAYTEMPORADA[0]['NOMBRE_TEMPORADA'];
                                                    } else {
                                                        $NOMBRETEMPORADA = "Sin Datos";
                                                    }
                                                    ?>

                                                    <tr class="text-center">
                                                        <td> <?php echo $r['NUMERO_INPSAG']; ?></td>
                                                        <td> <?php echo $r['CORRELATIVO_INPSAG']; ?></td>
                                                        <td>
                                                            <?php if ($foliosAgrupados) { ?>
                                                                <div class="d-flex flex-column w-100">
                                                                    <?php foreach ($estadoFoliosAgrupados as $estadoNombre => $foliosEstado) { ?>
                                                                        <?php $cantidadFolios = count($foliosEstado); ?>
                                                                        <?php if ($cantidadFolios > 0) { ?>
                                                                            <?php
                                                                            $claseEstado = 'badge-secondary';
                                                                            switch ($estadoNombre) {
                                                                                case 'En Inspección':
                                                                                    $claseEstado = 'badge-info';
                                                                                    break;
                                                                                case 'Aprobado Origen':
                                                                                case 'Aprobado USDA':
                                                                                case 'Fumigado':
                                                                                    $claseEstado = 'badge-success';
                                                                                    break;
                                                                                case 'Rechazado':
                                                                                    $claseEstado = 'badge-danger';
                                                                                    break;
                                                                                default:
                                                                                    $claseEstado = 'badge-secondary';
                                                                                    break;
                                                                            }
                                                                            ?>
                                                                            <span class="badge <?php echo $claseEstado; ?> d-block w-100 mb-1 text-center text-wrap">
                                                                                <?php echo $estadoNombre; ?>: <?php echo $cantidadFolios; ?>
                                                                            </span>
                                                                        <?php } ?>
                                                                    <?php } ?>
                                                                </div>
                                                            <?php } else { ?>
                                                                <span>-</span>
                                                            <?php } ?>
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
                                                                            <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_INPSAG']; ?>" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroInpsag" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URLO" name="URLO" value="listarInpSag" />
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
                                                                                </span>
                                                                            <?php } ?>
                                                                            <hr>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Informe">
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" name="informe" title="Informe" Onclick="abrirPestana('../../assest/documento/informeInpsag.php?parametro=<?php echo $r['ID_INPSAG']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Informe
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Informe">
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" name="informe"  title=" S.I.F" Onclick="abrirPestana('../../assest/documento/informeInpsagSif.php?parametro=<?php echo $r['ID_INPSAG']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> S.I.F
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Packing List">
                                                                                <button type="button" class="btn  btn-danger btn-block" id="defecto" name="tarjas" title="Packing List" Onclick="abrirPestana('../../assest/documento/informeInpsagPackingList.php?parametro=<?php echo $r['ID_INPSAG']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i>Packing List
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Packing List">
                                                                                <button type="button" class="btn  btn-danger btn-block" id="defecto" name="tarjas" title="Packing List USDA" Onclick="abrirPestana('../../assest/documento/informeInpsagPackingListUsda.php?parametro=<?php echo $r['ID_INPSAG']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i>Packing List USDA
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="CSV">
                                                                                <button type="button" class="btn  btn-success btn-block" id="defecto" name="tarjas" title="Archivo Plano" <?php echo $DISABLEDC; ?> <?php echo $DISABLEDT; ?> Onclick="abrirPestana('../../assest/csv/CsvInpsag.php?parametro=<?php echo $r['ID_INPSAG']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-excel-o"></i> Archivo Plano
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
                                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-block" data-toggle="modal" data-target="#modalEliminarInpsag" data-id="<?php echo $r['ID_INPSAG']; ?>" data-numero="<?php echo $r['NUMERO_INPSAG']; ?>">
                                                                        Eliminar inspección
                                                                    </button>
                                                                <?php } ?>
                                                                <?php if ($r['ESTADO'] == "0") { ?>
                                                                    <button type="button" class="btn btn-outline-success btn-sm btn-block" data-toggle="modal" data-target="#modalAbrirInpsag" data-id="<?php echo $r['ID_INPSAG']; ?>" data-numero="<?php echo $r['NUMERO_INPSAG']; ?>">
                                                                        Abrir inspección
                                                                    </button>
                                                                <?php } ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $r['FECHA_INPSAG']; ?></td>
                                                        <td><?php echo $NOMBRETINPSAG; ?></td>
                                                        <td><?php echo $TESTADOSAG; ?></td>
                                                        <td><?php echo $r['CIF']; ?></td>
                                                        <td><?php echo $r['ENVASE']; ?></td>
                                                        <td><?php echo $r['NETO']; ?></td>
                                                        <td><?php echo $r['BRUTO']; ?></td>
                                                        <td><?php echo $r['INGRESO']; ?></td>
                                                        <td><?php echo $r['MODIFICACION']; ?></td>
                                                        <td><?php echo $NOMBREPLANTA; ?></td>
                                                        <td><?php echo $NOMBRETEMPORADA; ?></td>
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
        <!- LLAMADA ARCHIVO DEL DISEÑO DEL FOOTER Y MENU USUARIO -!>
            <?php include_once "../../assest/config/footer.php"; ?>
            <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>

    <!-- Modal Eliminar Inpsag -->
    <div class="modal fade" id="modalEliminarInpsag" tabindex="-1" role="dialog" aria-labelledby="modalEliminarInpsagLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarInpsagLabel">Autorización para eliminar inspección</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <p class="mb-3">El código se envía a Maria de los Ángeles y Erwin Isla.</p>
                        <p class="font-weight-bold">Inspección N° <span class="numero-inpsag-eliminar"></span></p>
                        <input type="hidden" name="ID" value="">
                        <input type="hidden" name="URL" value="registroInpsag">
                        <input type="hidden" name="URLO" value="listarInpSag">
                        <div class="form-group">
                            <label for="codigoEliminar">Código de autorización</label>
                            <input type="text" class="form-control" id="codigoEliminar" name="CODIGO_ELIMINAR" placeholder="Ingresa el código recibido">
                            <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-outline-danger" name="SOLICITAR_ELIMINAR">Solicitar código</button>
                        <button type="submit" class="btn btn-danger" name="CONFIRMAR_ELIMINAR">Eliminar inspección</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Abrir Inpsag -->
    <div class="modal fade" id="modalAbrirInpsag" tabindex="-1" role="dialog" aria-labelledby="modalAbrirInpsagLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAbrirInpsagLabel">Autorización para abrir inspección</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <p class="mb-3">El código se envía a Maria de los Ángeles y Erwin Isla.</p>
                        <p class="font-weight-bold">Inspección N° <span class="numero-inpsag-abrir"></span></p>
                        <input type="hidden" name="ID" value="">
                        <input type="hidden" name="URL" value="registroInpsag">
                        <input type="hidden" name="URLO" value="listarInpSag">
                        <div class="form-group">
                            <label for="codigoAbrir">Código de autorización</label>
                            <input type="text" class="form-control" id="codigoAbrir" name="CODIGO_ABRIR" placeholder="Ingresa el código recibido">
                            <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-outline-success" name="SOLICITAR_ABRIR">Solicitar código</button>
                        <button type="submit" class="btn btn-success" name="CONFIRMAR_ABRIR">Abrir inspección</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#modalEliminarInpsag').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var idInpsag = button.data('id');
                var numero = button.data('numero');
                var modal = $(this);
                modal.find('input[name="ID"]').val(idInpsag);
                modal.find('.numero-inpsag-eliminar').text(numero || '');
            });

            $('#modalAbrirInpsag').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var idInpsag = button.data('id');
                var numero = button.data('numero');
                var modal = $(this);
                modal.find('input[name="ID"]').val(idInpsag);
                modal.find('.numero-inpsag-abrir').text(numero || '');
            });
        });
    </script>
</body>

</html>
