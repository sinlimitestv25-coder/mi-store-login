<?php
// =============================================
// users.php - Sistema Reseller
// =============================================
error_reporting(0);
require_once __DIR__ . '/config.php';
requireLogin();

$user = currentUser();
$db = getDB();
$msg = ''; $tipo = '';

// ---- CREAR usuario ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $nombre   = clean($_POST['nombre']   ?? '');
    $email    = clean($_POST['email']    ?? '');
    $pass     = $_POST['password'] ?? '';
    $rol      = clean($_POST['rol']      ?? 'final');
    $dias     = (int)($_POST['dias']     ?? 30);
    $creditos = (int)($_POST['creditos'] ?? 0);
    $vence    = date('Y-m-d', strtotime('+' . $dias . ' days'));

    if (!$nombre || !$email || !$pass) {
        $msg = 'Nombre, email y contrasena son obligatorios.'; $tipo = 'error';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $db->prepare("INSERT INTO usuarios (nombre,email,password,rol,creditos,estado,creado_por,fecha_vence) VALUES (?,?,?,?,?,'activo',?,?)")
               ->execute([$nombre, $email, $hash, $rol, $creditos, $user['id'], $vence]);
            $msg = 'Usuario creado correctamente.'; $tipo = 'ok';
        } catch (Exception $e) {
            $msg = 'El email ya existe.'; $tipo = 'error';
        }
    }
}

// ---- ELIMINAR usuario ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid !== (int)$user['id']) {
        $db->prepare("DELETE FROM usuarios WHERE id = ? AND creado_por = ?")->execute([$uid, $user['id']]);
        $msg = 'Usuario eliminado.'; $tipo = 'ok';
    }
}

// ---- LEER ----
$stmt = $db->prepare("SELECT * FROM usuarios WHERE creado_por = ? ORDER BY fecha_creado DESC");
$stmt->execute([$user['id']]);
$usuarios = $stmt->fetchAll();

$resellers = $db->query("SELECT COUNT(*) FROM usuarios WHERE creado_por = " . (int)$user['id'] . " AND rol IN ('reseller','super_reseller')")->fetchColumn();
$finales   = $db->query("SELECT COUNT(*) FROM usuarios WHERE creado_por = " . (int)$user['id'] . " AND rol = 'final'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>RESELLER SYSTEM - <?= APP_NAME ?></title>
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
.top-right{display:flex;align-items:center;gap:10px;}
.mini-pill{padding:8px 14px;border-radius:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);color:#d8d8d8;font-size:13px;font-weight:bold;}
.back-btn{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 16px;border-radius:12px;font-weight:bold;}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-mini{background:rgba(14,14,18,0.82);border:1px solid rgba(255,255,255,0.08);border-radius:18px;padding:20px;text-align:center;}
.stat-mini .val{font-size:36px;font-weight:900;background:linear-gradient(90deg,#b266ff,#10b981);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.stat-mini .lbl{font-size:12px;color:#a0a0b0;text-transform:uppercase;letter-spacing:0.05em;margin-top:4px;}
.layout{display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;}
.sidebar,.main-panel{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:24px;box-shadow:0 0 24px rgba(124,58,237,0.07);}
.sidebar h2,.main-panel h2{margin:0 0 6px 0;font-size:20px;font-weight:900;color:#b266ff;text-transform:uppercase;}
.sidebar p{margin:0 0 16px;color:#a9a9a9;font-size:14px;}
.input,.select-input{width:100%;padding:13px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:#151518;color:#fff;margin-bottom:12px;outline:none;font-size:14px;}
.input::placeholder{color:#666;}
.btn{width:100%;padding:14px;border:none;border-radius:14px;cursor:pointer;font-weight:900;background:linear-gradient(90deg,#7c3aed,#10b981);color:#fff;text-transform:uppercase;font-size:14px;}
.users-table{width:100%;border-collapse:collapse;}
.users-table th{text-align:left;padding:12px 16px;font-size:12px;color:#a0a0b0;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid rgba(255,255,255,0.06);}
.users-table td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:14px;vertical-align:middle;}
.badge-rol{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:bold;}
.admin{background:#1a0a2e;color:#c084fc;border:1px solid #6b21a8;}
.reseller{background:#0a2e1c;color:#8df0bf;border:1px solid #1e5c44;}
.super_reseller{background:#0a1e2e;color:#8dc8f0;border:1px solid #1e445c;}
.final{background:#1e1e1e;color:#a0a0a0;border:1px solid #333;}
.badge-estado{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:bold;}
.activo{background:#0a2e1c;color:#8df0bf;border:1px solid #1e5c44;}
.del-btn{padding:6px 12px;border-radius:8px;font-size:11px;font-weight:bold;background:#4a1717;color:#ffb0b0;border:none;cursor:pointer;text-transform:uppercase;}
.msg-box{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:14px;}
.msg-ok{background:#0f2f22;border:1px solid #1e5c44;color:#8df0bf;}
.msg-error{background:#351010;border:1px solid #662222;color:#ff9c9c;}
.empty-box{padding:40px;text-align:center;color:#555;}
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
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;font-size:24px;">&#128100;</div>
            <div class="brand-name"><?= APP_NAME ?></div>
        </div>
        <div class="top-right">
            <span class="mini-pill">ROL: <?= strtoupper($user['rol'] ?? '') ?></span>
            <span class="mini-pill">CREDITOS: <?= number_format((float)($user['creditos'] ?? 0)) ?></span>
            <a href="dashboard.php" class="back-btn">VOLVER</a>
        </div>
    </div>

    <?php if ($msg): ?><div class="msg-box msg-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-mini">
            <div class="val"><?= $resellers ?></div>
            <div class="lbl">RESELLERS</div>
        </div>
        <div class="stat-mini">
            <div class="val"><?= number_format((float)($user['creditos'] ?? 0)) ?></div>
            <div class="lbl">TUS CREDITOS</div>
        </div>
        <div class="stat-mini">
            <div class="val"><?= $finales ?></div>
            <div class="lbl">USUARIOS FINALES</div>
        </div>
    </div>

    <div class="layout">
        <div class="sidebar">
            <h2>CREAR NUEVA CUENTA</h2>
            <p>1 credito = 1 mes de panel.</p>
            <form method="POST">
                <input type="hidden" name="accion" value="crear"/>
                <input class="input" type="text" name="nombre" placeholder="Nombre completo" required/>
                <input class="input" type="email" name="email" placeholder="Correo electronico" required/>
                <input class="input" type="password" name="password" placeholder="Contrasena" required/>
                <select class="select-input" name="rol">
                    <option value="final">Final</option>
                    <option value="reseller">Reseller</option>
                    <option value="super_reseller">Super Reseller</option>
                </select>
                <select class="select-input" name="dias">
                    <option value="30">30 dias / 1 mes</option>
                    <option value="60">60 dias / 2 meses</option>
                    <option value="90">90 dias / 3 meses</option>
                    <option value="180">6 meses</option>
                    <option value="365">1 año</option>
                </select>
                <button type="submit" class="btn">CREAR USUARIO</button>
            </form>
        </div>
        <div class="main-panel">
            <h2>MIS USUARIOS</h2>
            <?php if (empty($usuarios)): ?>
            <div class="empty-box">No has creado ningún usuario todavia.</div>
            <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Vence</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge-rol <?= $u['rol'] ?>"><?= strtoupper($u['rol']) ?></span></td>
                    <td><span class="badge-estado <?= $u['estado'] ?>"><?= strtoupper($u['estado']) ?></span></td>
                    <td><?= htmlspecialchars($u['fecha_vence'] ?? '-') ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Eliminar usuario?')" style="display:inline">
                            <input type="hidden" name="accion" value="eliminar"/>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                            <button type="submit" class="del-btn">ELIMINAR</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
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
