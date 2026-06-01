<?php
include_once "../../assest/config/validarUsuarioFruta.php";
include_once '../../assest/controlador/productor_controller.php';

$ID_EMPRESA = $_SESSION['ID_EMPRESA'];
$productorController = new ProductorController();

// SOLO PRODUCTORES CON ESTADO_REGISTRO = 1
$productores = array_filter(
    $productorController->index($ID_EMPRESA),
    function($p) {
        return isset($p->ESTADO_REGISTRO) && $p->ESTADO_REGISTRO == 1;
    }
);

$especies = $productorController->listaEspecie();
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Subir Documento</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
<div class="wrapper">

    <?php include_once "../../assest/config/menuFruta.php";?>

    <div class="content-wrapper">
        <div class="container-full">

            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="mr-auto">
                        <h3 class="page-title">Subir Documento</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"> <a href="index.php"> <i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item" aria-current="page">Modulo</li>
                                    <li class="breadcrumb-item" aria-current="page">Calidad de la Fruta</li>
                                    <li class="breadcrumb-item" aria-current="page">Documentos por Productor</li>
                                    <li class="breadcrumb-item active" aria-current="page"> <a href="#">Subir Documento </a> </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>

            <section class="content">
                <div class="box">
                    <div class="box-header with-border bg-primary">
                        <h4 class="box-title">Subir Documento</h4>
                    </div>

                    <form class="form" role="form-subir-documento" id="form-subir-documento">
                        <div class="box-body">
                            <div class="row">

                                <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6 col-xs-6">
                                    <div class="form-group">
                                        <label>Productor</label>
                                        <select class="form-control select2" id="productor" name="productor" style="width: 100%;">
                                            <option value="">Seleccione una opción</option>
                                            <?php foreach ($productores as $productor): ?>
                                                <option value="<?= $productor->ID_PRODUCTOR ?>">
                                                    (CSG: <?= $productor->CSG_PRODUCTOR ?>) <?= $productor->NOMBRE_PRODUCTOR ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6 col-xs-6">
                                    <div class="form-group">
                                        <label>Especie</label>
                                        <select class="form-control select2" id="especie" name="especie" style="width: 100%;">
                                            <option value="">Seleccione una opción</option>
                                            <?php foreach ($especies as $especie): ?>
                                                <option value="<?= $especie->ID_ESPECIES ?>"><?= $especie->NOMBRE_ESPECIES ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 col-6 col-xs-6">
                                    <div class="form-group">
                                        <label>Nombre Documento</label>
                                        <input type="text" class="form-control" placeholder="Nombre Documento" id="nombre_documento" name="nombre_documento" required/>
                                    </div>
                                </div>

                                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 col-6 col-xs-6">
                                    <div class="form-group">
                                        <label>Fecha Vigencia</label>
                                        <input type="date" class="form-control" id="fecha_vigencia" name="fecha_vigencia" required/>
                                    </div>
                                </div>

                                <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6 col-xs-6">
                                    <div class="form-group">
                                        <label>Adjuntar Archivo</label>
                                        <input type="file" name="documento" id="documento">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="box-footer">
                            <div class="btn-group btn-block col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12" role="group">
                                <button type="button" class="btn btn-success" title="Volver" onclick="javascript:history.back()">
                                    <i class="ti-back-left"></i> Volver
                                </button>
                                <button type="submit" class="btn btn-primary" title="Subir">
                                    <i class="ti-save-alt"></i> Guardar
                                </button>
                            </div>
                        </div>

                    </form>

                </div>
            </section>
        </div>
    </div>

    <script type="text/javascript">

        $('body').on('submit', 'form#form-subir-documento', function(event) {
            event.preventDefault();
            var formData = new FormData($('form#form-subir-documento')[0]);

            $.ajax({
                data: formData,
                url: "../../assest/controlador/productor_controller.php?action=uploadDocumento",
                type: "POST",
                cache: false,
                contentType: false,
                processData: false,
                success: function(respuesta) {

                    $.toast({
                        heading: 'Archivo cargado',
                        text: '¡Archivo cargado correctamente!',
                        position: 'bottom-left',
                        loaderBg: '#ff6849',
                        icon: 'success',
                        hideAfter: 3500,
                        stack: 6
                    });

                    $("form#form-subir-documento")[0].reset();
                }
            });
            return false;
        });

    </script>

    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraFruta.php"; ?>

</div>

<?php include_once "../../assest/config/urlBase.php"; ?>

</body>
</html>
