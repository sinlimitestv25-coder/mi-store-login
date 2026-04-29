<?php
// =============================================
//  clients.php - Gestion de Clientes
// =============================================
require_once __DIR__ . '/config.php';
requireLogin();

$user = currentUser();
$db   = getDB();
$msg  = '';
$tipo = '';

// ---- Actualizar estados automaticamente ----
$db->prepare("
    UPDATE clientes SET estado = CASE
            WHEN fecha_vence < CURDATE() THEN 'suspendido'
                    WHEN DATEDIFF(fecha_vence, CURDATE()) <= 3 THEN 'por_vencer'
                            ELSE 'activo'
                                END
                                    WHERE propietario = ?
                                    ")->execute([$user['id']]);

// ---- CREAR cliente ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
      $servicio_id  = (int)($_POST['servicio_id'] ?? 0);
      $nombre       = clean($_POST['nombre']      ?? '');
      $email        = clean($_POST['email']       ?? '');
      $telefono     = clean($_POST['telefono']    ?? '');
      $precio       = (float)($_POST['precio']    ?? 0);
      $fecha_inicio = $_POST['fecha_inicio']      ?? date('Y-m-d');
      $dias         = (int)($_POST['dias']        ?? 30);

    if (!$nombre || !$servicio_id) {
              $msg = 'El nombre y el servicio son obligatorios.';
              $tipo = 'error';
    } else {
              $fecha_vence = date('Y-m-d', strtotime($fecha_inicio . ' + ' . $dias . ' days'));
              $stmt = $db->prepare("
                          INSERT INTO clientes (servicio_id, propietario, nombre, email, telefono, precio, fecha_inicio, fecha_vence, estado)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')
                                              ");
              $stmt->execute([$servicio_id, $user['id'], $nombre, $email, $telefono, $precio, $fecha_inicio, $fecha_vence]);
              $cid = $db->lastInsertId();
              // Registrar transaccion
          $db->prepare("INSERT INTO transacciones (tipo,cliente_id,usuario_id,servicio_id,monto,descripcion) VALUES ('nuevo',?,?,?,?,?)")
                       ->execute([$cid, $user['id'], $servicio_id, $precio, "Alta de cliente: $nombre"]);
              $msg = 'Cliente creado correctamente.';
              $tipo = 'ok';
    }
}

// ---- RENOVAR cliente ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'renovar') {
      $cid  = (int)($_POST['cliente_id'] ?? 0);
      $dias = (int)($_POST['dias']       ?? 30);
      $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ? AND propietario = ?");
      $stmt->execute([$cid, $user['id']]);
      $c = $stmt->fetch();
      if ($c) {
                $base        = $c['fecha_vence'] > date('Y-m-d') ? $c['fecha_vence'] : date('Y-m-d');
                $fecha_vence = date('Y-m-d', strtotime($base . ' + ' . $dias . ' days'));
                $db->prepare("UPDATE clientes SET fecha_vence = ?, estado = 'activo' WHERE id = ?")->execute([$fecha_vence, $cid]);
                $db->prepare("INSERT INTO transacciones (tipo,cliente_id,usuario_id,servicio_id,monto,descripcion) VALUES ('renovacion',?,?,?,?,?)")
                             ->execute([$cid, $user['id'], $c['servicio_id'], $c['precio'], "Renovacion +$dias dias"]);
                $msg = "Cliente renovado por $dias dias.";
                $tipo = 'ok';
      }
}

// ---- ELIMINAR cliente ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
      $cid = (int)($_POST['cliente_id'] ?? 0);
      $db->prepare("DELETE FROM clientes WHERE id = ? AND propietario = ?")->execute([$cid, $user['id']]);
      $msg = 'Cliente eliminado.';
      $tipo = 'ok';
}

// ---- LEER clientes con filtros ----
$buscar = clean($_GET['q']      ?? '');
$estado = clean($_GET['estado'] ?? '');

$where  = ['propietario = ?'];
$params = [$user['id']];

if ($buscar) {
      $where[]  = '(c.nombre LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)';
      $like     = "%$buscar%";
      $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($estado) {
      $where[]  = 'c.estado = ?';
      $params[] = $estado;
}

$sql = "SELECT c.*, s.nombre AS servicio_nombre, s.logo_url
        FROM clientes c
                JOIN servicios s ON s.id = c.servicio_id
                        WHERE " . implode(' AND ', $where) . "
                                ORDER BY c.fecha_vence ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

// ---- Cargar servicios para el select ----
$servicios = $db->prepare("SELECT id, nombre FROM servicios WHERE creado_por = ? OR creado_por IN (SELECT id FROM usuarios WHERE rol = 'admin') ORDER BY nombre");
$servicios->execute([$user['id']]);
$servicios = $servicios->fetchAll();

// ---- Estadisticas rapidas ----
$stats = $db->prepare("SELECT estado, COUNT(*) AS total FROM clientes WHERE propietario = ? GROUP BY estado");
$stats->execute([$user['id']]);
$conteos = ['activo' => 0, 'por_vencer' => 0, 'suspendido' => 0];
foreach ($stats->fetchAll() as $row) $conteos[$row['estado']] = $row['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>CLIENTES - <?= APP_NAME ?></title>title>
<style>
  *{box-sizing:border-box;}
  html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
  body{position:relative;overflow-x:hidden;}
  .bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,0.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,0.12),transparent 24%),linear-gradient(135deg,#030303 0%,#07070b 40%,#050505 100%);z-index:0;}
  .grid-lines{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,0.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,0.10) 1px,transparent 1px);background-size:40px 40px;transform:perspective(900px) rotateX(70deg) translateY(-40px);opacity:0.28;z-index:1;}
  .glow{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,0.20) 0%,transparent 70%);pointer-events:none;transform:translate(-50%,-50%);z-index:2;}
  .container{position:relative;z-index:3;max-width:1450px;margin:0 auto;padding:28px;}
  .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:12px;flex-wrap:wrap;}
  .brand-area{display:flex;align-items:center;gap:12px;}
  .brand-name{font-size:22px;font-weight:900;color:#b266ff;letter-spacing:0.04em;text-transform:uppercase;}
  .back-btn{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 16px;border-radius:12px;font-weight:bold;}
  .layout{display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start;}
  .sidebar,.main-panel{background:rgba(14,14,18,0.82);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:24px;box-shadow:0 0 24px rgba(124,58,237,0.07);}
  .sidebar h2,.main-panel h2{margin:0 0 6px 0;font-size:20px;font-weight:900;color:#b266ff;text-transform:uppercase;}
  .sidebar p,.main-panel > p{margin:0 0 18px 0;color:#a9a9a9;font-size:14px;}
  .input,.select{width:100%;padding:13px;border-radius:14px;border:1px solid rgba(255,255,255,0.08);background:#151518;color:#fff;margin-bottom:14px;outline:none;font-size:14px;}
  .input:focus,.select:focus{border-color:rgba(178,102,255,0.45);}
  .input::placeholder{color:#666;}
  .btn{width:100%;padding:14px;border:none;border-radius:14px;cursor:pointer;font-weight:900;background:linear-gradient(90deg,#7c3aed,#10b981);color:#fff;text-transform:uppercase;font-size:14px;}
  .toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;}
  .toolbar .input,.toolbar .select{margin-bottom:0;flex:1;min-width:180px;}
  .btn-filter{padding:12px 20px;border:none;border-radius:12px;cursor:pointer;font-weight:900;background:linear-gradient(90deg,#7c3aed,#10b981);color:#fff;text-transform:uppercase;font-size:13px;white-space:nowrap;}
  .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
  .client-card{background:#111216;border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:18px;transition:0.2s;}
  .client-card:hover{border-color:rgba(178,102,255,0.3);transform:translateY(-2px);}
  .client-head{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
  .client-logo{width:52px;height:52px;border-radius:14px;object-fit:cover;background:#1a1a1f;border:1px solid rgba(255,255,255,0.08);}
  .client-name{font-size:17px;font-weight:900;}
  .client-service{font-size:13px;color:#b8b8b8;}
  .client-info{font-size:13px;line-height:1.8;color:#ddd;}
  .badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:bold;text-transform:uppercase;}
  .activo{background:#103525;color:#8df0bf;}.por_vencer{background:#4a3b12;color:#ffe08d;}.suspendido{background:#3a1a1a;color:#ffaaaa;}
  .client-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;}
  .action{padding:7px 10px;border-radius:10px;font-size:11px;font-weight:bold;text-transform:uppercase;cursor:pointer;border:none;}
  .edit{background:#0f2848;color:#8dc6ff;}.renew{background:#103525;color:#8df0bf;}.delete{background:#4a1717;color:#ffb0b0;}.whatsapp{background:#0f9d58;color:#fff;}
  .msg-box{padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:14px;}
  .msg-ok{background:#0f2f22;border:1px solid #1e5c44;color:#8df0bf;}
  .msg-error{background:#351010;border:1px solid #662222;color:#ff9c9c;}
  .empty-box{border:2px dashed rgba(255,255,255,0.08);border-radius:20px;padding:40px;text-align:center;color:#555;grid-column:1/-1;}
  @media(max-width:900px){.layout{grid-template-columns:1fr;}}
  </style>
</head>head>
  <body>
    <div class="bg"></div>div>
    <div class="grid-lines" id="grid"></div>div>
    <div class="glow" id="glow"></div>div>
    <div class="container">
        <div class="topbar">
              <div class="brand-area">
                      <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;font-size:24px;">🚀</div>div>
                      <div class="brand-name"><?= APP_NAME ?></div>
              </div>div>
              <a href="dashboard.php" class="back-btn">VOLVER</a>a>
        </div>div>

        <?php if ($msg): ?>
      <div class="msg-box msg-<?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="layout">
              <!-- SIDEBAR -->
              <div class="sidebar">
                      <h2>NUEVO CLIENTE</h2>h2>
                      <p>Gestiona clientes, renovaciones y vencimientos.</p>p>
                    <form method="POST">
                              <input type="hidden" name="accion" value="crear"/>
                              <select name="servicio_id" class="select" required>
                                          <option value="">Selecciona un servicio</option>option>
                                          <?php foreach ($servicios as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                                          <?php endforeach; ?>
                              </select>select>
                              <input class="input" type="text"   name="nombre"       placeholder="Nombre del cliente" required/>
                              <input class="input" type="email"  name="email"        placeholder="Email"/>
                              <input class="input" type="tel"    name="telefono"     placeholder="Telefono"/>
                              <input class="input" type="number" name="precio"       placeholder="Precio" value="0" step="0.01"/>
                              <input class="input" type="date"   name="fecha_inicio" value="<?= date('Y-m-d') ?>"/>
                              <select name="dias" class="select">
                                          <option value="30">30 dias / 1 mes</option>option>
                                          <option value="60">60 dias / 2 meses</option>option>
                                          <option value="90">90 dias / 3 meses</option>option>
                                          <option value="365">365 dias / 1 ano</option>option>
                              </select>select>
                              <button type="submit" class="btn">CREAR CLIENTE</button>button>
                    </form>form>

                      <hr style="border:none;border-top:1px solid rgba(255,255,255,0.07);margin:20px 0;"/>
                      <p style="color:#a9a9a9;font-size:13px;">
                                Activos: <strong style="color:#8df0bf;"><?= $conteos['activo'] ?></strong> &nbsp;|&nbsp;
                                Por vencer: <strong style="color:#ffe08d;"><?= $conteos['por_vencer'] ?></strong> &nbsp;|&nbsp;
                                Vencidos: <strong style="color:#ffaaaa;"><?= $conteos['suspendido'] ?></strong>
                      </p>p>
              </div>div>

              <!-- MAIN -->
              <div class="main-panel">
                      <h2>LISTADO DE CLIENTES</h2>h2>
                      <p>Busca por nombre, email o telefono y filtra por estado.</p>p>
                    <form method="GET" class="toolbar">
                              <input class="input" type="text" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>"/>
                              <select class="select" name="estado">
                                          <option value="">Todos los estados</option>option>
                                          <option value="activo"     <?= $estado==='activo'     ?'selected':'' ?>>Activos</option>option>
                                          <option value="por_vencer" <?= $estado==='por_vencer' ?'selected':'' ?>>Por vencer</option>option>
                                          <option value="suspendido" <?= $estado==='suspendido' ?'selected':'' ?>>Suspendidos</option>option>
                              </select>select>
                              <button type="submit" class="btn-filter">FILTRAR</button>button>
                    </form>form>
                      <div class="cards">
                                <?php if (empty($clientes)): ?>
            <div class="empty-box">No se encontraron clientes.</div>div>
                                <?php endif; ?>
        <?php foreach ($clientes as $c):
            $dias_txt = '';
            $diff = (strtotime($c['fecha_vence']) - time()) / 86400;
            if ($diff < 0)      $dias_txt = 'Vencido hace ' . abs((int)$diff) . ' dias';
            elseif ($diff == 0) $dias_txt = 'Vence hoy';
            else                $dias_txt = 'Vence en ' . (int)$diff . ' dias';
          ?>
            <div class="client-card">
                          <div class="client-head">
                                          <?php if ($c['logo_url']): ?>
                            <img src="<?= htmlspecialchars($c['logo_url']) ?>" class="client-logo" onerror="this.style.display='none'"/>
                                          <?php else: ?>
                            <div class="client-logo" style="display:flex;align-items:center;justify-content:center;font-size:22px;">📺</div>div>
                                          <?php endif; ?>
                <div>
                                  <div class="client-name"><?= htmlspecialchars($c['nombre']) ?></div>
                                  <div class="client-service"><?= htmlspecialchars($c['servicio_nombre']) ?></div>
                </div>div>
                          </div>div>
                          <div class="client-info">
                                          <?php if ($c['email']): ?>Email: <?= htmlspecialchars($c['email']) ?><br><?php endif; ?>
                <?php if ($c['telefono']): ?>Tel: <?= htmlspecialchars($c['telefono']) ?><br><?php endif; ?>
                Precio: $<?= number_format($c['precio'],2) ?><br>
                                          Inicio: <?= $c['fecha_inicio'] ?><br>
                                          Vence: <?= $c['fecha_vence'] ?><br>
                                          Dias: <?= $dias_txt ?><br>
                                          Estado: <span class="badge <?= $c['estado'] ?>"><?= strtoupper($c['estado']) ?></span>
                          </div>div>
                          <div class="client-actions">
                                          <!-- Renovar -->
                                          <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="accion"    value="renovar"/>
                                                            <input type="hidden" name="cliente_id" value="<?= $c['id'] ?>"/>
                                                            <input type="hidden" name="dias"      value="30"/>
                                                            <button type="submit" class="action renew">+30</button>button>
                                          </form>form>
                                          <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="accion"    value="renovar"/>
                                                            <input type="hidden" name="cliente_id" value="<?= $c['id'] ?>"/>
                                                            <input type="hidden" name="dias"      value="60"/>
                                                            <button type="submit" class="action renew">+60</button>button>
                                          </form>form>
                                          <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="accion"    value="renovar"/>
                                                            <input type="hidden" name="cliente_id" value="<?= $c['id'] ?>"/>
                                                            <input type="hidden" name="dias"      value="90"/>
                                                            <button type="submit" class="action renew">+90</button>button>
                                          </form>form>
                                          <!-- Whatsapp -->
                                          <?php if ($c['telefono']): ?>
                            <a href="https://wa.me/<?= preg_replace('/\D/','',$c['telefono']) ?>?text=Hola+<?= urlencode($c['nombre']) ?>+tu+servicio+vence+el+<?= $c['fecha_vence'] ?>"
                                                 target="_blank" class="action whatsapp" style="text-decoration:none;">WHATSAPP</a>a>
                                          <?php endif; ?>
                <!-- Eliminar -->
                                          <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar cliente?')">
                                                            <input type="hidden" name="accion"    value="eliminar"/>
                                                            <input type="hidden" name="cliente_id" value="<?= $c['id'] ?>"/>
                                                            <button type="submit" class="action delete">ELIMINAR</button>button>
                                          </form>form>
                          </div>div>
            </div>div>
                                <?php endforeach; ?>
                      </div>div>
              </div>div>
        </div>div>
    </div>div>
    <script>
      var glow=document.getElementById('glow'),grid=document.getElementById('grid');
      document.addEventListener('mousemove',function(e){
          glow.style.left=e.clientX+'px';glow.style.top=e.clientY+'px';
          grid.style.transform='perspective(900px) rotateX(70deg) translate('+(e.clientX/innerWidth-.5)*25+'px,'+(e.clientY/innerHeight-.5)*15+'px)';
      });
    </script>
  </body>body>
</html>html>

    </script>
                    </p>
                    </p>
</style></title>
</head>
