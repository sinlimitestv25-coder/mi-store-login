<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
startSession();
requireLogin();
$user = currentUser();
$msg = '';
$msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $db = getDB();
    if ($_POST['action'] === 'cambiar_password') {
        $actual = $_POST['pass_actual'] ?? '';
        $nueva = $_POST['pass_nueva'] ?? '';
        $confirmar = $_POST['pass_confirmar'] ?? '';
        if (empty($actual) || empty($nueva) || empty($confirmar)) {
            $msg = 'Completa todos los campos de contrasena.'; $msg_type = 'error';
        } elseif ($nueva !== $confirmar) {
            $msg = 'Las contrasenas no coinciden.'; $msg_type = 'error';
        } elseif (strlen($nueva) < 6) {
            $msg = 'Minimo 6 caracteres.'; $msg_type = 'error';
        } else {
            $stmt = $db->prepare('SELECT password FROM usuarios WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            if ($row && password_verify($actual, $row['password'])) {
                $hash = password_hash($nueva, PASSWORD_DEFAULT);
                $upd = $db->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
                $upd->execute([$hash, $user['id']]);
                $msg = 'Contrasena actualizada.'; $msg_type = 'ok';
            } else {
                $msg = 'Contrasena actual incorrecta.'; $msg_type = 'error';
            }
        }
    }
    if ($_POST['action'] === 'cambiar_logo') {
        $logo_url = trim($_POST['logo_url'] ?? '');
        if (empty($logo_url)) {
            $msg = 'URL invalida.'; $msg_type = 'error';
        } else {
            $_SESSION['logo_url'] = $logo_url;
            try { $db->prepare('UPDATE usuarios SET logo_url = ? WHERE id = ?')->execute([$logo_url, $user['id']]); } catch (Exception $e) {}
            $msg = 'Logo guardado.'; $msg_type = 'ok';
        }
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>CONFIGURACION - MI STORE</title>
<style>
*{box-sizing:border-box;}
html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
body{position:relative;overflow-x:hidden;}
.bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,.12),transparent 35%);pointer-events:none;z-index:0;}
.gl{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,.10) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0;}
.gw{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,.20) 0%,rgba(178,102,255,.08) 35%,transparent 70%);top:-100px;right:-100px;pointer-events:none;z-index:0;}
.wrap{position:relative;z-index:3;max-width:900px;margin:0 auto;padding:28px;}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;gap:12px;flex-wrap:wrap;}
.brand{font-size:22px;font-weight:900;color:#b266ff;letter-spacing:.04em;text-transform:uppercase;}
.back{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 18px;border-radius:12px;font-weight:bold;}
.ptitle{font-size:24px;font-weight:900;color:#b266ff;letter-spacing:.05em;text-transform:uppercase;margin-bottom:28px;}
.card{background:rgba(14,14,18,.85);border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:28px;margin-bottom:24px;}
.ctitle{font-size:15px;font-weight:800;color:#b266ff;text-transform:uppercase;letter-spacing:.06em;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid rgba(178,102,255,.20);}
.fg{margin-bottom:16px;}
.fl{display:block;font-size:13px;color:#a0a0b0;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
.fi{width:100%;padding:12px 16px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.10);border-radius:12px;color:#fff;font-size:15px;outline:none;}
.fi:focus{border-color:rgba(178,102,255,.60);}
.fi::placeholder{color:#555;}
.btn{width:100%;padding:14px;background:linear-gradient(90deg,#7c3aed,#10b981);border:none;border-radius:14px;color:#fff;font-size:15px;font-weight:800;text-transform:uppercase;cursor:pointer;margin-top:6px;}
.aok{padding:14px 18px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:20px;background:rgba(16,185,129,.18);border:1px solid rgba(16,185,129,.35);color:#8df0bf;}
.aerr{padding:14px 18px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:20px;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.30);color:#fca5a5;}
.ir{display:flex;gap:16px;margin-bottom:8px;font-size:14px;}
.il{color:#a0a0b0;min-width:100px;}
.iv{color:#fff;font-weight:600;}
.lprev{margin-top:12px;display:none;text-align:center;}
.lprev img{max-width:120px;max-height:80px;border-radius:12px;border:1px solid rgba(255,255,255,.12);}
</style>
</head>
<body>
<div class="bg"></div>
<div class="gl"></div>
<div class="gw"></div>
<div class="wrap">
  <div class="topbar">
    <span class="brand">&#9881; MI STORE</span>
    <a href="dashboard.php" class="back">&#8592; VOLVER AL PANEL</a>
  </div>
  <div class="ptitle">&#9881; Configuracion del Administrador</div>
  <?php if ($msg): ?>
  <div class="<?php echo $msg_type==='ok'?'aok':'aerr'; ?>"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>
  <div class="card">
    <div class="ctitle">&#128100; Informacion de la cuenta</div>
    <div class="ir"><span class="il">Email:</span><span class="iv"><?php echo htmlspecialchars($user['email']??''); ?></span></div>
    <div class="ir"><span class="il">Rol:</span><span class="iv"><?php echo strtoupper($user['rol']??''); ?></span></div>
    <div class="ir"><span class="il">Creditos:</span><span class="iv"><?php echo number_format((float)($user['creditos']??0)); ?></span></div>
  </div>
  <div class="card">
    <div class="ctitle">&#128274; Cambiar Contrasena</div>
    <form method="POST">
      <input type="hidden" name="action" value="cambiar_password"/>
      <div class="fg"><label class="fl">Contrasena actual</label><input type="password" name="pass_actual" class="fi" placeholder="Contrasena actual" required/></div>
      <div class="fg"><label class="fl">Nueva contrasena</label><input type="password" name="pass_nueva" class="fi" placeholder="Minimo 6 caracteres" required/></div>
      <div class="fg"><label class="fl">Confirmar contrasena</label><input type="password" name="pass_confirmar" class="fi" placeholder="Repetir nueva contrasena" required/></div>
      <button type="submit" class="btn">&#128274; ACTUALIZAR CONTRASENA</button>
    </form>
  </div>
  <div class="card">
    <div class="ctitle">&#128444; Logo del Sistema</div>
    <form method="POST">
      <input type="hidden" name="action" value="cambiar_logo"/>
      <div class="fg"><label class="fl">URL del logo</label><input type="url" name="logo_url" id="lurlinput" class="fi" placeholder="https://ejemplo.com/logo.png" value="<?php echo htmlspecialchars($_SESSION['logo_url']??''); ?>" oninput="prvLogo(this.value)"/></div>
      <div class="lprev" id="lprev"><p style="font-size:12px;color:#888;margin-bottom:8px;">Vista previa:</p><img id="limg" src="" alt="Logo"/></div>
      <button type="submit" class="btn">&#128444; GUARDAR LOGO</button>
    </form>
  </div>
</div>
<script>
function prvLogo(u){var p=document.getElementById('lprev'),i=document.getElementById('limg');if(u&&u.startsWith('http')){i.src=u;p.style.display='block';}else{p.style.display='none';}}
var ex=document.getElementById('lurlinput').value;if(ex)prvLogo(ex);
</script>
</body>
</html>
