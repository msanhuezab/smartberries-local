<header class="main-header">
  <div class="d-flex align-items-center logo-box pl-20">
    <a href="#" class="waves-effect waves-light nav-link rounded d-none d-md-inline-block push-btn" data-toggle="push-menu" role="button">
      <img src="../../api/cryptioadmin10/html/images/svg-icon/collapse.svg" class="img-fluid svg-icon" alt="">
    </a>
    <a href="index.php" class="logo">
      <div class="logo-lg">
        <span class="light-logo"><img src="../../assest/img/logo.png" alt="logo"></span>
        <span class="dark-logo"><img src="../../assest/img/logo.png" alt="logo"></span>
      </div>
    </a>
  </div>
  <nav class="navbar navbar-static-top pl-10">
    <div class="app-menu">
      <ul class="header-megamenu nav">
        <li class="btn-group nav-item d-md-none">
          <a href="#" class="waves-effect waves-light nav-link rounded push-btn" data-toggle="push-menu" role="button">
            <img src="../../api/cryptioadmin10/html/images/svg-icon/collapse.svg" class="img-fluid svg-icon" alt="">
          </a>
        </li>
        <li class="btn-group nav-item">
          <a href="#" data-provide="fullscreen" class="waves-effect waves-light nav-link rounded full-screen" title="Full Screen">
            <img src="../../api/cryptioadmin10/html/images/svg-icon/fullscreen.svg" class="img-fluid svg-icon" alt="">
          </a>
        </li>
        <li class="btn-group nav-item">
          <div class="search-bx ml-10">
            <div class="input-group" style="font-size: 12px;">
              <?php
              $ARRAYEMPRESAS = $EMPRESA_ADO->verEmpresa($EMPRESAS);
              echo $ARRAYEMPRESAS ? $ARRAYEMPRESAS[0]['NOMBRE_EMPRESA'] : 'Empresa';
              ?>
              <br>
              <?php
              $ARRAYPLANTAS = $PLANTA_ADO->verPlanta($PLANTAS);
              echo $ARRAYPLANTAS ? $ARRAYPLANTAS[0]['NOMBRE_PLANTA'] : 'Planta';
              ?>
              <br>
              <?php
              $ARRAYTEMPORADAS = $TEMPORADA_ADO->verTemporada($TEMPORADAS);
              echo $ARRAYTEMPORADAS ? $ARRAYTEMPORADAS[0]['NOMBRE_TEMPORADA'] : 'Temporada';
              ?>
            </div>
          </div>
        </li>
      </ul>
    </div>
    <div class="navbar-custom-menu r-side">
      <ul class="nav navbar-nav">
        <li class="dropdown user user-menu">
          <a href="#" class="waves-effect waves-light dropdown-toggle" data-toggle="dropdown" title="User">
            <img src="../../api/cryptioadmin10/html/images/svg-icon/user.svg" class="rounded svg-icon" alt="" />
          </a>
          <ul class="dropdown-menu animated flipInX">
            <li class="user-header bg-img" style="background-image: url(../../api/cryptioadmin10/html/images/user-info.jpg)" data-overlay="3">
              <div class="flexbox align-self-center">
                <img src="../../api/cryptioadmin10/html/images/avatar/7.jpg" class="float-left rounded-circle" alt="User Image">
                <h4 class="user-name align-self-center">
                  <?php
                  $ARRAYNOMBRESUSUARIOSLOGIN = $USUARIO_ADO->ObtenerNombreCompleto($IDUSUARIOS);
                  $NOMBRESUSUARIOSLOGIN = $ARRAYNOMBRESUSUARIOSLOGIN ? $ARRAYNOMBRESUSUARIOSLOGIN[0]["NOMBRE_COMPLETO"] : $NOMBREUSUARIOS;
                  ?>
                  <span><?php echo $NOMBRESUSUARIOSLOGIN; ?></span>
                  <br>
                  <small>
                    <?php
                    $ARRAYTUSUARIO = $TUSUARIO_ADO->verTusuario($_SESSION["TIPO_USUARIO"]);
                    echo $ARRAYTUSUARIO ? $ARRAYTUSUARIO[0]['NOMBRE_TUSUARIO'] : '';
                    ?>
                  </small>
                </h4>
              </div>
            </li>
            <li class="user-body">
              <a class="dropdown-item" href="../../fruta/vista/verUsuario.php"><i class="ion ion-person"></i> Mi Perfil</a>
              <a class="dropdown-item" href="../../fruta/vista/editarUsuario.php"><i class="ion ion-email-unread"></i> Editar Perfil</a>
              <a class="dropdown-item" href="../../fruta/vista/editarUsuarioClave.php"><i class="ion ion-settings"></i> Cambiar Contrasena</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="../../fruta/vista/verUsuarioActividad.php"><i class="ion ion-bag"></i> Mi Actividad</a>
              <div class="dropdown-divider"></div>
              <div class="p-10">
                <center>
                  <form method="post">
                    <button type="submit" class="btn btn-rounded btn-danger" name="CERRARS" value="CERRARS">
                      <i class="ion-log-out"></i>
                      Cerrar Sesion
                    </button>
                  </form>
                </center>
              </div>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</header>

<?php
$calidadPaginaActual = basename($_SERVER["PHP_SELF"] ?? "");
$calidadGrupoActual = $_GET["GRUPO_REPORTE"] ?? ($_POST["GRUPO_REPORTE"] ?? "");
$calidadEtapaActual = strtoupper($_GET["ETAPA"] ?? ($_POST["ETAPA"] ?? ""));
$calidadEstadoRevision = strtoupper($_GET["ESTADO"] ?? ($_POST["ESTADO"] ?? ""));

$calidadEsDashboard = $calidadPaginaActual === "index.php";
$calidadEsRevision = $calidadPaginaActual === "revisionCalidad.php" && $calidadEstadoRevision === "";
$calidadEsReporte = $calidadPaginaActual === "revisionCalidad.php" && $calidadEstadoRevision !== "";
$calidadEsDefecto = $calidadPaginaActual === "registroParametro.php" && in_array($calidadGrupoActual, array("DEFECTOS_CALIDAD", "DEFECTOS_CONDICION"), true);
$calidadEsParametro = $calidadPaginaActual === "registroParametro.php" && in_array($calidadGrupoActual, array("CALIBRES", "PRESIONES", "PARAMETROS"), true);
$calidadEsConfiguracion = $calidadEsDefecto || $calidadEsParametro || in_array($calidadPaginaActual, array("registroReglaResolucion.php", "registroInspector.php"), true);
$calidadEsRegistro = in_array($calidadPaginaActual, array("registroRecepcion.php", "registroOperacion.php", "controlesCalidad.php"), true);

function calidadMenuClase($activo) {
  return $activo ? ' class="active"' : '';
}

function calidadTreeClase($activo) {
  return $activo ? ' class="treeview active menu-open"' : ' class="treeview"';
}

function calidadTreeStyle($activo) {
  return $activo ? ' style="display: block;"' : '';
}
?>

<aside class="main-sidebar">
  <section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
      <li<?php echo $calidadEsDashboard ? ' class="active"' : ''; ?>>
        <a href="index.php">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/dashboard.svg" class="svg-icon" alt="">
          <span>Inicio</span>
        </a>
      </li>
      <li class="header">Modulo</li>
      <li<?php echo calidadTreeClase($calidadEsDashboard || $calidadEsRevision); ?>>
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/reports.svg" class="svg-icon" alt="">
          <span>Calidad</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu"<?php echo calidadTreeStyle($calidadEsDashboard || $calidadEsRevision); ?>>
          <li<?php echo calidadMenuClase($calidadEsDashboard); ?>><a href="index.php">Dashboard<i class="ti-more"></i></a></li>
          <li<?php echo calidadMenuClase($calidadEsRevision); ?>><a href="revisionCalidad.php">Revision pendientes<i class="ti-more"></i></a></li>
        </ul>
      </li>
      <li<?php echo calidadTreeClase($calidadEsConfiguracion); ?>>
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/apps.svg" class="svg-icon" alt="">
          <span>Configuracion</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu"<?php echo calidadTreeStyle($calidadEsConfiguracion); ?>>
          <li<?php echo calidadTreeClase($calidadEsDefecto); ?>>
            <a href="#">Defectos
              <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
            </a>
            <ul class="treeview-menu"<?php echo calidadTreeStyle($calidadEsDefecto); ?>>
              <li<?php echo calidadMenuClase($calidadGrupoActual === "DEFECTOS_CALIDAD"); ?>><a href="registroParametro.php?GRUPO_REPORTE=DEFECTOS_CALIDAD">Calidad<i class="ti-more"></i></a></li>
              <li<?php echo calidadMenuClase($calidadGrupoActual === "DEFECTOS_CONDICION"); ?>><a href="registroParametro.php?GRUPO_REPORTE=DEFECTOS_CONDICION">Condicion<i class="ti-more"></i></a></li>
            </ul>
          </li>
          <li<?php echo calidadTreeClase($calidadEsParametro); ?>>
            <a href="#">Parametros
              <span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>
            </a>
            <ul class="treeview-menu"<?php echo calidadTreeStyle($calidadEsParametro); ?>>
              <li<?php echo calidadMenuClase($calidadGrupoActual === "CALIBRES"); ?>><a href="registroParametro.php?GRUPO_REPORTE=CALIBRES">Calibres<i class="ti-more"></i></a></li>
              <li<?php echo calidadMenuClase($calidadGrupoActual === "PRESIONES"); ?>><a href="registroParametro.php?GRUPO_REPORTE=PRESIONES">Presiones<i class="ti-more"></i></a></li>
              <li<?php echo calidadMenuClase($calidadGrupoActual === "PARAMETROS"); ?>><a href="registroParametro.php?GRUPO_REPORTE=PARAMETROS">Generales<i class="ti-more"></i></a></li>
            </ul>
          </li>
          <li<?php echo calidadMenuClase($calidadPaginaActual === "registroReglaResolucion.php"); ?>><a href="registroReglaResolucion.php">Resolucion<i class="ti-more"></i></a></li>
          <li<?php echo calidadMenuClase($calidadPaginaActual === "registroInspector.php"); ?>><a href="registroInspector.php">Inspectores<i class="ti-more"></i></a></li>
        </ul>
      </li>
      <li<?php echo calidadTreeClase($calidadEsRegistro); ?>>
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/layout.svg" class="svg-icon" alt="">
          <span>Registro</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu"<?php echo calidadTreeStyle($calidadEsRegistro); ?>>
          <li<?php echo calidadMenuClase($calidadPaginaActual === "registroRecepcion.php"); ?>><a href="registroRecepcion.php">Recepcion<i class="ti-more"></i></a></li>
          <li<?php echo calidadMenuClase($calidadPaginaActual === "registroOperacion.php" && $calidadEtapaActual === "PROCESO"); ?>><a href="registroOperacion.php?ETAPA=PROCESO">Proceso<i class="ti-more"></i></a></li>
          <li<?php echo calidadMenuClase($calidadPaginaActual === "registroOperacion.php" && $calidadEtapaActual === "EXPORTACION"); ?>><a href="registroOperacion.php?ETAPA=EXPORTACION">Exportacion<i class="ti-more"></i></a></li>
          <li<?php echo calidadMenuClase($calidadPaginaActual === "controlesCalidad.php"); ?>><a href="controlesCalidad.php">Cerrar / PDF recepcion<i class="ti-more"></i></a></li>
        </ul>
      </li>
      <li<?php echo calidadTreeClase($calidadEsReporte); ?>>
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/charts.svg" class="svg-icon" alt="">
          <span>Reportes</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu"<?php echo calidadTreeStyle($calidadEsReporte); ?>>
          <li<?php echo calidadMenuClase($calidadEstadoRevision === "SIN_CONTROL"); ?>><a href="revisionCalidad.php?ESTADO=SIN_CONTROL">Pendientes<i class="ti-more"></i></a></li>
          <li<?php echo calidadMenuClase($calidadEstadoRevision === "ABIERTO"); ?>><a href="revisionCalidad.php?ESTADO=ABIERTO">Abiertos<i class="ti-more"></i></a></li>
          <li<?php echo calidadMenuClase($calidadEstadoRevision === "CERRADO"); ?>><a href="revisionCalidad.php?ESTADO=CERRADO">Cerrados<i class="ti-more"></i></a></li>
        </ul>
      </li>
    </ul>
  </section>
</aside>
