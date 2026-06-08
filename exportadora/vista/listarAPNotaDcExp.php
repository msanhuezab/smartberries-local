<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$registros = [];
if ($EMPRESAS && $TEMPORADAS) {
    $stmt = $db->prepare("
        SELECT n.ID_NOTA, n.NUMERO_NOTA, n.TNOTA, n.OBSERVACIONES,
               n.ID_ICARGA, i.NREFERENCIA_ICARGA
        FROM fruta_notadc n
        LEFT JOIN fruta_icarga i ON i.ID_ICARGA = n.ID_ICARGA
        WHERE n.ID_EMPRESA = ?
          AND n.ID_TEMPORADA = ?
          AND n.ESTADO = 0
          AND n.ESTADO_REGISTRO = 1
        ORDER BY n.NUMERO_NOTA DESC
    ");
    $stmt->execute([$EMPRESAS, $TEMPORADAS]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$tipoNota = ['1' => 'Débito', '2' => 'Crédito'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Apertura Notas D/C</title>
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
                                    <li class="breadcrumb-item active">Notas D/C</li>
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
                        <h4 class="box-title">Notas D/C cerradas — solicitar reapertura</h4>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-hover" id="apNotaTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>N° Nota</th>
                                    <th>Tipo</th>
                                    <th>Referencia</th>
                                    <th>Observación</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($registros as $r):
                                $tipo = $tipoNota[$r['TNOTA']] ?? 'Sin datos';
                                $urlAP = 'registroAPExportadora.php?MODULO=NOTADC'
                                       . '&ID=' . (int)$r['ID_NOTA']
                                       . '&NUMERO=' . urlencode('Nota ' . $r['NUMERO_NOTA'] . ' - ' . $r['NREFERENCIA_ICARGA'])
                                       . '&RETORNO=listarAPNotaDcExp';
                            ?>
                                <tr>
                                    <td><?php echo h($r['NUMERO_NOTA']); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $r['TNOTA'] == 1 ? 'badge-warning' : 'badge-info'; ?>">
                                            <?php echo $tipo; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo h($r['NREFERENCIA_ICARGA']); ?></strong></td>
                                    <td><?php echo h($r['OBSERVACIONES']); ?></td>
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
$('#apNotaTable').DataTable({
    pageLength: 25,
    order: [[0, 'desc']],
    columnDefs: [{ orderable: false, targets: 4 }],
    language: {
        search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
        info: '_START_–_END_ de _TOTAL_', paginate: { previous: '‹', next: '›' },
        zeroRecords: 'No hay notas D/C cerradas'
    }
});
</script>
</body>
</html>
