// server.js
const express = require('express');
const fetch = require('node-fetch');
const cors = require('cors');

const app = express();
const PORT = 3000;

// Lejon aplikacionin tuaj Frontend (p.sh., http://localhost:8080 ose domeni juaj) 
// të bëjë kërkesa te ky server
app.use(cors()); 

// Middleware për të parandaluar ndonjë gabim të trupit të kërkesës
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Endpoint-i i ri për lidhjen me Portalet StB
app.get('/api/stb-login', async (req, res) => {
    // Marrja e parametrave nga kërkesa e frontend-it
    const { portalUrl, macAddress } = req.query;

    if (!portalUrl || !macAddress) {
        return res.status(400).json({ error: 'Portal URL ose MAC Address mungon.' });
    }

    // Sigurohuni që URL-ja të jetë e formatuar drejt, duke shtuar /c/
    const stbApiUrl = `${portalUrl.replace(/\/$/, "")}/c/index.html`; 

    try {
        console.log(`[PROXY] Duke kontaktuar: ${stbApiUrl} me MAC: ${macAddress}`);

        const response = await fetch(stbApiUrl, {
            method: 'GET',
            headers: {
                // Header-at e nevojshëm për të simuluar një Mag Box
                'User-Agent': 'Mozilla/5.0 (QtEmbedded; IGMP service; Linu)',
                'Cookie': `mac=${macAddress}`,
                'X-User-Agent': 'Model: MAG254; Link: WiFi', // Modeli standard i Mag
                'Referer': `${portalUrl.replace(/\/$/, "")}/c/`, 
                // Host-i duhet të jetë emri i hostit, jo porta
                'Host': new URL(portalUrl).hostname, 
            },
        });

        // Kontrollon nëse kërkesa në serverin IPTV dështoi (p.sh., 403, 404, 500)
        if (!response.ok) {
            console.error(`[PROXY] Gabim i serverit IPTV: ${response.status}`);
            return res.status(response.status).json({ 
                error: `Serveri IPTV ktheu gabim: ${response.status}. Kontrolloni URL-në ose MAC-un.`,
                status: response.status 
            });
        }

        // Kthehen të dhënat e papërpunuara (që zakonisht janë një dokument HTML/JS)
        // Frontend-i do të duhet të interpretojë këtë përgjigje për të gjetur listën e kanaleve.
        const rawData = await response.text();
        
        // Për momentin, ne po e kthejmë përgjigjen e suksesit bashkë me të dhënat e papërpunuara
        res.json({ 
            success: true, 
            message: 'Përgjigja u mor nga portali IPTV. Tani duhet të analizohet.', 
            rawData: rawData 
        });

    } catch (error) {
        console.error('[PROXY] Gabim fatal gjatë kërkesës:', error);
        res.status(500).json({ error: 'Gabim në lidhjen me serverin IPTV (Nuk mund të arrihet).', details: error.message });
    }
});

app.listen(PORT, () => {
    console.log(`Proxy serveri po dëgjon në http://localhost:${PORT}`);
});
