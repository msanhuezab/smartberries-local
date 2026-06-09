<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "../../assest/controlador/CALIDADPARAMETRO_ADO.php";
include_once "../../assest/controlador/ESPECIES_ADO.php";

$CALIDADPARAMETRO_ADO = new CALIDADPARAMETRO_ADO();
$ESPECIES_ADO = new ESPECIES_ADO();

$MENSAJE = "";
$TIPOMENSAJE = "";
$PARAMETRO_EDITAR = null;

$ETAPAS = array(
    "RECEPCION" => "Recepcion",
    "PROCESO" => "Proceso",
    "DESPACHO" => "Despacho",
    "DESTINO" => "Destino"
);

$GRUPOSREPORTE = array(
    "CALIBRES" => "Distribucion de Calibres",
    "PRESIONES" => "Presiones",
    "PARAMETROS" => "Parametros",
    "DEFECTOS_CONDICION" => "Defectos de Condicion",
    "DEFECTOS_CALIDAD" => "Defectos de Calidad",
    "RESOLUCION" => "Resolucion"
);

$GRUPO_REPORTE_ACTUAL = $_GET["GRUPO_REPORTE"] ?? "PARAMETROS";
if (!isset($GRUPOSREPORTE[$GRUPO_REPORTE_ACTUAL])) {
    $GRUPO_REPORTE_ACTUAL = "PARAMETROS";
}

$ABREVIATURAS_ETAPA = array(
    "RECEPCION" => "REC",
    "PROCESO" => "PRO",
    "DESPACHO" => "DES",
    "DESTINO" => "DST"
);

$ABREVIATURAS_GRUPO = array(
    "CALIBRES" => "CAL",
    "PRESIONES" => "PRE",
    "PARAMETROS" => "PAR",
    "DEFECTOS_CONDICION" => "DCON",
    "DEFECTOS_CALIDAD" => "DCAL",
    "RESOLUCION" => "RES"
);

function calidadTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function calidadAbreviarEspecie($nombre) {
    $nombre = strtoupper((string) $nombre);
    $nombre = str_replace(
        array("Á", "É", "Í", "Ó", "Ú", "Ñ"),
        array("A", "E", "I", "O", "U", "N"),
        $nombre
    );
    $nombre = preg_replace('/[^A-Z0-9]/', '', $nombre);
    return substr($nombre !== "" ? $nombre : "ESP", 0, 3);
}

function calidadNombreEspecie($idEspecie, $arrayEspecies) {
    foreach ($arrayEspecies as $especie) {
        if ((string) $especie["ID_ESPECIES"] === (string) $idEspecie) {
            return $especie["NOMBRE_ESPECIES"];
        }
    }
    return "ESP";
}

if (isset($_POST["GUARDARPARAMETRO"])) {
    $nombreEspecie = calidadNombreEspecie($_POST["ID_ESPECIES"], $ESPECIES_ADO->listarEspeciesCalidadEmpresaCBX($EMPRESAS));
    $codigoEtapa = $ABREVIATURAS_ETAPA[$_POST["ETAPA"]] ?? substr($_POST["ETAPA"], 0, 3);
    $codigoGrupo = $ABREVIATURAS_GRUPO[$GRUPO_REPORTE_ACTUAL] ?? substr($GRUPO_REPORTE_ACTUAL, 0, 4);
    $codigoEspecie = calidadAbreviarEspecie($nombreEspecie);
    $correlativo = $CALIDADPARAMETRO_ADO->contarParametroGrupo($EMPRESAS, $TEMPORADAS, $_POST["ID_ESPECIES"], $_POST["ETAPA"], $GRUPO_REPORTE_ACTUAL) + 1;
    $codigoParametro = $codigoEtapa . "-" . $codigoGrupo . "-" . $codigoEspecie . "-" . str_pad($correlativo, 3, "0", STR_PAD_LEFT);

    $CALIDADPARAMETRO = new CALIDADPARAMETRO();
    $CALIDADPARAMETRO->__SET("TIPO_PARAMETRO", $GRUPO_REPORTE_ACTUAL);
    $CALIDADPARAMETRO->__SET("GRUPO_REPORTE", $GRUPO_REPORTE_ACTUAL);
    $CALIDADPARAMETRO->__SET("ETAPA", $_POST["ETAPA"]);
    $CALIDADPARAMETRO->__SET("CODIGO_PARAMETRO", $codigoParametro);
    $CALIDADPARAMETRO->__SET("NOMBRE_PARAMETRO", $_POST["NOMBRE_PARAMETRO"]);
    $CALIDADPARAMETRO->__SET("UNIDAD_MEDIDA", null);
    $CALIDADPARAMETRO->__SET("VALOR_MINIMO", null);
    $CALIDADPARAMETRO->__SET("VALOR_MAXIMO", null);
    $CALIDADPARAMETRO->__SET("VALOR_REFERENCIA", null);
    $CALIDADPARAMETRO->__SET("ES_REQUERIDO", isset($_POST["ES_REQUERIDO"]) ? 1 : 0);
    $CALIDADPARAMETRO->__SET("ORDEN", $_POST["ORDEN"] !== "" ? $_POST["ORDEN"] : 0);
    $CALIDADPARAMETRO->__SET("ID_EMPRESA", $EMPRESAS);
    $CALIDADPARAMETRO->__SET("ID_TEMPORADA", $TEMPORADAS);
    $CALIDADPARAMETRO->__SET("ID_ESPECIES", $_POST["ID_ESPECIES"]);
    $CALIDADPARAMETRO->__SET("ID_USUARIOI", $IDUSUARIOS);
    $CALIDADPARAMETRO->__SET("ID_USUARIOM", $IDUSUARIOS);
    $CALIDADPARAMETRO_ADO->agregarParametro($CALIDADPARAMETRO);
    $MENSAJE = "Parametro guardado correctamente.";
    $TIPOMENSAJE = "success";
}

if (isset($_POST["ACTUALIZARPARAMETRO"])) {
    $CALIDADPARAMETRO = new CALIDADPARAMETRO();
    $CALIDADPARAMETRO->__SET("ID_CALIDAD_PARAMETRO", $_POST["ID_CALIDAD_PARAMETRO"]);
    $CALIDADPARAMETRO->__SET("TIPO_PARAMETRO", $GRUPO_REPORTE_ACTUAL);
    $CALIDADPARAMETRO->__SET("GRUPO_REPORTE", $GRUPO_REPORTE_ACTUAL);
    $CALIDADPARAMETRO->__SET("ETAPA", $_POST["ETAPA"]);
    $CALIDADPARAMETRO->__SET("CODIGO_PARAMETRO", $_POST["CODIGO_PARAMETRO"]);
    $CALIDADPARAMETRO->__SET("NOMBRE_PARAMETRO", $_POST["NOMBRE_PARAMETRO"]);
    $CALIDADPARAMETRO->__SET("UNIDAD_MEDIDA", null);
    $CALIDADPARAMETRO->__SET("VALOR_MINIMO", null);
    $CALIDADPARAMETRO->__SET("VALOR_MAXIMO", null);
    $CALIDADPARAMETRO->__SET("VALOR_REFERENCIA", null);
    $CALIDADPARAMETRO->__SET("ES_REQUERIDO", isset($_POST["ES_REQUERIDO"]) ? 1 : 0);
    $CALIDADPARAMETRO->__SET("ORDEN", $_POST["ORDEN"] !== "" ? $_POST["ORDEN"] : 0);
    $CALIDADPARAMETRO->__SET("ID_TEMPORADA", $TEMPORADAS);
    $CALIDADPARAMETRO->__SET("ID_ESPECIES", $_POST["ID_ESPECIES"]);
    $CALIDADPARAMETRO->__SET("ID_USUARIOM", $IDUSUARIOS);
    $CALIDADPARAMETRO_ADO->actualizarParametro($CALIDADPARAMETRO);
    $MENSAJE = "Parametro actualizado correctamente.";
    $TIPOMENSAJE = "success";
}

if (isset($_POST["DESHABILITARPARAMETRO"])) {
    $CALIDADPARAMETRO = new CALIDADPARAMETRO();
    $CALIDADPARAMETRO->__SET("ID_CALIDAD_PARAMETRO", $_POST["ID_CALIDAD_PARAMETRO"]);
    $CALIDADPARAMETRO->__SET("ID_USUARIOM", $IDUSUARIOS);
    $CALIDADPARAMETRO_ADO->deshabilitarParametro($CALIDADPARAMETRO);
    $MENSAJE = "Parametro deshabilitado correctamente.";
    $TIPOMENSAJE = "success";
}

$ARRAYESPECIES = $ESPECIES_ADO->listarEspeciesCalidadEmpresaCBX($EMPRESAS);
$ARRAYPARAMETROS = $CALIDADPARAMETRO_ADO->listarParametroActivo($EMPRESAS, $TEMPORADAS, "", "", $GRUPO_REPORTE_ACTUAL);
if (isset($_GET["ID"])) {
    $ARRAYPARAMETRO_EDITAR = $CALIDADPARAMETRO_ADO->verParametro($_GET["ID"]);
    if ($ARRAYPARAMETRO_EDITAR && $ARRAYPARAMETRO_EDITAR[0]["GRUPO_REPORTE"] === $GRUPO_REPORTE_ACTUAL) {
        $PARAMETRO_EDITAR = $ARRAYPARAMETRO_EDITAR[0];
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title><?php echo calidadTexto($GRUPOSREPORTE[$GRUPO_REPORTE_ACTUAL]); ?> Calidad</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuCalidad.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title"><?php echo calidadTexto($GRUPOSREPORTE[$GRUPO_REPORTE_ACTUAL]); ?></h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Calidad</li>
                                        <li class="breadcrumb-item" aria-current="page">Configuracion</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#"><?php echo calidadTexto($GRUPOSREPORTE[$GRUPO_REPORTE_ACTUAL]); ?></a></li>
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
                            <h4 class="box-title"><?php echo $PARAMETRO_EDITAR ? "Editar registro" : "Nuevo registro"; ?></h4>
                        </div>
                        <form method="POST">
                            <?php if ($PARAMETRO_EDITAR) { ?>
                                <input type="hidden" name="ID_CALIDAD_PARAMETRO" value="<?php echo $PARAMETRO_EDITAR["ID_CALIDAD_PARAMETRO"]; ?>">
                                <input type="hidden" name="CODIGO_PARAMETRO" value="<?php echo calidadTexto($PARAMETRO_EDITAR["CODIGO_PARAMETRO"]); ?>">
                            <?php } ?>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Etapa</label>
                                            <select class="form-control select2" name="ETAPA" required>
                                                <option></option>
                                                <?php foreach ($ETAPAS as $codigo => $nombre) { ?>
                                                    <option value="<?php echo $codigo; ?>" <?php echo ($PARAMETRO_EDITAR && $PARAMETRO_EDITAR["ETAPA"] === $codigo) ? "selected" : ""; ?>><?php echo $nombre; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Especie</label>
                                            <select class="form-control select2" name="ID_ESPECIES" required>
                                                <option></option>
                                                <?php foreach ($ARRAYESPECIES as $r) { ?>
                                                    <option value="<?php echo $r["ID_ESPECIES"]; ?>" <?php echo ($PARAMETRO_EDITAR && (string) $PARAMETRO_EDITAR["ID_ESPECIES"] === (string) $r["ID_ESPECIES"]) ? "selected" : ""; ?>><?php echo calidadTexto($r["NOMBRE_ESPECIES"]); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Nombre parametro</label>
                                            <input type="text" class="form-control" name="NOMBRE_PARAMETRO" maxlength="150" value="<?php echo $PARAMETRO_EDITAR ? calidadTexto($PARAMETRO_EDITAR["NOMBRE_PARAMETRO"]) : ""; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Orden</label>
                                            <input type="number" class="form-control" name="ORDEN" value="<?php echo $PARAMETRO_EDITAR ? calidadTexto($PARAMETRO_EDITAR["ORDEN"]) : "0"; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group pt-25">
                                            <input type="checkbox" id="ES_REQUERIDO" name="ES_REQUERIDO" value="1" <?php echo ($PARAMETRO_EDITAR && (int) $PARAMETRO_EDITAR["ES_REQUERIDO"] === 1) ? "checked" : ""; ?>>
                                            <label for="ES_REQUERIDO">Requerido en el registro</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer text-right">
                                <?php if ($PARAMETRO_EDITAR) { ?>
                                    <a href="registroParametro.php?GRUPO_REPORTE=<?php echo calidadTexto($GRUPO_REPORTE_ACTUAL); ?>" class="btn btn-rounded btn-secondary">Cancelar</a>
                                <?php } ?>
                                <button type="submit" class="btn btn-rounded btn-primary" name="<?php echo $PARAMETRO_EDITAR ? "ACTUALIZARPARAMETRO" : "GUARDARPARAMETRO"; ?>" value="1">
                                    <i class="fa fa-save"></i> Guardar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="box">
                        <div class="box-header with-border">
                            <h4 class="box-title">Parametros configurados</h4>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table id="tabla-parametros-calidad" class="table-hover" style="width: 100%;">
                                    <thead>
                                        <tr class="text-center">
                                            <th>Accion</th>
                                            <th>Etapa</th>
                                            <th>Especie</th>
                                            <th>Parametro</th>
                                            <th>Requerido</th>
                                            <th>Orden</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ARRAYPARAMETROS as $r) { ?>
                                            <?php
                                            $NOMBREESPECIE = "Sin datos";
                                            foreach ($ARRAYESPECIES as $especie) {
                                                if ((string) $especie["ID_ESPECIES"] === (string) $r["ID_ESPECIES"]) {
                                                    $NOMBREESPECIE = $especie["NOMBRE_ESPECIES"];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <tr class="text-center">
                                                <td>
                                                    <a href="registroParametro.php?GRUPO_REPORTE=<?php echo calidadTexto($GRUPO_REPORTE_ACTUAL); ?>&ID=<?php echo $r["ID_CALIDAD_PARAMETRO"]; ?>" class="btn btn-sm btn-info">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Desea deshabilitar este parametro?');">
                                                        <input type="hidden" name="ID_CALIDAD_PARAMETRO" value="<?php echo $r["ID_CALIDAD_PARAMETRO"]; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" name="DESHABILITARPARAMETRO" value="1">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td><?php echo calidadTexto($ETAPAS[$r["ETAPA"]] ?? $r["ETAPA"]); ?></td>
                                                <td><?php echo calidadTexto($NOMBREESPECIE); ?></td>
                                                <td><?php echo calidadTexto($r["NOMBRE_PARAMETRO"]); ?></td>
                                                <td><?php echo ((int) $r["ES_REQUERIDO"] === 1) ? "Si" : "No"; ?></td>
                                                <td><?php echo calidadTexto($r["ORDEN"]); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
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
            $('#tabla-parametros-calidad').DataTable({
                ordering: true,
                paging: true,
                searching: true,
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
            <?php if ($MENSAJE !== "") { ?>
                $.toast({
                    heading: '<?php echo $TIPOMENSAJE === "success" ? "Correcto" : "Aviso"; ?>',
                    text: '<?php echo calidadTexto($MENSAJE); ?>',
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
