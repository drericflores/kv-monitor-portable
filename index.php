<?php
$cfg = require __DIR__ . '/config.php';
$appName = $cfg['app_name'] ?? 'KV Monitor Enterprise';
$refresh = (int)($cfg['refresh_seconds'] ?? 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page">

    <header class="header">
        <div>
            <h1><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="subtitle">Live System Telemetry & Service Health</p>
        </div>
        <div id="updated" class="badge">Initializing...</div>
    </header>

    <section class="summary">
        <div class="card glow">
            <label>Host</label>
            <strong id="hostLabel">Host: --</strong>
        </div>

        <div class="card glow">
            <label>Uptime</label>
            <strong id="uptimeLabel">Uptime: --</strong>
        </div>

        <div class="card glow">
            <label>CPU Load</label>
            <strong id="cpuLabel">CPU Load: --</strong>
            <div class="bar"><div id="cpuBar"></div></div>
        </div>

        <div class="card glow">
            <label>Memory</label>
            <strong id="memLabel">Memory: --</strong>
            <div class="bar"><div id="memBar"></div></div>
        </div>

        <div class="card glow">
            <label>Disk</label>
            <strong id="diskLabel">Disk: --</strong>
            <div class="bar"><div id="diskBar"></div></div>
        </div>
    </section>

    <section class="summary mini-summary">
        <div class="card glow small-card">
            <label>Services Up</label>
            <strong id="upCount">--</strong>
        </div>
        <div class="card glow small-card">
            <label>Services Down</label>
            <strong id="downCount">--</strong>
        </div>
        <div class="card glow small-card">
            <label>Total Services</label>
            <strong id="totalCount">--</strong>
        </div>
    </section>

    <div id="groupsContainer"></div>

</div>

<script>
function pct(v){
    v = Number(v) || 0;
    return Math.max(0, Math.min(100, v));
}

function setBar(el, v){
    const p = pct(v);
    el.style.width = p + '%';
    el.classList.remove('warn', 'danger');
    if (p >= 85) el.classList.add('danger');
    else if (p >= 65) el.classList.add('warn');
}

function makeServiceCard(s){
    const statusClass = s.status === 'UP' ? 'up' : 'down';
    const latency = `${Number(s.latency_ms).toFixed(1)} ms`;
    const linkHtml = s.url
        ? `<a class="service-link" href="${s.url}" target="_blank" rel="noopener noreferrer">Open Service</a>`
        : `<span class="service-link disabled">No Link</span>`;

    return `
        <div class="card service ${statusClass}">
            <div class="icon">${s.icon}</div>
            <h3>${s.name}</h3>
            <p>${s.description}</p>

            <div class="service-meta">
                <div><span>Host</span><strong>${s.host}</strong></div>
                <div><span>Port</span><strong>${s.port}</strong></div>
                <div><span>Latency</span><strong>${latency}</strong></div>
                <div><span>Status</span><strong>${s.status}</strong></div>
            </div>

            <div class="service-actions">
                ${linkHtml}
            </div>

            <span class="status ${statusClass}">${s.status}</span>
        </div>
    `;
}

function makeGroupSection(groupName, services){
    return `
        <section class="group-section">
            <h2 class="section-title">${groupName}</h2>
            <div class="grid">
                ${services.map(makeServiceCard).join('')}
            </div>
        </section>
    `;
}

async function load(){
    const response = await fetch('api.php', { cache: 'no-store' });
    const data = await response.json();

    document.getElementById('hostLabel').textContent = `Host: ${data.hostname}`;
    document.getElementById('uptimeLabel').textContent = `Uptime: ${data.uptime}`;
    document.getElementById('cpuLabel').textContent = `CPU Load: ${data.cpu}`;
    document.getElementById('memLabel').textContent = `Memory: ${data.memory}%`;
    document.getElementById('diskLabel').textContent = `Disk: ${data.disk}%`;

    document.getElementById('upCount').textContent = data.summary.up;
    document.getElementById('downCount').textContent = data.summary.down;
    document.getElementById('totalCount').textContent = data.summary.total;

    setBar(document.getElementById('cpuBar'), Number(data.cpu) * 20);
    setBar(document.getElementById('memBar'), data.memory);
    setBar(document.getElementById('diskBar'), data.disk);

    document.getElementById('updated').textContent = `Updated: ${new Date().toLocaleTimeString()}`;

    const groupsContainer = document.getElementById('groupsContainer');
    groupsContainer.innerHTML = '';

    Object.entries(data.groups).forEach(([groupName, services]) => {
        groupsContainer.innerHTML += makeGroupSection(groupName, services);
    });
}

load();
setInterval(load, <?= $refresh ?> * 1000);
</script>

</body>
</html>
