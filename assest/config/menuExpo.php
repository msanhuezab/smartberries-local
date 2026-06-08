<?php
$MENU_CONFIGURACION = isset($MENU_CONFIGURACION) && $MENU_CONFIGURACION === true;
$HEADER_MODULO_TITULO = $MENU_CONFIGURACION ? 'Configuración' : 'Exportadora';
$HEADER_MODULO_URL = $MENU_CONFIGURACION ? '../../configuracion/' : 'index.php';
$CONFIG_EMPRESA_MENU = 'Empresa';
$CONFIG_TEMPORADA_MENU = 'Temporada';
$CONFIG_USUARIO_MENU = '';

if (isset($_SESSION["NOMBRE_USUARIO"])) {
  if (isset($EMPRESA_ADO, $EMPRESAS)) {
    $ARRAYEMPRESASMENU = $EMPRESA_ADO->verEmpresa($EMPRESAS);
    if ($ARRAYEMPRESASMENU) {
      $CONFIG_EMPRESA_MENU = $ARRAYEMPRESASMENU[0]['NOMBRE_EMPRESA'];
    }
  }

  if (isset($TEMPORADA_ADO, $TEMPORADAS)) {
    $ARRAYTEMPORADASMENU = $TEMPORADA_ADO->verTemporada($TEMPORADAS);
    if ($ARRAYTEMPORADASMENU) {
      $CONFIG_TEMPORADA_MENU = $ARRAYTEMPORADASMENU[0]['NOMBRE_TEMPORADA'];
    }
  }

  if (isset($USUARIO_ADO, $IDUSUARIOS)) {
    $ARRAYNOMBRESUSUARIOSLOGIN = $USUARIO_ADO->ObtenerNombreCompleto($IDUSUARIOS);
    if ($ARRAYNOMBRESUSUARIOSLOGIN) {
      $CONFIG_USUARIO_MENU = $ARRAYNOMBRESUSUARIOSLOGIN[0]["NOMBRE_COMPLETO"];
    }
  }
}
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  header.commandbar,
  header.commandbar * {
    box-sizing: border-box;
    font-family: 'Inter', Arial, sans-serif;
    letter-spacing: 0;
  }
  .sb-config-header {
    min-height: 76px;
    background: #ffffff;
    border-bottom: 1px solid #dce4ef;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 22px;
    padding: 12px 24px;
    box-shadow: 0 1px 10px rgba(16, 35, 63, .05);
    position: relative;
    z-index: 1000;
  }
  .sb-config-brand {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
  }
  .sb-config-toggle {
    width: 42px;
    height: 42px;
    border: 1px solid #dce4ef;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f8fbff;
  }
  .sb-config-toggle img {
    width: 20px;
  }
  .sb-config-logo {
    width: 146px;
    max-width: 34vw;
    height: auto;
  }
  .sb-config-module {
    border-left: 1px solid #dce4ef;
    padding-left: 16px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
  }
  .sb-config-module small {
    color: #4caf50;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: uppercase;
  }
  .sb-config-module strong {
    color: #0a3a6a;
    font-size: 18px;
    line-height: 1.1;
  }
  .sb-config-context {
    display: flex;
    justify-content: center;
    min-width: 0;
  }
  .sb-config-pill {
    min-height: 42px;
    max-width: 100%;
    border: 1px solid #dce4ef;
    border-radius: 8px;
    background: #f8fbff;
    color: #667085;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12px;
    font-weight: 700;
  }
  .sb-config-pill b {
    color: #10233f;
  }
  .sb-config-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
  }
  .sb-config-action {
    min-height: 46px;
    border: 1px solid #c8d4e3;
    border-radius: 8px;
    padding: 0 16px;
    background: #ffffff;
    color: #0a3a6a;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    font-size: 14px;
    font-weight: 800;
    line-height: 1;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
  }
  .sb-config-action:hover {
    border-color: #0a3a6a;
    background: #f7fbff;
    color: #0a3a6a;
    text-decoration: none;
  }
  .sb-config-action img {
    width: 20px;
  }
  .sb-config-action-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: #ffffff;
  }
  .sb-config-action-danger:hover {
    background: #c82333;
    border-color: #c82333;
    color: #ffffff;
  }
  .sb-config-user {
    color: #667085;
    font-size: 12px;
    font-weight: 700;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  @media (max-width: 920px) {
    .sb-config-header {
      grid-template-columns: 1fr;
      align-items: start;
    }
    .sb-config-context {
      justify-content: flex-start;
    }
    .sb-config-actions {
      justify-content: flex-start;
      flex-wrap: wrap;
    }
  }
  @media (max-width: 560px) {
    .sb-config-header {
      padding: 14px 16px;
    }
    .sb-config-brand {
      align-items: flex-start;
      flex-wrap: wrap;
    }
    .sb-config-module {
      border-left: 0;
      padding-left: 0;
      flex-basis: 100%;
    }
    .sb-config-action {
      flex: 1;
      justify-content: center;
    }
    .sb-config-user {
      max-width: 100%;
      flex-basis: 100%;
    }
  }
  .commandbar {
    min-height: 76px;
    background: #ffffff;
    border-bottom: 1px solid #dce4ef;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 22px;
    padding: 12px 24px;
    box-shadow: 0 1px 10px rgba(16, 35, 63, .05);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1200;
  }
  .commandbar-spacer {
    height: 76px;
  }
  .brand {
    display: flex;
    align-items: center;
    gap: 18px;
    min-width: 0;
  }
  .menu-toggle {
    width: 42px;
    height: 42px;
    border: 1px solid #dce4ef;
    border-radius: 8px;
    background: #f8fbff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .menu-toggle img {
    width: 20px;
    height: 20px;
  }
  .brand-logo {
    width: 150px;
    max-width: 38vw;
    height: auto;
  }
  .module-kicker {
    display: flex;
    flex-direction: column;
    gap: 2px;
    border-left: 1px solid #dce4ef;
    padding-left: 18px;
  }
  .module-kicker span:first-child {
    font-size: 12px;
    text-transform: uppercase;
    font-weight: 800;
    color: #4caf50;
  }
  .module-kicker span:last-child {
    font-size: 18px;
    font-weight: 800;
    color: #0a3a6a;
  }
  .context-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    min-width: 0;
  }
  .context-pill {
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 14px;
    border: 1px solid #dce4ef;
    border-radius: 8px;
    background: #f8fbff;
    color: #667085;
    font-size: 13px;
    font-weight: 700;
    max-width: 100%;
  }
  .vf-icon {
    width: 21px;
    height: 21px;
    display: inline-block;
    flex: 0 0 21px;
    background-color: currentColor;
    mask-position: center;
    mask-repeat: no-repeat;
    mask-size: contain;
    -webkit-mask-position: center;
    -webkit-mask-repeat: no-repeat;
    -webkit-mask-size: contain;
  }
  .vf-icon-verified {
    color: #0a3a6a;
    width: 18px;
    height: 18px;
    flex-basis: 18px;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2 4 5.5v6.2c0 5.1 3.4 8.5 8 10.3 4.6-1.8 8-5.2 8-10.3V5.5L12 2Zm-1 13.5-3.5-3.5 1.4-1.4 2.1 2.1 4.6-4.6L17 9.5l-6 6Z'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 2 4 5.5v6.2c0 5.1 3.4 8.5 8 10.3 4.6-1.8 8-5.2 8-10.3V5.5L12 2Zm-1 13.5-3.5-3.5 1.4-1.4 2.1 2.1 4.6-4.6L17 9.5l-6 6Z'/%3E%3C/svg%3E");
  }
  .vf-icon-apps {
    color: #0a3a6a;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4 4h4v4H4V4Zm6 0h4v4h-4V4Zm6 0h4v4h-4V4ZM4 10h4v4H4v-4Zm6 0h4v4h-4v-4Zm6 0h4v4h-4v-4ZM4 16h4v4H4v-4Zm6 0h4v4h-4v-4Zm6 0h4v4h-4v-4Z'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4 4h4v4H4V4Zm6 0h4v4h-4V4Zm6 0h4v4h-4V4ZM4 10h4v4H4v-4Zm6 0h4v4h-4v-4Zm6 0h4v4h-4v-4ZM4 16h4v4H4v-4Zm6 0h4v4h-4v-4Zm6 0h4v4h-4v-4Z'/%3E%3C/svg%3E");
  }
  .vf-icon-logout {
    color: #ffffff;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M10 3h8c1.1 0 2 .9 2 2v4h-2V5h-8v14h8v-4h2v4c0 1.1-.9 2-2 2h-8c-1.1 0-2-.9-2-2v-4h2v4Zm4.6 4.4L20.2 13l-5.6 5.6-1.4-1.4 3.2-3.2H3v-2h13.4l-3.2-3.2 1.4-1.4Z'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M10 3h8c1.1 0 2 .9 2 2v4h-2V5h-8v14h8v-4h2v4c0 1.1-.9 2-2 2h-8c-1.1 0-2-.9-2-2v-4h2v4Zm4.6 4.4L20.2 13l-5.6 5.6-1.4-1.4 3.2-3.2H3v-2h13.4l-3.2-3.2 1.4-1.4Z'/%3E%3C/svg%3E");
  }
  .vf-icon-menu {
    color: #0a3a6a;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4 6h16v2H4V6Zm0 5h16v2H4v-2Zm0 5h16v2H4v-2Z'/%3E%3C/svg%3E");
  }
  .context-text {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
  }
  .context-text strong {
    color: #10233f;
    font-weight: 800;
    max-width: 260px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .context-text span {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
  }
  .user-chip {
    font-size: 12px;
    font-weight: 800;
    color: #667085;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .action {
    min-height: 46px;
    border: 1px solid #c8d4e3;
    background: #ffffff;
    color: #0a3a6a;
    border-radius: 8px;
    padding: 0 16px;
    text-decoration: none;
    font-weight: 800;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    cursor: pointer;
    transition: .18s;
    white-space: nowrap;
  }
  .mobile-menu-action {
    display: none;
  }
  .action:hover {
    border-color: #0a3a6a;
    background: #f7fbff;
    color: #0a3a6a;
    text-decoration: none;
  }
  .action-danger {
    background: #dc3545;
    border-color: #dc3545;
    color: #ffffff;
  }
  .action-danger:hover {
    background: #c82333;
    border-color: #c82333;
    color: #ffffff;
  }
  .action img {
    width: 20px;
    height: 20px;
  }
  @media (max-width: 860px) {
    .commandbar {
      grid-template-columns: 1fr;
      align-items: start;
    }
    .commandbar-spacer {
      height: 168px;
    }
    .context-strip {
      justify-content: flex-start;
    }
    .actions {
      justify-content: flex-start;
      flex-wrap: wrap;
    }
    .user-chip {
      max-width: 100%;
      flex-basis: 100%;
    }
    .mobile-menu-action {
      display: inline-flex;
    }
  }
  @media (max-width: 560px) {
    .commandbar {
      padding: 16px;
    }
    .commandbar-spacer {
      height: 218px;
    }
    .brand {
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 10px;
    }
    .module-kicker {
      border-left: 0;
      padding-left: 0;
      flex-basis: 100%;
    }
    .action {
      flex: 1;
      justify-content: center;
    }
  }
  @media (max-width: 767px) {
    .main-sidebar {
      margin-top: 0 !important;
      top: 168px;
      height: calc(100vh - 168px);
      overflow-y: auto;
      z-index: 1190;
    }
    .sidebar-open .main-sidebar {
      box-shadow: 10px 0 28px rgba(16, 35, 63, .22);
    }
  }
  @media (max-width: 560px) {
    .main-sidebar {
      top: 218px;
      height: calc(100vh - 218px);
    }
  }
</style>
<header class="commandbar">
  <div class="brand">
    <a href="<?php echo $HEADER_MODULO_URL; ?>" title="<?php echo htmlspecialchars($HEADER_MODULO_TITULO, ENT_QUOTES, 'UTF-8'); ?>">
      <img class="brand-logo" src="../../assest/img/volcan-foods-logo-original.png" alt="Volcan Foods">
    </a>
    <div class="module-kicker">
      <span>Módulo</span>
      <span><?php echo htmlspecialchars($HEADER_MODULO_TITULO, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
  </div>
  <div class="context-strip">
    <div class="context-pill">
      <span class="vf-icon vf-icon-verified" aria-hidden="true"></span>
      <span class="context-text">
        <strong><?php echo htmlspecialchars($CONFIG_EMPRESA_MENU, ENT_QUOTES, 'UTF-8'); ?></strong>
        <span><?php echo htmlspecialchars($CONFIG_TEMPORADA_MENU, ENT_QUOTES, 'UTF-8'); ?></span>
      </span>
    </div>
  </div>
  <div class="actions">
    <?php if ($CONFIG_USUARIO_MENU !== '') { ?>
      <span class="user-chip"><?php echo htmlspecialchars($CONFIG_USUARIO_MENU, ENT_QUOTES, 'UTF-8'); ?></span>
    <?php } ?>
    <a class="action mobile-menu-action push-btn" href="#" data-toggle="push-menu" role="button" title="Menú">
      <span class="vf-icon vf-icon-menu" aria-hidden="true"></span>
      <span>Menú</span>
    </a>
    <a class="action" href="../../interno.php" title="Módulos">
      <span class="vf-icon vf-icon-apps" aria-hidden="true"></span>
      <span>Módulos</span>
    </a>
    <form method="post" class="m-0">
      <button class="action action-danger" type="submit" name="CERRARS" value="CERRARS">
        <span class="vf-icon vf-icon-logout" aria-hidden="true"></span>
        <span>Cerrar Sesión</span>
      </button>
    </form>
  </div>
</header>
<div class="commandbar-spacer" aria-hidden="true"></div>
<?php
$ARRAYEMPRESACAMBIAR = $EMPRESA_ADO->listarEmpresaCBX();
$ARRAYPLANTACAMBIAR = $PLANTA_ADO->listarPlantaPropiaCBX();
?>
<!-- modal Area -->
<div class="modal center-modal fade" id="modal-empresa" tabindex="-1">
  <form method="POST">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Cambiar </h5>
          <button type="button" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p>
          <div class="form-group">
            <label>Empresa</label>
            <select class="form-control select2" id="EMPRESACAMBIAR" name="EMPRESACAMBIAR" style="width: 100%;" <?php echo $DISABLEDMENU; ?>>
              <option></option>
              <?php foreach ($ARRAYEMPRESACAMBIAR as $r) : ?>
                <?php if ($ARRAYEMPRESACAMBIAR) {    ?>
                  <option value="<?php echo $r['ID_EMPRESA']; ?>" <?php if ($EMPRESAS == $r['ID_EMPRESA']) {
                                                                    echo "selected";
                                                                  } ?>> <?php echo $r['NOMBRE_EMPRESA'] ?> </option>
                <?php } else { ?>
                  <option>No Hay Datos Registrados </option>
                <?php } ?>
              <?php endforeach; ?>
            </select>
            <label id="val_empresa" class="validacion"> </label>
          </div>
          </p>

        </div>
        <div class="modal-footer modal-footer-uniform">
          <button type="button" class="btn btn-rounded btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-rounded btn-primary float-right" id="CAMBIARE" name="CAMBIARE" <?php echo $DISABLEDMENU; ?>>Cambiar</button>
        </div>
      </div>
    </div>
  </form>
</div>
<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">
  <!-- sidebar-->
  <section class="sidebar">
    <!-- sidebar menu-->
    <ul class="sidebar-menu" data-widget="tree">
      <li>
        <a href="<?php echo $MENU_CONFIGURACION ? '../../configuracion/' : 'index.php'; ?>">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/dashboard.svg" class="svg-icon" alt="">
          <span>Inicio</span>
        </a>
      </li>     
      <?php if(!$MENU_CONFIGURACION && $PEXPORTADORA=="1"){ ?>
        <li class="header">Módulo</li>    
        <?php if($PEMATERIALES=="1"){ ?>
          <!--<li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/reports.svg" class="svg-icon" alt="">
              <span>Materiales</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">     
              <li class="treeview">
                <a href="#">Ficha Consumo
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="registroFicha.php">Registro Ficha </i></a></li>
                  <li><a href="listarFicha.php"> Agrupado Ficha</i></a></li>
                </ul>
              </li>
              <li><a href="listarConsumo.php">Consumo Materiales</i></a></li>
              <li><a href="listarConsumoProceso.php">Cons. Mat. Proceso</i></a></li>
               <li style="display:none"><a href="listarConsumoFolio.php">Cons. Mat. Folio</i></a></li> 
            </ul>
          </li>-->            
        <?php  } ?>    
        <?php if($PEEXPORTACION=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/exchange.svg" class="svg-icon" alt="">
              <span> Exportación</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <li class="treeview">
                <a href="#">Inst. Carga
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="registroICarga.php">Registro Inst. Carga</i></a></li>
                  <li><a href="listarICarga.php">Agrupado Inst. Carga</i></a></li>
                  <li><a href="listarICargaDetallado.php">Detallado Inst. Carga</i></a></li>
                  <li><a href="registroInvoiceExp.php">Invoice Editable</i></a></li>
                </ul>
              </li> 
              <li class="treeview">
                <a href="#">Nota D/C
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="registroNotadc.php">Registro Nota</i></a></li>
                  <li><a href="listarNotadc.php">Agrupado Nota</i></a></li>
                </ul>
              </li>
              <li><a href="registroIvvExp.php">Registro IVV</i></a></li>
            </ul>
          </li>
        <?php  } ?>     
        <?php if($PELIQUIDACION=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/transactions.svg" class="svg-icon" alt="">
              <span> Liquidación</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
                <li><a href="registroLiquidacionExp.php">Liquidación Detallada</i></a></li>
                <li><a href="cuentaCorrienteBroker.php">Cuenta Corriente Broker</i></a></li>
                <li><a href="exportarLiquidacionExp.php">Exportar Liquidaciones</i></a></li>
                <li><a href="registroPresupuestoFob.php">Presupuesto FOB</i></a></li>
                <li><a href="registroTitemLiqui.php"></i>Item Liqui.</a></li>
            </ul>
          </li>
        <?php  } ?>
        <?php if($PEFRUTA=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/apps.svg" class="svg-icon" alt="">
              <span> Fruta</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
                <?php if($PEFCICARGA=="1"){ ?>
                  <li><a href="registroCambiarIcarga.php">Cambio  Inst. Carga</i></a></li>
                <?php  } ?>  
            </ul>
          </li>
        <?php  } ?> 
        <?php if($PEINFORMES=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/pages.svg" class="svg-icon" alt="">
              <span> Informes</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <li class="treeview">
                <a href="#">Granel
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">                  
                  <li class="treeview">
                    <a href="#">Detallado Recepción
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">
                      <li><a href="listarRecepcionmpDetallado.php">Detallado Recepción MP</i></a></li>
                      <li><a href="listarRecepcionindDetallado.php">Detallado Recepción IND</i></a></li>
                      <li><a href="listarRecepcionGranelConsolidado.php">Consolidado Recep. Granel</i></a></li>
                    </ul>
                  </li>
                  <li class="treeview">
                    <a href="#">Detallado Despacho
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">
                      <li><a href="listarDespachompDetallado.php">Detallado Despacho MP</i></a></li>  
                      <li><a href="listarDespachoindDetallado.php">Detallado Despacho IND</i></a></li>
                      <li><a href="listarDespachoGranelConsoliado.php">Consolidado Desp. Granel</i></a></li>
                    </ul>
                  </li>              
                  <li class="treeview">
                    <a href="#">Existencia
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">         
                      <li class="treeview">
                        <a href="#">Disponible
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="listarEximateriaprima.php">Materia Prima</i></a></li>          
                          <li><a href="listarExiindustrial.php">Producto Industrial</i></a></li>
                        </ul>
                      </li>            
                      <li class="treeview">
                        <a href="#">Despachado
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="listarEximateriaprimaDespachado.php">Materia Prima</i></a></li>          
                          <li><a href="listarExiindustrialDespachado.php">Producto Industrial</i></a></li>
                        </ul>
                      </li>     
                      <li class="treeview">
                        <a href="#">Historial
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="listarHEximateriaprima.php">Materia Prima</i></a></li>
                          <li><a href="listarHExiindustrial.php">Producto Industrial</i></a></li>
                        </ul>
                      </li>
                    </ul>
                  </li>
                </ul>
              </li>
              <li class="treeview">
                <a href="#">Producto Terminado
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">    
                  <li><a href="listarRecepcionptDetallado.php">Detallado Recepción</i></a></li>             
                  <li class="treeview">
                    <a href="#">Detallado Despacho
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">
                      <li><a href="listarDespachoptDetallado.php">Despacho PT</i></a></li>
                      <li><a href="listarDespachoexDetallado.php">Despacho Expo</i></a></li>
                      <li><a href="listarDespachoptexConsolidado.php">Consolidado Desp. PT</i></a></li>
                    </ul>
                  </li>       
                  <li class="treeview">
                    <a href="#">Existencia
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">
                      <li><a href="listarExiexportacion.php">Disponible </i></a></li>
                      <li><a href="listarExiexportacionDespachado.php">Despachado </i></a></li>
                      <li><a href="listarHExiexportacion.php">Historial </i></a></li>
                    </ul>
                  </li>                         
                  <li><a href="listarExiexportacionAgrupado.php">Agrupado PT </i></a></li>  
                </ul>
              </li>
              <li class="treeview">
                <a href="#">Gestión
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="listarRecepcionConsolidado.php">Consolidado Recepción </i></a></li>
                  <li><a href="listarDespachoConsolidado.php">Consolidado Despacho </i></a></li>
                  <li><a href="#">Informe Liquidación </i></a></li>
                </ul>
              </li>
            </ul>
          </li>      
        <?php  } ?>
        <?php if($PADMINISTRADOR=="1"){ ?>   
          <?php if($PADAPERTURA=="1"){ ?> 
            <li class="treeview">
              <a href="#">
                <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/extensions.svg" class="svg-icon" alt="">
                <span>Apertura Registro</span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">         
                <?php if($PEMATERIALES=="1"){ ?>
                  <li><a href="listarAPficha.php">Ficha Consumo </a></li>
                <?php  } ?>         
                <?php if($PEEXPORTACION=="1"){ ?>
                  <li><a href="listarAPiCarga.php">Instructivo Carga </a></li>
                <?php  } ?>  
                <?php if($PEEXPORTACION=="1"){ ?>
                  <li><a href="listarAPnotadc.php">Nota D/C </a></li>
                <?php  } ?>  
                <?php if($PELIQUIDACION=="1"){ ?>
                  <li><a href="listarAPvalor.php">Valor Liquidación </a></li>
                <?php  } ?>                   
              </ul>
            </li> 
          <?php  } ?>  
        <?php  } ?> 
      <?php  } ?>
      <?php if($MENU_CONFIGURACION && $PMANTENEDORES=="1"){ ?>
        <li class="header">Configuraciones</li>
        <li class="treeview">
          <a href="#">
            <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/miscellaneous.svg" class="svg-icon" alt="">
            <span>Configuración App</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>      
          <ul class="treeview-menu">
            <li class="treeview">
              <a href="#">Principal
                <span class="pull-left-container">
                  <i class=" fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="registroEmpresa.php"></i>Empresa</a></li>
                <li><a href="registroPlanta.php"></i>Planta</a></li>
                <li><a href="registroTemporada.php"></i>Temporada</a></li>
                <!--<li><a href="registroBodega.php"></i>Bodega</a></li> -->
                <li><a href="registroFolio.php"></i>Folio</a></li>
              </ul>
            </li>
            <li class="treeview">
              <a href="#">Fruta
                <span class="pull-left-container">
                  <i class=" fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="registroProductor.php"></i>Productor</a></li>
                <li><a href="registroVespecies.php"></i>Variedad </a></li>
                <li><a href="registroEspecies.php"></i>Especies</a></li>
                <li><a href="registroCuartel.php"></i> Cuartel</a></li>
                <li><a href="registroTetiqueta.php"></i>Etiqueta</a></li>
                <li><a href="registroTembalaje.php"></i>Embalaje</a></li>
                <li><a href="registroTcalibre.php"></i>Calibre</a></li>
                <li><a href="registroTcalibreind.php"></i>Calibre Industrial</a></li>
              </ul>
            </li>          
            <li class="treeview">
              <a href="#">Estandares
                <span class="pull-left-container">
                  <i class=" fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="registroErecepcion.php"></i>Granel</a></li>
                <li><a href="registroEexportacion.php"></i>Exportacion</a></li>
                <li><a href="registroEcomercial.php"></i> Expo. Comercial</a></li>
                <li><a href="registroEindustrial.php"></i>Industrial</a></li>
              </ul>
            </li>
            <li class="treeview">
              <a href="#">Ubicacion
                <span class="pull-left-container">
                  <i class=" fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="registroCiudad.php"></i>Ciudad</a></li>
                <li><a href="registroComuna.php"></i>Comuna</a></li>
                <li><a href="registroProvincia.php"></i>Provincia</a></li>
                <li><a href="registroRegion.php"></i>Region</a></li>
                <li><a href="registroPais.php"></i>Pais</a></li>
              </ul>
            </li>
            <li class="treeview">
              <a href="#">Transporte
                <span class="pull-left-container">
                  <i class=" fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="registroLaerea.php"></i>Linea Area</a></li>
                <li><a href="registroNaviera.php"></i>Naviera</a></li>
                <li><a href="registroTransporte.php"></i>Transporte</a></li>
                <li><a href="registroConductor.php"></i>Conductor</a></li>
              </ul>
            </li>
            <li class="treeview">
              <a href="#">Tipo
                <span class="pull-left-container">
                <i class=" fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="registroTproductor.php"></i>Tipo Productor</a></li>
                <li><a href="registroTproceso.php"></i>Tipo Proceso</a></li>
                <li><a href="registroTreembalaje.php"></i>Tipo Reembalaje</a></li>
                <li><a href="registroTcontenedor.php"></i>Tipo Contenedor</a></li>
                <li><a href="registroTflete.php"></i>Tipo Flete</a></li>
                <li><a href="registroTmoneda.php"></i>Tipo Moneda</a></li>
                <li><a href="registroTservicio.php"></i>Tipo Servicio</a></li>
                <li><a href="registroTmanejo.php"></i>Tipo Manejo</a></li>
                <li><a href="registroTinpsag.php"></i>Tipo Inspección Sag</a></li>
                <li><a href="registroTtratamiento1.php"></i>Tipo Tratamiento 1</a></li>
                <li><a href="registroTtratamiento2.php"></i>Tipo Tratamiento 2</a></li>
                <li><a href="registroTcategoria.php"></i>Tipo Categoria</a></li>
                <li><a href="registroTcolor.php"></i>Tipo Color</a></li>    
              </ul>
            </li>         
            <li class="treeview">
              <a href="#">Otros
                <span class="pull-left-container">
                  <i class=" fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="registroCcalidad.php"></i>Color Calidad</a></li>
                <li><a href="registroContraparte.php"></i>Contraparte</a></li>
                <li><a href="registroInpector.php"></i>Inpector</a></li>
                <li><a href="registroComprador.php"></i>Comprador</a></li>
              </ul>
            </li>      
          </ul>
        </li>     
      <?php  } ?>
        <?php if(!$MENU_CONFIGURACION && $PMANTENEDORES=="1" && $PEEXPORTACION=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/exchange.svg" class="svg-icon" alt="">
              <span>Configuración Exportadora</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <li class="treeview">
                <a href="#">Destino
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="registroLdestino.php"></i>Lugar Destino</a></li>
                  <li><a href="registroPdestino.php"></i>Puerto Destino </a></li>
                  <li><a href="registroAdestino.php"></i>Aeropuerto Destino </a></li>
                </ul>
              </li>
              <li class="treeview">
                <a href="#">Carga
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="registroLcarga.php"></i>Lugar Carga</a></li>
                  <li><a href="registroPcarga.php"></i>Puerto Carga </a></li>
                  <li><a href="registroAcarga.php"></i>Aeropuerto Carga </a></li>
                </ul>
              </li>
              <li class="treeview">
                <a href="#">Pago
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="registroFpago.php"></i>Formato Pago</a></li>
                  <li><a href="registroCventa.php"></i>Clausaula Venta </a></li>
                  <li><a href="registroMventa.php"></i>Modalidad Venta </a></li>
                </ul>
              </li>
              <li class="treeview">
                <a href="#">Mercado
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="registroMercado.php"></i>Mercado</a></li>
                  <li><a href="registroRmercado.php"></i>Restrinccion Mercado</a></li>
                </ul>
              </li>
              <li><a href="registroExportadora.php"></i>Exportadora</a></li>
              <li><a href="registroAtmosfera.php"></i>Atmosfera</a></li>
              <li><a href="registroEmisionbl.php"></i>Emision BL</a></li>
              <li><a href="registroConsignatorio.php"></i>Consignatorio</a></li>
              <li><a href="registroNotificador.php"></i>Notificador </a></li>
              <li><a href="registroBroker.php"></i>Cliente </a></li>
              <li><a href="registroRfinal.php"></i>Recibidor Final </a></li>
              <li><a href="registroAaduana.php"></i>Agente Aduana </a></li>
              <li><a href="registroAgcarga.php"></i>Agente Carga </a></li>
              <li><a href="registroDfinal.php"></i>Destino Final </a></li>
              <li><a href="registroSeguro.php"></i>Seguro </a></li>
            </ul>
          </li>
        <?php  } ?>
      <?php if($MENU_CONFIGURACION && $PADMINISTRADOR=="1"){ ?>
        <?php if($PADUSUARIO=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/members.svg" class="svg-icon" alt="">
              <span>Usuario</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <li><a href="registroUsuario.php">Usuario</i></a></li>
              <li><a href="listarAusuario.php">Historial Usuario</i></a></li>
              <li><a href="registroTusuario.php">Tipo Usuario</i></a></li>
              <li><a href="registroPtusuario.php">Privilegio Tipo Usuario</i></a></li>
              <li><a href="registroUsuarioEmpPro.php">Usu. Asoc.Empre. Prod.</i></a></li>
            </ul>
          </li>    
        <?php  } ?>   
        <?php if($PADAVISO=="1"){ ?>
          <li>
            <a href="registroUsuarioAviso.php">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/miscellaneous.svg" class="svg-icon" alt="">
              <span>Registro Aviso</span>
            </a>
          </li>
          <li>
            <a href="cronPt.php">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/schedule.svg" class="svg-icon" alt="">
              <span>Cron PT</span>
            </a>
          </li>
          <li>
            <a href="cronEjecutados.php">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/schedule.svg" class="svg-icon" alt="">
              <span>Cron ejecutados</span>
            </a>
          </li>
        <?php  } ?>
      <?php  } ?>
    </ul>
  </section>
</aside>


