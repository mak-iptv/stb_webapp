// server.js
const express = require('express');
const fetch = require('node-fetch');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3000; 

app.use(cors()); 
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Rruga Bazë (Testi i Statusit)
app.get('/', (req, res) => {
    res.status(200).send('Proxy Serveri StB eshte aktiv dhe funksionon.');
});

// Endpoint-i Kryesor për Lidhjen me Portalin IPTV
app.get('/api/stb-login', async (req, res) => {
    const { portalUrl, macAddress } = req.query;

    if (!portalUrl || !macAddress) {
        return res.status(400).json({ error: 'Portal URL ose MAC Address mungon.' });
    }

    // 🛑 KORRIGJIMI: Shton protokollin nëse mungon për të shmangur gabimin 'Invalid URL'
    let correctedUrl = portalUrl.trim();
    if (!correctedUrl.startsWith('http://') && !correctedUrl.startsWith('https://')) {
        correctedUrl = 'http://' + correctedUrl;
    }

    // Përgatit URL-në e API-së së Portalit
    const stbApiUrl = `${correctedUrl.replace(/\/$/, "")}/c/index.html`; 

    try {
        const response = await fetch(stbApiUrl, {
            method: 'GET',
            headers: {
                'User-Agent': 'Mozilla/5.0 (QtEmbedded; IGMP service; Linu)',
                'Cookie': `mac=${macAddress}`,
                'X-User-Agent': 'Model: MAG254; Link: WiFi', 
                'Referer': `${correctedUrl.replace(/\/$/, "")}/c/`, 
                'Host': new URL(correctedUrl).hostname, 
            },
        });

        if (!response.ok) {
            console.error(`[PROXY] Gabim i serverit IPTV: ${response.status}`);
            return res.status(response.status).json({ 
                error: `Serveri IPTV ktheu gabim: ${response.status}. Kontrolloni URL-në ose MAC-un.`,
                status: response.status 
            });
        }

        const rawData = await response.text();
        
        res.json({ 
            success: true, 
            message: 'Përgjigja u mor nga portali IPTV. Duhet analizuar përmbajtja.', 
            rawData: rawData 
        });

    } catch (error) {
        console.error('[PROXY] Gabim fatal gjatë kërkesës:', error);
        res.status(500).json({ error: 'Gabim në lidhjen me serverin IPTV (Nuk mund të arrihet).', details: error.message });
    }
});

app.listen(PORT, () => {
    console.log(`Proxy serveri po dëgjon në portën ${PORT}`);
});
