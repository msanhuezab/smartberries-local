<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "../../assest/controlador/CALIDADINSPECTOR_ADO.php";

$CALIDADINSPECTOR_ADO = new CALIDADINSPECTOR_ADO();

$MENSAJE = "";
$TIPOMENSAJE = "";
$INSPECTOR_EDITAR = null;

if (isset($_POST["GUARDARINSPECTOR"])) {
    $CALIDADINSPECTOR = new CALIDADINSPECTOR();
    $CALIDADINSPECTOR->__SET("NOMBRE_INSPECTOR", trim($_POST["NOMBRE_INSPECTOR"]));
    $CALIDADINSPECTOR->__SET("ID_EMPRESA", $EMPRESAS);
    $CALIDADINSPECTOR->__SET("ID_TEMPORADA", $TEMPORADAS);
    $CALIDADINSPECTOR->__SET("ID_USUARIOI", $IDUSUARIOS);
    $CALIDADINSPECTOR->__SET("ID_USUARIOM", $IDUSUARIOS);
    $CALIDADINSPECTOR_ADO->agregarInspector($CALIDADINSPECTOR);
    $MENSAJE = "Inspector guardado correctamente.";
    $TIPOMENSAJE = "success";
}

if (isset($_POST["ACTUALIZARINSPECTOR"])) {
    $CALIDADINSPECTOR = new CALIDADINSPECTOR();
    $CALIDADINSPECTOR->__SET("ID_CALIDAD_INSPECTOR", $_POST["ID_CALIDAD_INSPECTOR"]);
    $CALIDADINSPECTOR->__SET("NOMBRE_INSPECTOR", trim($_POST["NOMBRE_INSPECTOR"]));
    $CALIDADINSPECTOR->__SET("ID_USUARIOM", $IDUSUARIOS);
    $CALIDADINSPECTOR_ADO->actualizarInspector($CALIDADINSPECTOR);
    $MENSAJE = "Inspector actualizado correctamente.";
    $TIPOMENSAJE = "success";
}

if (isset($_POST["DESHABILITARINSPECTOR"])) {
    $CALIDADINSPECTOR = new CALIDADINSPECTOR();
    $CALIDADINSPECTOR->__SET("ID_CALIDAD_INSPECTOR", $_POST["ID_CALIDAD_INSPECTOR"]);
    $CALIDADINSPECTOR->__SET("ID_USUARIOM", $IDUSUARIOS);
    $CALIDADINSPECTOR_ADO->deshabilitarInspector($CALIDADINSPECTOR);
    $MENSAJE = "Inspector deshabilitado correctamente.";
    $TIPOMENSAJE = "success";
}

$ARRAYINSPECTORES = $CALIDADINSPECTOR_ADO->listarInspectorActivo($EMPRESAS, $TEMPORADAS);
if (isset($_GET["ID"])) {
    $ARRAYINSPECTOR_EDITAR = $CALIDADINSPECTOR_ADO->verInspector($_GET["ID"]);
    if ($ARRAYINSPECTOR_EDITAR && (string) $ARRAYINSPECTOR_EDITAR[0]["ID_EMPRESA"] === (string) $EMPRESAS && (string) $ARRAYINSPECTOR_EDITAR[0]["ID_TEMPORADA"] === (string) $TEMPORADAS) {
        $INSPECTOR_EDITAR = $ARRAYINSPECTOR_EDITAR[0];
    }
}

function inspectorTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Inspectores Calidad</title>
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
                            <h3 class="page-title">Inspectores</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Calidad</li>
                                        <li class="breadcrumb-item" aria-current="page">Configuracion</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Inspectores</a></li>
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
                            <h4 class="box-title"><?php echo $INSPECTOR_EDITAR ? "Editar inspector" : "Nuevo inspector"; ?></h4>
                        </div>
                        <form method="POST">
                            <?php if ($INSPECTOR_EDITAR) { ?>
                                <input type="hidden" name="ID_CALIDAD_INSPECTOR" value="<?php echo $INSPECTOR_EDITAR["ID_CALIDAD_INSPECTOR"]; ?>">
                            <?php } ?>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Nombre inspector</label>
                                            <input type="text" class="form-control" name="NOMBRE_INSPECTOR" maxlength="150" value="<?php echo $INSPECTOR_EDITAR ? inspectorTexto($INSPECTOR_EDITAR["NOMBRE_INSPECTOR"]) : ""; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group pt-25">
                                            <?php if ($INSPECTOR_EDITAR) { ?>
                                                <a href="registroInspector.php" class="btn btn-rounded btn-secondary">Cancelar</a>
                                            <?php } ?>
                                            <button type="submit" class="btn btn-rounded btn-primary" name="<?php echo $INSPECTOR_EDITAR ? "ACTUALIZARINSPECTOR" : "GUARDARINSPECTOR"; ?>" value="1">
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
                            <h4 class="box-title">Inspectores configurados</h4>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table id="tabla-inspectores-calidad" class="table-hover" style="width: 100%;">
                                    <thead>
                                        <tr class="text-center">
                                            <th>Accion</th>
                                            <th>Nombre inspector</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ARRAYINSPECTORES as $r) { ?>
                                            <tr class="text-center">
                                                <td>
                                                    <a href="registroInspector.php?ID=<?php echo $r["ID_CALIDAD_INSPECTOR"]; ?>" class="btn btn-sm btn-info">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Desea deshabilitar este inspector?');">
                                                        <input type="hidden" name="ID_CALIDAD_INSPECTOR" value="<?php echo $r["ID_CALIDAD_INSPECTOR"]; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" name="DESHABILITARINSPECTOR" value="1">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td><?php echo inspectorTexto($r["NOMBRE_INSPECTOR"]); ?></td>
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
            $('#tabla-inspectores-calidad').DataTable({
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
                    text: '<?php echo inspectorTexto($MENSAJE); ?>',
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
