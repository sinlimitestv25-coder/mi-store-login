<?php
require_once __DIR__ . '/config.php';
startSession();
requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>DASHBOARD - <?= APP_NAME ?></title>title>
<style>
  *{box-sizing:border-box;}
  html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
  body{position:relative;overflow-x:hidden;}
  .bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,0.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,0.12),transparent 24%),radial-gradient(circle at 50% 80%,rgba(124,58,237,0.10),transparent 30%),linear-gradient(135deg,#030303 0%,#07070b 40%,#050505 100%);z-index:0;}
  .grid-lines{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,0.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,0.10) 1px,transparent 1px);background-size:40px 40px;transform:perspective(900px) rotateX(70deg) translateY(-40px);opacity:0.28;z-index:1;transition:transform 0.12s linear;}
  .glow{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,0.20) 0%,rgba(178,102,255,0.08) 35%,transparent 70%);pointer-events:none;transform:translate(-50%,-50%);z-index:2;}
  .container{position:relative;z-index:3;max-width:1450px;margin:0 auto;padding:28px;}
  .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:12px;flex-wrap:wrap;}
  .brand-area{display:flex;align-items:center;gap:12px;}
  .brand-name{font-size:22px;font-weight:900;color:#b266ff;letter-spacing:0.04em;text-transform:uppercase;}
  .top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
  .mini-pill{padding:10px 14px;border-radius:12px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);color:#d8d8d8;font-weight:bold;}
  .logout-btn{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 16px;border-radius:12px;font-weight:bold;}
  .hero{display:grid;grid-template-columns:1.1fr 0.9fr;gap:22px;margin-bottom:24px;}
  .hero-card{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:24px;padding:26px;box-shadow:0 0 28px rgba(124,58,237,0.12);}
  .hero-left{display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap;}
  .hero-copy h1{margin:0 0 10px 0;font-size:34px;text-transform:uppercase;}
  .hero-copy p{margin:0;color:#c7c7c7;line-height:1.6;}
  .role-pill{display:inline-block;padding:8px 16px;border-radius:999px;background:#0a2e1c;color:#8df0bf;font-size:13px;font-weight:bold;border:1px solid #1e5c44;margin-top:14px;}
  .alert-box{border-radius:16px;padding:16px 20px;margin-top:16px;font-size:14px;line-height:1.7;}
  .alert-success{background:#0a2015;border:1px solid #1e5c44;color:#8df0bf;}
  .badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:bold;text-transform:uppercase;}
  .activo{background:#103525;color:#8df0bf;}
  .quick-links{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
  .quick-btn{display:flex;align-items:center;justify-content:center;text-decoration:none;min-height:92px;border-radius:18px;background:linear-gradient(135deg,rgba(124,58,237,0.95),rgba(16,185,129,0.85));color:#fff;font-weight:900;text-transform:uppercase;letter-spacing:0.06em;transition:0.2s ease;text-align:center;padding:12px;}
  .quick-btn:hover{transform:translateY(-2px) scale(1.01);box-shadow:0 0 24px rgba(124,58,237,0.20);}
  .cards{display:grid;grid-template-columns:repeat(4,minmax(220px,1fr));gap:18px;margin-bottom:18px;}
  .cards.three{grid-template-columns:repeat(3,minmax(220px,1fr));}
  .card{display:block;text-decoration:none;color:inherit;background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:22px;transition:0.2s ease;box-shadow:0 0 24px rgba(124,58,237,0.07);}
  .card:hover{transform:translateY(-3px);border-color:rgba(178,102,255,0.45);box-shadow:0 0 26px rgba(124,58,237,0.16);}
  .card-title{color:#a9a9a9;font-size:14px;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.05em;}
  .card-value{font-size:32px;font-weight:900;}
  .card-sub{margin-top:8px;color:#8f8f8f;font-size:13px;}
  .green{color:#8df0bf;}.yellow{color:#ffe08d;}.red{color:#ffaaaa;}.violet{color:#c084fc;}.cyan{color:#8dc6ff;}
  .section-title{font-size:18px;font-weight:900;color:#b266ff;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 16px 0;}
  .progress-bar{width:100%;height:16px;border-radius:999px;background:#111216;overflow:hidden;display:flex;}
  .seg-activo{background:#10b981;height:100%;}
  .seg-vencer{background:#f59e0b;height:100%;}
  .seg-suspendido{background:#ef4444;height:100%;}
  .progress-legend{display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;color:#cfcfcf;font-size:13px;}
  .bottom{display:grid;grid-template-columns:1.15fr 0.85fr;gap:18px;margin-top:12px;}
  .panel{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:22px;box-shadow:0 0 24px rgba(124,58,237,0.07);}
  .panel h3{margin:0 0 10px 0;color:#b266ff;text-transform:uppercase;letter-spacing:0.04em;}
  .panel-sub{color:#b7b7b7;margin-bottom:14px;}
  .notify-item{border:1px solid rgba(255,255,255,0.08);background:#111216;border-radius:16px;padding:14px;margin-bottom:12px;}
  .notify-name{font-weight:bold;margin-bottom:4px;}
  .notify-service{font-size:13px;color:#a9a9a9;margin-bottom:6px;}
  .notify-days{font-size:13px;color:#ffe08d;}
  .table-wrap{overflow-x:auto;}
  .table-premium{width:100%;border-collapse:collapse;}
  .table-premium th,.table-premium td{padding:13px 16px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;}
  .table-premium th{color:#9adfc3;background:#111216;text-transform:uppercase;font-size:12px;letter-spacing:0.05em;}
  .table-premium tr:hover td{background:#111216;}
  @media(max-width:900px){.hero,.bottom{grid-template-columns:1fr;}.cards{grid-template-columns:repeat(2,1fr);}.cards.three{grid-template-columns:repeat(2,1fr);}.quick-links{grid-template-columns:repeat(2,1fr);}}
  @media(max-width:500px){.cards{grid-template-columns:1fr;}}
  </style>
</head>head>
  <body>
    <div class="bg"></div>div>
    <div class="grid-lines" id="grid"></div>div>
    <div class="glow" id="glow"></div>div>
    <div class="container">
        <!-- TOPBAR -->
        <div class="topbar">
              <div class="brand-area">
                      <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;font-size:24px;">🚀</div>div>
                      <div class="brand-name"><?= APP_NAME ?></div>
              </div>div>
              <div class="top-actions">
                      <div class="mini-pill">CREDITOS: <?= htmlspecialchars($user['creditos'] ?? 0) ?></div>div>
                      <div class="mini-pill"><?= htmlspecialchars(strtoupper($user['rol'] ?? '')) ?></div>
                      <a href="logout.php" class="logout-btn">SALIR</a>a>
              </div>div>
        </div>div>

        <!-- HERO -->
        <div class="hero">
              <div class="hero-card hero-left">
                      <div class="hero-copy">
                                <h1>BIENVENIDO, <?= htmlspecialchars(strtoupper($user['nombre'] ?? 'USUARIO')) ?></h1>h1>
                                <p>Panel premium para administrar clientes, servicios, cuentas reseller, usuarios finales, renovaciones y vencimientos con una interfaz moderna.</p>p>
                              <div class="role-pill">SISTEMA ACTIVO</div>div>
                              <div class="alert-box alert-success" style="margin-top:16px;">
                                          <strong>Sesion activa como:</strong>strong> <?= htmlspecialchars($user['email'] ?? '') ?>
                              </div>div>
                      </div>div>
                      <div style="font-size:64px;">🚀</div>div>
              </div>div>
              <div class="hero-card">
                      <div class="section-title" style="color:#b266ff;margin-bottom:16px;">ACCESOS RAPIDOS</div>div>
                      <div class="quick-links">
                                <a href="services.php" class="quick-btn">🔧 SERVICIOS</a>a>
                                <a href="clients.php" class="quick-btn">👤 CLIENTES</a>a>
                                <a href="users.php" class="quick-btn">👑 USUARIOS</a>a>
                                <a href="#" class="quick-btn">🎬 STREAMING</a>a>
                                <a href="#" class="quick-btn">🎮 GAMING</a>a>
                      </div>div>
              </div>div>
        </div>div>

        <!-- STATS CARDS -->
        <div class="cards">
              <a href="clients.php" class="card">
                      <div class="card-title">CLIENTES</div>div>
                      <div class="card-value green">0</div>div>
                      <div class="card-sub">Control total de clientes registrados</div>div>
              </a>a>
              <a href="services.php" class="card">
                      <div class="card-title">SERVICIOS</div>div>
                      <div class="card-value violet">0</div>div>
                      <div class="card-sub">Catalogo global y servicios propios</div>div>
              </a>a>
              <a href="users.php" class="card">
                      <div class="card-title">USUARIOS</div>div>
                      <div class="card-value cyan">0</div>div>
                      <div class="card-sub">Paneles creados y gestionados</div>div>
              </a>a>
              <div class="card">
                      <div class="card-title">CREDITOS</div>div>
                      <div class="card-value"><?= htmlspecialchars($user['creditos'] ?? 0) ?></div>
                      <div class="card-sub">Capacidad disponible de tu cuenta</div>div>
              </div>div>
        </div>div>

        <!-- RESUMEN VISUAL -->
        <div class="panel" style="margin-bottom:18px;">
              <div class="section-title">RESUMEN VISUAL DE CLIENTES</div>div>
              <div class="progress-bar">
                      <div class="seg-activo" style="width:79%"></div>div>
                      <div class="seg-vencer" style="width:3%"></div>div>
                      <div class="seg-suspendido" style="width:18%"></div>div>
              </div>div>
              <div class="progress-legend">
                      <span>ACTIVOS: 79%</span>span>
                      <span>POR VENCER: 3%</span>span>
                      <span>VENCIDOS: 18%</span>span>
              </div>div>
        </div>div>
    </div>div>
    <script>
      var glow=document.getElementById('glow');
      var grid=document.getElementById('grid');
      document.addEventListener('mousemove',function(e){
          glow.style.left=e.clientX+'px';
          glow.style.top=e.clientY+'px';
          var x=(e.clientX/window.innerWidth-0.5)*25;
          var y=(e.clientY/window.innerHeight-0.5)*15;
          grid.style.transform='perspective(900px) rotateX(70deg) translate('+x+'px,'+y+'px)';
      });
    </script>
  </body>body>
</html>html>
    </script>
                              </p>
</style></title>
</head>
