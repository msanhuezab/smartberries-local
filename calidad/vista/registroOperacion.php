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

function operacionTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function operacionNumero($valor) {
    if ($valor === "" || $valor === null) {
        return 0;
    }
    return (float) str_replace(",", ".", (string) $valor);
}

function operacionDetalleControles($CALIDADCONTROL_ADO, $controles) {
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

function operacionFolioTexto($pallet) {
    return "Pallet " . ($pallet["FOLIO_AUXILIAR_EXIEXPORTACION"] ?? "") . " / Folio " . ($pallet["FOLIO_EXIEXPORTACION"] ?? "");
}

$ETAPA = strtoupper($_GET["ETAPA"] ?? ($_POST["ETAPA"] ?? "PROCESO"));
if (!in_array($ETAPA, array("PROCESO", "EXPORTACION"), true)) {
    $ETAPA = "PROCESO";
}

$MODOS = $ETAPA === "PROCESO"
    ? array("PROCESO" => "Proceso completo", "PALLET" => "Pallet")
    : array("PALLET" => "Pallet", "REFERENCIA" => "Referencia", "CONTENEDOR" => "Contenedor");

$ID_ESPECIES = $_GET["ID_ESPECIES"] ?? ($_POST["ID_ESPECIES"] ?? "");
$MODO_INGRESO = $_GET["MODO_INGRESO"] ?? ($_POST["MODO_INGRESO"] ?? array_key_first($MODOS));
if (!isset($MODOS[$MODO_INGRESO])) {
    $MODO_INGRESO = array_key_first($MODOS);
}

$ID_PROCESO = $_GET["ID_PROCESO"] ?? ($_POST["ID_PROCESO"] ?? "");
$ID_PALLET = $_GET["ID_PALLET"] ?? ($_POST["ID_PALLET"] ?? "");
$FOLIO_BUSCAR = $_GET["FOLIO_BUSCAR"] ?? ($_POST["FOLIO_BUSCAR"] ?? "");
$VALOR_OPERACION = $_GET["VALOR_OPERACION"] ?? ($_POST["VALOR_OPERACION"] ?? "");

$ARRAYESPECIES = $ESPECIES_ADO->listarEspeciesCalidadCBX();
$ARRAYINSPECTORES = $CALIDADINSPECTOR_ADO->listarInspectorActivo($EMPRESAS, $TEMPORADAS);
$ARRAYPROCESOS = array();
$ARRAYAGRUPACIONES = array();
$ARRAYPALLETS = array();
$ARRAYCONTROLES = array();
$ARRAYDETALLESCONTROL = array();
$PARAMETROS_GRUPO = array();
$OPERACION_SELECCIONADA = false;
$ID_OPERACION_CONTROL = null;
$NUMERO_OPERACION_CONTROL = "";

if ($ID_ESPECIES !== "") {
    foreach ($GRUPOS as $codigoGrupo => $nombreGrupo) {
        $PARAMETROS_GRUPO[$codigoGrupo] = $CALIDADPARAMETRO_ADO->listarParametroActivo($EMPRESAS, $TEMPORADAS, $ID_ESPECIES, $ETAPA, $codigoGrupo);
    }

    if ($ETAPA === "PROCESO") {
        $ARRAYPROCESOS = $CALIDADCONTROL_ADO->listarProcesoCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES);
        if ($ID_PROCESO !== "") {
            $ARRAYPALLETS = $CALIDADCONTROL_ADO->listarPalletProcesoCalidad($ID_PROCESO, $ID_ESPECIES);
            foreach ($ARRAYPROCESOS as $proceso) {
                if ((string) $proceso["ID_PROCESO"] === (string) $ID_PROCESO) {
                    $NUMERO_OPERACION_CONTROL = "Proceso " . $proceso["NUMERO_PROCESO"];
                    break;
                }
            }
            if ($MODO_INGRESO === "PROCESO") {
                $ID_OPERACION_CONTROL = $ID_PROCESO;
                $OPERACION_SELECCIONADA = true;
            } elseif ($MODO_INGRESO === "PALLET" && $ID_PALLET !== "") {
                $ID_OPERACION_CONTROL = $ID_PALLET;
                foreach ($ARRAYPALLETS as $pallet) {
                    if ((string) $pallet["ID_EXIEXPORTACION"] === (string) $ID_PALLET) {
                        $NUMERO_OPERACION_CONTROL = operacionFolioTexto($pallet);
                        break;
                    }
                }
                $ARRAYPALLETS = array_filter($ARRAYPALLETS, function($pallet) use ($ID_PALLET) {
                    return (string) $pallet["ID_EXIEXPORTACION"] === (string) $ID_PALLET;
                });
                $OPERACION_SELECCIONADA = true;
            }
        }
    } else {
        if ($MODO_INGRESO === "REFERENCIA") {
            $ARRAYAGRUPACIONES = $CALIDADCONTROL_ADO->listarReferenciaExportacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES);
            if ($VALOR_OPERACION !== "") {
                $ARRAYPALLETS = $CALIDADCONTROL_ADO->listarPalletExportacionPorAgrupacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "REFERENCIA", $VALOR_OPERACION);
                $NUMERO_OPERACION_CONTROL = $VALOR_OPERACION;
                $OPERACION_SELECCIONADA = true;
            }
        } elseif ($MODO_INGRESO === "CONTENEDOR") {
            $ARRAYAGRUPACIONES = $CALIDADCONTROL_ADO->listarContenedorExportacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES);
            if ($VALOR_OPERACION !== "") {
                $ARRAYPALLETS = $CALIDADCONTROL_ADO->listarPalletExportacionPorAgrupacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "CONTENEDOR", $VALOR_OPERACION);
                $NUMERO_OPERACION_CONTROL = $VALOR_OPERACION;
                $OPERACION_SELECCIONADA = true;
            }
        } elseif ($MODO_INGRESO === "PALLET" && $FOLIO_BUSCAR !== "") {
            $ARRAYPALLETS = $CALIDADCONTROL_ADO->buscarPalletExportacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $FOLIO_BUSCAR);
            if (!empty($ARRAYPALLETS)) {
                $ID_PALLET = $ARRAYPALLETS[0]["ID_EXIEXPORTACION"];
                $ID_OPERACION_CONTROL = $ID_PALLET;
                $NUMERO_OPERACION_CONTROL = operacionFolioTexto($ARRAYPALLETS[0]);
                $OPERACION_SELECCIONADA = true;
            } else {
                $MENSAJE = "No se encontro el pallet indicado para la especie seleccionada.";
                $TIPOMENSAJE = "warning";
            }
        }
    }

    if ($OPERACION_SELECCIONADA) {
        $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlOperacionCalidad(
            $EMPRESAS,
            $PLANTAS,
            $TEMPORADAS,
            $ID_ESPECIES,
            $ETAPA,
            $MODO_INGRESO,
            $ID_OPERACION_CONTROL,
            $NUMERO_OPERACION_CONTROL
        );
        $ARRAYDETALLESCONTROL = operacionDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
    }
}

if (isset($_POST["GUARDARCONTROL"])) {
    $puedeGuardar = true;
    if ($_POST["ID_ESPECIES"] === "" || $_POST["ID_CALIDAD_INSPECTOR"] === "") {
        $puedeGuardar = false;
        $MENSAJE = "Debe seleccionar especie e inspector.";
        $TIPOMENSAJE = "warning";
    }

    $palletsAsociar = array();
    if ($_POST["PALLETS_ASOCIAR"] !== "") {
        $palletsAsociar = json_decode($_POST["PALLETS_ASOCIAR"], true);
        if (!is_array($palletsAsociar)) {
            $palletsAsociar = array();
        }
    }
    if (empty($palletsAsociar)) {
        $puedeGuardar = false;
        $MENSAJE = "Debe seleccionar una operacion con pallets asociados.";
        $TIPOMENSAJE = "warning";
    }

    if ($puedeGuardar) {
        $muestra = operacionNumero($_POST["MUESTRA_GRAMOS"]);
        $valores = $_POST["VALOR_PARAMETRO"] ?? array();
        $nombres = $_POST["NOMBRE_PARAMETRO"] ?? array();
        $grupos = $_POST["GRUPO_PARAMETRO"] ?? array();
        $valoresScore = array();

        foreach ($valores as $idParametro => $valorIngresado) {
            $valoresScore[] = array(
                "grupo" => $grupos[$idParametro] ?? "",
                "nombre" => $nombres[$idParametro] ?? "",
                "valor" => operacionNumero($valorIngresado)
            );
        }

        $CALCULO_SCORE = $CALIDADCONTROL_ADO->calcularResolucionScore($valoresScore, $muestra);
        $CONTROL = new CALIDADCONTROL();
        $CONTROL->__SET("ETAPA", $_POST["ETAPA"]);
        $CONTROL->__SET("MODO_INGRESO", $_POST["MODO_INGRESO"]);
        $CONTROL->__SET("TIPO_PRODUCTO", "PT");
        $CONTROL->__SET("ID_OPERACION", $_POST["ID_OPERACION_CONTROL"] !== "" ? $_POST["ID_OPERACION_CONTROL"] : null);
        $CONTROL->__SET("NUMERO_OPERACION", $_POST["NUMERO_OPERACION_CONTROL"]);
        $CONTROL->__SET("FECHA", date("Y-m-d"));
        $CONTROL->__SET("HORA", date("H:i:s"));
        $CONTROL->__SET("ID_EMPRESA", $EMPRESAS);
        $CONTROL->__SET("ID_PLANTA", $PLANTAS);
        $CONTROL->__SET("ID_TEMPORADA", $TEMPORADAS);
        $CONTROL->__SET("ID_ESPECIES", $_POST["ID_ESPECIES"]);
        $CONTROL->__SET("ID_USUARIO", $IDUSUARIOS);
        $CONTROL->__SET("ID_CALIDAD_INSPECTOR", $_POST["ID_CALIDAD_INSPECTOR"]);
        $CONTROL->__SET("MUESTRA_GRAMOS", $muestra);
        $CONTROL->__SET("RESULTADO_GENERAL", $CALCULO_SCORE["RESULTADO_GENERAL"]);
        $CONTROL->__SET("ESTADO_CONTROL", "ABIERTO");
        $CONTROL->__SET("PORC_DEFECTO_CONDICION", $CALCULO_SCORE["PORC_DEFECTO_CONDICION"]);
        $CONTROL->__SET("PORC_DEFECTO_CALIDAD", $CALCULO_SCORE["PORC_DEFECTO_CALIDAD"]);
        $CONTROL->__SET("PORC_FIRMEZA", $CALCULO_SCORE["PORC_FIRMEZA"]);
        $CONTROL->__SET("PORC_ESTIMADO_EXPORTACION", $CALCULO_SCORE["PORC_ESTIMADO_EXPORTACION"]);
        $CONTROL->__SET("SCORE_GENERAL", $CALCULO_SCORE["SCORE_GENERAL"]);
        $CONTROL->__SET("OBSERVACION", $_POST["OBSERVACION"]);
        $IDCONTROL = $CALIDADCONTROL_ADO->agregarControl($CONTROL);

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
            $DETALLE->__SET("VALOR_NUMERICO", operacionNumero($valorIngresado));
            $DETALLE->__SET("VALOR_TEXTO", null);
            $DETALLE->__SET("RESULTADO", null);
            $DETALLE->__SET("OBSERVACION", null);
            $CALIDADCONTROL_ADO->agregarDetalle($DETALLE);
        }

        foreach ($palletsAsociar as $pallet) {
            $FOLIO = new CALIDADCONTROLFOLIO();
            $FOLIO->__SET("ID_CALIDAD_CONTROL", $IDCONTROL);
            $FOLIO->__SET("TIPO_PRODUCTO", "PT");
            $FOLIO->__SET("ID_EXISTENCIA", $pallet["id"]);
            $FOLIO->__SET("FOLIO_ORIGINAL", $pallet["folio"]);
            $FOLIO->__SET("FOLIO_AUXILIAR", $pallet["folioAuxiliar"]);
            $CALIDADCONTROL_ADO->agregarFolio($FOLIO);
        }

        $MENSAJE = "Control guardado. Score: " . $CALCULO_SCORE["SCORE_GENERAL"] . " - " . $CALCULO_SCORE["RESULTADO_GENERAL"];
        $TIPOMENSAJE = "success";
        $OPERACION_SELECCIONADA = true;
        $ID_OPERACION_CONTROL = $_POST["ID_OPERACION_CONTROL"] !== "" ? $_POST["ID_OPERACION_CONTROL"] : null;
        $NUMERO_OPERACION_CONTROL = $_POST["NUMERO_OPERACION_CONTROL"];
        $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlOperacionCalidad(
            $EMPRESAS,
            $PLANTAS,
            $TEMPORADAS,
            $_POST["ID_ESPECIES"],
            $_POST["ETAPA"],
            $_POST["MODO_INGRESO"],
            $ID_OPERACION_CONTROL,
            $NUMERO_OPERACION_CONTROL
        );
        $ARRAYDETALLESCONTROL = operacionDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
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
    $MENSAJE = "Control cerrado correctamente.";
    $TIPOMENSAJE = "success";
    if ($OPERACION_SELECCIONADA) {
        $ARRAYCONTROLES = $CALIDADCONTROL_ADO->listarControlOperacionCalidad(
            $EMPRESAS,
            $PLANTAS,
            $TEMPORADAS,
            $ID_ESPECIES,
            $ETAPA,
            $MODO_INGRESO,
            $ID_OPERACION_CONTROL,
            $NUMERO_OPERACION_CONTROL
        );
        $ARRAYDETALLESCONTROL = operacionDetalleControles($CALIDADCONTROL_ADO, $ARRAYCONTROLES);
    }
}

$PALLETS_JSON = array();
foreach ($ARRAYPALLETS as $pallet) {
    $PALLETS_JSON[] = array(
        "id" => $pallet["ID_EXIEXPORTACION"],
        "folio" => $pallet["FOLIO_EXIEXPORTACION"],
        "folioAuxiliar" => $pallet["FOLIO_AUXILIAR_EXIEXPORTACION"]
    );
}

$TITULO = $ETAPA === "PROCESO" ? "Control Calidad Proceso" : "Control Calidad Exportacion";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title><?php echo operacionTexto($TITULO); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
        .calidad-matriz { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border: 1px solid #e6e9f0; border-bottom: 0; border-right: 0; }
        .calidad-campo { border-bottom: 1px solid #e6e9f0; border-right: 1px solid #e6e9f0; padding: 10px; }
        .calidad-campo label { color: #5d697b; display: block; font-size: 11px; font-weight: 700; margin-bottom: 6px; text-transform: uppercase; }
        .resolucion-badge { border-radius: 4px; color: #fff; display: inline-block; font-weight: 700; min-width: 92px; padding: 5px 8px; }
        @media (max-width: 991px) { .calidad-matriz { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 575px) { .calidad-matriz { grid-template-columns: 1fr; } }
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
                            <h3 class="page-title"><?php echo operacionTexto($TITULO); ?></h3>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>

                <section class="content">
                    <div class="box">
                        <div class="box-header with-border"><h4 class="box-title">Seleccion</h4></div>
                        <form method="GET">
                            <input type="hidden" name="ETAPA" value="<?php echo operacionTexto($ETAPA); ?>">
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Especie</label>
                                        <select class="form-control select2" name="ID_ESPECIES" required onchange="this.form.submit()">
                                            <option></option>
                                            <?php foreach ($ARRAYESPECIES as $especie) { ?>
                                                <option value="<?php echo $especie["ID_ESPECIES"]; ?>" <?php echo (string) $ID_ESPECIES === (string) $especie["ID_ESPECIES"] ? "selected" : ""; ?>><?php echo operacionTexto($especie["NOMBRE_ESPECIES"]); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label>Ingreso por</label>
                                        <select class="form-control select2" name="MODO_INGRESO" onchange="this.form.submit()">
                                            <?php foreach ($MODOS as $codigo => $nombre) { ?>
                                                <option value="<?php echo $codigo; ?>" <?php echo $MODO_INGRESO === $codigo ? "selected" : ""; ?>><?php echo operacionTexto($nombre); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <?php if ($ETAPA === "PROCESO") { ?>
                                        <div class="col-md-5">
                                            <label>Proceso</label>
                                            <select class="form-control select2" name="ID_PROCESO" onchange="this.form.submit()" <?php echo $ID_ESPECIES === "" ? "disabled" : ""; ?>>
                                                <option></option>
                                                <?php foreach ($ARRAYPROCESOS as $proceso) { ?>
                                                    <option value="<?php echo $proceso["ID_PROCESO"]; ?>" <?php echo (string) $ID_PROCESO === (string) $proceso["ID_PROCESO"] ? "selected" : ""; ?>>
                                                        <?php echo "Proceso " . operacionTexto($proceso["NUMERO_PROCESO"]) . " / " . operacionTexto($proceso["FECHA_PROCESO"]) . " / " . operacionTexto($proceso["NOMBRE_PRODUCTOR"]); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <?php if ($MODO_INGRESO === "PALLET" && !empty($ARRAYPALLETS)) { ?>
                                            <div class="col-md-2">
                                                <label>Pallet</label>
                                                <select class="form-control select2" name="ID_PALLET" onchange="this.form.submit()">
                                                    <option></option>
                                                    <?php foreach ($ARRAYPALLETS as $pallet) { ?>
                                                        <option value="<?php echo $pallet["ID_EXIEXPORTACION"]; ?>" <?php echo (string) $ID_PALLET === (string) $pallet["ID_EXIEXPORTACION"] ? "selected" : ""; ?>><?php echo operacionTexto($pallet["FOLIO_AUXILIAR_EXIEXPORTACION"]); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <?php if ($MODO_INGRESO === "PALLET") { ?>
                                            <div class="col-md-4">
                                                <label>Buscar pallet</label>
                                                <input type="text" class="form-control" name="FOLIO_BUSCAR" value="<?php echo operacionTexto($FOLIO_BUSCAR); ?>" placeholder="Folio o folio auxiliar">
                                            </div>
                                        <?php } else { ?>
                                            <div class="col-md-5">
                                                <label><?php echo $MODO_INGRESO === "REFERENCIA" ? "Referencia" : "Contenedor"; ?></label>
                                                <select class="form-control select2" name="VALOR_OPERACION" onchange="this.form.submit()">
                                                    <option></option>
                                                    <?php foreach ($ARRAYAGRUPACIONES as $agrupacion) {
                                                        $valor = $MODO_INGRESO === "REFERENCIA" ? $agrupacion["REFERENCIA_CALIDAD"] : $agrupacion["CONTENEDOR_CALIDAD"];
                                                    ?>
                                                        <option value="<?php echo operacionTexto($valor); ?>" <?php echo (string) $VALOR_OPERACION === (string) $valor ? "selected" : ""; ?>>
                                                            <?php echo operacionTexto($valor) . " / Pallets " . operacionTexto($agrupacion["TOTAL_PALLETS"]); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-rounded btn-info btn-block">Filtrar</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($OPERACION_SELECCIONADA) { ?>
                        <div class="box">
                            <div class="box-header with-border"><h4 class="box-title">Nuevo control</h4></div>
                            <form method="POST">
                                <input type="hidden" name="ETAPA" value="<?php echo operacionTexto($ETAPA); ?>">
                                <input type="hidden" name="ID_ESPECIES" value="<?php echo operacionTexto($ID_ESPECIES); ?>">
                                <input type="hidden" name="MODO_INGRESO" value="<?php echo operacionTexto($MODO_INGRESO); ?>">
                                <input type="hidden" name="ID_PROCESO" value="<?php echo operacionTexto($ID_PROCESO); ?>">
                                <input type="hidden" name="ID_PALLET" value="<?php echo operacionTexto($ID_PALLET); ?>">
                                <input type="hidden" name="FOLIO_BUSCAR" value="<?php echo operacionTexto($FOLIO_BUSCAR); ?>">
                                <input type="hidden" name="VALOR_OPERACION" value="<?php echo operacionTexto($VALOR_OPERACION); ?>">
                                <input type="hidden" name="ID_OPERACION_CONTROL" value="<?php echo operacionTexto($ID_OPERACION_CONTROL); ?>">
                                <input type="hidden" name="NUMERO_OPERACION_CONTROL" value="<?php echo operacionTexto($NUMERO_OPERACION_CONTROL); ?>">
                                <input type="hidden" name="PALLETS_ASOCIAR" value="<?php echo operacionTexto(json_encode($PALLETS_JSON)); ?>">
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Operacion</label>
                                            <input type="text" class="form-control" value="<?php echo operacionTexto($NUMERO_OPERACION_CONTROL); ?>" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Inspector</label>
                                            <select class="form-control select2" name="ID_CALIDAD_INSPECTOR" required>
                                                <option></option>
                                                <?php foreach ($ARRAYINSPECTORES as $inspector) { ?>
                                                    <option value="<?php echo $inspector["ID_CALIDAD_INSPECTOR"]; ?>"><?php echo operacionTexto($inspector["NOMBRE_INSPECTOR"]); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label>Muestra gramos</label>
                                            <input type="number" step="0.01" class="form-control" name="MUESTRA_GRAMOS" value="1000" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Observacion</label>
                                            <input type="text" class="form-control" name="OBSERVACION">
                                        </div>
                                    </div>

                                    <?php foreach ($GRUPOS as $codigoGrupo => $nombreGrupo) { ?>
                                        <?php if (!empty($PARAMETROS_GRUPO[$codigoGrupo])) { ?>
                                            <h5 class="mt-20"><?php echo operacionTexto($nombreGrupo); ?></h5>
                                            <div class="calidad-matriz">
                                                <?php foreach ($PARAMETROS_GRUPO[$codigoGrupo] as $parametro) { ?>
                                                    <div class="calidad-campo">
                                                        <label><?php echo operacionTexto($parametro["NOMBRE_PARAMETRO"]); ?></label>
                                                        <input type="number" step="0.01" class="form-control" name="VALOR_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]">
                                                        <input type="hidden" name="NOMBRE_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" value="<?php echo operacionTexto($parametro["NOMBRE_PARAMETRO"]); ?>">
                                                        <input type="hidden" name="GRUPO_PARAMETRO[<?php echo $parametro["ID_CALIDAD_PARAMETRO"]; ?>]" value="<?php echo operacionTexto($codigoGrupo); ?>">
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                                <div class="box-footer text-right">
                                    <button type="submit" class="btn btn-rounded btn-primary" name="GUARDARCONTROL" value="1"><i class="fa fa-save"></i> Guardar control</button>
                                </div>
                            </form>
                        </div>

                        <div class="box">
                            <div class="box-header with-border"><h4 class="box-title">Pallets asociados</h4></div>
                            <div class="box-body table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead><tr><th>Pallet</th><th>Folio</th><th class="text-right">Kilos neto</th><th class="text-right">Envases</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($ARRAYPALLETS as $pallet) { ?>
                                            <tr>
                                                <td><?php echo operacionTexto($pallet["FOLIO_AUXILIAR_EXIEXPORTACION"]); ?></td>
                                                <td><?php echo operacionTexto($pallet["FOLIO_EXIEXPORTACION"]); ?></td>
                                                <td class="text-right"><?php echo operacionTexto($pallet["KILOS_NETO_EXIEXPORTACION"]); ?></td>
                                                <td class="text-right"><?php echo operacionTexto($pallet["CANTIDAD_ENVASE_EXIEXPORTACION"]); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="box">
                            <div class="box-header with-border"><h4 class="box-title">Controles ingresados</h4></div>
                            <div class="box-body table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Estado</th><th>Operacion</th><th>Inspector</th><th>Resolucion</th><th class="text-center">Score</th><th class="text-right">% Condicion</th><th class="text-right">% Calidad</th><th>Cierre</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ARRAYCONTROLES as $control) { ?>
                                            <tr>
                                                <td><?php echo $control["ESTADO_CONTROL"] === "CERRADO" ? '<span class="badge badge-success">CERRADO</span>' : '<span class="badge badge-warning">ABIERTO</span>'; ?></td>
                                                <td><?php echo operacionTexto($control["NUMERO_OPERACION"]); ?></td>
                                                <td><?php echo operacionTexto($control["NOMBRE_INSPECTOR"]); ?></td>
                                                <td><span class="resolucion-badge" style="background: <?php echo operacionTexto($CALIDADCONTROL_ADO->colorResolucion($control["RESULTADO_GENERAL"])); ?>"><?php echo operacionTexto($control["RESULTADO_GENERAL"]); ?></span></td>
                                                <td class="text-center"><?php echo operacionTexto($control["SCORE_GENERAL"]); ?></td>
                                                <td class="text-right"><?php echo operacionTexto($control["PORC_DEFECTO_CONDICION"]); ?></td>
                                                <td class="text-right"><?php echo operacionTexto($control["PORC_DEFECTO_CALIDAD"]); ?></td>
                                                <td>
                                                    <?php if ($control["ESTADO_CONTROL"] === "ABIERTO") { ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="ETAPA" value="<?php echo operacionTexto($ETAPA); ?>">
                                                            <input type="hidden" name="ID_ESPECIES" value="<?php echo operacionTexto($ID_ESPECIES); ?>">
                                                            <input type="hidden" name="MODO_INGRESO" value="<?php echo operacionTexto($MODO_INGRESO); ?>">
                                                            <input type="hidden" name="ID_PROCESO" value="<?php echo operacionTexto($ID_PROCESO); ?>">
                                                            <input type="hidden" name="ID_PALLET" value="<?php echo operacionTexto($ID_PALLET); ?>">
                                                            <input type="hidden" name="FOLIO_BUSCAR" value="<?php echo operacionTexto($FOLIO_BUSCAR); ?>">
                                                            <input type="hidden" name="VALOR_OPERACION" value="<?php echo operacionTexto($VALOR_OPERACION); ?>">
                                                            <input type="hidden" name="ID_CALIDAD_CONTROL" value="<?php echo operacionTexto($control["ID_CALIDAD_CONTROL"]); ?>">
                                                            <button type="submit" class="btn btn-rounded btn-success btn-sm" name="CERRARCONTROL" value="1"><i class="fa fa-check"></i> Cerrar</button>
                                                        </form>
                                                    <?php } else { ?>
                                                        <?php echo operacionTexto($control["FECHA_CIERRE"]); ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (empty($ARRAYCONTROLES)) { ?>
                                            <tr><td colspan="8" class="text-center text-muted">Sin controles ingresados.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
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
        $(document).ready(function() {
            $('.select2').select2();
            <?php if ($MENSAJE !== "") { ?>
                $.toast({
                    heading: '<?php echo $TIPOMENSAJE === "success" ? "Correcto" : "Aviso"; ?>',
                    text: '<?php echo operacionTexto($MENSAJE); ?>',
                    position: 'bottom-left',
                    loaderBg: '#ff6849',
                    icon: '<?php echo $TIPOMENSAJE; ?>',
                    hideAfter: 3500,
                    stack: 6
                });
            <?php } ?>
        });
    </script>
</body>
</html>
