<?php
$HEADER_MODULO_TITULO = 'Fruta';
$HEADER_MODULO_URL = 'index.php';
$CONFIG_EMPRESA_MENU = 'Empresa';
$CONFIG_PLANTA_MENU = 'Planta';
$CONFIG_TEMPORADA_MENU = 'Temporada';
$CONFIG_USUARIO_MENU = '';

if (isset($_SESSION["NOMBRE_USUARIO"])) {
  if (isset($EMPRESA_ADO, $EMPRESAS)) {
    $ARRAYEMPRESASMENU = $EMPRESA_ADO->verEmpresa($EMPRESAS);
    if ($ARRAYEMPRESASMENU) {
      $CONFIG_EMPRESA_MENU = $ARRAYEMPRESASMENU[0]['NOMBRE_EMPRESA'];
    }
  }

  if (isset($PLANTA_ADO, $PLANTAS)) {
    $ARRAYPLANTASMENU = $PLANTA_ADO->verPlanta($PLANTAS);
    if ($ARRAYPLANTASMENU) {
      $CONFIG_PLANTA_MENU = $ARRAYPLANTASMENU[0]['NOMBRE_PLANTA'];
    }
    $ARRAYPLANTACAMBIAR = $PLANTA_ADO->listarPlantaPropiaCBX();
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
  /* ── plant switcher ──────────────────────────── */
  .vf-icon-building {
    color: #0a3a6a;
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M3 21v-2h2v-8H3V9l9-6 9 6v2h-2v8h2v2H3zm4-2h4v-4H7v4zm6 0h4v-4h-4v4zm4-6V9h-4v4h4zm-6 0V9H7v4h4z'/%3E%3C/svg%3E");
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M3 21v-2h2v-8H3V9l9-6 9 6v2h-2v8h2v2H3zm4-2h4v-4H7v4zm6 0h4v-4h-4v4zm4-6V9h-4v4h4zm-6 0V9H7v4h4z'/%3E%3C/svg%3E");
  }
  .plant-switcher {
    position: relative;
  }
  .plant-panel {
    background: #fff;
    border: 1px solid #dce4ef;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(16,35,63,.14);
    display: none;
    min-width: 200px;
    padding: 6px;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    z-index: 2100;
  }
  .plant-panel.open { display: block; }
  .plant-panel-form { margin: 0; }
  .plant-panel-btn {
    align-items: center;
    background: none;
    border: none;
    border-radius: 7px;
    color: #0a3a6a;
    cursor: pointer;
    display: flex;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 13px;
    font-weight: 600;
    gap: 10px;
    padding: 9px 12px;
    text-align: left;
    width: 100%;
  }
  .plant-panel-btn:hover { background: #f0f6ff; }
  .plant-panel-btn.current {
    background: #e8f0fe;
    color: #1a56db;
    font-weight: 800;
  }
  .plant-panel-dot {
    background: transparent;
    border-radius: 50%;
    flex: 0 0 8px;
    height: 8px;
    width: 8px;
  }
  .plant-panel-btn.current .plant-panel-dot { background: #1a56db; }
  /* ── module switcher ──────────────────────────── */
  .module-switcher { position: relative; }
  .module-panel {
    background: #fff;
    border: 1px solid #dce4ef;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(16,35,63,.14);
    display: none;
    min-width: 180px;
    padding: 6px;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    z-index: 2100;
  }
  .module-panel.open { display: block; }
  .module-panel-btn {
    align-items: center;
    background: none;
    border: none;
    border-radius: 7px;
    color: #0a3a6a;
    cursor: pointer;
    display: flex;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 13px;
    font-weight: 600;
    gap: 10px;
    padding: 9px 12px;
    text-align: left;
    text-decoration: none;
    width: 100%;
  }
  .module-panel-btn:hover { background: #f0f6ff; color: #0a3a6a; text-decoration: none; }
  .module-panel-btn.current { background: #e8f0fe; color: #1a56db; font-weight: 800; }
  .module-panel-dot {
    background: transparent;
    border: 2px solid #c8d4e3;
    border-radius: 50%;
    flex: 0 0 8px;
    height: 8px;
    width: 8px;
  }
  .module-panel-btn.current .module-panel-dot { background: #1a56db; border-color: #1a56db; }
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
        <span><?php echo htmlspecialchars($CONFIG_PLANTA_MENU, ENT_QUOTES, 'UTF-8'); ?></span>
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
    <?php if (!empty($ARRAYPLANTACAMBIAR) && count($ARRAYPLANTACAMBIAR) > 1) { ?>
    <div class="plant-switcher">
      <button type="button" class="action plant-switcher-btn" title="Cambiar planta">
        <span class="vf-icon vf-icon-building" aria-hidden="true"></span>
        <span><?php echo htmlspecialchars($CONFIG_PLANTA_MENU, ENT_QUOTES, 'UTF-8'); ?></span>
      </button>
      <div class="plant-panel" id="plantPanel">
        <?php foreach ($ARRAYPLANTACAMBIAR as $r) { ?>
          <form class="plant-panel-form" method="POST">
            <input type="hidden" name="PLANTACAMBIAR" value="<?php echo htmlspecialchars($r['ID_PLANTA'], ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" name="CAMBIARP" value="CAMBIARP"
              class="plant-panel-btn <?php echo ((string)$PLANTAS === (string)$r['ID_PLANTA']) ? 'current' : ''; ?>">
              <span class="plant-panel-dot"></span>
              <?php echo htmlspecialchars($r['NOMBRE_PLANTA'], ENT_QUOTES, 'UTF-8'); ?>
            </button>
          </form>
        <?php } ?>
      </div>
    </div>
    <?php } ?>
    <div class="module-switcher">
      <button type="button" class="action module-switcher-btn" title="Cambiar módulo">
        <span class="vf-icon vf-icon-apps" aria-hidden="true"></span>
        <span>Módulos</span>
      </button>
      <div class="module-panel" id="modulePanel">
        <?php
        $SB_MODULOS = [
          ['titulo' => 'Fruta',         'url' => '../../fruta/'],
          ['titulo' => 'Material',      'url' => '../../material/'],
          ['titulo' => 'Exportadora',   'url' => '../../exportadora/'],
          ['titulo' => 'Calidad',       'url' => '../../calidad/'],
          ['titulo' => 'Estadística',   'url' => '../../estadistica/'],
          ['titulo' => 'Configuración', 'url' => '../../configuracion/'],
        ];
        foreach ($SB_MODULOS as $mod) {
          $isMod = (stripos($HEADER_MODULO_TITULO, $mod['titulo']) !== false || stripos($mod['titulo'], $HEADER_MODULO_TITULO) !== false);
        ?>
          <a href="<?php echo htmlspecialchars($mod['url'], ENT_QUOTES, 'UTF-8'); ?>"
             class="module-panel-btn<?php echo $isMod ? ' current' : ''; ?>">
            <span class="module-panel-dot"></span>
            <?php echo htmlspecialchars($mod['titulo'], ENT_QUOTES, 'UTF-8'); ?>
          </a>
        <?php } ?>
      </div>
    </div>
    <form method="post" class="m-0">
      <button class="action action-danger" type="submit" name="CERRARS" value="CERRARS">
        <span class="vf-icon vf-icon-logout" aria-hidden="true"></span>
        <span>Cerrar Sesión</span>
      </button>
    </form>
  </div>
</header>
<div class="commandbar-spacer" aria-hidden="true"></div>
<script>
(function(){
  var btn    = document.querySelector('.plant-switcher-btn');
  var panel  = document.getElementById('plantPanel');
  var modBtn = document.querySelector('.module-switcher-btn');
  var modPanel = document.getElementById('modulePanel');
  if (btn && panel) {
    btn.addEventListener('click', function(e){ e.stopPropagation(); panel.classList.toggle('open'); });
  }
  if (modBtn && modPanel) {
    modBtn.addEventListener('click', function(e){ e.stopPropagation(); modPanel.classList.toggle('open'); });
  }
  document.addEventListener('click', function(){
    if (panel) panel.classList.remove('open');
    if (modPanel) modPanel.classList.remove('open');
  });
})();
</script>
<?php
$ARRAYEMPRESACAMBIAR = $EMPRESA_ADO->listarEmpresaCBX();
$ARRAYPLANTACAMBIAR = $PLANTA_ADO->listarPlantaPropiaCBX();

$RECEPCIONMP_ABIERTA = 0;
$RECEPCIONIND_ABIERTA = 0;
$RECEPCION_GRANEL_ABIERTA = 0;
$DESPACHOMP_ABIERTO = 0;
$DESPACHOIND_ABIERTO = 0;
$DESPACHOPT_ABIERTO = 0;
$DESPACHOEXPO_ABIERTO = 0;
$DESPACHO_GRANEL_ABIERTO = 0;
$DESPACHO_FRIGORIFICO_ABIERTO = 0;
$PROCESO_ABIERTO = 0;
$REEMBALAJE_ABIERTO = 0;
$REPALETIZAJE_ABIERTO = 0;
$RECEPCIONPT_ABIERTA = 0;
$PACKING_ABIERTO = 0;
$FRIGORIFICO_ABIERTO = 0;
try {
  if (!isset($ARRAYREGISTROSABIERTOSMENU) || !$ARRAYREGISTROSABIERTOSMENU) {
    include_once '../../assest/controlador/CONSULTA_ADO.php';
    $MENU_CONSULTA_ADO = new CONSULTA_ADO();
    $ARRAYREGISTROSABIERTOSMENU = $MENU_CONSULTA_ADO->contarRegistrosAbiertosFruta($EMPRESAS, $PLANTAS, $TEMPORADAS);
  }
  if ($ARRAYREGISTROSABIERTOSMENU) {
    $RECEPCIONMP_ABIERTA = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['RECEPCIONMP'] ?? 0);
    $RECEPCIONIND_ABIERTA = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['RECEPCIONIND'] ?? 0);
    $RECEPCION_GRANEL_ABIERTA = $RECEPCIONMP_ABIERTA + $RECEPCIONIND_ABIERTA;
    $DESPACHOMP_ABIERTO = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['DESPACHOMP'] ?? 0);
    $DESPACHOIND_ABIERTO = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['DESPACHOIND'] ?? 0);
    $DESPACHOPT_ABIERTO = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['DESPACHOPT'] ?? 0);
    $DESPACHOEXPO_ABIERTO = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['DESPACHOEXPO'] ?? 0);
    $DESPACHO_GRANEL_ABIERTO = $DESPACHOMP_ABIERTO + $DESPACHOIND_ABIERTO;
    $DESPACHO_FRIGORIFICO_ABIERTO = $DESPACHOPT_ABIERTO + $DESPACHOEXPO_ABIERTO;
    $PROCESO_ABIERTO = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['PROCESO'] ?? 0);
    $REEMBALAJE_ABIERTO = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['REEMBALAJE'] ?? 0);
    $REPALETIZAJE_ABIERTO = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['REPALETIZAJE'] ?? 0);
    $RECEPCIONPT_ABIERTA = (int) ($ARRAYREGISTROSABIERTOSMENU[0]['RECEPCIONPT'] ?? 0);
    $PACKING_ABIERTO = $PROCESO_ABIERTO + $REEMBALAJE_ABIERTO + $REPALETIZAJE_ABIERTO;
    $FRIGORIFICO_ABIERTO = $RECEPCIONPT_ABIERTA + $DESPACHO_FRIGORIFICO_ABIERTO + $REPALETIZAJE_ABIERTO;
  }
} catch (Exception $e) {
  $RECEPCIONMP_ABIERTA = 0;
  $RECEPCIONIND_ABIERTA = 0;
  $RECEPCION_GRANEL_ABIERTA = 0;
}
$badgeRecepcion = function ($cantidad, $titulo = 'Registros abiertos') {
  if ((int) $cantidad <= 0) {
    return '';
  }
  return '<span class="badge badge-danger pull-right" title="' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '">' . (int) $cantidad . '</span>';
};
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
<div class="modal center-modal fade" id="modal-planta" tabindex="-1">
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
            <label>Planta</label>
            <select class="form-control select2" id="PLANTACAMBIAR" name="PLANTACAMBIAR" style="width: 100%;" <?php echo $DISABLEDMENU; ?>>
              <option></option>
              <?php foreach ($ARRAYPLANTACAMBIAR as $r) : ?>
                <?php if ($ARRAYPLANTACAMBIAR) {    ?>
                  <option value="<?php echo $r['ID_PLANTA']; ?>" <?php if ($PLANTAS == $r['ID_PLANTA']) {
                                                                    echo "selected";
                                                                  } ?>> <?php echo $r['NOMBRE_PLANTA'] ?> </option>
                <?php } else { ?>
                  <option>No Hay Datos Registrados </option>
                <?php } ?>
              <?php endforeach; ?>
            </select>
            <label id="val_planta" class="validacion"> </label>
          </div>
          </p>
        </div>
        <div class="modal-footer modal-footer-uniform">
          <button type="button" class="btn btn-rounded btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-rounded btn-primary float-right" id="CAMBIARP" name="CAMBIARP" <?php echo $DISABLEDMENU; ?>>Cambiar</button>
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
        <a href="index.php">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/dashboard.svg" class="svg-icon" alt="">
          <span>Inicio</span>
        </a>
      </li>
      <?php if($PFRUTA=="1"){ ?>
        <li class="header">Modulo</li>        
        <?php if($PFGRANEL=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/layout.svg" class="svg-icon" alt="">
              <span>Granel</span>
              <span class="pull-right-container">
                <?php echo $badgeRecepcion($RECEPCION_GRANEL_ABIERTA + $DESPACHO_GRANEL_ABIERTO, 'Registros abiertos en Granel'); ?>
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">                  
              <?php if($PFGRECEPCION=="1"){ ?>
                <li class="treeview">
                  <a href="#">Recepcion
                    <span class="pull-left-container">
                      <?php echo $badgeRecepcion($RECEPCION_GRANEL_ABIERTA); ?>
                      <i class=" fa fa-angle-right pull-right "></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li class="treeview">
                      <a href="#">Materia Prima
                        <span class="pull-left-container">
                          <?php echo $badgeRecepcion($RECEPCIONMP_ABIERTA); ?>
                          <i class=" fa fa-angle-right pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li><a href="registroRecepcionmp.php">Registro Recepción <i class="ti-more"></i></a></li>
                        <li><a href="listarRecepcionmp.php">Agrupado Recepción<i class="ti-more"></i></a></li>
                        <li><a href="listarRecepcionmpDetallado.php">Detallado Recepción<i class="ti-more"></i></a></li>
                        <li><a href="listarRecepcionmpInterplanta.php">Agrupado Interplanta<i class="ti-more"></i></a></li>
                      </ul>
                    </li>
                    <li class="treeview">
                      <a href="#">Industrial
                        <span class="pull-left-container">
                          <?php echo $badgeRecepcion($RECEPCIONIND_ABIERTA); ?>
                          <i class=" fa fa-angle-right pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li><a href="registroRecepcionind.php">Registro Recepción <i class="ti-more"></i></a> </li>
                        <li><a href="listarRecepcionind.php">Agrupado Recepción<i class="ti-more"></i></a></li>
                        <li><a href="listarRecepcionindDetallado.php">Detallado Recepción<i class="ti-more"></i></a></li>
                        <li><a href="listarRecepcionindInterplanta.php">Agrupado Interplanta<i class="ti-more"></i></a></li>
                      </ul>
                    </li>
                    <li><a href="listarRecepcionGranelConsolidado.php">Consolidado Recepción<i class="ti-more"></i></a></li>     
                  </ul>
                </li>     
              <?php  } ?>
              <?php if($PFGDESPACHO=="1"){ ?>
                <li class="treeview">
                  <a href="#">Despacho
                    <span class="pull-left-container">
                      <?php echo $badgeRecepcion($DESPACHO_GRANEL_ABIERTO, 'Despachos abiertos'); ?>
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li class="treeview">
                      <a href="#">Materia Prima
                        <span class="pull-left-container">
                          <?php echo $badgeRecepcion($DESPACHOMP_ABIERTO, 'Despachos MP abiertos'); ?>
                          <i class=" fa fa-angle-right pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li><a href="registroDespachomp.php">Registro Despacho<i class="ti-more"></i></a></li>
                        <li><a href="listarDespachomp.php">Agrupado Despacho<i class="ti-more"></i></a></li>
                        <li><a href="listarDespachompDetallado.php">Detallado Despacho<i class="ti-more"></i></a></li>
                      </ul>
                    </li>
                    <li class="treeview">
                      <a href="#">Industrial
                        <span class="pull-left-container">
                          <?php echo $badgeRecepcion($DESPACHOIND_ABIERTO, 'Despachos IND abiertos'); ?>
                          <i class=" fa fa-angle-right pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li><a href="registroDespachoind.php">Registro Despacho<i class="ti-more"></i></a></li>
                        <li><a href="listarDespachoind.php">Agrupado Despacho<i class="ti-more"></i></a></li>
                        <li><a href="listarDespachoindDetallado.php">Detallado Despacho<i class="ti-more"></i></a></li>
                      </ul>
                    </li>
                    <li><a href="listarDespachoGranelConsoliado.php">Consolidado Despacho<i class="ti-more"></i></a></li>
                  </ul>
                </li>      
              <?php  } ?>
              <?php if($PFGGUIA=="1"){ ?>
                <li class="treeview">
                  <a href="#">Guia Por Recibir
                    <span class="pull-left-container">
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroGuiaPorRecibirMP.php">Materia Prima<i class="ti-more"></i> </a></li>
                    <li><a href="registroGuiaPorRecibirIND.php">Inudstrial<i class="ti-more"></i></a></li>
                  </ul>
                </li>      
              <?php  } ?>
            </ul>
          </li>                 
        <?php  } ?>
        <?php if($PMATERIALES=="1"){ ?>
          <?php if($PMENVASE=="1"){ ?>
            <li class="treeview">
              <a href="#">
                <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/cards.svg" class="svg-icon" alt="">
                <span>Envases</span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-right pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">                
                    <?php if($PMERECEPCION=="1"){ ?>
                      <li class="treeview">
                        <a href="#">Recepcion  
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="registroRecepcione.php">Registro Recepcion<i class="ti-more"></i></a></li>
                          <li><a href="listarRecepcione.php">Agrupado Recepcion<i class="ti-more"></i></a></li>
                          <li><a href="listarRecepcioneDetallado.php">Detallado Recepcion<i class="ti-more"></i></a></li>
                          <li><a href="listarRecepcioneInterplanta.php">Agrupado Interplanta<i class="ti-more"></i></a></li>
                        </ul>
                      </li>
                    <?php  } ?>
                    <?php if($PMEDESPACHO=="1"){ ?>
                      <li class="treeview">
                        <a href="#">Despacho  
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="registroDespachoe.php">Registro Despacho<i class="ti-more"></i></a></li>
                            <li><a href="listarDespachoe.php">Agrupado Despacho<i class="ti-more"></i></a></li>
                            <li><a href="listarDespachoeDetallado.php">Detallado Despacho<i class="ti-more"></i></a></li>
                        </ul>
                      </li>
                    <?php  } ?>
                    <?php if($PMEGUIA=="1"){ ?>
                      <li class="treeview">
                        <a href="#">Guia Por Recibir  
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="registroGuiaPorRecibirE.php">Envases<i class="ti-more"></i></a></li>
                        </ul>
                      </li>
                    <?php  } ?>
                    <?php if($PMKARDEX=="1"){ ?>
                      <?php if($PMKENVASE=="1"){ ?>
                        <li><a href="listarHinventarioe.php">Kardex <i class="ti-more"></i></a></li>
                      <?php  } ?>
                    <?php  } ?>
              </ul>
            </li>              
          <?php  } ?>      
        <?php  } ?>
        <?php if($PFPACKING=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/forms2.svg" class="svg-icon" alt="">
              <span>Packing</span>
              <span class="pull-right-container">
                <?php echo $badgeRecepcion($PACKING_ABIERTO, 'Registros abiertos en Packing'); ?>
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if($PFPPROCESO=="1"){ ?>
                <li class="treeview">
                  <a href="#">Proceso
                    <span class="pull-left-container">
                      <?php echo $badgeRecepcion($PROCESO_ABIERTO, 'Procesos abiertos'); ?>
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroProceso.php">Registro Proceso<i class="ti-more"></i></a></li>
                    <li><a href="listarProceso.php">Agrupado Proceso<i class="ti-more"></i></a></li>
                  </ul>
                </li>    
              <?php  } ?>
              <?php if($PFPREEMBALEJE=="1"){ ?>
                <li class="treeview">
                  <a href="#">Reembalaje
                    <span class="pull-left-container">
                      <?php echo $badgeRecepcion($REEMBALAJE_ABIERTO, 'Reembalajes abiertos'); ?>
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroReembalajeEx.php"> Registro Reembalaje<i class="ti-more"></i></a></li>
                    <li><a href="listarReembalajeEx.php">Agrupado Reembalaje<i class="ti-more"></i></a></li>
                  </ul>
                </li>    
              <?php  } ?>
              <?php if($PFPPROCESO=="1"){ ?>
              <li class="treeview">
                  <a href="#">Repaletizaje
                    <span class="pull-left-container">
                      <?php echo $badgeRecepcion($REPALETIZAJE_ABIERTO, 'Repaletizajes abiertos'); ?>
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroRepaletizajePTFrigorifico.php">Registro Repaletizaje<i class="ti-more"></i></a></li>
                    <li><a href="listarRepaletizajePTFrigorifico.php">Agrupado Repaletizaje<i class="ti-more"></i></a></li>
                  </ul>
                </li> 
                <?php  } ?>
            </ul>
          </li>          
        <?php  } ?>
        <?php if($PEXPORTADORA=="1"){ ?>
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
                    <li><a href="registroICarga.php">Registro Inst. Carga<i class="ti-more"></i></a></li>
                    <li><a href="listarICarga.php">Agrupado Inst. Carga<i class="ti-more"></i></a></li>
                    <li><a href="listarICargaDetallado.php">Detallado Inst. Carga</i></a></li>
                  </ul>
                </li>
                <li class="treeview">
                  <a href="#">Nota D/C
                    <span class="pull-left-container">
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroNotadc.php">Registro Nota<i class="ti-more"></i></a></li>
                    <li><a href="listarNotadc.php">Agrupado Nota<i class="ti-more"></i></a></li>
                  </ul>
                </li>
              </ul>
            </li>        
          <?php  } ?>  
        <?php  } ?>
        <?php if($PFSAG=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/transactions.svg" class="svg-icon" alt="">
              <span> Operaciónes SAG</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if($PFSAGINSPECCION=="1"){ ?>
                <li class="treeview">
                  <a href="#">Inspección SAG
                    <span class="pull-left-container">
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroInpsag.php">Registro Inspección<i class="ti-more"></i></a></li>
                    <li><a href="listarInpsag.php">Agrupado Inspección<i class="ti-more"></i></a></li>
                  </ul>
                </li>  
              <?php  } ?>
            </ul>
          </li>          
        <?php  } ?>
        <?php if($PFFRIGORIFICO=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/maps.svg" class="svg-icon" alt="">
              <span> Frigorifico</span>
              <span class="pull-right-container">
                <?php echo $badgeRecepcion($FRIGORIFICO_ABIERTO, 'Registros abiertos en Frigorifico'); ?>
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <?php if($PFFRECEPCION=="1"){ ?>
                <?php $BADGE_RECEPCION_PT = $badgeRecepcion($RECEPCIONPT_ABIERTA, 'Recepciones PT abiertas'); ?>
                <li class="treeview">
                  <a href="#">Recepción P. Terminado
                    <span class="pull-left-container">
                      <?php echo $BADGE_RECEPCION_PT; ?>
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroRecepcionpt.php">Registro Recepción<i class="ti-more"></i></a></li>
                    <li><a href="listarRecepcionpt.php">Agrupado Recepción<i class="ti-more"></i></a></li>
                    <li><a href="listarRecepcionptDetallado.php">Detallado Recepción<i class="ti-more"></i></a></li>
                    <li><a href="listarRecepcionptInterplanta.php">Agrupado Interplanta<i class="ti-more"></i></a></li>
                  </ul>
                </li>     
              <?php  } ?>
              <?php if($PFFRDESPACHO=="1"){ ?>
                <li class="treeview">
                  <a href="#">Despacho
                    <span class="pull-left-container">
                      <?php echo $badgeRecepcion($DESPACHO_FRIGORIFICO_ABIERTO, 'Despachos PT/Expo abiertos'); ?>
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li class="treeview">
                      <a href="#">Despacho P. Terminado
                        <span class="pull-left-container">
                          <?php echo $badgeRecepcion($DESPACHOPT_ABIERTO, 'Despachos PT abiertos'); ?>
                          <i class=" fa fa-angle-right pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li><a href="registroDespachopt.php">Registro Despacho<i class="ti-more"></i></a></li>
                        <li><a href="listarDespachopt.php">Agrupado Despacho<i class="ti-more"></i></a></li>
                      </ul>
                    </li>
                    <li class="treeview">
                      <a href="#">Despacho Exportacion
                        <span class="pull-left-container">
                          <?php echo $badgeRecepcion($DESPACHOEXPO_ABIERTO, 'Despachos exportacion abiertos'); ?>
                          <i class=" fa fa-angle-right pull-right"></i>
                        </span>
                      </a>
                      <ul class="treeview-menu">
                        <li><a href="registroDespachoEX.php">Registro Despacho<i class="ti-more"></i></a></li>
                        <li><a href="listarDespachoEX.php">Agrupado Despacho<i class="ti-more"></i></a></li>
                      </ul>
                    </li>
                    <li><a href="listarDespachoptexConsolidado.php">Consolidado Despacho<i class="ti-more"></i></a></li>
                  </ul>
                </li>     
              <?php  } ?>
              <?php if($PFFRGUIA=="1"){ ?>
                <li class="treeview">
                  <a href="#">Guia Por Recibir
                    <span class="pull-left-container">
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroGuiaPorRecibirPT.php"> Producto Terminado<i class="ti-more"></i></a></li>
                  </ul>
                </li>     
              <?php  } ?>
              <?php if($PFFRREPALETIZAJE=="1"){ ?>
                <li class="treeview">
                  <a href="#">Repaletizaje
                    <span class="pull-left-container">
                      <?php echo $badgeRecepcion($REPALETIZAJE_ABIERTO, 'Repaletizajes abiertos'); ?>
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroRepaletizajePTFrigorifico.php">Registro Repaletizaje<i class="ti-more"></i></a></li>
                    <li><a href="listarRepaletizajePTFrigorifico.php">Agrupado Repaletizaje<i class="ti-more"></i></a></li>
                  </ul>
                </li>     
              <?php  } ?>
              <?php if($PFFRPC=="1"){ ?>
                <li class="treeview">
                  <a href="#">Planificador Carga PT
                    <span class="pull-left-container">
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroPcdespacho.php"> Registro PC PT<i class="ti-more"></i></a></li>
                    <li><a href="listarPcdespacho.php">Agrupado PC PT<i class="ti-more"></i></a></li>
                  </ul>
                </li> 
                
                <li class="treeview">
                  <a href="#">Planificador Carga MP
                    <span class="pull-left-container">
                      <i class=" fa fa-angle-right pull-right"></i>
                    </span>
                  </a>
                  <ul class="treeview-menu">
                    <li><a href="registroPcMP.php"> Registro PC MP<i class="ti-more"></i></a></li>
                    <li><a href="listarPcMP.php">Agrupado PC MP<i class="ti-more"></i></a></li>
                    <li><a href="listarDetalladoPcMP.php">Detallado PC MP<i class="ti-more"></i></a></li>
                  </ul>
                </li> 
              <?php  } ?>
            </ul>
          </li>
        <?php  } ?>
        <?php if($PFFRCFOLIO=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/apps.svg" class="svg-icon" alt="">
              <span> Gestion de folios</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <li><a href="registroGestionFolioMp.php">Folio Materia Prima<i class="ti-more"></i></a></li>
              <li><a href="registroCambiarFolioPT.php">Cambiar Folio P. Terminado<i class="ti-more"></i></a></li>
            </ul>
          </li>
        <?php  } ?>
        <?php if($PFCFRUTA=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/reports.svg" class="svg-icon" alt="">
              <span> Calidad de la fruta</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">              
                <?php if($PFCFRECHAZO=="1"){ ?>
                  <li class="treeview">
                    <a href="#">Rechazo  
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">
                      <li class="treeview">
                        <a href="#">Materia Prima
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="registroRechazomp.php">Registro Rechazo<i class="ti-more"></i></a></li>
                          <li><a href="listarRechazomp.php">Agrupado Rechazo<i class="ti-more"></i></a></li>
                          <li><a href="listarRechazompDetallado.php">Detallado Rechazo<i class="ti-more"></i></a></li>
                        </ul>
                      </li>                  
                      <li class="treeview">
                        <a href="#">Producto Terminado
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="registroRechazopt.php">Registro Rechazo<i class="ti-more"></i></a></li>
                          <li><a href="listarRechazopt.php">Agrupado Rechazo<i class="ti-more"></i></a></li>
                          <li><a href="listarRechazoptDetallado.php">Detallado Rechazo<i class="ti-more"></i></a></li>
                        </ul>
                      </li>                
                    </ul>
                  </li> 
                  <li class="treeview">
                    <a href="#">Levantamiento  
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">
                      <li class="treeview">
                        <a href="#">Materia Prima
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="registroLevantamientomp.php">Registro Levantamiento<i class="ti-more"></i></a></li>
                          <li><a href="listarLevantamientomp.php">Agrupado Levantamiento<i class="ti-more"></i></a></li>
                          <li><a href="listarLevantamientompDetallado.php">Detallado Levantamiento<i class="ti-more"></i></a></li>
                        </ul>
                      </li>                  
                      <li class="treeview">
                        <a href="#">Producto Terminado
                          <span class="pull-left-container">
                            <i class=" fa fa-angle-right pull-right"></i>
                          </span>
                        </a>
                        <ul class="treeview-menu">
                          <li><a href="registroLevantamientopt.php">Registro Levantamiento<i class="ti-more"></i></a></li>
                          <li><a href="listarLevantamientopt.php">Agrupado Levantamiento<i class="ti-more"></i></a></li>
                          <li><a href="listarLevantamientoptDetallado.php">Detallado Levantamiento<i class="ti-more"></i></a></li>
                        </ul>
                      </li>                
                    </ul>
                  </li> 
                  <li><a href="listarExiRegistroCalidad.php">Registro de Calidad<i class="ti-more"></i></a></li>
                  <li><a href="listarResumenRegistroCalidad.php">Agrupado Registro de Calidad<i class="ti-more"></i></a></li>
                  <li><a href="listarProductorDocumento.php">Documentos por Productor<i class="ti-more"></i></a></li>
                <?php  } ?>
                <?php if($PFCFLEVANTAMIENTO=="1"){ ?>
                  <!--
                  <li class="treeview">
                    <a href="#">Levantamiento  
                      <span class="pull-left-container">
                        <i class=" fa fa-angle-right pull-right"></i>
                      </span>
                    </a>
                    <ul class="treeview-menu">
                      <li class="treeview">
                          <a href="#">Materia Prima
                            <span class="pull-left-container">
                              <i class=" fa fa-angle-right pull-right"></i>
                            </span>
                          </a>
                          <ul class="treeview-menu">
                            <li><a href="#">Registro Levantamiento<i class="ti-more"></i></a></li>
                            <li><a href="#">Agrupado Levantamiento<i class="ti-more"></i></a></li>
                            <li><a href="#">Detallado Levantamiento<i class="ti-more"></i></a></li>
                          </ul>
                        </li>
                        <li class="treeview">
                          <a href="#">Producto Terminado
                            <span class="pull-left-container">
                              <i class=" fa fa-angle-right pull-right"></i>
                            </span>
                          </a>
                          <ul class="treeview-menu">
                            <li><a href="#">Registro Levantamiento<i class="ti-more"></i></a></li>
                            <li><a href="#">Agrupado Levantamiento<i class="ti-more"></i></a></li>
                            <li><a href="#">Detallado Levantamiento<i class="ti-more"></i></a></li>
                          </ul>
                        </li>
                    </ul>
                  </li> 
                  -->              
                <?php  } ?>       
            </ul>
          </li>           
        <?php  } ?>
        <?php if($PFEXISTENCIA=="1"){ ?>
          <li class="treeview">
            <a href="#">
              <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/pages.svg" class="svg-icon" alt="">
              <span>Existencia</span>
              <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
            <li class="treeview" style="display:none;">
                <a href="#">En Desarrollo
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="listarProductoTerminadoCompleto.php">PT Completo<i class="ti-more"></i></a></li>
                  <li><a href="listarProductoTerminadoIncompleto.php">PT Incompleto<i class="ti-more"></i></a></li>
                  <li><a href="listarProductoTerminadoMuestras.php">PT Muestras<i class="ti-more"></i></a></li>
                </ul>
              </li>
              <li class="treeview">
                <a href="#">Disponible
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="listarEximateriaprima.php">Materia Prima<i class="ti-more"></i></a></li>
                  <li><a href="listarExiexportacion.php">Producto Terminado<i class="ti-more"></i></a></li>
                  <li><a href="listarExiIndexportacion.php">Ind Producto Terminado<i class="ti-more"></i></a></li>
                  <li><a href="listarExiindustrial.php">Producto Industrial<i class="ti-more"></i></a></li>
                  <li><a href="listarExiexportacionAgrupado.php">Existencia PT Resumen<i class="ti-more"></i></a></li>
                  
                </ul>
              </li>
              <li class="treeview">
                <a href="#">Despachado
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="listarEximateriaprimaDespachado.php">Materia Prima<i class="ti-more"></i></a></li>
                  <li><a href="listarExiexportacionDespachado.php">Producto Terminado<i class="ti-more"></i></a></li>
                  <li><a href="listarExiindustrialDespachado.php">Producto Industrial<i class="ti-more"></i></a></li>
                </ul>
              </li>        
              <li class="treeview">
                <a href="#">Historial
                  <span class="pull-left-container">
                    <i class=" fa fa-angle-right pull-right"></i>
                  </span>
                </a>
                <ul class="treeview-menu">
                  <li><a href="listarHEximateriaprima.php"> Materia Prima<i class="ti-more"></i></a></li>
                  <li><a href="listarHExiexportacion.php"> Producto Terminado<i class="ti-more"></i></a></li>
                  <li><a href="listarHExiindustrial.php">Producto Industrial<i class="ti-more"></i></a></li>
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
                <?php if($PFGRANEL=="1"){ ?>     
                  <?php if($PFGRECEPCION=="1"){ ?>
                    <li><a href="listarAPrecepcionmp.php">Recepcion Materia Prima </a></li>
                    <li><a href="listarAPrecepcionind.php">Recepcion Producto Industrial </a></li>
                  <?php  } ?>         
                  <?php if($PFGDESPACHO=="1"){ ?>
                    <li><a href="listarAPdespachomp.php">Despacho Materia Prima </a></li>
                    <li><a href="listarAPdespachoind.php">Despacho Producto Industrial </a></li>
                  <?php  } ?>    
                <?php  } ?>    
                <?php if($PFPACKING=="1"){ ?>
                  <?php if($PFPPROCESO=="1"){ ?>
                    <li><a href="listarAPproceso.php">Proceso </a></li>
                  <?php  } ?>       
                  <?php if($PFPREEMBALEJE=="1"){ ?>
                    <li><a href="listarAPreembalajeEx.php">Reembalaje </a></li>
                  <?php  } ?>    
                <?php  } ?>    
                <?php if($PFSAG=="1"){ ?>
                  <?php if($PFSAGINSPECCION=="1"){ ?>
                    <li><a href="listarAPInpSag.php">Inspección SAG </a></li>
                  <?php  } ?>      
                <?php  } ?>    
                <?php if($PFFRIGORIFICO=="1"){ ?>     
                  <?php if($PFFRECEPCION=="1"){ ?>
                    <li><a href="listarAPrecepcionpt.php">Recepcion Producto Terminado </a></li>
                  <?php  } ?>         
                  <?php if($PFFRDESPACHO=="1"){ ?>
                    <li><a href="listarAPdespachopt.php">Despacho Producto Terminado </a></li>
                    <li><a href="listarAPdespachoEX.php">Despacho Exportacion </a></li>
                  <?php  } ?>    
                  <?php if($PFFRREPALETIZAJE=="1"){ ?>
                    <li><a href="listarAPrepaletizajePTFrigorifico.php"> Repaletizaje </a></li>
                  <?php  } ?>   
                  <?php if($PFFRREPALETIZAJE=="1"){ ?>
                    <li><a href="listarAPpcdespacho.php"> Planificador Carga </a></li>
                  <?php  } ?>   
                <?php  } ?>                   
                <?php if($PFCFRUTA=="1"){ ?>
                  <?php if($PFCFRECHAZO=="1"){ ?>
                    <li><a href="listarAPrechazomp.php"> Rechazo Materia Prima </a></li>
                    <li><a href="listarAPrechazopt.php"> Rechazo Producto Terminado </a></li>
                  <?php  } ?>   
                <?php  } ?>   
              </ul>
            </li> 
          <?php  } ?>  
        <?php  } ?>   
      <?php  } ?> 
    </ul>
  </section>
</aside>