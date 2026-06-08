<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$registros = [];
if ($EMPRESAS && $TEMPORADAS) {
    $stmt = $db->prepare("
        SELECT i.ID_ICARGA, i.NUMERO_ICARGA, i.NREFERENCIA_ICARGA, i.NCONTENEDOR_ICARGA,
               i.ESTADO, i.ESTADO_ICARGA, i.FECHA_ICARGA AS FECHA
        FROM fruta_icarga i
        WHERE i.ID_EMPRESA = ?
          AND i.ID_TEMPORADA = ?
          AND i.ESTADO = 0
          AND i.ESTADO_REGISTRO = 1
        ORDER BY i.NUMERO_ICARGA DESC
    ");
    $stmt->execute([$EMPRESAS, $TEMPORADAS]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$estadoLabels = ['1' => 'Creado', '2' => 'Por Cargar', '3' => 'Cargado', '4' => 'Arrivado', '5' => 'Cancelado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Apertura Instructivos</title>
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
                                    <li class="breadcrumb-item active">Instructivos</li>
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
                        <h4 class="box-title">Instructivos cerrados — solicitar reapertura</h4>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-hover" id="apIcargaTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Referencia</th>
                                    <th>Contenedor</th>
                                    <th>Estado Instructivo</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($registros as $r):
                                $urlAP = 'registroAPExportadora.php?MODULO=ICARGA'
                                       . '&ID=' . (int)$r['ID_ICARGA']
                                       . '&NUMERO=' . urlencode($r['NREFERENCIA_ICARGA'])
                                       . '&RETORNO=listarAPICargaExp';
                            ?>
                                <tr>
                                    <td><?php echo h($r['NUMERO_ICARGA']); ?></td>
                                    <td><strong><?php echo h($r['NREFERENCIA_ICARGA']); ?></strong></td>
                                    <td><?php echo h($r['NCONTENEDOR_ICARGA']); ?></td>
                                    <td class="text-center">
                                        <?php echo h($estadoLabels[$r['ESTADO_ICARGA']] ?? $r['ESTADO_ICARGA']); ?>
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
$('#apIcargaTable').DataTable({
    pageLength: 25,
    order: [[0, 'desc']],
    columnDefs: [{ orderable: false, targets: 4 }],
    language: {
        search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
        info: '_START_–_END_ de _TOTAL_', paginate: { previous: '‹', next: '›' },
        zeroRecords: 'No hay instructivos cerrados'
    }
});
</script>
</body>
</html>
