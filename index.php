<!DOCTYPE html>
<html lang="es">
<head>
    <title>Sistemas - Volcan Foods</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="icon" href="assest/img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        :root {
            --azul: #0a3a6a;
            --rojo: #a12a1a;
            --verde: #4caf50;
            --blanco: #fff;
            --gris: #f5f7fa;
        }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Montserrat', Arial, sans-serif;
            background: var(--gris);
        }
        body, .main-container {
            min-height: 100vh;
            width: 100vw;
        }
        .main-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        .left-panel {
            flex: 1.1 0 0;
            min-width: 340px;
            max-width: 440px;
            background: var(--blanco);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            z-index: 2;
            position: relative;
            padding: 0 0 48px 0;
        }
        .logo-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 48px;
            margin-top: 40px;
            width: 100%;
        }
        .logo-header img {
            width: 180px;
            max-width: 80vw;
            margin-bottom: 14px;
            filter: drop-shadow(0 2px 8px rgba(10,58,106,0.08));
        }
        .web-address {
            font-size: 1.08rem;
            color: var(--azul);
            font-weight: 600;
            text-align: center;
        }
        .glass-card {
            background: var(--blanco);
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(10,58,106,0.04);
            border: 1px solid #e6eaf0;
            padding: 32px 28px 24px 28px;
            width: 100%;
            max-width: 360px;
            margin: 0 auto 36px auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .content-top-agile h2 {
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--azul);
            margin-bottom: 24px;
            text-align: center;
        }
        .button-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
        }
        .modern-btn-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--blanco);
            border-radius: 8px;
            border: 1.2px solid #bfc9d8;
            padding: 12px 18px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: var(--azul);
            transition: all 0.18s;
            width: 100%;
        }
        .modern-btn-card .material-icons {
            font-size: 1.2rem;
            color: var(--azul);
            background: #f2f6fa;
            border-radius: 50%;
            padding: 4px;
        }
        .modern-btn-card:hover {
            background: #f2f6fa;
            border: 1.2px solid var(--verde);
        }
        .modern-btn-card:hover .material-icons {
            color: var(--verde);
            background: #e8f5e9;
        }
        .btn-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .btn-title {
            font-size: 1rem;
            font-weight: 700;
        }
        .btn-desc {
            font-size: 0.89rem;
            color: #7a8ca0;
        }
        .indicadores-panel {
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
            background: var(--blanco);
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(10,58,106,0.03);
            border: 1px solid #e6eaf0;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .indicadores-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--azul);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .indicadores-list {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 20px;
        }
        .indicador {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.95rem;
            color: var(--azul);
            min-width: 70px;
        }
        .indicador .valor {
            font-size: 1.05em;
            font-weight: 700;
            color: var(--rojo);
            margin-top: 2px;
        }
        .eco-date {
            font-size: 0.85rem;
            color: #666;
            text-align: center;
            margin-top: 6px;
        }
        .green-divider {
            width: 18px;
            min-width: 18px;
            height: 100vh;
        }
        .green-divider svg {
            height: 100%;
            width: 100%;
        }
        .right-panel {
            flex: 2;
            position: relative;
            overflow: hidden;
        }
        .slider-bg .slide {
            position: absolute;
            width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s;
        }
        .slider-bg .slide.active {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="left-panel">
            <div class="logo-header">
                <img src="assest/img/logo2.png" alt="Volcan Foods Logo">
                <div class="web-address">www.volcanfoods.cl</div>
            </div>
            <div class="glass-card">
                <div class="content-top-agile">
                    <h2>SELECCIÓN INTRANET</h2>
                </div>
                <div class="button-grid">
                    <!-- ✅ Corregido el enlace a acceso interno -->
                    <a href="./interno.php" style="text-decoration: none;">
                        <button type="button" class="modern-btn-card">
                            <span class="material-icons">account_circle</span>
                            <span class="btn-content">
                                <span class="btn-title">Acceso Interno</span>
                                <span class="btn-desc">Usuarios internos y administrativos</span>
                            </span>
                        </button>
                    </a>
                    <a href="./productor/" style="text-decoration: none;">
                        <button type="button" class="modern-btn-card">
                            <span class="material-icons">warehouse</span>
                            <span class="btn-content">
                                <span class="btn-title">Portal Productores</span>
                                <span class="btn-desc">Acceso para productores registrados</span>
                            </span>
                        </button>
                    </a>
                </div>
            </div>
            <div class="indicadores-panel">
                <div class="indicadores-title">
                    <span class="material-icons">trending_up</span>
                    Indicadores Económicos
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
                <!-- ✅ Fecha de actualización -->
                <div class="eco-date" id="fechaIndicadores">Actualizado: -</div>
            </div>
        </div>

        <div class="green-divider">
            <svg viewBox="0 0 18 1000" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9,0 Q18,500 9,1000" stroke="#4caf50" stroke-width="3" fill="none"/>
            </svg>
        </div>
        <div class="right-panel">
            <div class="slider-bg">
                <div class="slide active" style="background-image: url('assest/img/abeja.jpg');"></div>
                <div class="slide" style="background-image: url('assest/img/arandano.jpg');"></div>
                <div class="slide" style="background-image: url('assest/img/esparragos.jpg');"></div>
            </div>
        </div>
    </div>
    <script>
        // Slider automático
        const slides = document.querySelectorAll('.slider-bg .slide');
        let current = 0;
        setInterval(() => {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
        }, 5000);

        // Indicadores económicos
        async function cargarIndicadores() {
            try {
                const resp = await fetch('https://mindicador.cl/api');
                const data = await resp.json();
                document.getElementById('uf').textContent = data.uf.valor.toLocaleString('es-CL', {style: 'currency', currency: 'CLP'});
                document.getElementById('dolar').textContent = data.dolar.valor.toLocaleString('es-CL', {style: 'currency', currency: 'CLP'});
                document.getElementById('euro').textContent = data.euro.valor.toLocaleString('es-CL', {style: 'currency', currency: 'CLP'});
                document.getElementById('fechaIndicadores').textContent = "Actualizado: " + new Date(data.fecha).toLocaleDateString("es-CL");
            } catch (e) {
                document.getElementById('uf').textContent = "N/D";
                document.getElementById('dolar').textContent = "N/D";
                document.getElementById('euro').textContent = "N/D";
                document.getElementById('fechaIndicadores').textContent = "Sin conexión";
            }
        }
        cargarIndicadores();
    </script>
</body>
</html>
