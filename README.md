# Scribble - Progressive Web App & Android App

A multiplayer drawing and guessing game converted from Python/Flask to PHP/JavaScript/MySQL with PWA support and Google Play Store publishing capability.

## 📱 What's Included

This package contains:
- ✅ **Complete PHP/MySQL application** ready for Hostinger deployment
- ✅ **Progressive Web App (PWA)** with offline support
- ✅ **Service Worker** for app-like experience
- ✅ **Manifest.json** for installability
- ✅ **Google Play Store publishing guide** (step-by-step)
- ✅ **Download buttons** for Play Store and App Store
- ✅ **Icon generation guide**
- ✅ **All game features** preserved from original Python version

## 🎯 Quick Start

### Important: This Application Requires PHP Hosting

This is a **PHP application** that needs to be deployed to a web host with:
- PHP 7.4 or higher
- MySQL database
- Apache web server

**It cannot run in this Replit environment** (which is Python-based). 

### Deployment Steps

1. **Deploy to Hostinger** (or any PHP hosting)
   - Follow `DEPLOYMENT_GUIDE.md` for complete step-by-step instructions

2. **Set Up PWA**
   - Create icons following `ICON_GENERATION_GUIDE.md`
   - Verify manifest.json and service worker work

3. **Publish to Google Play Store**
   - Follow `PLAYSTORE_PUBLISHING_GUIDE.md` for complete guide
   - Convert your PWA to Android APK using Bubblewrap
   - Submit to Google Play Console

4. **Update Download Links**
   - After publishing, update the Play Store/App Store links in `index.php`

## 📁 File Structure

```
scribble-php/
├── api/                          # PHP API endpoints (11 files)
│   ├── achievements.php          # Get player achievements
│   ├── choose_word.php           # Drawer selects word
│   ├── create_room.php           # Create game room
│   ├── csrf_token.php            # Get CSRF protection token
│   ├── get_words.php             # Get word choices for drawer
│   ├── join_room.php             # Join existing room
│   ├── lobby.php                 # List available rooms
│   ├── poll_updates.php          # AJAX polling for game updates
│   ├── send_drawing.php          # Submit drawing strokes
│   ├── send_message.php          # Send chat message/guess
│   ├── start_game.php            # Host starts the game
│   └── stats.php                 # Get player statistics
│
├── assets/
│   ├── js/
│   │   └── game.js               # Main game logic with AJAX polling
│   └── icons/                    # PWA icons (create these)
│       ├── icon-72x72.png
│       ├── icon-96x96.png
│       ├── icon-128x128.png
│       ├── icon-144x144.png
│       ├── icon-152x152.png
│       ├── icon-192x192.png
│       ├── icon-384x384.png
│       └── icon-512x512.png
│
├── database/
│   ├── schema.sql                # MySQL database schema
│   └── init_data.sql             # Default words and achievements
│
├── includes/
│   ├── config.php                # Configuration settings
│   ├── database.php              # Database connection class
│   ├── game.php                  # Game logic
│   └── session.php               # Session management + CSRF protection
│
├── .htaccess                     # Apache configuration
├── index.php                     # Main application file
├── manifest.json                 # PWA manifest
├── sw.js                         # Service worker
│
├── DEPLOYMENT_GUIDE.md           # Step-by-step Hostinger deployment
├── PLAYSTORE_PUBLISHING_GUIDE.md # Complete Play Store guide
├── ICON_GENERATION_GUIDE.md      # How to create all required icons
└── README.md                     # This file
```

## 🚀 Features

### Game Features
- ✅ Real-time multiplayer (AJAX polling, 1-second refresh)
- ✅ Private rooms with 6-character codes
- ✅ Turn-based drawing and guessing
- ✅ Progressive hints system
- ✅ Scoring with speed bonuses
- ✅ Achievements and statistics
- ✅ Custom word lists
- ✅ Chat functionality
- ✅ Dark mode
- ✅ Mobile responsive

### PWA Features
- ✅ Installable on home screen
- ✅ Offline support with service worker
- ✅ App-like full-screen experience
- ✅ Custom install prompt
- ✅ Cross-platform (Android, iOS via browser)
- ✅ Fast loading with caching
- ✅ Automatic updates

### Security Features
- ✅ CSRF token protection on all POST requests
- ✅ SQL injection prevention (prepared statements)
- ✅ Session management
- ✅ XSS protection
- ✅ Secure headers (.htaccess)

## 📖 Documentation

### For Hosting on Hostinger
📄 **DEPLOYMENT_GUIDE.md** - Complete step-by-step guide:
- Create MySQL database
- Upload files via FTP or File Manager
- Configure database credentials
- Set up SSL certificate
- Test all features
- Performance optimization
- Troubleshooting

### For Publishing on Google Play Store
📄 **PLAYSTORE_PUBLISHING_GUIDE.md** - Complete guide:
- Create required assets (icons, screenshots, graphics)
- Install and use Bubblewrap CLI
- Convert PWA to Android APK/AAB
- Set up Digital Asset Links
- Create Google Play Developer account
- Submit app for review
- Handle updates
- iOS App Store options

### For Creating Icons
📄 **ICON_GENERATION_GUIDE.md** - Icon creation guide:
- Required sizes and specifications
- Free online generators
- DIY design tips
- Hiring a designer
- Screenshot guidelines
- Feature graphic creation

## 🔧 Technical Details

### Technology Stack
- **Backend:** PHP 7.4+ with object-oriented design
- **Database:** MySQL 5.7+ with prepared statements
- **Frontend:** Vanilla JavaScript (ES6+)
- **CSS:** Tailwind CSS (CDN)
- **Icons:** Font Awesome 6.4.0
- **Communication:** AJAX polling (1-second interval)
- **PWA:** Service Worker + Web App Manifest

### Why AJAX Polling Instead of WebSockets?
Hostinger shared hosting doesn't support WebSocket connections. AJAX polling provides:
- Compatible with all shared hosting
- 1-second refresh rate (acceptable for drawing games)
- Reliable across all networks
- No special server configuration needed

## 🎨 Customization

### Branding
Update these in `index.php`:
- App name/title
- Color scheme (currently purple/indigo #6366f1)
- Logo/icons
- Buy Me a Coffee link

### Game Settings
Edit `includes/config.php`:
- Max players per room (default: 12)
- Default rounds (default: 3)
- Default timer (default: 80 seconds)
- Points range (10-100)

### Words
Add custom words in `database/init_data.sql` or via the custom words feature when creating a room.

## 📱 After Publishing

### Update Download Links

Once your app is published, update `index.php`:

```php
<!-- Replace # with actual URLs -->
<a href="https://play.google.com/store/apps/details?id=YOUR_PACKAGE_NAME" id="playstore-link">
<a href="https://apps.apple.com/app/YOUR_APP_ID" id="appstore-link">
```

The buttons will automatically become visible when you provide real URLs.

### Monitor and Maintain

1. **Check Play Console dashboard** for installs and reviews
2. **Respond to user feedback** promptly
3. **Fix bugs** by updating your web code (updates automatically!)
4. **Add new features** to keep users engaged
5. **Promote** on social media and gaming communities

## ⚠️ Important Notes

### Cannot Run in Replit
This PHP application is specifically designed for **PHP hosting environments** (like Hostinger). It cannot run in this Replit workspace because:
- Replit is currently configured for Python
- This app requires PHP 7.4+, MySQL, and Apache
- The application is ready for deployment to your hosting provider

### Deployment is Required
To use this application:
1. Download all files from the `scribble-php/` folder
2. Follow the DEPLOYMENT_GUIDE.md to deploy to Hostinger
3. Your app will be live at your domain (e.g., https://yourdomain.com)
4. Then follow PLAYSTORE_PUBLISHING_GUIDE.md to create the Android app

## 🆘 Support

If you need help:

1. **Deployment Issues:** See DEPLOYMENT_GUIDE.md troubleshooting section
2. **Play Store Issues:** See PLAYSTORE_PUBLISHING_GUIDE.md common issues
3. **Icon Creation:** See ICON_GENERATION_GUIDE.md for tools and tips
4. **Database Errors:** Check config.php credentials and database connection
5. **CSRF Errors:** Clear browser cache and cookies

## 📝 License

This is a converted version of the Scribble drawing game. Customize and use as you wish!

## 🎉 Ready to Launch!

Your complete package includes:
- ✅ Working PHP multiplayer game
- ✅ PWA with offline support  
- ✅ Play Store publishing guides
- ✅ Download buttons ready to go
- ✅ All documentation needed

**Next Steps:**
1. Deploy to Hostinger following DEPLOYMENT_GUIDE.md
2. Create icons following ICON_GENERATION_GUIDE.md
3. Publish to Play Store following PLAYSTORE_PUBLISHING_GUIDE.md
4. Share with friends and start playing!

Good luck with your launch! 🚀
