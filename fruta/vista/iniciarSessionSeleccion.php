<?php
require_once '../../api/vendor/autoload.php';
$detect = new Mobile_Detect;

session_start();
include_once '../../assest/controlador/USUARIO_ADO.php';
$USUARIO_ADO = new USUARIO_ADO();
if (isset($_SESSION["ID_USUARIO"]) && !$USUARIO_ADO->usuarioActivo($_SESSION["ID_USUARIO"])) {
    session_destroy();
    header('Location: iniciarSession.php?USUARIO_INACTIVO=1');
    exit;
}
if (isset($_SESSION["ID_USUARIO"], $_SESSION["NOMBRE_USUARIO"], $_SESSION["ID_EMPRESA"], $_SESSION["ID_PLANTA"], $_SESSION["ID_TEMPORADA"])) {
    if ($_SESSION["ID_EMPRESA"] && $_SESSION["ID_PLANTA"] && $_SESSION["ID_TEMPORADA"]) {
        header('Location: index.php');
        exit;
    }
}
include_once '../../assest/config/cronPtHelper.php';

include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/PLANTA_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';
include_once "../../assest/controlador/AUSUARIO_ADO.php";

$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
$TEMPORADA_ADO = new TEMPORADA_ADO();
$AUSUARIO_ADO = new AUSUARIO_ADO;

$EMPRESA = $_SESSION["ID_EMPRESA"] ?? "";
$PLANTA = $_SESSION["ID_PLANTA"] ?? "";
$TEMPORADA = $_SESSION["ID_TEMPORADA"] ?? "";

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$ARRAYEMPRESA = $EMPRESA_ADO->listarEmpresaCBX();
$ARRAYPLANTA = $PLANTA_ADO->listarPlantaPropiaCBX();
$ARRAYTEMPORADA = $TEMPORADA_ADO->listarTemporadaCBX();

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selección de Parámetros - Volcan Foods</title>
  <link rel="icon" href="../../assest/img/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="../../assest/js/sweetalert2@11.js"></script>
  <style>
    /* Mantener estilos originales */
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif}
    body,html{height:100%;background:#f5f7fa;}
    .container{display:flex;min-height:100vh}
    .left-panel{
        flex:1;max-width:420px;background:#fff;padding:40px;
        display:flex;flex-direction:column;justify-content:center;
        box-shadow:0 2px 10px rgba(0,0,0,.05);
    }
    .logo{text-align:center;margin-bottom:25px}
    .logo img{max-width:180px}
    .logo p{color:#555;font-size:.9rem;margin-top:8px}
    h2{text-align:center;color:#0a3a6a;font-size:1.3rem;margin-bottom:20px;font-weight:700}
    form{width:100%;max-width:320px;margin:auto}
    .form-input{width:100%;padding:12px;border:1px solid #ccd4e0;border-radius:8px;margin-bottom:15px;font-size:14px}
    label{font-size:.85rem;color:#444;margin-bottom:4px;display:block}
    .btn{width:100%;padding:12px;border:none;border-radius:8px;font-weight:600;cursor:pointer;margin-top:5px;text-decoration:none;display:block;text-align:center}
    .btn-login{background:#28a745;color:#fff}
    .btn-login:hover{background:#218838}
    .btn-back{background:#dc3545;color:#fff}
    .btn-back:hover{background:#b02a37}
    .right-panel{flex:2;position:relative;overflow:hidden}
    .slide{position:absolute;width:100%;height:100%;background-size:cover;background-position:center;opacity:0;transition:opacity 1.5s}
    .slide.active{opacity:1}
    @media(max-width:768px){
      .container{flex-direction:column}
      .left-panel{max-width:none;width:100%}
      .right-panel{height:220px}
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Panel izquierdo -->
    <div class="left-panel">
      <div class="logo">
        <img src="../../assest/img/volcan-foods-logo-original.png" alt="Volcan Foods">
        <p><a href="https://www.volcanfoods.cl" target="_blank" rel="noopener noreferrer" style="color:#555; text-decoration:none;">www.volcanfoods.cl</a></p>
      </div>
      <h2>Selección de Parámetros</h2>
      
      <form method="post" id="form_reg_dato" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
        
        <label for="EMPRESA">Seleccionar Empresa</label>
        <select class="form-input" id="EMPRESA" name="EMPRESA" required>
          <option value="">-- Seleccione Empresa --</option>
          <?php foreach ($ARRAYEMPRESA as $r): ?>
            <option value="<?= h($r['ID_EMPRESA']) ?>" <?= $EMPRESA == $r['ID_EMPRESA'] ? "selected" : "" ?>><?= h($r['NOMBRE_EMPRESA']) ?></option>
          <?php endforeach;?>
        </select>

        <label for="PLANTA">Seleccionar Planta</label>
        <select class="form-input" id="PLANTA" name="PLANTA" required>
          <option value="">-- Seleccione Planta --</option>
          <?php foreach ($ARRAYPLANTA as $r): ?>
            <option value="<?= h($r['ID_PLANTA']) ?>" <?= $PLANTA == $r['ID_PLANTA'] ? "selected" : "" ?>><?= h($r['NOMBRE_PLANTA']) ?></option>
          <?php endforeach;?>
        </select>

        <label for="TEMPORADA">Seleccionar Temporada</label>
        <select class="form-input" id="TEMPORADA" name="TEMPORADA" required>
          <option value="">-- Seleccione Temporada --</option>
          <?php foreach ($ARRAYTEMPORADA as $r): ?>
            <option value="<?= h($r['ID_TEMPORADA']) ?>" <?= $TEMPORADA == $r['ID_TEMPORADA'] ? "selected" : "" ?>><?= h($r['NOMBRE_TEMPORADA']) ?></option>
          <?php endforeach;?>
        </select>

        <button type="submit" class="btn btn-login" name="ENTRAR">Ingresar</button>
      </form>

      <!-- Botón atrás fuera del formulario -->
      <a href="../../" class="btn btn-back">Atrás</a>
    </div>

    <!-- Panel derecho con slider -->
    <div class="right-panel">
      <div class="slide active" style="background-image:url('../../assest/img/abeja.jpg')"></div>
      <div class="slide" style="background-image:url('../../assest/img/arandano.jpg')"></div>
      <div class="slide" style="background-image:url('../../assest/img/esparragos.jpg')"></div>
    </div>
  </div>

  <script>
    // Slider automático
    const slides=document.querySelectorAll('.slide');let i=0;
    setInterval(()=>{slides[i].classList.remove('active');i=(i+1)%slides.length;slides[i].classList.add('active')},5000);
  </script>

<?php
// Procesamiento del formulario
if (isset($_POST['ENTRAR'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo '<script>
          Swal.fire({
            icon:"error",
            title:"Error de seguridad",
            text:"Token CSRF inválido. Recarga la página."
          });
        </script>';
        exit;
    }

    // Validar y sanear entradas
    $empresa = filter_input(INPUT_POST, 'EMPRESA', FILTER_VALIDATE_INT);
    $planta = filter_input(INPUT_POST, 'PLANTA', FILTER_VALIDATE_INT);
    $temporada = filter_input(INPUT_POST, 'TEMPORADA', FILTER_VALIDATE_INT);

    if (!$empresa || !$planta || !$temporada) {
        echo '<script>
          Swal.fire({
            icon:"warning",
            title:"Campos requeridos",
            text:"Debes seleccionar empresa, planta y temporada válidas"
          });
        </script>';
        exit;
    }

    // Guardar en sesión
    $_SESSION["ID_EMPRESA"] = $empresa;
    $_SESSION["ID_PLANTA"] = $planta;
    $_SESSION["ID_TEMPORADA"] = $temporada;

    // Registrar acción (asegúrate que agregarAusuario2 use consultas preparadas)
    $AUSUARIO_ADO->agregarAusuario2(
        'NULL',1,0,"".($_SESSION["NOMBRE_USUARIO"] ?? 'Usuario desconocido').", Selección de parámetros",
        "usuario_usuario",$_SESSION["ID_USUARIO"] ?? 0,$_SESSION["ID_USUARIO"] ?? 0,
        $empresa,$planta,$temporada
    );

    // Regenerar ID de sesión para mayor seguridad
    session_regenerate_id(true);

    echo '<script>
      Swal.fire({
        icon:"success",
        title:"Éxito",
        text:"Parámetros seleccionados correctamente",
        timer:2000,
        showConfirmButton:false
      }).then(()=>{location.href="index.php";});
    </script>';
}
?>
</body>
</html>
