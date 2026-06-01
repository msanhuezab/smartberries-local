<?php

include_once "../../assest/config/validarUsuarioOpera.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES

include_once '../../assest/controlador/productor_controller.php';
include_once '../../assest/controlador/EMPRESAPRODUCTOR_ADO.php';


$EMPRESAPRODUCTOR_ADO =  new EMPRESAPRODUCTOR_ADO();


$ARRAYEMPRESAPRODUCTOR=$EMPRESAPRODUCTOR_ADO->buscarEmpresaProductorPorUsuarioCBX($IDUSUARIOS);
//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR
//$id = base64_decode($_REQUEST['id']);
$productorController = new ProductorController();

?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Lista Documentos</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <!- LLAMADA DE LOS ARCHIVOS NECESARIOS PARA DISEÑO Y FUNCIONES BASE DE LA VISTA -!>
        <?php include_once "../../assest/config/urlHead.php"; ?>
        <style>
            .documentos-toolbar {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }
            .documentos-toolbar .form-control {
                min-width: 220px;
            }
            .documentos-meta {
                font-size: 0.85rem;
                color: #6c757d;
            }
            .documentos-table {
                width: 100%;
                table-layout: auto;
                border-collapse: collapse;
            }
            .documentos-table thead th {
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #5f6b7a;
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
                padding: 12px;
            }
            .documentos-table tbody td {
                padding: 12px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                vertical-align: middle;
            }
            .documentos-col--icon,
            .documentos-col--vigencia {
                width: 1%;
                white-space: nowrap;
            }
            .documentos-col--nombre {
                width: auto;
            }
            .documentos-table tbody tr:hover {
                background: rgba(15, 129, 199, 0.05);
            }
            .documentos-badge {
                display: inline-flex;
                align-items: center;
                font-size: 0.75rem;
                padding: 4px 8px;
                border-radius: 999px;
                font-weight: 600;
            }
            .documentos-badge--vigente {
                background: rgba(40, 167, 69, 0.12);
                color: #1e7e34;
            }
            .documentos-badge--vencido {
                background: rgba(220, 53, 69, 0.12);
                color: #c82333;
            }
        </style>
        <!- FUNCIONES BASES -!>
            <script type="text/javascript">
                //REDIRECCIONAR A LA PAGINA SELECIONADA
                function irPagina(url) {
                    location.href = "" + url;
                }
                function abrirPestana(url) {
                    var win = window.open(url, '_blank');
                    win.focus();
                }
                //FUNCION PARA ABRIR VENTANA QUE SE ENCUENTRA LA OPERACIONES DE DETALLE DE RECEPCION
                function abrirVentana(url) {
                    var opciones =
                        "'directories=no, location=no, menubar=no, scrollbars=yes, statusbar=no, tittlebar=no, width=1000, height=800'";
                    window.open(url, 'window', opciones);
                }
                function ordenarDocumentos() {
                    var selector = document.getElementById('ordenDocumentos');
                    if (!selector) {
                        return;
                    }
                    var opcion = selector.value;
                    var tbody = document.getElementById('bodyRegistroCalidad');
                    if (!tbody) {
                        return;
                    }
                    var filas = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
                    var orden = opcion.includes('desc') ? -1 : 1;

                    filas.sort(function (a, b) {
                        if (opcion.includes('vigencia')) {
                            var aTs = parseInt(a.dataset.vigenciaTs || '0', 10);
                            var bTs = parseInt(b.dataset.vigenciaTs || '0', 10);
                            return (aTs - bTs) * orden;
                        }
                        var aNombre = (a.dataset.nombre || '').toLowerCase();
                        var bNombre = (b.dataset.nombre || '').toLowerCase();
                        return aNombre.localeCompare(bNombre) * orden;
                    });

                    filas.forEach(function (fila) {
                        tbody.appendChild(fila);
                    });
                }
                function filtrarDocumentos() {
                    var filtro = document.getElementById('filtroDocumento').value.toLowerCase();
                    var soloVigentes = document.getElementById('soloVigentes').checked;
                    var filas = document.querySelectorAll('#bodyRegistroCalidad tr');
                    var visibles = 0;

                    filas.forEach(function (fila) {
                        var nombre = (fila.dataset.nombre || '').toLowerCase();
                        var vigencia = (fila.dataset.vigencia || '').toLowerCase();
                        var vencido = fila.dataset.vencido === '1';
                        var coincide = nombre.includes(filtro) || vigencia.includes(filtro);
                        var mostrar = coincide && (!soloVigentes || !vencido);

                        fila.style.display = mostrar ? '' : 'none';
                        if (mostrar) {
                            visibles++;
                        }
                    });

                    var contador = document.getElementById('contadorDocumentos');
                    if (contador) {
                        contador.textContent = visibles + ' documentos';
                    }
                }
                function actualizarDocumentos() {
                    ordenarDocumentos();
                    filtrarDocumentos();
                }
            </script>

</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary" >
    <div class="wrapper">
        <!- LLAMADA AL MENU PRINCIPAL DE LA PAGINA-!>
            <?php include_once "../../assest/config/menuOpera.php"; ?>
            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">
                <div class="container-full">

                    <!-- Content Header (Page header) -->
                    <div class="content-header">
                        <div class="d-flex align-items-center">
                            <div class="mr-auto">
                                <h3 class="page-title">Lista Documentos</h3>
                                <div class="d-inline-block align-items-center">
                                    <nav>
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                            <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                            <li class="breadcrumb-item" aria-current="page">Registro de Calidad</li>
                                            <li class="breadcrumb-item" aria-current="page">Documentos</li>
                                            <li class="breadcrumb-item active" aria-current="page"> <a href="#">Lista Documentos</a>
                                            </li>
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
                                <div class="row">
                                    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 col-xs-12">
                                        <div class="documentos-toolbar">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <input id="filtroDocumento" type="text" class="form-control" placeholder="Buscar por nombre o vigencia" onkeyup="filtrarDocumentos()">
                                                <select id="ordenDocumentos" class="form-control" onchange="actualizarDocumentos()">
                                                    <option value="nombre-asc">Ordenar: Nombre (A-Z)</option>
                                                    <option value="nombre-desc">Ordenar: Nombre (Z-A)</option>
                                                    <option value="vigencia-asc">Ordenar: Vigencia (Antigua)</option>
                                                    <option value="vigencia-desc">Ordenar: Vigencia (Reciente)</option>
                                                </select>
                                                <label class="d-flex align-items-center mb-0">
                                                    <input id="soloVigentes" type="checkbox" class="mr-2" onchange="filtrarDocumentos()">
                                                    <span class="documentos-meta">Solo vigentes</span>
                                                </label>
                                            </div>
                                            <span id="contadorDocumentos" class="documentos-meta">0 documentos</span>
                                        </div>
                                        <div class="table-responsive">
                                            <table id="existenciaptagrupado" class="documentos-table">
                                                <thead>
                                                    <tr>
                                                        <th class="documentos-col--icon">Archivo</th>
                                                        <th class="documentos-col--nombre">Nombre</th>
                                                        <th class="documentos-col--vigencia">Vigencia</th>
                                                      
                                                    </tr>
                                                </thead>
                                                <tbody id="bodyRegistroCalidad">
                                                <?php foreach ($ARRAYEMPRESAPRODUCTOR as $a) : ?>
                                                <?php 
                                                    
                                                        $documentos = $productorController->viewDocumentosEspecie($a["ID_PRODUCTOR"], $ESPECIE);
                                                
                                                ?>
                                                <?php if (!empty($documentos)): ?>
                                                    <?php foreach ($documentos as $documento): ?>
                                                        <?php
                                                            $vigenciaRaw = $documento->vigencia_documento ?? '';
                                                            $vigenciaDate = $vigenciaRaw ? date_create($vigenciaRaw) : false;
                                                            $vencido = $vigenciaDate ? ($vigenciaDate < new DateTime('today')) : false;
                                                            $vigenciaTimestamp = $vigenciaDate ? $vigenciaDate->getTimestamp() : 0;
                                                        ?>
                                                        <tr data-nombre="<?php echo htmlspecialchars($documento->nombre_documento); ?>" data-vigencia="<?php echo htmlspecialchars($vigenciaRaw); ?>" data-vencido="<?php echo $vencido ? '1' : '0'; ?>" data-vigencia-ts="<?php echo $vigenciaTimestamp; ?>">
                                                            <td class="documentos-col--icon">
                                                                <a href="../../data/data_productor/<?php echo $documento->archivo_documento; ?>" target="_blank" class="btn btn-info">
                                                                <i class="ti-file"></i>
                                                                </a>
                                                            </td>
                                                            <td class="documentos-col--nombre"><?php echo htmlspecialchars($documento->nombre_documento); ?></td>
                                                            <td class="documentos-col--vigencia">
                                                                <div class="d-flex flex-column">
                                                                    <span><?php echo htmlspecialchars($documento->vigencia_documento); ?></span>
                                                                    <?php if ($vigenciaDate): ?>
                                                                        <span class="documentos-badge <?php echo $vencido ? 'documentos-badge--vencido' : 'documentos-badge--vigente'; ?>">
                                                                            <?php echo $vencido ? 'Vencido' : 'Vigente'; ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                      
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                   <!--<tr>
                                                        <td colspan="7">No hay productores disponibles.</td>
                                                    </tr>-->
                                                <?php endif; ?>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
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



            <!- LLAMADA ARCHIVO DEL DISEÑO DEL FOOTER Y MENU USUARIO -!>
                <?php include_once "../../assest/config/footer.php"; ?>
                <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <!- LLAMADA URL DE ARCHIVOS DE DISEÑO Y JQUERY E OTROS -!>
        <?php include_once "../../assest/config/urlBase.php"; ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            actualizarDocumentos();
        });

        </script>


</body>

</html>
