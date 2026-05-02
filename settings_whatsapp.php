<?php
// settings_whatsapp.php
// WhatsApp Template Settings Page with RBAC

require_once 'config.php';

// RBAC: Only authenticated users
if (!isset($_SESSION['user_id'])) {
      http_response_code(403);
      die("Acceso denegado");
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Get current template for user
$stmt = $db->prepare("SELECT whatsapp_template FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$current_template = $result['whatsapp_template'] ?? null;

// Default template
$default_template = "Hola {nombre_cliente},\n\nTus credenciales de acceso:\nUsuario: {usuario}\nContraseña: {contraseña}\n\nServicio: {servicio}\nVencimiento: {fecha_vence}\n\n¡Bienvenido a {app_name}!";

$display_template = $current_template ?? $default_template;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (isset($_POST['action'])) {
                if ($_POST['action'] === 'save') {
                              $new_template = $_POST['template'] ?? '';
                              $stmt = $db->prepare("UPDATE usuarios SET whatsapp_template = ? WHERE id = ?");
                              $stmt->execute([$new_template, $user_id]);
                              $_SESSION['success'] = "Template actualizado correctamente";
                              header("Location: settings_whatsapp.php");
                              exit;
                } elseif ($_POST['action'] === 'reset') {
                              $stmt = $db->prepare("UPDATE usuarios SET whatsapp_template = NULL WHERE id = ?");
                              $stmt->execute([$user_id]);
                              $_SESSION['success'] = "Template resetado al default";
                              header("Location: settings_whatsapp.php");
                              exit;
                }
      }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>WhatsApp Settings</title>title>
    <style>
              body { font-family: Arial, sans-serif; background: #f5f5f5; }
              .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; border-radius: 5px; }
              textarea { width: 100%; min-height: 200px; padding: 10px; border: 1px solid #ddd; }
              button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
              button:hover { background: #0056b3; }
              .info { background: #e7f3ff; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
          </style>
</head>head>
  <body>
        <div class="container">
                  <h1>Configuración de Template WhatsApp</h1>h1>

                  <?php if (isset($_SESSION['success'])): ?>
              <div style="background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
              </div>div>
                  <?php endif; ?>

                  <div class="info">
                                <strong>Variables disponibles:</strong>strong>
                                {usuario}, {contraseña}, {nombre_cliente}, {servicio}, {fecha_vence}, {app_name}
                  </div>div>

                  <form method="POST">
                                <label>Tu Template Personalizado:</label>label><br>
                                <textarea name="template"><?php echo htmlspecialchars($display_template); ?></textarea>
                                <br><br>
                                <button type="submit" name="action" value="save">Guardar Cambios</button>button>
                                <button type="submit" name="action" value="reset">Restaurar Default</button>button>
                  </form>form>
        </div>div>
  </body>body>
</html>html>
    </style></title>
</head>
