<?php
require_once 'config.php';
if(!isset($_SESSION['user_id'])){http_response_code(403);exit('No autorizado');}
$db=getDB();
$user_id=$_SESSION['user_id'];
if($_SERVER['REQUEST_METHOD']=='POST'){
  $email=$_POST['email']??'';
  $telefono=$_POST['telefono']??'';
  $asunto=$_POST['asunto']??'Confirmación de envío WhatsApp';
  $mensaje=$_POST['mensaje']??'Su mensaje ha sido procesado correctamente.';
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)){echo json_encode(['ok'=>0,'msg'=>'Email inválido']);exit;}
  $headers="From: noreply@mistore.com\r\nContent-Type: text/html; charset=UTF-8\r\n";
  $body="<html><body style='font-family:Arial;background:#1a1a1a;color:#fff;padding:20px'><div style='max-width:600px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:5px'><h2 style='color:#25d366'>{$asunto}</h2><p>{$mensaje}</p><p>Teléfono: {$telefono}</p><hr style='border:none;border-top:1px solid #444'><p style='font-size:12px;color:#999'>Correo enviado desde Mi Store</p></div></body></html>";
  if(mail($email,$asunto,$body,$headers)){
    $stmt=$db->prepare('INSERT INTO whatsapp_email_logs (user_id,destinatario,asunto,estado,fecha) VALUES (?,?,?,?,NOW())');
    $stmt->execute([$user_id,$email,$asunto,'enviado']);
    echo json_encode(['ok'=>1,'msg'=>'Email enviado correctamente']);
  }else{
    echo json_encode(['ok'=>0,'msg'=>'Error al enviar email']);}
  exit;}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Enviar Confirmación por Email</title>title>
<style>
  body{font-family:Arial;margin:0;padding:20px;background:#1a1a1a;color:#fff}
  .formulario{max-width:600px;margin:0 auto;background:#2a2a2a;padding:20px;border-radius:5px}
  .campo{margin:15px 0}
  label{display:block;margin-bottom:5px;font-weight:bold}
  input,textarea{width:100%;padding:10px;background:#333;border:1px solid #444;color:#fff;border-radius:3px;font-family:Arial}
  input:focus,textarea:focus{outline:0;border-color:#25d366}
  button{background:#25d366;color:#000;padding:10px 20px;border:0;border-radius:3px;cursor:pointer;font-weight:bold;width:100%;margin-top:20px}
  button:hover{background:#20a853}
  .mensaje{margin-top:20px;padding:10px;border-radius:3px;display:none}
  .exito{background:#25d36630;color:#25d366;border:1px solid #25d366}
  .error{background:#ff006630;color:#ff0066;border:1px solid #ff0066}
  </style>
</head>head><body>
  <div class="formulario">
    <h2>Enviar Confirmación por Email</h2>h2>
    <form id="frm" onsubmit="enviar(event)">
      <div class="campo"><label>Email Destinatario:</label>label><input type="email" name="email" required></div>div>
      <div class="campo"><label>Teléfono:</label>label><input type="tel" name="telefono" required></div>div>
      <div class="campo"><label>Asunto:</label>label><input type="text" name="asunto" value="Confirmación de envío WhatsApp"></div>div>
      <div class="campo"><label>Mensaje:</label>label><textarea name="mensaje" rows="5" required>Su mensaje ha sido procesado correctamente.</textarea></div>div>
      <button type="submit">Enviar Email</button>button>
    </form>form>
    <div id="msg" class="mensaje"></div>div>
  </div>div>
  <script>
    function enviar(e){
      e.preventDefault();
      const data=new FormData(document.getElementById('frm'));
      fetch('enviar_confirmacion_email.php',{method:'POST',body:data}).then(r=>r.json()).then(res=>{
        const msg=document.getElementById('msg');
        msg.className='mensaje '+(res.ok?'exito':'error');
        msg.textContent=res.msg;
        msg.style.display='block';
        if(res.ok)document.getElementById('frm').reset();})}
  </script>
</body>body></html>html>
  </script>
</style></head></html>
