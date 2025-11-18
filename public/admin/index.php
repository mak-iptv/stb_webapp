<?php
// Admin single-page - uses the same API endpoints
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin - IPTV</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/milligram/dist/milligram.min.css">
<style>body{margin:20px}</style>
</head>
<body>
  <h2>Admin - IPTV (Demo)</h2>

  <section id="auth">
    <h4>Login</h4>
    <input id="username" placeholder="username">
    <input id="password" placeholder="password" type="password">
    <button id="loginBtn">Login</button>
    <button id="createAdminBtn">Create Admin (demo)</button>
  </section>

  <section id="manage" style="display:none">
    <h4>Channels</h4>
    <button id="refresh">Refresh</button>
    <table id="channelsTable"></table>

    <h4>Add Channel</h4>
    <input id="c_name" placeholder="Name">
    <input id="c_url" placeholder="Stream URL">
    <input id="c_cat" placeholder="Category">
    <button id="addChannel">Add</button>
    <button id="logout">Logout</button>
  </section>

<script>
async function api(path, opts={}) {
  const token = localStorage.getItem('iptv_jwt');
  opts.headers = opts.headers || {};
  if (token) opts.headers['Authorization'] = 'Bearer ' + token;
  const res = await fetch('/api/' + path, opts);
  if (res.status===403) throw new Error('Forbidden - check token/role');
  return await res.json();
}

document.getElementById('loginBtn').onclick = async ()=>{
  const u=document.getElementById('username').value, p=document.getElementById('password').value;
  const res = await fetch('/api/login.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({username:u,password:p})});
  const js = await res.json();
  if (js.token) { localStorage.setItem('iptv_jwt', js.token); alert('Logged in'); showManage(); loadChannels(); }
  else alert(JSON.stringify(js));
};

document.getElementById('createAdminBtn').onclick = async ()=>{
  // Create admin user (demo) - registers then updates role directly via a simple server endpoint? For demo we'll call register and instruct DB edit.
  const u = prompt('admin username?','admin');
  const p = prompt('admin password?','adminpass');
  if (!u||!p) return;
  const res = await fetch('/api/register.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({username:u,password:p})});
  const js = await res.json();
  alert(JSON.stringify(js) + '\nYou must update role to admin in DB for full rights (or run migration sample).');
};

function showManage(){ document.getElementById('auth').style.display='none'; document.getElementById('manage').style.display='block'; }

document.getElementById('addChannel').onclick = async ()=>{
  const name=document.getElementById('c_name').value, url=document.getElementById('c_url').value, cat=document.getElementById('c_cat').value;
  const res = await api('channels_admin.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({name:name,stream_url:url,category:cat})});
  alert(JSON.stringify(res));
  loadChannels();
};

document.getElementById('refresh').onclick = loadChannels;
document.getElementById('logout').onclick = ()=>{ localStorage.removeItem('iptv_jwt'); location.reload(); };

async function loadChannels(){
  try {
    const js = await api('channels_admin.php');
    const table = document.getElementById('channelsTable');
    table.innerHTML = '<tr><th>ID</th><th>Name</th><th>URL</th><th>Cat</th><th>Active</th></tr>';
    js.channels.forEach(c=>{
      const row = document.createElement('tr');
      row.innerHTML = `<td>${c.id}</td><td>${c.name}</td><td><small>${c.stream_url}</small></td><td>${c.category}</td><td>${c.is_active}</td>`;
      table.appendChild(row);
    });
  } catch(e) { alert('Load failed: ' + e.message); }
}
</script>
</body>
</html>
