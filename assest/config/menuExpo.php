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
  .main-sidebar .sidebar { overflow-y:auto !important; overflow-x:hidden !important; }
  .main-sidebar .treeview-menu.scrollable { max-height:55vh; overflow-y:auto !important; overflow-x:hidden; }
  .main-sidebar .treeview-menu.scrollable::-webkit-scrollbar { width:4px; }
  .main-sidebar .treeview-menu.scrollable::-webkit-scrollbar-thumb { background:#c5d5e8; border-radius:4px; }
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
  var modBtn = document.querySelector('.module-switcher-btn');
  var modPanel = document.getElementById('modulePanel');
  if (modBtn && modPanel) {
    modBtn.addEventListener('click', function(e){ e.stopPropagation(); modPanel.classList.toggle('open'); });
  }
  document.addEventListener('click', function(){
    if (modPanel) modPanel.classList.remove('open');
  });
})();
</script>
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

      <!-- Inicio -->
      <li class="<?php echo _sb(['index.php','../../configuracion/']); ?>">
        <a href="<?php echo $MENU_CONFIGURACION ? '../../configuracion/' : 'index.php'; ?>">
          <i class="fa fa-home"></i><span>Inicio</span>
        </a>
      </li>

      <?php if (!$MENU_CONFIGURACION && $PEXPORTADORA == "1"): ?>

        <?php if ($PEEXPORTACION == "1"): ?>
        <li class="header">Exportación</li>

        <li class="<?php echo _sb('listarICarga.php'); ?>">
          <a href="listarICarga.php"><i class="fa fa-ship"></i><span>Instructivos</span></a>
        </li>
        <li class="<?php echo _sb('registroInvoiceExp.php'); ?>">
          <a href="registroInvoiceExp.php"><i class="fa fa-file-text-o"></i><span>Invoice</span></a>
        </li>
        <li class="<?php echo _sbTree(['registroNotadc.php','listarNotadc.php']); ?>">
          <a href="#">
            <i class="fa fa-exchange"></i><span>Nota D/C</span>
            <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <li class="<?php echo _sb('registroNotadc.php'); ?>"><a href="registroNotadc.php">Registro</a></li>
            <li class="<?php echo _sb('listarNotadc.php'); ?>"><a href="listarNotadc.php">Listado</a></li>
          </ul>
        </li>
        <li class="<?php echo _sb('registroIvvExp.php'); ?>">
          <a href="registroIvvExp.php"><i class="fa fa-clipboard"></i><span>Registro IVV</span></a>
        </li>
        <?php endif; ?>

        <?php if ($PELIQUIDACION == "1"): ?>
        <li class="header">Liquidación</li>

        <li class="<?php echo _sb('registroLiquidacionExp.php'); ?>">
          <a href="registroLiquidacionExp.php"><i class="fa fa-calculator"></i><span>Liquidaciones</span></a>
        </li>
        <li class="<?php echo _sb('cuentaCorrienteBroker.php'); ?>">
          <a href="cuentaCorrienteBroker.php"><i class="fa fa-credit-card"></i><span>Cta. Cte. Broker</span></a>
        </li>
        <li class="<?php echo _sb('registroPresupuestoFob.php'); ?>">
          <a href="registroPresupuestoFob.php"><i class="fa fa-bar-chart"></i><span>Presupuesto FOB</span></a>
        </li>
        <li class="<?php echo _sb('registroTitemLiqui.php'); ?>">
          <a href="registroTitemLiqui.php"><i class="fa fa-list"></i><span>Ítems Liquidación</span></a>
        </li>
        <?php endif; ?>

        <?php if ($PADMINISTRADOR == "1" && $PADAPERTURA == "1"): ?>
        <li class="header">Administración</li>

        <li class="<?php echo _sbTree(['listarAPICargaExp.php','listarAPInvoiceExp.php','listarAPNotaDcExp.php','listarAPLiquidacionExp.php']); ?>">
          <a href="#">
            <i class="fa fa-unlock-alt"></i><span>Apertura Registro</span>
            <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
          </a>
          <ul class="treeview-menu">
            <?php if ($PEEXPORTACION == "1"): ?>
            <li class="<?php echo _sb('listarAPICargaExp.php'); ?>"><a href="listarAPICargaExp.php">Instructivos</a></li>
            <li class="<?php echo _sb('listarAPInvoiceExp.php'); ?>"><a href="listarAPInvoiceExp.php">Invoice</a></li>
            <li class="<?php echo _sb('listarAPNotaDcExp.php'); ?>"><a href="listarAPNotaDcExp.php">Notas D/C</a></li>
            <?php endif; ?>
            <?php if ($PELIQUIDACION == "1"): ?>
            <li class="<?php echo _sb('listarAPLiquidacionExp.php'); ?>"><a href="listarAPLiquidacionExp.php">Liquidaciones</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>

        <?php if ($PMANTENEDORES == "1" && $PEEXPORTACION == "1"): ?>
        <li class="header">Configuración</li>

        <li class="<?php echo _sbTree(['registroExportadora.php','registroConsignatorio.php','registroBroker.php','registroRfinal.php','registroAaduana.php','registroAgcarga.php','registroDfinal.php','registroNotificador.php','registroSeguro.php','registroAtmosfera.php','registroEmisionbl.php','registroLdestino.php','registroPdestino.php','registroAdestino.php','registroLcarga.php','registroPcarga.php','registroAcarga.php','registroFpago.php','registroCventa.php','registroMventa.php','registroMercado.php','registroRmercado.php','registroTcontenedor.php','registroTflete.php','registroTmoneda.php','registroTservicio.php','registroTmanejo.php']); ?>">
          <a href="#">
            <i class="fa fa-cog"></i><span>Config. Exportadora</span>
            <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
          </a>
          <ul class="treeview-menu scrollable">
            <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Actores</li>
            <li class="<?php echo _sb('registroExportadora.php'); ?>"><a href="registroExportadora.php">Exportadora</a></li>
            <li class="<?php echo _sb('registroConsignatorio.php'); ?>"><a href="registroConsignatorio.php">Consignatorio</a></li>
            <li class="<?php echo _sb('registroBroker.php'); ?>"><a href="registroBroker.php">Cliente</a></li>
            <li class="<?php echo _sb('registroRfinal.php'); ?>"><a href="registroRfinal.php">Recibidor Final</a></li>
            <li class="<?php echo _sb('registroAaduana.php'); ?>"><a href="registroAaduana.php">Agente Aduana</a></li>
            <li class="<?php echo _sb('registroAgcarga.php'); ?>"><a href="registroAgcarga.php">Agente Carga</a></li>
            <li class="<?php echo _sb('registroDfinal.php'); ?>"><a href="registroDfinal.php">Destino Final</a></li>
            <li class="<?php echo _sb('registroNotificador.php'); ?>"><a href="registroNotificador.php">Notificador</a></li>
            <li class="<?php echo _sb('registroSeguro.php'); ?>"><a href="registroSeguro.php">Seguro</a></li>
            <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Rutas</li>
            <li class="<?php echo _sb('registroLdestino.php'); ?>"><a href="registroLdestino.php">Lugar Destino</a></li>
            <li class="<?php echo _sb('registroPdestino.php'); ?>"><a href="registroPdestino.php">Puerto Destino</a></li>
            <li class="<?php echo _sb('registroAdestino.php'); ?>"><a href="registroAdestino.php">Aeropuerto Destino</a></li>
            <li class="<?php echo _sb('registroLcarga.php'); ?>"><a href="registroLcarga.php">Lugar Carga</a></li>
            <li class="<?php echo _sb('registroPcarga.php'); ?>"><a href="registroPcarga.php">Puerto Carga</a></li>
            <li class="<?php echo _sb('registroAcarga.php'); ?>"><a href="registroAcarga.php">Aeropuerto Carga</a></li>
            <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Comercio</li>
            <li class="<?php echo _sb('registroFpago.php'); ?>"><a href="registroFpago.php">Formato Pago</a></li>
            <li class="<?php echo _sb('registroCventa.php'); ?>"><a href="registroCventa.php">Cláusula Venta</a></li>
            <li class="<?php echo _sb('registroMventa.php'); ?>"><a href="registroMventa.php">Modalidad Venta</a></li>
            <li class="<?php echo _sb('registroMercado.php'); ?>"><a href="registroMercado.php">Mercado</a></li>
            <li class="<?php echo _sb('registroRmercado.php'); ?>"><a href="registroRmercado.php">Restricción Mercado</a></li>
            <li class="<?php echo _sb('registroAtmosfera.php'); ?>"><a href="registroAtmosfera.php">Atmósfera</a></li>
            <li class="<?php echo _sb('registroEmisionbl.php'); ?>"><a href="registroEmisionbl.php">Emisión BL</a></li>
            <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Tipos</li>
            <li class="<?php echo _sb('registroTcontenedor.php'); ?>"><a href="registroTcontenedor.php">Contenedor</a></li>
            <li class="<?php echo _sb('registroTflete.php'); ?>"><a href="registroTflete.php">Flete</a></li>
            <li class="<?php echo _sb('registroTmoneda.php'); ?>"><a href="registroTmoneda.php">Moneda</a></li>
            <li class="<?php echo _sb('registroTservicio.php'); ?>"><a href="registroTservicio.php">Servicio</a></li>
            <li class="<?php echo _sb('registroTmanejo.php'); ?>"><a href="registroTmanejo.php">Manejo</a></li>
          </ul>
        </li>
        <?php endif; ?>

      <?php endif; ?>

      <?php if ($MENU_CONFIGURACION && $PMANTENEDORES == "1"): ?>
      <li class="header">Configuraciones</li>

      <li class="<?php echo _sbTree(['registroEmpresa.php','registroPlanta.php','registroTemporada.php','registroFolio.php','registroProductor.php','registroVespecies.php','registroEspecies.php','registroCuartel.php','registroTetiqueta.php','registroTembalaje.php','registroTcalibre.php','registroTcalibreind.php','registroErecepcion.php','registroEexportacion.php','registroEcomercial.php','registroEindustrial.php','registroCiudad.php','registroComuna.php','registroProvincia.php','registroRegion.php','registroPais.php','registroLaerea.php','registroNaviera.php','registroTransporte.php','registroConductor.php','registroTproductor.php','registroTproceso.php','registroTreembalaje.php','registroTcontenedor.php','registroTflete.php','registroTmoneda.php','registroTservicio.php','registroTmanejo.php','registroTinpsag.php','registroTtratamiento1.php','registroTtratamiento2.php','registroTcategoria.php','registroTcolor.php','registroCcalidad.php','registroContraparte.php','registroInpector.php','registroComprador.php']); ?>">
        <a href="#">
          <i class="fa fa-cog"></i><span>Configuración App</span>
          <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
        </a>
        <ul class="treeview-menu scrollable">
          <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Principal</li>
          <li class="<?php echo _sb('registroEmpresa.php'); ?>"><a href="registroEmpresa.php">Empresa</a></li>
          <li class="<?php echo _sb('registroPlanta.php'); ?>"><a href="registroPlanta.php">Planta</a></li>
          <li class="<?php echo _sb('registroTemporada.php'); ?>"><a href="registroTemporada.php">Temporada</a></li>
          <li class="<?php echo _sb('registroFolio.php'); ?>"><a href="registroFolio.php">Folio</a></li>
          <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Fruta</li>
          <li class="<?php echo _sb('registroProductor.php'); ?>"><a href="registroProductor.php">Productor</a></li>
          <li class="<?php echo _sb('registroVespecies.php'); ?>"><a href="registroVespecies.php">Variedad</a></li>
          <li class="<?php echo _sb('registroEspecies.php'); ?>"><a href="registroEspecies.php">Especies</a></li>
          <li class="<?php echo _sb('registroCuartel.php'); ?>"><a href="registroCuartel.php">Cuartel</a></li>
          <li class="<?php echo _sb('registroTetiqueta.php'); ?>"><a href="registroTetiqueta.php">Etiqueta</a></li>
          <li class="<?php echo _sb('registroTembalaje.php'); ?>"><a href="registroTembalaje.php">Embalaje</a></li>
          <li class="<?php echo _sb('registroTcalibre.php'); ?>"><a href="registroTcalibre.php">Calibre</a></li>
          <li class="<?php echo _sb('registroTcalibreind.php'); ?>"><a href="registroTcalibreind.php">Calibre Industrial</a></li>
          <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Estándares</li>
          <li class="<?php echo _sb('registroErecepcion.php'); ?>"><a href="registroErecepcion.php">Granel</a></li>
          <li class="<?php echo _sb('registroEexportacion.php'); ?>"><a href="registroEexportacion.php">Exportación</a></li>
          <li class="<?php echo _sb('registroEcomercial.php'); ?>"><a href="registroEcomercial.php">Expo. Comercial</a></li>
          <li class="<?php echo _sb('registroEindustrial.php'); ?>"><a href="registroEindustrial.php">Industrial</a></li>
          <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Ubicación</li>
          <li class="<?php echo _sb('registroCiudad.php'); ?>"><a href="registroCiudad.php">Ciudad</a></li>
          <li class="<?php echo _sb('registroComuna.php'); ?>"><a href="registroComuna.php">Comuna</a></li>
          <li class="<?php echo _sb('registroProvincia.php'); ?>"><a href="registroProvincia.php">Provincia</a></li>
          <li class="<?php echo _sb('registroRegion.php'); ?>"><a href="registroRegion.php">Región</a></li>
          <li class="<?php echo _sb('registroPais.php'); ?>"><a href="registroPais.php">País</a></li>
          <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Transporte</li>
          <li class="<?php echo _sb('registroLaerea.php'); ?>"><a href="registroLaerea.php">Línea Aérea</a></li>
          <li class="<?php echo _sb('registroNaviera.php'); ?>"><a href="registroNaviera.php">Naviera</a></li>
          <li class="<?php echo _sb('registroTransporte.php'); ?>"><a href="registroTransporte.php">Transporte</a></li>
          <li class="<?php echo _sb('registroConductor.php'); ?>"><a href="registroConductor.php">Conductor</a></li>
          <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Tipos</li>
          <li class="<?php echo _sb('registroTproductor.php'); ?>"><a href="registroTproductor.php">Tipo Productor</a></li>
          <li class="<?php echo _sb('registroTproceso.php'); ?>"><a href="registroTproceso.php">Tipo Proceso</a></li>
          <li class="<?php echo _sb('registroTreembalaje.php'); ?>"><a href="registroTreembalaje.php">Tipo Reembalaje</a></li>
          <li class="<?php echo _sb('registroTcontenedor.php'); ?>"><a href="registroTcontenedor.php">Tipo Contenedor</a></li>
          <li class="<?php echo _sb('registroTflete.php'); ?>"><a href="registroTflete.php">Tipo Flete</a></li>
          <li class="<?php echo _sb('registroTmoneda.php'); ?>"><a href="registroTmoneda.php">Tipo Moneda</a></li>
          <li class="<?php echo _sb('registroTservicio.php'); ?>"><a href="registroTservicio.php">Tipo Servicio</a></li>
          <li class="<?php echo _sb('registroTmanejo.php'); ?>"><a href="registroTmanejo.php">Tipo Manejo</a></li>
          <li class="<?php echo _sb('registroTinpsag.php'); ?>"><a href="registroTinpsag.php">Tipo Insp. SAG</a></li>
          <li class="<?php echo _sb('registroTtratamiento1.php'); ?>"><a href="registroTtratamiento1.php">Tratamiento 1</a></li>
          <li class="<?php echo _sb('registroTtratamiento2.php'); ?>"><a href="registroTtratamiento2.php">Tratamiento 2</a></li>
          <li class="<?php echo _sb('registroTcategoria.php'); ?>"><a href="registroTcategoria.php">Tipo Categoría</a></li>
          <li class="<?php echo _sb('registroTcolor.php'); ?>"><a href="registroTcolor.php">Tipo Color</a></li>
          <li class="header" style="padding:8px 10px 4px 46px;font-size:9px;">Otros</li>
          <li class="<?php echo _sb('registroCcalidad.php'); ?>"><a href="registroCcalidad.php">Color Calidad</a></li>
          <li class="<?php echo _sb('registroContraparte.php'); ?>"><a href="registroContraparte.php">Contraparte</a></li>
          <li class="<?php echo _sb('registroInpector.php'); ?>"><a href="registroInpector.php">Inspector</a></li>
          <li class="<?php echo _sb('registroComprador.php'); ?>"><a href="registroComprador.php">Comprador</a></li>
        </ul>
      </li>
      <?php endif; ?>

      <?php if ($MENU_CONFIGURACION && $PADMINISTRADOR == "1"): ?>
      <li class="header">Usuarios y Sistema</li>

      <?php if ($PADUSUARIO == "1"): ?>
      <li class="<?php echo _sbTree(['registroUsuario.php','listarAusuario.php','registroTusuario.php','registroPtusuario.php','registroUsuarioEmpPro.php']); ?>">
        <a href="#">
          <i class="fa fa-users"></i><span>Usuarios</span>
          <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
        </a>
        <ul class="treeview-menu">
          <li class="<?php echo _sb('registroUsuario.php'); ?>"><a href="registroUsuario.php">Registro</a></li>
          <li class="<?php echo _sb('listarAusuario.php'); ?>"><a href="listarAusuario.php">Historial</a></li>
          <li class="<?php echo _sb('registroTusuario.php'); ?>"><a href="registroTusuario.php">Tipo Usuario</a></li>
          <li class="<?php echo _sb('registroPtusuario.php'); ?>"><a href="registroPtusuario.php">Privilegios</a></li>
          <li class="<?php echo _sb('registroUsuarioEmpPro.php'); ?>"><a href="registroUsuarioEmpPro.php">Usu. Asoc. Empresa</a></li>
        </ul>
      </li>
      <?php endif; ?>

      <?php if ($PADAVISO == "1"): ?>
      <li class="<?php echo _sb('registroUsuarioAviso.php'); ?>">
        <a href="registroUsuarioAviso.php"><i class="fa fa-bell-o"></i><span>Avisos</span></a>
      </li>
      <li class="<?php echo _sbTree(['cronPt.php','cronEjecutados.php']); ?>">
        <a href="#">
          <i class="fa fa-clock-o"></i><span>Cron</span>
          <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
        </a>
        <ul class="treeview-menu">
          <li class="<?php echo _sb('cronPt.php'); ?>"><a href="cronPt.php">Cron PT</a></li>
          <li class="<?php echo _sb('cronEjecutados.php'); ?>"><a href="cronEjecutados.php">Ejecutados</a></li>
        </ul>
      </li>
      <?php endif; ?>

      <?php endif; ?>

    </ul>
  </section>
</aside>





