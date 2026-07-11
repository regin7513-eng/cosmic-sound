<?php require_once __DIR__ . '/config/session.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0a0014">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    <title>Ginz Song</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) { window.location.reload(); }
    });
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').catch(function() {});
    }
    </script>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-collapse-btn" onclick="toggleSidebarCollapse()" title="Toggle sidebar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </div>
            <div class="sidebar-nav-wrapper">
                <div class="sidebar-section">
                    <div class="sidebar-section-title" onclick="toggleSidebarSection(this)">
                        Discover
                        <svg class="section-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <ul class="sidebar-nav">
                        <li>
                            <a href="#" class="nav-link active" data-section="home" onclick="showSection('home', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                                <span>Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link" data-section="favorites" onclick="showSection('favorites', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                <span>Favorites</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link" data-section="recent" onclick="showSection('recent', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Recently Played</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title" onclick="toggleSidebarSection(this)">
                        Library
                        <svg class="section-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <ul class="sidebar-nav">
                        <li>
                            <a href="#" class="nav-link" data-section="playlists" onclick="showSection('playlists', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                                <span>My Playlists</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link" data-section="artists" onclick="showSection('artists', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span>Artists</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nav-link" data-section="albums" onclick="showSection('albums', this)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                <span>Albums</span>
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
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="mobile-header-left">
                    <div class="header-avatar" onclick="toggleUserMenu()" id="header-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                    <div class="header-greeting">
                        <h1>Ginz Song</h1>
                    </div>
                </div>
                <div class="user-menu-dropdown" id="user-menu-dropdown">
                    <div class="user-menu-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                    <button class="user-menu-item" onclick="window.location.href='login.php'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add Account
                    </button>
                    <button class="user-menu-item user-menu-logout" onclick="handleLogout()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Logout
                    </button>
                </div>
                <div class="search-container">
                    <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <form onsubmit="handleSearch(event)">
                        <input type="text" id="search-input" class="search-input" placeholder="What do you want to listen to?" oninput="onSearchInput(this.value)">
                    </form>
                </div>
            </header>

            <!-- Section: Listen Now -->
            <div id="section-home" class="section active">
                <section class="playlist-tabs">
                    <button class="tab-btn active" onclick="loadPlaylistTab(this, 'trending')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        Trending
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'chill')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                        Chill
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'energy')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        Energy
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'romance')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        Romance
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'focus')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Focus
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'party')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        Party
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'sad')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                        Sad
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'indie')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        Indie
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'hiphop')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                        Hip Hop
                    </button>
                    <button class="tab-btn" onclick="loadPlaylistTab(this, 'karaoke')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                        Karaoke
                    </button>
                </section>
                <div class="section-header">
                    <h2 id="playlist-title">Trending</h2>
                </div>
                <div class="song-grid"></div>

                <!-- Mobile: Recently Played in Home -->
                <div class="mobile-home-extras">
                    <div class="section-header">
                        <h2>Recently Played</h2>
                    </div>
                    <div class="song-grid" id="mobile-recent-grid"></div>
                </div>
            </div>

            <!-- Section: Favorites -->
            <div id="section-favorites" class="section">
                <div class="section-header">
                    <h2>Favorites</h2>
                </div>
                <div class="song-grid" id="favorites-grid"></div>
            </div>

            <!-- Section: Search (mobile only) -->
            <div id="section-search" class="section">
                <div class="mobile-search-box">
                    <input type="text" id="mobile-search-input" class="mobile-search-input" placeholder="What do you want to listen to?" oninput="onMobileSearchInput(this.value)">
                </div>
                <div class="section-header">
                    <h2 id="mobile-search-title">Search</h2>
                </div>
                <div class="song-grid" id="mobile-search-grid"></div>
            </div>

            <!-- Section: Recently Played -->
            <div id="section-recent" class="section">
                <div class="section-header">
                    <h2>Recently Played</h2>
                </div>
                <div class="song-grid" id="recent-grid"></div>
            </div>

            <!-- Section: Artists -->
            <div id="section-artists" class="section">
                <div class="section-header">
                    <h2>Artists</h2>
                </div>
                <div class="artist-grid" id="artists-grid"></div>
            </div>

            <!-- Section: Albums -->
            <div id="section-albums" class="section">
                <div class="section-header">
                    <h2>Albums</h2>
                </div>
                <div class="song-grid" id="albums-grid"></div>
            </div>

            <!-- Section: My Playlists -->
            <div id="section-playlists" class="section">
                <div class="section-header">
                    <h2 id="playlists-section-title">My Playlists</h2>
                    <button class="create-playlist-btn-inline" onclick="showCreatePlaylistModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        New Playlist
                    </button>
                </div>
                <div class="song-grid" id="user-playlists-grid"></div>

                <!-- Mobile: Artists & Albums in Library -->
                <div class="mobile-library-extras">
                    <div class="section-header">
                        <h2>Artists</h2>
                    </div>
                    <div class="artist-grid" id="mobile-artists-grid"></div>

                    <div class="section-header" style="margin-top: 1rem;">
                        <h2>Albums</h2>
                    </div>
                    <div class="song-grid" id="mobile-albums-grid"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Player Bar -->
    <div class="player-bar" id="player-bar">
        <div class="player-info" onclick="openNpMobile()" style="cursor:pointer;">
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
            <div class="player-progress" onclick="seek(event)">
                <span id="current-time">0:00</span>
                <div class="progress-bar"><div class="progress-bar-fill"></div></div>
                <span id="duration">0:00</span>
            </div>
        </div>

        <div class="player-right">
            <button id="lyrics-btn" class="control-btn" title="Lyrics" onclick="toggleLyrics()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </button>
            <button class="control-btn" title="Queue" onclick="toggleQueue()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
            <button id="volume-btn" class="control-btn" title="Volume">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
            </button>
            <input type="range" id="volume-slider" min="0" max="100" value="70" class="volume-slider">
            
        </div>
    </div>

    <!-- Mobile Bottom Nav -->
    <nav class="mobile-bottom-nav" id="mobile-bottom-nav">
        <button class="mobile-nav-btn active" onclick="mobileNav('home', this)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            <span>Home</span>
        </button>
        <button class="mobile-nav-btn" onclick="mobileNav('favorites', this)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span>Favorites</span>
        </button>
        <button class="mobile-nav-btn" onclick="mobileNav('search', this)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <span>Search</span>
        </button>
        <button class="mobile-nav-btn" onclick="mobileNav('playlists', this)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
            <span>Library</span>
        </button>
    </nav>

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

    <!-- Queue Panel -->
    <div class="queue-panel" id="queue-panel">
        <div class="queue-header">
            <div class="queue-tabs">
                <button class="queue-tab active" onclick="switchQueueTab(this, 'queue')">Queue</button>
                <button class="queue-tab" onclick="switchQueueTab(this, 'recent')">Recently played</button>
            </div>
            <button class="queue-close" onclick="toggleQueue()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="queue-content" id="queue-content">
            <div class="queue-section">
                <h4>Now playing</h4>
                <div id="queue-now"></div>
            </div>
            <div class="queue-section">
                <h4 id="queue-next-title">Next from: Queue</h4>
                <div id="queue-next"></div>
            </div>
        </div>
        <div class="queue-content" id="recent-content" style="display:none">
            <div class="queue-section">
                <h4>Recently played</h4>
                <div id="recent-list"></div>
            </div>
        </div>
    </div>

    <!-- Now Playing Full View -->
    <div class="now-playing-overlay" id="now-playing-overlay">
        <button class="np-collapse-btn" id="np-collapse-btn" onclick="toggleNpCollapse()" title="Collapse panel">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
        <button class="np-close-btn" id="np-close-btn" onclick="closeNpMobile()" title="Close">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="now-playing-bg" id="now-playing-bg">
            <div class="now-playing-bg-stars"></div>
        </div>
        <div class="now-playing-content" id="np-content-full">
            <div class="np-empty-state" id="np-empty-state">
                <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="rgba(255,45,149,0.3)" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                <p>Nothing Playing</p>
                <span>Pick a song to start listening</span>
            </div>
            <div class="np-has-song" id="np-has-song" style="display:none;">
                <div class="now-playing-art" id="np-art-container">
                    <img id="np-cover" src="" alt="">
                </div>
                <div class="now-playing-info">
                    <h2 id="np-title">Not Playing</h2>
                    <p id="np-artist">-</p>
                </div>
                <div class="now-playing-fav" id="np-fav-section">
                    <button class="np-fav-btn" id="np-fav-btn" onclick="toggleNpFav()">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    </button>
                    <button class="np-pl-btn" id="np-pl-btn" onclick="openNpPlaylistModal()" title="Add to Playlist">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>
                <div class="now-playing-controls">
                    <div class="np-progress">
                        <span id="np-current-time">0:00</span>
                        <div class="progress-bar" onclick="seek(event)"><div class="progress-bar-fill" id="np-progress-fill"></div></div>
                        <span id="np-duration">0:00</span>
                    </div>
                    <div class="np-buttons">
                        <button class="np-ctrl-btn" id="np-shuffle-btn" onclick="toggleShuffle()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                        </button>
                        <button class="np-ctrl-btn" onclick="playPrevious()">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                        </button>
                        <button class="np-play-btn" id="np-play-btn" onclick="togglePlay()">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" id="np-play-icon"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                        <button class="np-ctrl-btn" onclick="playNext()">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                        </button>
                        <button class="np-ctrl-btn" id="np-repeat-btn" onclick="toggleRepeat()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="np-lyrics-section" id="np-lyrics-section">
                    <h3>Lyrics</h3>
                    <div class="np-lyrics-content" id="np-lyrics-content"></div>
                </div>
                <div class="np-artist-section" id="np-artist-section">
                    <h3>About the artist</h3>
                    <div class="np-artist-card">
                        <img id="np-artist-img" src="" alt="">
                        <div class="np-artist-info">
                            <h4 id="np-artist-name">-</h4>
                            <p class="np-artist-listeners" id="np-artist-listeners"></p>
                        </div>
                    </div>
                    <p class="np-artist-bio" id="np-artist-bio"></p>
                    <a class="np-artist-more" id="np-artist-link" href="#" target="_blank">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        View on Spotify
                    </a>
                </div>
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
            if (name === 'recent') loadRecent();
            if (name === 'artists') loadArtists();
            if (name === 'albums') loadAlbums();
            if (isMobile() && (name === 'playlists' || name === 'home')) {
                setTimeout(function() { loadMobileExtras(); }, 300);
            }
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
                    allSongs = favSongs;
                    if (typeof accumulateKnown === 'function') accumulateKnown(favSongs);
                    renderGrid(grid, favSongs);
                } else {
                    allSongs = [];
                    grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div><h3>No favorites yet</h3><p>Songs you love will appear here</p></div>';
                }
            } catch (e) {
                grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><h3>Could not load favorites</h3></div>';
            }
        }

        async function loadUserPlaylists() {
            currentPlaylistId = null;
            document.getElementById('playlists-section-title').textContent = 'My Playlists';
            const grid = document.getElementById('user-playlists-grid');
            grid._songs = null;
            grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading playlists...</h3></div>';
            try {
                const res = await fetch(API_BASE + '/playlists.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success && data.data.length > 0) {
                    grid.innerHTML = data.data.map(p => {
                        var covers = (p.track_covers || []).filter(c => c).slice(0, 4);
                        var coverHtml;
                        if (covers.length === 0) {
                            coverHtml = '<div class="playlist-cover-gradient"><svg width="32" height="32" viewBox="0 0 24 24" fill="white"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg></div>';
                        } else if (covers.length === 1) {
                            coverHtml = '<img src="' + covers[0] + '" alt="" style="width:100%;height:100%;object-fit:cover;">';
                        } else {
                            coverHtml = '<div class="playlist-collage">' + covers.map(c => '<img src="' + c + '" alt="">').join('') + '</div>';
                        }
                        return '<div class="song-card playlist-card">' +
                            '<div class="song-card-cover" onclick="loadUserPlaylistTracks(\'' + p.id + '\', \'' + esc(p.name).replace(/'/g, "\\'") + '\')" style="cursor:pointer">' +
                                coverHtml +
                                '<div class="play-overlay"><div class="play-btn-circle"><svg width="20" height="20" viewBox="0 0 24 24" fill="#000"><path d="M8 5v14l11-7z"/></svg></div></div>' +
                            '</div>' +
                            '<h4 class="song-card-title" onclick="loadUserPlaylistTracks(\'' + p.id + '\', \'' + esc(p.name).replace(/'/g, "\\'") + '\')" style="cursor:pointer">' + esc(p.name) + '</h4>' +
                            '<p class="song-card-artist">' + (p.track_count || 0) + ' songs</p>' +
                            '<button class="playlist-delete-btn" onclick="event.stopPropagation(); deletePlaylist(\'' + p.id + '\', \'' + esc(p.name).replace(/'/g, "\\'") + '\')" title="Delete playlist">' +
                                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>' +
                            '</button>' +
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
            currentPlaylistId = playlistId;
            document.getElementById('playlists-section-title').textContent = playlistName;
            const grid = document.getElementById('user-playlists-grid');
            grid.innerHTML = '<div class="empty-state"><div class="loading-spinner"></div><h3 style="margin-top:1rem">Loading ' + esc(playlistName) + '...</h3></div>';
            try {
                const res = await fetch(API_BASE + '/playlists.php?id=' + playlistId, { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success && data.tracks && data.tracks.length > 0) {
                    const plSongs = data.tracks.map(t => ({
                        id: t.track_id, title: t.title, artist: t.artist, album: t.album,
                        cover_image: t.cover_image, file_path: t.file_path, track_url: t.track_url,
                        duration_text: t.duration_text, source: 'spotify', _track_id: t.id
                    }));
                    allSongs = plSongs;
                    grid.innerHTML = '<div style="margin-bottom:1rem;"><button class="tab-btn" onclick="currentPlaylistId=null; loadUserPlaylists()" style="font-size:0.8rem;">&larr; Back to Playlists</button> <span style="color:var(--text-secondary);font-size:0.85rem;margin-left:0.5rem;">' + esc(playlistName) + ' &middot; ' + plSongs.length + ' songs</span></div>';
                    renderGrid(grid, plSongs);
                } else {
                    currentPlaylistId = null;
                    grid.innerHTML = '<div style="margin-bottom:1rem;"><button class="tab-btn" onclick="currentPlaylistId=null; loadUserPlaylists()" style="font-size:0.8rem;">&larr; Back to Playlists</button></div><div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div><h3>No tracks yet</h3><p>Add songs from the Listen Now section</p></div>';
                }
            } catch (e) {
                currentPlaylistId = null;
                grid.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><h3>Could not load playlist</h3></div>';
            }
        }

        async function removeFromPlaylist(trackId) {
            if (!currentPlaylistId) return;
            try {
                const res = await fetch(API_BASE + '/playlist_tracks.php?playlist_id=' + currentPlaylistId + '&track_id=' + encodeURIComponent(trackId), {
                    method: 'DELETE',
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Removed from playlist');
                    var plName = document.querySelector('#user-playlists-grid .tab-btn + span')?.textContent?.split(' · ')[0] || 'Playlist';
                    loadUserPlaylistTracks(currentPlaylistId, plName);
                } else {
                    showToast(data.message || 'Failed to remove');
                }
            } catch {
                showToast('Failed to remove from playlist');
            }
        }

        async function deletePlaylist(playlistId, playlistName) {
            if (!confirm('Delete playlist "' + playlistName + '"?')) return;
            try {
                const res = await fetch(API_BASE + '/playlists.php?id=' + playlistId, {
                    method: 'DELETE',
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Playlist deleted');
                    loadUserPlaylists();
                } else {
                    showToast(data.message || 'Failed to delete');
                }
            } catch {
                showToast('Failed to delete playlist');
            }
        }
    </script>
</body>
</html>