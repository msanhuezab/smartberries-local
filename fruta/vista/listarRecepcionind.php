<?php

include_once "../../assest/config/validarUsuarioFruta.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES¿

include_once '../../assest/controlador/RECEPCIONIND_ADO.php';
include_once '../../assest/controlador/DRECEPCIONIND_ADO.php';

include_once '../../assest/controlador/CONDUCTOR_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/TRANSPORTE_ADO.php';
include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/modelo/RECEPCIONIND.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR¿

$RECEPCIONIND_ADO =  new RECEPCIONIND_ADO();
$DRECEPCIONIND_ADO =  new DRECEPCIONIND_ADO();

$PRODUCTOR_ADO =  new PRODUCTOR_ADO();
$TRANSPORTE_ADO =  new TRANSPORTE_ADO();
$CONDUCTOR_ADO =  new CONDUCTOR_ADO();
$USUARIO_ADO =  new USUARIO_ADO();
$RECEPCIONIND = new RECEPCIONIND();



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
            "To: " . implode(', ', $destinatarios) . "\r\n" .
            "Subject: {$asunto}\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n\r\n";

        $comando($cabeceras . $mensaje . "\r\n.\r\n", '250');
    } catch (Exception $e) {
        fclose($conexion);
        return [false, "Error durante el envío SMTP: " . $e->getMessage()];
    }

    try {
        $comando('QUIT', '221');
    } catch (Exception $e) {
        // Algunos servidores cierran la conexión antes de procesar QUIT; no es un error crítico.
    }

    fclose($conexion);
    return [true, null];
}

function obtenerDatosCorreoRecepcion($recepcion, $PRODUCTOR_ADO, $PLANTA_ADO, $EMPRESA_ADO, $TEMPORADA_ADO)
{
    if (!$recepcion) {
        return [
            'numero' => '',
            'fecha' => '',
            'tipo' => '',
            'origen' => '',
            'empresa' => '',
            'planta' => '',
            'temporada' => '',
        ];
    }

    $numero = $recepcion['NUMERO_RECEPCION'] ?? '';
    $fecha = $recepcion['FECHA'] ?? ($recepcion['FECHA_RECEPCION'] ?? '');
    $tipoRecepcion = 'Sin Datos';
    $origen = 'Sin Datos';

    switch ($recepcion['TRECEPCION'] ?? null) {
        case '1':
            $tipoRecepcion = 'Desde Productor';
            $productor = $PRODUCTOR_ADO->verProductor($recepcion['ID_PRODUCTOR'] ?? null);
            if ($productor) {
                $origen = $productor[0]['NOMBRE_PRODUCTOR'];
            }
            break;
        case '2':
            $tipoRecepcion = 'Planta Externa';
            $plantaExt = $PLANTA_ADO->verPlanta($recepcion['ID_PLANTA2'] ?? null);
            if ($plantaExt) {
                $origen = $plantaExt[0]['NOMBRE_PLANTA'];
            }
            break;
    }

    $empresa = '';
    if (!empty($recepcion['ID_EMPRESA'])) {
        $empresaData = $EMPRESA_ADO->verEmpresa($recepcion['ID_EMPRESA']);
        if ($empresaData) {
            $empresa = $empresaData[0]['NOMBRE_EMPRESA'];
        }
    }

    $planta = '';
    if (!empty($recepcion['ID_PLANTA'])) {
        $plantaData = $PLANTA_ADO->verPlanta($recepcion['ID_PLANTA']);
        if ($plantaData) {
            $planta = $plantaData[0]['NOMBRE_PLANTA'];
        }
    }

    $temporada = '';
    if (!empty($recepcion['ID_TEMPORADA'])) {
        $temporadaData = $TEMPORADA_ADO->verTemporada($recepcion['ID_TEMPORADA']);
        if ($temporadaData) {
            $temporada = $temporadaData[0]['NOMBRE_TEMPORADA'];
        }
    }

    return [
        'numero' => $numero,
        'fecha' => $fecha,
        'tipo' => $tipoRecepcion,
        'origen' => $origen,
        'empresa' => $empresa,
        'planta' => $planta,
        'temporada' => $temporada,
    ];
}

if ($_POST) {
    $IDRECEPCION = $_REQUEST['ID'] ?? null;
    $CODIGOELIMINAR = trim($_REQUEST['CODIGO_ELIMINAR'] ?? '');
    $CODIGOAPERTURA = trim($_REQUEST['CODIGO_ABRIR'] ?? '');

    $detalleRecepcion = $IDRECEPCION ? $RECEPCIONIND_ADO->verRecepcion3($IDRECEPCION) : [];
    $datosRecepcion = $detalleRecepcion ? $detalleRecepcion[0] : [];
    $datosCorreo = obtenerDatosCorreoRecepcion($datosRecepcion, $PRODUCTOR_ADO, $PLANTA_ADO, $EMPRESA_ADO, $TEMPORADA_ADO);

    if (isset($_REQUEST['SOLICITARELIMINAR'])) {
        if (!$IDRECEPCION) {
            $MENSAJE = "No se ha seleccionado una recepción válida.";
        } elseif (!$datosRecepcion || ($datosRecepcion['ESTADO'] ?? null) != 1) {
            $MENSAJE = "Solo se pueden solicitar eliminaciones para recepciones abiertas.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['RECEPCIONIND_ELIMINAR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['RECEPCIONIND_ELIMINAR_ID'] = $IDRECEPCION;
            $_SESSION['RECEPCIONIND_ELIMINAR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización eliminación recepción #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó la eliminación de una recepción." . "\r\n\r\n" .
                "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de recepción: " . $datosCorreo['tipo'] . "\r\n" .
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

    if (isset($_REQUEST['CONFIRMAR_ELIMINAR'])) {
        $codigoSesion = $_SESSION['RECEPCIONIND_ELIMINAR_CODIGO'] ?? null;
        $idSesion = $_SESSION['RECEPCIONIND_ELIMINAR_ID'] ?? null;
        $tiempoSesion = $_SESSION['RECEPCIONIND_ELIMINAR_TIEMPO'] ?? 0;

        if (!$IDRECEPCION) {
            $MENSAJE = "No se ha seleccionado una recepción válida.";
        } elseif (!$datosRecepcion || ($datosRecepcion['ESTADO'] ?? null) != 1) {
            $MENSAJE = "Solo se pueden eliminar recepciones abiertas.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDRECEPCION) {
            $MENSAJE = "No hay una solicitud de eliminación vigente para esta recepción.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOELIMINAR || $CODIGOELIMINAR != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $RECEPCIONIND->__SET('ID_RECEPCION', $IDRECEPCION);
            $RECEPCIONIND_ADO->deshabilitar($RECEPCIONIND);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación eliminación recepción #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la eliminación de la recepción." . "\r\n\r\n" .
                "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de recepción: " . $datosCorreo['tipo'] . "\r\n" .
                "Origen: " . $datosCorreo['origen'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado del registro fue desactivado.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Recepción eliminada (estado de registro desactivado)." : ($errorEnvio ?: "La recepción se eliminó pero hubo un problema al enviar la notificación.");
            unset($_SESSION['RECEPCIONIND_ELIMINAR_CODIGO']);
            unset($_SESSION['RECEPCIONIND_ELIMINAR_ID']);
            unset($_SESSION['RECEPCIONIND_ELIMINAR_TIEMPO']);
        }
    }

    if (isset($_REQUEST['SOLICITAR_ABRIR'])) {
        if (!$IDRECEPCION) {
            $MENSAJE = "No se ha seleccionado una recepción válida.";
        } elseif (!$datosRecepcion || ($datosRecepcion['ESTADO'] ?? null) != 0) {
            $MENSAJE = "Solo es posible solicitar apertura para recepciones cerradas.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['RECEPCIONIND_ABRIR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['RECEPCIONIND_ABRIR_ID'] = $IDRECEPCION;
            $_SESSION['RECEPCIONIND_ABRIR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización apertura recepción #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó la apertura de una recepción cerrada." . "\r\n\r\n" .
                "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de recepción: " . $datosCorreo['tipo'] . "\r\n" .
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

    if (isset($_REQUEST['CONFIRMAR_ABRIR'])) {
        $codigoSesion = $_SESSION['RECEPCIONIND_ABRIR_CODIGO'] ?? null;
        $idSesion = $_SESSION['RECEPCIONIND_ABRIR_ID'] ?? null;
        $tiempoSesion = $_SESSION['RECEPCIONIND_ABRIR_TIEMPO'] ?? 0;

        if (!$IDRECEPCION) {
            $MENSAJE = "No se ha seleccionado una recepción válida.";
        } elseif (!$datosRecepcion || ($datosRecepcion['ESTADO'] ?? null) != 0) {
            $MENSAJE = "Solo es posible abrir recepciones que estén cerradas.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDRECEPCION) {
            $MENSAJE = "No hay una solicitud de apertura vigente para esta recepción.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOAPERTURA || $CODIGOAPERTURA != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $RECEPCIONIND->__SET('ID_RECEPCION', $IDRECEPCION);
            $RECEPCIONIND_ADO->habilitar($RECEPCIONIND);
            $RECEPCIONIND_ADO->abierto($RECEPCIONIND);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación apertura recepción #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la apertura de la recepción." . "\r\n\r\n" .
                "Número de recepción: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de recepción: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de recepción: " . $datosCorreo['tipo'] . "\r\n" .
                "Origen: " . $datosCorreo['origen'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado de la recepción cambió a abierta.";

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
            unset($_SESSION['RECEPCIONIND_ABRIR_CODIGO']);
            unset($_SESSION['RECEPCIONIND_ABRIR_ID']);
            unset($_SESSION['RECEPCIONIND_ABRIR_TIEMPO']);
        }
    }
}

//INICIALIZAR ARREGLOS
$ARRAYRECEPCION = "";
$ARRAYRECEPCIONTOTALES = "";
$ARRAYVEREMPRESA = "";
$ARRAYVERPRODUCTOR = "";
$ARRAYVERTRANSPORTE = "";
$ARRAYVERCONDUCTOR = "";
$ARRAYFECHA = "";
$ARRAYPRODUCTOR = "";

//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES


if ($EMPRESAS  && $PLANTAS && $TEMPORADAS) {
    $ARRAYRECEPCION = $RECEPCIONIND_ADO->listarRecepcionEmpresaPlantaTemporadaCBX($EMPRESAS, $PLANTAS, $TEMPORADAS);
}

include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";







?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Agrupado Recepcion</title>
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
        <?php include_once "../../assest/config/menuFruta.php"; ?>
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
                                        <li class="breadcrumb-item" aria-current="page">Recepcion</li>
                                        <li class="breadcrumb-item" aria-current="page">Industrial</li>
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
                                        <table id="recepcionind" class="table-hover " style="width: 100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Numero Recepción </th>
                                                    <th>Estado</th>
                                                    <th class="text-center">Operaciones</th>
                                                    <th class="text-center">Autorizaciones</th>
                                                    <th>Fecha Recepción </th>
                                                    <th>Numero Guia </th>
                                                    <th>Hora Recepción </th>
                                                    <th>Tipo Recepción</th>
                                                    <th>CSG/CSP Recepción</th>
                                                    <th>Origen Recepción</th>
                                                    <th>Fecha Guia </th>
                                                    <th>Total Kilos Guia</th>
                                                    <th>Cantidad Envase</th>
                                                    <th>Total Kilos Neto</th>
                                                    <th>Total Kilos Bruto</th>
                                                    <th>Fecha Ingreso</th>
                                                    <th>Fecha Modificacion</th>
                                                    <th>Transporte </th>
                                                    <th>Nombre Conductor </th>
                                                    <th>Patente Camión </th>
                                                    <th>Patente Carro </th>
                                                    <th>Semana Recepción </th>
                                                    <th>Semana Guia </th>
                                                    <th>Empresa</th>
                                                    <th>Planta</th>
                                                    <th>Temporada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYRECEPCION as $r) : ?>
                                                    <?php   
                                                            if ($r['TRECEPCION'] == "1") {
                                                                $TRECEPCION = "Desde Productor ";
                                                                $ARRAYPRODUCTOR2 = $PRODUCTOR_ADO->verProductor($r['ID_PRODUCTOR']);
                                                                if ($ARRAYPRODUCTOR2) {
                                                                    $CSGCSPORIGEN=$ARRAYPRODUCTOR2[0]['CSG_PRODUCTOR'] ;
                                                                    $ORIGEN =  $ARRAYPRODUCTOR2[0]['NOMBRE_PRODUCTOR'];
                                                                } else {
                                                                    $ORIGEN = "Sin Datos";
                                                                    $CSGCSPORIGEN="Sin Datos";
                                                                }
                                                            } else if ($r['TRECEPCION'] == "2") {
                                                                $TRECEPCION = "Planta Externa";
                                                                $ARRAYPLANTA2 = $PLANTA_ADO->verPlanta($r['ID_PLANTA2']);
                                                                if ($ARRAYPLANTA2) {
                                                                    $CSGCSPORIGEN=$ARRAYPLANTA2[0]['CODIGO_SAG_PLANTA'];
                                                                    $ORIGEN = $ARRAYPLANTA2[0]['NOMBRE_PLANTA'];
                                                                } else {
                                                                    $ORIGEN = "Sin Datos";
                                                                    $CSGCSPORIGEN="Sin Datos";
                                                                }
                                                            } else {
                                                                $TRECEPCION = "Sin Datos";
                                                                $CSGCSPORIGEN="Sin Datos";
                                                                $ORIGEN = "Sin Datos";
                                                            }
                                                            $ARRAYVEREMPRESA = $EMPRESA_ADO->verEmpresa($r['ID_EMPRESA']);
                                                            if($ARRAYVEREMPRESA){
                                                                $NOMBREEMPRESA= $ARRAYVEREMPRESA[0]['NOMBRE_EMPRESA'];
                                                            }else{
                                                                $NOMBREEMPRESA="Sin Datos";
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


                                                            
                                                            $ARRAYVERTRANSPORTE = $TRANSPORTE_ADO->verTransporte($r['ID_TRANSPORTE']);
                                                            if($ARRAYVERTRANSPORTE){
                                                                $NOMBRETRANSPORTE= $ARRAYVERTRANSPORTE[0]['NOMBRE_TRANSPORTE'];
                                                            }else{
                                                                $NOMBRETRANSPORTE="Sin Datos";
                                                            }
                                                            $ARRAYVERCONDUCTOR = $CONDUCTOR_ADO->verConductor($r['ID_CONDUCTOR']);
                                                            if($ARRAYVERCONDUCTOR){
                                                                $NOMBRECONDUCTOR= $ARRAYVERCONDUCTOR[0]['NOMBRE_CONDUCTOR'];
                                                            }else{
                                                                $NOMBRECONDUCTOR="Sin Datos";
                                                            }
                                                            
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
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroRecepcionind" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URLO" name="URLO" value="listarRecepcionind" />
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
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" name="informe" title="Informe" Onclick="abrirPestana('../../assest/documento/informeRecepcionInd.php?parametro=<?php echo $r['ID_RECEPCION']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Informe
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Tarjas">
                                                                                <button type="button" class="btn  btn-danger btn-block" id="defecto" name="tarjas" title="Tarjas" Onclick="abrirPestana('../../assest/documento/informeTarjasRecepcionInd.php?parametro=<?php echo $r['ID_RECEPCION']; ?>'); ">
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
                                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-block" data-toggle="modal" data-target="#modalEliminarRecepcion" data-id="<?php echo $r['ID_RECEPCION']; ?>" data-numero="<?php echo $r['NUMERO_RECEPCION']; ?>">
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
                                                        <td><?php echo $r['FECHA']; ?></td>
                                                        <td><?php echo $r['NUMERO_GUIA_RECEPCION']; ?></td>
                                                        <td><?php echo $r['HORA_RECEPCION']; ?></td>
                                                        <td><?php echo $TRECEPCION; ?></td>
                                                        <td><?php echo $CSGCSPORIGEN; ?></td>   
                                                        <td><?php echo $ORIGEN; ?></td>                       
                                                        <td><?php echo $r['FECHA_GUIA']; ?></td>
                                                        <td><?php echo $r['GUIA']; ?></td>
                                                        <td><?php echo $r['ENVASE']; ?></td>
                                                        <td><?php echo $r['NETO']; ?></td>
                                                        <td><?php echo $r['BRUTO']; ?></td>
                                                        <td><?php echo $r['INGRESO']; ?></td>
                                                        <td><?php echo $r['MODIFICACION']; ?></td>
                                                        <td><?php echo $NOMBRETRANSPORTE; ?></td>           
                                                        <td><?php echo $NOMBRECONDUCTOR; ?></td>                                                         
                                                        <td><?php echo $r['PATENTE_CAMION']; ?></td>
                                                        <td><?php echo $r['PATENTE_CARRO']; ?></td>                                        
                                                        <td><?php echo $r['SEMANA']; ?></td>
                                                        <td><?php echo $r['SEMANAGUIA']; ?></td>
                                                        <td><?php echo $NOMBREEMPRESA; ?></td>
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
        <?php include_once "../../assest/config/footer.php"; ?>
        <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>
        <!-- Modal Eliminar Recepción -->
        <div class="modal fade" id="modalEliminarRecepcion" tabindex="-1" role="dialog" aria-labelledby="modalEliminarRecepcionLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEliminarRecepcionLabel">Autorización para eliminar recepción</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">Se enviará un código de confirmación a Maria de los Ángeles y Erwin Isla. Ingrésalo para completar la eliminación.</p>
                            <p class="font-weight-bold">Recepción N° <span class="numero-recepcion-eliminar"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroRecepcionind">
                            <input type="hidden" name="URLO" value="listarRecepcionind">
                            <div class="form-group">
                                <label for="codigoEliminarRecepcion">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoEliminarRecepcion" name="CODIGO_ELIMINAR" placeholder="Ingresa el código recibido">
                                <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-danger" name="SOLICITARELIMINAR">Solicitar código</button>
                            <button type="submit" class="btn btn-danger" name="CONFIRMAR_ELIMINAR">Eliminar</button>
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
                            <p class="mb-3">Solicita y confirma el código enviado a Maria de los Ángeles y Erwin Isla para abrir la recepción cerrada.</p>
                            <p class="font-weight-bold">Recepción N° <span class="numero-recepcion-abrir"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroRecepcionind">
                            <input type="hidden" name="URLO" value="listarRecepcionind">
                            <div class="form-group">
                                <label for="codigoAbrirRecepcion">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoAbrirRecepcion" name="CODIGO_ABRIR" placeholder="Ingresa el código recibido">
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
                $('#modalEliminarRecepcion').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idRecepcion = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idRecepcion);
                    modal.find('.numero-recepcion-eliminar').text(numero || '');
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