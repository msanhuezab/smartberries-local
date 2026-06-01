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
if (isset($_SESSION["ID_USUARIO"]) && isset($_SESSION["NOMBRE_USUARIO"]) && isset($_SESSION["ID_EMPRESA"])  && isset($_SESSION["ID_TEMPORADA"])) {
    if($_SESSION["ID_EMPRESA"]!="" && $_SESSION["ID_TEMPORADA"]!=""){
        header('Location: index.php');
        exit;
    }
}
include_once '../../assest/config/cronPtHelper.php';

include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';
include_once "../../assest/controlador/AUSUARIO_ADO.php";

$EMPRESA_ADO = new EMPRESA_ADO();
$TEMPORADA_ADO =  new TEMPORADA_ADO();
$AUSUARIO_ADO =  NEW AUSUARIO_ADO;

$EMPRESA = "";
$TEMPORADA = "";
$MENSAJE = "";
$MENSAJE2 = "";

$ARRAYEMPRESA = $EMPRESA_ADO->listarEmpresaCBX();
$ARRAYTEMPORADA = $TEMPORADA_ADO->listarTemporadaCBX();

if (isset($_SESSION["ID_EMPRESA"])) {
    $EMPRESA = $_SESSION["ID_EMPRESA"];
}
if (isset($_SESSION["ID_TEMPORADA"])) {
    $TEMPORADA = $_SESSION["ID_TEMPORADA"];   
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
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif}
    body,html{height:100%}
    .container{display:flex;min-height:100vh}
    .left-panel{flex:1;max-width:450px;background:#fff;padding:40px;display:flex;flex-direction:column;justify-content:center}
    .logo{text-align:center;margin-bottom:20px}
    .logo img{max-width:180px}
    .logo p{margin-top:5px;color:#666;font-size:13px}
    h2{text-align:center;margin-bottom:20px;color:#1a2b4c;font-weight:700;font-size:18px}
    .card{border:1px solid #e0e6ef;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 2px 6px rgba(0,0,0,0.05)}
    label{font-size:14px;color:#1a2b4c;font-weight:600;margin-bottom:8px;display:block}
    select.form-control{width:100%;padding:12px;border:1px solid #ccd4e0;border-radius:8px;font-size:14px;margin-bottom:10px}
    .validacion{font-size:12px;color:#d32f2f;margin-bottom:10px;display:block}
    .btn{width:100%;padding:14px;border:none;border-radius:8px;font-weight:600;cursor:pointer;margin-top:10px}
    .btn-primary{background:#007bff;color:#fff}
    .btn-primary:hover{background:#0069d9}
    .btn-danger{background:#dc3545;color:#fff}
    .btn-danger:hover{background:#b52a37}
    .right-panel{flex:2;position:relative;overflow:hidden}
    .slide{position:absolute;top:0;left:0;width:100%;height:100%;background-size:cover;background-position:center;opacity:0;transition:opacity 1.5s}
    .slide.active{opacity:1}
    @media(max-width:768px){
       .container{flex-direction:column}
       .left-panel{max-width:none;width:100%}
       .right-panel{min-height:250px}
    }
  </style>
  <script>
    function validacion() {
        var retorno = 1;
        EMPRESA = document.getElementById("EMPRESA").selectedIndex;
        TEMPORADA = document.getElementById("TEMPORADA").selectedIndex;
        document.getElementById('val_select_empresa').innerHTML = "";
        document.getElementById('val_select_temporada').innerHTML = "";

        if (EMPRESA == null || EMPRESA == 0) {
            document.form_reg_dato.EMPRESA.focus();
            document.form_reg_dato.EMPRESA.style.borderColor = "#FF0000";
            document.getElementById('val_select_empresa').innerHTML = "Debe seleccionar una Empresa.";
            retorno = 1;
        } else {
            retorno = 0;
            document.form_reg_dato.EMPRESA.style.borderColor = "#4CAF50";
        }
        if (TEMPORADA == null || TEMPORADA == 0) {
            document.form_reg_dato.TEMPORADA.focus();
            document.form_reg_dato.TEMPORADA.style.borderColor = "#FF0000";
            document.getElementById('val_select_temporada').innerHTML = "Debe seleccionar una Temporada.";
            retorno = 1;
        } else {
            retorno = 0;
            document.form_reg_dato.TEMPORADA.style.borderColor = "#4CAF50";
        }
        if(retorno==1){ return false; }
    }
  </script>
</head>
<body>
  <div class="container">
    <!-- Panel selección -->
    <div class="left-panel">
      <div class="logo">
        <img src="../../assest/img/volcan-foods-logo-original.png" alt="Volcan Foods">
        <p>www.volcanfoods.cl</p>
      </div>
      <h2>Selección de Parámetros</h2>
      <div class="card">
        <form method="post" name="form_reg_dato" id="form_reg_dato">
          <label for="EMPRESA">Seleccionar Empresa</label>
          <select class="form-control" id="EMPRESA" name="EMPRESA">
              <option value="">-- Seleccione --</option>
              <?php foreach ($ARRAYEMPRESA as $r) : ?>
                  <option value="<?php echo $r['ID_EMPRESA']; ?>" <?php if ($EMPRESA == $r['ID_EMPRESA']) { echo "selected"; } ?>><?php echo $r['NOMBRE_EMPRESA']; ?></option>
              <?php endforeach; ?>
          </select>
          <label id="val_select_empresa" class="validacion"><?php echo  $MENSAJE; ?></label>

          <label for="TEMPORADA">Seleccionar Temporada</label>
          <select class="form-control" id="TEMPORADA" name="TEMPORADA">
              <option value="">-- Seleccione --</option>
              <?php foreach ($ARRAYTEMPORADA as $r) : ?>
                  <option value="<?php echo $r['ID_TEMPORADA']; ?>" <?php if ($TEMPORADA == $r['ID_TEMPORADA']) { echo "selected"; } ?>><?php echo $r['NOMBRE_TEMPORADA']; ?></option>
              <?php endforeach; ?>
          </select>
          <label id="val_select_temporada" class="validacion"><?php echo  $MENSAJE2; ?></label>

          <button type="submit" class="btn btn-primary" id="ENTRAR" name="ENTRAR" value="ENTRAR" onclick="return validacion()">Ingresar</button>
        </form>

        <form method="post">
          <button type="submit" class="btn btn-danger" id="SALIR" name="SALIR" value="SALIR">Salir</button>
        </form>
      </div>
    </div>

    <!-- Fondo / Slider -->
    <div class="right-panel">
      <div class="slide active" style="background-image:url('../../assest/img/abeja.jpg')"></div>
      <div class="slide" style="background-image:url('../../assest/img/arandano.jpg')"></div>
      <div class="slide" style="background-image:url('../../assest/img/esparragos.jpg')"></div>
    </div>
  </div>

  <script>
    // Slider animado
    const slides=document.querySelectorAll('.slide');let idx=0;
    setInterval(()=>{slides[idx].classList.remove('active');idx=(idx+1)%slides.length;slides[idx].classList.add('active');},5000);
  </script>

  <?php if ($detect->isMobile() && $detect->isiOS() ): ?>
    <script>
      Swal.fire({
        icon: 'info',
        title: 'Celular iPhone detectado',
        html:"Hemos detectado que estás desde un iPhone 📱<br>Algunas vistas no están adaptadas, sugerimos que uses tablet Android, iPad o computador.",
        confirmButtonText:"Vale! 😉"
      })
    </script>
  <?php endif ?>

  <?php if ($detect->isMobile() && $detect->isAndroidOS()): ?>
    <script>
      Swal.fire({
        icon: 'info',
        title: 'Celular Android detectado',
        html:"Hemos detectado que estás desde un Android 🤖<br>Algunas vistas no están adaptadas, sugerimos que uses tablet Android, iPad o computador.",
        confirmButtonText:"Vale! 😉"
      })
    </script>
  <?php endif ?>

<?php
if (isset($_REQUEST['ENTRAR'])) {
    $_SESSION["ID_EMPRESA"] = $_REQUEST['EMPRESA'];
    $_SESSION["ID_TEMPORADA"] = $_REQUEST['TEMPORADA'];
    $AUSUARIO_ADO->agregarAusuario2('NULL',3,0,"".$_SESSION["NOMBRE_USUARIO"].", Inicio Sesion, Seleccion","usuario_usuario",$_SESSION["ID_USUARIO"],$_SESSION["ID_USUARIO"],$_REQUEST["EMPRESA"],'NULL',$_REQUEST['TEMPORADA'] );            
    echo "<script> location.href = 'index.php';</script>";
}
if (isset($_REQUEST['SALIR'])) {
    session_destroy();
    echo "<script> location.href = '../../';</script>";
}
?>
</body>
</html>
