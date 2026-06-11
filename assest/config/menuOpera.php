<?php
if (!isset($HEADER_MODULO_TITULO)) {
  $HEADER_MODULO_TITULO = 'Estadística';
}
if (!isset($HEADER_MODULO_URL)) {
  $HEADER_MODULO_URL = 'index.php';
}
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
  /* ── sidebar scroll fix ─────────────────────────── */
  .main-sidebar {
    top: 76px !important;
    padding-top: 0 !important;
    height: calc(100vh - 76px) !important;
    overflow: hidden !important;
  }
  .main-sidebar .slimScrollDiv { height: 100% !important; }
  .main-sidebar .sidebar {
    height: 100% !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
  }
  /* ── sidebar redesign ─────────────────────────── */
  .main-sidebar { background:#fff; border-right:1px solid #e8eef6; }
  .main-sidebar .sidebar-menu > li > a {
    padding:10px 16px 10px 18px; font-size:13px; font-weight:500;
    color:#3a4a5c; display:flex; align-items:center; gap:10px;
    border-left:3px solid transparent; transition:background .14s, border-color .14s, color .14s;
  }
  .main-sidebar .sidebar-menu > li > a > i.fa {
    width:17px; text-align:center; font-size:13px; color:#8fa3bc; flex-shrink:0;
  }
  .main-sidebar .sidebar-menu > li > a:hover { background:#f0f6ff; border-left-color:#0a3a6a; color:#0a3a6a; }
  .main-sidebar .sidebar-menu > li > a:hover > i.fa { color:#0a3a6a; }
  .main-sidebar .sidebar-menu > li.active > a {
    background:#e8f0fe !important; border-left-color:#0a3a6a !important;
    color:#0a3a6a !important; font-weight:700;
  }
  .main-sidebar .sidebar-menu > li.active > a > i.fa { color:#0a3a6a !important; }
  .main-sidebar .sidebar-menu li.header {
    font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
    color:#9baec8; padding:16px 18px 5px; background:none; border:none;
  }
  .main-sidebar .sidebar-menu li.header:first-child { padding-top:10px; }
  .main-sidebar .treeview-menu { background:#f8fbff !important; padding:3px 0 5px !important; }
  .main-sidebar .treeview-menu > li > a {
    padding:7px 10px 7px 46px !important; font-size:12px !important;
    color:#4a5568 !important; border-left:3px solid transparent;
    transition:background .14s, border-color .14s, color .14s;
  }
  .main-sidebar .treeview-menu > li > a:hover,
  .main-sidebar .treeview-menu > li.active > a {
    background:#deeafe !important; border-left-color:#0a3a6a !important;
    color:#0a3a6a !important; font-weight:600;
  }
  .main-sidebar .treeview > a > .pull-right-container { margin-left:auto; }
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
$_sbPage = basename($_SERVER['PHP_SELF']);
function _sb($pages) {
    global $_sbPage;
    return in_array($_sbPage, (array)$pages) ? 'active' : '';
}
function _sbTree($pages) {
    global $_sbPage;
    return in_array($_sbPage, (array)$pages) ? 'treeview active menu-open' : 'treeview';
}
?>
<aside class="main-sidebar">
  <section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">

      <li class="<?php echo _sb('index.php'); ?>">
        <a href="index.php"><i class="fa fa-home"></i><span>Inicio</span></a>
      </li>

      <?php if($PESTADISTICA=="1"): ?>
        <li class="header">Módulo</li>

        <?php if($PESTAPRODUCTOR=="1"): ?>
        <li class="<?php echo _sbTree(['listarProductorRecepcionmp.php','listarRecepcionmpDetalladoProductor.php','listarProductorRecepcionind.php','listarRecepcionindDetalladoProductor.php','listarProductorProceso.php']); ?>">
          <a href="#">
            <i class="fa fa-file-text-o"></i><span>Informe Productor</span>
            <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?php echo _sb('listarProductorRecepcionmp.php'); ?>"><a href="listarProductorRecepcionmp.php">Recepción MP</a></li>
            <li class="<?php echo _sb('listarRecepcionmpDetalladoProductor.php'); ?>"><a href="listarRecepcionmpDetalladoProductor.php">Detallado Recepción MP</a></li>
            <li class="<?php echo _sb('listarProductorRecepcionind.php'); ?>"><a href="listarProductorRecepcionind.php">Recepción IND</a></li>
            <li class="<?php echo _sb('listarRecepcionindDetalladoProductor.php'); ?>"><a href="listarRecepcionindDetalladoProductor.php">Detallado Recepción IND</a></li>
            <li class="<?php echo _sb('listarProductorProceso.php'); ?>"><a href="listarProductorProceso.php">Proceso</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if($PESTAINFORME=="1"): ?>
        <li class="<?php echo _sbTree(['listarProceso.php','listarReembalajeEx.php']); ?>">
          <a href="#">
            <i class="fa fa-bar-chart"></i><span>Informe</span>
            <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?php echo _sb('listarProceso.php'); ?>"><a href="listarProceso.php">Proceso</a></li>
            <li class="<?php echo _sb('listarReembalajeEx.php'); ?>"><a href="listarReembalajeEx.php">Reembalaje</a></li>
          </ul>
        </li>
        <li class="<?php echo _sbTree(['listarRecepcionesDetallado.php','listarDespachosDetallado.php']); ?>">
          <a href="#">
            <i class="fa fa-list-alt"></i><span>Detallado</span>
            <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?php echo _sb('listarRecepcionesDetallado.php'); ?>"><a href="listarRecepcionesDetallado.php">Recepciones</a></li>
            <li class="<?php echo _sb('listarDespachosDetallado.php'); ?>"><a href="listarDespachosDetallado.php">Despachos</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if($PESTAEXISTENCIA=="1"): ?>
        <li class="<?php echo _sbTree(['listarEximateriaprima.php','listarExiexportacion.php','listarExiindustrial.php']); ?>">
          <a href="#">
            <i class="fa fa-cubes"></i><span>Existencia</span>
            <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?php echo _sb('listarEximateriaprima.php'); ?>"><a href="listarEximateriaprima.php">Materia Prima</a></li>
            <li class="<?php echo _sb('listarExiexportacion.php'); ?>"><a href="listarExiexportacion.php">Producto Terminado</a></li>
            <li class="<?php echo _sb('listarExiindustrial.php'); ?>"><a href="listarExiindustrial.php">Producto Industrial</a></li>
          </ul>
        </li>
        <?php endif; ?>

        <?php if($PESTAPRODUCTOR=="1"): ?>
        <li class="<?php echo _sb('listaDocumento.php'); ?>">
          <a href="listaDocumento.php"><i class="fa fa-folder-open-o"></i><span>Mis Documentos</span></a>
        </li>
        <?php endif; ?>

      <?php endif; ?>
    </ul>
  </section>
</aside>
