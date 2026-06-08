<?php
chdir(__DIR__);
include_once __DIR__ . "/../../assest/config/validarUsuarioConfiguracion.php";

$accesos = array();

if ($PMANTENEDORES == "1") {
    $accesos[] = array(
        "titulo" => "Configuración App",
        "descripcion" => "Empresa, planta, temporada, ubicación, fruta, estándares y tipos.",
        "icono" => "tune",
        "url" => "../../exportadora/vista/registroEmpresa.php",
        "meta" => "Parámetros base"
    );
}

if ($PADMINISTRADOR == "1" && $PADUSUARIO == "1") {
    $accesos[] = array(
        "titulo" => "Usuarios",
        "descripcion" => "Usuarios, perfiles, privilegios e historial de actividad.",
        "icono" => "admin_panel_settings",
        "url" => "../../exportadora/vista/registroUsuario.php",
        "meta" => "Acceso y seguridad"
    );
}

if ($PADMINISTRADOR == "1" && $PADAVISO == "1") {
    $accesos[] = array(
        "titulo" => "Avisos y Cron",
        "descripcion" => "Registro de avisos, Cron PT y ejecuciones programadas.",
        "icono" => "event_repeat",
        "url" => "../../exportadora/vista/registroUsuarioAviso.php",
        "meta" => "Tareas del sistema"
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuración - Volcan Foods</title>
  <link rel="icon" href="../../assest/img/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    :root{
      --ink:#10233f;
      --muted:#667085;
      --line:#dce4ef;
      --surface:#ffffff;
      --canvas:#eef3f8;
      --blue:#0a3a6a;
      --green:#4caf50;
      --red:#dc3545;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',Arial,sans-serif}
    body{min-height:100vh;background:var(--canvas);color:var(--ink)}
    .app-shell{min-height:100vh;display:grid;grid-template-rows:auto 1fr}
    .commandbar{
      min-height:76px;background:var(--surface);border-bottom:1px solid var(--line);
      display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:22px;padding:12px 24px;
      box-shadow:0 1px 10px rgba(16,35,63,.05)
    }
    .brand{display:flex;align-items:center;gap:18px;min-width:0}
    .brand img{width:150px;max-width:38vw;height:auto}
    .module-kicker{display:flex;flex-direction:column;gap:2px;border-left:1px solid var(--line);padding-left:18px}
    .module-kicker span:first-child{font-size:12px;text-transform:uppercase;font-weight:800;color:var(--green)}
    .module-kicker span:last-child{font-size:18px;font-weight:800;color:var(--blue)}
    .context-strip{display:flex;align-items:center;justify-content:center;gap:10px;min-width:0}
    .context-pill{
      height:42px;display:inline-flex;align-items:center;gap:8px;padding:0 14px;border:1px solid var(--line);
      border-radius:8px;background:#f8fbff;color:var(--muted);font-size:13px;font-weight:700;max-width:100%
    }
    .context-pill .material-icons{font-size:18px;color:var(--blue)}
    .actions{display:flex;align-items:center;justify-content:flex-end;gap:10px}
    .action{
      min-height:46px;border:1px solid #c8d4e3;background:#fff;color:var(--blue);border-radius:8px;
      padding:0 16px;text-decoration:none;font-weight:800;font-size:14px;display:inline-flex;align-items:center;gap:9px;
      cursor:pointer;transition:.18s;white-space:nowrap
    }
    .action:hover{border-color:var(--blue);background:#f7fbff;color:var(--blue)}
    .action-danger{background:var(--red);border-color:var(--red);color:#fff}
    .action-danger:hover{background:#c82333;border-color:#c82333;color:#fff}
    .action .material-icons{font-size:21px}
    main{width:100%;max-width:1180px;margin:0 auto;padding:34px 24px 42px}
    .workspace-head{
      display:grid;grid-template-columns:1fr auto;align-items:end;gap:18px;margin-bottom:22px
    }
    .eyebrow{font-size:12px;text-transform:uppercase;font-weight:800;color:var(--green);letter-spacing:.02em;margin-bottom:8px}
    h1{font-size:32px;line-height:1.15;font-weight:800;color:var(--ink);margin-bottom:8px}
    .lead{font-size:15px;color:var(--muted);max-width:680px;line-height:1.5}
    .count-box{
      min-width:148px;background:#fff;border:1px solid var(--line);border-radius:8px;padding:14px 16px;text-align:right
    }
    .count-box strong{display:block;font-size:26px;color:var(--blue);line-height:1}
    .count-box span{font-size:12px;color:var(--muted);font-weight:700}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
    .module-card{
      position:relative;overflow:hidden;background:#fff;border:1px solid var(--line);border-radius:8px;
      min-height:210px;padding:20px;text-decoration:none;color:inherit;display:flex;flex-direction:column;justify-content:space-between;
      transition:.18s
    }
    .module-card:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,var(--blue),var(--green))}
    .module-card:hover{transform:translateY(-3px);border-color:#b9c7d7;box-shadow:0 14px 30px rgba(16,35,63,.11)}
    .module-icon{
      width:46px;height:46px;border-radius:8px;background:#edf6ef;color:var(--green);
      display:flex;align-items:center;justify-content:center;margin-bottom:18px
    }
    .module-icon .material-icons{font-size:26px}
    .module-card h2{font-size:19px;font-weight:800;margin-bottom:8px;color:var(--ink)}
    .module-card p{font-size:14px;line-height:1.48;color:var(--muted);margin-bottom:18px}
    .module-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;border-top:1px solid #edf1f5;padding-top:14px}
    .module-meta{font-size:12px;font-weight:800;color:var(--blue)}
    .module-go{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:800;color:var(--green)}
    .module-go .material-icons{font-size:18px}
    .empty{background:#fff;border:1px solid var(--line);border-radius:8px;padding:22px;color:var(--muted)}
    @media(max-width:860px){
      .commandbar{grid-template-columns:1fr;align-items:start}
      .context-strip{justify-content:flex-start}
      .actions{justify-content:flex-start;flex-wrap:wrap}
      .workspace-head{grid-template-columns:1fr}
      .count-box{text-align:left}
    }
    @media(max-width:560px){
      .commandbar{padding:16px}
      .brand{align-items:flex-start;flex-direction:column;gap:10px}
      .module-kicker{border-left:0;padding-left:0}
      .action{flex:1;justify-content:center}
      main{padding:24px 16px 34px}
      h1{font-size:26px}
    }
  </style>
</head>
<body>
  <div class="app-shell">
    <header class="commandbar">
      <div class="brand">
        <img src="../../assest/img/volcan-foods-logo-original.png" alt="Volcan Foods">
        <div class="module-kicker">
          <span>Módulo</span>
          <span>Configuración</span>
        </div>
      </div>
      <div class="context-strip">
        <div class="context-pill">
          <span class="material-icons">verified_user</span>
          <span>Administración del sistema</span>
        </div>
      </div>
      <div class="actions">
        <a class="action" href="../../interno.php"><span class="material-icons">apps</span> Módulos</a>
        <form method="post">
          <button class="action action-danger" type="submit" name="CERRARS" value="CERRARS">
            <span class="material-icons">logout</span> Cerrar Sesión
          </button>
        </form>
      </div>
    </header>
    <main>
      <section class="workspace-head">
        <div>
          <div class="eyebrow">Panel de control</div>
          <h1>Configuración</h1>
          <p class="lead">Seleccione el grupo de herramientas que desea administrar. Los accesos se muestran según los permisos activos de su perfil.</p>
        </div>
        <div class="count-box">
          <strong><?php echo count($accesos); ?></strong>
          <span>accesos disponibles</span>
        </div>
      </section>
      <?php if (count($accesos) > 0) { ?>
        <section class="grid">
          <?php foreach ($accesos as $acceso) { ?>
            <a class="module-card" href="<?php echo $acceso["url"]; ?>">
              <div>
                <div class="module-icon"><span class="material-icons"><?php echo $acceso["icono"]; ?></span></div>
                <h2><?php echo $acceso["titulo"]; ?></h2>
                <p><?php echo $acceso["descripcion"]; ?></p>
              </div>
              <div class="module-footer">
                <span class="module-meta"><?php echo $acceso["meta"]; ?></span>
                <span class="module-go">Abrir <span class="material-icons">arrow_forward</span></span>
              </div>
            </a>
          <?php } ?>
        </section>
      <?php } else { ?>
        <div class="empty">Su perfil no tiene permisos de configuración habilitados.</div>
      <?php } ?>
    </main>
  </div>
</body>
</html>
