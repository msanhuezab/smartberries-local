<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "../../assest/controlador/CALIDADCONTROL_ADO.php";
include_once "../../assest/controlador/ESPECIES_ADO.php";

$CALIDADCONTROL_ADO = new CALIDADCONTROL_ADO();
$ESPECIES_ADO = new ESPECIES_ADO();

function revisionTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function revisionNumero($valor, $dec = 0) {
    if ($valor === null || $valor === "") {
        return "";
    }
    return number_format((float) $valor, $dec, ",", ".");
}

function revisionEstado($fila) {
    if ((int) ($fila["TOTAL_CONTROLES"] ?? 0) <= 0) {
        return array("SIN_CONTROL", "Sin control", "badge-danger");
    }
    if ((int) ($fila["ABIERTOS"] ?? 0) > 0) {
        return array("ABIERTO", "Abierto", "badge-warning");
    }
    return array("CERRADO", "Cerrado", "badge-success");
}

$ETAPAS = array(
    "RECEPCION" => "Recepcion",
    "PROCESO" => "Proceso",
    "EXPORTACION" => "Exportacion"
);
$MODOS_ETAPA = array(
    "RECEPCION" => array("AGRUPADO" => "Recepcion", "FOLIO" => "Folio"),
    "PROCESO" => array("PROCESO" => "Proceso", "PALLET" => "Pallet"),
    "EXPORTACION" => array("PALLET" => "Pallet", "REFERENCIA" => "Referencia", "CONTENEDOR" => "Contenedor")
);

$ETAPA = strtoupper($_GET["ETAPA"] ?? "PROCESO");
if (!isset($ETAPAS[$ETAPA])) {
    $ETAPA = "PROCESO";
}
$MODO = strtoupper($_GET["MODO"] ?? array_key_first($MODOS_ETAPA[$ETAPA]));
if (!isset($MODOS_ETAPA[$ETAPA][$MODO])) {
    $MODO = array_key_first($MODOS_ETAPA[$ETAPA]);
}
$ID_ESPECIES = $_GET["ID_ESPECIES"] ?? "1";
$FILTRO_ESTADO = strtoupper($_GET["ESTADO"] ?? "TODOS");
if (!in_array($FILTRO_ESTADO, array("TODOS", "SIN_CONTROL", "ABIERTO", "CERRADO"), true)) {
    $FILTRO_ESTADO = "TODOS";
}

$ARRAYESPECIES = $ESPECIES_ADO->listarEspeciesCalidadCBX();
$FILAS = array();
if ($ID_ESPECIES !== "") {
    if ($ETAPA === "RECEPCION") {
        $FILAS = $CALIDADCONTROL_ADO->listarRevisionRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $MODO);
    } elseif ($ETAPA === "PROCESO") {
        $FILAS = $CALIDADCONTROL_ADO->listarRevisionProcesoCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $MODO);
    } else {
        $FILAS = $CALIDADCONTROL_ADO->listarRevisionExportacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $MODO);
    }
}

$RESUMEN = array("TOTAL" => 0, "SIN_CONTROL" => 0, "ABIERTO" => 0, "CERRADO" => 0);
$FILAS_FILTRADAS = array();
foreach ($FILAS as $fila) {
    $estado = revisionEstado($fila);
    $RESUMEN["TOTAL"]++;
    $RESUMEN[$estado[0]]++;
    if ($FILTRO_ESTADO === "TODOS" || $FILTRO_ESTADO === $estado[0]) {
        $fila["_ESTADO_CODIGO"] = $estado[0];
        $fila["_ESTADO_NOMBRE"] = $estado[1];
        $fila["_ESTADO_BADGE"] = $estado[2];
        $FILAS_FILTRADAS[] = $fila;
    }
}

function revisionUrlRegistro($etapa, $modo, $idEspecies, $fila) {
    if ($etapa === "RECEPCION") {
        if ($modo === "FOLIO") {
            return "registroRecepcion.php?ID_ESPECIES=" . urlencode($idEspecies) . "&MODO_INGRESO=FOLIO&FOLIO_BUSCAR=" . urlencode($fila["NUMERO_OPERACION"]);
        }
        return "registroRecepcion.php?ID_ESPECIES=" . urlencode($idEspecies) . "&ID_RECEPCION=" . urlencode($fila["ID_OPERACION"]) . "&MODO_INGRESO=AGRUPADO";
    }
    if ($etapa === "PROCESO") {
        if ($modo === "PALLET") {
            return "registroOperacion.php?ETAPA=PROCESO&ID_ESPECIES=" . urlencode($idEspecies) . "&MODO_INGRESO=PALLET&ID_PROCESO=" . urlencode($fila["ID_PROCESO"]) . "&ID_PALLET=" . urlencode($fila["ID_OPERACION"]);
        }
        return "registroOperacion.php?ETAPA=PROCESO&ID_ESPECIES=" . urlencode($idEspecies) . "&MODO_INGRESO=PROCESO&ID_PROCESO=" . urlencode($fila["ID_OPERACION"]);
    }
    if ($modo === "PALLET") {
        return "registroOperacion.php?ETAPA=EXPORTACION&ID_ESPECIES=" . urlencode($idEspecies) . "&MODO_INGRESO=PALLET&FOLIO_BUSCAR=" . urlencode($fila["NUMERO_OPERACION"]);
    }
    return "registroOperacion.php?ETAPA=EXPORTACION&ID_ESPECIES=" . urlencode($idEspecies) . "&MODO_INGRESO=" . urlencode($modo) . "&VALOR_OPERACION=" . urlencode($fila["NUMERO_OPERACION"]);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Revision Calidad</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
        .revision-kpi { border: 1px solid #e7ebf2; border-radius: 6px; padding: 12px 14px; background: #fff; }
        .revision-kpi span { color: #6b7280; display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .revision-kpi strong { color: #172b4d; display: block; font-size: 24px; line-height: 1.15; }
        .revision-actions a { margin: 2px; }
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
                            <h3 class="page-title">Revision Calidad</h3>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>

                <section class="content">
                    <div class="box">
                        <div class="box-header with-border"><h4 class="box-title">Filtros</h4></div>
                        <form method="GET">
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>Etapa</label>
                                        <select class="form-control select2" name="ETAPA" onchange="this.form.submit()">
                                            <?php foreach ($ETAPAS as $codigo => $nombre) { ?>
                                                <option value="<?php echo $codigo; ?>" <?php echo $ETAPA === $codigo ? "selected" : ""; ?>><?php echo revisionTexto($nombre); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Modo</label>
                                        <select class="form-control select2" name="MODO" onchange="this.form.submit()">
                                            <?php foreach ($MODOS_ETAPA[$ETAPA] as $codigo => $nombre) { ?>
                                                <option value="<?php echo $codigo; ?>" <?php echo $MODO === $codigo ? "selected" : ""; ?>><?php echo revisionTexto($nombre); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Especie</label>
                                        <select class="form-control select2" name="ID_ESPECIES" onchange="this.form.submit()">
                                            <?php foreach ($ARRAYESPECIES as $especie) { ?>
                                                <option value="<?php echo $especie["ID_ESPECIES"]; ?>" <?php echo (string) $ID_ESPECIES === (string) $especie["ID_ESPECIES"] ? "selected" : ""; ?>><?php echo revisionTexto($especie["NOMBRE_ESPECIES"]); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Estado calidad</label>
                                        <select class="form-control select2" name="ESTADO" onchange="this.form.submit()">
                                            <option value="TODOS" <?php echo $FILTRO_ESTADO === "TODOS" ? "selected" : ""; ?>>Todos</option>
                                            <option value="SIN_CONTROL" <?php echo $FILTRO_ESTADO === "SIN_CONTROL" ? "selected" : ""; ?>>Sin control</option>
                                            <option value="ABIERTO" <?php echo $FILTRO_ESTADO === "ABIERTO" ? "selected" : ""; ?>>Abierto</option>
                                            <option value="CERRADO" <?php echo $FILTRO_ESTADO === "CERRADO" ? "selected" : ""; ?>>Cerrado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-10">
                                    <div class="col-md-12 text-right">
                                        <a class="btn btn-rounded btn-success" href="exportarCalidadExcel.php?ETAPA=<?php echo revisionTexto($ETAPA); ?>&MODO=<?php echo revisionTexto($MODO); ?>&ID_ESPECIES=<?php echo revisionTexto($ID_ESPECIES); ?>&ESTADO=<?php echo revisionTexto($FILTRO_ESTADO); ?>">
                                            <i class="fa fa-file-excel-o"></i> Exportar Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row">
                        <div class="col-md-3"><div class="revision-kpi"><span>Total</span><strong><?php echo revisionNumero($RESUMEN["TOTAL"]); ?></strong></div></div>
                        <div class="col-md-3"><div class="revision-kpi"><span>Sin control</span><strong><?php echo revisionNumero($RESUMEN["SIN_CONTROL"]); ?></strong></div></div>
                        <div class="col-md-3"><div class="revision-kpi"><span>Abiertos</span><strong><?php echo revisionNumero($RESUMEN["ABIERTO"]); ?></strong></div></div>
                        <div class="col-md-3"><div class="revision-kpi"><span>Cerrados</span><strong><?php echo revisionNumero($RESUMEN["CERRADO"]); ?></strong></div></div>
                    </div>

                    <div class="box">
                        <div class="box-header with-border"><h4 class="box-title">Operaciones</h4></div>
                        <div class="box-body table-responsive">
                            <table id="tabla-revision-calidad" class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th>Estado calidad</th>
                                        <th>Operacion</th>
                                        <th>Fecha</th>
                                        <th>Detalle</th>
                                        <th class="text-right">Pallets</th>
                                        <th class="text-right">Kilos</th>
                                        <th class="text-center">Score</th>
                                        <th>Resolucion</th>
                                        <th class="text-center">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($FILAS_FILTRADAS as $fila) { ?>
                                        <?php $urlRegistro = revisionUrlRegistro($ETAPA, $MODO, $ID_ESPECIES, $fila); ?>
                                        <tr>
                                            <td><span class="badge <?php echo revisionTexto($fila["_ESTADO_BADGE"]); ?>"><?php echo revisionTexto($fila["_ESTADO_NOMBRE"]); ?></span></td>
                                            <td><?php echo revisionTexto($fila["NUMERO_OPERACION"]); ?></td>
                                            <td><?php echo revisionTexto($fila["FECHA_OPERACION"]); ?></td>
                                            <td><?php echo revisionTexto($fila["DESCRIPCION"]); ?></td>
                                            <td class="text-right"><?php echo revisionNumero($fila["TOTAL_PALLETS"]); ?></td>
                                            <td class="text-right"><?php echo revisionNumero($fila["KILOS_NETO"], 2); ?></td>
                                            <td class="text-center"><?php echo revisionTexto($fila["SCORE_GENERAL"]); ?></td>
                                            <td><?php echo revisionTexto($fila["RESULTADO_GENERAL"]); ?></td>
                                            <td class="text-center revision-actions">
                                                <a href="<?php echo revisionTexto($urlRegistro); ?>" class="btn btn-rounded btn-primary btn-sm">
                                                    <i class="fa fa-pencil"></i> Revisar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (empty($FILAS_FILTRADAS)) { ?>
                                        <tr><td colspan="9" class="text-center text-muted">Sin datos para los filtros seleccionados.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
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
            $('#tabla-revision-calidad').DataTable({
                ordering: true,
                paging: true,
                searching: true,
                pageLength: 25,
                language: {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
                    "sZeroRecords": "No se encontraron resultados",
                    "sEmptyTable": "Ningun dato disponible en esta tabla",
                    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "sSearch": "Buscar:",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Ultimo",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    }
                }
            });
        });
    </script>
</body>
</html>
