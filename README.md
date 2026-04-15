# CineHub - Premium Movie Website

## Setup Steps

1. **Upload files** to your hosting public folder (htdocs)

2. **Create database** via InfinityFree Control Panel

3. **Import database.sql** via phpMyAdmin (select your DB first, then Import)

4. **Edit config.php** with your DB details:
   ```php
   define('DB_HOST', 'sql100.infinityfree.com');
   define('DB_NAME', 'if0_XXXXXXX_yourdb');
   define('DB_USER', 'if0_XXXXXXX');
   define('DB_PASS', 'your_password');
   ```

5. **Visit your site** → Go to `/admin` → Login with `admin@cinehub.com` / `admin123`

6. **Set TMDB API Key** → Admin → API Settings

7. **Import content** → Admin → Import from TMDB → Search & import

8. **Add download links** → Admin → All Media → Edit → Add Download Links

## Admin Login
- Email: admin@cinehub.com
- Password: admin123

## File Structure
```
cinehub/
├── index.php          Homepage
├── movie.php          Movie detail (/movie/slug OR ?id=tmdbid)
├── series.php         Series detail & browse (/series/slug)
├── movies.php         Browse movies
├── series.php         Browse series
├── anime.php          Anime section
├── search.php         Search
├── watch.php          Video player
├── genres.php         Genres
├── watchlist.php      User watchlist
├── login.php          Login
├── register.php       Register
├── logout.php         Logout
├── config.php         ← UPDATE DB CREDENTIALS
├── database.sql       Import this first
├── .htaccess          URL routing
│
├── admin/
│   ├── index.php         Dashboard
│   ├── import.php        ★ Import from TMDB
│   ├── media.php         All media list
│   ├── edit-media.php    ★ Edit + Download links
│   ├── embed-servers.php Video servers
│   ├── api-settings.php  TMDB API key
│   ├── settings.php      Site settings
│   └── users.php         User management
│
├── includes/
│   ├── core.php      DB, auth, TMDB, helpers
│   ├── header.php    Site header
│   ├── footer.php    Site footer
│   └── cards.php     Card components
│
└── assets/
    ├── css/style.css  Main stylesheet
    └── js/main.js     JavaScript
```
