// Scribble Game - JavaScript with AJAX Polling (replaces Socket.IO)

let canvas, ctx;
let isDrawing = false;
let currentColor = '#000000';
let brushSize = 5;
let roomCode = '';
let username = '';
let isDrawer = false;
let isHost = false;
let currentWord = '';
let lastUpdateId = 0;
let lastChatId = 0;
let pollInterval = null;
let drawingQueue = [];
let sendingDrawing = false;

// CSRF Token Management
let csrfToken = '';

// Get CSRF Token on page load
async function getCSRFToken() {
    try {
        const response = await fetch('api/csrf_token.php');
        const result = await response.json();
        if (result.token) {
            csrfToken = result.token;
        }
    } catch (error) {
        console.error('Failed to get CSRF token:', error);
    }
}

// Initialize CSRF token on page load
document.addEventListener('DOMContentLoaded', () => {
    getCSRFToken();
    
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        document.getElementById('theme-icon')?.classList.remove('fa-moon');
        document.getElementById('theme-icon')?.classList.add('fa-sun');
    }
});

// API Helper Function
async function apiRequest(endpoint, data = null) {
    try {
        const options = {
            method: data ? 'POST' : 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        // Add CSRF token to POST requests
        if (data) {
            data._csrf_token = csrfToken;
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(`api/${endpoint}`, options);
        const result = await response.json();
        
        if (result.error) {
            alert(result.error);
            return null;
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        alert('Connection error. Please try again.');
        return null;
    }
}

// Share Functions
function shareWhatsApp() {
    const url = window.location.origin;
    const text = `Join my Scribble game! Room Code: ${roomCode}\n\n${url}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
}

function copyLink() {
    const url = window.location.origin;
    const text = `Join my Scribble game! Room Code: ${roomCode}\n\n${url}`;
    navigator.clipboard.writeText(text).then(() => {
        alert('Link copied to clipboard!');
    }).catch(() => {
        prompt('Copy this link:', text);
    });
}

// UI Functions
function showCreateRoom() {
    document.getElementById('menu-options').classList.add('hidden');
    document.getElementById('create-room-options').classList.remove('hidden');
}

function showMenu() {
    document.getElementById('menu-options').classList.remove('hidden');
    document.getElementById('create-room-options').classList.add('hidden');
}

// Create Room
async function createRoom() {
    username = document.getElementById('username-input').value.trim();
    if (!username) {
        alert('Please enter a username');
        return;
    }
    
    const rounds = parseInt(document.getElementById('rounds-input').value);
    const timer = parseInt(document.getElementById('timer-input').value);
    const customWordsText = document.getElementById('custom-words-input').value.trim();
    const customWords = customWordsText ? customWordsText.split(',').map(w => w.trim().toLowerCase()).filter(w => w) : [];
    
    const result = await apiRequest('create_room.php', {
        username,
        rounds,
        timer,
        custom_words: customWords
    });
    
    if (result) {
        roomCode = result.room_code;
        isHost = result.is_host;
        enterWaitingRoom(result);
        if (isHost) {
            document.getElementById('start-game-btn').classList.remove('hidden');
        }
    }
}

// Join Room
async function joinRoom() {
    username = document.getElementById('username-input').value.trim();
    const code = document.getElementById('room-code-input').value.trim().toUpperCase();
    
    if (!username) {
        alert('Please enter a username');
        return;
    }
    
    if (!code) {
        alert('Please enter a room code');
        return;
    }
    
    const result = await apiRequest('join_room.php', {
        username,
        room_code: code
    });
    
    if (result) {
        roomCode = result.room_code;
        isHost = result.is_host;
        enterWaitingRoom(result);
        if (isHost) {
            document.getElementById('start-game-btn').classList.remove('hidden');
        }
    }
}

// Enter Waiting Room
function enterWaitingRoom(data) {
    document.getElementById('lobby-screen').classList.add('hidden');
    document.getElementById('waiting-room').classList.remove('hidden');
    document.getElementById('room-code-display').textContent = data.room_code;
    document.getElementById('rounds-display').textContent = data.settings.rounds;
    document.getElementById('timer-display').textContent = data.settings.timer + 's';
    updateWaitingRoomPlayers(data.players);
    
    // Start polling for updates
    startPolling();
}

// Update Waiting Room Players
function updateWaitingRoomPlayers(players) {
    const container = document.getElementById('waiting-room-players');
    container.innerHTML = '';
    
    players.forEach((player, index) => {
        const playerDiv = document.createElement('div');
        playerDiv.className = 'bg-white rounded-lg p-3 flex items-center justify-between';
        playerDiv.innerHTML = `
            <span class="font-semibold">${index + 1}. ${player.username}</span>
            ${index === 0 ? '<span class="text-xs bg-purple-500 text-white px-2 py-1 rounded">Host</span>' : ''}
        `;
        container.appendChild(playerDiv);
    });
    
    document.getElementById('player-count-display').textContent = players.length;
}

// Start Game
async function startGame() {
    await apiRequest('start_game.php');
}

// Polling for Updates
function startPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
    
    pollInterval = setInterval(pollForUpdates, 1000);
    pollForUpdates(); // Initial poll
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

async function pollForUpdates() {
    const result = await apiRequest(`poll_updates.php?last_id=${lastUpdateId}&last_chat_id=${lastChatId}`);
    
    if (!result) return;
    
    // Process updates
    if (result.updates && result.updates.length > 0) {
        result.updates.forEach(update => {
            handleUpdate(update);
            lastUpdateId = Math.max(lastUpdateId, update.id);
        });
    }
    
    // Process chat messages
    if (result.chat_messages && result.chat_messages.length > 0) {
        result.chat_messages.forEach(msg => {
            addChatMessage(msg.username, msg.message, msg.message_type);
            lastChatId = Math.max(lastChatId, msg.id);
        });
    }
    
    // Update game state
    if (result.game_state) {
        updateGameState(result.game_state);
    }
}

// Handle Update
function handleUpdate(update) {
    const data = typeof update.update_data === 'string' ? JSON.parse(update.update_data) : update.update_data;
    
    switch (update.update_type) {
        case 'player_joined':
            // Player list will be updated in game_state
            break;
            
        case 'game_started':
            document.getElementById('waiting-room').classList.add('hidden');
            document.getElementById('game-screen').classList.remove('hidden');
            initCanvas();
            break;
            
        case 'round_starting':
            document.getElementById('current-drawer').textContent = data.drawer;
            document.getElementById('current-round').textContent = data.round;
            addChatMessage('System', `${data.drawer} is choosing a word...`, 'system');
            
            // If I'm the drawer, get words
            if (data.drawer_id && username) {
                setTimeout(async () => {
                    const wordsResult = await apiRequest('get_words.php');
                    if (wordsResult && wordsResult.words) {
                        showWordSelection(wordsResult.words);
                    }
                }, 500);
            }
            break;
            
        case 'round_started':
            clearCanvasLocal();
            addChatMessage('System', 'Round started!', 'system');
            break;
            
        case 'draw':
            drawOnCanvas(data);
            break;
            
        case 'correct_guess':
            addChatMessage('System', `${data.username} guessed correctly! +${data.points} points`, 'correct');
            break;
            
        case 'round_ended':
            isDrawer = false;
            document.getElementById('drawing-tools').classList.add('hidden');
            addChatMessage('System', `Round ended! The word was: ${data.word}`, 'system');
            break;
            
        case 'game_ended':
            stopPolling();
            showGameEnded(data.final_scores);
            break;
    }
}

// Update Game State
function updateGameState(state) {
    if (state.status === 'playing') {
        // Update timer
        document.getElementById('timer-countdown').textContent = state.time_remaining;
        
        // Update hint
        if (state.hint) {
            document.getElementById('word-hint').textContent = state.hint;
        }
        
        // Update drawer
        if (state.drawer) {
            document.getElementById('current-drawer').textContent = state.drawer;
        }
        
        // Update round
        document.getElementById('current-round').textContent = state.current_round;
        document.getElementById('total-rounds').textContent = state.total_rounds;
        
        // Check if I'm drawer
        if (state.is_drawer && !isDrawer) {
            isDrawer = true;
            document.getElementById('drawing-tools').classList.remove('hidden');
        } else if (!state.is_drawer && isDrawer) {
            isDrawer = false;
            document.getElementById('drawing-tools').classList.add('hidden');
        }
        
        // Update players
        if (state.players) {
            updatePlayerList(state.players);
        }
    }
}

// Word Selection
function showWordSelection(words) {
    const container = document.getElementById('word-choices');
    container.innerHTML = '';
    
    words.forEach(word => {
        const button = document.createElement('button');
        button.className = 'w-full btn-primary text-white font-semibold py-4 rounded-lg text-xl';
        button.textContent = word;
        button.onclick = () => selectWord(word);
        container.appendChild(button);
    });
    
    document.getElementById('word-selection').classList.remove('hidden');
}

async function selectWord(word) {
    document.getElementById('word-selection').classList.add('hidden');
    currentWord = word;
    await apiRequest('choose_word.php', { word });
}

// Canvas Functions
function initCanvas() {
    canvas = document.getElementById('drawing-canvas');
    ctx = canvas.getContext('2d');
    
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Touch support
    canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousedown', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    });
    
    canvas.addEventListener('touchmove', (e) => {
        e.preventDefault();
        const touch = e.touches[0];
        const mouseEvent = new MouseEvent('mousemove', {
            clientX: touch.clientX,
            clientY: touch.clientY
        });
        canvas.dispatchEvent(mouseEvent);
    });
    
    canvas.addEventListener('touchend', (e) => {
        e.preventDefault();
        const mouseEvent = new MouseEvent('mouseup', {});
        canvas.dispatchEvent(mouseEvent);
    });
}

function startDrawing(e) {
    if (!isDrawer) return;
    
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX - rect.left) * (canvas.width / rect.width);
    const y = (e.clientY - rect.top) * (canvas.height / rect.height);
    
    ctx.beginPath();
    ctx.moveTo(x, y);
    
    // Add to queue
    drawingQueue.push({
        type: 'start',
        x, y,
        color: currentColor,
        size: brushSize
    });
}

function draw(e) {
    if (!isDrawing || !isDrawer) return;
    
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX - rect.left) * (canvas.width / rect.width);
    const y = (e.clientY - rect.top) * (canvas.height / rect.height);
    
    ctx.lineTo(x, y);
    ctx.strokeStyle = currentColor;
    ctx.lineWidth = brushSize;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.stroke();
    
    // Add to queue
    drawingQueue.push({
        type: 'draw',
        x, y,
        color: currentColor,
        size: brushSize
    });
    
    // Send periodically
    if (drawingQueue.length >= 5) {
        sendDrawingData();
    }
}

function stopDrawing() {
    if (isDrawing && isDrawer) {
        drawingQueue.push({ type: 'stop' });
        sendDrawingData();
    }
    isDrawing = false;
    ctx.beginPath();
}

async function sendDrawingData() {
    if (sendingDrawing || drawingQueue.length === 0) return;
    
    sendingDrawing = true;
    const data = [...drawingQueue];
    drawingQueue = [];
    
    await apiRequest('send_drawing.php', { stroke_data: data });
    sendingDrawing = false;
}

function drawOnCanvas(strokes) {
    if (!Array.isArray(strokes)) {
        strokes = [strokes];
    }
    
    strokes.forEach(stroke => {
        if (stroke.type === 'stop' || stroke.type === 'start') {
            ctx.beginPath();
            if (stroke.type === 'start') {
                ctx.moveTo(stroke.x, stroke.y);
            }
        } else if (stroke.type === 'draw') {
            ctx.lineTo(stroke.x, stroke.y);
            ctx.strokeStyle = stroke.color;
            ctx.lineWidth = stroke.size;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.stroke();
        }
    });
}

function setColor(color) {
    currentColor = color;
}

function setBrushSize(size) {
    brushSize = size;
}

async function clearCanvas() {
    if (!isDrawer) return;
    clearCanvasLocal();
    // TODO: Send clear command to server
}

function clearCanvasLocal() {
    if (ctx) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.beginPath();
    }
}

// Chat Functions
async function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    await apiRequest('send_message.php', { message });
    input.value = '';
}

document.getElementById('chat-input')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

function addChatMessage(username, message, type = 'normal') {
    const container = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message px-3 py-2 rounded-lg';
    
    if (type === 'system') {
        messageDiv.className += ' bg-blue-100 text-blue-800';
    } else if (type === 'correct') {
        messageDiv.className += ' correct-guess';
    } else if (type === 'close') {
        messageDiv.className += ' close-guess';
    } else if (type === 'drawer') {
        messageDiv.className += ' drawer-message';
    } else if (type === 'already_guessed') {
        messageDiv.className += ' bg-gray-200 text-gray-600';
    } else {
        messageDiv.className += ' bg-gray-100';
    }
    
    messageDiv.innerHTML = `<strong>${username}:</strong> ${message}`;
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
}

// Player List
function updatePlayerList(players) {
    const container = document.getElementById('player-list');
    container.innerHTML = '';
    
    players.sort((a, b) => b.score - a.score);
    
    players.forEach((player, index) => {
        const playerDiv = document.createElement('div');
        playerDiv.className = 'player-card bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg p-3';
        
        let badge = '';
        if (player.is_drawer) {
            badge = '<span class="text-xs bg-purple-500 text-white px-2 py-1 rounded ml-2">Drawing</span>';
        } else if (player.guessed) {
            badge = '<span class="text-xs bg-green-500 text-white px-2 py-1 rounded ml-2">✓ Guessed</span>';
        }
        
        playerDiv.innerHTML = `
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <span class="font-semibold">${index + 1}. ${player.username}</span>
                    ${badge}
                </div>
                <span class="text-purple-600 font-bold">${player.score}</span>
            </div>
        `;
        container.appendChild(playerDiv);
    });
}

// Game Ended
function showGameEnded(finalScores) {
    document.getElementById('game-ended').classList.remove('hidden');
    const container = document.getElementById('final-scores');
    container.innerHTML = '';
    
    finalScores.forEach((player, index) => {
        const scoreDiv = document.createElement('div');
        scoreDiv.className = 'flex justify-between items-center p-3 rounded-lg';
        
        if (index === 0) {
            scoreDiv.className += ' bg-gradient-to-r from-yellow-400 to-yellow-500 text-white';
            scoreDiv.innerHTML = `
                <span class="font-bold text-lg">🏆 ${player.username}</span>
                <span class="font-bold text-lg">${player.score} pts</span>
            `;
        } else if (index === 1) {
            scoreDiv.className += ' bg-gradient-to-r from-gray-300 to-gray-400 text-white';
            scoreDiv.innerHTML = `
                <span class="font-semibold">🥈 ${player.username}</span>
                <span class="font-semibold">${player.score} pts</span>
            `;
        } else if (index === 2) {
            scoreDiv.className += ' bg-gradient-to-r from-orange-400 to-orange-500 text-white';
            scoreDiv.innerHTML = `
                <span class="font-semibold">🥉 ${player.username}</span>
                <span class="font-semibold">${player.score} pts</span>
            `;
        } else {
            scoreDiv.className += ' bg-gray-100';
            scoreDiv.innerHTML = `
                <span>${index + 1}. ${player.username}</span>
                <span>${player.score} pts</span>
            `;
        }
        
        container.appendChild(scoreDiv);
    });
}

// Theme Switcher
function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById('theme-icon');
    
    if (body.classList.contains('dark-mode')) {
        body.classList.remove('dark-mode');
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        localStorage.setItem('theme', 'light');
    } else {
        body.classList.add('dark-mode');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
        localStorage.setItem('theme', 'dark');
    }
}

// Theme initialization moved to CSRF token initialization

// Lobby Browser
async function showLobbyBrowser() {
    document.getElementById('lobby-browser-modal').classList.remove('hidden');
    
    const rooms = await apiRequest('lobby.php');
    
    const roomsList = document.getElementById('rooms-list');
    roomsList.innerHTML = '';
    
    if (!rooms || rooms.length === 0) {
        roomsList.innerHTML = '<p class="text-gray-500 text-center py-8">No rooms available</p>';
        return;
    }
    
    rooms.forEach(room => {
        const roomDiv = document.createElement('div');
        roomDiv.className = 'bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition cursor-pointer';
        roomDiv.onclick = () => {
            document.getElementById('room-code-input').value = room.room_code;
            closeLobbyBrowser();
        };
        
        roomDiv.innerHTML = `
            <div class="flex justify-between items-center">
                <div>
                    <p class="font-semibold text-gray-800">Room ${room.room_code}</p>
                    <p class="text-sm text-gray-600">Host: ${room.host || 'Unknown'}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">${room.players}/12 players</p>
                    <p class="text-sm text-gray-600">${room.num_rounds} rounds</p>
                </div>
            </div>
        `;
        
        roomsList.appendChild(roomDiv);
    });
}

function closeLobbyBrowser() {
    document.getElementById('lobby-browser-modal').classList.add('hidden');
}

// Statistics Modal
async function showStatsModal() {
    document.getElementById('stats-modal').classList.remove('hidden');
    
    const stats = await apiRequest('stats.php');
    const content = document.getElementById('stats-content');
    
    if (!stats || Object.keys(stats).length === 0) {
        content.innerHTML = '<p class="text-gray-500 text-center">No statistics available yet. Play some games!</p>';
        return;
    }
    
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-blue-600">${stats.total_games || 0}</p>
                <p class="text-sm text-gray-600">Games Played</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-green-600">${stats.total_wins || 0}</p>
                <p class="text-sm text-gray-600">Games Won</p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-purple-600">${stats.total_points || 0}</p>
                <p class="text-sm text-gray-600">Total Points</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-orange-600">${stats.total_correct_guesses || 0}</p>
                <p class="text-sm text-gray-600">Correct Guesses</p>
            </div>
            <div class="bg-indigo-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-indigo-600">${stats.total_drawings || 0}</p>
                <p class="text-sm text-gray-600">Drawings Made</p>
            </div>
            <div class="bg-pink-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-bold text-pink-600">${stats.total_wins > 0 ? Math.round((stats.total_wins / stats.total_games) * 100) : 0}%</p>
                <p class="text-sm text-gray-600">Win Rate</p>
            </div>
        </div>
    `;
}

function closeStatsModal() {
    document.getElementById('stats-modal').classList.add('hidden');
}

// Achievements Modal
async function showAchievementsModal() {
    document.getElementById('achievements-modal').classList.remove('hidden');
    
    const achievements = await apiRequest('achievements.php');
    const content = document.getElementById('achievements-content');
    content.innerHTML = '';
    
    if (!achievements || achievements.length === 0) {
        content.innerHTML = '<p class="text-gray-500 text-center">No achievements available</p>';
        return;
    }
    
    achievements.forEach(achievement => {
        const achDiv = document.createElement('div');
        achDiv.className = 'bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg p-4 border border-purple-100';
        
        achDiv.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="text-3xl">${achievement.icon}</div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800">${achievement.name}</h3>
                    <p class="text-sm text-gray-600 mb-2">${achievement.description}</p>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500">${achievement.requirement_value} ${achievement.requirement_type.replace('_', ' ')}</span>
                        <span class="text-xs bg-purple-500 text-white px-2 py-1 rounded">+${achievement.points_reward} pts</span>
                    </div>
                </div>
            </div>
        `;
        
        content.appendChild(achDiv);
    });
}

function closeAchievementsModal() {
    document.getElementById('achievements-modal').classList.add('hidden');
}
