<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';

$session = new SessionManager();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scribble - Multiplayer Drawing Game</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Draw, guess, and win! A fun multiplayer drawing and guessing game to play with friends.">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Scribble">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/assets/icons/icon-192x192.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/icons/icon-72x72.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .game-bg {
            background: #f8fafc;
        }
        
        /* Dark Mode Styles */
        body.dark-mode {
            background: #1a202c;
        }
        
        body.dark-mode .game-bg {
            background: #1a202c;
        }
        
        body.dark-mode .bg-white {
            background: #2d3748;
        }
        
        body.dark-mode .text-gray-800 {
            color: #e2e8f0;
        }
        
        body.dark-mode .text-gray-700 {
            color: #cbd5e0;
        }
        
        body.dark-mode .text-gray-600 {
            color: #a0aec0;
        }
        
        body.dark-mode .text-gray-500 {
            color: #718096;
        }
        
        body.dark-mode .border-gray-200 {
            border-color: #4a5568;
        }
        
        body.dark-mode .border-gray-100 {
            border-color: #4a5568;
        }
        
        body.dark-mode .bg-indigo-50 {
            background: #2d3748;
        }
        
        body.dark-mode .bg-blue-50,
        body.dark-mode .bg-emerald-50,
        body.dark-mode .bg-purple-50 {
            background: #2d3748;
        }
        
        body.dark-mode canvas {
            background: #374151;
            border-color: #4a5568;
        }
        
        canvas {
            cursor: crosshair;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .chat-message {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }
        
        .player-card {
            transition: all 0.2s ease;
        }
        
        .player-card:hover {
            transform: translateX(2px);
        }
        
        .correct-guess {
            background-color: #10b981 !important;
            color: white !important;
        }
        
        .close-guess {
            background-color: #f59e0b !important;
            color: white !important;
        }

        .drawer-message {
            background-color: #8b5cf6 !important;
            color: white !important;
        }

        .share-btn {
            transition: all 0.2s ease;
        }

        .share-btn:hover {
            transform: scale(1.05);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        @media (max-width: 1024px) {
            .game-layout {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body class="game-bg min-h-screen">
    <!-- Theme Switcher and Navigation -->
    <div class="fixed top-4 right-4 z-50 flex gap-2">
        <button onclick="toggleTheme()" class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg hover:shadow-xl transition">
            <i id="theme-icon" class="fas fa-moon text-gray-700 dark:text-gray-300"></i>
        </button>
        <button onclick="showLobbyBrowser()" class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg hover:shadow-xl transition" title="Browse Rooms">
            <i class="fas fa-list text-gray-700 dark:text-gray-300"></i>
        </button>
        <button onclick="showStatsModal()" class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg hover:shadow-xl transition" title="Statistics">
            <i class="fas fa-chart-bar text-gray-700 dark:text-gray-300"></i>
        </button>
        <button onclick="showAchievementsModal()" class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg hover:shadow-xl transition" title="Achievements">
            <i class="fas fa-trophy text-gray-700 dark:text-gray-300"></i>
        </button>
    </div>

    <div id="lobby-screen" class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 max-w-md w-full">
            <div class="text-center mb-8">
                <h1 class="text-5xl font-bold gradient-bg bg-clip-text text-transparent mb-2">Scribble</h1>
                <p class="text-gray-500 text-sm">Draw, Guess, and Win!</p>
            </div>
            
            <div id="menu-options">
                <div class="space-y-4">
                    <input type="text" id="username-input" placeholder="Enter your username" 
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition"
                        maxlength="15">
                    
                    <button onclick="showCreateRoom()" class="w-full btn-primary text-white font-medium py-3 rounded-lg">
                        Create Room
                    </button>
                    
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-xs">
                            <span class="px-2 bg-white text-gray-400">or</span>
                        </div>
                    </div>
                    
                    <input type="text" id="room-code-input" placeholder="Enter room code" 
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none uppercase transition"
                        maxlength="6">
                    
                    <button onclick="joinRoom()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-3 rounded-lg transition">
                        Join Room
                    </button>
                </div>
            </div>
            
            <div id="create-room-options" class="hidden space-y-4">
                <button onclick="showMenu()" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium mb-4">
                    ← Back
                </button>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of Rounds</label>
                    <input type="number" id="rounds-input" value="3" min="1" max="10" 
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Timer Duration (seconds)</label>
                    <input type="number" id="timer-input" value="80" min="30" max="120" 
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Custom Words (comma separated)</label>
                    <textarea id="custom-words-input" placeholder="pizza, computer, rainbow..." 
                        class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition"
                        rows="3"></textarea>
                </div>
                
                <button onclick="createRoom()" class="w-full btn-primary text-white font-medium py-3 rounded-lg">
                    Create Room
                </button>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100">
                <!-- App Store Download Buttons -->
                <div class="flex flex-col gap-3 mb-4">
                    <a href="#" id="playstore-link" class="block">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play" class="h-12 mx-auto">
                    </a>
                    <a href="#" id="appstore-link" class="block">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="Download on the App Store" class="h-12 mx-auto">
                    </a>
                </div>
                
                <!-- Install PWA Button (for browsers that support it) -->
                <button id="install-pwa-btn" class="hidden w-full bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 text-white font-medium py-3 rounded-lg mb-4 transition">
                    <i class="fas fa-download mr-2"></i>Install App
                </button>
                
                <div class="text-center">
                    <a href="https://rzp.io/rzp/scribblecorridors" target="_blank" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-amber-600 transition">
                        <i class="fas fa-coffee"></i>
                        <span>Buy me a coffee</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="waiting-room" class="hidden min-h-screen p-4">
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Room: <span id="room-code-display" class="gradient-bg bg-clip-text text-transparent"></span></h2>
                        <p class="text-gray-500 text-sm">Share this code with friends!</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="shareWhatsApp()" class="share-btn bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                        </button>
                        <button onclick="copyLink()" class="share-btn bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-link mr-1"></i> Copy Link
                        </button>
                    </div>
                </div>
                
                <button id="start-game-btn" onclick="startGame()" class="btn-primary text-white font-medium px-6 py-3 rounded-lg hidden mb-6">
                    Start Game
                </button>

                <div class="bg-indigo-50 rounded-xl p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Players in Room</h3>
                    <div id="waiting-room-players" class="space-y-2"></div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-blue-50 rounded-xl p-4">
                        <p class="text-gray-600 text-xs font-medium uppercase">Rounds</p>
                        <p id="rounds-display" class="text-3xl font-bold text-blue-600">3</p>
                    </div>
                    <div class="bg-emerald-50 rounded-xl p-4">
                        <p class="text-gray-600 text-xs font-medium uppercase">Timer</p>
                        <p id="timer-display" class="text-3xl font-bold text-emerald-600">80s</p>
                    </div>
                    <div class="bg-purple-50 rounded-xl p-4">
                        <p class="text-gray-600 text-xs font-medium uppercase">Players</p>
                        <p id="player-count-display" class="text-3xl font-bold text-purple-600">0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="word-selection" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Choose a Word to Draw</h2>
            <div id="word-choices" class="space-y-3"></div>
        </div>
    </div>

    <div id="game-screen" class="hidden min-h-screen p-4">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-4">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                    <div>
                        <p class="text-xs text-gray-500 font-medium uppercase">Round <span id="current-round">1</span> of <span id="total-rounds">3</span></p>
                        <h2 id="word-hint" class="text-3xl font-bold text-gray-800">_ _ _ _ _</h2>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 font-medium uppercase">Time Left</p>
                        <p id="timer-countdown" class="text-4xl font-bold text-indigo-600">80</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium uppercase">Drawing</p>
                        <p id="current-drawer" class="text-xl font-bold text-indigo-600">Waiting...</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 game-layout">
                <div class="lg:col-span-1 order-1 lg:order-1">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 sticky top-4">
                        <h3 class="text-lg font-semibold mb-3 text-gray-800">Players</h3>
                        <div id="player-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-4 order-2 lg:order-2">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4">
                        <div id="drawing-tools" class="hidden flex gap-2 mb-4 flex-wrap items-center">
                            <div class="flex gap-1.5">
                                <button onclick="setColor('#000000')" class="w-8 h-8 rounded-full bg-black border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#ffffff')" class="w-8 h-8 rounded-full bg-white border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#ef4444')" class="w-8 h-8 rounded-full bg-red-500 border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#f97316')" class="w-8 h-8 rounded-full bg-orange-500 border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#eab308')" class="w-8 h-8 rounded-full bg-yellow-500 border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#22c55e')" class="w-8 h-8 rounded-full bg-green-500 border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#3b82f6')" class="w-8 h-8 rounded-full bg-blue-500 border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#8b5cf6')" class="w-8 h-8 rounded-full bg-violet-500 border border-gray-300 hover:scale-110 transition"></button>
                                <button onclick="setColor('#ec4899')" class="w-8 h-8 rounded-full bg-pink-500 border border-gray-300 hover:scale-110 transition"></button>
                            </div>
                            
                            <div class="flex gap-1.5 items-center">
                                <span class="text-xs font-medium text-gray-600">Size:</span>
                                <button onclick="setBrushSize(2)" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-medium transition">S</button>
                                <button onclick="setBrushSize(5)" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-medium transition">M</button>
                                <button onclick="setBrushSize(10)" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-medium transition">L</button>
                                <button onclick="setBrushSize(20)" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded text-xs font-medium transition">XL</button>
                            </div>
                            
                            <button onclick="clearCanvas()" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition">
                                Clear
                            </button>
                        </div>
                        
                        <canvas id="drawing-canvas" width="800" height="600" class="w-full"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-1 order-3 lg:order-3">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 sticky top-4">
                        <h3 class="text-lg font-semibold mb-3 text-gray-800">Chat</h3>
                        <div id="chat-messages" class="h-80 overflow-y-auto mb-3 space-y-2 bg-gray-50 rounded-lg p-3"></div>
                        <div class="flex flex-col gap-2">
                            <input type="text" id="chat-input" placeholder="Type your guess..." 
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none text-sm transition">
                            <button onclick="sendMessage()" class="btn-primary text-white font-medium px-4 py-2 rounded-lg text-sm">
                                Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="game-ended" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 max-w-md w-full mx-4">
            <h2 class="text-3xl font-bold text-center mb-6 text-gray-800">Game Over!</h2>
            <div id="final-scores" class="space-y-3 mb-6"></div>
            <button onclick="location.reload()" class="w-full btn-primary text-white font-medium py-3 rounded-lg">
                Play Again
            </button>
            <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                <a href="https://www.buymeacoffee.com/yourname" target="_blank" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-amber-600 transition">
                    <i class="fas fa-coffee"></i>
                    <span>Enjoyed the game? Buy me a coffee!</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Lobby Browser Modal -->
    <div id="lobby-browser-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Available Rooms</h2>
                <button onclick="closeLobbyBrowser()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="rooms-list" class="space-y-3"></div>
        </div>
    </div>

    <!-- Statistics Modal -->
    <div id="stats-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Your Statistics</h2>
                <button onclick="closeStatsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="stats-content" class="space-y-4"></div>
        </div>
    </div>

    <!-- Achievements Modal -->
    <div id="achievements-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 max-w-md w-full max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Achievements</h2>
                <button onclick="closeAchievementsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="achievements-content" class="space-y-3"></div>
        </div>
    </div>

    <script src="assets/js/game.js"></script>
    
    <script>
        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('Service Worker registered:', registration.scope);
                    })
                    .catch((error) => {
                        console.error('Service Worker registration failed:', error);
                    });
            });
        }

        // PWA Install Prompt
        let deferredPrompt;
        const installBtn = document.getElementById('install-pwa-btn');

        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent default browser prompt
            e.preventDefault();
            deferredPrompt = e;
            
            // Show custom install button
            installBtn.classList.remove('hidden');
        });

        installBtn?.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            
            // Show the install prompt
            deferredPrompt.prompt();
            
            // Wait for user response
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`User response: ${outcome}`);
            
            // Clear the stored prompt
            deferredPrompt = null;
            installBtn.classList.add('hidden');
        });

        // Update app store links with actual URLs when published
        // Replace these with your actual app store URLs
        const playstoreLink = document.getElementById('playstore-link');
        const appstoreLink = document.getElementById('appstore-link');
        
        // Hide links until apps are published
        if (playstoreLink && playstoreLink.href === '#') {
            playstoreLink.style.display = 'none';
        }
        if (appstoreLink && appstoreLink.href === '#') {
            appstoreLink.style.display = 'none';
        }
    </script>
</body>
</html>
