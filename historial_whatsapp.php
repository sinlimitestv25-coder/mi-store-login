<?php
// historial_whatsapp.php
// Página de auditoría y historial de envíos de WhatsApp

require_once 'config.php';

// RBAC: Solo usuarios autenticados
if (!isset($_SESSION['user_id'])) {
      http_response_code(403);
      die("Acceso denegado");
}

$user_id = $_SESSION['user_id'];
$db = getDB();

// Obtener historial de envíos del usuario
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT 
            id, cliente_id, numero_telefono, nombre_cliente, 
                    usuario_email, estado, fecha_envio
                        FROM whatsapp_audit_log 
                            WHERE reseller_id = ? 
                                ORDER BY fecha_envio DESC 
                                    LIMIT ? OFFSET ?
                                    ");
$stmt->execute([$user_id, $limit, $offset]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total de registros
$stmt = $db->prepare("SELECT COUNT(*) as total FROM whatsapp_audit_log WHERE reseller_id = ?");
$stmt->execute([$user_id]);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// Estadísticas
$stmt = $db->prepare("
    SELECT 
            estado,
                    COUNT(*) as cantidad
                        FROM whatsapp_audit_log 
                            WHERE reseller_id = ? 
                                GROUP BY estado
                                ");
$stmt->execute([$user_id]);
$estadisticas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Handle delete (for testing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="whatsapp_historial_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
      fputcsv($output, ['ID', 'Cliente', 'Email', 'Teléfono', 'Estado', 'Fecha de Envío']);

    foreach ($historial as $row) {
              fputcsv($output, [
                                  $row['id'],
                                  $row['nombre_cliente'],
                                  $row['usuario_email'],
                                  $row['numero_telefono'],
                                  $row['estado'],
                                  $row['fecha_envio']
                              ]);
    }
      fclose($output);
      exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Historial de Envíos WhatsApp</title>title>
    <style>
              * { margin: 0; padding: 0; box-sizing: border-box; }
              body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            background: #f5f7fa;
                            color: #333;
              }
              .container {
                            max-width: 1200px;
                            margin: 0 auto;
                            padding: 20px;
              }
              .header {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            color: white;
                            padding: 30px;
                            border-radius: 10px;
                            margin-bottom: 30px;
                            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
              }
              .header h1 { margin-bottom: 10px; }
              .stats {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                            gap: 20px;
                            margin-bottom: 30px;
              }
              .stat-card {
                            background: white;
                            padding: 20px;
                            border-radius: 8px;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
              }
              .stat-card h3 { color: #667eea; font-size: 24px; margin-bottom: 5px; }
              .stat-card p { color: #999; font-size: 14px; }
              .filter-bar {
                            background: white;
                            padding: 20px;
                            border-radius: 8px;
                            margin-bottom: 20px;
                            display: flex;
                            gap: 15px;
                            flex-wrap: wrap;
              }
              .filter-bar input,
              .filter-bar select {
                            padding: 10px;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                            font-size: 14px;
              }
              .btn {
                            padding: 10px 20px;
                            border: none;
                            border-radius: 5px;
                            cursor: pointer;
                            font-size: 14px;
                            font-weight: 600;
                            transition: all 0.3s;
              }
              .btn-primary { background: #667eea; color: white; }
              .btn-primary:hover { background: #5568d3; }
              .btn-success { background: #4caf50; color: white; }
              .btn-success:hover { background: #45a049; }
              .table-container {
                            background: white;
                            border-radius: 8px;
                            overflow: hidden;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
              }
              table {
                            width: 100%;
                            border-collapse: collapse;
              }
              thead {
                            background: #f8f9fa;
                            border-bottom: 2px solid #e0e0e0;
              }
              th {
                            padding: 15px;
                            text-align: left;
                            font-weight: 600;
                            color: #333;
              }
              td {
                            padding: 15px;
                            border-bottom: 1px solid #eee;
              }
              tr:hover { background: #f9f9f9; }
              .badge {
                            display: inline-block;
                            padding: 5px 12px;
                            border-radius: 20px;
                            font-size: 12px;
                            font-weight: 600;
              }
              .badge-enviado { background: #d4edda; color: #155724; }
              .badge-pendiente { background: #fff3cd; color: #856404; }
              .badge-fallido { background: #f8d7da; color: #721c24; }
              .pagination {
                            display: flex;
                            justify-content: center;
                            gap: 10px;
                            margin-top: 20px;
                            padding: 20px;
              }
              .pagination a,
              .pagination span {
                            padding: 8px 12px;
                            border: 1px solid #ddd;
                            border-radius: 5px;
                            text-decoration: none;
                            color: #667eea;
              }
              .pagination .active {
                            background: #667eea;
                            color: white;
              }
              .empty {
                            padding: 40px;
                            text-align: center;
                            color: #999;
              }
          </style>
</head>head>
  <body>
        <div class="container">
                  <div class="header">
                                <h1>📊 Historial de Envíos WhatsApp</h1>h1>
                                <p>Auditoría completa de todos tus envíos realizados</p>p>
                  </div>div>
        
                <!-- Estadísticas -->
                <div class="stats">
                              <div class="stat-card">
                                                <h3><?php echo $total; ?></h3>
                                                <p>Total de envíos</p>p>
                              </div>div>
                            <div class="stat-card">
                                              <h3><?php echo $estadisticas['enviado'] ?? 0; ?></h3>
                                              <p>Envíos exitosos</p>p>
                            </div>div>
                              <div class="stat-card">
                                                <h3><?php echo $estadisticas['pendiente'] ?? 0; ?></h3>
                                                <p>Pendientes</p>p>
                              </div>div>
                              <div class="stat-card">
                                                <h3><?php echo $estadisticas['fallido'] ?? 0; ?></h3>
                                                <p>Fallidos</p>p>
                              </div>div>
                </div>div>

                  <!-- Filtros y acciones -->
                  <div class="filter-bar">
                                <input type="text" placeholder="Buscar por nombre o email..." id="search">
                                <select id="estado">
                                                  <option value="">- Todos los estados -</option>option>
                                                  <option value="enviado">Enviado</option>option>
                                                  <option value="pendiente">Pendiente</option>option>
                                                  <option value="fallido">Fallido</option>option>
                                </select>select>
                                <form method="POST" style="display: inline;">
                                                  <input type="hidden" name="action" value="export">
                                                  <button type="submit" class="btn btn-success">Descargar CSV</button>button>
                                </form>form>
                                <a href="enviar_whatsapp.php" class="btn btn-primary">Nuevo Envío</a>a>
                  </div>div>

                  <!-- Tabla de historial -->
                  <div class="table-container">
                                <?php if (empty($historial)): ?>
                  <div class="empty">
                                        <p>No hay envíos registrados aún</p>p>
                  </div>div>
                                <?php else: ?>
                  <table>
                                        <thead>
                                                                  <tr>
                                                                                                <th>Cliente</th>th>
                                                                    <th>Email</th>th>
                                                                    <th>Teléfono</th>th>
                                                                    <th>Estado</th>th>
                                                                    <th>Fecha</th>th>
                                                                  </tr>tr>
                                        </thead>thead>
                                      <tbody>
                                                                <?php foreach ($historial as $row): ?>
                              <tr>
                                                                <td><?php echo htmlspecialchars($row['nombre_cliente']); ?></td>
                                                  <td><?php echo htmlspecialchars($row['usuario_email']); ?></td>
                                                  <td><?php echo htmlspecialchars($row['numero_telefono']); ?></td>
                                                  <td>
                                                                                        <span class="badge badge-<?php echo $row['estado']; ?>">
                                                                                                                                  <?php echo ucfirst($row['estado']); ?>
                                                                                        </span>span>
                                                  </td>td>
                                                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_envio'])); ?></td>
                              </tr>tr>
                                                                <?php endforeach; ?>
                                      </tbody>tbody>
                  </table>table>

                                    <!-- Paginación -->
                                    <?php if ($total_pages > 1): ?>
                      <div class="pagination">
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                              <?php if ($i === (int)$page): ?>
                                  <span class="active"><?php echo $i; ?></span>
                                                    <?php else: ?>
                                  <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                    <?php endif; ?>
                          <?php endfor; ?>
                      </div>div>
                                    <?php endif; ?>
              <?php endif; ?>
                  </div>div>
        </div>div>
  </body>body>
</html>html>
                                                  </td></td>
                              </tr>
                                      </tbody></th></th>
                                                                  </tr>
                            </p>
                </p>
    </style></title>
</head>
