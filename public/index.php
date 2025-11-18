<?php
// Simple index - serves the player
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>IPTV Player - Render Starter</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
  <h1>IPTV Player - Render Starter</h1>
  <div id="player">
    <video id="video" controls width="800" height="450"></video>
  </div>
  <div id="channels"></div>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
const token = localStorage.getItem('iptv_token') || '';
if (!token) {
  // For demo only: set a token that matches server sample. Replace with login flow in production.
  localStorage.setItem('iptv_token', 'demo-token-123');
}
fetch('/api/channels.php', { headers:{ 'X-Auth-Token': localStorage.getItem('iptv_token') } })
  .then(r=>r.json())
  .then(data=>{
    const list = document.getElementById('channels');
    data.channels.forEach(ch=>{
      const btn = document.createElement('button');
      btn.textContent = ch.name;
      btn.onclick = ()=>play(ch.stream_url + '?token=' + encodeURIComponent(localStorage.getItem('iptv_token')));
      list.appendChild(btn);
    });
  }).catch(err=>{ console.error(err); });

function play(url) {
  const video = document.getElementById('video');
  if (Hls.isSupported()) {
    if (window.hls) { window.hls.destroy(); window.hls = null; }
    const hls = new Hls();
    hls.loadSource(url);
    hls.attachMedia(video);
    window.hls = hls;
    video.play().catch(()=>{});
  } else {
    video.src = url;
    video.play().catch(()=>{});
  }
}
</script>
</body>
</html>
