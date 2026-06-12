<?php

include_once "../../assest/config/validarUsuarioOpera.php";
include_once "../../assest/config/BDCONFIG.php";

$COLUMNAS_RECEPCIONES = [
    'Tipo',
    'N° Folio',
    'Fecha Detalle',
    'Código Estandar',
    'Envase/Estandar',
    'CSG',
    'Productor',
    'Especies',
    'Variedad',
    'Cantidad Envase',
    'Kilo Neto',
    '% Deshidratación',
    'Kilo Deshidratación',
    'Kilo Bruto',
    'Número Recepción',
    'Fecha Recepción',
    'Tipo Recepción',
    'Origen Recepción',
    'Número Guía Recepción',
    'Fecha Guía Recepción',
    'Tipo Manejo',
    'Tipo Calibre',
    'Tipo Embalaje',
    'Stock',
    'Embolsado',
    'Gasificación',
    'Prefrío',
    'Transporte',
    'Nombre Conductor',
    'Patente Camión',
    'Patente Carro',
    'Semana Recepción',
    'Semana Guía',
    'Empresa',
    'Planta',
    'Temporada',
];

$h = function ($valor) {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$parseNumero = function ($valor) {
    $valor = trim((string) $valor);
    return is_numeric($valor) ? (float) $valor : 0.0;
};

$ARRAYRECEPCIONES = [];
$TOTALENVASE = 0;
$TOTALNETO = 0;
$TOTALBRUTO = 0;

if ($TEMPORADAS) {
    $conexion = BDCONFIG::conectar();
    if ($conexion) {
        $sql = "SELECT
                    TIPO_REPORTE,
                    FOLIO,
                    FECHA_DETALLE,
                    CODIGO_ESTANDAR,
                    NOMBRE_ESTANDAR,
                    CSG,
                    PRODUCTOR,
                    ESPECIE,
                    VARIEDAD,
                    ENVASE,
                    NETO,
                    PORCENTAJE_DESHIDRATACION,
                    KILOS_DESHIDRATACION,
                    BRUTO,
                    NUMERO_RECEPCION,
                    FECHA_RECEPCION,
                    TIPO_RECEPCION,
                    ORIGEN_RECEPCION,
                    NUMERO_GUIA_RECEPCION,
                    FECHA_GUIA_RECEPCION,
                    TIPO_MANEJO,
                    TIPO_CALIBRE,
                    TIPO_EMBALAJE,
                    STOCK,
                    EMBOLSADO,
                    GASIFICACION,
                    PREFRIO,
                    TRANSPORTE,
                    NOMBRE_CONDUCTOR,
                    PATENTE_CAMION,
                    PATENTE_CARRO,
                    SEMANA,
                    SEMANAGUIA,
                    EMPRESA,
                    PLANTA,
                    TEMPORADA
                FROM view_recepciones
                WHERE ID_TEMPORADA = :temporada
                  AND ID_EMPRESA <> 5";
        $params = [':temporada' => $TEMPORADAS];

        if (!empty($ESPECIE)) {
            $sql .= " AND ID_ESPECIES = :especie";
            $params[':especie'] = $ESPECIE;
        }

        $sql .= " ORDER BY FECHA_RECEPCION DESC, NUMERO_RECEPCION DESC, ID_DRECEPCION DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $ARRAYRECEPCIONES = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        $conexion = null;
    }
}

foreach ($ARRAYRECEPCIONES as $filaTotal) {
    $TOTALENVASE += $parseNumero($filaTotal['ENVASE'] ?? 0);
    $TOTALNETO += $parseNumero($filaTotal['NETO'] ?? 0);
    $TOTALBRUTO += $parseNumero($filaTotal['BRUTO'] ?? 0);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Detallado Recepciones</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <script type="text/javascript">
        function abrirPestana(url) {
            var win = window.open(url, '_blank');
            win.focus();
        }
    </script>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuOpera.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Detallado</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Detallado</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Recepciones</a></li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>

                <section class="content">
                    <div class="box">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table id="detalladorecepciones" class="table table-bordered table-hover table-striped" style="width:100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <?php foreach ($COLUMNAS_RECEPCIONES as $columna) : ?>
                                                        <th><?php echo $h($columna); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYRECEPCIONES as $r) : ?>
                                                    <tr class="text-center">
                                                        <td><?php echo $h($r['TIPO_REPORTE']); ?></td>
                                                        <td><?php echo $h($r['FOLIO']); ?></td>
                                                        <td><?php echo $h($r['FECHA_DETALLE']); ?></td>
                                                        <td><?php echo $h($r['CODIGO_ESTANDAR']); ?></td>
                                                        <td><?php echo $h($r['NOMBRE_ESTANDAR']); ?></td>
                                                        <td><?php echo $h($r['CSG']); ?></td>
                                                        <td><?php echo $h($r['PRODUCTOR']); ?></td>
                                                        <td><?php echo $h($r['ESPECIE']); ?></td>
                                                        <td><?php echo $h($r['VARIEDAD']); ?></td>
                                                        <td><?php echo $h($r['ENVASE']); ?></td>
                                                        <td><?php echo $h($r['NETO']); ?></td>
                                                        <td><?php echo $h($r['PORCENTAJE_DESHIDRATACION']); ?></td>
                                                        <td><?php echo $h($r['KILOS_DESHIDRATACION']); ?></td>
                                                        <td><?php echo $h($r['BRUTO']); ?></td>
                                                        <td><?php echo $h($r['NUMERO_RECEPCION']); ?></td>
                                                        <td><?php echo $h($r['FECHA_RECEPCION']); ?></td>
                                                        <td><?php echo $h($r['TIPO_RECEPCION']); ?></td>
                                                        <td><?php echo $h($r['ORIGEN_RECEPCION']); ?></td>
                                                        <td><?php echo $h($r['NUMERO_GUIA_RECEPCION']); ?></td>
                                                        <td><?php echo $h($r['FECHA_GUIA_RECEPCION']); ?></td>
                                                        <td><?php echo $h($r['TIPO_MANEJO']); ?></td>
                                                        <td><?php echo $h($r['TIPO_CALIBRE']); ?></td>
                                                        <td><?php echo $h($r['TIPO_EMBALAJE']); ?></td>
                                                        <td><?php echo $h($r['STOCK']); ?></td>
                                                        <td><?php echo $h($r['EMBOLSADO']); ?></td>
                                                        <td><?php echo $h($r['GASIFICACION']); ?></td>
                                                        <td><?php echo $h($r['PREFRIO']); ?></td>
                                                        <td><?php echo $h($r['TRANSPORTE']); ?></td>
                                                        <td><?php echo $h($r['NOMBRE_CONDUCTOR']); ?></td>
                                                        <td><?php echo $h($r['PATENTE_CAMION']); ?></td>
                                                        <td><?php echo $h($r['PATENTE_CARRO']); ?></td>
                                                        <td><?php echo $h($r['SEMANA']); ?></td>
                                                        <td><?php echo $h($r['SEMANAGUIA']); ?></td>
                                                        <td><?php echo $h($r['EMPRESA']); ?></td>
                                                        <td><?php echo $h($r['PLANTA']); ?></td>
                                                        <td><?php echo $h($r['TEMPORADA']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="box-footer">
                                <div class="btn-toolbar mb-3" role="toolbar" aria-label="Datos generales">
                                    <div class="form-row align-items-center" role="group" aria-label="Datos">
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Envase</div>
                                                    <button class="btn btn-default" id="TOTALENVASEV" name="TOTALENVASEV"><?php echo number_format($TOTALENVASE, 0, ',', '.'); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Neto</div>
                                                    <button class="btn btn-default" id="TOTALNETOV" name="TOTALNETOV"><?php echo number_format($TOTALNETO, 2, ',', '.'); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Bruto</div>
                                                    <button class="btn btn-default" id="TOTALBRUTOV" name="TOTALBRUTOV"><?php echo number_format($TOTALBRUTO, 2, ',', '.'); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <?php include_once "../../assest/config/footer.php"; ?>
        <?php include_once "../../assest/config/menuExtraOpera.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>
    <script>
        $(function() {
            $('#detalladorecepciones').DataTable({
                drawCallback: function() {
                    var api = this.api();
                    $('#TOTALENVASEV').text(new Intl.NumberFormat('de-DE').format(parseFloat(api.column(9, { search: 'applied' }).data().sum()).toFixed(0)));
                    $('#TOTALNETOV').text(new Intl.NumberFormat('de-DE').format(parseFloat(api.column(10, { search: 'applied' }).data().sum()).toFixed(2)));
                    $('#TOTALBRUTOV').text(new Intl.NumberFormat('de-DE').format(parseFloat(api.column(13, { search: 'applied' }).data().sum()).toFixed(2)));
                },
                scrollX: true,
                paging: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], ['10', '25', '50', 'Todos']],
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: false,
                order: [[15, 'desc']],
                pagingType: 'full_numbers',
                language: {
                    processing: 'Procesando...',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    zeroRecords: 'No se encontraron resultados',
                    emptyTable: 'Ningún dato disponible en esta tabla',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
                    infoFiltered: '(filtrado de un total de _MAX_ registros)',
                    search: 'Buscar:',
                    paginate: {
                        first: 'Primero',
                        last: 'Último',
                        next: 'Siguiente',
                        previous: 'Anterior'
                    },
                    buttons: {
                        excel: 'Excel'
                    }
                },
                initComplete: function() {
                    var api = this.api();
                    api.columns().every(function() {
                        var col = this;
                        var header = $(col.header());
                        var vals = [];

                        col.data().unique().sort().each(function(d) {
                            var text = $('<span>').html(d).text().trim();
                            if (text) vals.push(text);
                        });

                        if (vals.length <= 25) {
                            var sel = $('<br><select class="form-control form-control-sm dt-col-filter" style="font-size:11px;"><option value="">Todos</option></select>')
                                .appendTo(header)
                                .on('change', function() {
                                    var v = $.fn.dataTable.util.escapeRegex($(this).val());
                                    col.search(v ? '^' + v + '$' : '', true, false).draw();
                                });

                            $.each(vals, function(i, v) {
                                sel.append($('<option>', { value: v, text: v }));
                            });
                        }
                    });

                    api.on('draw.dt', function() {
                        api.columns().every(function() {
                            var col = this;
                            var idx = col.index();
                            var sel = $(col.header()).find('select.dt-col-filter');
                            if (!sel.length || sel.val()) return;

                            var vals = [];
                            api.column(idx, { search: 'applied' }).data().unique().sort().each(function(d) {
                                var text = $('<span>').html(d).text().trim();
                                if (text) vals.push(text);
                            });

                            sel.find('option:not(:first)').remove();
                            $.each(vals, function(i, v) {
                                sel.append($('<option>', { value: v, text: v }));
                            });
                        });
                    });
                },
                buttons: [
                    {
                        extend: 'excel',
                        exportOptions: {
                            modifier: { search: 'applied', order: 'applied' }
                        }
                    }
                ],
                dom: 'Blrtip'
            });
        });
    </script>
</body>

</html>
