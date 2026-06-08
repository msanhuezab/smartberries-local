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

function recepcionTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function recepcionNumero($valor) {
    if ($valor === "" || $valor === null) {
        return 0;
    }
    return (float) str_replace(",", ".", (string) $valor);
}

function recepcionPorcentaje($valor, $muestra) {
    if ((float) $muestra <= 0) {
        return 0;
    }
    return round(((float) $valor / (float) $muestra) * 100, 4);
}

function recepcionEsFirme($nombre) {
    $nombre = strtoupper((string) $nombre);
    return strpos($nombre, "FIRME") !== false;
}

function recepcionDetalleControles($CALIDADCONTROL_ADO, $controles) {
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

$ARRAYESPECIES = $ESPECIES_ADO->listarEspeciesCalidadCBX();
$ARRAYINSPECTORES = $CALIDADINSPECTOR_ADO->listarInspectorActivo($EMPRESAS, $TEMPORADAS);

$ID_ESPECIES = $_GET["ID_ESPECIES"] ?? ($_POST["ID_ESPECIES"] ?? "");
$MODO_INGRESO = $_GET["MODO_INGRESO"] ?? ($_POST["MODO_INGRESO"] ?? "AGRUPADO");
$ID_RECEPCION = $_GET["ID_RECEPCION"] ?? ($_POST["ID_RECEPCION"] ?? "");
$ID_FOLIO = $_GET["ID_FOLIO"] ?? ($_POST["ID_FOLIO"] ?? "");
$FOLIO_BUSCAR = $_GET["FOLIO_BUSCAR"] ?? "";
$RECEPCION_BUSCADA = array();

if (!in_array($MODO_INGRESO, array("AGRUPADO", "FOLIO"), true)) {
    $MODO_INGRESO = "AGRUPADO";
}

if ($FOLIO_BUSCAR !== "") {
    $ARRAYBUSQUEDA = $CALIDADCONTROL_ADO->buscarRecepcionPorFolioCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $FOLIO_BUSCAR);
    if (!empty($ARRAYBUSQUEDA)) {
        $RECEPCION_BUSCADA = $ARRAYBUSQUEDA[0];
        $ID_ESPECIES = $RECEPCION_BUSCADA["ID_ESPECIES"];
        $ID_RECEPCION = $RECEPCION_BUSCADA["ID_RECEPCION"];
    } else {
        $MENSAJE = "No se encontro el folio " . $FOLIO_BUSCAR . " para la empresa, planta y temporada actual.";
        $TIPOMENSAJE = "warning";
    }
}

$ARRAYRECEPCIONES = array();
$ARRAYFOLIOS = array();
$ARRAYCONTROLES = array();
$ARRAYDETALLESCONTROL = array();
$PARAMETROS_GRUPO = array();

if ($ID_ESPECIES !== "") {
    $ARRAYRECEPCIONES = $CALIDADCONTROL_ADO->listarRecepcionMateriaPrimaCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES);
    if ($ID_RECEPCION !== "") {
        $ARRAYFOLIOS = $CALIDADCONTROL_ADO->listarFolioRecepcionMateriaPrimaCalidad($ID_RECEPCION, $ID_ESPECIES);
        $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $ID_RECEPCION);
        $ARRAYDETALLESCONTROL = recepcionDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
    }
    foreach ($GRUPOS as $codigoGrupo => $nombreGrupo) {
        $PARAMETROS_GRUPO[$codigoGrupo] = $CALIDADPARAMETRO_ADO->listarParametroActivo($EMPRESAS, $TEMPORADAS, $ID_ESPECIES, "RECEPCION", $codigoGrupo);
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
    if ($_POST["MODO_INGRESO"] === "FOLIO" && $_POST["ID_FOLIO"] === "") {
        $puedeGuardar = false;
        $MENSAJE = "Debe seleccionar un folio para guardar el control por folio.";
        $TIPOMENSAJE = "warning";
    }
    if (!in_array($_POST["MODO_INGRESO"], array("AGRUPADO", "FOLIO"), true)) {
        $puedeGuardar = false;
        $MENSAJE = "Modo de ingreso no valido.";
        $TIPOMENSAJE = "warning";
    }

    if ($puedeGuardar) {
    $muestra = recepcionNumero($_POST["MUESTRA_GRAMOS"]);
    $valores = $_POST["VALOR_PARAMETRO"] ?? array();
    $nombres = $_POST["NOMBRE_PARAMETRO"] ?? array();
    $grupos = $_POST["GRUPO_PARAMETRO"] ?? array();

    $valoresScore = array();

    foreach ($valores as $idParametro => $valorIngresado) {
        $valor = recepcionNumero($valorIngresado);
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
        $DETALLE->__SET("VALOR_NUMERICO", recepcionNumero($valorIngresado));
        $DETALLE->__SET("VALOR_TEXTO", null);
        $DETALLE->__SET("RESULTADO", null);
        $DETALLE->__SET("OBSERVACION", null);
        $CALIDADCONTROL_ADO->agregarDetalle($DETALLE);
    }

    if ($IDCONTROL_EDITAR === "") {
        $foliosAsociar = $CALIDADCONTROL_ADO->listarFolioRecepcionMateriaPrimaCalidad($_POST["ID_RECEPCION"], $_POST["ID_ESPECIES"]);
        foreach ($foliosAsociar as $folio) {
            if ($_POST["MODO_INGRESO"] === "FOLIO" && (string) $folio["ID_EXIMATERIAPRIMA"] !== (string) $_POST["ID_FOLIO"]) {
                continue;
            }
            $FOLIO = new CALIDADCONTROLFOLIO();
            $FOLIO->__SET("ID_CALIDAD_CONTROL", $IDCONTROL);
            $FOLIO->__SET("TIPO_PRODUCTO", "MP");
            $FOLIO->__SET("ID_EXISTENCIA", $folio["ID_EXIMATERIAPRIMA"]);
            $FOLIO->__SET("FOLIO_ORIGINAL", $folio["FOLIO_EXIMATERIAPRIMA"]);
            $FOLIO->__SET("FOLIO_AUXILIAR", $folio["FOLIO_AUXILIAR_EXIMATERIAPRIMA"]);
            $CALIDADCONTROL_ADO->agregarFolio($FOLIO);
        }
    }

    $MENSAJE = ($IDCONTROL_EDITAR !== "" ? "Control actualizado. " : "Control guardado. ") . "Score: " . $scoreGeneral . " (" . $grupoScore . ") - " . $resultadoGeneral;
    $TIPOMENSAJE = "success";
    $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $_POST["ID_ESPECIES"], $_POST["ID_RECEPCION"]);
    $ARRAYDETALLESCONTROL = recepcionDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
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
        $ARRAYDETALLESCONTROL = recepcionDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
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
    <title>Control Calidad Recepcion</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
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
                            <h3 class="page-title">Control Calidad Recepcion</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Calidad</li>
                                        <li class="breadcrumb-item" aria-current="page">Registro</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Recepcion</a></li>
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
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Especie</label>
                                            <select class="form-control select2" name="ID_ESPECIES" required onchange="this.form.submit()">
                                                <option></option>
                                                <?php foreach ($ARRAYESPECIES as $especie) { ?>
                                                    <option value="<?php echo $especie["ID_ESPECIES"]; ?>" <?php echo ((string) $ID_ESPECIES === (string) $especie["ID_ESPECIES"]) ? "selected" : ""; ?>><?php echo recepcionTexto($especie["NOMBRE_ESPECIES"]); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Buscar folio</label>
                                            <input type="text" class="form-control" name="FOLIO_BUSCAR" value="<?php echo recepcionTexto($FOLIO_BUSCAR); ?>" placeholder="Folio">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Recepcion</label>
                                            <select class="form-control select2" name="ID_RECEPCION" onchange="this.form.submit()" <?php echo $ID_ESPECIES === "" ? "disabled" : ""; ?>>
                                                <option></option>
                                                <?php foreach ($ARRAYRECEPCIONES as $recepcion) { ?>
                                                    <option value="<?php echo $recepcion["ID_RECEPCION"]; ?>" <?php echo ((string) $ID_RECEPCION === (string) $recepcion["ID_RECEPCION"]) ? "selected" : ""; ?>>
                                                        <?php echo "N " . recepcionTexto($recepcion["NUMERO_RECEPCION"]) . " / Guia " . recepcionTexto($recepcion["NUMERO_GUIA_RECEPCION"]) . " / " . recepcionTexto($recepcion["FECHA_RECEPCION"]); ?>
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
                                <h4 class="box-title">Folios de la recepcion</h4>
                                <div class="box-controls pull-right">
                                    <a href="controlesCalidad.php?ID_ESPECIES=<?php echo recepcionTexto($ID_ESPECIES); ?>&ID_RECEPCION=<?php echo recepcionTexto($ID_RECEPCION); ?>" class="btn btn-rounded btn-info btn-sm mr-5">
                                        <i class="fa fa-list"></i> Ver controles
                                    </a>
                                    <button type="button" class="btn btn-rounded btn-primary btn-sm abrir-control-calidad"
                                        data-toggle="modal"
                                        data-target="#modalControlCalidad"
                                        data-modo="AGRUPADO"
                                        data-folio=""
                                        data-folio-texto="Recepcion completa">
                                        <i class="fa fa-check-square-o"></i> Control agrupado
                                    </button>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered mb-0" id="tablaFoliosRecepcion">
                                        <thead>
                                            <tr>
                                                <th>Folio</th>
                                                <th>Folio auxiliar</th>
                                                <th>Variedad</th>
                                                <th class="text-right">Kilos neto</th>
                                                <th class="text-center">Accion</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ARRAYFOLIOS as $folio) { ?>
                                                <tr>
                                                    <td><?php echo recepcionTexto($folio["FOLIO_EXIMATERIAPRIMA"]); ?></td>
                                                    <td><?php echo recepcionTexto($folio["FOLIO_AUXILIAR_EXIMATERIAPRIMA"]); ?></td>
                                                    <td><?php echo recepcionTexto($folio["NOMBRE_VESPECIES"]); ?></td>
                                                    <td class="text-right"><?php echo recepcionTexto($folio["KILOS_NETO_EXIMATERIAPRIMA"]); ?></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-rounded btn-info btn-sm abrir-control-calidad"
                                                            data-toggle="modal"
                                                            data-target="#modalControlCalidad"
                                                            data-modo="FOLIO"
                                                            data-folio="<?php echo recepcionTexto($folio["ID_EXIMATERIAPRIMA"]); ?>"
                                                            data-folio-texto="Folio <?php echo recepcionTexto($folio["FOLIO_AUXILIAR_EXIMATERIAPRIMA"]); ?>">
                                                            <i class="fa fa-edit"></i> Ingresar
                                                        </button>
                                                    </td>
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
                                        <input type="hidden" name="ID_ESPECIES" value="<?php echo recepcionTexto($ID_ESPECIES); ?>">
                                        <input type="hidden" name="MODO_INGRESO" id="modalModoIngreso" value="AGRUPADO">
                                        <input type="hidden" name="ID_RECEPCION" value="<?php echo recepcionTexto($ID_RECEPCION); ?>">
                                        <input type="hidden" name="ID_FOLIO" id="modalIdFolio" value="">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalControlCalidadLabel">Ingreso control calidad</h5>
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
                                                                <option value="<?php echo $inspector["ID_CALIDAD_INSPECTOR"]; ?>"><?php echo recepcionTexto($inspector["NOMBRE_INSPECTOR"]); ?></option>
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
                                                        <div class="calidad-bloque-titulo"><?php echo recepcionTexto($nombreGrupo); ?></div>
                                                        <div class="calidad-matriz">
                                                            <?php foreach ($PARAMETROS_GRUPO[$codigoGrupo] as $parametro) { ?>
                                                                <div class="calidad-campo">
                                                                    <label><?php echo recepcionTexto($parametro["NOMBRE_PARAMETRO"]); ?></label>
                                                                    <?php $mostrarPorcentaje = $codigoGrupo !== "PARAMETROS"; ?>
                                                                    <div class="<?php echo $mostrarPorcentaje ? "calidad-input-porcentaje" : ""; ?>">
                                                                        <input type="number" step="0.01" class="form-control valor-parametro-calidad" data-calcula-porcentaje="<?php echo $mostrarPorcentaje ? "1" : "0"; ?>" name="VALOR_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" <?php echo ((int) $parametro["ES_REQUERIDO"] === 1) ? "required" : ""; ?>>
                                                                        <?php if ($mostrarPorcentaje) { ?>
                                                                            <span class="calidad-porcentaje">0,00%</span>
                                                                        <?php } ?>
                                                                    </div>
                                                                    <input type="hidden" name="NOMBRE_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" value="<?php echo recepcionTexto($parametro["NOMBRE_PARAMETRO"]); ?>">
                                                                    <input type="hidden" name="GRUPO_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" value="<?php echo recepcionTexto($codigoGrupo); ?>">
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
                                                <i class="fa fa-save"></i> Guardar Control
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

            if ($.fn.DataTable && $('#tablaFoliosRecepcion').length) {
                $('#tablaFoliosRecepcion').DataTable({
                    pageLength: 25,
                    order: [[1, 'asc']]
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
                    return;
                }

                var modo = button.data('modo') || 'AGRUPADO';
                var folio = button.data('folio') || '';
                var folioTexto = button.data('folio-texto') || 'Recepcion completa';

                modal.find('#modalModoIngreso').val(modo);
                modal.find('#modalIdFolio').val(folio);
                modal.find('#modalModoTexto').text(modo === 'FOLIO' ? 'Control por folio' : 'Control agrupado');
                modal.find('#modalFolioTexto').text(folioTexto);
                modal.find('#modalControlCalidadLabel').text('Ingreso control calidad');
                modal.find('#modalBotonGuardarControl').html('<i class="fa fa-save"></i> Guardar Control');
                actualizarPorcentajesCalidad(modal);
            });

            <?php if ($MENSAJE !== "") { ?>
                $.toast({
                    heading: '<?php echo $TIPOMENSAJE === "success" ? "Correcto" : "Aviso"; ?>',
                    text: '<?php echo recepcionTexto($MENSAJE); ?>',
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
