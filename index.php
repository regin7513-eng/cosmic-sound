<?php require_once __DIR__ . '/config/session.php'; if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ginz Song - Music Streaming</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 40%, rgba(255, 110, 180, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 60%, rgba(125, 211, 252, 0.1) 0%, transparent 50%);
            animation: heroGlow 10s ease-in-out infinite alternate;
        }

        @keyframes heroGlow {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-5%, 5%); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 600px;
        }

        .hero-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: var(--radius-pill);
            color: var(--primary);
            font-family: var(--font-heading);
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            box-shadow: 4px 4px 0 var(--neo-shadow);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 700;
            letter-spacing: -0.04em;
            line-height: 1.1;
            margin-bottom: 1.25rem;
        }

        .hero h1 span {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 480px;
            margin: 0 auto 2.5rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero .btn-primary {
            width: auto;
            padding: 1rem 2.5rem;
            font-size: 1rem;
        }

        .hero .btn-ghost {
            padding: 1rem 2rem;
            font-size: 1rem;
            background: var(--bg-card);
            color: var(--text);
        }

        .hero-features {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 4rem;
            flex-wrap: wrap;
        }

        .hero-feature {
            text-align: center;
            padding: 1.25rem;
            background: var(--bg-card);
            border: 2px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: 4px 4px 0 var(--neo-shadow);
            min-width: 140px;
            transition: all 0.2s ease;
        }

        .hero-feature:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 rgba(255, 110, 180, 0.3);
            border-color: rgba(255, 110, 180, 0.4);
        }

        .hero-feature-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .hero-feature h4 {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .hero-feature p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0;
        }

        .hero-gradient {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 200px;
            background: linear-gradient(to top, var(--bg), transparent);
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-gradient"></div>
        <div class="hero-content">
            <div class="hero-badge">Music Streaming</div>
            <h1>Your Music.<br><span>Your Universe.</span></h1>
            <p>Stream millions of songs, create playlists, and discover new music all in one place.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">Get Started Free</a>
                <a href="login.php" class="btn btn-ghost">Sign In</a>
            </div>
            <div class="hero-features">
                <div class="hero-feature">
                    <div class="hero-feature-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div>
                    <h4>Unlimited Streaming</h4>
                    <p>Millions of tracks</p>
                </div>
                <div class="hero-feature">
                    <div class="hero-feature-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
                    <h4>Smart Search</h4>
                    <p>Find any song</p>
                </div>
                <div class="hero-feature">
                    <div class="hero-feature-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
                    <h4>Save Favorites</h4>
                    <p>Your collection</p>
                </div>
                <div class="hero-feature">
                    <div class="hero-feature-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg></div>
                    <h4>Curated Playlists</h4>
                    <p>Mood-based mixes</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>