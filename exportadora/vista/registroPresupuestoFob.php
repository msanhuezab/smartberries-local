<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();
$mensaje = '';
$mensajeOk = '';
$ID = isset($_REQUEST['ID']) ? (int)$_REQUEST['ID'] : 0;
$OP = $_GET['a'] ?? '';
$DISABLED = $OP === 'ver' ? 'disabled' : '';

function h($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function listarCbx($db, $sql, $params = [])
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function valorPost($nombre, $default = '')
{
    return $_POST[$nombre] ?? $default;
}

function normalizarDecimal($valor)
{
    return (float)str_replace(',', '.', (string)$valor);
}

$form = [
    'ID_TEMPORADA' => $TEMPORADAS,
    'ID_MERCADO' => '',
    'ID_CONSIGNATARIO' => '',
    'ID_ESTANDAR' => '',
    'ID_TCALIBRE' => '',
    'ID_TMONEDA' => '',
    'ID_CVENTA' => '',
    'ID_MVENTA' => '',
    'ID_TFLETE' => '',
    'PESO_CAJA' => '',
    'FOB_CAJA' => '',
    'MOTIVO_AJUSTE' => '',
];

if ($ID > 0) {
    $stmt = $db->prepare("SELECT * FROM exportadora_presupuesto_fob WHERE ID_PRESUPUESTO_FOB = ? AND ID_EMPRESA = ? LIMIT 1");
    $stmt->execute([$ID, $EMPRESAS]);
    $actual = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($actual) {
        $form = array_merge($form, $actual);
    }
}

if (isset($_POST['CREAR']) || isset($_POST['GUARDAR'])) {
    foreach ($form as $campo => $valor) {
        if (array_key_exists($campo, $_POST)) {
            $form[$campo] = $_POST[$campo];
        }
    }

    $requeridos = ['ID_TEMPORADA', 'ID_MERCADO', 'ID_CONSIGNATARIO', 'ID_ESTANDAR', 'ID_TCALIBRE', 'ID_TMONEDA', 'ID_CVENTA', 'ID_MVENTA', 'ID_TFLETE', 'FOB_CAJA'];
    foreach ($requeridos as $campo) {
        if (trim((string)$form[$campo]) === '') {
            $mensaje = 'Debe completar temporada, mercado, cliente, producto, moneda, condicion comercial y FOB caja.';
            break;
        }
    }

    if ($mensaje === '') {
        $pesoCaja = normalizarDecimal($form['PESO_CAJA']);
        $fobCaja = normalizarDecimal($form['FOB_CAJA']);
        if ($fobCaja <= 0) {
            $mensaje = 'El FOB caja debe ser mayor a cero.';
        }
    }

    if ($mensaje === '') {
        if (isset($_POST['CREAR'])) {
            $stmtExiste = $db->prepare("
                SELECT ID_PRESUPUESTO_FOB
                FROM exportadora_presupuesto_fob
                WHERE ID_EMPRESA = ?
                AND ID_TEMPORADA = ?
                AND ID_MERCADO = ?
                AND ID_CONSIGNATARIO = ?
                AND ID_ESTANDAR = ?
                AND ID_TCALIBRE = ?
                AND ESTADO_REGISTRO = 1
                LIMIT 1
            ");
            $stmtExiste->execute([$EMPRESAS, $form['ID_TEMPORADA'], $form['ID_MERCADO'], $form['ID_CONSIGNATARIO'], $form['ID_ESTANDAR'], $form['ID_TCALIBRE']]);
            if ($stmtExiste->fetchColumn()) {
                $mensaje = 'Ya existe un presupuesto vigente para esa combinacion. Edite el registro existente para mantener historial.';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO exportadora_presupuesto_fob
                        (ID_TEMPORADA, ID_MERCADO, ID_CONSIGNATARIO, ID_ESTANDAR, ID_TCALIBRE, ID_TMONEDA, ID_CVENTA, ID_MVENTA, ID_TFLETE, PESO_CAJA, FOB_CAJA, MOTIVO_AJUSTE, ESTADO_REGISTRO, INGRESO, MODIFICACION, ID_EMPRESA, ID_USUARIOI, ID_USUARIOM)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, SYSDATE(), SYSDATE(), ?, ?, ?)
                ");
                $stmt->execute([
                    $form['ID_TEMPORADA'], $form['ID_MERCADO'], $form['ID_CONSIGNATARIO'], $form['ID_ESTANDAR'], $form['ID_TCALIBRE'],
                    $form['ID_TMONEDA'], $form['ID_CVENTA'], $form['ID_MVENTA'], $form['ID_TFLETE'], $pesoCaja, $fobCaja,
                    $form['MOTIVO_AJUSTE'], $EMPRESAS, $IDUSUARIOS, $IDUSUARIOS
                ]);
                $mensajeOk = 'Presupuesto FOB creado correctamente.';
                $ID = (int)$db->lastInsertId();
                $form['ID_PRESUPUESTO_FOB'] = $ID;
            }
        } elseif ($ID > 0) {
            $stmtActual = $db->prepare("SELECT * FROM exportadora_presupuesto_fob WHERE ID_PRESUPUESTO_FOB = ? AND ID_EMPRESA = ? LIMIT 1");
            $stmtActual->execute([$ID, $EMPRESAS]);
            $anterior = $stmtActual->fetch(PDO::FETCH_ASSOC);
            if ($anterior) {
                $stmt = $db->prepare("
                    UPDATE exportadora_presupuesto_fob
                    SET ID_TEMPORADA = ?, ID_MERCADO = ?, ID_CONSIGNATARIO = ?, ID_ESTANDAR = ?, ID_TCALIBRE = ?,
                        ID_TMONEDA = ?, ID_CVENTA = ?, ID_MVENTA = ?, ID_TFLETE = ?, PESO_CAJA = ?, FOB_CAJA = ?,
                        MOTIVO_AJUSTE = ?, ID_USUARIOM = ?, MODIFICACION = SYSDATE()
                    WHERE ID_PRESUPUESTO_FOB = ?
                    AND ID_EMPRESA = ?
                ");
                $stmt->execute([
                    $form['ID_TEMPORADA'], $form['ID_MERCADO'], $form['ID_CONSIGNATARIO'], $form['ID_ESTANDAR'], $form['ID_TCALIBRE'],
                    $form['ID_TMONEDA'], $form['ID_CVENTA'], $form['ID_MVENTA'], $form['ID_TFLETE'], $pesoCaja, $fobCaja,
                    $form['MOTIVO_AJUSTE'], $IDUSUARIOS, $ID, $EMPRESAS
                ]);

                $stmtHist = $db->prepare("
                    INSERT INTO exportadora_presupuesto_fob_historial
                        (ID_PRESUPUESTO_FOB, FOB_CAJA_ANTERIOR, FOB_CAJA_NUEVO, PESO_CAJA_ANTERIOR, PESO_CAJA_NUEVO,
                         ID_TMONEDA_ANTERIOR, ID_TMONEDA_NUEVO, ID_CVENTA_ANTERIOR, ID_CVENTA_NUEVO, ID_MVENTA_ANTERIOR, ID_MVENTA_NUEVO,
                         ID_TFLETE_ANTERIOR, ID_TFLETE_NUEVO, MOTIVO_AJUSTE, INGRESO, ID_USUARIO)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, SYSDATE(), ?)
                ");
                $stmtHist->execute([
                    $ID, $anterior['FOB_CAJA'], $fobCaja, $anterior['PESO_CAJA'], $pesoCaja,
                    $anterior['ID_TMONEDA'], $form['ID_TMONEDA'], $anterior['ID_CVENTA'], $form['ID_CVENTA'],
                    $anterior['ID_MVENTA'], $form['ID_MVENTA'], $anterior['ID_TFLETE'], $form['ID_TFLETE'],
                    $form['MOTIVO_AJUSTE'], $IDUSUARIOS
                ]);
                $mensajeOk = 'Presupuesto FOB actualizado correctamente.';
            }
        }
    }
}

if ($ID > 0 && isset($_POST['DESACTIVAR'])) {
    $db->prepare("UPDATE exportadora_presupuesto_fob SET ESTADO_REGISTRO = 0, ID_USUARIOM = ?, MODIFICACION = SYSDATE() WHERE ID_PRESUPUESTO_FOB = ? AND ID_EMPRESA = ?")
        ->execute([$IDUSUARIOS, $ID, $EMPRESAS]);
    $mensajeOk = 'Presupuesto FOB desactivado.';
}

$TEMPORADAS_LIST = listarCbx($db, "SELECT ID_TEMPORADA, NOMBRE_TEMPORADA FROM principal_temporada WHERE ESTADO_REGISTRO = 1 ORDER BY NOMBRE_TEMPORADA");
$MERCADOS = listarCbx($db, "SELECT ID_MERCADO, NOMBRE_MERCADO FROM fruta_mercado WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY NOMBRE_MERCADO", [$EMPRESAS]);
$CONSIGNATARIOS = listarCbx($db, "SELECT ID_CONSIGNATARIO, NOMBRE_CONSIGNATARIO FROM fruta_consignatario WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY NOMBRE_CONSIGNATARIO", [$EMPRESAS]);
$ESTANDARES = listarCbx($db, "SELECT ID_ESTANDAR, CONCAT(IFNULL(CODIGO_ESTANDAR,''), ' ', IFNULL(NOMBRE_ESTANDAR,'')) AS NOMBRE_ESTANDAR FROM estandar_eexportacion WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY CODIGO_ESTANDAR, NOMBRE_ESTANDAR", [$EMPRESAS]);
$CALIBRES = listarCbx($db, "SELECT ID_TCALIBRE, NOMBRE_TCALIBRE FROM fruta_tcalibre WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY ORDEN, NOMBRE_TCALIBRE", [$EMPRESAS]);
$MONEDAS = listarCbx($db, "SELECT ID_TMONEDA, NOMBRE_TMONEDA FROM fruta_tmoneda WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY NOMBRE_TMONEDA", [$EMPRESAS]);
$CVENTAS = listarCbx($db, "SELECT ID_CVENTA, NOMBRE_CVENTA FROM fruta_cventa WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY NOMBRE_CVENTA", [$EMPRESAS]);
$MVENTAS = listarCbx($db, "SELECT ID_MVENTA, NOMBRE_MVENTA FROM fruta_mventa WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY NOMBRE_MVENTA", [$EMPRESAS]);
$TFLETES = listarCbx($db, "SELECT ID_TFLETE, NOMBRE_TFLETE FROM fruta_tflete WHERE ID_EMPRESA = ? AND ESTADO_REGISTRO = 1 ORDER BY NOMBRE_TFLETE", [$EMPRESAS]);

$stmtLista = $db->prepare("
    SELECT p.*, te.NOMBRE_TEMPORADA, me.NOMBRE_MERCADO, co.NOMBRE_CONSIGNATARIO,
           es.CODIGO_ESTANDAR, es.NOMBRE_ESTANDAR, ca.NOMBRE_TCALIBRE,
           tm.NOMBRE_TMONEDA, cv.NOMBRE_CVENTA, mv.NOMBRE_MVENTA, tf.NOMBRE_TFLETE
    FROM exportadora_presupuesto_fob p
    LEFT JOIN principal_temporada te ON te.ID_TEMPORADA = p.ID_TEMPORADA
    LEFT JOIN fruta_mercado me ON me.ID_MERCADO = p.ID_MERCADO
    LEFT JOIN fruta_consignatario co ON co.ID_CONSIGNATARIO = p.ID_CONSIGNATARIO
    LEFT JOIN estandar_eexportacion es ON es.ID_ESTANDAR = p.ID_ESTANDAR
    LEFT JOIN fruta_tcalibre ca ON ca.ID_TCALIBRE = p.ID_TCALIBRE
    LEFT JOIN fruta_tmoneda tm ON tm.ID_TMONEDA = p.ID_TMONEDA
    LEFT JOIN fruta_cventa cv ON cv.ID_CVENTA = p.ID_CVENTA
    LEFT JOIN fruta_mventa mv ON mv.ID_MVENTA = p.ID_MVENTA
    LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = p.ID_TFLETE
    WHERE p.ID_EMPRESA = ?
    ORDER BY p.ESTADO_REGISTRO DESC, p.ID_TEMPORADA DESC, co.NOMBRE_CONSIGNATARIO, es.CODIGO_ESTANDAR, ca.NOMBRE_TCALIBRE
");
$stmtLista->execute([$EMPRESAS]);
$LISTA = $stmtLista->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Presupuesto FOB Exportadora</title>
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
                        <h3 class="page-title">Presupuesto FOB</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item">Exportadora</li>
                                    <li class="breadcrumb-item active">Presupuesto FOB</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>
            <section class="content">
                <?php if ($mensaje !== '') { ?><div class="alert alert-warning"><?php echo h($mensaje); ?></div><?php } ?>
                <?php if ($mensajeOk !== '') { ?><div class="alert alert-success"><?php echo h($mensajeOk); ?></div><?php } ?>
                <div class="box">
                    <div class="box-header with-border bg-primary">
                        <h4 class="box-title">Valor sugerido por temporada, cliente y producto</h4>
                    </div>
                    <form method="post">
                        <div class="box-body">
                            <input type="hidden" name="ID" value="<?php echo (int)$ID; ?>">
                            <div class="row">
                                <?php
                                $selects = [
                                    ['ID_TEMPORADA', 'Temporada', $TEMPORADAS_LIST, 'ID_TEMPORADA', 'NOMBRE_TEMPORADA'],
                                    ['ID_MERCADO', 'Mercado', $MERCADOS, 'ID_MERCADO', 'NOMBRE_MERCADO'],
                                    ['ID_CONSIGNATARIO', 'Cliente', $CONSIGNATARIOS, 'ID_CONSIGNATARIO', 'NOMBRE_CONSIGNATARIO'],
                                    ['ID_ESTANDAR', 'Estandar', $ESTANDARES, 'ID_ESTANDAR', 'NOMBRE_ESTANDAR'],
                                    ['ID_TCALIBRE', 'Calibre', $CALIBRES, 'ID_TCALIBRE', 'NOMBRE_TCALIBRE'],
                                    ['ID_TMONEDA', 'Moneda', $MONEDAS, 'ID_TMONEDA', 'NOMBRE_TMONEDA'],
                                    ['ID_CVENTA', 'Clausula venta', $CVENTAS, 'ID_CVENTA', 'NOMBRE_CVENTA'],
                                    ['ID_MVENTA', 'Modalidad venta', $MVENTAS, 'ID_MVENTA', 'NOMBRE_MVENTA'],
                                    ['ID_TFLETE', 'Tipo flete', $TFLETES, 'ID_TFLETE', 'NOMBRE_TFLETE'],
                                ];
                                foreach ($selects as $s) :
                                ?>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label><?php echo h($s[1]); ?></label>
                                            <select class="form-control select2" name="<?php echo h($s[0]); ?>" style="width:100%" <?php echo $DISABLED; ?>>
                                                <option></option>
                                                <?php foreach ($s[2] as $r) : ?>
                                                    <option value="<?php echo (int)$r[$s[3]]; ?>" <?php echo (string)$form[$s[0]] === (string)$r[$s[3]] ? 'selected' : ''; ?>>
                                                        <?php echo h($r[$s[4]]); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Peso caja</label>
                                        <input type="number" step="0.0001" class="form-control" name="PESO_CAJA" value="<?php echo h($form['PESO_CAJA']); ?>" <?php echo $DISABLED; ?>>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>FOB caja</label>
                                        <input type="number" step="0.0001" class="form-control" name="FOB_CAJA" value="<?php echo h($form['FOB_CAJA']); ?>" <?php echo $DISABLED; ?>>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Motivo ajuste</label>
                                        <input type="text" class="form-control" name="MOTIVO_AJUSTE" value="<?php echo h($form['MOTIVO_AJUSTE']); ?>" <?php echo $DISABLED; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <a class="btn btn-success" href="registroPresupuestoFob.php">Nuevo</a>
                            <?php if ($OP !== 'ver') : ?>
                                <?php if ($ID > 0) : ?>
                                    <button class="btn btn-warning" name="GUARDAR" value="1">Guardar ajuste</button>
                                    <button class="btn btn-danger" name="DESACTIVAR" value="1" onclick="return confirm('Desactivar presupuesto vigente?');">Desactivar</button>
                                <?php else : ?>
                                    <button class="btn btn-primary" name="CREAR" value="1">Crear presupuesto</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h4 class="box-title">Presupuestos registrados</h4>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped datatable">
                                <thead>
                                    <tr>
                                        <th>Accion</th>
                                        <th>Estado</th>
                                        <th>Temporada</th>
                                        <th>Mercado</th>
                                        <th>Cliente</th>
                                        <th>Estandar</th>
                                        <th>Calibre</th>
                                        <th>Moneda</th>
                                        <th>FOB caja</th>
                                        <th>Clausula</th>
                                        <th>Modalidad</th>
                                        <th>Flete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($LISTA as $r) : ?>
                                        <tr>
                                            <td>
                                                <a class="btn btn-sm btn-info" href="registroPresupuestoFob.php?ID=<?php echo (int)$r['ID_PRESUPUESTO_FOB']; ?>&a=ver">Ver</a>
                                                <a class="btn btn-sm btn-warning" href="registroPresupuestoFob.php?ID=<?php echo (int)$r['ID_PRESUPUESTO_FOB']; ?>&a=editar">Editar</a>
                                            </td>
                                            <td><?php echo (int)$r['ESTADO_REGISTRO'] === 1 ? 'Vigente' : 'Inactivo'; ?></td>
                                            <td><?php echo h($r['NOMBRE_TEMPORADA']); ?></td>
                                            <td><?php echo h($r['NOMBRE_MERCADO']); ?></td>
                                            <td><?php echo h($r['NOMBRE_CONSIGNATARIO']); ?></td>
                                            <td><?php echo h(trim($r['CODIGO_ESTANDAR'] . ' ' . $r['NOMBRE_ESTANDAR'])); ?></td>
                                            <td><?php echo h($r['NOMBRE_TCALIBRE']); ?></td>
                                            <td><?php echo h($r['NOMBRE_TMONEDA']); ?></td>
                                            <td><?php echo h(number_format((float)$r['FOB_CAJA'], 4, '.', '')); ?></td>
                                            <td><?php echo h($r['NOMBRE_CVENTA']); ?></td>
                                            <td><?php echo h($r['NOMBRE_MVENTA']); ?></td>
                                            <td><?php echo h($r['NOMBRE_TFLETE']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>
</body>
</html>
