<?php

include_once "../../assest/config/validarUsuarioFruta.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES

include_once '../../assest/controlador/TPROCESO_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/VESPECIES_ADO.php';
include_once '../../assest/controlador/ESPECIES_ADO.php';
include_once '../../assest/controlador/PROCESO_ADO.php';
include_once '../../assest/controlador/EXIEXPORTACION_ADO.php';
include_once '../../assest/controlador/EXIINDUSTRIAL_ADO.php';
include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/PLANTA_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';

include_once '../../assest/controlador/EXIMATERIAPRIMA_ADO.php';
include_once '../../assest/modelo/PROCESO.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR

$TPROCESO_ADO =  new TPROCESO_ADO();
$PRODUCTOR_ADO =  new PRODUCTOR_ADO();
$VESPECIES_ADO =  new VESPECIES_ADO();
$ESPECIES_ADO =  new ESPECIES_ADO();

$EXIEXPORTACION_ADO =  new EXIEXPORTACION_ADO();
$EXIINDUSTRIAL_ADO =  new EXIINDUSTRIAL_ADO();
$USUARIO_ADO = new USUARIO_ADO();
$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
$TEMPORADA_ADO = new TEMPORADA_ADO();
$EXIMATERIAPRIMA_ADO =  new EXIMATERIAPRIMA_ADO();
$PROCESO_ADO =  new PROCESO_ADO();
$PROCESO =  new PROCESO();



//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD

$TOTALNETOPROCESADO = 0;
$TOTALEXPORTACIONNETOS = 0;
$TOTALDESHIDRATACIONEXPO = 0;
$TOTALNETOINDUSTRIAL = 0;
$TURNO = "";
$NETOENTRADA = "";
$MENSAJE = "";
$MENSAJEENVIO = "";
$CORREOUSUARIO = "";
$NOMBRECOMPLETOUSUARIO = $_SESSION['NOMBRE_USUARIO'] ?? '';

/** Totales generales usados en el primer foreach */
$TOTALNETOENTRADA = 0;
$TOTALNETO        = 0;
$TOTALEXPORTACION = 0;
$TOTALINDUSTRIAL  = 0;

/** Para cálculos de promedios */
$TOTALPROCESOS          = 0;
$SUMAPDEXPORTACION      = 0;
$SUMAPDEXPORTACIONCD    = 0;
$SUMAPDINDUSTRIAL       = 0;
$SUMAPORCENTAJE         = 0;
$SUMAPDDESHIDRATACION   = 0;


//INICIALIZAR ARREGLOS
$ARRAYEMPRESA = "";
$ARRAYFOLIO2 = "";
$ARRAYPVESPECIES = "";
$ARRAYTPROCESO = "";
$ARRAYPRODUCTOR = "";
$ARRAYVESPECIES = "";

$ARRAYPROCESO = "";
$ARRAYTOTALPROCESO = "";
$ARRAYTOTALPROCESOENTRADA = "";
$ARRAYEXISMATERIPRIMAPROCESO = "";

$cacheProductor = [];
$cacheVespecies = [];
$cacheEspecies = [];
$cacheEmpresa = [];
$cachePlanta = [];
$cacheTemporada = [];

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

function obtenerDatosCorreoProceso($proceso, $PRODUCTOR_ADO, $VESPECIES_ADO, $ESPECIES_ADO, $TPROCESO_ADO, $EMPRESA_ADO, $PLANTA_ADO, $TEMPORADA_ADO)
{
    $numero = $proceso['NUMERO_PROCESO'] ?? 'Sin datos';
    $fecha = $proceso['FECHA_PROCESO'] ?? 'Sin datos';
    $tipoProceso = 'Sin datos';
    $productor = 'Sin datos';
    $variedad = 'Sin datos';
    $especie = 'Sin datos';
    $empresa = 'Sin datos';
    $planta = 'Sin datos';
    $temporada = 'Sin datos';

    if (!empty($proceso['ID_TPROCESO'])) {
        $tproceso = $TPROCESO_ADO->verTproceso($proceso['ID_TPROCESO']);
        if ($tproceso) {
            $tipoProceso = $tproceso[0]['NOMBRE_TPROCESO'];
        }
    }

    if (!empty($proceso['ID_PRODUCTOR'])) {
        $productorData = $PRODUCTOR_ADO->verProductor($proceso['ID_PRODUCTOR']);
        if ($productorData) {
            $productor = $productorData[0]['NOMBRE_PRODUCTOR'];
        }
    }

    if (!empty($proceso['ID_VESPECIES'])) {
        $vespecieData = $VESPECIES_ADO->verVespecies($proceso['ID_VESPECIES']);
        if ($vespecieData) {
            $variedad = $vespecieData[0]['NOMBRE_VESPECIES'];
            $especiesData = $ESPECIES_ADO->verEspecies($vespecieData[0]['ID_ESPECIES']);
            if ($especiesData) {
                $especie = $especiesData[0]['NOMBRE_ESPECIES'];
            }
        }
    }

    if (!empty($proceso['ID_EMPRESA'])) {
        $empresaData = $EMPRESA_ADO->verEmpresa($proceso['ID_EMPRESA']);
        if ($empresaData) {
            $empresa = $empresaData[0]['NOMBRE_EMPRESA'];
        }
    }

    if (!empty($proceso['ID_PLANTA'])) {
        $plantaData = $PLANTA_ADO->verPlanta($proceso['ID_PLANTA']);
        if ($plantaData) {
            $planta = $plantaData[0]['NOMBRE_PLANTA'];
        }
    }

    if (!empty($proceso['ID_TEMPORADA'])) {
        $temporadaData = $TEMPORADA_ADO->verTemporada($proceso['ID_TEMPORADA']);
        if ($temporadaData) {
            $temporada = $temporadaData[0]['NOMBRE_TEMPORADA'];
        }
    }

    return [
        'numero' => $numero,
        'fecha' => $fecha,
        'tipo' => $tipoProceso,
        'productor' => $productor,
        'variedad' => $variedad,
        'especie' => $especie,
        'empresa' => $empresa,
        'planta' => $planta,
        'temporada' => $temporada,
    ];
}

if ($_POST) {
    $IDPROCESO = $_REQUEST['ID'] ?? null;
    $CODIGOVERIFICACION = $_REQUEST['CODIGO_ELIMINAR'] ?? '';
    $CODIGOAPERTURA = $_REQUEST['CODIGO_ABRIR'] ?? '';

    $detalleProceso = $IDPROCESO ? $PROCESO_ADO->verProceso($IDPROCESO) : [];
    $datosProceso = $detalleProceso ? $detalleProceso[0] : [];
    $datosCorreo = obtenerDatosCorreoProceso($datosProceso, $PRODUCTOR_ADO, $VESPECIES_ADO, $ESPECIES_ADO, $TPROCESO_ADO, $EMPRESA_ADO, $PLANTA_ADO, $TEMPORADA_ADO);

    if (isset($_REQUEST['SOLICITARELIMINAR'])) {
        $foliosMateriaPrima = $IDPROCESO ? $EXIMATERIAPRIMA_ADO->buscarPorProceso($IDPROCESO) : [];
        $foliosPt = $IDPROCESO ? $EXIEXPORTACION_ADO->buscarPorProceso($IDPROCESO) : [];
        $industrialProceso = $IDPROCESO ? $EXIINDUSTRIAL_ADO->buscarPorProceso($IDPROCESO) : [];

        if ($foliosMateriaPrima || $foliosPt || $industrialProceso) {
            $MENSAJE = "El proceso no puede eliminarse porque tiene folios de materia prima, industrial o producto terminado activos.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['PROCESO_ELIMINAR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['PROCESO_ELIMINAR_ID'] = $IDPROCESO;
            $_SESSION['PROCESO_ELIMINAR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización eliminación proceso #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó eliminar un proceso." . "\r\n\r\n" .
                "Número de proceso: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de proceso: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de proceso: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Especie: " . $datosCorreo['especie'] . "\r\n" .
                "Variedad: " . $datosCorreo['variedad'] . "\r\n" .
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
        $codigoSesion = $_SESSION['PROCESO_ELIMINAR_CODIGO'] ?? null;
        $idSesion = $_SESSION['PROCESO_ELIMINAR_ID'] ?? null;
        $tiempoSesion = $_SESSION['PROCESO_ELIMINAR_TIEMPO'] ?? 0;

        $foliosMateriaPrima = $IDPROCESO ? $EXIMATERIAPRIMA_ADO->buscarPorProceso($IDPROCESO) : [];
        $foliosPt = $IDPROCESO ? $EXIEXPORTACION_ADO->buscarPorProceso($IDPROCESO) : [];
        $industrialProceso = $IDPROCESO ? $EXIINDUSTRIAL_ADO->buscarPorProceso($IDPROCESO) : [];

        if ($foliosMateriaPrima || $foliosPt || $industrialProceso) {
            $MENSAJE = "El proceso tiene folios activos asociados y no puede ser eliminado.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDPROCESO) {
            $MENSAJE = "No hay una solicitud de eliminación vigente para este proceso.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOVERIFICACION || $CODIGOVERIFICACION != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $PROCESO->__SET('ID_PROCESO', $IDPROCESO);
            $PROCESO_ADO->deshabilitar($PROCESO);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación eliminación proceso #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la eliminación del proceso." . "\r\n\r\n" .
                "Número de proceso: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de proceso: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de proceso: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Especie: " . $datosCorreo['especie'] . "\r\n" .
                "Variedad: " . $datosCorreo['variedad'] . "\r\n" .
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
            $MENSAJEENVIO = $envioOk ? "Proceso eliminado (estado de registro desactivado)." : ($errorEnvio ?: "El proceso se eliminó pero hubo un problema al enviar la notificación.");
            unset($_SESSION['PROCESO_ELIMINAR_CODIGO']);
            unset($_SESSION['PROCESO_ELIMINAR_ID']);
            unset($_SESSION['PROCESO_ELIMINAR_TIEMPO']);
        }
    }

    if (isset($_REQUEST['SOLICITAR_ABRIR'])) {
        if (!$IDPROCESO) {
            $MENSAJE = "No se ha seleccionado un proceso válido.";
        } elseif (!$datosProceso || $datosProceso['ESTADO'] != 0) {
            $MENSAJE = "Solo es posible solicitar apertura para procesos cerrados.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['PROCESO_ABRIR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['PROCESO_ABRIR_ID'] = $IDPROCESO;
            $_SESSION['PROCESO_ABRIR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización apertura proceso #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó la apertura de un proceso cerrado." . "\r\n\r\n" .
                "Número de proceso: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de proceso: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de proceso: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Especie: " . $datosCorreo['especie'] . "\r\n" .
                "Variedad: " . $datosCorreo['variedad'] . "\r\n" .
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
                $MENSAJEENVIO = "Código de autorización para abrir enviado a Maria de los Ángeles y Erwin Isla.";
            } else {
                $MENSAJE = $errorEnvio ?: "No fue posible enviar el código de autorización.";
            }
        }
    }

    if (isset($_REQUEST['CONFIRMAR_ABRIR'])) {
        $codigoSesion = $_SESSION['PROCESO_ABRIR_CODIGO'] ?? null;
        $idSesion = $_SESSION['PROCESO_ABRIR_ID'] ?? null;
        $tiempoSesion = $_SESSION['PROCESO_ABRIR_TIEMPO'] ?? 0;

        if (!$IDPROCESO) {
            $MENSAJE = "No se ha seleccionado un proceso válido.";
        } elseif (!$datosProceso || $datosProceso['ESTADO'] != 0) {
            $MENSAJE = "Solo es posible abrir procesos que estén cerrados.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDPROCESO) {
            $MENSAJE = "No hay una solicitud de apertura vigente para este proceso.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOAPERTURA || $CODIGOAPERTURA != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $PROCESO->__SET('ID_PROCESO', $IDPROCESO);
            $PROCESO_ADO->habilitar($PROCESO);
            $PROCESO_ADO->abierto($PROCESO);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación apertura proceso #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la apertura del proceso." . "\r\n\r\n" .
                "Número de proceso: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de proceso: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de proceso: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Especie: " . $datosCorreo['especie'] . "\r\n" .
                "Variedad: " . $datosCorreo['variedad'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado del proceso cambió a abierto.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Proceso abierto correctamente." : ($errorEnvio ?: "El proceso se abrió pero hubo un problema al enviar la notificación.");
            unset($_SESSION['PROCESO_ABRIR_CODIGO']);
            unset($_SESSION['PROCESO_ABRIR_ID']);
            unset($_SESSION['PROCESO_ABRIR_TIEMPO']);
        }
    }
}


//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES


if ($EMPRESAS  && $PLANTAS && $TEMPORADAS) {
    $ARRAYPROCESO = $PROCESO_ADO->listarProcesoEmpresaPlantaTemporadaCBX($EMPRESAS, $PLANTAS, $TEMPORADAS);
}
$ARRAYPROCESOSBAJOEXPORTACION = [];
if ($ARRAYPROCESO) {

    // cantidad de procesos para promedios
    $TOTALPROCESOS = count($ARRAYPROCESO);

    foreach ($ARRAYPROCESO as $procesoTotales) {
        $TOTALNETOENTRADA += (float) ($procesoTotales['ENTRADA'] ?? 0);
        $TOTALNETO        += (float) ($procesoTotales['NETO'] ?? 0);
        $TOTALEXPORTACION += (float) ($procesoTotales['EXPORTACION'] ?? 0);
        $TOTALINDUSTRIAL  += (float) ($procesoTotales['SUMA_INDUSTRIAL_INFO'] ?? 0);
    }

    foreach ($ARRAYPROCESO as $proceso) {
        $TOTALNETOPROCESADO    += (float) ($proceso['NETO'] ?? 0);
        $TOTALEXPORTACIONNETOS += (float) ($proceso['EXPORTACION'] ?? 0);
        $TOTALNETOINDUSTRIAL   += (float) ($proceso['SUMA_INDUSTRIAL_INFO'] ?? 0);

        $kilosDeshidratacion = ((float) ($proceso['EXPORTACION'] ?? 0)) - ((float) ($proceso['NETO'] ?? 0));
        $TOTALDESHIDRATACIONEXPO += $kilosDeshidratacion;

        // para promedios
        $SUMAPDEXPORTACION    += (float) ($proceso['PDEXPORTACION_PROCESO'] ?? 0);
        $SUMAPDEXPORTACIONCD  += (float) ($proceso['PDEXPORTACIONCD_PROCESO'] ?? 0);
        $SUMAPDINDUSTRIAL     += (float) ($proceso['PDINDUSTRIAL_PROCESO'] ?? 0);
        $SUMAPORCENTAJE       += (float) ($proceso['PORCENTAJE_PROCESO'] ?? 0);
        $SUMAPDDESHIDRATACION += (float)(
            ($proceso['PDEXPORTACIONCD_PROCESO'] ?? 0) - ($proceso['PDEXPORTACION_PROCESO'] ?? 0)
        );

        if ($proceso['PDEXPORTACION_PROCESO'] < 85) {
            $ARRAYPROCESOSBAJOEXPORTACION[] = [
                "numero" => $proceso['NUMERO_PROCESO'],
                "porcentaje" => number_format($proceso['PDEXPORTACION_PROCESO'], 2, ".", "")
            ];
        }
    }
}

$PROMPDEXPORTACION   = $TOTALPROCESOS ? $SUMAPDEXPORTACION / $TOTALPROCESOS : 0;
$PROMPDEXPORTACIONCD = $TOTALPROCESOS ? $SUMAPDEXPORTACIONCD / $TOTALPROCESOS : 0;
$PROMPDINDUSTRIAL    = $TOTALPROCESOS ? $SUMAPDINDUSTRIAL / $TOTALPROCESOS : 0;
$PROMPORCENTAJE      = $TOTALPROCESOS ? $SUMAPORCENTAJE / $TOTALPROCESOS : 0;
$PROMPDDESHIDRATACION= $TOTALPROCESOS ? $SUMAPDDESHIDRATACION / $TOTALPROCESOS : 0;


include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";


?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Agrupado Proceso</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <!- LLAMADA DE LOS ARCHIVOS NECESARIOS PARA DISEÑO Y FUNCIONES BASE DE LA VISTA -!>
        <?php include_once "../../assest/config/urlHead.php"; ?>
        <style>
            .low-export-indicator {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-weight: 600;
                color: #c46b00;
                letter-spacing: 0.02em;
            }

            .low-export-indicator::before {
                content: '';
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: linear-gradient(135deg, #f7e2c2, #f0a040);
                box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.04);
            }
        </style>
        <!- FUNCIONES BASES -!>
            <script type="text/javascript">
                //REDIRECCIONAR A LA PAGINA SELECIONADA
                function irPagina(url) {
                    location.href = "" + url;
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
                            <h3 class="page-title">Packing</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Modulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Packing</li>
                                        <li class="breadcrumb-item" aria-current="page">Proceso</li>
                                        <li class="breadcrumb-item active" aria-current="page"> <a href="#"> Agrupado Proceso </a>   </li>
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
                                        <table id="procesoFruta" class="table-hover " style="width: 100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Numero</th>
                                                    <th>Estado</th>
                                                    <th class="text-center">Administración</th>
                                                    <th class="text-center">Autorizaciones</th>
                                                    <th>Fecha Proceso</th>
                                                    <th>Tipo Proceso</th>
                                                    <th>% Exportación</th>
                                                    <th>Turno </th>
                                                    <th>CSG Productor</th>
                                                    <th>Nombre Productor</th>
                                                    <th>Especie</th>
                                                    <th>Variedad</th>
                                                    <th>Kg. Neto Entrada</th>
                                                    <th>Kg. Neto Expo.</th>
                                                    <th>Kg. Deshi. </th>
                                                    <th>Kg. Con Deshi. </th>
                                                    <th>Kg. IQF</th>
                                                    <th>Kg. Merma</th>
                                                    <th>Kg. Desecho</th>
                                                    <th>Kg. Dif. en Proceso</th>
                                                    <th>Total Industrial</th>
                                                    <th>Kg. Diferencia</th>
                                                    <th>% Deshitación</th>
                                                    <th>% Industrial</th>
                                                    <th>% Total</th>
                                                    <th>PT Embolsado</th>
                                                    <th>Fecha Ingreso</th>
                                                    <th>Fecha Modificacion</th>
                                                    <th class="d-none export-col">Semana Proceso</th>
                                                    <th class="d-none export-col">Empresa</th>
                                                    <th class="d-none export-col">Planta</th>
                                                    <th class="d-none export-col">Temporada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYPROCESO as $r) : ?>

                                                    <?php
                                                    $claseProceso = "";
                                                    $NETOENTRADA = 0;
                                                    $ARRAYTOTALENVASESEMBOLSADO = $PROCESO_ADO->obtenerTotalEnvasesEmbolsado($r['ID_PROCESO']);
                                                    $ENVASESEMBOLSADO = $ARRAYTOTALENVASESEMBOLSADO[0]["ENVASE"];
                                                    $ARRAYEXISMATERIPRIMAPROCESO = $EXIMATERIAPRIMA_ADO->obtenerTotalesProceso2($r['ID_PROCESO']);
                                                    if ($ARRAYEXISMATERIPRIMAPROCESO) {
                                                        $NETOENTRADA = $ARRAYEXISMATERIPRIMAPROCESO[0]['NETOSF'];
                                                    }

                                                    $idVespecie = $r['ID_VESPECIES'] ?? null;
                                                    if ($idVespecie && !array_key_exists($idVespecie, $cacheVespecies)) {
                                                        $cacheVespecies[$idVespecie] = $VESPECIES_ADO->verVespecies($idVespecie);
                                                    }
                                                    $ARRAYVERVESPECIESID = $idVespecie ? ($cacheVespecies[$idVespecie] ?? []) : [];
                                                    if ($ARRAYVERVESPECIESID) {
                                                        $NOMBREVESPECIES = $ARRAYVERVESPECIESID[0]['NOMBRE_VESPECIES'];
                                                        $idEspecie = $ARRAYVERVESPECIESID[0]['ID_ESPECIES'] ?? null;
                                                        if ($idEspecie && !array_key_exists($idEspecie, $cacheEspecies)) {
                                                            $cacheEspecies[$idEspecie] = $ESPECIES_ADO->verEspecies($idEspecie);
                                                        }
                                                        $ARRAYVERESPECIESID = $idEspecie ? ($cacheEspecies[$idEspecie] ?? []) : [];
                                                        if ($ARRAYVERESPECIESID) {
                                                            $NOMBRESPECIES = $ARRAYVERESPECIESID[0]['NOMBRE_ESPECIES'];
                                                        } else {
                                                            $NOMBRESPECIES = "Sin Datos";
                                                        }
                                                    } else {
                                                        $NOMBREVESPECIES = "Sin Datos";
                                                        $NOMBRESPECIES = "Sin Datos";
                                                    }

                                                    $idProductor = $r['ID_PRODUCTOR'] ?? null;
                                                    if ($idProductor && !array_key_exists($idProductor, $cacheProductor)) {
                                                        $cacheProductor[$idProductor] = $PRODUCTOR_ADO->verProductor($idProductor);
                                                    }
                                                    $ARRAYVERPRODUCTORID = $idProductor ? ($cacheProductor[$idProductor] ?? []) : [];
                                                    if ($ARRAYVERPRODUCTORID) {
                                                        $CSGPRODUCTOR = $ARRAYVERPRODUCTORID[0]['CSG_PRODUCTOR'];
                                                        $NOMBREPRODUCTOR = $ARRAYVERPRODUCTORID[0]['NOMBRE_PRODUCTOR'];
                                                    } else {
                                                        $CSGPRODUCTOR = "Sin Datos";
                                                        $NOMBREPRODUCTOR = "Sin Datos";
                                                    }

                                                    $ARRAYTPROCESO = $TPROCESO_ADO->verTproceso($r['ID_TPROCESO']);
                                                    if ($ARRAYTPROCESO) {
                                                        $TPROCESO = $ARRAYTPROCESO[0]['NOMBRE_TPROCESO'];
                                                    } else {
                                                        $TPROCESO = "Sin Datos";
                                                    }
                                                    if ($r['TURNO']) {
                                                        if ($r['TURNO'] == "1") {
                                                            $TURNO = "Dia";
                                                        }
                                                        if ($r['TURNO'] == "2") {
                                                            $TURNO = "Noche";
                                                        }
                                                    } else {
                                                        $TURNO = "Sin Datos";
                                                    }

                                                    $idEmpresa = $r['ID_EMPRESA'] ?? null;
                                                    if ($idEmpresa && !array_key_exists($idEmpresa, $cacheEmpresa)) {
                                                        $cacheEmpresa[$idEmpresa] = $EMPRESA_ADO->verEmpresa($idEmpresa);
                                                    }
                                                    $ARRAYEMPRESA = $idEmpresa ? ($cacheEmpresa[$idEmpresa] ?? []) : [];
                                                    if ($ARRAYEMPRESA) {
                                                        $NOMBREEMPRESA = $ARRAYEMPRESA[0]['NOMBRE_EMPRESA'];
                                                    } else {
                                                        $NOMBREEMPRESA = "Sin Datos";
                                                    }

                                                    $idPlanta = $r['ID_PLANTA'] ?? null;
                                                    if ($idPlanta && !array_key_exists($idPlanta, $cachePlanta)) {
                                                        $cachePlanta[$idPlanta] = $PLANTA_ADO->verPlanta($idPlanta);
                                                    }
                                                    $ARRAYPLANTA = $idPlanta ? ($cachePlanta[$idPlanta] ?? []) : [];
                                                    if ($ARRAYPLANTA) {
                                                        $NOMBREPLANTA = $ARRAYPLANTA[0]['NOMBRE_PLANTA'];
                                                    } else {
                                                        $NOMBREPLANTA = "Sin Datos";
                                                    }

                                                    $idTemporada = $r['ID_TEMPORADA'] ?? null;
                                                    if ($idTemporada && !array_key_exists($idTemporada, $cacheTemporada)) {
                                                        $cacheTemporada[$idTemporada] = $TEMPORADA_ADO->verTemporada($idTemporada);
                                                    }
                                                    $ARRAYTEMPORADA = $idTemporada ? ($cacheTemporada[$idTemporada] ?? []) : [];
                                                    if ($ARRAYTEMPORADA) {
                                                        $NOMBRETEMPORADA = $ARRAYTEMPORADA[0]['NOMBRE_TEMPORADA'];
                                                    } else {
                                                        $NOMBRETEMPORADA = "Sin Datos";
                                                    }

                                                    $esProcesoBajoExportacion = $r['PDEXPORTACION_PROCESO'] < 85;
                                                    if ($esProcesoBajoExportacion) {
                                                        $claseProceso = 'table-warning';
                                                    }

                                                    $kilosDeshidratacion = ((float) $r['EXPORTACION']) - ((float) $r['NETO']);
                                                    $kilosDiferencia = ((float) $r['ENTRADA']) - ((float) $r['EXPORTACION']) - ((float) $r['SUMA_INDUSTRIAL_INFO']) - ((float) $r['SUMA_DIFERENCIA_PROCESO']);
                                                    $porcentajeDeshidratacion = ((float) $r['PDEXPORTACIONCD_PROCESO']) - ((float) $r['PDEXPORTACION_PROCESO']);
                                                    ?>
                                                    <tr class="text-center <?php echo $claseProceso; ?>">
                                                        <td> <?php echo $r['NUMERO_PROCESO']; ?> </td>
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
                                                                            <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_PROCESO']; ?>" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroProceso" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URLO" name="URLO" value="listarProceso" />
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
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" <?php if ($r['ESTADO'] == "1") { echo "disabled"; } ?> name="informe" title="Informe" Onclick="abrirPestana('../../assest/documento/informeProceso.php?parametro=<?php echo $r['ID_PROCESO']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Informe
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Tarjas">
                                                                                <button type="button" class="btn  btn-danger btn-block" id="defecto" name="tarjas" title="Tarjas" Onclick="abrirPestana('../../assest/documento/informeTarjasProceso.php?parametro=<?php echo $r['ID_PROCESO']; ?>'); ">
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
                                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-block" data-toggle="modal" data-target="#modalEliminarProceso" data-id="<?php echo $r['ID_PROCESO']; ?>" data-numero="<?php echo $r['NUMERO_PROCESO']; ?>">
                                                                        Eliminar proceso
                                                                    </button>
                                                                <?php } ?>
                                                                <?php if ($r['ESTADO'] == "0") { ?>
                                                                    <button type="button" class="btn btn-outline-success btn-sm btn-block" data-toggle="modal" data-target="#modalAbrirProceso" data-id="<?php echo $r['ID_PROCESO']; ?>" data-numero="<?php echo $r['NUMERO_PROCESO']; ?>">
                                                                        Abrir proceso
                                                                    </button>
                                                                <?php } ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $r['FECHA']; ?></td>
                                                        <td><?php echo $TPROCESO; ?></td>
                                                        <td>
                                                            <?php if ($esProcesoBajoExportacion) { ?>
                                                                <span class="low-export-indicator" title="Bajo 85% de exportación"><?php echo $r['PDEXPORTACION_PROCESO']; ?>%</span>
                                                            <?php } else { ?>
                                                                <?php echo $r['PDEXPORTACION_PROCESO']; ?>%
                                                            <?php } ?>
                                                        </td>
                                                        <td><?php echo $TURNO; ?> </td>
                                                        <td><?php echo $CSGPRODUCTOR; ?></td>
                                                        <td><?php echo $NOMBREPRODUCTOR; ?></td>
                                                        <td><?php echo $NOMBRESPECIES; ?></td>
                                                        <td><?php echo $NOMBREVESPECIES; ?></td>
                                                        <td><?php echo $r['ENTRADA']; ?></td>
                                                        <td><?php echo $r['NETO']; ?></td>
                                                        <td><?php echo number_format($kilosDeshidratacion, 2, ".", ""); ?></td>
                                                        <td><?php echo $r['EXPORTACION']; ?></td>
                                                        <td><?php echo $r['IQF_INFO']; ?></td>
                                                        <td><?php echo $r['MERMA_INFO']; ?></td>
                                                        <td><?php echo $r['DESECHO_INFO']; ?></td>
                                                        <td><?php echo $r['SUMA_DIFERENCIA_PROCESO']; ?></td>
                                                        <td><?php echo $r['SUMA_INDUSTRIAL_INFO']; ?></td>
                                                        <td><?php echo number_format($kilosDiferencia, 2, ".", ""); ?></td>
                                                        <td><?php echo number_format($porcentajeDeshidratacion, 2, ".", ""); ?></td>
                                                        <td><?php echo $r['PDINDUSTRIAL_PROCESO']; ?></td>
                                                        <td><?php echo number_format($r['PORCENTAJE_PROCESO'], 2, ".", "");  ?></td>
                                                        <td><?php echo $ENVASESEMBOLSADO; ?></td>
                                                        <td><?php echo $r['INGRESO']; ?></td>
                                                        <td><?php echo $r['MODIFICACION']; ?></td>
                                                        <td class="d-none export-col"><?php echo $r['SEMANA']; ?></td>
                                                        <td class="d-none export-col"><?php echo $NOMBREEMPRESA; ?></td>
                                                        <td class="d-none export-col"><?php echo $NOMBREPLANTA; ?></td>
                                                        <td class="d-none export-col"><?php echo $NOMBRETEMPORADA; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>                                               
                        <div class="box-footer">
                                <div class="btn-toolbar mb-3" role="toolbar" aria-label="Totales generales">
                                    <div class="form-row align-items-center" role="group" aria-label="Totales principales">
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                      <div class="input-group-text">Kg netos procesado</div>
                                                      <button class="btn btn-default" id="TOTALNETOPROCESADO" name="TOTALNETOPROCESADO" >
                                                          <?php echo number_format($TOTALNETOPROCESADO, 2, ',', '.'); ?>
                                                      </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                      <div class="input-group-text">Kg Netos exportación</div>
                                                      <button class="btn btn-default" id="TOTALEXPORTACION" name="TOTALEXPORTACION" >
                                                          <?php echo number_format($TOTALEXPORTACIONNETOS, 2, ',', '.'); ?>
                                                      </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                      <div class="input-group-text">Kg con deshidratación expo</div>
                                                      <button class="btn btn-default" id="TOTALDESHIDRATACIONEXPO" name="TOTALDESHIDRATACIONEXPO" >
                                                          <?php echo number_format($TOTALDESHIDRATACIONEXPO, 2, ',', '.'); ?>
                                                      </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                      <div class="input-group-text">Kg Netos Industrial</div>
                                                      <button class="btn btn-default" id="TOTALNETOINDUSTRIAL" name="TOTALNETOINDUSTRIAL" >
                                                          <?php echo number_format($TOTALNETOINDUSTRIAL, 2, ',', '.'); ?>
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
        <!-- Modal Eliminar Proceso -->
        <div class="modal fade" id="modalEliminarProceso" tabindex="-1" role="dialog" aria-labelledby="modalEliminarProcesoLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEliminarProcesoLabel">Autorización para eliminar proceso</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">Se enviará un código de confirmación a Maria de los Ángeles y Erwin Isla. Ingrésalo para completar la eliminación.</p>
                            <p class="font-weight-bold">Proceso N° <span class="numero-proceso-eliminar"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroProceso">
                            <input type="hidden" name="URLO" value="listarProceso">
                            <div class="form-group">
                                <label for="codigoEliminar">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoEliminar" name="CODIGO_ELIMINAR" placeholder="Ingresa el código recibido">
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

        <!-- Modal Abrir Proceso -->
        <div class="modal fade" id="modalAbrirProceso" tabindex="-1" role="dialog" aria-labelledby="modalAbrirProcesoLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAbrirProcesoLabel">Autorización para abrir proceso</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">Solicita y confirma el código enviado a Maria de los Ángeles y Erwin Isla para abrir el proceso cerrado.</p>
                            <p class="font-weight-bold">Proceso N° <span class="numero-proceso-abrir"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroProceso">
                            <input type="hidden" name="URLO" value="listarProceso">
                            <div class="form-group">
                                <label for="codigoAbrir">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoAbrir" name="CODIGO_ABRIR" placeholder="Ingresa el código recibido">
                                <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-success" name="SOLICITAR_ABRIR">Solicitar código</button>
                            <button type="submit" class="btn btn-success" name="CONFIRMAR_ABRIR">Abrir proceso</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                const $procesoTable = $('#procesoFruta');
                const visibleRows = Math.min($procesoTable.find('tbody tr').length, 12);
                const rowHeight = $procesoTable.find('tbody tr:first').outerHeight() || 40;
                const scrollHeight = (visibleRows || 1) * rowHeight + 12;

                if ($.fn.DataTable.isDataTable('#procesoFruta')) {
                    $('#procesoFruta').DataTable().destroy();
                }

                $('#procesoFruta').DataTable({
                    order: [[0, 'desc']],
                    paging: false,
                    info: false,
                    searching: true,
                    searchDelay: 150,
                    deferRender: true,
                    lengthChange: false,
                    scrollX: true,
                    scrollResize: true,
                    scrollY: scrollHeight + 'px',
                    scrollCollapse: true,
                    language: {
                        search: "Buscar:",
                    },
                    columnDefs: [
                        { targets: [28, 29, 30, 31], visible: false }
                    ],
                    dom: 'Brtip',
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: 'Excel',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31]
                            }
                        },
                        {
                            extend: 'searchBuilder',
                            text: 'Filtros'
                        }
                    ]
                });

                // IMPORTANTE: ya no se oculta el buscador de DataTables
                // $('#procesoFruta_filter').remove();

                $('#modalEliminarProceso').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idProceso = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idProceso);
                    modal.find('.numero-proceso-eliminar').text(numero || '');
                });

                $('#modalAbrirProceso').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idProceso = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idProceso);
                    modal.find('.numero-proceso-abrir').text(numero || '');
                });
            });
        </script>
</body>

</html>
