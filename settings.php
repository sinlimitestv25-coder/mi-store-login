<?php
error_reporting(0);
require_once __DIR__ . '/config.php';
startSession();
requireLogin();
$user = currentUser();

$msg = '';
$msg_type = '';

// Procesar cambio de contrasena
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
      $db = getDB();

    if ($_POST['action'] === 'cambiar_password') {
              $actual = $_POST['pass_actual'] ?? '';
              $nueva = $_POST['pass_nueva'] ?? '';
              $confirmar = $_POST['pass_confirmar'] ?? '';

          if (empty($actual) || empty($nueva) || empty($confirmar)) {
                        $msg = 'Completa todos los campos de contrasena.';
                        $msg_type = 'error';
          } elseif ($nueva !== $confirmar) {
                        $msg = 'La nueva contrasena y la confirmacion no coinciden.';
                        $msg_type = 'error';
          } elseif (strlen($nueva) < 6) {
                        $msg = 'La nueva contrasena debe tener al menos 6 caracteres.';
                        $msg_type = 'error';
          } else {
                        $stmt = $db->prepare('SELECT password FROM usuarios WHERE id = ?');
                        $stmt->execute([$user['id']]);
                        $row = $stmt->fetch();
                        if ($row && password_verify($actual, $row['password'])) {
                                          $hash = password_hash($nueva, PASSWORD_DEFAULT);
                                          $upd = $db->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
                                          $upd->execute([$hash, $user['id']]);
                                          $msg = 'Contrasena actualizada correctamente.';
                                          $msg_type = 'ok';
                        } else {
                                          $msg = 'La contrasena actual es incorrecta.';
                                          $msg_type = 'error';
                        }
          }
    }

    if ($_POST['action'] === 'cambiar_logo') {
              $logo_url = trim($_POST['logo_url'] ?? '');
              if (empty($logo_url)) {
                            $msg = 'Ingresa una URL de logo valida.';
                            $msg_type = 'error';
              } else {
                            // Guardar en sesion y en base de datos
                  $_SESSION['logo_url'] = $logo_url;
                            try {
                                              $upd = $db->prepare('UPDATE usuarios SET logo_url = ? WHERE id = ?');
                                              $upd->execute([$logo_url, $user['id']]);
                            } catch (Exception $e) { /* columna puede no existir */ }
                            $msg = 'Logo actualizado correctamente.';
                            $msg_type = 'ok';
              }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>CONFIGURACION - MI STORE</title>title>
  <style>
        *{box-sizing:border-box;}
        html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
        body{position:relative;overflow-x:hidden;}
        .bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,0.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,0.12),transparent 35%);pointer-events:none;z-index:0;}
        .grid-lines{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,0.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,0.10) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0;}
        .glow{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,0.20) 0%,rgba(178,102,255,0.08) 35%,transparent 70%);top:-100px;right:-100px;pointer-events:none;z-index:0;}
        .container{position:relative;z-index:3;max-width:900px;margin:0 auto;padding:28px;}
        .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;gap:12px;flex-wrap:wrap;}
        .brand-name{font-size:22px;font-weight:900;color:#b266ff;letter-spacing:0.04em;text-transform:uppercase;}
        .back-btn{text-decoration:none;color:#fff;background:linear-gradient(90deg,#7c3aed,#10b981);padding:10px 18px;border-radius:12px;font-weight:bold;}
        .page-title{font-size:26px;font-weight:900;color:#b266ff;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:28px;display:flex;align-items:center;gap:10px;}
        .card{background:rgba(14,14,18,0.85);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:28px;margin-bottom:24px;}
        .card-title{font-size:16px;font-weight:800;color:#b266ff;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid rgba(178,102,255,0.20);}
        .form-group{margin-bottom:16px;}
        .form-label{display:block;font-size:13px;color:#a0a0b0;margin-bottom:6px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;}
        .form-input{width:100%;padding:12px 16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);border-radius:12px;color:#fff;font-size:15px;outline:none;transition:border-color 0.2s;}
        .form-input:focus{border-color:rgba(178,102,255,0.60);background:rgba(178,102,255,0.06);}
        .form-input::placeholder{color:#555;}
        .btn-submit{width:100%;padding:14px;background:linear-gradient(90deg,#7c3aed,#10b981);border:none;border-radius:14px;color:#fff;font-size:15px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;cursor:pointer;transition:opacity 0.2s;margin-top:6px;}
        .btn-submit:hover{opacity:0.88;}
        .alert{padding:14px 18px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:20px;}
        .alert-ok{background:rgba(16,185,129,0.18);border:1px solid rgba(16,185,129,0.35);color:#8df0bf;}
        .alert-error{background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.30);color:#fca5a5;}
        .logo-preview{margin-top:12px;display:none;text-align:center;}
        .logo-preview img{max-width:120px;max-height:80px;border-radius:12px;border:1px solid rgba(255,255,255,0.12);}
        .info-row{display:flex;gap:16px;margin-bottom:8px;font-size:14px;}
        .info-label{color:#a0a0b0;min-width:100px;}
        .info-value{color:#fff;font-weight:600;}
        @media(max-width:600px){.topbar{flex-direction:column;align-items:flex-start;}}
      </style>
</head>head>
  <body>
    <div class="bg"></div>div>
    <div class="grid-lines"></div>div>
    <div class="glow"></div>div>
    <div class="container">
        <div class="topbar">
              <span class="brand-name">&#9881; MI STORE</span>span>
              <a href="dashboard.php" class="back-btn">&#8592; VOLVER AL PANEL</a>a>
        </div>div>

        <div class="page-title">&#9881; Configuracion del Administrador</div>div>

        <?php if ($msg): ?>
    <div class="alert <?php echo $msg_type === 'ok' ? 'alert-ok' : 'alert-error'; ?>">
          <?php echo htmlspecialchars($msg); ?>
    </div>div>
        <?php endif; ?>

        <!-- Info de la cuenta -->
        <div class="card">
              <div class="card-title">&#128100; Informacion de la cuenta</div>div>
              <div class="info-row"><span class="info-label">Email:</span>span><span class="info-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span></div>div>
              <div class="info-row"><span class="info-label">Rol:</span>span><span class="info-value"><?php echo strtoupper($user['rol'] ?? ''); ?></span></div>div>
              <div class="info-row"><span class="info-label">Creditos:</span>span><span class="info-value"><?php echo number_format((float)($user['creditos'] ?? 0)); ?></span></div>div>
        </div>div>

        <!-- Cambio de contrasena -->
        <div class="card">
              <div class="card-title">&#128274; Cambiar Contrasena</div>div>
              <form method="POST">
                      <input type="hidden" name="action" value="cambiar_password"/>
                      <div class="form-group">
                                <label class="form-label">Contrasena actual</label>label>
                                <input type="password" name="pass_actual" class="form-input" placeholder="Ingresa tu contrasena actual" required/>
                      </div>div>
                      <div class="form-group">
                                <label class="form-label">Nueva contrasena</label>label>
                                <input type="password" name="pass_nueva" class="form-input" placeholder="Minimo 6 caracteres" required/>
                      </div>div>
                      <div class="form-group">
                                <label class="form-label">Confirmar nueva contrasena</label>label>
                                <input type="password" name="pass_confirmar" class="form-input" placeholder="Repite la nueva contrasena" required/>
                      </div>div>
                      <button type="submit" class="btn-submit">&#128274; ACTUALIZAR CONTRASENA</button>button>
              </form>form>
        </div>div>

        <!-- Logo del sistema -->
        <div class="card">
              <div class="card-title">&#128444; Logo del Sistema</div>div>
              <form method="POST">
                      <input type="hidden" name="action" value="cambiar_logo"/>
                      <div class="form-group">
                                <label class="form-label">URL del logo</label>label>
                                <input type="url" name="logo_url" id="logo_url_input" class="form-input"
                                                 placeholder="https://ejemplo.com/mi-logo.png"
                                                 value="<?php echo htmlspecialchars($_SESSION['logo_url'] ?? ''); ?>"
                                                 oninput="previewLogo(this.value)"/>
                      </div>div>
                      <div class="logo-preview" id="logo_preview">
                                <p style="font-size:12px;color:#888;margin-bottom:8px;">Vista previa:</p>p>
                                <img id="logo_img" src="" alt="Logo preview"/>
                      </div>div>
                      <button type="submit" class="btn-submit">&#128444; GUARDAR LOGO</button>button>
              </form>form>
        </div>div>

    </div>div>
    <script>
      function previewLogo(url) {
          const preview = document.getElementById('logo_preview');
          const img = document.getElementById('logo_img');
          if (url && url.startsWith('http')) {
                img.src = url;
                preview.style.display = 'block';
          } else {
                preview.style.display = 'none';
          }
      }
      // Auto-preview on load if there is a value
      const existing = document.getElementById('logo_url_input').value;
      if (existing) previewLogo(existing);
        </script>
  </body>body>
</html>html>
    </script>
  </style></title>
</head>
