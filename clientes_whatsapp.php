<?php
require_once 'config.php';
if(!isset($_SESSION['user_id'])){http_response_code(403);exit('No autorizado');}
$db=getDB();
$stmt=$db->prepare('SELECT id,nombre,email,telefono FROM clientes ORDER BY nombre');
$stmt->execute();
$clientes=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clientes - WhatsApp</title>title>
<script src="modal_whatsapp.js"></script>
<style>
  body{font-family:Arial;margin:0;padding:20px;background:#1a1a1a;color:#fff}
  .contenedor{max-width:1000px;margin:0 auto}
  .titulo{font-size:28px;margin-bottom:20px;color:#25d366}
  table{width:100%;border-collapse:collapse;background:#2a2a2a;border-radius:5px;overflow:hidden}
  th{background:#1a1a1a;padding:12px;text-align:left;font-weight:bold;color:#25d366}
  td{padding:12px;border-bottom:1px solid #444}
  tr:hover{background:#333}
  .btn{padding:8px 12px;border:none;border-radius:3px;cursor:pointer;font-weight:bold}
  .btn-wa{background:#25d366;color:#000;margin-right:5px}
  .btn-wa:hover{background:#20a853}
  .no-datos{padding:20px;text-align:center;color:#999}
  </style>
</head>head><body>
  <div class="contenedor">
    <h1 class="titulo">WhatsApp - Gestionar Clientes</h1>h1>
    <table>
      <thead><tr><th>Nombre</th>th><th>Email</th>th><th>Telefono</th>th><th>Accion</th>th></tr>tr></thead>thead>
    <tbody>
      <?php if(count($clientes)>0){
  foreach($clientes as $c){
    $tel=preg_replace("/[^0-9]/","",$c['telefono']);
    echo '<tr><td>'.$c['nombre'].'</td><td>'.$c['email'].'</td><td>'.$c['telefono'].'</td><td><button class="btn btn-wa" onclick="mostrarModalWhatsApp({nombre:\"'.$c['nombre'].'\",telefono:\"'.$tel.'\",mensaje:\"Hola '.$c['nombre'].',\\nTe envio este mensaje desde Mi Store.\"})">Enviar WhatsApp</button></td></tr>';
  }
}else{echo '<tr><td colspan="4" class="no-datos">Sin clientes</td></tr>';}
?>
    </tbody>tbody>
    </table>table>
  </div>div>
</body>body></html>html>
    </tbody></th></th></tr>
</style></head></html>
