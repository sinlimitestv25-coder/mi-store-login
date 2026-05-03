// Modal WhatsApp - Componente reutilizable
function mostrarModalWhatsApp(datos){
const modal=document.createElement('div');
modal.id='modalWA';
modal.style.cssText='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:9999';
const contenido=`
<div style="background:#2a2a2a;color:#fff;border-radius:10px;padding:30px;max-width:500px;width:90%;box-shadow:0 4px 20px rgba(0,0,0,0.5)">
<h2 style="margin:0 0 20px 0;color:#25d366">Enviar por WhatsApp</h2>
<div style="background:#1a1a1a;padding:15px;border-radius:5px;margin:20px 0;border-left:4px solid #25d366;min-height:100px">
<p style="margin:0 0 10px 0;font-weight:bold">Para: <span style="color:#25d366">${datos.nombre}</span></p>
<p style="margin:0 0 10px 0;color:#999">Teléfono: ${datos.telefono}</p>
<hr style="border:none;border-top:1px solid #444;margin:10px 0">
<p style="margin:0;white-space:pre-wrap;line-height:1.6">${datos.mensaje}</p>
</div>
<div style="display:flex;gap:10px;margin-top:20px">
<button onclick="document.getElementById('modalWA').remove()" style="flex:1;padding:12px;background:#555;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:bold">Cancelar</button>
<button onclick="aceptarWhatsApp('${datos.telefono}','${datos.mensaje.replace(/'/g,'\\\'')}','${datos.nombre}')" style="flex:1;padding:12px;background:#25d366;color:#000;border:none;border-radius:5px;cursor:pointer;font-weight:bold">Aceptar y Enviar</button>
</div>
</div>
`;
modal.innerHTML=contenido;
document.body.appendChild(modal);
modal.addEventListener('click',function(e){
if(e.target.id=='modalWA')modal.remove();});
}
function aceptarWhatsApp(telefono,mensaje,nombre){
const url=`https://wa.me/${telefono.replace(/[^0-9]/g,'')}?text=${encodeURIComponent(mensaje)}`;
window.open(url,'_blank');
registrarEnvio(nombre,telefono);
document.getElementById('modalWA').remove();
}
function registrarEnvio(nombre,telefono){
fetch('enviar_whatsapp.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`accion=registrar&nombre=${encodeURIComponent(nombre)}&telefono=${encodeURIComponent(telefono)}`
}).then(r=>r.json()).catch(e=>console.log('Registro realizado'));
}
