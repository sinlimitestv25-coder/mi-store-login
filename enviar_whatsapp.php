<?php
// enviar_whatsapp.php
// Envío manual de mensajes por WhatsApp
// Permite generar y copiar mensajes para enviar manualmente por WhatsApp Web

require_once 'config.php';

// RBAC: Solo usuarios autenticados
if (!isset($_SESSION['user_id'])) {
      http_response_code(403);
      die("Acceso denegado");
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Obtener datos del usuario autenticado
$stmt = $db->prepare("SELECT whatsapp_template FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$custom_template = $user_data['whatsapp_template'];

// Plantilla default
$default_template = "Hola {nombre_cliente},\n\nTus credenciales de acceso:\nUsuario: {usuario}\nContraseña: {contraseña}\n\nServicio: {servicio}\nVencimiento: {fecha_vence}\n\n¡Bienvenido a {app_name}!";

$template = $custom_template ?? $default_template;

// Obtener lista de usuarios/clientes del reseller
$usuarios_disponibles = [];
$servicios_disponibles = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      // Obtener usuarios y servicios para el reseller
    $stmt = $db->prepare("SELECT id, nombre, email FROM usuarios WHERE id != ? AND estado = 'activo' LIMIT 100");
      $stmt->execute([$user_id]);
      $usuarios_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle AJAX request para generar mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generar') {
      header('Content-Type: application/json');

    $usuario_id = $_POST['usuario_id'] ?? null;
      $telefono = $_POST['telefono'] ?? null;

    if (!$usuario_id || !$telefono) {
              echo json_encode(['error' => 'Faltan parámetros']);
              exit;
    }

    // Obtener datos del usuario
    $stmt = $db->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
      $stmt->execute([$usuario_id]);
      $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
              echo json_encode(['error' => 'Usuario no encontrado']);
              exit;
    }

    // Preparar variables para reemplazar
    $variables = [
              '{nombre_cliente}' => $usuario['nombre'],
              '{usuario}' => $usuario['email'],
              '{contraseña}' => 'ver_en_plataforma',
              '{servicio}' => 'Acceso a Mi Store',
              '{fecha_vence}' => date('d/m/Y', strtotime('+30 days')),
              '{app_name}' => 'Mi Store'
          ];

    // Generar mensaje
    $mensaje = str_replace(
              array_keys($variables),
              array_values($variables),
              $template
          );

    // Crear enlace wa.me
    $telefono_limpio = preg_replace('/[^0-9]/', '', $telefono);
      $wa_link = "https://wa.me/" . $telefono_limpio . "?text=" . urlencode($mensaje);

    echo json_encode([
                             'success' => true,
                             'mensaje' => $mensaje,
                             'wa_link' => $wa_link,
                             'telefono' => $telefono
                         ]);
      exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Enviar por WhatsApp</title>title>
    <style>
              * { margin: 0; padding: 0; box-sizing: border-box; }
              body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            min-height: 100vh;
                            padding: 20px;
              }
              .container {
                            max-width: 900px;
                            margin: 0 auto;
                            background: white;
                            border-radius: 10px;
                            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                            overflow: hidden;
              }
              .header {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                            padding: 30px;
                            text-align: center;
              }
              .header h1 { font-size: 28px; margin-bottom: 10px; }
              .header p { opacity: 0.9; font-size: 14px; }
              .content {
                            padding: 30px;
              }
              .form-group {
                            margin-bottom: 20px;
              }
              .form-group label {
                            display: block;
                            margin-bottom: 8px;
                            font-weight: 600;
                            color: #333;
              }
              .form-group input,
              .form-group select,
              .form-group textarea {
                            width: 100%;
                            padding: 12px;
                            border: 2px solid #e0e0e0;
                            border-radius: 5px;
                            font-size: 14px;
                            font-family: inherit;
                            transition: border-color 0.3s;
              }
              .form-group input:focus,
              .form-group select:focus,
              .form-group textarea:focus {
                            outline: none;
                            border-color: #667eea;
                            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
              }
              .form-row {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 20px;
              }
              @media (max-width: 600px) {
                            .form-row { grid-template-columns: 1fr; }
              }
              .btn-group {
                            display: flex;
                            gap: 10px;
                            margin-top: 30px;
              }
              button {
                            flex: 1;
                            padding: 14px;
                            border: none;
                            border-radius: 5px;
                            font-size: 16px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s;
              }
              .btn-generar {
                            background: #667eea;
                            color: white;
              }
              .btn-generar:hover { background: #5568d3; transform: translateY(-2px); }
              .btn-copiar {
                            background: #26a69a;
                            color: white;
                            display: none;
              }
              .btn-copiar:hover { background: #1f8980; }
              .btn-whatsapp {
                            background: #25d366;
                            color: white;
                            display: none;
              }
              .btn-whatsapp:hover { background: #1eaa56; }
              .mensaje-preview {
                            background: #f5f5f5;
                            border-left: 4px solid #667eea;
                            padding: 20px;
                            border-radius: 5px;
                            margin: 20px 0;
                            white-space: pre-wrap;
                            word-wrap: break-word;
                            font-size: 14px;
                            line-height: 1.6;
                            display: none;
              }
              .alert {
                            padding: 15px;
                            border-radius: 5px;
                            margin-bottom: 20px;
                            display: none;
              }
              .alert.success {
                            background: #d4edda;
                            color: #155724;
                            border: 1px solid #c3e6cb;
                            display: block;
              }
              .alert.error {
                            background: #f8d7da;
                            color: #721c24;
                            border: 1px solid #f5c6cb;
                            display: block;
              }
          </style>
</head>head>
  <body>
        <div class="container">
                  <div class="header">
                                <h1>📱 Enviar por WhatsApp</h1>h1>
                                <p>Genera mensajes personalizados y envíalos manualmente por WhatsApp Web</p>p>
                  </div>div>
                <div class="content">
                              <form id="whatsappForm" onsubmit="generarMensaje(event)">
                                                <div class="form-row">
                                                                      <div class="form-group">
                                                                                                <label for="usuario_id">Seleccionar Usuario/Cliente:</label>label>
                                                                                                <select id="usuario_id" name="usuario_id" required>
                                                                                                                              <option value="">-- Elige un usuario --</option>option>
                                                                                                                              <?php foreach($usuarios_disponibles as $u): ?>
                                  <option value="<?php echo $u['id']; ?>">
                                                                        <?php echo htmlspecialchars($u['nombre']); ?> (<?php echo htmlspecialchars($u['email']); ?>)
                                  </option>option>
                                                                                                                              <?php endforeach; ?>
                                                                                                </select>select>
                                                                      </div>div>
                                                                      <div class="form-group">
                                                                                                <label for="telefono">Número de WhatsApp:</label>label>
                                                                                                <input type="text" id="telefono" name="telefono" placeholder="+34 612345678" required>
                                                                      </div>div>
                                                </div>div>

                                                <div class="form-group">
                                                                      <label for="template">Vista previa de tu mensaje:</label>label>
                                                                      <textarea id="template" readonly rows="6"><?php echo htmlspecialchars($template); ?></textarea>
                                                </div>div>

                                                <div class="btn-group">
                                                                      <button type="submit" class="btn-generar">Generar Mensaje</button>button>
                                                                      <button type="button" class="btn-copiar" onclick="copiarMensaje()">Copiar Mensaje</button>button>
                                                                      <button type="button" class="btn-whatsapp" onclick="abrirWhatsApp()">Abrir en WhatsApp Web</button>button>
                                                </div>div>
                              </form>form>

                              <div id="alerta" class="alert"></div>div>
                              <div id="mensajePreview" class="mensaje-preview"></div>div>
                </div>div>
        </div>div>

        <script>
                  let mensaje_generado = '';
                  let wa_link_generado = '';

                  function generarMensaje(e) {
                                e.preventDefault();

                                const usuario_id = document.getElementById('usuario_id').value;
                                const telefono = document.getElementById('telefono').value;

                                if (!usuario_id || !telefono) {
                                                  mostrarAlerta('Por favor completa todos los campos', 'error');
                                                  return;
                                }

                                const formData = new FormData();
                                formData.append('action', 'generar');
                                formData.append('usuario_id', usuario_id);
                                formData.append('telefono', telefono);

                                fetch('enviar_whatsapp.php', {
                                                  method: 'POST',
                                                  body: formData
                                })
                                .then(r => r.json())
                                .then(data => {
                                                  if (data.error) {
                                                                        mostrarAlerta(data.error, 'error');
                                                  } else {
                                                                        mensaje_generado = data.mensaje;
                                                                        wa_link_generado = data.wa_link;

                                                                        document.getElementById('mensajePreview').textContent = mensaje_generado;
                                                                        document.getElementById('mensajePreview').style.display = 'block';
                                                                        document.querySelector('.btn-copiar').style.display = 'block';
                                                                        document.querySelector('.btn-whatsapp').style.display = 'block';

                                                                        mostrarAlerta('✓ Mensaje generado correctamente', 'success');
                                                  }
                                })
                                .catch(err => mostrarAlerta('Error: ' + err, 'error'));
                  }

                  function copiarMensaje() {
                                navigator.clipboard.writeText(mensaje_generado).then(() => {
                                                  mostrarAlerta('✓ Mensaje copiado al portapapeles', 'success');
                                });
                  }

                  function abrirWhatsApp() {
                                window.open(wa_link_generado, '_blank');
                  }

                  function mostrarAlerta(mensaje, tipo) {
                                const alerta = document.getElementById('alerta');
                                alerta.textContent = mensaje;
                                alerta.className = 'alert ' + tipo;
                                setTimeout(() => alerta.style.display = 'none', 4000);
                  }
        </script>
  </body>body>
</html>html>
        </script>
                </p>
    </style></title>
</head>
