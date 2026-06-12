<?php

include_once "../../assest/config/validarUsuarioOpera.php";
include_once "../../assest/config/BDCONFIG.php";

$COLUMNAS_DESPACHOS = [
    'TIPO_REPORTE' => 'Tipo',
    'FOLIO' => 'N Folio',
    'FECHA_DETALLE' => 'Fecha Detalle',
    'TIPO_PRODUCTO' => 'Tipo Producto',
    'CODIGO_ESTANDAR' => 'Codigo Estandar',
    'NOMBRE_ESTANDAR' => 'Envase/Estandar',
    'CSG' => 'CSG',
    'PRODUCTOR' => 'Productor',
    'ESPECIE' => 'Especies',
    'VARIEDAD' => 'Variedad',
    'ENVASE' => 'Cantidad Envase',
    'NETO' => 'Kilo Neto',
    'PORCENTAJE_DESHIDRATACION' => '% Deshidratacion',
    'KILOS_DESHIDRATACION' => 'Kilo Deshidratacion',
    'BRUTO' => 'Kilo Bruto',
    'NUMERO_DESPACHO' => 'Numero Despacho',
    'FECHA_DESPACHO' => 'Fecha Despacho',
    'NUMERO_GUIA_DESPACHO' => 'Numero Guia Despacho',
    'TIPO_DESPACHO' => 'Tipo Despacho',
    'DESTINO' => 'Destino',
    'TIPO_MANEJO' => 'Tipo Manejo',
    'TIPO_CALIBRE' => 'Tipo Calibre',
    'TIPO_EMBALAJE' => 'Tipo Embalaje',
    'STOCK' => 'Stock',
    'EMBOLSADO' => 'Embolsado',
    'GASIFICACION' => 'Gasificacion',
    'PREFRIO' => 'Prefrio',
    'TRANSPORTE' => 'Transporte',
    'NOMBRE_CONDUCTOR' => 'Nombre Conductor',
    'PATENTE_CAMION' => 'Patente Camion',
    'PATENTE_CARRO' => 'Patente Carro',
    'SEMANA_DESPACHO' => 'Semana Despacho',
    'PRECIO_UNITARIO' => 'Precio Unitario',
    'VALOR_VENTA' => 'Valor Venta',
    'TOTAL_PRECIO_DESPACHO' => 'Total Precio Despacho',
    'FOB_REFERENCIA_CAJA' => 'FOB Referencia Caja',
    'FOB_REFERENCIA_KG' => 'FOB Referencia Kg',
    'FOB_VENTA_CAJA' => 'FOB Venta Caja',
    'FOB_VENTA_KG' => 'FOB Venta Kg',
    'VENTA_USD' => 'Venta USD',
    'VENTA_USD_NETO' => 'Venta USD Neto',
    'RETORNO_NETO' => 'Retorno Neto',
    'FOB_FINAL_KG' => 'FOB Final Kg',
    'FOB_FINAL_CAJA' => 'FOB Final Caja',
    'NUMERO_VALOR' => 'Numero Valor',
    'FECHA_VALOR' => 'Fecha Valor',
    'NUMERO_INVOICE' => 'Numero Invoice',
    'PRECIO_CAJA_INVOICE' => 'Precio Caja Invoice',
    'TOTAL_LINEA_INVOICE' => 'Total Linea Invoice',
    'NREFERENCIA_ICARGA' => 'Referencia Carga',
    'CLIENTE' => 'Cliente',
    'MERCADO' => 'Mercado',
    'CONTENEDOR' => 'Contenedor',
    'BL_AWB' => 'BL/AWB',
    'RECIBIDOR_FINAL' => 'Recibidor Final',
    'TIPO_EMBARQUE' => 'Tipo Embarque',
    'NAVE' => 'Nave',
    'NUMERO_VIAJE' => 'Viaje/Vuelo',
    'FECHA_ETD' => 'ETD',
    'FECHA_ETA' => 'ETA',
    'FECHA_REAL_ETD' => 'ETD Real',
    'FECHA_REAL_ETA' => 'ETA Real',
    'TERMOGRAFO_DESPACHO' => 'Termografo Despacho',
    'TERMOGRAFO_PALLET' => 'Termografo Pallet',
    'EMPRESA' => 'Empresa',
    'PLANTA' => 'Planta',
    'TEMPORADA' => 'Temporada',
];

$h = function ($valor) {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$parseNumero = function ($valor) {
    $valor = trim((string) $valor);
    return is_numeric($valor) ? (float) $valor : 0.0;
};

$ARRAYDESPACHOS = [];
$TOTALENVASE = 0;
$TOTALNETO = 0;
$TOTALBRUTO = 0;
$TOTALVENTA = 0;

if ($TEMPORADAS) {
    $conexion = BDCONFIG::conectar();
    if ($conexion) {
        $sql = "SELECT " . implode(', ', array_keys($COLUMNAS_DESPACHOS)) . "
                FROM view_despachos
                WHERE ID_TEMPORADA = :temporada
                  AND ID_EMPRESA <> 5";
        $params = [':temporada' => $TEMPORADAS];

        if (!empty($ESPECIE)) {
            $sql .= " AND ID_ESPECIES = :especie";
            $params[':especie'] = $ESPECIE;
        }

        $sql .= " ORDER BY FECHA_DESPACHO DESC, NUMERO_DESPACHO DESC, ID_DETALLE DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute($params);
        $ARRAYDESPACHOS = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        $conexion = null;
    }
}

foreach ($ARRAYDESPACHOS as $filaTotal) {
    $TOTALENVASE += $parseNumero($filaTotal['ENVASE'] ?? 0);
    $TOTALNETO += $parseNumero($filaTotal['NETO'] ?? 0);
    $TOTALBRUTO += $parseNumero($filaTotal['BRUTO'] ?? 0);
    $TOTALVENTA += $parseNumero($filaTotal['VALOR_VENTA'] ?? 0);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Detallado Despachos</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
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
                                        <li class="breadcrumb-item" aria-current="page">Modulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Detallado</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Despachos</a></li>
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
                                        <table id="detalladodespachos" class="table table-bordered table-hover table-striped" style="width:100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <?php foreach ($COLUMNAS_DESPACHOS as $columna) : ?>
                                                        <th><?php echo $h($columna); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYDESPACHOS as $r) : ?>
                                                    <tr class="text-center">
                                                        <?php foreach (array_keys($COLUMNAS_DESPACHOS) as $campo) : ?>
                                                            <td><?php echo $h($r[$campo] ?? ''); ?></td>
                                                        <?php endforeach; ?>
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
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Valor Venta</div>
                                                    <button class="btn btn-default" id="TOTALVENTAV" name="TOTALVENTAV"><?php echo number_format($TOTALVENTA, 2, ',', '.'); ?></button>
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
            $('#detalladodespachos').DataTable({
                drawCallback: function() {
                    var api = this.api();
                    $('#TOTALENVASEV').text(new Intl.NumberFormat('de-DE').format(parseFloat(api.column(10, { search: 'applied' }).data().sum()).toFixed(0)));
                    $('#TOTALNETOV').text(new Intl.NumberFormat('de-DE').format(parseFloat(api.column(11, { search: 'applied' }).data().sum()).toFixed(2)));
                    $('#TOTALBRUTOV').text(new Intl.NumberFormat('de-DE').format(parseFloat(api.column(14, { search: 'applied' }).data().sum()).toFixed(2)));
                    $('#TOTALVENTAV').text(new Intl.NumberFormat('de-DE').format(parseFloat(api.column(33, { search: 'applied' }).data().sum()).toFixed(2)));
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
                order: [[16, 'desc']],
                pagingType: 'full_numbers',
                language: {
                    processing: 'Procesando...',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    zeroRecords: 'No se encontraron resultados',
                    emptyTable: 'Ningun dato disponible en esta tabla',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
                    infoFiltered: '(filtrado de un total de _MAX_ registros)',
                    search: 'Buscar:',
                    paginate: {
                        first: 'Primero',
                        last: 'Ultimo',
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

                        if (vals.length <= 35) {
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
