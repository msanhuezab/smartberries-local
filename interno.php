<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistemas Internos - Volcan Foods</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="icon" href="assest/img/favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        :root {
            --azul: #0a3a6a;
            --rojo: #a12a1a;
            --verde: #4caf50;
            --blanco: #fff;
            --gris: #f5f7fa;
        }
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Montserrat',sans-serif}
        body,html{height:100%;background:var(--gris);}
        .container{display:flex;min-height:100vh;overflow:hidden}
        .left-panel{
            flex:1.1;max-width:440px;min-width:320px;background:var(--blanco);
            display:flex;flex-direction:column;align-items:center;justify-content:flex-start;
            padding:30px 20px;z-index:2;
        }
        .logo-header{display:flex;flex-direction:column;align-items:center;margin-bottom:20px}
        .logo-header img{width:160px;max-width:100%;margin-bottom:10px}
        .logo-header .web-address{font-size:.95rem;font-weight:600;color:var(--azul);}
        .glass-card{
            background:var(--blanco);border-radius:12px;padding:25px;
            width:100%;max-width:360px;margin:20px auto;
            border:1px solid #e0e6ef;box-shadow:0 2px 8px rgba(0,0,0,.06);
        }
        .glass-card h2{text-align:center;font-size:1.1rem;font-weight:700;color:var(--azul);margin-bottom:20px}
        .button-grid{display:flex;flex-direction:column;gap:15px}
        .modern-btn-card{
            display:flex;align-items:center;gap:12px;padding:12px 16px;width:100%;
            border:1px solid #bfc9d8;border-radius:8px;background:var(--blanco);
            font-weight:600;font-size:1rem;color:var(--azul);cursor:pointer;
            transition:.25s;text-align:left;
        }
        .modern-btn-card .material-icons{
            background:#f2f6fa;color:var(--azul);padding:6px;border-radius:50%;font-size:1.1rem;
        }
        .modern-btn-card:hover{border:1px solid var(--verde);background:#f8fdf8;transform:translateY(-2px)}
        .modern-btn-card:hover .material-icons{color:var(--verde);background:#e8f5e9}
        .btn-content{display:flex;flex-direction:column}
        .btn-title{font-size:1rem;font-weight:700}
        .btn-desc{font-size:.86rem;font-weight:400;color:#7a8ca0}
        .indicadores-panel{
            background:var(--blanco);border-radius:12px;padding:15px 18px;margin-top:25px;
            border:1px solid #e0e6ef;box-shadow:0 1px 6px rgba(0,0,0,0.03);max-width:360px;width:100%;
        }
        .indicadores-title{font-size:.95rem;font-weight:700;color:var(--azul);margin-bottom:8px;display:flex;align-items:center;gap:6px}
        .indicadores-list{display:flex;justify-content:space-between;}
        .indicador{text-align:center;flex:1}
        .indicador .label{font-size:.85rem;font-weight:600;color:var(--azul)}
        .indicador .valor{font-size:1rem;font-weight:700;color:var(--rojo);margin-top:2px}
        .eco-date{text-align:center;font-size:.8rem;color:#666;margin-top:6px}
        .green-divider{width:18px;min-width:18px;background:transparent;}
        .green-divider svg{width:100%;height:100%}
        .right-panel{
            flex:2;position:relative;overflow:hidden;
            display:flex;align-items:center;justify-content:center;
        }
        .slide{
            position:absolute;width:100%;height:100%;
            top:0;left:0;background-size:cover;background-position:center;
            opacity:0;transition:opacity 2s ease-in-out, transform 7s ease-in-out;
            transform:scale(1.05);
        }
        .slide.active{
            opacity:1;transform:scale(1);
        }
        @media(max-width:900px){
            .container{flex-direction:column}
            .left-panel{max-width:100%;width:100%;box-shadow:none}
            .green-divider{display:none}
            .right-panel{height:220px}
        }
    </style>
</head>
<body>
  <div class="container">
    <div class="left-panel">
        <div class="logo-header">
            <img src="https://volcanfoods.cl/wp-content/uploads/2024/09/volcan_borde.png.webp" alt="Volcan Foods Logo">
            <div class="web-address">www.volcanfoods.cl</div>

        </div>
        <div class="glass-card">
            <h2>SISTEMAS INTERNOS</h2>
            <div class="button-grid">
                <a href="fruta/" style="text-decoration:none">
                    <button type="button" class="modern-btn-card">
                        <span class="material-icons">local_florist</span>
                        <span class="btn-content">
                            <span class="btn-title">Fruta</span>
                            <span class="btn-desc">Módulo de gestión de fruta</span>
                        </span>
                    </button>
                </a>
                <a href="material/" style="text-decoration:none">
                    <button type="button" class="modern-btn-card">
                        <span class="material-icons">inventory_2</span>
                        <span class="btn-content">
                            <span class="btn-title">Material</span>
                            <span class="btn-desc">Módulo de gestión de material</span>
                        </span>
                    </button>
                </a>
                <a href="exportadora/" style="text-decoration:none">
                    <button type="button" class="modern-btn-card">
                        <span class="material-icons">flight_takeoff</span>
                        <span class="btn-content">
                            <span class="btn-title">Exportadora</span>
                            <span class="btn-desc">Gestión del área exportadora</span>
                        </span>
                    </button>
                </a>
                <a href="configuracion/" style="text-decoration:none">
                    <button type="button" class="modern-btn-card">
                        <span class="material-icons">settings</span>
                        <span class="btn-content">
                            <span class="btn-title">Configuración</span>
                            <span class="btn-desc">Parámetros, usuarios y tareas del sistema</span>
                        </span>
                    </button>
                </a>
                <a href="calidad/" style="text-decoration:none">
                    <button type="button" class="modern-btn-card">
                        <span class="material-icons">fact_check</span>
                        <span class="btn-content">
                            <span class="btn-title">Calidad</span>
                            <span class="btn-desc">Control y parametros de calidad</span>
                        </span>
                    </button>
                </a>
                <a href="estadistica/" style="text-decoration:none">
                    <button type="button" class="modern-btn-card">
                        <span class="material-icons">bar_chart</span>
                        <span class="btn-content">
                            <span class="btn-title">Estadísticas</span>
                            <span class="btn-desc">Información y reportes</span>
                        </span>
                    </button>
                </a>
            </div>
        </div>

        <!-- Indicadores económicos -->
        <div class="indicadores-panel">
            <div class="indicadores-title">
                <span class="material-icons">trending_up</span> Indicadores Económicos
            </div>
            <div class="indicadores-list">
                <div class="indicador">
                    <span class="label">UF</span>
                    <span class="valor" id="uf">-</span>
                </div>
                <div class="indicador">
                    <span class="label">Dólar</span>
                    <span class="valor" id="dolar">-</span>
                </div>
                <div class="indicador">
                    <span class="label">Euro</span>
                    <span class="valor" id="euro">-</span>
                </div>
            </div>
            <div class="eco-date" id="fechaIndicadores">Actualizado: -</div>
        </div>
    </div>

    <div class="green-divider">
        <svg viewBox="0 0 18 1000" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9,0 Q18,500 9,1000" stroke="#4caf50" stroke-width="3" fill="none"/>
        </svg>
    </div>

    <!-- PANEL DERECHO CON SLIDER DE IMÁGENES -->
    <div class="right-panel">
            <div class="slide active" style="background-image:url('assest/img/abeja.jpg')"></div>
<div class="slide" style="background-image:url('assest/img/arandano.jpg')"></div>
<div class="slide" style="background-image:url('assest/img/esparragos.jpg')"></div>
<div class="slide" style="background-image:url('assest/img/abeja.jpg')"></div>


    </div>
  </div>

  <script>
    // Slider automático con transición suave
    const slides = document.querySelectorAll('.slide');
    let i = 0;
    setInterval(() => {
      slides[i].classList.remove('active');
      i = (i + 1) % slides.length;
      slides[i].classList.add('active');
    }, 6000);

    // Indicadores económicos dinámicos
    async function cargarIndicadores() {
      try {
        const resp = await fetch("https://mindicador.cl/api");
        const data = await resp.json();
        document.getElementById("uf").textContent = data.uf.valor.toLocaleString("es-CL",{style:"currency",currency:"CLP"});
        document.getElementById("dolar").textContent = data.dolar.valor.toLocaleString("es-CL",{style:"currency",currency:"CLP"});
        document.getElementById("euro").textContent = data.euro.valor.toLocaleString("es-CL",{style:"currency",currency:"CLP"});
        document.getElementById("fechaIndicadores").textContent="Actualizado: "+new Date(data.fecha).toLocaleDateString("es-CL");
      } catch(e){
        document.getElementById("uf").textContent="N/D";
        document.getElementById("dolar").textContent="N/D";
        document.getElementById("euro").textContent="N/D";
        document.getElementById("fechaIndicadores").textContent="Sin conexión";
      }
    }
    cargarIndicadores();
    setInterval(cargarIndicadores,60000);
  </script>
</body>
</html>
