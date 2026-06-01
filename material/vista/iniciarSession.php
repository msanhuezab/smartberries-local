<?php
require_once '../../api/vendor/autoload.php';
$detect = new Mobile_Detect;
session_start();

include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/PTUSUARIO_ADO.php';
include_once "../../assest/controlador/AUSUARIO_ADO.php";
include_once '../../assest/modelo/USUARIO.php';

$USUARIO_ADO = new USUARIO_ADO();
$PTUSUARIO_ADO = new PTUSUARIO_ADO();
$AUSUARIO_ADO = new AUSUARIO_ADO;
$USUARIO = new USUARIO;

if (isset($_SESSION["NOMBRE_USUARIO"])) {
    if (!isset($_SESSION["ID_USUARIO"]) || !$USUARIO_ADO->usuarioActivo($_SESSION["ID_USUARIO"])) {
        session_destroy();
    } else {
        header('Location: iniciarSessionSeleccion.php');
        exit;
    }
}

$NOMBRE = "";
$CONTRASENA = "";

// Generar token CSRF si no existe
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializar contador de intentos
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// Claves reCAPTCHA
$siteKey = '6LcZrukrAAAAAGikMJAF8utszcdOin0XCpDSPWRp';
$secretKey = '6LcZrukrAAAAAHC10OqwnsRaVjBT28xWovsfUgyE';
$captchaHabilitado = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true);

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Acceso Intranet - Volcan Foods</title>
  <link rel="icon" href="../../assest/img/favicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <script src="../../assest/js/sweetalert2@11.js"></script>
  <?php if ($captchaHabilitado): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
  <style>
    /* Tus estilos originales */
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif}
    body,html{height:100%}
    .container{display:flex;min-height:100vh}
    .left-panel{flex:1;max-width:420px;background:#fff;padding:40px;display:flex;flex-direction:column;justify-content:center}
    .logo{text-align:center;margin-bottom:30px}
    .logo img{max-width:200px}
    .logo p {
      margin-top:8px;
      color:#555;
      font-size:14px;
    }
    .logo p a {
      color:#555;
      text-decoration:none;
    }
    .logo p a:hover {
      text-decoration:underline;
    }
    h2{text-align:center;margin-bottom:20px;color:#1a2b4c;font-weight:700}
    .card{border:1px solid #e0e6ef;border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:0 2px 6px rgba(0,0,0,0.05)}
    .card h3{font-size:16px;margin-bottom:15px;color:#1a2b4c}
    .form-input{width:100%;padding:14px;border:1px solid #ccd4e0;border-radius:8px;margin-bottom:15px;font-size:14px}
    .btn{width:100%;padding:14px;border:none;border-radius:8px;font-weight:600;cursor:pointer}
    .btn-login{background:#28a745;color:#fff}
    .btn-login:hover{background:#218838}
    .btn-login:disabled{background:#ccc;cursor:not-allowed}
    .btn-link{display:block;text-align:center;color:#1a2b4c;text-decoration:none;padding:12px;border:1px solid #ccd4e0;border-radius:8px;margin-top:10px}
    .economics{margin-top:20px}
    .economics h4{font-size:14px;margin-bottom:12px;color:#1a2b4c}
    .eco-list {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      justify-items: center;
      text-align: center;
    }
    .eco-item {
      font-size: 14px;
      color: #1a2b4c;
    }
    .eco-item span {
      display: block;
      color: #c62828;
      font-weight: 700;
      margin-top: 4px;
    }
    .eco-date{text-align:center;font-size:12px;color:#666;margin-top:10px}
    .right-panel{flex:2;position:relative;overflow:hidden}
    .slide{position:absolute;top:0;left:0;width:100%;height:100%;background-size:cover;background-position:center;opacity:0;transition:opacity 1.5s}
    .slide.active{opacity:1}
    @media(max-width:768px){
       .container{flex-direction:column}
       .left-panel{max-width:none;width:100%}
       .right-panel{min-height:300px}
       .eco-list {
         grid-template-columns: repeat(2, 1fr);
       }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left-panel">
      <div class="logo">
        <img src="../../assest/img/volcan-foods-logo-original.png" alt="Volcan Foods" />
        <p><a href="https://www.volcanfoods.cl" target="_blank" rel="noopener noreferrer">www.volcanfoods.cl</a></p>
      </div>
      <h2>SELECCIÓN INTRANET</h2>

      <?php if ($_SESSION['login_attempts'] > 0): ?>
      <div style="color:#856404; background:#fff3cd; padding:10px; border-radius:8px; margin-bottom:15px; font-size:12px;">
        ⚠️ Intentos fallidos: <?php echo $_SESSION['login_attempts']; ?>/5
      </div>
      <?php endif; ?>

      <div class="card">
        <h3>Acceso Interno</h3>
        <form method="post" id="loginForm">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
          <input type="text" class="form-input" placeholder="Usuario" name="NOMBRE" value="<?php echo htmlspecialchars($NOMBRE); ?>" required />
          <input type="password" class="form-input" placeholder="Contraseña" name="CONTRASENA" required minlength="6" />
          <?php if ($captchaHabilitado): ?>
          <div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>"></div>
          <?php endif; ?>
          <button type="submit" name="ENTRAR" class="btn btn-login" style="margin-top:15px;">Entrar</button>
          <div style="display:flex; align-items:center; margin-top:10px; color: #2e7d32; font-weight: 600; font-size: 14px;">
            <span class="material-icons" style="margin-right:6px; font-size:20px;">lock</span>
            Conexión segura por SSL
          </div>
        </form>
      </div>
      <a href="../../estadistica/vista/iniciarSession.php" class="btn-link">Portal Productores</a>
      <div class="economics card">
        <h4>Indicadores Económicos</h4>
        <div class="eco-list">
          <div class="eco-item">UF <span id="uf">...</span></div>
          <div class="eco-item">Dólar <span id="dolar">...</span></div>
          <div class="eco-item">Euro <span id="euro">...</span></div>
          <div class="eco-item">IPC <span id="ipc">...</span></div>
        </div>
        <div class="eco-date" id="fechaIndicadores">Cargando...</div>
      </div>
    </div>
    <div class="right-panel">
      <div class="slide active" style="background-image:url('../../assest/img/abeja.jpg')"></div>
      <div class="slide" style="background-image:url('../../assest/img/arandano.jpg')"></div>
      <div class="slide" style="background-image:url('../../assest/img/esparragos.jpg')"></div>
    </div>
  </div>

  <script>
    const slides = document.querySelectorAll('.slide');
    let idx = 0;
    setInterval(() => {
      slides[idx].classList.remove('active');
      idx = (idx + 1) % slides.length;
      slides[idx].classList.add('active');
    }, 5000);

    // Cargar indicadores económicos con 1 decimal
    async function cargarIndicadores() {
      try {
        const resp = await fetch("https://mindicador.cl/api");
        const data = await resp.json();
        document.getElementById("uf").innerText = "$" + data.uf.valor.toFixed(1);
        document.getElementById("dolar").innerText = "$" + data.dolar.valor.toFixed(1);
        document.getElementById("euro").innerText = "$" + data.euro.valor.toFixed(1);
        // IPC no tiene valor en pesos, es porcentaje, mostrar con 2 decimales y % al final
        document.getElementById("ipc").innerText = data.ipc.valor.toFixed(2) + "%";
        document.getElementById("fechaIndicadores").innerText = "Actualizado: " + new Date(data.fecha).toLocaleDateString("es-CL");
      } catch (e) {
        document.getElementById("uf").innerText = "N/D";
        document.getElementById("dolar").innerText = "N/D";
        document.getElementById("euro").innerText = "N/D";
        document.getElementById("ipc").innerText = "N/D";
        document.getElementById("fechaIndicadores").innerText = "Sin conexión";
      }
    }
    cargarIndicadores();
    setInterval(cargarIndicadores, 60000);
  </script>

<?php
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

    // Limitar intentos de login
    if ($_SESSION['login_attempts'] >= 5) {
        echo '<script>
          Swal.fire({
            icon:"error",
            title:"Demasiados intentos",
            text:"Has superado el número máximo de intentos. Intenta más tarde."
          });
        </script>';
        exit;
    }

    if ($captchaHabilitado) {
        if (!isset($_POST['g-recaptcha-response'])) {
            echo '<script>
              Swal.fire({
                icon:"error",
                title:"Captcha requerido",
                text:"Por favor verifica que no eres un robot."
              });
            </script>';
            exit;
        }

        $captcha_response = $_POST['g-recaptcha-response'];
        $verify_response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$captcha_response}");
        $response_data = json_decode($verify_response);

        if (!$response_data->success) {
            echo '<script>
              Swal.fire({
                icon:"error",
                title:"Captcha incorrecto",
                text:"Por favor verifica que no eres un robot."
              });
            </script>';
            exit;
        }
    }

    // Sanitizar entradas
    $NOMBRE = trim($_POST['NOMBRE']);
    $CONTRASENA = $_POST['CONTRASENA'];

    if ($NOMBRE === "" || $CONTRASENA === "") {
        echo '<script>
          Swal.fire({
            icon:"warning",
            title:"Alerta",
            text:"Debes ingresar usuario y contraseña"
          });
        </script>';
        exit;
    }

    if (strlen($CONTRASENA) < 6) {
        echo '<script>
          Swal.fire({
            icon:"warning",
            title:"Alerta",
            text:"La contraseña debe tener al menos 6 caracteres."
          });
        </script>';
        exit;
    }

    $ARRAYINICIOSESSION = $USUARIO_ADO->iniciarSession($NOMBRE, $CONTRASENA);

    if (!$ARRAYINICIOSESSION) {
        $_SESSION['login_attempts']++;
        echo '<script>
          Swal.fire({
            icon:"error",
            title:"Error",
            text:"Usuario o contraseña incorrectos"
          });
        </script>';
        exit;
    } else {
        session_regenerate_id(true);
        $_SESSION['login_attempts'] = 0;

        $_SESSION["ID_USUARIO"] = $ARRAYINICIOSESSION[0]['ID_USUARIO'];
        $_SESSION["NOMBRE_USUARIO"] = $ARRAYINICIOSESSION[0]['NOMBRE_USUARIO'];
        $_SESSION["TIPO_USUARIO"] = $ARRAYINICIOSESSION[0]['ID_TUSUARIO'];

        echo '<script>
          Swal.fire({
            icon:"success",
            title:"Éxito",
            text:"Inicio de sesión correcto",
            timer:2000,
            timerProgressBar:true,
            showConfirmButton:false,
            willClose: () => { window.location.href = "iniciarSessionSeleccion.php"; }
          });
        </script>';
        exit;
    }
}
?>
</body>
</html>
