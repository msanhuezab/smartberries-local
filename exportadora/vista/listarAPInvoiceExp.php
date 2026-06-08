<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$registros = [];
if ($EMPRESAS && $TEMPORADAS) {
    $stmt = $db->prepare("
        SELECT inv.ID_INVOICE, inv.NUMERO_INVOICE, inv.ESTADO_INVOICE,
               inv.ID_ICARGA, i.NREFERENCIA_ICARGA, i.NCONTENEDOR_ICARGA
        FROM exportadora_invoice inv
        INNER JOIN fruta_icarga i ON i.ID_ICARGA = inv.ID_ICARGA
        WHERE inv.ID_EMPRESA = ?
          AND inv.ID_TEMPORADA = ?
          AND inv.ESTADO_INVOICE = 'CONFIRMADA'
          AND inv.ESTADO_REGISTRO = 1
        ORDER BY inv.NUMERO_INVOICE DESC
    ");
    $stmt->execute([$EMPRESAS, $TEMPORADAS]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Apertura Invoices</title>
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
                                    <li class="breadcrumb-item active">Invoice</li>
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
                        <h4 class="box-title">Invoices confirmadas — solicitar reapertura</h4>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-hover" id="apInvoiceTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>N° Invoice</th>
                                    <th>Referencia</th>
                                    <th>Contenedor</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($registros as $r):
                                $urlAP = 'registroAPExportadora.php?MODULO=INVOICE'
                                       . '&ID=' . (int)$r['ID_INVOICE']
                                       . '&ICARGA=' . (int)$r['ID_ICARGA']
                                       . '&NUMERO=' . urlencode('Invoice ' . $r['NUMERO_INVOICE'] . ' - ' . $r['NREFERENCIA_ICARGA'])
                                       . '&RETORNO=registroInvoiceExp';
                            ?>
                                <tr>
                                    <td><?php echo h($r['NUMERO_INVOICE']); ?></td>
                                    <td><strong><?php echo h($r['NREFERENCIA_ICARGA']); ?></strong></td>
                                    <td><?php echo h($r['NCONTENEDOR_ICARGA']); ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?php echo h($r['ESTADO_INVOICE']); ?></span>
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
$('#apInvoiceTable').DataTable({
    pageLength: 10,
    order: [[0, 'desc']],
    columnDefs: [{ orderable: false, targets: 4 }],
    language: {
        search: 'Buscar:', lengthMenu: 'Mostrar _MENU_ registros',
        info: '_START_–_END_ de _TOTAL_', paginate: { previous: '‹', next: '›' },
        zeroRecords: 'No hay invoices confirmadas'
    }
});
</script>
</body>
</html>
