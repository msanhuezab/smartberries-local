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

<aside class="main-sidebar">
  <section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
      <li>
        <a href="index.php">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/dashboard.svg" class="svg-icon" alt="">
          <span>Inicio</span>
        </a>
      </li>
      <li class="header">Modulo</li>
      <li class="treeview">
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/reports.svg" class="svg-icon" alt="">
          <span>Calidad</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li><a href="index.php">Resumen<i class="ti-more"></i></a></li>
        </ul>
      </li>
      <li class="treeview">
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/apps.svg" class="svg-icon" alt="">
          <span>Configuracion</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li><a href="registroParametro.php?GRUPO_REPORTE=DEFECTOS_CALIDAD">Items Calidad<i class="ti-more"></i></a></li>
          <li><a href="registroParametro.php?GRUPO_REPORTE=DEFECTOS_CONDICION">Items Condicion<i class="ti-more"></i></a></li>
          <li><a href="registroParametro.php?GRUPO_REPORTE=CALIBRES">Calibres<i class="ti-more"></i></a></li>
          <li><a href="registroParametro.php?GRUPO_REPORTE=PRESIONES">Presiones<i class="ti-more"></i></a></li>
          <li><a href="registroParametro.php?GRUPO_REPORTE=PARAMETROS">Parametros Generales<i class="ti-more"></i></a></li>
          <li><a href="registroReglaResolucion.php">Reglas Resolucion<i class="ti-more"></i></a></li>
          <li><a href="registroInspector.php">Inspectores<i class="ti-more"></i></a></li>
        </ul>
      </li>
      <li class="treeview">
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/layout.svg" class="svg-icon" alt="">
          <span>Registro</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li><a href="registroRecepcion.php">Recepcion<i class="ti-more"></i></a></li>
          <li><a href="index.php">Proceso<i class="ti-more"></i></a></li>
          <li><a href="index.php">Despacho<i class="ti-more"></i></a></li>
          <li><a href="index.php">Destino<i class="ti-more"></i></a></li>
        </ul>
      </li>
      <li class="treeview">
        <a href="#">
          <img src="../../api/cryptioadmin10/html/images/svg-icon/sidebar-menu/charts.svg" class="svg-icon" alt="">
          <span>Reportes</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-right pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li><a href="index.php">Por Folio<i class="ti-more"></i></a></li>
          <li><a href="index.php">Por Operacion<i class="ti-more"></i></a></li>
          <li><a href="index.php">Por Etapa<i class="ti-more"></i></a></li>
        </ul>
      </li>
    </ul>
  </section>
</aside>
