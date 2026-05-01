<?php
error_reporting(0);
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
      <title>DASHBOARD - MI STORE</title>title>
  <style>
          *{box-sizing:border-box;}
          html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
          body{position:relative;overflow-x:hidden;}
          .bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,0.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,0.12),transparent 35%);pointer-events:none;z-index:0;}
          .grid-lines{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,0.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,0.10) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0;}
          .glow{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,0.20) 0%,rgba(178,102,255,0.08) 35%,transparent 70%);top:-100px;right:-100px;pointer-events:none;z-index:0;}
          .container{position:relative;z-index:3;max-width:1450px;margin:0 auto;padding:28px;}
          .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:12px;flex-wrap:wrap;}
          .brand-area{display:flex;align-items:center;gap:12px;}
          .brand-logo{width:40px;height:40px;border-radius:10px;object-fit:contain;}
          .brand-name{font-size:22px;font-weight:900;color:#b266ff;letter-spacing:0.04em;text-transform:uppercase;}
          .top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
          .mini-pill{padding:10px 14px;border-radius:12px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);color:#d8d8d8;font-weight:bold;}
          .logout-btn{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 16px;border-radius:12px;font-weight:bold;}
          .settings-btn{text-decoration:none;color:#fff;background:rgba(178,102,255,0.18);border:1px solid rgba(178,102,255,0.35);padding:10px 16px;border-radius:12px;font-weight:bold;}
          .hero{display:grid;grid-template-columns:1.1fr 0.9fr;gap:22px;margin-bottom:24px;}
          .hero-card{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:24px;padding:26px;box-shadow:0 0 28px rgba(124,58,237,0.10);}
          .hero-left{display:flex;justify-content:space-between;align-items:center;gap:18px;}
          .hero-text h1{font-size:28px;font-weight:900;background:linear-gradient(90deg,#b266ff,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin:0 0 6px;}
          .hero-text p{color:#a0a0b0;margin:0 0 18px;font-size:15px;}
          .hero-badges{display:flex;gap:8px;flex-wrap:wrap;}
          .badge{padding:6px 14px;border-radius:999px;font-size:12px;font-weight:bold;border:1px solid rgba(255,255,255,0.12);}
          .badge-green{background:#0a2e1c;color:#8df0bf;}
          .badge-purple{background:#1a0a2e;color:#c084fc;}
          .role-pill{display:inline-block;padding:8px 16px;border-radius:999px;background:#0a2e1c;color:#8df0bf;font-size:13px;font-weight:bold;border:1px solid #1e5c44;margin-bottom:10px;}
          .hero-rocket{font-size:80px;line-height:1;filter:drop-shadow(0 0 18px #7c3aed);}
          .credits-card{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(178,102,255,0.20);border-radius:24px;padding:26px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;}
          .credits-label{font-size:13px;color:#a0a0b0;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:8px;}
          .credits-value{font-size:52px;font-weight:900;background:linear-gradient(90deg,#b266ff,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;}
          .credits-sub{font-size:12px;color:#6b6b80;margin-top:8px;}
          .session-bar{background:rgba(10,46,28,0.60);border:1px solid rgba(16,185,129,0.20);border-radius:14px;padding:12px 20px;margin-bottom:24px;font-size:13px;color:#8df0bf;}
          .quick-title{font-size:18px;font-weight:800;color:#b266ff;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:14px;}
          .quick-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:28px;}
          .quick-card{background:rgba(14,14,18,0.82);border:1px solid rgba(255,255,255,0.08);border-radius:18px;padding:22px 16px;text-align:center;text-decoration:none;color:#fff;transition:transform 0.18s,box-shadow 0.18s;display:block;}
          .quick-card:hover{transform:translateY(-4px);box-shadow:0 8px 32px rgba(124,58,237,0.25);}
          .quick-card .icon{font-size:32px;margin-bottom:8px;}
          .quick-card .label{font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;}
          .quick-card.grad1{background:linear-gradient(135deg,rgba(124,58,237,0.22),rgba(16,185,129,0.12));}
          .quick-card.grad2{background:linear-gradient(135deg,rgba(16,185,129,0.22),rgba(124,58,237,0.12));}
          .quick-card.grad3{background:linear-gradient(135deg,rgba(178,102,255,0.22),rgba(16,185,129,0.12));}
          .quick-card.grad4{background:linear-gradient(135deg,rgba(245,158,11,0.22),rgba(16,185,129,0.12));}
          .quick-card.grad5{background:linear-gradient(135deg,rgba(178,102,255,0.28),rgba(59,130,246,0.15));}
          .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:28px;}
          .stat-card{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:22px;transition:transform 0.18s;}
          .stat-card:hover{transform:translateY(-3px);}
          .stat-label{font-size:12px;color:#a0a0b0;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:8px;}
          .stat-value{font-size:36px;font-weight:900;background:linear-gradient(90deg,#b266ff,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;margin-bottom:4px;}
          .stat-desc{font-size:12px;color:#6b6b80;}
          @media(max-width:768px){.hero{grid-template-columns:1fr;}.hero-rocket{display:none;}.hero-left{flex-direction:column;align-items:flex-start;}}
        </style>
</head>head>
    <body>
        <div class="bg"></div>div>
        <div class="grid-lines"></div>div>
        <div class="glow"></div>div>
        <div class="container">
              <div class="topbar">
                      <div class="brand-area">
                                <?php if(!empty($_SESSION['logo_url'])): ?>
          <img src="<?php echo htmlspecialchars($_SESSION['logo_url']); ?>" alt="Logo" class="brand-logo"/>
                                <?php endif; ?>
      <span class="brand-name">MI STORE</span>span>
                      </div>div>
                      <div class="top-actions">
                                <span class="mini-pill"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                                <span class="mini-pill">ROL: <?php echo strtoupper($user['rol'] ?? ''); ?></span>span>
                                <a href="settings.php" class="settings-btn">&#9881; CONFIGURACION</a>a>
                                <a href="logout.php" class="logout-btn">SALIR</a>a>
                      </div>div>
              </div>div>

              <div class="hero">
                      <div class="hero-card">
                                <div class="hero-left">
                                            <div class="hero-text">
                                                          <div class="role-pill">SISTEMA ACTIVO</div>div>
                                                          <h1>BIENVENIDO, <?php echo strtoupper($user['rol'] ?? ''); ?></h1>h1>
                                                          <p>Panel de control de MI STORE. Gestion de servicios, clientes y usuarios.</p>p>
                                                      <div class="hero-badges">
                                                                      <span class="badge badge-green">Online</span>span>
                                                                      <span class="badge badge-purple"><?php echo number_format((float)($user['creditos'] ?? 0)); ?> creditos</span>span>
                                                      </div>div>
                                            </div>div>
                                            <div class="hero-rocket">&#128640;</div>div>
                                </div>div>
                      </div>div>
                      <div class="credits-card">
                                <div class="credits-label">CREDITOS DISPONIBLES</div>div>
                                <div class="credits-value"><?php echo number_format((float)($user['creditos'] ?? 0)); ?></div>
                                <div class="credits-sub">Saldo actual de la cuenta</div>div>
                      </div>div>
              </div>div>

              <div class="session-bar">
                      Sesion activa como: <strong><?php echo htmlspecialchars($user['email'] ?? ''); ?></strong>
              </div>div>

              <div class="quick-title">ACCESOS RAPIDOS</div>div>
              <div class="quick-grid">
                      <a href="services.php" class="quick-card grad1"><div class="icon">S</div>div><div class="label">SERVICIOS</div>div></a>a>
                      <a href="clients.php" class="quick-card grad2"><div class="icon">C</div>div><div class="label">CLIENTES</div>div></a>a>
                      <a href="users.php" class="quick-card grad3"><div class="icon">U</div>div><div class="label">USUARIOS</div>div></a>a>
                      <a href="#" class="quick-card grad4"><div class="icon">$</div>div><div class="label">CREDITOS</div>div></a>a>
                      <a href="settings.php" class="quick-card grad5"><div class="icon">&#9881;</div>div><div class="label">CONFIGURACION</div>div></a>a>
              </div>div>

              <div class="stats-grid">
                      <div class="stat-card">
                                <div class="stat-label">CLIENTES</div>div>
                                <div class="stat-value">0</div>div>
                                <div class="stat-desc">Control total de clientes registrados</div>div>
                      </div>div>
                      <div class="stat-card">
                                <div class="stat-label">SERVICIOS</div>div>
                                <div class="stat-value">0</div>div>
                                <div class="stat-desc">Catalogo global y servicios propios</div>div>
                      </div>div>
                      <div class="stat-card">
                                <div class="stat-label">USUARIOS</div>div>
                                <div class="stat-value">0</div>div>
                                <div class="stat-desc">Gestion de usuarios del sistema</div>div>
                      </div>div>
                      <div class="stat-card">
                                <div class="stat-label">CREDITOS</div>div>
                                <div class="stat-value"><?php echo number_format((float)($user['creditos'] ?? 0)); ?></div>
                                <div class="stat-desc">Balance disponible en cuenta</div>div>
                      </div>div>
              </div>div>
        </div>div>
    </body>body>
</html>html>
                                                      </p>
  </style></title>
</head>
