<?php
// =============================================
// clients.php - Gestion de Clientes
// =============================================
error_reporting(0);
require_once __DIR__ . '/config.php';
requireLogin();

$user = currentUser();
$db = getDB();
$msg = ''; $tipo = '';

// ---- CREAR cliente ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    $servicio_id = (int)($_POST['servicio_id'] ?? 0);
    $nombre   = clean($_POST['nombre']   ?? '');
    $email    = clean($_POST['email']    ?? '');
    $telefono = clean($_POST['telefono'] ?? '');
    $precio   = (float)($_POST['precio'] ?? 0);
    $inicio   = clean($_POST['fecha_inicio'] ?? date('Y-m-d'));
    $dias     = (int)($_POST['dias'] ?? 30);
    $vence    = date('Y-m-d', strtotime($inicio . ' + ' . $dias . ' days'));

    if (!$servicio_id || !$nombre) {
        $msg = 'Servicio y nombre son obligatorios.'; $tipo = 'error';
    } else {
        $db->prepare("INSERT INTO clientes (servicio_id,propietario,nombre,email,telefono,precio,fecha_inicio,fecha_vence,estado) VALUES (?,?,?,?,?,?,?,?,'activo')")
           ->execute([$servicio_id, $user['id'], $nombre, $email, $telefono, $precio, $inicio, $vence]);
        $msg = 'Cliente creado correctamente.'; $tipo = 'ok';
    }
}

// ---- ELIMINAR cliente ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $cid = (int)($_POST['cliente_id'] ?? 0);
    $db->prepare("DELETE FROM clientes WHERE id = ? AND propietario = ?")->execute([$cid, $user['id']]);
    $msg = 'Cliente eliminado.'; $tipo = 'ok';
}

// ---- LEER ----
$servicios = $db->prepare("SELECT * FROM servicios ORDER BY nombre");
$servicios->execute();
$servicios = $servicios->fetchAll();

$stmt = $db->prepare("
    SELECT c.*, s.nombre AS servicio_nombre
    FROM clientes c JOIN servicios s ON s.id = c.servicio_id
    WHERE c.propietario = ?
    ORDER BY c.fecha_vence ASC
");
$stmt->execute([$user['id']]);
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>CLIENTES - <?= APP_NAME ?></title>
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
.layout{display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start;}
.sidebar,.main-panel{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:24px;box-shadow:0 0 24px rgba(124,58,237,0.07);}
.sidebar h2,.main-panel h2{margin:0 0 6px 0;font-size:20px;font-weight:900;color:#b266ff;text-transform:uppercase;}
.sidebar p,.main-panel > p{margin:0 0 18px 0;color:#a9a9a9;font-size:14px;}
.input,.select-input{width:100%;padding:13px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:#151518;color:#fff;margin-bottom:12px;outline:none;font-size:14px;}
.input::placeholder{color:#666;}
.btn{width:100%;padding:14px;border:none;border-radius:14px;cursor:pointer;font-weight:900;background:linear-gradient(90deg,#7c3aed,#10b981);color:#fff;text-transform:uppercase;font-size:14px;}
.clients-table{width:100%;border-collapse:collapse;}
.clients-table th{text-align:left;padding:12px 16px;font-size:12px;color:#a0a0b0;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid rgba(255,255,255,0.06);}
.clients-table td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.04);font-size:14px;vertical-align:middle;}
.clients-table tr:hover td{background:rgba(255,255,255,0.02);}
.badge-estado{padding:4px 10px;border-radius:999px;font-size:11px;font-weight:bold;}
.activo{background:#0a2e1c;color:#8df0bf;border:1px solid #1e5c44;}
.por_vencer{background:#2e1a0a;color:#f0c88d;border:1px solid #5c3e1e;}
.suspendido{background:#2e0a0a;color:#f08d8d;border:1px solid #5c1e1e;}
.msg-box{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:14px;}
.msg-ok{background:#0f2f22;border:1px solid #1e5c44;color:#8df0bf;}
.msg-error{background:#351010;border:1px solid #662222;color:#ff9c9c;}
.empty-box{padding:40px;text-align:center;color:#555;}
.del-btn{padding:6px 12px;border-radius:8px;font-size:11px;font-weight:bold;background:#4a1717;color:#ffb0b0;border:none;cursor:pointer;text-transform:uppercase;}
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
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;font-size:24px;">&#128101;</div>
            <div class="brand-name"><?= APP_NAME ?></div>
        </div>
        <a href="dashboard.php" class="back-btn">VOLVER</a>
    </div>
    <?php if ($msg): ?><div class="msg-box msg-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <div class="layout">
        <div class="sidebar">
            <h2>NUEVO CLIENTE</h2>
            <p>Gestiona clientes, renovaciones y vencimientos.</p>
            <form method="POST">
                <input type="hidden" name="accion" value="crear"/>
                <select class="select-input" name="servicio_id" required>
                    <option value="">Selecciona un servicio</option>
                    <?php foreach ($servicios as $sv): ?>
                    <option value="<?= $sv['id'] ?>"><?= htmlspecialchars($sv['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="input" type="text" name="nombre" placeholder="Nombre del cliente" required/>
                <input class="input" type="email" name="email" placeholder="Email"/>
                <input class="input" type="text" name="telefono" placeholder="Telefono"/>
                <input class="input" type="number" name="precio" placeholder="Precio" value="0" min="0" step="0.01"/>
                <input class="input" type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>"/>
                <select class="select-input" name="dias">
                    <option value="30">30 dias / 1 mes</option>
                    <option value="60">60 dias / 2 meses</option>
                    <option value="90">90 dias / 3 meses</option>
                    <option value="180">180 dias / 6 meses</option>
                    <option value="365">365 dias / 1 año</option>
                </select>
                <button type="submit" class="btn">CREAR CLIENTE</button>
            </form>
        </div>
        <div class="main-panel">
            <h2>MIS CLIENTES</h2>
            <p>Lista de todos los clientes registrados.</p>
            <?php if (empty($clientes)): ?>
            <div class="empty-box">No tienes clientes aun. Agrega el primero.</div>
            <?php else: ?>
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Servicio</th>
                        <th>Vence</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($c['nombre']) ?></strong><br>
                        <small style="color:#777"><?= htmlspecialchars($c['email'] ?? '') ?></small>
                    </td>
                    <td><?= htmlspecialchars($c['servicio_nombre']) ?></td>
                    <td><?= htmlspecialchars($c['fecha_vence']) ?></td>
                    <td><span class="badge-estado <?= $c['estado'] ?>"><?= strtoupper($c['estado']) ?></span></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Eliminar cliente?')" style="display:inline">
                            <input type="hidden" name="accion" value="eliminar"/>
                            <input type="hidden" name="cliente_id" value="<?= $c['id'] ?>"/>
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
