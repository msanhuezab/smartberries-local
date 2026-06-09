<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "../../assest/controlador/CALIDADREGLARESOLUCION_ADO.php";
include_once "../../assest/controlador/ESPECIES_ADO.php";

$REGLA_ADO = new CALIDADREGLARESOLUCION_ADO();
$ESPECIES_ADO = new ESPECIES_ADO();

$MENSAJE = "";
$TIPOMENSAJE = "";
$REGLA_EDITAR = null;

$RESULTADOS = array(
    "APROBADO" => "Aprobado",
    "OBJETADO" => "Objetado",
    "RECHAZADO" => "Rechazado"
);

if (isset($_POST["GUARDARREGLA"])) {
    $REGLA = new CALIDADREGLARESOLUCION();
    $REGLA->__SET("NOMBRE_REGLA", "% ESTIMADO EXPORTACION");
    $REGLA->__SET("RESULTADO", $_POST["RESULTADO"]);
    $REGLA->__SET("VALOR_MINIMO", $_POST["VALOR_MINIMO"]);
    $REGLA->__SET("VALOR_MAXIMO", $_POST["VALOR_MAXIMO"]);
    $REGLA->__SET("ID_EMPRESA", $EMPRESAS);
    $REGLA->__SET("ID_TEMPORADA", $TEMPORADAS);
    $REGLA->__SET("ID_ESPECIES", $_POST["ID_ESPECIES"]);
    $REGLA->__SET("ID_USUARIOI", $IDUSUARIOS);
    $REGLA->__SET("ID_USUARIOM", $IDUSUARIOS);
    $REGLA_ADO->agregarRegla($REGLA);
    $MENSAJE = "Regla guardada correctamente.";
    $TIPOMENSAJE = "success";
}

if (isset($_POST["ACTUALIZARREGLA"])) {
    $REGLA = new CALIDADREGLARESOLUCION();
    $REGLA->__SET("ID_CALIDAD_REGLA_RESOLUCION", $_POST["ID_CALIDAD_REGLA_RESOLUCION"]);
    $REGLA->__SET("RESULTADO", $_POST["RESULTADO"]);
    $REGLA->__SET("VALOR_MINIMO", $_POST["VALOR_MINIMO"]);
    $REGLA->__SET("VALOR_MAXIMO", $_POST["VALOR_MAXIMO"]);
    $REGLA->__SET("ID_ESPECIES", $_POST["ID_ESPECIES"]);
    $REGLA->__SET("ID_USUARIOM", $IDUSUARIOS);
    $REGLA_ADO->actualizarRegla($REGLA);
    $MENSAJE = "Regla actualizada correctamente.";
    $TIPOMENSAJE = "success";
}

if (isset($_POST["CREARREGLASESTANDAR"])) {
    $reglas = array(
        array("APROBADO", 75, 100),
        array("OBJETADO", 60, 74.9999),
        array("RECHAZADO", 0, 59.9999)
    );
    foreach ($reglas as $item) {
        $REGLA = new CALIDADREGLARESOLUCION();
        $REGLA->__SET("NOMBRE_REGLA", "% ESTIMADO EXPORTACION");
        $REGLA->__SET("RESULTADO", $item[0]);
        $REGLA->__SET("VALOR_MINIMO", $item[1]);
        $REGLA->__SET("VALOR_MAXIMO", $item[2]);
        $REGLA->__SET("ID_EMPRESA", $EMPRESAS);
        $REGLA->__SET("ID_TEMPORADA", $TEMPORADAS);
        $REGLA->__SET("ID_ESPECIES", $_POST["ID_ESPECIES"]);
        $REGLA->__SET("ID_USUARIOI", $IDUSUARIOS);
        $REGLA->__SET("ID_USUARIOM", $IDUSUARIOS);
        $REGLA_ADO->agregarRegla($REGLA);
    }
    $MENSAJE = "Reglas estandar guardadas correctamente.";
    $TIPOMENSAJE = "success";
}

if (isset($_POST["DESHABILITARREGLA"])) {
    $REGLA = new CALIDADREGLARESOLUCION();
    $REGLA->__SET("ID_CALIDAD_REGLA_RESOLUCION", $_POST["ID_CALIDAD_REGLA_RESOLUCION"]);
    $REGLA->__SET("ID_USUARIOM", $IDUSUARIOS);
    $REGLA_ADO->deshabilitarRegla($REGLA);
    $MENSAJE = "Regla deshabilitada correctamente.";
    $TIPOMENSAJE = "success";
}

$ARRAYESPECIES = $ESPECIES_ADO->listarEspeciesCalidadEmpresaCBX($EMPRESAS);
$ARRAYREGLAS = $REGLA_ADO->listarReglaActiva($EMPRESAS, $TEMPORADAS);
if (isset($_GET["ID"])) {
    $ARRAYREGLA_EDITAR = $REGLA_ADO->verRegla($_GET["ID"]);
    if ($ARRAYREGLA_EDITAR && (string) $ARRAYREGLA_EDITAR[0]["ID_EMPRESA"] === (string) $EMPRESAS && (string) $ARRAYREGLA_EDITAR[0]["ID_TEMPORADA"] === (string) $TEMPORADAS) {
        $REGLA_EDITAR = $ARRAYREGLA_EDITAR[0];
    }
}

function reglaTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function reglaNombreEspecie($idEspecie, $arrayEspecies) {
    foreach ($arrayEspecies as $especie) {
        if ((string) $especie["ID_ESPECIES"] === (string) $idEspecie) {
            return $especie["NOMBRE_ESPECIES"];
        }
    }
    return "Sin datos";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Reglas de Resolucion</title>
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
                            <h3 class="page-title">Reglas de Resolucion</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Calidad</li>
                                        <li class="breadcrumb-item" aria-current="page">Configuracion</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Reglas Resolucion</a></li>
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
                            <h4 class="box-title"><?php echo $REGLA_EDITAR ? "Editar regla" : "Nueva regla"; ?></h4>
                        </div>
                        <form method="POST">
                            <?php if ($REGLA_EDITAR) { ?>
                                <input type="hidden" name="ID_CALIDAD_REGLA_RESOLUCION" value="<?php echo $REGLA_EDITAR["ID_CALIDAD_REGLA_RESOLUCION"]; ?>">
                            <?php } ?>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Especie</label>
                                            <select class="form-control select2" name="ID_ESPECIES" required>
                                                <option></option>
                                                <?php foreach ($ARRAYESPECIES as $r) { ?>
                                                    <option value="<?php echo $r["ID_ESPECIES"]; ?>" <?php echo ($REGLA_EDITAR && (string) $REGLA_EDITAR["ID_ESPECIES"] === (string) $r["ID_ESPECIES"]) ? "selected" : ""; ?>><?php echo reglaTexto($r["NOMBRE_ESPECIES"]); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Resultado</label>
                                            <select class="form-control select2" name="RESULTADO" required>
                                                <option></option>
                                                <?php foreach ($RESULTADOS as $codigo => $nombre) { ?>
                                                    <option value="<?php echo $codigo; ?>" <?php echo ($REGLA_EDITAR && $REGLA_EDITAR["RESULTADO"] === $codigo) ? "selected" : ""; ?>><?php echo $nombre; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Desde %</label>
                                            <input type="number" step="0.0001" class="form-control" name="VALOR_MINIMO" value="<?php echo $REGLA_EDITAR ? reglaTexto($REGLA_EDITAR["VALOR_MINIMO"]) : ""; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Hasta %</label>
                                            <input type="number" step="0.0001" class="form-control" name="VALOR_MAXIMO" value="<?php echo $REGLA_EDITAR ? reglaTexto($REGLA_EDITAR["VALOR_MAXIMO"]) : ""; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group pt-25">
                                            <?php if ($REGLA_EDITAR) { ?>
                                                <a href="registroReglaResolucion.php" class="btn btn-rounded btn-secondary">Cancelar</a>
                                            <?php } ?>
                                            <button type="submit" class="btn btn-rounded btn-primary" name="<?php echo $REGLA_EDITAR ? "ACTUALIZARREGLA" : "GUARDARREGLA"; ?>" value="1">
                                                <i class="fa fa-save"></i> Guardar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="box">
                        <div class="box-header with-border">
                            <h4 class="box-title">Crear reglas estandar</h4>
                        </div>
                        <form method="POST">
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Especie</label>
                                            <select class="form-control select2" name="ID_ESPECIES" required>
                                                <option></option>
                                                <?php foreach ($ARRAYESPECIES as $r) { ?>
                                                    <option value="<?php echo $r["ID_ESPECIES"]; ?>"><?php echo reglaTexto($r["NOMBRE_ESPECIES"]); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group pt-25">
                                            <button type="submit" class="btn btn-rounded btn-info" name="CREARREGLASESTANDAR" value="1">
                                                <i class="fa fa-magic"></i> Crear Aprobado / Objetado / Rechazado
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="box">
                        <div class="box-header with-border">
                            <h4 class="box-title">Reglas configuradas</h4>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table id="tabla-reglas-resolucion" class="table-hover" style="width: 100%;">
                                    <thead>
                                        <tr class="text-center">
                                            <th>Accion</th>
                                            <th>Especie</th>
                                            <th>Regla</th>
                                            <th>Resultado</th>
                                            <th>Desde %</th>
                                            <th>Hasta %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ARRAYREGLAS as $r) { ?>
                                            <tr class="text-center">
                                                <td>
                                                    <a href="registroReglaResolucion.php?ID=<?php echo $r["ID_CALIDAD_REGLA_RESOLUCION"]; ?>" class="btn btn-sm btn-info">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Desea deshabilitar esta regla?');">
                                                        <input type="hidden" name="ID_CALIDAD_REGLA_RESOLUCION" value="<?php echo $r["ID_CALIDAD_REGLA_RESOLUCION"]; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" name="DESHABILITARREGLA" value="1">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td><?php echo reglaTexto(reglaNombreEspecie($r["ID_ESPECIES"], $ARRAYESPECIES)); ?></td>
                                                <td><?php echo reglaTexto($r["NOMBRE_REGLA"]); ?></td>
                                                <td><?php echo reglaTexto($RESULTADOS[$r["RESULTADO"]] ?? $r["RESULTADO"]); ?></td>
                                                <td><?php echo reglaTexto($r["VALOR_MINIMO"]); ?></td>
                                                <td><?php echo reglaTexto($r["VALOR_MAXIMO"]); ?></td>
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
            $('#tabla-reglas-resolucion').DataTable({
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
                    text: '<?php echo reglaTexto($MENSAJE); ?>',
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
