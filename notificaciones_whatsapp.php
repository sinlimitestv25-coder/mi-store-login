<?php
require_once 'config.php';
if(!isset($_SESSION['user_id'])){http_response_code(403);exit('No autorizado');}
$db=getDB();
$user_id=$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']=='POST'){
  if(!empty($_POST['accion'])){
    if($_POST['accion']=='obtener'){
      $stmt=$db->prepare('SELECT id,tipo,mensaje,leida,fecha FROM whatsapp_notificaciones WHERE user_id=? ORDER BY fecha DESC LIMIT 20');
      $stmt->execute([$user_id]);
      header('Content-Type: application/json');
      echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
      exit;}
    if($_POST['accion']=='marcar_leida'){
      $id=(int)$_POST['id'];
      $stmt=$db->prepare('UPDATE whatsapp_notificaciones SET leida=1 WHERE id=? AND user_id=?');
      $stmt->execute([$id,$user_id]);
      echo json_encode(['ok'=>1]);
      exit;}}}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Notificaciones WhatsApp</title>title>
<style>
  body{font-family:Arial;margin:0;padding:20px;background:#1a1a1a;color:#fff}
  .notificaciones{max-width:600px;margin:0 auto}
  .titulo{font-size:24px;margin-bottom:20px}
  .notif{background:#2a2a2a;padding:15px;margin:10px 0;border-left:4px solid #25d366;border-radius:5px;cursor:pointer}
  .notif.no-leida{background:#333}
  .notif-tipo{font-size:12px;color:#999;margin-bottom:5px}
  .notif-msg{margin:10px 0}
  .notif-fecha{font-size:11px;color:#666}
  .notif:hover{background:#3a3a3a}
  </style>
</head>head><body>
  <div class="notificaciones">
    <div class="titulo">Notificaciones</div>div>
    <div id="lista"></div>div>
  </div>div>
  <script>
    function cargar(){
      fetch('notificaciones_whatsapp.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'accion=obtener'})
      .then(r=>r.json()).then(notifs=>{
        const html=notifs.map(n=>`<div class='notif ${n.leida?'':'no-leida'}' onclick="marcar(${n.id})">
        <div class='notif-tipo'>${n.tipo}</div>
        <div class='notif-msg'>${n.mensaje}</div>
        <div class='notif-fecha'>${n.fecha}</div></div>`).join('');
        document.getElementById('lista').innerHTML=html||'<p>Sin notificaciones</p>'})}
    function marcar(id){
      fetch('notificaciones_whatsapp.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`accion=marcar_leida&id=${id}`}).then(()=>cargar())}
    cargar();setInterval(cargar,5000);
  </script>
</body>body></html>html>
  </script>
</style></head></html>
