<?php
// =============================================
// services.php - Gestion de Servicios
// =============================================
error_reporting(0);
require_once __DIR__ . '/config.php';
requireLogin();

$user = currentUser();
$db = getDB();
$msg = ''; $tipo = '';

// ---- CREAR servicio ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $nombre = clean($_POST['nombre'] ?? '');
    $logo = clean($_POST['logo_url'] ?? '');
    if (!$nombre) {
        $msg = 'El nombre del servicio es obligatorio.'; $tipo = 'error';
    } else {
        $db->prepare("INSERT INTO servicios (nombre, logo_url, creado_por) VALUES (?,?,?)")
           ->execute([$nombre, $logo, $user['id']]);
        $msg = 'Servicio creado correctamente.'; $tipo = 'ok';
    }
}

// ---- ELIMINAR servicio ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $sid = (int)($_POST['servicio_id'] ?? 0);
    $db->prepare("DELETE FROM servicios WHERE id = ? AND creado_por = ?")->execute([$sid, $user['id']]);
    $msg = 'Servicio eliminado.'; $tipo = 'ok';
}

// ---- LEER servicios ----
$stmt = $db->prepare("
    SELECT s.*, u.nombre AS creador
    FROM servicios s JOIN usuarios u ON u.id = s.creado_por
    WHERE s.creado_por = ? OR u.rol = 'admin'
    ORDER BY s.nombre
");
$stmt->execute([$user['id']]);
$servicios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>SERVICIOS - <?= APP_NAME ?></title>
<style>
*{box-sizing:border-box;}
html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
body{position:relative;overflow-x:hidden;}
.bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,0.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,0.12),transparent 24%),linear-gradient(135deg,#030303 0%,#07070b 40%,#050505 100%);z-index:0;}
.grid-lines{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,0.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,0.10) 1px,transparent 1px);background-size:40px 40px;transform:perspective(900px) rotateX(70deg) translateY(-40px);opacity:0.28;z-index:1;}
.glow{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,0.20) 0%,transparent 70%);pointer-events:none;transform:translate(-50%,-50%);z-index:2;}
.container{position:relative;z-index:3;max-width:1450px;margin:0 auto;padding:28px;}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:12px;flex-wrap:wrap;}
.brand-name{font-size:22px;font-weight:900;color:#b266ff;letter-spacing:0.04em;text-transform:uppercase;}
.back-btn{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 16px;border-radius:12px;font-weight:bold;}
.layout{display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;}
.sidebar,.main-panel{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:24px;box-shadow:0 0 24px rgba(124,58,237,0.07);}
.sidebar h2,.main-panel h2{margin:0 0 6px 0;font-size:20px;font-weight:900;color:#b266ff;text-transform:uppercase;}
.sidebar p,.main-panel > p{margin:0 0 18px 0;color:#a9a9a9;font-size:14px;}
.input{width:100%;padding:13px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:#151518;color:#fff;margin-bottom:14px;outline:none;font-size:14px;}
.input::placeholder{color:#666;}
.btn{width:100%;padding:14px;border:none;border-radius:14px;cursor:pointer;font-weight:900;background:linear-gradient(90deg,#7c3aed,#10b981);color:#fff;text-transform:uppercase;font-size:14px;}
.services-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
.service-card{background:#111216;border:1px solid rgba(255,255,255,0.08);border-radius:20px;overflow:hidden;transition:0.2s;}
.service-card:hover{border-color:rgba(178,102,255,0.35);transform:translateY(-3px);}
.service-img{width:100%;height:150px;background:linear-gradient(135deg,rgba(124,58,237,0.4),rgba(16,185,129,0.3));display:flex;align-items:center;justify-content:center;font-size:48px;overflow:hidden;}
.service-img img{width:100%;height:100%;object-fit:cover;}
.service-info{padding:14px;}
.service-name{font-size:15px;font-weight:900;text-transform:uppercase;margin-bottom:2px;}
.service-creator{font-size:12px;color:#a9a9a9;margin-bottom:10px;}
.service-actions{display:flex;gap:8px;}
.action{padding:7px 12px;border-radius:10px;font-size:11px;font-weight:bold;text-transform:uppercase;cursor:pointer;border:none;}
.delete{background:#4a1717;color:#ffb0b0;}
.msg-box{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:14px;}
.msg-ok{background:#0f2f22;border:1px solid #1e5c44;color:#8df0bf;}
.msg-error{background:#351010;border:1px solid #662222;color:#ff9c9c;}
.empty-box{border:2px dashed rgba(255,255,255,0.08);border-radius:20px;padding:40px;text-align:center;color:#555;grid-column:1/-1;}
@media(max-width:900px){.layout{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="bg"></div>
<div class="grid-lines" id="grid"></div>
<div class="glow" id="glow"></div>
<div class="container">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;font-size:24px;">🚀</div>
            <div class="brand-name"><?= APP_NAME ?></div>
        </div>
        <a href="dashboard.php" class="back-btn">VOLVER</a>
    </div>
    <?php if ($msg): ?><div class="msg-box msg-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <div class="layout">
        <div class="sidebar">
            <h2>NUEVO SERVICIO</h2>
            <p>Crea servicios con logo editable por URL.</p>
            <form method="POST">
                <input type="hidden" name="accion" value="crear"/>
                <input class="input" type="text" name="nombre" placeholder="Nombre del servicio" required/>
                <input class="input" type="url" name="logo_url" placeholder="URL del logo o imagen"/>
                <button type="submit" class="btn">CREAR SERVICIO</button>
            </form>
        </div>
        <div class="main-panel">
            <h2>SERVICIOS DISPONIBLES</h2>
            <p>Servicios globales del admin y servicios propios del usuario.</p>
            <div class="services-grid">
                <?php if (empty($servicios)): ?>
                <div class="empty-box">No hay servicios aun. Crea el primero.</div>
                <?php endif; ?>
                <?php foreach ($servicios as $s): ?>
                <div class="service-card">
                    <div class="service-img">
                        <?php if ($s['logo_url']): ?>
                        <img src="<?= htmlspecialchars($s['logo_url']) ?>" alt="<?= htmlspecialchars($s['nombre']) ?>" onerror="this.parentElement.innerHTML='&#128225;'"/>
                        <?php else: ?>&#128250;<?php endif; ?>
                    </div>
                    <div class="service-info">
                        <div class="service-name"><?= htmlspecialchars($s['nombre']) ?></div>
                        <div class="service-creator">Creado por: <?= htmlspecialchars($s['creador']) ?></div>
                        <div class="service-actions">
                            <?php if ($s['creado_por'] == $user['id']): ?>
                            <form method="POST" onsubmit="return confirm('Eliminar servicio?')">
                                <input type="hidden" name="accion" value="eliminar"/>
                                <input type="hidden" name="servicio_id" value="<?= $s['id'] ?>"/>
                                <button type="submit" class="action delete">ELIMINAR</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
var glow=document.getElementById('glow'),grid=document.getElementById('grid');
document.addEventListener('mousemove',function(e){
    glow.style.left=e.clientX+'px';glow.style.top=e.clientY+'px';
    grid.style.transform='perspective(900px) rotateX(70deg) translate('+(e.clientX/innerWidth-.5)*25+'px,'+(e.clientY/innerHeight-.5)*15+'px)';
});
</script>
</body>
</html>
