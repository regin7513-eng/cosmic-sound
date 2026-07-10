const API_BASE = window.location.origin + '/cosmic-sound/api';

let currentSong = null;
let isPlaying = false;
let audio = new Audio();
let allSongs = [];
let currentIndex = 0;
let isShuffled = false;
let isRepeating = false;
let favoriteIds = new Set();
let userPlaylists = [];
let contextSong = null;
let urlCache = {};

document.addEventListener('DOMContentLoaded', () => {
    const playerBar = document.getElementById('player-bar');
    if (playerBar) {
        initPlayer();
        loadFavoriteIds();
    }
    if (document.querySelector('.song-grid')) loadPlaylist('trending');

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('context-menu');
        if (menu && !menu.contains(e.target) && !e.target.closest('.song-card-menu') && !e.target.closest('.song-action-btn')) {
            menu.classList.remove('show');
        }
        const sidebar = document.querySelector('.sidebar');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !menuBtn?.contains(e.target)) {
            sidebar.classList.remove('open');
            document.querySelector('.sidebar-overlay')?.remove();
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
    if (!sidebar) return;
    sidebar.classList.toggle('open');
    if (sidebar.classList.contains('open')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:99;';
        document.body.appendChild(overlay);
    } else {
        document.querySelector('.sidebar-overlay')?.remove();
    }
}

function initPlayer() {
    document.getElementById('play-btn')?.addEventListener('click', togglePlay);
    document.getElementById('prev-btn')?.addEventListener('click', playPrevious);
    document.getElementById('next-btn')?.addEventListener('click', playNext);
    document.getElementById('shuffle-btn')?.addEventListener('click', toggleShuffle);
    document.getElementById('repeat-btn')?.addEventListener('click', toggleRepeat);
    document.querySelector('.progress-bar')?.addEventListener('click', seek);
    document.getElementById('volume-slider')?.addEventListener('input', changeVolume);
    document.getElementById('volume-btn')?.addEventListener('click', toggleMute);

    audio.volume = 0.7;
    audio.addEventListener('timeupdate', () => { updateProgress(); updateLyricsHighlight(); });
    audio.addEventListener('loadedmetadata', updateDuration);
    audio.addEventListener('play', () => { isPlaying = true; updatePlayButton(); refreshGrid(); });
    audio.addEventListener('pause', () => { isPlaying = false; updatePlayButton(); refreshGrid(); });
    audio.addEventListener('ended', handleSongEnd);
    audio.addEventListener('error', () => showToast('Unable to play this track'));
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
        const idx = parseInt(card.dataset.index);
        const s = allSongs[idx];
        if (!s || s.id !== song.id) return;
        const b = card.querySelector('.fav-btn');
        if (!b) return;
        if (isFavorited(s.id)) {
            b.classList.add('favorited');
            b.innerHTML = svgHeartFilled();
        } else {
            b.classList.remove('favorited');
            b.innerHTML = svgHeart();
        }
    });
}

function showContextMenu(e, song) {
    e.preventDefault();
    e.stopPropagation();
    contextSong = song;
    const menu = document.getElementById('context-menu');
    const favText = document.getElementById('ctx-fav-text');
    favText.textContent = isFavorited(song.id) ? 'Remove from Favorites' : 'Add to Favorites';
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
    openPlaylistModal();
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
        showToast(data.success ? 'Added to "' + playlistName + '"' : (data.message || 'Failed'));
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
        document.getElementById('playlist-title').textContent = pl.name;
        renderGrid(grid, allSongs);
    } catch (e) {
        grid.innerHTML = emptyState('Couldn\'t load playlist', esc(e.message));
    }
}

async function searchMusic(query) {
    const grid = document.querySelector('#section-home .song-grid');
    if (!grid || !query.trim()) return;
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Searching...</h3></div>';

    try {
        const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=' + encodeURIComponent(query) + '&limit=18');
        const data = await res.json();

        if (!data.success || !data.data || data.data.length === 0) {
            grid.innerHTML = emptyState('No results', 'Try a different search term');
            return;
        }

        allSongs = data.data;
        document.getElementById('playlist-title').textContent = 'Search: "' + esc(query) + '"';
        renderGrid(grid, allSongs);
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
    if (recentSongs.length > 0) {
        allSongs = recentSongs;
        renderGrid(grid, recentSongs);
    } else {
        grid.innerHTML = emptyState('No recently played songs', 'Songs you play will appear here');
    }
}

async function loadArtists() {
    const grid = document.getElementById('artists-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading artists...</h3></div>';

    try {
        const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=popular artists 2025&limit=18');
        const data = await res.json();

        if (!data.success || !data.data || data.data.length === 0) {
            grid.innerHTML = emptyState('No artists found', '');
            return;
        }

        var artistMap = {};
        data.data.forEach(function(song) {
            if (!artistMap[song.artist]) {
                artistMap[song.artist] = song;
            }
        });
        var artists = Object.values(artistMap);

        grid.innerHTML = artists.map(function(song) {
            return '<div class="artist-card" onclick="searchMusic(\'' + esc(song.artist).replace(/'/g, "\\'") + '\')">' +
                '<div class="artist-avatar">' +
                    '<img src="' + esc(song.cover_image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
                '</div>' +
                '<h4 class="artist-name">' + esc(song.artist) + '</h4>' +
                '<p class="artist-label">Artist</p>' +
            '</div>';
        }).join('');
    } catch (e) {
        grid.innerHTML = emptyState('Could not load artists', '');
    }
}

async function loadAlbums() {
    const grid = document.getElementById('albums-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading albums...</h3></div>';

    try {
        const res = await fetch(API_BASE + '/sankavollerei.php?action=search&q=best albums 2025&limit=18');
        const data = await res.json();

        if (!data.success || !data.data || data.data.length === 0) {
            grid.innerHTML = emptyState('No albums found', '');
            return;
        }

        var albumMap = {};
        data.data.forEach(function(song) {
            if (song.album && !albumMap[song.album]) {
                albumMap[song.album] = song;
            }
        });
        var albums = Object.values(albumMap);

        grid.innerHTML = albums.map(function(song) {
            return '<div class="song-card" onclick="searchMusic(\'' + esc(song.album).replace(/'/g, "\\'") + '\')">' +
                '<div class="song-card-cover">' +
                    '<img src="' + esc(song.cover_image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
                    '<div class="play-overlay"><div class="play-btn-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg></div></div>' +
                '</div>' +
                '<h4 class="song-card-title">' + esc(song.album) + '</h4>' +
                '<p class="song-card-artist">' + esc(song.artist) + '</p>' +
            '</div>';
        }).join('');
    } catch (e) {
        grid.innerHTML = emptyState('Could not load albums', '');
    }
}

function emptyState(title, subtitle) {
    return '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>' + title + '</h3><p>' + subtitle + '</p></div>';
}

function renderGrid(grid, songList) {
    if (!grid || !songList) return;
    if (songList.length === 0) {
        grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>No songs found</h3></div>';
        return;
    }
    grid.innerHTML = songList.map(function(song, i) {
        var isActive = currentSong && currentSong.id === song.id;
        var isFav = isFavorited(song.id);
        var playIcon = (isActive && isPlaying)
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'
            : '<svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg>';
        var cardClick = isActive ? 'togglePlay()' : 'playSong(' + i + ')';
        return '<div class="song-card' + (isActive ? ' active' : '') + '" data-index="' + i + '" onclick="' + cardClick + '" oncontextmenu="showContextMenu(event, allSongs[' + i + '])">' +
            '<div class="song-card-cover">' +
                '<img src="' + esc(song.cover_image) + '" alt="" loading="lazy" onerror="this.style.display=\'none\'">' +
                (isActive && isPlaying ? '<div class="now-playing-bars" style="position:absolute;bottom:8px;left:8px;z-index:3;"><span></span><span></span><span></span></div>' : '') +
                '<div class="play-overlay"><div class="play-btn-circle">' + playIcon + '</div></div>' +
                '<div class="song-card-actions">' +
                    '<button class="song-action-btn fav-btn' + (isFav ? ' favorited' : '') + '" onclick="event.stopPropagation(); toggleFavorite(allSongs[' + i + '], this)" title="Favorite">' +
                        (isFav ? svgHeartFilled() : svgHeart()) +
                    '</button>' +
                    '<button class="song-action-btn" onclick="event.stopPropagation(); showContextMenu(event, allSongs[' + i + '])" title="More">' +
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<h4 class="song-card-title">' + esc(song.title) + '</h4>' +
            '<p class="song-card-artist">' + esc(song.artist) + '</p>' +
        '</div>';
    }).join('');
}

async function playSong(index) {
    if (index < 0 || index >= allSongs.length) return;
    currentIndex = index;
    currentSong = allSongs[index];

    if (!currentSong.track_url) {
        showToast('This track is not available');
        return;
    }

    updatePlayerUI();
    updatePlayButton();
    refreshGrid();
    showToast('Loading...');

    try {
        let streamUrl = urlCache[currentSong.track_url];

        if (!streamUrl) {
            const res = await fetch(API_BASE + '/sankavollerei.php?action=download&url=' + encodeURIComponent(currentSong.track_url));
            const data = await res.json();

            if (!data.success || !data.download_url) {
                showToast('Unable to load stream');
                return;
            }

            streamUrl = data.download_url;
            urlCache[currentSong.track_url] = streamUrl;
        }

        audio.src = streamUrl;
        audio.load();
        audio.play().then(() => {
            addToRecent(currentSong);
        }).catch(() => showToast('Unable to play this track'));
    } catch (e) {
        showToast('Failed to load stream');
    }
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
    playSong(currentIndex);
}

function playNext() {
    if (allSongs.length === 0) return;
    if (isShuffled) {
        var n;
        do { n = Math.floor(Math.random() * allSongs.length); } while (n === currentIndex && allSongs.length > 1);
        playSong(n);
    } else {
        currentIndex = (currentIndex + 1) % allSongs.length;
        playSong(currentIndex);
    }
}

function handleSongEnd() {
    if (isRepeating) { audio.currentTime = 0; audio.play(); }
    else playNext();
}

function seek(e) {
    if (!audio.duration) return;
    var rect = e.currentTarget.getBoundingClientRect();
    audio.currentTime = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)) * audio.duration;
}

function changeVolume(e) {
    audio.volume = e.target.value / 100;
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
    var fill = document.querySelector('.progress-bar-fill');
    var curEl = document.getElementById('current-time');
    if (fill) fill.style.width = ((audio.currentTime / audio.duration) * 100) + '%';
    if (curEl) curEl.textContent = fmt(audio.currentTime);
}

function updateDuration() {
    var durEl = document.getElementById('duration');
    if (durEl && audio.duration) durEl.textContent = fmt(audio.duration);
}

function updatePlayerUI() {
    document.querySelector('.player-bar')?.classList.add('active');
    document.getElementById('player-cover-img').src = currentSong.cover_image || '';
    document.getElementById('player-title').textContent = currentSong.title;
    document.getElementById('player-artist').textContent = currentSong.artist;
    document.title = currentSong.title + ' - ' + currentSong.artist + ' | Ginz Song';
}

function updatePlayButton() {
    var btn = document.getElementById('play-btn');
    if (!btn) return;
    btn.innerHTML = isPlaying
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="#000"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg>';
}

function refreshGrid() {
    var homeGrid = document.querySelector('#section-home .song-grid');
    if (homeGrid && allSongs.length > 0) {
        renderGrid(homeGrid, allSongs);
    }
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

async function loadLyrics(song) {
    const content = document.getElementById('lyrics-content');
    const titleEl = document.getElementById('lyrics-title');
    const artistEl = document.getElementById('lyrics-artist');
    const coverEl = document.getElementById('lyrics-cover');

    titleEl.textContent = song.title || '-';
    artistEl.textContent = song.artist || '-';
    coverEl.src = song.cover_image || '';

    content.innerHTML = '<div class="lyrics-loading"><div class="loading-spinner"></div><p>Loading lyrics...</p></div>';

    try {
        const res = await fetch(API_BASE + '/lyrics.php?' + new URLSearchParams({
            artist: song.artist || '',
            track: song.title || ''
        }));
        const data = await res.json();

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
