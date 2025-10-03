# Scribble Game - Hostinger Deployment Guide

Complete step-by-step guide to deploy your Scribble multiplayer drawing game on Hostinger shared hosting.

## 📋 Prerequisites

Before you begin, make sure you have:
- A Hostinger shared hosting account (Business or higher recommended)
- PHP 7.4 or higher
- MySQL database access
- FTP/File Manager access
- Your domain name configured

## 🚀 Deployment Steps

### Step 1: Prepare Your Files

1. **Download all files** from the `scribble-php` folder
2. **Verify you have these files and folders:**
   ```
   scribble-php/
   ├── api/
   │   ├── achievements.php
   │   ├── choose_word.php
   │   ├── create_room.php
   │   ├── get_words.php
   │   ├── join_room.php
   │   ├── lobby.php
   │   ├── poll_updates.php
   │   ├── send_drawing.php
   │   ├── send_message.php
   │   ├── start_game.php
   │   └── stats.php
   ├── assets/
   │   └── js/
   │       └── game.js
   ├── database/
   │   ├── schema.sql
   │   └── init_data.sql
   ├── includes/
   │   ├── config.php
   │   ├── database.php
   │   ├── game.php
   │   └── session.php
   ├── .htaccess
   └── index.php
   ```

### Step 2: Create MySQL Database

1. **Log in to Hostinger Control Panel** (hPanel)

2. **Navigate to Databases > MySQL Databases**

3. **Create a new database:**
   - Database name: `u123456789_scribble` (or your preferred name)
   - Click "Create"

4. **Create a database user:**
   - Username: `u123456789_scribble_user`
   - Password: Create a strong password (save this!)
   - Click "Create User"

5. **Add user to database:**
   - Select the database you created
   - Select the user you created
   - Grant "All Privileges"
   - Click "Add"

6. **Note down these details:**
   ```
   Database Host: localhost (usually)
   Database Name: u123456789_scribble
   Database User: u123456789_scribble_user
   Database Password: [your password]
   ```

### Step 3: Import Database Schema

1. **Go to phpMyAdmin** (in hPanel > Databases section)

2. **Select your database** from the left sidebar

3. **Click the "SQL" tab**

4. **Copy the contents of `database/schema.sql`** and paste it into the SQL query box

5. **Click "Go"** to execute

6. **Repeat for `database/init_data.sql`** to add default words and achievements

7. **Verify tables were created:**
   - You should see tables like: players, game_rooms, room_players, drawing_data, chat_messages, etc.

### Step 4: Upload Files to Hostinger

#### Option A: Using File Manager (Recommended for beginners)

1. **Log in to hPanel**

2. **Go to Files > File Manager**

3. **Navigate to `public_html`** (or your domain's root directory)

4. **Upload all files:**
   - Click "Upload" button
   - Select all files from `scribble-php` folder
   - Upload (this may take a few minutes)

5. **Verify the structure:**
   - Make sure `index.php` is in the root
   - Make sure folders `api/`, `assets/`, `includes/` are present

#### Option B: Using FTP Client (FileZilla)

1. **Download FileZilla** (https://filezilla-project.org/)

2. **Get FTP credentials from hPanel:**
   - Go to Files > FTP Accounts
   - Use existing account or create new one

3. **Connect to your server:**
   - Host: ftp.yourdomain.com
   - Username: [your FTP username]
   - Password: [your FTP password]
   - Port: 21

4. **Navigate to `public_html`**

5. **Upload all files from `scribble-php` folder**

### Step 5: Configure the Application

1. **Edit `includes/config.php`:**

   Using File Manager or FTP, open `includes/config.php` and update these values:

   ```php
   // Database Configuration
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u123456789_scribble');        // Your database name
   define('DB_USER', 'u123456789_scribble_user');   // Your database user
   define('DB_PASS', 'your_database_password');     // Your database password

   // Site URL (update with your domain)
   define('SITE_URL', 'https://yourdomain.com');    // Your actual domain
   ```

2. **Important: Set proper permissions**

   In File Manager, right-click on these folders/files and set permissions:
   - `includes/` folder: 755
   - `includes/config.php`: 644 (or 640 for better security)
   - All other PHP files: 644
   - All folders: 755

### Step 6: Configure SSL (HTTPS)

1. **Enable SSL in hPanel:**
   - Go to Security > SSL
   - Install free SSL certificate (Let's Encrypt)
   - Wait for activation (5-15 minutes)

2. **Force HTTPS:**
   - Edit `.htaccess` file
   - Uncomment these lines (remove the `#`):
     ```apache
     RewriteCond %{HTTPS} off
     RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
     ```

3. **Update config.php:**
   ```php
   define('SITE_URL', 'https://yourdomain.com');  // Make sure it's https://
   define('SESSION_COOKIE_SECURE', true);  // Change false to true
   ```

### Step 7: Test Your Application

1. **Open your website:** `https://yourdomain.com`

2. **Test basic functionality:**
   - [ ] Homepage loads correctly
   - [ ] Can enter username
   - [ ] Can create a room
   - [ ] Room code is displayed
   - [ ] Can open a second browser/incognito window
   - [ ] Can join the room with room code
   - [ ] Host can start the game
   - [ ] Drawing works
   - [ ] Chat/guessing works
   - [ ] Timer counts down
   - [ ] Round ends and shows scores
   - [ ] Game ends and shows final scores

3. **Check for errors:**
   - If you see any errors, check:
     - Database connection (config.php settings)
     - File permissions
     - PHP error logs (in hPanel > Advanced > Error Logs)

### Step 8: Performance Optimization

1. **Enable PHP OPcache** (if available):
   - Go to hPanel > Advanced > PHP Configuration
   - Enable OPcache

2. **Set up Cron Job for cleanup** (optional but recommended):
   - Go to hPanel > Advanced > Cron Jobs
   - Add a new cron job:
     ```
     Schedule: Every hour
     Command: php /home/username/public_html/cleanup.php
     ```
   
   Create `cleanup.php` in your root directory:
   ```php
   <?php
   require_once 'includes/config.php';
   require_once 'includes/database.php';
   
   $db = Database::getInstance();
   $db->cleanupInactiveRooms();
   $db->cleanupInactivePlayers();
   $db->cleanupOldUpdates();
   
   echo "Cleanup completed\n";
   ```

### Step 9: Security Best Practices

1. **Change default settings:**
   - Update the `SECRET_KEY` in `config.php` to a random string

2. **Protect sensitive files:**
   - Make sure `.htaccess` is protecting your includes folder

3. **Regular backups:**
   - Use hPanel backup feature
   - Schedule automatic backups

4. **Monitor logs:**
   - Check PHP error logs regularly
   - Monitor unusual activity

5. **Keep updated:**
   - Update PHP version when available
   - Keep database optimized

## 🔧 Troubleshooting

### Issue: "Database connection failed"
**Solution:**
- Verify database credentials in `config.php`
- Check if database user has proper permissions
- Confirm database exists in phpMyAdmin

### Issue: "500 Internal Server Error"
**Solution:**
- Check `.htaccess` file (try renaming it temporarily)
- Check PHP error logs in hPanel
- Verify file permissions (644 for files, 755 for folders)
- Make sure PHP version is 7.4 or higher

### Issue: Pages not loading (404 errors)
**Solution:**
- Make sure `index.php` is in the correct directory
- Check `.htaccess` file is present
- Verify mod_rewrite is enabled (usually is on Hostinger)

### Issue: Drawing not syncing between players
**Solution:**
- This is normal due to polling delay (1 second)
- Make sure `poll_updates.php` is accessible
- Check browser console for JavaScript errors

### Issue: Room codes not working
**Solution:**
- Check database connection
- Verify `game_rooms` table exists
- Make sure session is working (check browser cookies)

## 📊 Monitoring & Maintenance

### Daily Checks:
- Monitor active users
- Check error logs

### Weekly Tasks:
- Review database size
- Clean up old game data (manually or via cron)
- Check storage usage

### Monthly Tasks:
- Update backups
- Review security logs
- Optimize database tables

## 🎮 Features & Limitations

### What Works:
✅ Real-time drawing (with 1-second polling delay)
✅ Multiplayer rooms
✅ Chat and guessing
✅ Scoring system
✅ Achievements tracking
✅ Player statistics
✅ Custom words
✅ Mobile responsive

### Known Limitations:
⚠️ 1-second delay for updates (due to polling instead of WebSockets)
⚠️ Shared hosting resource limits (consider upgrading for high traffic)
⚠️ Drawing sync is not instant (slight delay is normal)

## 🆘 Support

If you encounter issues:

1. **Check PHP error logs** in hPanel
2. **Review browser console** for JavaScript errors
3. **Verify database connection** and tables
4. **Check file permissions** (very common issue)
5. **Test with different browsers**

## 🎉 Congratulations!

Your Scribble game is now live! Share your room codes and enjoy playing with friends!

**Remember to:**
- Keep your database credentials secure
- Regularly backup your database
- Monitor resource usage
- Update PHP version when needed

---

## 📝 Quick Reference

### Default Settings:
- Max players per room: 12
- Default rounds: 3
- Default timer: 80 seconds
- Polling interval: 1 second
- Session timeout: 1 hour
- Inactive room cleanup: 30 minutes

### Important Files:
- Configuration: `includes/config.php`
- Database: `includes/database.php`
- Game logic: `includes/game.php`
- Frontend: `index.php`
- JavaScript: `assets/js/game.js`

### Database Tables:
- `players` - User accounts and stats
- `game_rooms` - Active game rooms
- `room_players` - Players in rooms
- `drawing_data` - Drawing strokes
- `chat_messages` - Chat history
- `achievements` - Available achievements
- `words` - Word database

Enjoy your game! 🎨✨
