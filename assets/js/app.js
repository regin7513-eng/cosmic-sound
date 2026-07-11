const API_BASE = window.location.pathname.includes('/cosmic-sound')
    ? window.location.origin + '/cosmic-sound/api'
    : window.location.origin + '/api';

let currentSong = null;
let isPlaying = false;
let audio = new Audio();
let allSongs = [];
let sectionSongs = {};
let currentSection = 'home';
let currentIndex = 0;
let isShuffled = false;
let isRepeating = false;
let favoriteIds = new Set();
let userPlaylists = [];
let contextSong = null;
let urlCache = {};
let currentSongId = 0;
let allKnownSongs = [];
let currentPlaylistId = null;
let currentPlaylistName = '';

function accumulateKnown(songs) {
    if (!songs || !songs.length) return;
    var ids = new Set(allKnownSongs.map(function(s) { return s.id; }));
    songs.forEach(function(s) {
        if (!ids.has(s.id)) {
            allKnownSongs.push(s);
            ids.add(s.id);
        }
    });
}

function preloadUrls(songs) {
    songs.forEach(function(song, i) {
        if (song.track_url && !urlCache[song.track_url]) {
            setTimeout(function() {
                fetch(API_BASE + '/sankavollerei.php?action=download&url=' + encodeURIComponent(song.track_url))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.download_url) {
                            urlCache[song.track_url] = data.download_url;
                        }
                    }).catch(function() {});
            }, i * 200);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    restoreSidebarState();
    restoreNpState();
    const playerBar = document.getElementById('player-bar');
    if (playerBar) {
        initPlayer();
        initProgressDrag();
        loadFavoriteIds();
    }
    if (document.querySelector('.song-grid')) loadPlaylist('trending');

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('context-menu');
        if (menu && !menu.contains(e.target) && !e.target.closest('.song-card-menu') && !e.target.closest('.song-action-btn')) {
            menu.classList.remove('show');
        }
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !menuBtn?.contains(e.target) && !overlay?.contains(e.target)) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            var np = document.getElementById('now-playing-overlay');
            if (np && np.classList.contains('active')) {
                toggleNowPlaying();
                return;
            }
        }
        if (e.code === 'Space' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            togglePlay();
        }
    });

    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                modal.classList.remove('show');
            }
        });
    });
});

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;
    var opening = !sidebar.classList.contains('open');
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('show', sidebar.classList.contains('open'));
    if (isMobile() && opening) history.pushState({ sidebar: true }, '', '');
}

function mobileNav(section, btn) {
    document.querySelectorAll('.mobile-nav-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (section === 'search') {
        if (isMobile()) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            var searchSection = document.getElementById('section-search');
            if (searchSection) searchSection.classList.add('active');
            var searchInput = document.getElementById('mobile-search-input');
            if (searchInput) searchInput.focus();
        } else {
            document.getElementById('search-input')?.focus();
        }
        return;
    }
    if (section === 'playlists') section = 'playlists';
    var navLink = document.querySelector('.nav-link[data-section="' + section + '"]');
    if (navLink) {
        showSection(section, navLink);
    }
}

function toggleSidebarSection(titleEl) {
    titleEl.closest('.sidebar-section').classList.toggle('collapsed');
}

function toggleSidebarCollapse() {
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    const dash = document.querySelector('.dashboard');
    const playerBar = document.getElementById('player-bar');
    const lyricsPanel = document.getElementById('lyrics-panel');
    if (!sidebar || !dash) return;
    sidebar.classList.toggle('collapsed');
    dash.classList.toggle('sidebar-collapsed');
    const left = sidebar.classList.contains('collapsed') ? '64px' : 'var(--sidebar-width)';
    if (playerBar) playerBar.style.left = left;
    if (lyricsPanel) lyricsPanel.style.left = left;
    localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
}

function restoreSidebarState() {
    if (localStorage.getItem('sidebar_collapsed') === '1') {
        const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
        const dash = document.querySelector('.dashboard');
        const playerBar = document.getElementById('player-bar');
        const lyricsPanel = document.getElementById('lyrics-panel');
        if (sidebar) sidebar.classList.add('collapsed');
        if (dash) dash.classList.add('sidebar-collapsed');
        if (playerBar) playerBar.style.left = '64px';
        if (lyricsPanel) lyricsPanel.style.left = '64px';
    }
}

function initPlayer() {
    document.getElementById('play-btn')?.addEventListener('click', togglePlay);
    document.getElementById('prev-btn')?.addEventListener('click', playPrevious);
    document.getElementById('next-btn')?.addEventListener('click', playNext);
    document.getElementById('shuffle-btn')?.addEventListener('click', toggleShuffle);
    document.getElementById('repeat-btn')?.addEventListener('click', toggleRepeat);
    document.getElementById('volume-slider')?.addEventListener('input', changeVolume);
    document.getElementById('volume-btn')?.addEventListener('click', toggleMute);

    audio.volume = 0.7;
    audio.preload = 'auto';
    audio.setAttribute('playsinline', '');
    document.getElementById('volume-slider').style.setProperty('--vol-pct', '70%');
    audio.addEventListener('timeupdate', () => { updateProgress(); updateLyricsHighlight(); updateNpLyricsHighlight(); updateMediaSessionPosition(); });
    audio.addEventListener('loadedmetadata', updateDuration);
    audio.addEventListener('play', () => { isPlaying = true; updatePlayButton(); refreshGrid(); updateMediaSessionState(); });
    audio.addEventListener('pause', () => { isPlaying = false; updatePlayButton(); refreshGrid(); updateMediaSessionState(); });
    audio.addEventListener('ended', handleSongEnd);
    audio.addEventListener('error', () => showToast('Unable to play this track'));

    if ('mediaSession' in navigator) {
        navigator.mediaSession.setActionHandler('play', function() { audio.play().catch(function(){}); });
        navigator.mediaSession.setActionHandler('pause', function() { audio.pause(); });
        navigator.mediaSession.setActionHandler('previoustrack', playPrevious);
        navigator.mediaSession.setActionHandler('nexttrack', playNext);
        navigator.mediaSession.setActionHandler('seekto', function(e) {
            if (e.seekTime != null) audio.currentTime = e.seekTime;
        });
        navigator.mediaSession.setActionHandler('seekbackward', function(e) {
            audio.currentTime = Math.max(0, audio.currentTime - (e.seekOffset || 10));
        });
        navigator.mediaSession.setActionHandler('seekforward', function(e) {
            audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + (e.seekOffset || 10));
        });
    }
}

async function loadFavoriteIds() {
    try {
        const res = await fetch(API_BASE + '/favorites.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (data.success) {
            favoriteIds = new Set(data.data.map(f => f.track_id));
        }
    } catch {}
}

function isFavorited(songId) {
    return favoriteIds.has(songId);
}

async function toggleFavorite(song) {
    const wasFav = isFavorited(song.id);

    if (wasFav) {
        favoriteIds.delete(song.id);
        try {
            await fetch(API_BASE + '/favorites.php?track_id=' + encodeURIComponent(song.id), {
                method: 'DELETE', credentials: 'same-origin'
            });
        } catch {}
        showToast('Removed from Favorites');
    } else {
        favoriteIds.add(song.id);
        try {
            await fetch(API_BASE + '/favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    track_id: song.id, title: song.title, artist: song.artist,
                    album: song.album, cover_image: song.cover_image,
                    track_url: song.track_url, file_path: song.file_path,
                    duration_text: song.duration_text
                })
            });
        } catch {}
        showToast('Added to Favorites');
    }

    document.querySelectorAll('.song-card').forEach(card => {
        var s = getSongFromCard(card);
        if (!s || s.id !== song.id) return;
        var b = card.querySelector('.fav-btn');
        if (!b) return;
        if (isFavorited(s.id)) {
            b.classList.add('favorited');
            b.innerHTML = svgHeartFilled();
        } else {
            b.classList.remove('favorited');
            b.innerHTML = svgHeart();
        }
    });

    if (document.getElementById('section-favorites')?.classList.contains('active')) {
        loadFavorites();
    }

    var npFavBtn = document.getElementById('np-fav-btn');
    if (npFavBtn && currentSong && currentSong.id === song.id) {
        npFavBtn.classList.toggle('favorited', isFavorited(song.id));
        npFavBtn.innerHTML = isFavorited(song.id)
            ? '<svg width="24" height="24" viewBox="0 0 24 24" fill="#00ff88" stroke="#00ff88" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>'
            : '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
    }
}

function showContextMenu(e, song) {
    e.preventDefault();
    e.stopPropagation();
    contextSong = song;
    const menu = document.getElementById('context-menu');
    const favText = document.getElementById('ctx-fav-text');
    favText.textContent = isFavorited(song.id) ? 'Remove from Favorites' : 'Add to Favorites';
    var plBtn = document.getElementById('ctx-add-playlist');
    if (currentPlaylistId) {
        plBtn.querySelector('span') || (plBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg><span>Remove from Playlist</span>');
        var sp = plBtn.querySelector('span');
        if (sp) sp.textContent = 'Remove from Playlist';
    } else {
        var sp = plBtn.querySelector('span');
        if (sp) sp.textContent = 'Add to Playlist';
        plBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg><span>Add to Playlist</span>';
    }
    menu.style.left = Math.min(e.clientX, window.innerWidth - 220) + 'px';
    menu.style.top = Math.min(e.clientY, window.innerHeight - 120) + 'px';
    menu.classList.add('show');
}

document.getElementById('ctx-add-fav')?.addEventListener('click', async () => {
    if (!contextSong) return;
    document.getElementById('context-menu').classList.remove('show');
    await toggleFavorite(contextSong);
});

document.getElementById('ctx-add-playlist')?.addEventListener('click', () => {
    if (!contextSong) return;
    document.getElementById('context-menu').classList.remove('show');
    if (currentPlaylistId) {
        removeFromPlaylist(contextSong.id);
    } else {
        openPlaylistModal();
    }
});

async function openPlaylistModal() {
    const modal = document.getElementById('playlist-modal');
    const list = document.getElementById('playlist-modal-list');
    modal.classList.add('show');
    list.innerHTML = '<div style="text-align:center;padding:2rem;"><div class="loading-spinner"></div></div>';

    try {
        const res = await fetch(API_BASE + '/playlists.php', { credentials: 'same-origin' });
        const data = await res.json();
        userPlaylists = data.success ? data.data : [];
    } catch {
        userPlaylists = [];
    }

    if (userPlaylists.length === 0) {
        list.innerHTML = '<p style="text-align:center;color:var(--text-secondary);padding:1.5rem;">No playlists yet. Create one below.</p>';
        return;
    }

    list.innerHTML = '<ul class="playlist-list">' + userPlaylists.map(function(p) {
        return '<li class="playlist-list-item" onclick="addToPlaylist(\'' + p.id + '\', \'' + esc(p.name).replace(/'/g, "\\'") + '\')">' +
            '<div class="playlist-list-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div>' +
            '<div class="playlist-list-info"><h4>' + esc(p.name) + '</h4><p>' + esc(p.description || 'Playlist') + '</p></div>' +
            '</li>';
    }).join('') + '</ul>';
}

function openNpPlaylistModal() {
    if (!currentSong) return;
    contextSong = currentSong;
    openPlaylistModal();
}

function closePlaylistModal() {
    document.getElementById('playlist-modal')?.classList.remove('show');
}

async function addToPlaylist(playlistId, playlistName) {
    if (!contextSong) return;
    closePlaylistModal();

    try {
        const res = await fetch(API_BASE + '/playlist_tracks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                playlist_id: playlistId,
                track_id: contextSong.id,
                title: contextSong.title,
                artist: contextSong.artist,
                album: contextSong.album,
                cover_image: contextSong.cover_image,
                track_url: contextSong.track_url,
                file_path: contextSong.file_path,
                duration_text: contextSong.duration_text
            })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Added to "' + playlistName + '"');
            if (currentPlaylistId === playlistId && typeof loadUserPlaylistTracks === 'function') {
                loadUserPlaylistTracks(playlistId, playlistName);
            }
        } else {
            showToast(data.message || 'Failed');
        }
    } catch {
        showToast('Failed to add to playlist');
    }
}

function showCreatePlaylistModal() {
    document.getElementById('playlist-modal')?.classList.remove('show');
    document.getElementById('create-playlist-modal')?.classList.add('show');
    setTimeout(() => document.getElementById('new-playlist-name')?.focus(), 100);
}

function closeCreatePlaylistModal() {
    document.getElementById('create-playlist-modal')?.classList.remove('show');
    document.getElementById('new-playlist-name').value = '';
    document.getElementById('new-playlist-desc').value = '';
}

async function createPlaylist(e) {
    e.preventDefault();
    const name = document.getElementById('new-playlist-name').value.trim();
    const desc = document.getElementById('new-playlist-desc').value.trim();
    if (!name) return;

    try {
        const res = await fetch(API_BASE + '/playlists.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ name: name, description: desc })
        });
        const data = await res.json();
        if (data.success && data.data) {
            closeCreatePlaylistModal();
            showToast('Playlist "' + name + '" created');
            if (contextSong) {
                await addToPlaylist(data.data.id, name);
            }
            if (document.getElementById('section-playlists')?.classList.contains('active')) {
                loadUserPlaylists();
            }
        } else {
            showToast(data.message || 'Failed to create playlist');
        }
    } catch {
        showToast('Failed to create playlist');
    }
}

async function loadPlaylist(id) {
    const grid = document.querySelector('#section-home .song-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading...</h3></div>';

    const playlists = {
        'trending': { name: 'Trending', q: 'top hits 2025' },
        'chill': { name: 'Chill', q: 'lofi chill beats relaxing' },
        'energy': { name: 'Energy', q: 'workout motivation pump up' },
        'romance': { name: 'Romance', q: 'love songs romantic' },
        'focus': { name: 'Focus', q: 'study music instrumental concentration' },
        'party': { name: 'Party', q: 'party dance hits club' },
        'sad': { name: 'Sad', q: 'sad emotional heartbreak' },
        'indie': { name: 'Indie', q: 'indie alternative chill' },
        'hiphop': { name: 'Hip Hop', q: 'hip hop rap best' },
        'karaoke': { name: 'Karaoke', q: 'karaoke sing along popular' }
    };

    const pl = playlists[id] || playlists['trending'];

    try {
        const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=' + encodeURIComponent(pl.q) + '&limit=18');
        const data = await res.json();

        if (!data.success || !data.data || data.data.length === 0) {
            grid.innerHTML = emptyState('Nothing here yet', 'Try another playlist');
            return;
        }

        allSongs = data.data;
        accumulateKnown(allSongs);
        document.getElementById('playlist-title').textContent = pl.name;
        renderGrid(grid, allSongs);
        preloadUrls(allSongs);
        if (isMobile()) loadMobileExtras();
    } catch (e) {
        grid.innerHTML = emptyState('Couldn\'t load playlist', esc(e.message));
    }
}

async function searchMusic(query) {
    if (!query.trim()) return;

    var homeBtn = document.querySelector('.nav-link[data-section="home"]');
    if (homeBtn) homeBtn.click();

    const grid = document.querySelector('#section-home .song-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Searching...</h3></div>';

    try {
        const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=' + encodeURIComponent(query) + '&limit=18');
        const data = await res.json();

        if (!data.success || !data.data || data.data.length === 0) {
            grid.innerHTML = emptyState('No results', 'Try a different search term');
            return;
        }

        allSongs = data.data;
        accumulateKnown(allSongs);
        document.getElementById('playlist-title').textContent = 'Search: "' + esc(query) + '"';
        renderGrid(grid, allSongs);
        preloadUrls(allSongs);
        refreshArtistsAlbums();
    } catch (e) {
        grid.innerHTML = emptyState('Search failed', esc(e.message));
    }
}

let recentSongs = [];

function addToRecent(song) {
    recentSongs = recentSongs.filter(s => s.id !== song.id);
    recentSongs.unshift(song);
    if (recentSongs.length > 20) recentSongs = recentSongs.slice(0, 20);
    try { localStorage.setItem('recentSongs', JSON.stringify(recentSongs)); } catch {}
}

function loadRecentFromStorage() {
    try {
        var stored = localStorage.getItem('recentSongs');
        if (stored) recentSongs = JSON.parse(stored);
    } catch {}
}

async function loadRecent() {
    const grid = document.getElementById('recent-grid');
    if (!grid) return;
    loadRecentFromStorage();
    accumulateKnown(recentSongs);
    if (recentSongs.length > 0) {
        allSongs = recentSongs;
        renderGrid(grid, recentSongs);
    } else {
        grid.innerHTML = emptyState('No recently played songs', 'Songs you play will appear here');
    }
}

function loadMobileExtras() {
    if (!isMobile()) return;

    var recentGrid = document.getElementById('mobile-recent-grid');
    if (recentGrid) {
        loadRecentFromStorage();
        if (recentSongs.length > 0) {
            renderGrid(recentGrid, recentSongs);
        } else {
            recentGrid.innerHTML = '<div class="empty-state" style="padding:1rem 0"><p style="color:var(--text-muted);font-size:0.8rem">No recently played songs yet</p></div>';
        }
    }

    var artistsGrid = document.getElementById('mobile-artists-grid');
    if (artistsGrid) {
        var artists = renderArtistCards(allKnownSongs);
        if (artists.length > 0) {
            artistsGrid.innerHTML = artists.slice(0, 6).map(artistCardHTML).join('');
        } else {
            artistsGrid.innerHTML = '<div class="empty-state" style="padding:1rem 0"><p style="color:var(--text-muted);font-size:0.8rem">No artists yet</p></div>';
        }
    }

    var albumsGrid = document.getElementById('mobile-albums-grid');
    if (albumsGrid) {
        var albums = renderAlbumCards(allKnownSongs);
        if (albums.length > 0) {
            albumsGrid.innerHTML = albums.slice(0, 6).map(albumCardHTML).join('');
        } else {
            albumsGrid.innerHTML = '<div class="empty-state" style="padding:1rem 0"><p style="color:var(--text-muted);font-size:0.8rem">No albums yet</p></div>';
        }
    }
}

function getActiveGrid() {
    var active = document.querySelector('.section.active .song-grid');
    return active;
}

function renderArtistCards(songs) {
    var artistMap = {};
    songs.forEach(function(song) {
        if (song.artist && !artistMap[song.artist]) {
            artistMap[song.artist] = song;
        }
    });
    return Object.values(artistMap);
}

function renderAlbumCards(songs) {
    var albumMap = {};
    songs.forEach(function(song) {
        if (song.album && !albumMap[song.album]) {
            albumMap[song.album] = song;
        }
    });
    return Object.values(albumMap);
}

function artistCardHTML(song) {
    return '<div class="artist-card" onclick="searchMusic(\'' + esc(song.artist).replace(/'/g, "\\'") + '\')">' +
        '<div class="artist-avatar">' +
            '<img src="' + esc(song.cover_image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
        '</div>' +
        '<h4 class="artist-name">' + esc(song.artist) + '</h4>' +
        '<p class="artist-label">Artist</p>' +
    '</div>';
}

function albumCardHTML(song) {
    return '<div class="song-card" onclick="searchMusic(\'' + esc(song.album).replace(/'/g, "\\'") + '\')">' +
        '<div class="song-card-cover">' +
            '<img src="' + esc(song.cover_image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
            '<div class="play-overlay"><div class="play-btn-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg></div></div>' +
        '</div>' +
        '<h4 class="song-card-title">' + esc(song.album) + '</h4>' +
        '<p class="song-card-artist">' + esc(song.artist) + '</p>' +
    '</div>';
}

function refreshArtistsAlbums() {
    if (document.getElementById('section-artists')?.classList.contains('active')) loadArtists();
    if (document.getElementById('section-albums')?.classList.contains('active')) loadAlbums();
}

async function loadArtists() {
    const grid = document.getElementById('artists-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading artists...</h3></div>';

    var artists = renderArtistCards(allKnownSongs);

    if (artists.length < 6) {
        try {
            const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=popular+artists+2025&limit=18');
            const data = await res.json();
            if (data.success && data.data) {
                accumulateKnown(data.data);
                artists = renderArtistCards(allKnownSongs);
            }
        } catch {}
    }

    if (artists.length === 0) {
        grid.innerHTML = emptyState('No artists found', 'Play some songs first');
        return;
    }

    grid.innerHTML = artists.map(artistCardHTML).join('');
}

async function loadAlbums() {
    const grid = document.getElementById('albums-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading albums...</h3></div>';

    var albums = renderAlbumCards(allKnownSongs);

    if (albums.length < 6) {
        try {
            const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=best+albums+2025&limit=18');
            const data = await res.json();
            if (data.success && data.data) {
                accumulateKnown(data.data);
                albums = renderAlbumCards(allKnownSongs);
            }
        } catch {}
    }

    if (albums.length === 0) {
        grid.innerHTML = emptyState('No albums found', 'Play some songs first');
        return;
    }

    grid.innerHTML = albums.map(albumCardHTML).join('');
}

function emptyState(title, subtitle) {
    return '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>' + title + '</h3><p>' + subtitle + '</p></div>';
}

function renderGrid(grid, songList) {
    if (!grid || !songList) return;
    grid._songs = songList;
    grid._renderType = 'grid';
    if (songList.length === 0) {
        grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>No songs found</h3></div>';
        return;
    }
    var gridId = grid.id || 'home';
    grid.innerHTML = songList.map(function(song, i) {
        var isActive = currentSong && currentSong.id === song.id;
        var isFav = isFavorited(song.id);
        var playIcon = (isActive && isPlaying)
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'
            : '<svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg>';
        var cardClick = isActive ? 'togglePlay()' : 'playSongFromGrid(this, ' + i + ')';
        return '<div class="song-card' + (isActive ? ' active' : '') + '" data-index="' + i + '" data-grid="' + gridId + '" onclick="' + cardClick + '" oncontextmenu="showContextMenuFromGrid(event, this, ' + i + ')">' +
            '<div class="song-card-cover">' +
                '<img src="' + esc(song.cover_image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
                (isActive && isPlaying ? '<div class="now-playing-bars" style="position:absolute;bottom:8px;left:8px;z-index:3;"><span></span><span></span><span></span></div>' : '') +
                '<div class="play-overlay"><div class="play-btn-circle">' + playIcon + '</div></div>' +
                '<div class="song-card-actions">' +
                    '<button class="song-action-btn fav-btn' + (isFav ? ' favorited' : '') + '" onclick="event.stopPropagation(); toggleFavoriteFromCard(this.closest(\'.song-card\'), ' + i + ')" title="Favorite">' +
                        (isFav ? svgHeartFilled() : svgHeart()) +
                    '</button>' +
                    '<button class="song-action-btn" onclick="event.stopPropagation(); showContextMenuFromGrid(event, this.closest(\'.song-card\'), ' + i + ')" title="More">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<h4 class="song-card-title">' + esc(song.title) + '</h4>' +
            '<p class="song-card-artist">' + esc(song.artist) + '</p>' +
        '</div>';
    }).join('');
}

function renderTrackList(grid, songList) {
    if (!grid || !songList) return;
    grid._songs = songList;
    grid._renderType = 'tracklist';
    if (songList.length === 0) {
        grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>No songs found</h3></div>';
        return;
    }
    var gridId = grid.id || 'tracklist';
    var html = '<div class="track-list">';
    html += '<div class="track-list-header"><span>#</span><span>Title</span><span>Album</span><span>Duration</span></div>';
    songList.forEach(function(song, i) {
        var isActive = currentSong && currentSong.id === song.id;
        var isFav = isFavorited(song.id);
        var clickAction = isActive ? 'togglePlay()' : 'playSongFromGrid(this, ' + i + ')';
        html += '<div class="track-item' + (isActive ? ' active' : '') + '" data-index="' + i + '" data-grid="' + gridId + '" onclick="' + clickAction + '" oncontextmenu="showContextMenuFromGrid(event, this, ' + i + ')">';
        html += '<div class="track-num">';
        if (isActive && isPlaying) {
            html += '<div class="now-playing-bars"><span></span><span></span><span></span></div>';
        } else {
            html += '<span class="track-num-text">' + (i + 1) + '</span>';
            html += '<span class="track-num-play"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>';
        }
        html += '</div>';
        html += '<div class="track-info">';
        html += '<img class="track-cover" src="' + esc(song.cover_image) + '" alt="" loading="lazy" onerror="this.src=\'data:image/svg+xml,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;%23555&quot;><path d=&quot;M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z&quot;/></svg>\'">';
        html += '<div class="track-text">';
        html += '<div class="track-title">' + esc(song.title) + '</div>';
        html += '<div class="track-artist">' + esc(song.artist) + '</div>';
        html += '</div>';
        html += '</div>';
        html += '<div class="track-album">' + esc(song.album || '') + '</div>';
        html += '<div class="track-duration">' + esc(song.duration_text || '') + '</div>';
        html += '</div>';
    });
    html += '</div>';
    grid.innerHTML = html;
}

function getSongFromCard(cardEl) {
    var gridEl = cardEl.closest('.song-grid');
    var idx = parseInt(cardEl.dataset.index);
    if (gridEl && gridEl._songs && gridEl._songs[idx]) return gridEl._songs[idx];
    return allSongs[idx] || null;
}

function playSongFromGrid(cardEl, index) {
    var gridEl = cardEl.closest('.song-grid');
    var songs = (gridEl && gridEl._songs) || allSongs;
    if (index < 0 || index >= songs.length) return;
    currentSection = gridEl?.id || 'home';
    sectionSongs[currentSection] = songs;
    allSongs = songs;
    currentIndex = index;
    currentSong = songs[index];
    playSongDirect();
}

function showContextMenuFromGrid(e, cardEl, index) {
    var song = getSongFromCard(cardEl);
    if (song) showContextMenu(e, song);
}

function toggleFavoriteFromCard(cardEl, index) {
    var song = getSongFromCard(cardEl);
    if (song) toggleFavorite(song);
}

function playSongDirect() {
    if (!currentSong || !currentSong.track_url) {
        showToast('This track is not available');
        return;
    }

    audio.pause();

    currentSongId++;
    var myId = currentSongId;

    updatePlayerUI();
    updatePlayButton();
    if (!isMobile()) refreshGrid();

    if (lyricsVisible && currentSong) {
        loadLyrics(currentSong);
    }

    var cachedUrl = urlCache[currentSong.track_url];
    if (cachedUrl) {
        playWithUrl(cachedUrl, myId);
    } else {
        fetchDownloadUrl(currentSong.track_url).then(function(downloadUrl) {
            if (myId !== currentSongId) return;
            if (downloadUrl) {
                urlCache[currentSong.track_url] = downloadUrl;
                playWithUrl(downloadUrl, myId);
            } else {
                showToast('Unable to load track');
            }
        }).catch(function() {
            if (myId === currentSongId) showToast('Playback error');
        });
    }
}

function fetchDownloadUrl(trackUrl) {
    return fetch(API_BASE + '/sankavollerei.php?action=download&url=' + encodeURIComponent(trackUrl))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            return (data.success && data.download_url) ? data.download_url : null;
        });
}

function playWithUrl(mp3Url, myId) {
    if (myId !== currentSongId) return;
    updateMediaSessionMetadata();
    audio.src = mp3Url;
    audio.load();
    audio.play().then(function() {
        if (myId === currentSongId) {
            addToRecent(currentSong);
            updateMediaSessionState();
            startBgKeepAlive();
        }
    }).catch(function() {
        if (myId === currentSongId) showToast('Unable to play this track');
    });
}

async function playSong(index) {
    if (index < 0 || index >= allSongs.length) return;
    currentIndex = index;
    currentSong = allSongs[index];
    playSongDirect();
}

function togglePlay() {
    if (!currentSong && allSongs.length > 0) { playSong(0); return; }
    if (!currentSong) return;
    if (isPlaying) {
        audio.pause();
    } else {
        audio.play().catch(function() {});
    }
    updatePlayButton();
    refreshGrid();
}

function playPrevious() {
    if (allSongs.length === 0) return;
    if (audio.currentTime > 3) { audio.currentTime = 0; return; }
    currentIndex = (currentIndex - 1 + allSongs.length) % allSongs.length;
    currentSong = allSongs[currentIndex];
    playSongDirect();
}

function playNext() {
    if (allSongs.length === 0) return;
    if (isShuffled) {
        var n;
        do { n = Math.floor(Math.random() * allSongs.length); } while (n === currentIndex && allSongs.length > 1);
        currentIndex = n;
        currentSong = allSongs[n];
        playSongDirect();
    } else {
        currentIndex = (currentIndex + 1) % allSongs.length;
        currentSong = allSongs[currentIndex];
        playSongDirect();
    }
}

function handleSongEnd() {
    if (isRepeating) { audio.currentTime = 0; audio.play(); }
    else playNext();
}

function updateMediaSessionState() {
    if (!('mediaSession' in navigator)) return;
    navigator.mediaSession.playbackState = isPlaying ? 'playing' : 'paused';
    updateMediaSessionPosition();
}

function updateMediaSessionPosition() {
    if (!('mediaSession' in navigator) || !audio || isNaN(audio.duration) || !isFinite(audio.duration)) return;
    var dur = Math.max(audio.duration, 0);
    var pos = Math.max(0, Math.min(audio.currentTime || 0, dur));
    try {
        navigator.mediaSession.setPositionState({
            duration: dur,
            playbackRate: audio.playbackRate || 1,
            position: pos
        });
    } catch(e) {}
}

function artworkToBlob(src, callback) {
    try {
        var img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            try {
                var c = document.createElement('canvas');
                c.width = 512; c.height = 512;
                c.getContext('2d').drawImage(img, 0, 0, 512, 512);
                c.toBlob(function(blob) {
                    callback(blob ? URL.createObjectURL(blob) : src);
                }, 'image/jpeg', 0.8);
            } catch(e) { callback(src); }
        };
        img.onerror = function() { callback(src); };
        img.src = src;
    } catch(e) { callback(src); }
}

function updateMediaSessionMetadata() {
    if (!('mediaSession' in navigator) || !currentSong) return;
    var artUrl = currentSong.cover_image || '';
    if (!artUrl) {
        navigator.mediaSession.metadata = new MediaMetadata({
            title: currentSong.title || 'Unknown',
            artist: currentSong.artist || 'Unknown',
            album: 'Ginz Song'
        });
        return;
    }
    var proxySrc = API_BASE + '/artwork.php?url=' + encodeURIComponent(artUrl);
    artworkToBlob(proxySrc, function(blobUrl) {
        if (!currentSong) return;
        navigator.mediaSession.metadata = new MediaMetadata({
            title: currentSong.title || 'Unknown',
            artist: currentSong.artist || 'Unknown',
            album: 'Ginz Song',
            artwork: [
                { src: blobUrl, sizes: '96x96', type: 'image/jpeg' },
                { src: blobUrl, sizes: '192x192', type: 'image/jpeg' },
                { src: blobUrl, sizes: '512x512', type: 'image/jpeg' }
            ]
        });
    });
}

document.addEventListener('visibilitychange', function() {
    if (isPlaying && audio && !audio.paused) {
        updateMediaSessionState();
        updateMediaSessionPosition();
    }
});

var bgKeepAliveTimer;
function startBgKeepAlive() {
    clearInterval(bgKeepAliveTimer);
    bgKeepAliveTimer = setInterval(function() {
        if (isPlaying && audio && !audio.paused) {
            updateMediaSessionState();
        }
    }, 25000);
}

function seek(e) {
    if (!audio.duration || isNaN(audio.duration)) return;
    var track = e.target.closest('.progress-bar') || e.target.closest('.player-progress');
    if (!track) return;
    var bar = track.querySelector('.progress-bar') || track;
    var rect = bar.getBoundingClientRect();
    var pos = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    audio.currentTime = pos * audio.duration;
}

function initProgressDrag() {
    var dragging = false;

    function getBar(e) {
        return (e.target.closest('.progress-bar') || e.target.closest('.player-progress') || e.target.closest('.np-progress'));
    }

    function seekFromEvent(e) {
        if (!audio.duration) return;
        var container = getBar(e);
        if (!container) return;
        var bar = container.querySelector('.progress-bar') || container;
        var rect = bar.getBoundingClientRect();
        var x = e.touches ? e.touches[0].clientX : e.clientX;
        var pos = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
        audio.currentTime = pos * audio.duration;
    }

    document.addEventListener('mousedown', function(e) {
        if (!getBar(e)) return;
        dragging = true;
        seekFromEvent(e);
        e.preventDefault();
    });

    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        seekFromEvent(e);
    });

    document.addEventListener('mouseup', function() {
        dragging = false;
    });

    document.addEventListener('touchstart', function(e) {
        if (!getBar(e)) return;
        dragging = true;
        seekFromEvent(e);
    }, { passive: true });

    document.addEventListener('touchmove', function(e) {
        if (!dragging) return;
        seekFromEvent(e);
    }, { passive: true });

    document.addEventListener('touchend', function() {
        dragging = false;
    });
}

function changeVolume(e) {
    audio.volume = e.target.value / 100;
    e.target.style.setProperty('--vol-pct', e.target.value + '%');
    updateVolumeIcon();
}

function toggleMute() {
    if (audio.volume > 0) {
        audio.dataset.prevVol = audio.volume;
        audio.volume = 0;
        document.getElementById('volume-slider').value = 0;
    } else {
        audio.volume = audio.dataset.prevVol || 0.7;
        document.getElementById('volume-slider').value = (audio.dataset.prevVol || 0.7) * 100;
    }
    updateVolumeIcon();
}

function updateVolumeIcon() {
    var btn = document.getElementById('volume-btn');
    if (!btn) return;
    var vol = audio.volume;
    var path;
    if (vol === 0) path = '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/>';
    else if (vol < 0.5) path = '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>';
    else path = '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/>';
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + path + '</svg>';
}

function toggleShuffle() {
    isShuffled = !isShuffled;
    document.getElementById('shuffle-btn')?.classList.toggle('active', isShuffled);
    showToast(isShuffled ? 'Shuffle On' : 'Shuffle Off');
}

function toggleRepeat() {
    isRepeating = !isRepeating;
    document.getElementById('repeat-btn')?.classList.toggle('active', isRepeating);
    showToast(isRepeating ? 'Repeat On' : 'Repeat Off');
}

function updateProgress() {
    if (!audio.duration) return;
    var pct = (audio.currentTime / audio.duration) * 100;
    var fill = document.querySelector('.progress-bar-fill');
    var npFill = document.getElementById('np-progress-fill');
    var curEl = document.getElementById('current-time');
    var npCur = document.getElementById('np-current-time');
    if (fill) fill.style.width = pct + '%';
    if (npFill) npFill.style.width = pct + '%';
    if (curEl) curEl.textContent = fmt(audio.currentTime);
    if (npCur) npCur.textContent = fmt(audio.currentTime);
}

function updateDuration() {
    var durEl = document.getElementById('duration');
    var npDur = document.getElementById('np-duration');
    if (durEl && audio.duration) durEl.textContent = fmt(audio.duration);
    if (npDur && audio.duration) npDur.textContent = fmt(audio.duration);
}

function updatePlayerUI() {
    document.querySelector('.player-bar')?.classList.add('active');
    document.getElementById('player-cover-img').src = currentSong.cover_image || '';
    document.getElementById('player-title').textContent = currentSong.title;
    document.getElementById('player-artist').textContent = currentSong.artist;
    document.title = currentSong.title + ' - ' + currentSong.artist + ' | Ginz Song';
    updateNowPlaying();
}

function updatePlayButton() {
    var btn = document.getElementById('play-btn');
    if (!btn) return;
    btn.innerHTML = isPlaying
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="#000"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg>';
    var npIcon = document.getElementById('np-play-icon');
    if (npIcon) npIcon.innerHTML = isPlaying
        ? '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>'
        : '<path d="M8 5v14l11-7z"/>';
}

function refreshGrid() {
    if (isMobile()) {
        clearTimeout(refreshGridTimer);
        refreshGridTimer = setTimeout(refreshGridNow, 150);
    } else {
        refreshGridNow();
    }
}

var refreshGridTimer;
function refreshGridNow() {
    document.querySelectorAll('.song-grid').forEach(function(grid) {
        if (grid.id === 'user-playlists-grid') return;
        if (grid._songs && grid._songs.length > 0) {
            if (grid._renderType === 'tracklist') {
                renderTrackList(grid, grid._songs);
            } else {
                renderGrid(grid, grid._songs);
            }
        }
    });
}

function fmt(s) {
    if (isNaN(s)) return '0:00';
    return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
}

function esc(str) {
    if (!str) return '';
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function svgHeart() {
    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
}

function svgHeartFilled() {
    return '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
}

var searchTimeout;
function onSearchInput(q) {
    clearTimeout(searchTimeout);
    if (!q.trim()) {
        var activeTab = document.querySelector('.tab-btn.active');
        if (activeTab) {
            var match = activeTab.getAttribute('onclick');
            if (match) {
                var idMatch = match.match(/'([^']+)'/);
                if (idMatch) loadPlaylist(idMatch[1]);
            }
        }
        return;
    }
    searchTimeout = setTimeout(function() { searchMusic(q); }, 600);
}

var mobileSearchTimeout;
function onMobileSearchInput(q) {
    clearTimeout(mobileSearchTimeout);
    if (!q.trim()) {
        var grid = document.getElementById('mobile-search-grid');
        if (grid) grid.innerHTML = '';
        document.getElementById('mobile-search-title').textContent = 'Search';
        return;
    }
    mobileSearchTimeout = setTimeout(function() { searchMusicMobile(q); }, 600);
}

async function searchMusicMobile(query) {
    if (!query.trim()) return;
    var grid = document.getElementById('mobile-search-grid');
    var title = document.getElementById('mobile-search-title');
    if (!grid) return;
    title.textContent = 'Search';
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Searching...</h3></div>';
    try {
        const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=' + encodeURIComponent(query) + '&limit=18');
        const data = await res.json();
        if (!data.success || !data.data || data.data.length === 0) {
            grid.innerHTML = emptyState('No results', 'Try a different search term');
            return;
        }
        title.textContent = 'Results: "' + esc(query) + '"';
        allSongs = data.data;
        accumulateKnown(allSongs);
        renderGrid(grid, allSongs);
        preloadUrls(allSongs);
    } catch (e) {
        grid.innerHTML = emptyState('Search failed', esc(e.message));
    }
}

function handleSearch(e) {
    e.preventDefault();
    var q = document.getElementById('search-input')?.value;
    if (q && q.trim()) searchMusic(q);
}

function showToast(msg) {
    var t = document.getElementById('toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        t.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:var(--bg-tertiary);color:var(--text);padding:0.625rem 1.25rem;border-radius:9999px;font-size:0.8rem;font-weight:500;z-index:9999;pointer-events:none;opacity:0;transition:opacity 0.3s ease;box-shadow:0 4px 16px rgba(0,0,0,0.4);';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    setTimeout(function() { t.style.opacity = '0'; }, 2000);
}

async function handleLogin(e) {
    e.preventDefault();
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var alertEl = document.getElementById('alert');
    var btn = e.target.querySelector('button[type="submit"]');
    var orig = btn.textContent;
    btn.disabled = true; btn.textContent = 'Signing in...';
    try {
        var res = await fetch(API_BASE + '/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, password: password })
        });
        var data = await res.json();
        if (data.success) {
            showAlert(alertEl, 'Welcome back!', 'success');
            setTimeout(function() { window.location.href = 'dashboard.php'; }, 600);
        } else {
            showAlert(alertEl, data.message, 'error');
            btn.disabled = false; btn.textContent = orig;
        }
    } catch (err) {
        showAlert(alertEl, 'Something went wrong', 'error');
        btn.disabled = false; btn.textContent = orig;
    }
}

async function handleRegister(e) {
    e.preventDefault();
    var username = document.getElementById('username').value;
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm-password').value;
    var alertEl = document.getElementById('alert');
    var btn = e.target.querySelector('button[type="submit"]');
    var orig = btn.textContent;
    if (password !== confirmPassword) {
        showAlert(alertEl, "Passwords don't match", 'error');
        return;
    }
    btn.disabled = true; btn.textContent = 'Creating account...';
    try {
        var res = await fetch(API_BASE + '/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username, email: email, password: password })
        });
        var data = await res.json();
        if (data.success) {
            showAlert(alertEl, 'Account created!', 'success');
            setTimeout(function() { window.location.href = 'login.php'; }, 1000);
        } else {
            showAlert(alertEl, data.message, 'error');
            btn.disabled = false; btn.textContent = orig;
        }
    } catch (err) {
        showAlert(alertEl, 'Something went wrong', 'error');
        btn.disabled = false; btn.textContent = orig;
    }
}

async function handleLogout() {
    try { await fetch(API_BASE + '/logout.php', { method: 'POST' }); } catch (e) {}
    window.location.href = 'login.php';
}

function showAlert(el, msg, type) {
    if (!el) return;
    el.textContent = msg;
    el.className = 'alert alert-' + type + ' show';
}

function simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash;
    }
    return Math.abs(hash).toString(36);
}

// ===== LYRICS =====
let lyricsData = [];
let lyricsSynced = false;
let lyricsCache = {};
let lyricsVisible = false;

function toggleLyrics() {
    const panel = document.getElementById('lyrics-panel');
    const btn = document.getElementById('lyrics-btn');
    lyricsVisible = !lyricsVisible;
    panel.classList.toggle('show', lyricsVisible);
    btn.classList.toggle('active', lyricsVisible);
    if (lyricsVisible && currentSong) {
        loadLyrics(currentSong);
    }
}

function toggleQueue() {
    var panel = document.getElementById('queue-panel');
    var isActive = panel.classList.toggle('active');
    if (isActive) renderQueue();
}

function switchQueueTab(btn, tab) {
    document.querySelectorAll('.queue-tab').forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('queue-content').style.display = tab === 'queue' ? '' : 'none';
    document.getElementById('recent-content').style.display = tab === 'recent' ? '' : 'none';
    if (tab === 'recent') renderRecent();
}

function renderQueue() {
    var nowEl = document.getElementById('queue-now');
    var nextEl = document.getElementById('queue-next');
    var titleEl = document.getElementById('queue-next-title');

    if (!currentSong) {
        nowEl.innerHTML = '<div class="queue-empty">No song playing</div>';
        nextEl.innerHTML = '';
        titleEl.textContent = 'Next from: Queue';
        return;
    }

    nowEl.innerHTML = queueItemHTML(currentSong, true);

    var upcoming = [];
    if (sectionSongs[currentSection] && currentIndex >= 0) {
        for (var i = currentIndex + 1; i < sectionSongs[currentSection].length && upcoming.length < 20; i++) {
            upcoming.push(sectionSongs[currentSection][i]);
        }
    } else if (allSongs.length > 0 && currentIndex >= 0) {
        for (var i = currentIndex + 1; i < allSongs.length && upcoming.length < 20; i++) {
            upcoming.push(allSongs[i]);
        }
    }

    titleEl.textContent = 'Next from: ' + (currentSection === 'favorites' ? 'Favorites' : currentSection === 'recent' ? 'Recently Played' : 'Queue');

    if (upcoming.length === 0) {
        nextEl.innerHTML = '<div class="queue-empty">No upcoming songs</div>';
    } else {
        nextEl.innerHTML = upcoming.map(function(song) {
            return queueItemHTML(song, false);
        }).join('');
    }
}

function renderRecent() {
    var el = document.getElementById('recent-list');
    if (recentSongs.length === 0) {
        el.innerHTML = '<div class="queue-empty">No recently played songs</div>';
        return;
    }
    el.innerHTML = recentSongs.map(function(song) {
        return queueItemHTML(song, false);
    }).join('');
}

function queueItemHTML(song, isNowPlaying) {
    return '<div class="queue-item' + (isNowPlaying ? ' now-playing-item' : '') + '" onclick="playQueueSong(\'' + esc(song.id).replace(/'/g, "\\'") + '\')">' +
        '<img class="queue-item-img" src="' + esc(song.cover_image) + '" alt="" onerror="this.style.display=\'none\'">' +
        '<div class="queue-item-info">' +
            '<div class="queue-item-title">' + esc(song.title) + '</div>' +
            '<div class="queue-item-artist">' + esc(song.artist) + '</div>' +
        '</div>' +
    '</div>';
}

function playQueueSong(songId) {
    var songs = sectionSongs[currentSection] || allSongs;
    for (var i = 0; i < songs.length; i++) {
        if (songs[i].id === songId) {
            currentSong = songs[i];
            currentIndex = i;
            playSongDirect();
            renderQueue();
            return;
        }
    }
}

function toggleNowPlaying() {
    var overlay = document.getElementById('now-playing-overlay');
    var isFs = overlay.classList.toggle('np-fullscreen');
    document.querySelector('.main-content')?.classList.toggle('np-hidden', isFs);
    document.querySelector('.player-bar')?.classList.toggle('np-fullscreen-bar', isFs);
    document.body.style.overflow = isFs ? 'hidden' : '';
    if (isFs) updateNowPlaying();
}

function isMobile() {
    return window.innerWidth <= 768;
}

function toggleUserMenu() {
    var menu = document.getElementById('user-menu-dropdown');
    if (!menu) return;
    menu.classList.toggle('active');
}

function addAccount() {
    fetch(API_BASE + '/logout.php', { method: 'POST' }).then(function() {
        window.location.href = 'register.php';
    }).catch(function() {
        window.location.href = 'register.php';
    });
}

document.addEventListener('click', function(e) {
    var menu = document.getElementById('user-menu-dropdown');
    var avatar = document.getElementById('header-avatar');
    if (menu && !menu.contains(e.target) && avatar && !avatar.contains(e.target)) {
        menu.classList.remove('active');
    }
    var sMenu = document.getElementById('sidebar-user-menu');
    var sInfo = document.getElementById('sidebar-user-info');
    if (sMenu && sInfo && !sInfo.contains(e.target)) {
        sMenu.classList.remove('active');
    }
});

function toggleSidebarUserMenu() {
    var menu = document.getElementById('sidebar-user-menu');
    if (menu) menu.classList.toggle('active');
}

function toggleNpCollapse() {
    var np = document.getElementById('now-playing-overlay');
    var main = document.querySelector('.main-content');
    if (!np) return;
    if (isMobile()) {
        np.classList.toggle('np-mobile-active');
    } else {
        np.classList.toggle('np-collapsed');
        if (main) main.classList.toggle('np-collapsed', np.classList.contains('np-collapsed'));
        localStorage.setItem('np_collapsed', np.classList.contains('np-collapsed') ? '1' : '0');
    }
}

function restoreNpState() {
    var np = document.getElementById('now-playing-overlay');
    var main = document.querySelector('.main-content');
    if (isMobile()) {
        if (np) { np.classList.remove('np-collapsed'); np.classList.remove('np-mobile-active'); }
        if (main) main.classList.remove('np-collapsed');
        return;
    }
    if (localStorage.getItem('np_collapsed') === '1') {
        if (np) np.classList.add('np-collapsed');
        if (main) main.classList.add('np-collapsed');
    }
}

function openNpMobile() {
    var np = document.getElementById('now-playing-overlay');
    if (np && isMobile()) {
        np.classList.add('np-mobile-active');
        history.pushState({ np: true }, '', '');
    }
}

function closeNpMobile() {
    var np = document.getElementById('now-playing-overlay');
    if (np && isMobile()) np.classList.remove('np-mobile-active');
}

window.addEventListener('popstate', function(e) {
    if (isMobile()) {
        var np = document.getElementById('now-playing-overlay');
        if (np && np.classList.contains('np-mobile-active')) {
            closeNpMobile();
            return;
        }
        var sidebar = document.querySelector('.sidebar');
        if (sidebar && sidebar.classList.contains('open')) {
            toggleSidebar();
            return;
        }
    }
});

function updateNowPlaying() {
    var emptyState = document.getElementById('np-empty-state');
    var hasSong = document.getElementById('np-has-song');
    if (!currentSong) {
        if (emptyState) emptyState.style.display = '';
        if (hasSong) hasSong.style.display = 'none';
        return;
    }
    if (emptyState) emptyState.style.display = 'none';
    if (hasSong) hasSong.style.display = '';
    document.getElementById('np-title').textContent = currentSong.title || 'Not Playing';
    document.getElementById('np-artist').textContent = currentSong.artist || '-';
    document.getElementById('np-cover').src = currentSong.cover_image || '';
    document.getElementById('np-fav-btn').classList.toggle('favorited', isFavorited(currentSong.id));
    document.getElementById('np-fav-btn').innerHTML = isFavorited(currentSong.id)
        ? '<svg width="24" height="24" viewBox="0 0 24 24" fill="#a78bfa" stroke="#a78bfa" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>'
        : '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
    var npBg = document.getElementById('now-playing-bg');
    var overlay = document.getElementById('now-playing-overlay');
    if (currentSong.cover_image) {
        overlay.style.backgroundImage = 'url(' + currentSong.cover_image + ')';
        overlay.style.backgroundSize = 'cover';
        overlay.style.backgroundPosition = 'center';
        overlay.style.backgroundBlendMode = 'overlay';
    }
    var npIcon = document.getElementById('np-play-icon');
    npIcon.innerHTML = isPlaying
        ? '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>'
        : '<path d="M8 5v14l11-7z"/>';
    document.getElementById('np-artist-name').textContent = currentSong.artist || '-';
    document.getElementById('np-artist-img').src = currentSong.cover_image || '';
    document.getElementById('np-artist-bio').textContent = currentSong.artist ? currentSong.artist + ' is an artist on Ginz Song. Enjoy their music and discover more.' : '';
    document.getElementById('np-artist-link').href = 'https://open.spotify.com/search/' + encodeURIComponent(currentSong.artist || '');
    document.getElementById('np-artist-listeners').textContent = '';
    if (isMobile()) loadNpLyrics(currentSong);
}

var npLyricsData = [];
var npLyricsSynced = false;

async function loadNpLyrics(song) {
    var container = document.getElementById('np-lyrics-content');
    if (!container) return;
    if (!song) { container.innerHTML = ''; return; }

    var cacheKey = (song.artist || '') + '|' + (song.title || '');
    if (lyricsCache[cacheKey]) {
        renderNpLyrics(lyricsCache[cacheKey]);
        return;
    }

    container.innerHTML = '<div class="np-lyrics-loading">Loading lyrics...</div>';

    try {
        var res = await fetch(API_BASE + '/lyrics.php?' + new URLSearchParams({
            artist: song.artist || '',
            track: song.title || ''
        }));
        var data = await res.json();
        lyricsCache[cacheKey] = data;
        renderNpLyrics(data);
    } catch {
        container.innerHTML = '<div class="np-lyrics-not-found">No lyrics available</div>';
    }
}

function renderNpLyrics(data) {
    var container = document.getElementById('np-lyrics-content');
    if (!container) return;

    if (data.success && data.synced && Array.isArray(data.lyrics)) {
        npLyricsData = data.lyrics;
        npLyricsSynced = true;
        container.innerHTML = data.lyrics.map(function(line, i) {
            return '<div class="np-lyrics-line" data-time="' + line.time + '" data-index="' + i + '" onclick="seekToNpLyric(' + line.time + ')">' + esc(line.text) + '</div>';
        }).join('');
    } else if (data.success && typeof data.lyrics === 'string') {
        npLyricsSynced = false;
        container.innerHTML = '<div class="np-lyrics-plain">' + esc(data.lyrics) + '</div>';
    } else {
        npLyricsSynced = false;
        container.innerHTML = '<div class="np-lyrics-not-found">No lyrics available for this song</div>';
    }
}

function seekToNpLyric(time) {
    audio.currentTime = time;
    if (!isPlaying) audio.play().catch(function() {});
}

function updateNpLyricsHighlight() {
    if (!isMobile() || !npLyricsSynced || npLyricsData.length === 0) return;
    var container = document.getElementById('np-lyrics-content');
    if (!container) return;

    var currentTime = audio.currentTime;
    var activeIndex = -1;
    for (var i = npLyricsData.length - 1; i >= 0; i--) {
        if (currentTime >= npLyricsData[i].time - 0.1) {
            activeIndex = i;
            break;
        }
    }

    var lines = container.querySelectorAll('.np-lyrics-line');
    lines.forEach(function(line, i) {
        if (i === activeIndex) {
            if (!line.classList.contains('active')) {
                line.classList.add('active');
                var scrollTarget = line.offsetTop - container.offsetTop - container.clientHeight / 3;
                container.scrollTo({ top: scrollTarget, behavior: 'smooth' });
            }
        } else {
            line.classList.remove('active');
        }
    });
}

function toggleNpFav() {
    if (!currentSong) return;
    toggleFavorite(currentSong);
    updateNowPlaying();
}

async function loadLyrics(song) {
    const content = document.getElementById('lyrics-content');
    const titleEl = document.getElementById('lyrics-title');
    const artistEl = document.getElementById('lyrics-artist');
    const coverEl = document.getElementById('lyrics-cover');

    titleEl.textContent = song.title || '-';
    artistEl.textContent = song.artist || '-';
    coverEl.src = song.cover_image || '';

    var cacheKey = (song.artist || '') + '|' + (song.title || '');
    if (lyricsCache[cacheKey]) {
        var cached = lyricsCache[cacheKey];
        if (cached.synced && Array.isArray(cached.lyrics)) {
            lyricsData = cached.lyrics;
            lyricsSynced = true;
            renderSyncedLyrics();
        } else if (typeof cached.lyrics === 'string') {
            lyricsSynced = false;
            content.innerHTML = '<div class="lyrics-plain">' + esc(cached.lyrics) + '</div>';
        } else {
            lyricsSynced = false;
            content.innerHTML = '<div class="lyrics-not-found"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p>No lyrics available</p></div>';
        }
        return;
    }

    content.innerHTML = '<div class="lyrics-loading"><div class="loading-spinner"></div><p>Loading lyrics...</p></div>';

    try {
        const res = await fetch(API_BASE + '/lyrics.php?' + new URLSearchParams({
            artist: song.artist || '',
            track: song.title || ''
        }));
        const data = await res.json();
        lyricsCache[cacheKey] = data;

        if (data.success && data.synced && Array.isArray(data.lyrics)) {
            lyricsData = data.lyrics;
            lyricsSynced = true;
            renderSyncedLyrics();
        } else if (data.success && typeof data.lyrics === 'string') {
            lyricsSynced = false;
            content.innerHTML = '<div class="lyrics-plain">' + esc(data.lyrics) + '</div>';
        } else {
            lyricsSynced = false;
            content.innerHTML = '<div class="lyrics-not-found"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p>No lyrics available for this song</p></div>';
        }
    } catch {
        lyricsSynced = false;
        content.innerHTML = '<div class="lyrics-not-found"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><p>Failed to load lyrics</p></div>';
    }
}

function renderSyncedLyrics() {
    const content = document.getElementById('lyrics-content');
    content.innerHTML = lyricsData.map((line, i) =>
        '<div class="lyrics-line" data-time="' + line.time + '" data-index="' + i + '" onclick="seekToLyric(' + line.time + ')">' + esc(line.text) + '</div>'
    ).join('');
}

function seekToLyric(time) {
    audio.currentTime = time;
    if (!isPlaying) {
        audio.play().catch(function() {});
    }
}

function updateLyricsHighlight() {
    if (!lyricsSynced || !lyricsVisible || lyricsData.length === 0) return;

    const currentTime = audio.currentTime;
    let activeIndex = -1;

    for (let i = lyricsData.length - 1; i >= 0; i--) {
        if (currentTime >= lyricsData[i].time - 0.1) {
            activeIndex = i;
            break;
        }
    }

    const lines = document.querySelectorAll('.lyrics-line');
    lines.forEach((line, i) => {
        line.classList.remove('active', 'past');
        if (i === activeIndex) {
            line.classList.add('active');
        } else if (i < activeIndex) {
            line.classList.add('past');
        }
    });

    if (activeIndex >= 0) {
        const activeLine = lines[activeIndex];
        if (activeLine) {
            const container = document.getElementById('lyrics-content');
            const containerHeight = container.clientHeight;
            const lineTop = activeLine.offsetTop;
            const lineHeight = activeLine.clientHeight;
            const scrollTarget = lineTop - containerHeight / 2 + lineHeight / 2;
            container.scrollTo({ top: scrollTarget, behavior: 'smooth' });
        }
    }
}
