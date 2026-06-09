<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "../../assest/controlador/CALIDADCONTROL_ADO.php";
include_once "../../assest/controlador/CALIDADINSPECTOR_ADO.php";
include_once "../../assest/controlador/CALIDADPARAMETRO_ADO.php";
include_once "../../assest/controlador/ESPECIES_ADO.php";

$CALIDADCONTROL_ADO = new CALIDADCONTROL_ADO();
$CALIDADINSPECTOR_ADO = new CALIDADINSPECTOR_ADO();
$CALIDADPARAMETRO_ADO = new CALIDADPARAMETRO_ADO();
$ESPECIES_ADO = new ESPECIES_ADO();

$MENSAJE = "";
$TIPOMENSAJE = "";

$GRUPOS = array(
    "CALIBRES" => "Distribucion de Calibres",
    "PRESIONES" => "Presiones",
    "PARAMETROS" => "Parametros Generales",
    "DEFECTOS_CONDICION" => "Defectos de Condicion",
    "DEFECTOS_CALIDAD" => "Defectos de Calidad"
);

function controlesTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function controlesNumero($valor) {
    if ($valor === "" || $valor === null) {
        return 0;
    }
    return (float) str_replace(",", ".", (string) $valor);
}

function controlesPorcentaje($valor, $muestra) {
    if ((float) $muestra <= 0) {
        return 0;
    }
    return round(((float) $valor / (float) $muestra) * 100, 4);
}

function controlesEsFirme($nombre) {
    $nombre = strtoupper((string) $nombre);
    return strpos($nombre, "FIRME") !== false;
}

function controlesDetalleControles($CALIDADCONTROL_ADO, $controles) {
    $resultado = array();
    foreach ($controles as $control) {
        $detalleControl = array();
        $detalles = $CALIDADCONTROL_ADO->listarDetalle($control["ID_CALIDAD_CONTROL"]);
        foreach ($detalles as $detalle) {
            $detalleControl[$detalle["ID_CALIDAD_PARAMETRO"]] = $detalle["VALOR_NUMERICO"];
        }
        $resultado[$control["ID_CALIDAD_CONTROL"]] = $detalleControl;
    }
    return $resultado;
}

$ARRAYESPECIES = $ESPECIES_ADO->listarEspeciesCalidadEmpresaCBX($EMPRESAS);
$ARRAYINSPECTORES = $CALIDADINSPECTOR_ADO->listarInspectorActivo($EMPRESAS, $TEMPORADAS);

$ID_ESPECIES = $_GET["ID_ESPECIES"] ?? ($_POST["ID_ESPECIES"] ?? "");
$ID_RECEPCION = $_GET["ID_RECEPCION"] ?? ($_POST["ID_RECEPCION"] ?? "");

$ARRAYRECEPCIONES = array();
$ARRAYCONTROLES = array();
$ARRAYDETALLESCONTROL = array();
$PARAMETROS_GRUPO = array();

if ($ID_ESPECIES !== "") {
    $ARRAYRECEPCIONES = $CALIDADCONTROL_ADO->listarRecepcionMateriaPrimaCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES);
    if ($ID_RECEPCION !== "") {
        $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $ID_RECEPCION);
        $ARRAYDETALLESCONTROL = controlesDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
        foreach ($GRUPOS as $codigoGrupo => $nombreGrupo) {
            $PARAMETROS_GRUPO[$codigoGrupo] = $CALIDADPARAMETRO_ADO->listarParametroActivo($EMPRESAS, $TEMPORADAS, $ID_ESPECIES, "RECEPCION", $codigoGrupo);
        }
    }
}

if (isset($_POST["GUARDARCONTROL"])) {
    $puedeGuardar = true;
    $IDCONTROL_EDITAR = $_POST["ID_CALIDAD_CONTROL"] ?? "";
    if ($_POST["ID_ESPECIES"] === "" || $_POST["ID_RECEPCION"] === "" || $_POST["ID_CALIDAD_INSPECTOR"] === "") {
        $puedeGuardar = false;
        $MENSAJE = "Debe seleccionar especie, recepcion e inspector.";
        $TIPOMENSAJE = "warning";
    }
    if (!in_array($_POST["MODO_INGRESO"], array("AGRUPADO", "FOLIO"), true)) {
        $puedeGuardar = false;
        $MENSAJE = "Modo de ingreso no valido.";
        $TIPOMENSAJE = "warning";
    }

    if ($puedeGuardar) {
        $muestra = controlesNumero($_POST["MUESTRA_GRAMOS"]);
        $valores = $_POST["VALOR_PARAMETRO"] ?? array();
        $nombres = $_POST["NOMBRE_PARAMETRO"] ?? array();
        $grupos = $_POST["GRUPO_PARAMETRO"] ?? array();

        $valoresScore = array();

        foreach ($valores as $idParametro => $valorIngresado) {
            $valor = controlesNumero($valorIngresado);
            $grupo = $grupos[$idParametro] ?? "";
            $nombre = $nombres[$idParametro] ?? "";
            $valoresScore[] = array("grupo" => $grupo, "nombre" => $nombre, "valor" => $valor);
        }

        $CALCULO_SCORE = $CALIDADCONTROL_ADO->calcularResolucionScore(
            $valoresScore,
            $muestra,
            $EMPRESAS,
            $TEMPORADAS,
            $_POST["ID_ESPECIES"],
            $_POST["ID_RECEPCION"],
            $_POST["MODO_INGRESO"],
            $_POST["ID_FOLIO"] ?? null
        );
        $porcDefectoCondicion = $CALCULO_SCORE["PORC_DEFECTO_CONDICION"];
        $porcDefectoCalidad = $CALCULO_SCORE["PORC_DEFECTO_CALIDAD"];
        $porcFirmeza = $CALCULO_SCORE["PORC_FIRMEZA"];
        $porcEstimadoExportacion = $CALCULO_SCORE["PORC_ESTIMADO_EXPORTACION"];
        $scoreGeneral = $CALCULO_SCORE["SCORE_GENERAL"];
        $grupoScore = $CALCULO_SCORE["GRUPO_SCORE"];
        $resultadoGeneral = $CALCULO_SCORE["RESULTADO_GENERAL"];

        $numeroOperacion = "";
        foreach ($ARRAYRECEPCIONES as $recepcion) {
            if ((string) $recepcion["ID_RECEPCION"] === (string) $_POST["ID_RECEPCION"]) {
                $numeroOperacion = $recepcion["NUMERO_RECEPCION"];
                break;
            }
        }

        $CONTROL = new CALIDADCONTROL();
        $CONTROL->__SET("ETAPA", "RECEPCION");
        $CONTROL->__SET("MODO_INGRESO", $_POST["MODO_INGRESO"]);
        $CONTROL->__SET("TIPO_PRODUCTO", "MP");
        $CONTROL->__SET("ID_OPERACION", $_POST["ID_RECEPCION"]);
        $CONTROL->__SET("NUMERO_OPERACION", $numeroOperacion);
        $CONTROL->__SET("FECHA", date("Y-m-d"));
        $CONTROL->__SET("HORA", date("H:i:s"));
        $CONTROL->__SET("ID_EMPRESA", $EMPRESAS);
        $CONTROL->__SET("ID_PLANTA", $PLANTAS);
        $CONTROL->__SET("ID_TEMPORADA", $TEMPORADAS);
        $CONTROL->__SET("ID_ESPECIES", $_POST["ID_ESPECIES"]);
        $CONTROL->__SET("ID_USUARIO", $IDUSUARIOS);
        $CONTROL->__SET("ID_CALIDAD_INSPECTOR", $_POST["ID_CALIDAD_INSPECTOR"]);
        $CONTROL->__SET("MUESTRA_GRAMOS", $muestra);
        $CONTROL->__SET("RESULTADO_GENERAL", $resultadoGeneral);
        $CONTROL->__SET("ESTADO_CONTROL", "ABIERTO");
        $CONTROL->__SET("PORC_DEFECTO_CONDICION", $porcDefectoCondicion);
        $CONTROL->__SET("PORC_DEFECTO_CALIDAD", $porcDefectoCalidad);
        $CONTROL->__SET("PORC_FIRMEZA", $porcFirmeza);
        $CONTROL->__SET("PORC_ESTIMADO_EXPORTACION", $porcEstimadoExportacion);
        $CONTROL->__SET("SCORE_GENERAL", $scoreGeneral);
        $CONTROL->__SET("GRUPO_SCORE", $grupoScore);
        $CONTROL->__SET("OBSERVACION", $_POST["OBSERVACION"]);

        if ($IDCONTROL_EDITAR !== "") {
            $CONTROL->__SET("ID_CALIDAD_CONTROL", $IDCONTROL_EDITAR);
            $CALIDADCONTROL_ADO->actualizarControlRecepcion($CONTROL);
            $CALIDADCONTROL_ADO->limpiarDetalleControl($IDCONTROL_EDITAR);
            $IDCONTROL = $IDCONTROL_EDITAR;
        } else {
            $IDCONTROL = $CALIDADCONTROL_ADO->agregarControl($CONTROL);
        }

        foreach ($valores as $idParametro => $valorIngresado) {
            if ($valorIngresado === "") {
                continue;
            }
            $DETALLE = new CALIDADCONTROLDETALLE();
            $DETALLE->__SET("ID_CALIDAD_CONTROL", $IDCONTROL);
            $DETALLE->__SET("ID_CALIDAD_PARAMETRO", $idParametro);
            $DETALLE->__SET("TIPO_PARAMETRO", $grupos[$idParametro] ?? "");
            $DETALLE->__SET("NOMBRE_PARAMETRO", $nombres[$idParametro] ?? "");
            $DETALLE->__SET("UNIDAD_MEDIDA", "g");
            $DETALLE->__SET("VALOR_NUMERICO", controlesNumero($valorIngresado));
            $DETALLE->__SET("VALOR_TEXTO", null);
            $DETALLE->__SET("RESULTADO", null);
            $DETALLE->__SET("OBSERVACION", null);
            $CALIDADCONTROL_ADO->agregarDetalle($DETALLE);
        }

        $MENSAJE = ($IDCONTROL_EDITAR !== "" ? "Control actualizado. " : "Control guardado. ") . "Score: " . $scoreGeneral . " (" . $grupoScore . ") - " . $resultadoGeneral;
        $TIPOMENSAJE = "success";
        $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $_POST["ID_ESPECIES"], $_POST["ID_RECEPCION"]);
        $ARRAYDETALLESCONTROL = controlesDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
    }
}

if (isset($_POST["CERRARCONTROL"])) {
    $CONTROL = new CALIDADCONTROL();
    $CONTROL->__SET("ID_CALIDAD_CONTROL", $_POST["ID_CALIDAD_CONTROL"]);
    $CONTROL->__SET("ID_EMPRESA", $EMPRESAS);
    $CONTROL->__SET("ID_PLANTA", $PLANTAS);
    $CONTROL->__SET("ID_TEMPORADA", $TEMPORADAS);
    $CONTROL->__SET("FECHA_CIERRE", date("Y-m-d"));
    $CONTROL->__SET("HORA_CIERRE", date("H:i:s"));
    $CONTROL->__SET("ID_USUARIO_CIERRE", $IDUSUARIOS);
    $CONTROL->__SET("OBSERVACION_CIERRE", $_POST["OBSERVACION_CIERRE"] ?? "");
    $CALIDADCONTROL_ADO->cerrarControl($CONTROL);

    $MENSAJE = "Control cerrado y aprobacion registrada.";
    $TIPOMENSAJE = "success";

    if ($ID_ESPECIES !== "" && $ID_RECEPCION !== "") {
        $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $ID_RECEPCION);
        $ARRAYDETALLESCONTROL = controlesDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
    }
}

$CONTROLES_EDICION = array();
foreach ($ARRAYCONTROLES as $control) {
    $CONTROLES_EDICION[$control["ID_CALIDAD_CONTROL"]] = array(
        "id" => $control["ID_CALIDAD_CONTROL"],
        "modo" => $control["MODO_INGRESO"],
        "folio" => $control["ID_FOLIO_CONTROL"],
        "folioTexto" => $control["MODO_INGRESO"] === "AGRUPADO" ? "Recepcion completa" : "Folio " . $control["FOLIOS"],
        "inspector" => $control["ID_CALIDAD_INSPECTOR"],
        "muestra" => $control["MUESTRA_GRAMOS"],
        "observacion" => $control["OBSERVACION"],
        "detalles" => $ARRAYDETALLESCONTROL[$control["ID_CALIDAD_CONTROL"]] ?? array()
    );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Controles de Calidad</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
        #modalControlCalidad .modal-dialog {
            max-width: 1180px;
        }
        #modalControlCalidad .modal-body {
            background: #f7f8fb;
            max-height: calc(100vh - 190px);
            overflow-y: auto;
        }
        .calidad-bloque {
            background: #fff;
            border: 1px solid #e6e9f0;
            border-radius: 6px;
            margin-bottom: 14px;
        }
        .calidad-bloque-titulo {
            border-bottom: 1px solid #e6e9f0;
            color: #172b4d;
            font-weight: 700;
            padding: 10px 14px;
        }
        .calidad-matriz {
            display: grid;
            gap: 0;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 0;
        }
        .calidad-matriz.datos-generales {
            grid-template-columns: 2fr 1fr 2fr;
        }
        .calidad-campo {
            border-bottom: 1px solid #edf0f5;
            border-right: 1px solid #edf0f5;
            min-width: 0;
            padding: 10px 12px;
        }
        .calidad-campo label {
            color: #5d697b;
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .calidad-campo .form-control,
        .calidad-campo .select2-container {
            width: 100% !important;
        }
        .calidad-campo .select2-selection {
            min-height: 38px;
        }
        .calidad-input-porcentaje {
            align-items: stretch;
            display: flex;
            gap: 6px;
        }
        .calidad-input-porcentaje .form-control {
            min-width: 0;
        }
        .calidad-porcentaje {
            align-items: center;
            background: #eef3f8;
            border: 1px solid #d9e2ec;
            border-radius: 4px;
            color: #17324d;
            display: flex;
            flex: 0 0 70px;
            font-size: 12px;
            font-weight: 700;
            justify-content: center;
            min-height: 38px;
            padding: 0 8px;
            white-space: nowrap;
        }
        @media (max-width: 991px) {
            .calidad-matriz,
            .calidad-matriz.datos-generales {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 575px) {
            .calidad-matriz,
            .calidad-matriz.datos-generales {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuCalidad.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Controles de Calidad</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Calidad</li>
                                        <li class="breadcrumb-item" aria-current="page">Registro</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Controles</a></li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>

                <section class="content">
                    <div class="box">
                        <div class="box-header with-border">
                            <h4 class="box-title">Seleccion</h4>
                        </div>
                        <form method="GET">
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Especie</label>
                                            <select class="form-control select2" name="ID_ESPECIES" required onchange="this.form.submit()">
                                                <option></option>
                                                <?php foreach ($ARRAYESPECIES as $especie) { ?>
                                                    <option value="<?php echo $especie["ID_ESPECIES"]; ?>" <?php echo ((string) $ID_ESPECIES === (string) $especie["ID_ESPECIES"]) ? "selected" : ""; ?>><?php echo controlesTexto($especie["NOMBRE_ESPECIES"]); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Recepcion</label>
                                            <select class="form-control select2" name="ID_RECEPCION" onchange="this.form.submit()" <?php echo $ID_ESPECIES === "" ? "disabled" : ""; ?>>
                                                <option></option>
                                                <?php foreach ($ARRAYRECEPCIONES as $recepcion) { ?>
                                                    <option value="<?php echo $recepcion["ID_RECEPCION"]; ?>" <?php echo ((string) $ID_RECEPCION === (string) $recepcion["ID_RECEPCION"]) ? "selected" : ""; ?>>
                                                        <?php echo "N " . controlesTexto($recepcion["NUMERO_RECEPCION"]) . " / Guia " . controlesTexto($recepcion["NUMERO_GUIA_RECEPCION"]) . " / " . controlesTexto($recepcion["FECHA_RECEPCION"]); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group pt-25">
                                            <button type="submit" class="btn btn-rounded btn-info">Filtrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($ID_ESPECIES !== "" && $ID_RECEPCION !== "") { ?>
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">Controles ingresados</h4>
                                <div class="box-controls pull-right">
                                    <a href="registroRecepcion.php?ID_ESPECIES=<?php echo controlesTexto($ID_ESPECIES); ?>&ID_RECEPCION=<?php echo controlesTexto($ID_RECEPCION); ?>" class="btn btn-rounded btn-secondary btn-sm">
                                        <i class="fa fa-arrow-left"></i> Ir a Recepcion
                                    </a>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered mb-0" id="tablaControlesCalidad">
                                        <thead>
                                            <tr>
                                                <th>Estado</th>
                                                <th>Tipo</th>
                                                <th>Folios</th>
                                                <th>Inspector</th>
                                                <th class="text-center">Resolucion</th>
                                                <th class="text-center">Score</th>
                                                <th class="text-center">Grupo</th>
                                                <th class="text-right">% Condicion</th>
                                                <th class="text-right">% Calidad</th>
                                                <th class="text-right">% Firmeza</th>
                                                <th class="text-right">% Exportacion</th>
                                                <th class="text-center">Cierre</th>
                                                <th class="text-center">PDF</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ARRAYCONTROLES as $control) { ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($control["ESTADO_CONTROL"] === "CERRADO") { ?>
                                                            <span class="badge badge-success">CERRADO</span>
                                                        <?php } else { ?>
                                                            <span class="badge badge-warning">ABIERTO</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td><?php echo controlesTexto($control["MODO_INGRESO"]); ?></td>
                                                    <td><?php echo controlesTexto($control["MODO_INGRESO"] === "AGRUPADO" ? "Recepcion completa" : $control["FOLIOS"]); ?></td>
                                                    <td><?php echo controlesTexto($control["NOMBRE_INSPECTOR"]); ?></td>
                                                    <td class="text-center">
                                                        <span class="badge" style="background: <?php echo controlesTexto($CALIDADCONTROL_ADO->colorResolucion($control["RESULTADO_GENERAL"])); ?>; color: #fff;">
                                                            <?php echo controlesTexto($control["RESULTADO_GENERAL"]); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center"><?php echo controlesTexto($control["SCORE_GENERAL"] ?? ""); ?></td>
                                                    <td class="text-center"><?php echo controlesTexto($control["GRUPO_SCORE"] ?? ""); ?></td>
                                                    <td class="text-right"><?php echo controlesTexto($control["PORC_DEFECTO_CONDICION"]); ?></td>
                                                    <td class="text-right"><?php echo controlesTexto($control["PORC_DEFECTO_CALIDAD"]); ?></td>
                                                    <td class="text-right"><?php echo controlesTexto($control["PORC_FIRMEZA"]); ?></td>
                                                    <td class="text-right"><?php echo controlesTexto($control["PORC_ESTIMADO_EXPORTACION"]); ?></td>
                                                    <td class="text-center">
                                                        <?php if ($control["ESTADO_CONTROL"] === "CERRADO") { ?>
                                                            <?php echo controlesTexto($control["FECHA_CIERRE"]); ?>
                                                        <?php } else { ?>
                                                            <button type="button" class="btn btn-rounded btn-warning btn-sm editar-control-calidad"
                                                                data-toggle="modal"
                                                                data-target="#modalControlCalidad"
                                                                data-id="<?php echo controlesTexto($control["ID_CALIDAD_CONTROL"]); ?>">
                                                                <i class="fa fa-edit"></i> Editar
                                                            </button>
                                                            <button type="button" class="btn btn-rounded btn-success btn-sm cerrar-control-calidad"
                                                                data-toggle="modal"
                                                                data-target="#modalCerrarControl"
                                                                data-id="<?php echo controlesTexto($control["ID_CALIDAD_CONTROL"]); ?>"
                                                                data-resolucion="<?php echo controlesTexto($control["RESULTADO_GENERAL"]); ?>"
                                                                data-score="<?php echo controlesTexto($control["SCORE_GENERAL"] ?? ""); ?>"
                                                                data-exportacion="<?php echo controlesTexto($control["PORC_ESTIMADO_EXPORTACION"]); ?>">
                                                                <i class="fa fa-check"></i> Cerrar
                                                            </button>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($control["ESTADO_CONTROL"] === "CERRADO") { ?>
                                                            <button type="button" class="btn btn-rounded btn-danger btn-sm"
                                                                onclick="window.open('../../assest/documento/informeCalidadRecepcion.php?parametro=<?php echo controlesTexto($control["ID_CALIDAD_CONTROL"]); ?>&usuario=<?php echo controlesTexto($IDUSUARIOS); ?>', '_blank');">
                                                                <i class="fa fa-file-pdf-o"></i> PDF
                                                            </button>
                                                        <?php } else { ?>
                                                            <span class="text-muted">Pendiente</span>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            <?php if (empty($ARRAYCONTROLES)) { ?>
                                                <tr>
                                                    <td colspan="13" class="text-center text-muted">No hay controles ingresados para esta recepcion.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="modalControlCalidad" tabindex="-1" role="dialog" aria-labelledby="modalControlCalidadLabel" aria-hidden="true">
                            <div class="modal-dialog modal-xl" role="document">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="ID_CALIDAD_CONTROL" id="modalIdControl" value="">
                                        <input type="hidden" name="ID_ESPECIES" value="<?php echo controlesTexto($ID_ESPECIES); ?>">
                                        <input type="hidden" name="MODO_INGRESO" id="modalModoIngreso" value="AGRUPADO">
                                        <input type="hidden" name="ID_RECEPCION" value="<?php echo controlesTexto($ID_RECEPCION); ?>">
                                        <input type="hidden" name="ID_FOLIO" id="modalIdFolio" value="">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalControlCalidadLabel">Editar control calidad</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-info mb-20">
                                                <strong id="modalModoTexto">Control agrupado</strong> - <span id="modalFolioTexto">Recepcion completa</span>
                                            </div>

                                            <div class="calidad-bloque">
                                                <div class="calidad-bloque-titulo">Datos generales</div>
                                                <div class="calidad-matriz datos-generales">
                                                    <div class="calidad-campo">
                                                        <label>Inspector</label>
                                                        <select class="form-control select2-modal" name="ID_CALIDAD_INSPECTOR" required>
                                                            <option></option>
                                                            <?php foreach ($ARRAYINSPECTORES as $inspector) { ?>
                                                                <option value="<?php echo $inspector["ID_CALIDAD_INSPECTOR"]; ?>"><?php echo controlesTexto($inspector["NOMBRE_INSPECTOR"]); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                    <div class="calidad-campo">
                                                        <label>Muestra gramos</label>
                                                        <input type="number" step="0.01" class="form-control" name="MUESTRA_GRAMOS" value="1000" required>
                                                    </div>
                                                    <div class="calidad-campo">
                                                        <label>Observacion</label>
                                                        <input type="text" class="form-control" name="OBSERVACION" maxlength="500">
                                                    </div>
                                                </div>
                                            </div>

                                            <?php foreach ($GRUPOS as $codigoGrupo => $nombreGrupo) { ?>
                                                <?php if (!empty($PARAMETROS_GRUPO[$codigoGrupo])) { ?>
                                                    <div class="calidad-bloque">
                                                        <div class="calidad-bloque-titulo"><?php echo controlesTexto($nombreGrupo); ?></div>
                                                        <div class="calidad-matriz">
                                                            <?php foreach ($PARAMETROS_GRUPO[$codigoGrupo] as $parametro) { ?>
                                                                <div class="calidad-campo">
                                                                    <label><?php echo controlesTexto($parametro["NOMBRE_PARAMETRO"]); ?></label>
                                                                    <?php $mostrarPorcentaje = $codigoGrupo !== "PARAMETROS"; ?>
                                                                    <div class="<?php echo $mostrarPorcentaje ? "calidad-input-porcentaje" : ""; ?>">
                                                                        <input type="number" step="0.01" class="form-control valor-parametro-calidad" data-calcula-porcentaje="<?php echo $mostrarPorcentaje ? "1" : "0"; ?>" name="VALOR_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" <?php echo ((int) $parametro["ES_REQUERIDO"] === 1) ? "required" : ""; ?>>
                                                                        <?php if ($mostrarPorcentaje) { ?>
                                                                            <span class="calidad-porcentaje">0,00%</span>
                                                                        <?php } ?>
                                                                    </div>
                                                                    <input type="hidden" name="NOMBRE_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" value="<?php echo controlesTexto($parametro["NOMBRE_PARAMETRO"]); ?>">
                                                                    <input type="hidden" name="GRUPO_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" value="<?php echo controlesTexto($codigoGrupo); ?>">
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-rounded btn-secondary" data-dismiss="modal">Cerrar</button>
                                            <button type="submit" class="btn btn-rounded btn-primary" id="modalBotonGuardarControl" name="GUARDARCONTROL" value="1">
                                                <i class="fa fa-save"></i> Actualizar Control
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="modalCerrarControl" tabindex="-1" role="dialog" aria-labelledby="modalCerrarControlLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="ID_ESPECIES" value="<?php echo controlesTexto($ID_ESPECIES); ?>">
                                        <input type="hidden" name="ID_RECEPCION" value="<?php echo controlesTexto($ID_RECEPCION); ?>">
                                        <input type="hidden" name="ID_CALIDAD_CONTROL" id="cerrarIdControl" value="">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalCerrarControlLabel">Cerrar control calidad</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-success">
                                                Resolucion: <strong id="cerrarResolucion"></strong> / Score: <strong id="cerrarScore"></strong> / Exportacion: <strong id="cerrarExportacion"></strong>%
                                            </div>
                                            <div class="form-group">
                                                <label>Observacion cierre</label>
                                                <textarea class="form-control" name="OBSERVACION_CIERRE" rows="3" maxlength="500"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-rounded btn-secondary" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-rounded btn-success" name="CERRARCONTROL" value="1">
                                                <i class="fa fa-check"></i> Confirmar cierre
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </section>
            </div>
        </div>

        <?php include_once "../../assest/config/footer.php"; ?>
        <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>
    <script>
        var controlesCalidadEdicion = <?php echo json_encode($CONTROLES_EDICION, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        $(document).ready(function() {
            $('.select2').select2();
            $('.select2-modal').select2({
                dropdownParent: $('#modalControlCalidad'),
                width: '100%'
            });

            if ($.fn.DataTable && $('#tablaControlesCalidad').length) {
                $('#tablaControlesCalidad').DataTable({
                    pageLength: 25,
                    order: [[0, 'asc']]
                });
            }

            function numeroCalidad(valor) {
                valor = (valor || '').toString().replace(',', '.');
                var numero = parseFloat(valor);
                return isNaN(numero) ? 0 : numero;
            }

            function formatoPorcentajeCalidad(valor) {
                return valor.toLocaleString('es-CL', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + '%';
            }

            function actualizarPorcentajesCalidad(modal) {
                var muestra = numeroCalidad(modal.find('input[name="MUESTRA_GRAMOS"]').val());
                modal.find('.valor-parametro-calidad[data-calcula-porcentaje="1"]').each(function() {
                    var input = $(this);
                    var gramos = numeroCalidad(input.val());
                    var porcentaje = muestra > 0 ? (gramos / muestra) * 100 : 0;
                    input.closest('.calidad-input-porcentaje').find('.calidad-porcentaje').text(formatoPorcentajeCalidad(porcentaje));
                });
            }

            $('#modalControlCalidad').on('input change', 'input[name="MUESTRA_GRAMOS"], .valor-parametro-calidad', function() {
                actualizarPorcentajesCalidad($('#modalControlCalidad'));
            });

            $('#modalControlCalidad').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var modal = $(this);
                var idControl = button.data('id') || '';

                modal.find('form')[0].reset();
                modal.find('#modalIdControl').val('');
                modal.find('#modalIdFolio').val('');
                modal.find('#modalModoIngreso').val('AGRUPADO');
                modal.find('input[name^="VALOR_PARAMETRO"]').val('');
                modal.find('.select2-modal').val('').trigger('change');
                actualizarPorcentajesCalidad(modal);

                if (idControl !== '' && controlesCalidadEdicion[idControl]) {
                    var control = controlesCalidadEdicion[idControl];
                    modal.find('#modalIdControl').val(control.id);
                    modal.find('#modalModoIngreso').val(control.modo || 'AGRUPADO');
                    modal.find('#modalIdFolio').val(control.folio || '');
                    modal.find('#modalModoTexto').text('Editando control ' + control.id);
                    modal.find('#modalFolioTexto').text(control.folioTexto || '');
                    modal.find('select[name="ID_CALIDAD_INSPECTOR"]').val(control.inspector || '').trigger('change');
                    modal.find('input[name="MUESTRA_GRAMOS"]').val(control.muestra || 1000);
                    modal.find('input[name="OBSERVACION"]').val(control.observacion || '');
                    $.each(control.detalles || {}, function(idParametro, valor) {
                        modal.find('input[name="VALOR_PARAMETRO[' + idParametro + ']"]').val(valor);
                    });
                    actualizarPorcentajesCalidad(modal);
                    modal.find('#modalControlCalidadLabel').text('Editar control calidad');
                    modal.find('#modalBotonGuardarControl').html('<i class="fa fa-save"></i> Actualizar Control');
                }
            });

            $('#modalCerrarControl').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var modal = $(this);
                modal.find('#cerrarIdControl').val(button.data('id') || '');
                modal.find('#cerrarResolucion').text(button.data('resolucion') || '');
                modal.find('#cerrarScore').text(button.data('score') || '');
                modal.find('#cerrarExportacion').text(button.data('exportacion') || '');
            });

            <?php if ($MENSAJE !== "") { ?>
                $.toast({
                    heading: '<?php echo $TIPOMENSAJE === "success" ? "Correcto" : "Aviso"; ?>',
                    text: '<?php echo controlesTexto($MENSAJE); ?>',
                    position: 'bottom-left',
                    loaderBg: '#ff6849',
                    icon: '<?php echo $TIPOMENSAJE; ?>',
                    hideAfter: 6500,
                    stack: 6
                });
            <?php } ?>
        });
    </script>
</body>

</html>
