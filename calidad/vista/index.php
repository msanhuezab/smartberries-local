<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "../../assest/controlador/CALIDADCONTROL_ADO.php";
include_once "../../assest/controlador/ESPECIES_ADO.php";

$CALIDADCONTROL_ADO = new CALIDADCONTROL_ADO();
$ESPECIES_ADO = new ESPECIES_ADO();

function dashTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function dashNumero($valor, $dec = 0) {
    if ($valor === null || $valor === "") {
        return "0";
    }
    return number_format((float) $valor, $dec, ",", ".");
}

function dashEstado($fila) {
    if ((int) ($fila["TOTAL_CONTROLES"] ?? 0) <= 0) {
        return "SIN_CONTROL";
    }
    if ((int) ($fila["ABIERTOS"] ?? 0) > 0) {
        return "ABIERTO";
    }
    return "CERRADO";
}

function dashResumenCobertura($filas) {
    $resumen = array("TOTAL" => 0, "SIN_CONTROL" => 0, "ABIERTO" => 0, "CERRADO" => 0, "KILOS" => 0);
    foreach ($filas as $fila) {
        $estado = dashEstado($fila);
        $resumen["TOTAL"]++;
        $resumen[$estado]++;
        $resumen["KILOS"] += (float) ($fila["KILOS_NETO"] ?? 0);
    }
    return $resumen;
}

$ARRAYESPECIES = $ESPECIES_ADO->listarEspeciesCalidadCBX();
$ID_ESPECIES = $_GET["ID_ESPECIES"] ?? ($ARRAYESPECIES[0]["ID_ESPECIES"] ?? "");

$COBERTURAS = array();
if ($ID_ESPECIES !== "") {
    $COBERTURAS[] = array(
        "ETAPA" => "RECEPCION",
        "MODO" => "AGRUPADO",
        "NOMBRE" => "Recepcion",
        "SUBTITULO" => "Agrupado por recepcion",
        "URL" => "revisionCalidad.php?ETAPA=RECEPCION&MODO=AGRUPADO&ID_ESPECIES=" . urlencode($ID_ESPECIES),
        "DATA" => $CALIDADCONTROL_ADO->listarRevisionRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "AGRUPADO")
    );
    $COBERTURAS[] = array(
        "ETAPA" => "RECEPCION",
        "MODO" => "FOLIO",
        "NOMBRE" => "Recepcion folio",
        "SUBTITULO" => "Folio materia prima",
        "URL" => "revisionCalidad.php?ETAPA=RECEPCION&MODO=FOLIO&ID_ESPECIES=" . urlencode($ID_ESPECIES),
        "DATA" => $CALIDADCONTROL_ADO->listarRevisionRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "FOLIO")
    );
    $COBERTURAS[] = array(
        "ETAPA" => "PROCESO",
        "MODO" => "PROCESO",
        "NOMBRE" => "Proceso",
        "SUBTITULO" => "Proceso completo",
        "URL" => "revisionCalidad.php?ETAPA=PROCESO&MODO=PROCESO&ID_ESPECIES=" . urlencode($ID_ESPECIES),
        "DATA" => $CALIDADCONTROL_ADO->listarRevisionProcesoCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "PROCESO")
    );
    $COBERTURAS[] = array(
        "ETAPA" => "PROCESO",
        "MODO" => "PALLET",
        "NOMBRE" => "Proceso pallet",
        "SUBTITULO" => "Pallet producido",
        "URL" => "revisionCalidad.php?ETAPA=PROCESO&MODO=PALLET&ID_ESPECIES=" . urlencode($ID_ESPECIES),
        "DATA" => $CALIDADCONTROL_ADO->listarRevisionProcesoCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "PALLET")
    );
    $COBERTURAS[] = array(
        "ETAPA" => "EXPORTACION",
        "MODO" => "PALLET",
        "NOMBRE" => "Exportacion pallet",
        "SUBTITULO" => "Pallet producto terminado",
        "URL" => "revisionCalidad.php?ETAPA=EXPORTACION&MODO=PALLET&ID_ESPECIES=" . urlencode($ID_ESPECIES),
        "DATA" => $CALIDADCONTROL_ADO->listarRevisionExportacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "PALLET")
    );
    $COBERTURAS[] = array(
        "ETAPA" => "EXPORTACION",
        "MODO" => "CONTENEDOR",
        "NOMBRE" => "Exportacion contenedor",
        "SUBTITULO" => "Agrupado por contenedor",
        "URL" => "revisionCalidad.php?ETAPA=EXPORTACION&MODO=CONTENEDOR&ID_ESPECIES=" . urlencode($ID_ESPECIES),
        "DATA" => $CALIDADCONTROL_ADO->listarRevisionExportacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, "CONTENEDOR")
    );
}

$TOTAL = array("OPERACIONES" => 0, "SIN_CONTROL" => 0, "ABIERTO" => 0, "CERRADO" => 0);
foreach ($COBERTURAS as $i => $item) {
    $resumen = dashResumenCobertura($item["DATA"]);
    $COBERTURAS[$i]["RESUMEN"] = $resumen;
    $TOTAL["OPERACIONES"] += $resumen["TOTAL"];
    $TOTAL["SIN_CONTROL"] += $resumen["SIN_CONTROL"];
    $TOTAL["ABIERTO"] += $resumen["ABIERTO"];
    $TOTAL["CERRADO"] += $resumen["CERRADO"];
}

$ULTIMOS_CONTROLES = array_slice($CALIDADCONTROL_ADO->listarControl($EMPRESAS, $PLANTAS, $TEMPORADAS), 0, 8);
$PORC_COBERTURA = $TOTAL["OPERACIONES"] > 0 ? round((($TOTAL["ABIERTO"] + $TOTAL["CERRADO"]) / $TOTAL["OPERACIONES"]) * 100, 1) : 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Calidad</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
        .dash-kpi {
            background: #fff;
            border: 1px solid #e7ebf2;
            border-radius: 6px;
            min-height: 92px;
            padding: 14px 16px;
        }
        .dash-kpi span {
            color: #667085;
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .dash-kpi strong {
            color: #172b4d;
            display: block;
            font-size: 28px;
            line-height: 1.15;
        }
        .coverage-card {
            background: #fff;
            border: 1px solid #e7ebf2;
            border-radius: 6px;
            min-height: 156px;
            padding: 14px;
        }
        .coverage-title {
            color: #172b4d;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .coverage-subtitle {
            color: #667085;
            font-size: 12px;
            min-height: 18px;
        }
        .coverage-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 12px 0;
        }
        .coverage-grid div {
            background: #f7f8fb;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
        }
        .coverage-grid span {
            color: #667085;
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .coverage-grid strong {
            color: #172b4d;
            font-size: 18px;
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
                            <h3 class="page-title">Dashboard Calidad</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Calidad</a></li>
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
                            <h4 class="box-title">Cobertura de controles</h4>
                            <div class="box-controls pull-right">
                                <form method="GET" class="form-inline">
                                    <select class="form-control select2" name="ID_ESPECIES" onchange="this.form.submit()" style="min-width: 220px;">
                                        <?php foreach ($ARRAYESPECIES as $especie) { ?>
                                            <option value="<?php echo $especie["ID_ESPECIES"]; ?>" <?php echo (string) $ID_ESPECIES === (string) $especie["ID_ESPECIES"] ? "selected" : ""; ?>>
                                                <?php echo dashTexto($especie["NOMBRE_ESPECIES"]); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </form>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-xl-3 col-md-6 col-12 mb-15">
                                    <div class="dash-kpi">
                                        <span>Cobertura</span>
                                        <strong><?php echo dashNumero($PORC_COBERTURA, 1); ?>%</strong>
                                        <small><?php echo dashNumero($TOTAL["ABIERTO"] + $TOTAL["CERRADO"]); ?> con control de <?php echo dashNumero($TOTAL["OPERACIONES"]); ?></small>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 col-12 mb-15">
                                    <div class="dash-kpi">
                                        <span>Sin control</span>
                                        <strong><?php echo dashNumero($TOTAL["SIN_CONTROL"]); ?></strong>
                                        <a href="revisionCalidad.php?ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>&ESTADO=SIN_CONTROL">Ver pendientes</a>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 col-12 mb-15">
                                    <div class="dash-kpi">
                                        <span>Abiertos</span>
                                        <strong><?php echo dashNumero($TOTAL["ABIERTO"]); ?></strong>
                                        <a href="revisionCalidad.php?ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>&ESTADO=ABIERTO">Revisar abiertos</a>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6 col-12 mb-15">
                                    <div class="dash-kpi">
                                        <span>Cerrados</span>
                                        <strong><?php echo dashNumero($TOTAL["CERRADO"]); ?></strong>
                                        <a href="revisionCalidad.php?ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>&ESTADO=CERRADO">Ver cerrados</a>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-15">
                                <div class="col-md-12">
                                    <a href="exportarCalidadExcel.php?ETAPA=RECEPCION&MODO=TODOS&ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-success btn-sm">
                                        <i class="fa fa-file-excel-o"></i> Excel recepcion
                                    </a>
                                    <a href="exportarCalidadExcel.php?ETAPA=PROCESO&MODO=TODOS&ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-success btn-sm">
                                        <i class="fa fa-file-excel-o"></i> Excel proceso
                                    </a>
                                    <a href="exportarCalidadExcel.php?ETAPA=EXPORTACION&MODO=TODOS&ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-success btn-sm">
                                        <i class="fa fa-file-excel-o"></i> Excel exportacion
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <?php foreach ($COBERTURAS as $item) { ?>
                                    <?php $r = $item["RESUMEN"]; ?>
                                    <div class="col-xl-4 col-lg-6 col-12 mb-15">
                                        <div class="coverage-card">
                                            <div class="coverage-title"><?php echo dashTexto($item["NOMBRE"]); ?></div>
                                            <div class="coverage-subtitle"><?php echo dashTexto($item["SUBTITULO"]); ?></div>
                                            <div class="coverage-grid">
                                                <div><span>Total</span><strong><?php echo dashNumero($r["TOTAL"]); ?></strong></div>
                                                <div><span>Falta</span><strong><?php echo dashNumero($r["SIN_CONTROL"]); ?></strong></div>
                                                <div><span>Abierto</span><strong><?php echo dashNumero($r["ABIERTO"]); ?></strong></div>
                                            </div>
                                            <a href="<?php echo dashTexto($item["URL"]); ?>" class="btn btn-rounded btn-primary btn-sm">
                                                <i class="fa fa-search"></i> Revisar
                                            </a>
                                            <a href="exportarCalidadExcel.php?ETAPA=<?php echo dashTexto($item["ETAPA"]); ?>&MODO=<?php echo dashTexto($item["MODO"]); ?>&ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-success btn-sm">
                                                <i class="fa fa-file-excel-o"></i> Excel
                                            </a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-8 col-12">
                            <div class="box">
                                <div class="box-header with-border">
                                    <h4 class="box-title">Ultimos controles</h4>
                                </div>
                                <div class="box-body table-responsive">
                                    <table id="tabla-ultimos-controles" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Etapa</th>
                                                <th>Modo</th>
                                                <th>Operacion</th>
                                                <th>Estado</th>
                                                <th>Resolucion</th>
                                                <th class="text-center">Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ULTIMOS_CONTROLES as $control) { ?>
                                                <tr>
                                                    <td><?php echo dashTexto($control["FECHA"]); ?></td>
                                                    <td><?php echo dashTexto($control["ETAPA"]); ?></td>
                                                    <td><?php echo dashTexto($control["MODO_INGRESO"]); ?></td>
                                                    <td><?php echo dashTexto($control["NUMERO_OPERACION"]); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $control["ESTADO_CONTROL"] === "CERRADO" ? "badge-success" : "badge-warning"; ?>">
                                                            <?php echo dashTexto($control["ESTADO_CONTROL"]); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background: <?php echo dashTexto($CALIDADCONTROL_ADO->colorResolucion($control["RESULTADO_GENERAL"])); ?>; color: #fff;">
                                                            <?php echo dashTexto($control["RESULTADO_GENERAL"]); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center"><?php echo dashTexto($control["SCORE_GENERAL"]); ?></td>
                                                </tr>
                                            <?php } ?>
                                            <?php if (empty($ULTIMOS_CONTROLES)) { ?>
                                                <tr><td colspan="7" class="text-center text-muted">Sin controles registrados.</td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-12">
                            <div class="box">
                                <div class="box-header with-border">
                                    <h4 class="box-title">Accesos rapidos</h4>
                                </div>
                                <div class="box-body">
                                    <a href="revisionCalidad.php?ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-primary btn-block mb-10">
                                        <i class="fa fa-list"></i> Revision de controles
                                    </a>
                                    <a href="registroRecepcion.php?ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-info btn-block mb-10">
                                        <i class="fa fa-clipboard"></i> Registrar recepcion
                                    </a>
                                    <a href="registroOperacion.php?ETAPA=PROCESO&ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-secondary btn-block mb-10">
                                        <i class="fa fa-cogs"></i> Registrar proceso
                                    </a>
                                    <a href="registroOperacion.php?ETAPA=EXPORTACION&ID_ESPECIES=<?php echo dashTexto($ID_ESPECIES); ?>" class="btn btn-rounded btn-warning btn-block mb-10">
                                        <i class="fa fa-truck"></i> Registrar exportacion
                                    </a>
                                    <a href="registroParametro.php?GRUPO_REPORTE=DEFECTOS_CALIDAD" class="btn btn-rounded btn-light btn-block mb-10">
                                        <i class="fa fa-sliders"></i> Defectos de calidad
                                    </a>
                                    <a href="registroParametro.php?GRUPO_REPORTE=DEFECTOS_CONDICION" class="btn btn-rounded btn-light btn-block">
                                        <i class="fa fa-sliders"></i> Defectos de condicion
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
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
            $('#tabla-ultimos-controles').DataTable({
                ordering: false,
                paging: false,
                searching: false,
                info: false
            });
        });
    </script>
</body>

</html>
