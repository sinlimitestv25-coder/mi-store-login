<?php
require_once 'config.php';
if(!isset($_SESSION['user_id'])){http_response_code(403);exit('No autorizado');}
$db=getDB();
$user_id=$_SESSION['user_id'];
$stats=[];
if($_SERVER['REQUEST_METHOD']=='POST'&&!empty($_POST['accion'])){
  if($_POST['accion']=='obtener_datos'){
    header('Content-Type: application/json');
    $stmt=$db->prepare('SELECT DATE(fecha) as dia,COUNT(*) as total FROM whatsapp_audit_log WHERE user_id=? GROUP BY DATE(fecha) ORDER BY dia DESC LIMIT 30');
    $stmt->execute([$user_id]);
    $datos=$stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['dias'=>array_reverse($datos)]);
    exit;}}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gráficos WhatsApp</title>title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
  body{font-family:Arial;margin:0;padding:20px;background:#1a1a1a;color:#fff}
  .contenedor{max-width:900px;margin:0 auto}
  .titulo{font-size:24px;margin-bottom:20px}
  .grafico-container{background:#2a2a2a;padding:20px;border-radius:5px;margin:20px 0}
  canvas{max-height:400px}
  </style>
</head>head><body>
  <div class="contenedor">
    <div class="titulo">Estadísticas WhatsApp</div>div>
    <div class="grafico-container">
      <canvas id="grafico"></canvas>canvas>
    </div>div>
  </div>div>
  <script>
    let chart=null;
    function cargarDatos(){
      fetch('graficos_whatsapp.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'accion=obtener_datos'})
      .then(r=>r.json()).then(data=>{
        const dias=data.dias.map(d=>d.dia);
        const totales=data.dias.map(d=>d.total);
        const ctx=document.getElementById('grafico').getContext('2d');
        if(chart)chart.destroy();
        chart=new Chart(ctx,{type:'line',data:{labels:dias,datasets:[{label:'Mensajes Enviados',data:totales,borderColor:'#25d366',backgroundColor:'rgba(37,211,102,0.1)',tension:0.4,fill:true}]},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{labels:{color:'#fff'}}},scales:{x:{ticks:{color:'#999'},grid:{color:'#444'}},y:{ticks:{color:'#999'},grid:{color:'#444'}}}}})})}
    cargarDatos();
    setInterval(cargarDatos,30000);
  </script>
</body>body></html>html>
  </script>
</style></head></html>
