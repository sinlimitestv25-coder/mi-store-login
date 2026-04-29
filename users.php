<?php
require_once __DIR__ . '/config.php';
requireLogin();
$user = currentUser();
$db   = getDB();
$msg  = ''; $tipo = '';

if (!in_array($user['rol'], ['admin','super_reseller','reseller'])) {
      header('Location: dashboard.php'); exit;
}

$db->prepare("UPDATE usuarios SET estado = CASE WHEN fecha_vence < CURDATE() THEN 'vencido' ELSE 'activo' END WHERE creado_por = ? AND rol IN ('reseller','final')")->execute([$user['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
              $nombre   = clean($_POST['nombre']   ?? '');
              $email    = clean($_POST['email']    ?? '');
              $password = $_POST['password']       ?? '';
              $rol      = in_array($_POST['rol'] ?? '', ['reseller','final']) ? $_POST['rol'] : 'final';
              $dias     = (int)($_POST['dias'] ?? 30);
              if (!$nombre || !$email || strlen($password) < 6) {
                            $msg = 'Nombre, email y contrasena (min 6 chars) son obligatorios.'; $tipo = 'error';
              } elseif ($user['creditos'] < 1) {
                            $msg = 'No tienes creditos suficientes.'; $tipo = 'error';
              } else {
                            $ck = $db->prepare("SELECT id FROM usuarios WHERE email=?"); $ck->execute([$email]);
                            if ($ck->fetch()) { $msg='Email ya en uso.'; $tipo='error'; }
                            else {
                                              $hash = password_hash($password, PASSWORD_BCRYPT);
                                              $vence = date('Y-m-d', strtotime("+$dias days"));
                                              $db->prepare("INSERT INTO usuarios (nombre,email,password,rol,creditos,estado,creado_por,fecha_vence) VALUES(?,?,?,?,0,'activo',?,?)")->execute([$nombre,$email,$hash,$rol,$user['id'],$vence]);
                                              $db->prepare("UPDATE usuarios SET creditos=creditos-1 WHERE id=?")->execute([$user['id']]);
                                              $_SESSION['user']['creditos'] = $user['creditos'] - 1;
                                              $msg = "Cuenta creada. Vence: $vence."; $tipo = 'ok';
                            }
              }
    }

    if ($accion === 'recargar') {
              $uid = (int)($_POST['uid'] ?? 0);
              $cantidad = (int)($_POST['cantidad'] ?? 0);
              if ($cantidad < 1) { $msg='Cantidad invalida.'; $tipo='error'; }
              elseif ($user['creditos'] < $cantidad) { $msg='Creditos insuficientes.'; $tipo='error'; }
              else {
                            $ck = $db->prepare("SELECT id FROM usuarios WHERE id=? AND creado_por=?"); $ck->execute([$uid,$user['id']]);
                            if (!$ck->fetch()) { $msg='Cuenta no encontrada.'; $tipo='error'; }
                            else {
                                              $db->prepare("UPDATE usuarios SET creditos=creditos+? WHERE id=?")->execute([$cantidad,$uid]);
                                              $db->prepare("UPDATE usuarios SET creditos=creditos-? WHERE id=?")->execute([$cantidad,$user['id']]);
                                              $_SESSION['user']['creditos'] = $user['creditos'] - $cantidad;
                                              $msg = "Se recargaron $cantidad creditos."; $tipo = 'ok';
                            }
              }
    }

    if ($accion === 'toggle') {
              $uid = (int)($_POST['uid'] ?? 0);
              $db->prepare("UPDATE usuarios SET estado=IF(estado='activo','inactivo','activo') WHERE id=? AND creado_por=?")->execute([$uid,$user['id']]);
              $msg = 'Estado actualizado.'; $tipo = 'ok';
    }

    if ($accion === 'eliminar') {
              $uid = (int)($_POST['uid'] ?? 0);
              $db->prepare("DELETE FROM usuarios WHERE id=? AND creado_por=?")->execute([$uid,$user['id']]);
              $msg = 'Cuenta eliminada.'; $tipo = 'ok';
    }
}

$stmt = $db->prepare("SELECT * FROM usuarios WHERE creado_por=? ORDER BY fecha_creado DESC");
$stmt->execute([$user['id']]); $cuentas = $stmt->fetchAll();
$row = $db->prepare("SELECT creditos FROM usuarios WHERE id=?"); $row->execute([$user['id']]);
$creds = $row->fetchColumn(); $_SESSION['user']['creditos'] = $creds;
$tr = $tf = 0;
foreach ($cuentas as $c) { if($c['rol']==='reseller') $tr++; else $tf++; }
?>
<!DOCTYPE html><html lang="es"><head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>RESELLER SYSTEM - <?= APP_NAME ?></title>title>
<style>
  *{box-sizing:border-box;}html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
  body{position:relative;overflow-x:hidden;}
  .bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,0.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,0.12),transparent 24%),linear-gradient(135deg,#030303 0%,#07070b 40%,#050505 100%);z-index:0;}
  .grid-lines{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,.10) 1px,transparent 1px);background-size:40px 40px;transform:perspective(900px) rotateX(70deg) translateY(-40px);opacity:.28;z-index:1;}
  .glow{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,.20) 0%,transparent 70%);pointer-events:none;transform:translate(-50%,-50%);z-index:2;}
  .container{position:relative;z-index:3;max-width:1450px;margin:0 auto;padding:28px;}
  .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap;}
  .brand-name{font-size:22px;font-weight:900;color:#b266ff;letter-spacing:.04em;text-transform:uppercase;}
  .top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
  .mini-pill{padding:10px 14px;border-radius:12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:#d8d8d8;font-weight:bold;font-size:13px;}
  .back-btn{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 16px;border-radius:12px;font-weight:bold;}
  .stats{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:18px;margin-bottom:20px;}
  .stat-card{background:rgba(14,14,18,.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:22px;box-shadow:0 0 24px rgba(124,58,237,.07);}
  .stat-title{color:#a9a9a9;font-size:13px;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em;}
  .stat-value{font-size:32px;font-weight:900;}
  .green{color:#8df0bf;}.yellow{color:#ffe08d;}.violet{color:#c084fc;}
  .layout{display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start;}
  .sidebar,.main-panel{background:rgba(14,14,18,.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:24px;box-shadow:0 0 24px rgba(124,58,237,.07);}
  h2{margin:0 0 6px 0;font-size:18px;font-weight:900;color:#b266ff;text-transform:uppercase;}
  .sub{margin:0 0 16px 0;color:#a9a9a9;font-size:13px;}
  .input,.select{width:100%;padding:13px;border-radius:14px;border:1px solid rgba(255,255,255,.08);background:#151518;color:#fff;margin-bottom:12px;outline:none;font-size:14px;}
  .input::placeholder{color:#666;}
  .btn{width:100%;padding:13px;border:none;border-radius:14px;cursor:pointer;font-weight:900;background:linear-gradient(90deg,#7c3aed,#10b981);color:#fff;text-transform:uppercase;font-size:14px;margin-bottom:8px;}
  .divider{border:none;border-top:1px solid rgba(255,255,255,.07);margin:18px 0;}
  .table-wrap{overflow-x:auto;}
  table{width:100%;border-collapse:collapse;}
  th,td{padding:12px 14px;text-align:left;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;white-space:nowrap;}
  th{color:#9adfc3;background:#111216;text-transform:uppercase;font-size:11px;letter-spacing:.05em;}
  tr:hover td{background:#111216;}
  .badge{display:inline-block;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:bold;text-transform:uppercase;}
  .reseller{background:#0f2848;color:#8dc6ff;}.final{background:#4a3b12;color:#ffe08d;}
  .activo{background:#103525;color:#8df0bf;}.inactivo,.vencido{background:#3a1a1a;color:#ffaaaa;}
  .actions{display:flex;gap:6px;flex-wrap:wrap;}
  .action{padding:7px 10px;border-radius:10px;font-size:11px;font-weight:bold;text-transform:uppercase;cursor:pointer;border:none;}
  .toggle-on{background:#103525;color:#8df0bf;}.toggle-off,.delete{background:#4a1717;color:#ffb0b0;}
  .msg-box{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:14px;}
  .msg-ok{background:#0f2f22;border:1px solid #1e5c44;color:#8df0bf;}
  .msg-error{background:#351010;border:1px solid #662222;color:#ff9c9c;}
  .empty{text-align:center;color:#555;padding:24px;}
  @media(max-width:1000px){.layout{grid-template-columns:1fr;}.stats{grid-template-columns:repeat(2,1fr);}}
  </style></head>head><body>
    <div class="bg"></div>div><div class="grid-lines" id="grid"></div>div><div class="glow" id="glow"></div>div>
    <div class="container">
      <div class="topbar">
          <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;font-size:24px;">🚀</div>div>
                <div class="brand-name"><?= APP_NAME ?></div>
          </div>div>
          <div class="top-actions">
                <div class="mini-pill">ROL: <?= strtoupper($user['rol']) ?></div>div>
                <div class="mini-pill">CREDITOS: <?= $creds ?></div>div>
                <a href="dashboard.php" class="back-btn">VOLVER</a>a>
          </div>div>
      </div>div>
      <?php if($msg): ?><div class="msg-box msg-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="stats">
    <div class="stat-card"><div class="stat-title">RESELLERS</div>div><div class="stat-value green"><?= $tr ?></div></div>div>
    <div class="stat-card"><div class="stat-title">USUARIOS FINALES</div>div><div class="stat-value violet"><?= $tf ?></div></div>div>
    <div class="stat-card"><div class="stat-title">TUS CREDITOS</div>div><div class="stat-value yellow"><?= $creds ?></div></div>div>
</div>div>
      <div class="layout">
        <div class="sidebar">
            <h2>CREAR NUEVA CUENTA</h2>h2>
            <p class="sub">1 credito = 1 mes de panel.</p>p>
            <form method="POST">
                  <input type="hidden" name="accion" value="crear"/>
                  <input class="input" type="text"     name="nombre"   placeholder="Nombre completo" required/>
                  <input class="input" type="email"    name="email"    placeholder="Email" required/>
                  <input class="input" type="password" name="password" placeholder="Contrasena (min 6 chars)" required/>
                  <select name="rol" class="select"><option value="reseller">Reseller</option>option><option value="final">Usuario Final</option>option></select>select>
                  <select name="dias" class="select">
                          <option value="30">30 dias / 1 mes</option>option><option value="60">60 dias / 2 meses</option>option>
                          <option value="90">90 dias / 3 meses</option>option><option value="365">365 dias / 1 ano</option>option>
                  </select>select>
                  <button type="submit" class="btn">CREAR CUENTA</button>button>
            </form>form>
            <hr class="divider"/>
            <h2>RECARGAR CREDITOS</h2>h2>
            <p class="sub">Recarga creditos a tus Resellers.</p>p>
            <form method="POST">
                  <input type="hidden" name="accion" value="recargar"/>
                  <select name="uid" class="select" required>
                          <option value="">Selecciona una cuenta</option>option>
                          <?php foreach($cuentas as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['email']) ?> (<?= $c['rol'] ?>)</option>option>
                          <?php endforeach; ?>
                  </select>select>
                  <input class="input" type="number" name="cantidad" placeholder="Cantidad de creditos" min="1" required/>
                  <button type="submit" class="btn">RECARGAR</button>button>
            </form>form>
        </div>div>
        <div class="main-panel">
            <h2>LISTADO DE CUENTAS</h2>h2>
            <p class="sub">Cuentas creadas por ti.</p>p>
            <div class="table-wrap">
                  <table>
                          <thead><tr><th>Nombre</th>th><th>Email</th>th><th>Rol</th>th><th>Cred.</th>th><th>Estado</th>th><th>Vence</th>th><th>Dias</th>th><th>Acciones</th>th></tr>tr></thead>thead>
                        <tbody>
                                  <?php if(empty($cuentas)): ?><tr><td colspan="8" class="empty">No hay cuentas aun.</td>td></tr>tr><?php endif; ?>
        <?php foreach($cuentas as $c):
            $diff = $c['fecha_vence'] ? (strtotime($c['fecha_vence'])-time())/86400 : null;
            $dt = $diff===null?'Sin vencimiento':($diff<0?'Vencido hace '.abs((int)$diff).' dias':($diff==0?'Vence hoy':'Vence en '.(int)$diff.' dias'));
          ?>
          <tr>
                      <td><?= htmlspecialchars($c['nombre']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><span class="badge <?= $c['rol'] ?>"><?= strtoupper($c['rol']) ?></span></td>td>
                            <td><?= $c['creditos'] ?></td>
                            <td><span class="badge <?= $c['estado'] ?>"><?= strtoupper($c['estado']) ?></span></td>td>
                            <td><?= $c['fecha_vence'] ?? '-' ?></td>
                            <td><?= $dt ?></td>
                            <td>
                                          <div class="actions">
                                                          <form method="POST" style="display:inline;">
                                                                            <input type="hidden" name="accion" value="toggle"/>
                                                                            <input type="hidden" name="uid" value="<?= $c['id'] ?>"/>
                                                                            <button type="submit" class="action <?= $c['estado']==='activo'?'toggle-on':'toggle-off' ?>">
                                                                                                <?= $c['estado']==='activo'?'DESACTIVAR':'ACTIVAR' ?>
                                                                            </button>button>
                                                          </form>form>
                                                          <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar?')">
                                                                            <input type="hidden" name="accion" value="eliminar"/>
                                                                            <input type="hidden" name="uid" value="<?= $c['id'] ?>"/>
                                                                            <button type="submit" class="action delete">ELIMINAR</button>button>
                                                          </form>form>
                                          </div>div>
                            </td>td>
          </tr>tr>
                                  <?php endforeach; ?>
                        </tbody>tbody>
                  </table>table>
            </div>div>
        </div>div>
      </div>div>
    </div>div>
    <script>
      var g=document.getElementById('glow'),gr=document.getElementById('grid');
      document.addEventListener('mousemove',function(e){
          g.style.left=e.clientX+'px';g.style.top=e.clientY+'px';
          gr.style.transform='perspective(900px) rotateX(70deg) translate('+(e.clientX/innerWidth-.5)*25+'px,'+(e.clientY/innerHeight-.5)*15+'px)';
      });
    </script>
  </body>body></html>html>
    </script>
                            </td></td>
          </tr></tr>
                        </tbody></th></th></tr>
</style></title>
</head>
