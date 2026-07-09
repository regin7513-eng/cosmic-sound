<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmic Sound</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                </div>
                <span class="sidebar-logo-text">Cosmic Sound</span>
            </div>

            <div class="sidebar-nav-wrapper">
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Discover</div>
                    <ul class="sidebar-nav">
                        <li>
                            <a href="#" class="nav-link active" data-section="home" onclick="showSection('home', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                                <span>Listen Now</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link" data-section="favorites" onclick="showSection('favorites', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                <span>Favorites</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title">Library</div>
                    <ul class="sidebar-nav">
                        <li>
                            <a href="#" class="nav-link" data-section="playlists" onclick="showSection('playlists', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                                <span>My Playlists</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                    <div class="user-details">
                        <span class="user-name"><?php echo $username; ?></span>
                        <span class="user-label">Premium</span>
                    </div>
                </div>
                <button class="logout-btn" onclick="handleLogout()" title="Sign Out">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-greeting">
                    <h1>Listen Now</h1>
                    <p class="header-subtitle">Discover music that matches your vibe</p>
                </div>
                <div class="search-container">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <form onsubmit="handleSearch(event)">
                        <input type="text" id="search-input" class="search-input" placeholder="What do you want to listen to?" oninput="onSearchInput(this.value)">
                    </form>
                </div>
            </header>

            <!-- Section: Listen Now -->
            <div id="section-home" class="section active">
                <section class="playlist-tabs">
                    <button class="tab-btn active" onclick="loadPlaylistTab(this, 'trending')">Trending</button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'chill')">Chill</button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'energy')">Energy</button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'romance')">Romance</button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'focus')">Focus</button>
                </section>
                <div class="section-header">
                    <h2 id="playlist-title">Trending</h2>
                </div>
                <div class="song-grid"></div>
            </div>

            <!-- Section: Favorites -->
            <div id="section-favorites" class="section">
                <div class="section-header">
                    <h2>Favorites</h2>
                </div>
                <div class="song-grid" id="favorites-grid"></div>
            </div>

            <!-- Section: My Playlists -->
            <div id="section-playlists" class="section">
                <div class="section-header">
                    <h2>My Playlists</h2>
                    <button class="create-playlist-btn-inline" onclick="showCreatePlaylistModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        New Playlist
                    </button>
                </div>
                <div class="song-grid" id="user-playlists-grid"></div>
            </div>
        </main>
    </div>

    <!-- Player Bar -->
    <div class="player-bar" id="player-bar">
        <div class="player-info">
            <img id="player-cover-img" src="" alt="">
            <div class="player-info-text">
                <h4 id="player-title">Not Playing</h4>
                <p id="player-artist">-</p>
            </div>
        </div>

        <div class="player-center">
            <div class="player-controls">
                <button id="shuffle-btn" class="control-btn" title="Shuffle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                </button>
                <button id="prev-btn" class="control-btn" title="Previous">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                </button>
                <button id="play-btn" class="play-btn-main" title="Play">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </button>
                <button id="next-btn" class="control-btn" title="Next">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                </button>
                <button id="repeat-btn" class="control-btn" title="Repeat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </button>
            </div>
            <div class="player-progress">
                <span id="current-time">0:00</span>
                <div class="progress-bar"><div class="progress-bar-fill"></div></div>
                <span id="duration">0:00</span>
            </div>
        </div>

        <div class="player-right">
            <button id="lyrics-btn" class="control-btn" title="Lyrics" onclick="toggleLyrics()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </button>
            <button id="volume-btn" class="control-btn" title="Volume">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
            </button>
            <input type="range" id="volume-slider" min="0" max="100" value="70" class="volume-slider">
        </div>
    </div>

    <!-- Lyrics Panel -->
    <div class="lyrics-panel" id="lyrics-panel">
        <div class="lyrics-header">
            <div class="lyrics-song-info">
                <img id="lyrics-cover" src="" alt="">
                <div>
                    <h4 id="lyrics-title">-</h4>
                    <p id="lyrics-artist">-</p>
                </div>
            </div>
            <button class="lyrics-close" onclick="toggleLyrics()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="lyrics-content" id="lyrics-content">
            <div class="lyrics-loading">
                <div class="loading-spinner"></div>
                <p>Loading lyrics...</p>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div class="context-menu" id="context-menu">
        <button class="context-menu-item" id="ctx-add-fav">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span id="ctx-fav-text">Add to Favorites</span>
        </button>
        <button class="context-menu-item" id="ctx-add-playlist">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add to Playlist
        </button>
    </div>

    <!-- Add to Playlist Modal -->
    <div class="modal-overlay" id="playlist-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Add to Playlist</h3>
                <button class="modal-close" onclick="closePlaylistModal()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div id="playlist-modal-list">
                <div style="text-align:center;padding:2rem;"><div class="loading-spinner"></div></div>
            </div>
            <button class="create-playlist-btn" onclick="showCreatePlaylistModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create New Playlist
            </button>
        </div>
    </div>

    <!-- Create Playlist Modal -->
    <div class="modal-overlay" id="create-playlist-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>New Playlist</h3>
                <button class="modal-close" onclick="closeCreatePlaylistModal()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <form onsubmit="createPlaylist(event)">
                <div class="form-group">
                    <label for="new-playlist-name">Name</label>
                    <input type="text" id="new-playlist-name" class="form-input" placeholder="My Playlist" required>
                </div>
                <div class="form-group">
                    <label for="new-playlist-desc">Description (optional)</label>
                    <input type="text" id="new-playlist-desc" class="form-input" placeholder="Add a description">
                </div>
                <button type="submit" class="btn btn-primary">Create Playlist</button>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        function loadPlaylistTab(el, id) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            loadPlaylist(id);
        }

        function showSection(name, el) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            document.getElementById('section-' + name)?.classList.add('active');
            if (el) el.classList.add('active');
            if (name === 'favorites') loadFavorites();
            if (name === 'playlists') loadUserPlaylists();
        }

        async function loadFavorites() {
            const grid = document.getElementById('favorites-grid');
            grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading favorites...</h3></div>';
            try {
                const res = await fetch(API_BASE + '/favorites.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success && data.data.length > 0) {
                    const favSongs = data.data.map(f => ({
                        id: f.track_id, title: f.title, artist: f.artist, album: f.album,
                        cover_image: f.cover_image, file_path: f.file_path, track_url: f.track_url,
                        duration_text: f.duration_text, source: 'spotify'
                    }));
                    renderGrid(grid, favSongs);
                } else {
                    grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><h3>No favorites yet</h3><p>Songs you love will appear here</p></div>';
                }
            } catch (e) {
                grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><h3>Could not load favorites</h3></div>';
            }
        }

        async function loadUserPlaylists() {
            const grid = document.getElementById('user-playlists-grid');
            grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading playlists...</h3></div>';
            try {
                const res = await fetch(API_BASE + '/playlists.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success && data.data.length > 0) {
                    grid.innerHTML = data.data.map(p => {
                        return '<div class="song-card playlist-card" onclick="loadUserPlaylistTracks(\'' + p.id + '\', \'' + esc(p.name).replace(/'/g, "\\'") + '\')" style="cursor:pointer">' +
                            '<div class="song-card-cover">' +
                                '<div class="playlist-cover-gradient"><svg width="32" height="32" viewBox="0 0 24 24" fill="white"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg></div>' +
                                '<div class="play-overlay"><div class="play-btn-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg></div></div>' +
                            '</div>' +
                            '<h4 class="song-card-title">' + esc(p.name) + '</h4>' +
                            '<p class="song-card-artist">' + esc(p.description || 'Playlist') + '</p>' +
                        '</div>';
                    }).join('');
                } else {
                    grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>No playlists yet</h3><p>Create playlists to save your favorite tracks</p></div>';
                }
            } catch (e) {
                grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><h3>Could not load playlists</h3></div>';
            }
        }

        async function loadUserPlaylistTracks(playlistId, playlistName) {
            const grid = document.getElementById('user-playlists-grid');
            grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading ' + esc(playlistName) + '...</h3></div>';
            try {
                const res = await fetch(API_BASE + '/playlists.php?id=' + playlistId, { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success && data.tracks && data.tracks.length > 0) {
                    const plSongs = data.tracks.map(t => ({
                        id: t.track_id, title: t.title, artist: t.artist, album: t.album,
                        cover_image: t.cover_image, file_path: t.file_path, track_url: t.track_url,
                        duration_text: t.duration_text, source: 'spotify'
                    }));
                    allSongs = plSongs;
                    grid.innerHTML = '<div style="margin-bottom:1rem;"><button class="tab-btn" onclick="loadUserPlaylists()" style="font-size:0.8rem;">&larr; Back to Playlists</button> <span style="color:var(--text-secondary);font-size:0.85rem;margin-left:0.5rem;">' + esc(playlistName) + ' &middot; ' + plSongs.length + ' songs</span></div>';
                    renderGrid(grid, plSongs);
                } else {
                    grid.innerHTML = '<div style="margin-bottom:1rem;"><button class="tab-btn" onclick="loadUserPlaylists()" style="font-size:0.8rem;">&larr; Back to Playlists</button></div><div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>No tracks yet</h3><p>Add songs from the Listen Now section</p></div>';
                }
            } catch (e) {
                grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><h3>Could not load playlist</h3></div>';
            }
        }
    </script>
</body>
</html>