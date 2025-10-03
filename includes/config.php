<?php
// Scribble Game - Configuration File

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

// Game Configuration
define('MAX_PLAYERS_PER_ROOM', 12);
define('DEFAULT_ROUNDS', 3);
define('DEFAULT_TIMER', 80);
define('MIN_TIMER', 30);
define('MAX_TIMER', 120);
define('MAX_ROUNDS', 10);

// Scoring Configuration
define('POINTS_MIN', 10);
define('POINTS_MAX', 100);
define('DRAWER_BONUS', 5);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('INACTIVE_TIMEOUT', 300); // 5 minutes

// Polling Configuration
define('POLL_INTERVAL', 1000); // 1 second in milliseconds
define('MAX_POLL_MESSAGES', 50); // Max messages to return per poll

// Room cleanup
define('ROOM_CLEANUP_TIME', 1800); // 30 minutes of inactivity

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('UTC');

// Security
define('ENABLE_CSRF_PROTECTION', true);
define('SESSION_COOKIE_SECURE', true); // Set to true if using HTTPS
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Lax');

// Site URL (update this with your domain)
define('SITE_URL', 'https://scribble.corridors.in');

// Default word list (fallback)
define('DEFAULT_WORDS', json_encode([
    "apple", "banana", "cat", "dog", "elephant", "flower", "guitar", "house", "island", "jacket",
    "keyboard", "laptop", "mountain", "notebook", "ocean", "piano", "queen", "rabbit", "sunset", "tree",
    "umbrella", "violin", "waterfall", "xylophone", "yacht", "zebra", "airplane", "bicycle", "camera", "dragon",
    "eagle", "forest", "garden", "hospital", "internet", "jungle", "kangaroo", "library", "mirror", "necklace",
    "octopus", "penguin", "quilt", "rocket", "sandwich", "telescope", "unicorn", "volcano", "wizard", "diamond",
    "football", "hamburger", "lighthouse", "mushroom", "rainbow", "snowman", "butterfly", "helicopter", "pineapple", "sunflower"
]));
