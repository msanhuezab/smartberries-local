<?php
require_once '../../api/vendor/autoload.php';
$detect = new Mobile_Detect;

session_start();
if (isset($_SESSION["NOMBRE_USUARIO"], $_SESSION["ID_TEMPORADA"])) {
    if ($_SESSION["NOMBRE_USUARIO"] && $_SESSION["ID_TEMPORADA"]) {
        header('Location: index.php');
    }
}

include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';
include_once '../../assest/controlador/PTUSUARIO_ADO.php';
include_once "../../assest/controlador/AUSUARIO_ADO.php";
include_once '../../assest/modelo/USUARIO.php';

$USUARIO_ADO = new USUARIO_ADO();
$TEMPORADA_ADO = new TEMPORADA_ADO();
$PTUSUARIO_ADO = new PTUSUARIO_ADO();
$AUSUARIO_ADO = new AUSUARIO_ADO;
$USUARIO = new USUARIO;

$NOMBRE = "";
$CONTRASENA = "";
$TEMPORADA = $_SESSION["ID_TEMPORADA"] ?? "";
$ARRAYTEMPORADA = $TEMPORADA_ADO->listarTemporadaCBX();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Productores - Volcan Foods</title>
  <link rel="icon" href="../../assest/img/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="../../assest/js/sweetalert2@11.js"></script>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif}
    body,html{height:100%;background:#f5f7fa;}
    .container{display:flex;min-height:100vh}
    .left-panel{
        flex:1;max-width:420px;background:#fff;padding:40px;
        display:flex;flex-direction:column;justify-content:center;
        box-shadow:0 2px 10px rgba(0,0,0,.05);
        border-right:3px solid #e0e6ef;
        position:relative;
    }
    .left-panel::after{
        content:'';
        position:absolute;
        right:-2px;
        top:0;
        bottom:0;
        width:2px;
        background:linear-gradient(to bottom, #4caf50, #2196f3);
    }
    .logo{text-align:center;margin-bottom:25px}
    .logo img{max-width:180px}
    .logo p{color:#555;font-size:.9rem;margin-top:8px}
    h2{text-align:center;color:#0a3a6a;font-size:1.3rem;margin-bottom:20px;font-weight:700}
    .card{border:1px solid #e0e6ef;border-radius:12px;padding:20px;margin-bottom:15px;box-shadow:0 2px 6px rgba(0,0,0,0.05)}
    .card h3{font-size:16px;margin-bottom:15px;color:#1a2b4c}
    form{width:100%;max-width:320px;margin:auto}
    .form-input{width:100%;padding:12px;border:1px solid #ccd4e0;border-radius:8px;margin-bottom:15px;font-size:14px;transition:border-color 0.3s}
    .form-input:focus{border-color:#4caf50;outline:none}
    label{font-size:.85rem;color:#444;margin-bottom:4px;display:block;font-weight:600}
    .btn{width:100%;padding:14px;border:none;border-radius:8px;font-weight:600;cursor:pointer;margin-bottom:10px;text-decoration:none;display:block;text-align:center;transition:all 0.3s}
    .btn-login{background:#28a745;color:#fff;box-shadow:0 2px 4px rgba(40,167,69,0.3)}
    .btn-login:hover{background:#218838;transform:translateY(-1px);box-shadow:0 4px 8px rgba(40,167,69,0.4)}
    .btn-back{background:#dc3545;color:#fff;box-shadow:0 2px 4px rgba(220,53,69,0.3)}
    .btn-back:hover{background:#b02a37;transform:translateY(-1px);box-shadow:0 4px 8px rgba(220,53,69,0.4)}
    .btn-group{display:flex;gap:10px}
    .btn-group .btn{margin-bottom:0;flex:1}
    .right-panel{flex:2;position:relative;overflow:hidden}
    .slide{position:absolute;width:100%;height:100%;background-size:cover;background-position:center;opacity:0;transition:opacity 1.5s}
    .slide.active{opacity:1}
    @media(max-width:768px){
      .container{flex-direction:column}
      .left-panel{max-width:none;width:100%;border-right:none;border-bottom:3px solid #e0e6ef}
      .left-panel::after{right:0;left:0;top:auto;bottom:-2px;width:auto;height:2px;background:linear-gradient(to right, #4caf50, #2196f3)}
      .right-panel{height:220px}
      .btn-group{flex-direction:column}
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Panel izquierdo -->
    <div class="left-panel">
      <div class="logo">
        <img src="https://volcanfoods.cl/wp-content/uploads/2024/09/volcan_borde.png.webp" alt="Volcan Foods">
        <p>www.volcanfoods.cl</p>
      </div>
      <h2>PORTAL PRODUCTORES</h2>
      
      <div class="card">
        <h3>Acceso Estadísticas</h3>
        <form method="post" id="form_reg_dato">
          <input type="text" class="form-input" placeholder="Nombre Usuario" name="NOMBRE" value="<?= $NOMBRE?>" required>
          
          <input type="password" class="form-input" placeholder="Contraseña" name="CONTRASENA" value="<?= $CONTRASENA?>" required>

          <label for="ESPECIE">Seleccionar Especie</label>
          <select class="form-input" id="ESPECIE" name="ESPECIE" required>
            <option value="">-- Seleccione Especie --</option>
            <option value="1">Arándanos</option>
            <option value="3">Espárragos</option>
          </select>

          <label for="TEMPORADA">Seleccionar Temporada</label>
          <select class="form-input" id="TEMPORADA" name="TEMPORADA" required>
            <option value="6" selected>2025-2026</option>
            <?php foreach ($ARRAYTEMPORADA as $r): ?>
            <?php endforeach;?>
          </select>

          <div class="btn-group">
            <a href="../../" class="btn btn-back">Volver</a>
            <button type="submit" class="btn btn-login" name="ENTRAR">Entrar</button>
          </div>
        </form>
      </div>
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

  <!-- Detección móvil con SweetAlert2 -->
  <?php if ($detect->isMobile() && $detect->isiOS()): ?>
    <script>
      Swal.fire({
        icon:"info",
        title:"iPhone detectado",
        html:"Algunas vistas no están adaptadas 📱<br>Recomendamos usar tablet Android/iPad o PC",
        confirmButtonText:"Vale! 😉"
      })
    </script>
  <?php endif; ?>

  <?php if ($detect->isMobile() && $detect->isAndroidOS()): ?>
    <script>
      Swal.fire({
        icon:"info",
        title:"Android detectado",
        html:"Algunas vistas no están adaptadas 🤖<br>Recomendamos usar tablet Android/iPad o PC",
        confirmButtonText:"Vale! 😉"
      })
    </script>
  <?php endif; ?>

<?php
// Procesamiento del formulario
if (isset($_POST['ENTRAR'])) {
    if (empty($_POST['NOMBRE']) || empty($_POST['CONTRASENA'])) {
        echo '<script>
            Swal.fire({
                icon:"info",
                title:"Alerta de inicio de sesión",
                text:"El usuario o contraseña se encuentra vacío, por favor llena los datos mínimos para iniciar sesión",
                confirmButtonText:"OK"
            });
        </script>';
    } else {
        $NOMBRE = $_POST['NOMBRE'];
        $CONTRASENA = $_POST['CONTRASENA'];
        
        $ARRAYINICIOSESSION = $USUARIO_ADO->iniciarSession($NOMBRE, $CONTRASENA);
        if (empty($ARRAYINICIOSESSION) || sizeof($ARRAYINICIOSESSION) == 0) {
            $ARRAYINICIOSESSIONINTENTOS = $USUARIO_ADO->iniciarSessionNIntentos($NOMBRE);
            if ($ARRAYINICIOSESSIONINTENTOS) {
                if ($ARRAYINICIOSESSIONINTENTOS[0]["NINTENTO"] >= 3) {
                    $USUARIO->__SET('NOMBRE_USUARIO', $NOMBRE);
                    $USUARIO_ADO->deshabilitar2($USUARIO);
                    $AUSUARIO_ADO->agregarAusuario2('NULL',4,0, "".$_POST['NOMBRE'].", Usuario bloqueado, se superó el número de intentos permitidos.", "usuario_usuario", 'NULL','NULL','NULL','NULL','NULL');
                    
                    echo '<script>
                        Swal.fire({
                            icon:"error",
                            title:"Usuario bloqueado",
                            text:"Se superó el número de intentos permitidos, contactarse con el administrador."
                        });
                    </script>';
                } else {
                    $NINTENTONUEVO = $ARRAYINICIOSESSIONINTENTOS[0]["NINTENTO"] + 1;
                    $NINTENTORESTANTE = 4 - $NINTENTONUEVO;
                    
                    $USUARIO->__SET('NINTENTO', $NINTENTONUEVO);
                    $USUARIO->__SET('NOMBRE_USUARIO', $NOMBRE);
                    $USUARIO_ADO->NintentoSuma($USUARIO);
                    $AUSUARIO_ADO->agregarAusuario2('NULL',4,0, "".$_POST['NOMBRE'].", Los datos ingresados son erróneos, número de intentos restante ".$NINTENTORESTANTE.".", "usuario_usuario", 'NULL','NULL','NULL','NULL','NULL');
                    
                    echo '<script>
                        Swal.fire({
                            icon:"warning",
                            title:"Error de acceso",
                            text:"Los datos ingresados son erróneos, número de intentos restante '.$NINTENTORESTANTE.'"
                        });
                    </script>';
                }
            } else {
                $AUSUARIO_ADO->agregarAusuario2('NULL',4,0, "".$_POST['NOMBRE'].", Los datos ingresados no coinciden con el usuario.", "usuario_usuario", 'NULL','NULL','NULL','NULL','NULL');
                echo '<script>
                    Swal.fire({
                        icon:"warning",
                        title:"Error de acceso",
                        text:"Los datos ingresados no coinciden con nuestros registros, reintente"
                    });
                </script>';
            }
        } else {
            $ARRAYVERPTUSUARIO = $PTUSUARIO_ADO->listarPtusuarioPorTusuarioCBX($ARRAYINICIOSESSION[0]['ID_TUSUARIO']);
            if ($ARRAYVERPTUSUARIO) {
                $PESTADISTICA = $ARRAYVERPTUSUARIO[0]['ESTADISTICA'];
                if ($PESTADISTICA == "1") {
                    $_SESSION["ID_USUARIO"] = $ARRAYINICIOSESSION[0]['ID_USUARIO'];
                    $_SESSION["NOMBRE_USUARIO"] = $ARRAYINICIOSESSION[0]['NOMBRE_USUARIO'];
                    $_SESSION["TIPO_USUARIO"] = $ARRAYINICIOSESSION[0]['ID_TUSUARIO'];
                    $_SESSION["ID_TEMPORADA"] = $_POST['TEMPORADA'];
                    $_SESSION["ID_ESPECIE"] = $_POST['ESPECIE'];
                    
                    $USUARIO->__SET('ID_USUARIO', $ARRAYINICIOSESSION[0]['ID_USUARIO']);
                    $USUARIO_ADO->NintentoZero($USUARIO);
                    $AUSUARIO_ADO->agregarAusuario2('NULL',4,0,"".$ARRAYINICIOSESSION[0]['NOMBRE_USUARIO'].", Inicio Sesión","usuario_usuario",$ARRAYINICIOSESSION[0]['ID_USUARIO'],$ARRAYINICIOSESSION[0]['ID_USUARIO'],'NULL','NULL',$_POST['TEMPORADA']);
                    
                    echo '<script>
                        Swal.fire({
                            icon:"success",
                            title:"Credenciales correctas",
                            text:"Cargando página principal",
                            timer:2000,
                            showConfirmButton:false
                        }).then(()=>{
                            location.href="index.php";
                        });
                    </script>';
                } else {
                    echo '<script>
                        Swal.fire({
                            icon:"warning",
                            title:"Error de acceso",
                            text:"El Usuario no cuenta con los privilegios para acceder al módulo."
                        }).then(()=>{
                            location.href="../../";
                        });
                    </script>';
                }
            } else {
                echo '<script>
                    Swal.fire({
                        icon:"warning",
                        title:"Error de acceso",
                        text:"El Usuario no cuenta con los privilegios asociados."
                    }).then(()=>{
                        location.href="../../";
                    });
                </script>';
            }
        }
    }
}
?>
</body>
</html>