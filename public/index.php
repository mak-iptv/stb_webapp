<?php
// Simple landing / player
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>IPTV Player - Complete Starter</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:20px}</style>
</head>
<body>
  <h1>IPTV Player - Complete Starter</h1>
  <p>Open <a href="/admin/">Admin panel</a> to manage channels (demo).</p>

  <div id="player">
    <video id="video" controls width="800" height="450"></video>
  </div>
  <div id="channels"></div>

<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script>
async function fetchChannels(token) {
  const res = await fetch('/api/channels.php', {
    headers: { 'Authorization': 'Bearer ' + token }
  });
  if (!res.ok) throw new Error('Unauthorized or network error');
  return await res.json();
}

const demoToken = localStorage.getItem('iptv_jwt') || '';

if (!demoToken) {
  // for demo: prompt for token (or use /api/login to fetch)
  const t = prompt('Enter demo JWT token (see README) or press Cancel to use demo-token (insecure demo)');
  if (t) localStorage.setItem('iptv_jwt', t);
  else localStorage.setItem('iptv_jwt', 'demo-token-123');
}

fetchChannels(localStorage.getItem('iptv_jwt'))
  .then(data=>{
    const list = document.getElementById('channels');
    data.channels.forEach(ch=>{
      const btn = document.createElement('button');
      btn.textContent = ch.name;
      btn.onclick = ()=>play(ch.stream_url + '?token=' + encodeURIComponent(localStorage.getItem('iptv_jwt')));
      list.appendChild(btn);
    });
  }).catch(err=>{ console.error(err); alert('Failed to fetch channels: ' + err.message); });

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
