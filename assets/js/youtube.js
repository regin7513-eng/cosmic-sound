/**
 * YouTube Music Stream Module
 * Standalone module for searching and streaming YouTube Music via InnerTube API
 * 
 * Usage:
 *   ytSearch('Bohemian Rhapsody Queen').then(results => console.log(results));
 *   ytFetchAudio({title: 'Bohemian Rhapsody', artist: 'Queen'}).then(url => audio.src = url);
 *   ytFetchStreamUrl('fJ9rUzIMcZQ').then(stream => { audio.src = stream.url; });
 */

const YT = (() => {
    const API_BASE = (location.pathname.includes('/cosmic-sound') ? '/cosmic-sound' : '') + '/api/youtube.php';

    async function search(query, limit = 5) {
        const sep = API_BASE.includes('?') ? '&' : '?';
        const url = `${API_BASE}${sep}action=search&q=${encodeURIComponent(query)}&limit=${limit}`;
        try {
            const res = await fetch(url);
            if (!res.ok) return [];
            const data = await res.json();
            return data.results || [];
        } catch (e) {
            console.warn('[YT] Search failed:', e);
            return [];
        }
    }

    async function getStreamUrl(videoId) {
        const sep = API_BASE.includes('?') ? '&' : '?';
        const url = `${API_BASE}${sep}action=stream&videoId=${encodeURIComponent(videoId)}`;
        try {
            const res = await fetch(url);
            if (!res.ok) return null;
            const data = await res.json();
            return data.stream || null;
        } catch (e) {
            console.warn('[YT] Stream fetch failed:', e);
            return null;
        }
    }

    async function getStreamRedirect(videoId) {
        const sep = API_BASE.includes('?') ? '&' : '?';
        return `${API_BASE}${sep}action=streamUrl&videoId=${encodeURIComponent(videoId)}`;
    }

    /**
     * Search YouTube and return best audio stream URL for a song.
     * @param {Object} song - {title, artist, duration?}
     * @returns {Promise<{url: string, mimeType: string, bitrate: number, videoId: string, title: string, artist: string} | null>}
     */
    async function fetchAudio(song) {
        const query = [song.artist, song.title].filter(Boolean).join(' ');
        if (!query.trim()) return null;

        const results = await search(query, 3);
        if (!results.length) return null;

        // Pick the first result (best match from YouTube search)
        const best = results[0];
        const stream = await getStreamUrl(best.videoId);
        if (!stream) return null;

        return {
            url: stream.url,
            mimeType: stream.mimeType,
            bitrate: stream.bitrate,
            videoId: best.videoId,
            title: best.title,
            artist: best.artist,
            duration: best.duration,
            source: 'youtube',
        };
    }

    /**
     * Build a playable audio source for a song.
     * Returns {source: 'youtube', url: streamUrl, mimeType, videoId, ...}
     * or null if not available.
     */
    async function resolveAudio(song) {
        try {
            return await fetchAudio(song);
        } catch (e) {
            console.warn('[YT] resolveAudio failed:', e);
            return null;
        }
    }

    return {
        search,
        getStreamUrl,
        getStreamRedirect,
        fetchAudio,
        resolveAudio,
    };
})();
