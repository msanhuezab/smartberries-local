<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$registros = [];
if ($EMPRESAS && $TEMPORADAS) {
    $stmt = $db->prepare("
        SELECT val.ID_VALOR, val.NUMERO_VALOR, val.ESTADO_LIQUIDACION,
               val.ID_ICARGA, i.NREFERENCIA_ICARGA, i.NCONTENEDOR_ICARGA
        FROM liquidacion_valor val
        INNER JOIN fruta_icarga i ON i.ID_ICARGA = val.ID_ICARGA
        WHERE val.ID_EMPRESA = ?
          AND val.ID_TEMPORADA = ?
          AND val.ESTADO_LIQUIDACION = 'LIQUIDADA'
          AND val.ESTADO_REGISTRO = 1
        ORDER BY val.NUMERO_VALOR DESC
    ");
    $stmt->execute([$EMPRESAS, $TEMPORADAS]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Apertura Liquidaciones</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
</head>
<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
<div class="wrapper">
    <?php include_once "../../assest/config/menuExpo.php"; ?>
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="mr-auto">
                        <h3 class="page-title">Apertura Registro</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item">Apertura Registro</li>
                                    <li class="breadcrumb-item active">Liquidaciones</li>
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
                        <h4 class="box-title">Liquidaciones cerradas — solicitar reapertura</h4>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-hover" id="apLiqTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>N° Liquidación</th>
                                    <th>Referencia</th>
                                    <th>Contenedor</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($registros as $r):
                                $urlAP = 'registroAPExportadora.php?MODULO=LIQUIDACION'
                                       . '&ID=' . (int)$r['ID_VALOR']
                                       . '&ICARGA=' . (int)$r['ID_ICARGA']
                                       . '&NUMERO=' . urlencode('Liq. ' . $r['NUMERO_VALOR'] . ' - ' . $r['NREFERENCIA_ICARGA'])
                                       . '&RETORNO=registroLiquidacionExp';
                            ?>
                                <tr>
                                    <td><?php echo h($r['NUMERO_VALOR']); ?></td>
                                    <td><strong><?php echo h($r['NREFERENCIA_ICARGA']); ?></strong></td>
                                    <td><?php echo h($r['NCONTENEDOR_ICARGA']); ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?php echo h($r['ESTADO_LIQUIDACION']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo h($urlAP); ?>" class="btn btn-warning">
                                            <i class="fa fa-folder-open"></i> Solicitar reapertura
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>
<script>
$('#apLiqTable').DataTable({
    pageLength: 10,
    order: [[0, 'desc']],
    columnDefs: [{ orderable: false, targets: 4 }],
    language: {
        search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
        info: '_START_–_END_ de _TOTAL_', paginate: { previous: '‹', next: '›' },
        zeroRecords: 'No hay liquidaciones cerradas'
    }
});
</script>
</body>
</html>
