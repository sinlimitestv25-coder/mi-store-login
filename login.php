<?php
// =============================================
//  login.php - Autenticacion
//  Redirige al dashboard si ya esta logueado
// =============================================
require_once __DIR__ . '/config.php';
startSession();

$error = '';

// Si ya esta logueado, redirigir
if (isLoggedIn()) {
      header('Location: dashboard.php');
      exit;
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $email    = trim($_POST['email']    ?? '');
      $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
              $error = 'Por favor completa todos los campos.';
    } else {
              $db   = getDB();
              $stmt = $db->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
              $stmt->execute([$email]);
              $user = $stmt->fetch();

          if ($user && password_verify($password, $user['password'])) {
                        if ($user['estado'] !== 'activo') {
                                          $error = 'Tu cuenta esta inactiva o vencida. Contacta al administrador.';
                        } else {
                                          // Guardar sesion
                            $_SESSION['user_id'] = $user['id'];
                                          $_SESSION['user']    = [
                                                                'id'       => $user['id'],
                                                                'nombre'   => $user['nombre'],
                                                                'email'    => $user['email'],
                                                                'rol'      => $user['rol'],
                                                                'creditos' => $user['creditos'],
                                                            ];
                                          header('Location: dashboard.php');
                                          exit;
                        }
          } else {
                        $error = 'Email o contrasena incorrectos.';
          }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>LOGIN - <?= APP_NAME ?></title>title>
<style>
  *{box-sizing:border-box;}
  html,body{margin:0;padding:0;min-height:100%;font-family:Arial,sans-serif;background:#050505;color:#fff;}
  body{position:relative;overflow-x:hidden;}
  .bg{position:fixed;inset:0;background:radial-gradient(circle at 20% 20%,rgba(124,58,237,0.18),transparent 28%),radial-gradient(circle at 80% 30%,rgba(16,185,129,0.12),transparent 24%),radial-gradient(circle at 50% 80%,rgba(124,58,237,0.10),transparent 30%),linear-gradient(135deg,#030303 0%,#07070b 40%,#050505 100%);z-index:0;}
  .grid-lines{position:fixed;inset:-10%;background-image:linear-gradient(rgba(178,102,255,0.10) 1px,transparent 1px),linear-gradient(90deg,rgba(178,102,255,0.10) 1px,transparent 1px);background-size:40px 40px;transform:perspective(900px) rotateX(70deg) translateY(-40px);opacity:0.28;z-index:1;transition:transform 0.12s linear;}
  .glow{position:fixed;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(178,102,255,0.20) 0%,rgba(178,102,255,0.08) 35%,transparent 70%);pointer-events:none;transform:translate(-50%,-50%);z-index:2;}
  .container{position:relative;z-index:3;min-height:100vh;display:flex;align-items:center;justify-content:center;gap:80px;padding:40px 28px;}
  .brand-side{display:flex;flex-direction:column;align-items:center;gap:18px;}
  .brand-logo-placeholder{width:220px;height:220px;border-radius:50%;background:linear-gradient(135deg,rgba(124,58,237,0.8),rgba(16,185,129,0.6));display:flex;align-items:center;justify-content:center;font-size:72px;filter:drop-shadow(0 0 24px rgba(178,102,255,0.5));}
  .brand-name{font-size:26px;font-weight:900;letter-spacing:0.08em;text-transform:uppercase;color:#b266ff;text-shadow:0 0 18px rgba(178,102,255,0.6);}
  .login-card{background:rgba(14,14,18,0.85);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.08);border-radius:22px;padding:40px 36px;min-width:320px;max-width:380px;width:100%;box-shadow:0 0 40px rgba(124,58,237,0.12);}
  .login-card h2{text-align:center;margin:0 0 28px 0;font-size:20px;font-weight:900;letter-spacing:0.10em;text-transform:uppercase;color:#fff;}
  .input-wrap{position:relative;margin-bottom:16px;}
  .input-wrap input{width:100%;padding:14px 16px;background:rgba(255,255,255,0.95);border:none;border-radius:10px;font-size:15px;color:#111;outline:none;transition:box-shadow 0.2s;}
  .input-wrap input:focus{box-shadow:0 0 0 2px rgba(124,58,237,0.5);}
  .toggle-pass{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:700;color:#7c3aed;cursor:pointer;user-select:none;}
  .btn-login{width:100%;padding:14px;margin-top:8px;border:none;border-radius:10px;background:linear-gradient(90deg,#7c3aed 0%,#10b981 100%);color:#fff;font-size:15px;font-weight:900;letter-spacing:0.10em;text-transform:uppercase;cursor:pointer;transition:opacity 0.2s,transform 0.15s;}
  .btn-login:hover{opacity:0.90;transform:translateY(-1px);}
  .error-msg{margin-top:14px;padding:10px 14px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:13px;color:#f87171;text-align:center;}
  .footer{position:fixed;bottom:18px;left:50%;transform:translateX(-50%);z-index:10;font-size:12px;color:#666;letter-spacing:0.05em;text-transform:uppercase;white-space:nowrap;}
  .footer a{color:#b266ff;text-decoration:none;font-weight:700;}
  @media(max-width:640px){.container{flex-direction:column;gap:32px;padding:60px 20px 80px;}.brand-logo-placeholder{width:150px;height:150px;}}
  </style>
</head>head>
  <body>
    <div class="bg"></div>div>
    <div class="grid-lines" id="grid"></div>div>
    <div class="glow" id="glow"></div>div>
    <div class="container">
        <div class="brand-side">
              <!-- Reemplaza por: <img src="logo.png" style="width:220px;height:220px;object-fit:contain;"> -->
              <div class="brand-logo-placeholder">🚀</div>div>
              <div class="brand-name"><?= APP_NAME ?></div>
        </div>div>
        <div class="login-card">
              <h2>Iniciar Sesion</h2>h2>
              <form method="POST" action="">
                      <div class="input-wrap">
                                <input type="email" name="email" placeholder="Correo electronico" required
                                                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
                      </div>div>
                      <div class="input-wrap">
                                <input type="password" name="password" id="password" placeholder="Contrasena" required/>
                                <span class="toggle-pass" onclick="togglePass()">VER</span>span>
                      </div>div>
                      <button type="submit" class="btn-login">Ingresar</button>button>
                      <?php if ($error): ?>
          <div class="error-msg"><?= htmlspecialchars($error) ?></div>
                      <?php endif; ?>
              </form>form>
        </div>div>
    </div>div>
    <div class="footer">DESARROLLADO POR <a href="#"><?= APP_NAME ?></a></div>div>
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
      function togglePass(){var p=document.getElementById('password');p.type=p.type==='password'?'text':'password';}
    </script>
  </body>body>
</html>html>

    </script>
</style></title>
</head>
