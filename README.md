# FutOnline - Sports Streaming and Schedule Platform

FutOnline is a web platform designed to provide users with schedules for upcoming sports matches, live streaming options, and TV channel information. It features an administrative panel for managing content, including matches, leagues, teams, channels, and a comprehensive advertisement system.

## Key Features

*   **Match Listings:** Displays upcoming matches, filterable by league. Includes match time, description, team information, and cover images.
*   **Live Match Streaming:** For individual matches, provides embedded players and multiple stream options.
*   **TV Channel Player:** Lists available TV channels with an embedded player for live viewing.
*   **Search Functionality:** Allows users to search for matches.
*   **Comprehensive Admin Panel:**
    *   Manage Matches (Add, Edit, Delete)
    *   Manage Leagues (Add, Edit, Delete)
    *   Manage Teams (Add, Edit, Delete)
    *   Manage TV Channels (Add, Edit, Delete)
    *   Manage Streams for matches
    *   Manage Site Settings (e.g., site name, logo, default cover images)
    *   **Advanced Ad Management System:**
        *   Support for multiple ad types:
            *   **Image Banners:** Traditional clickable image ads.
            *   **Script Ads (Pop-up):** For pop-up advertisements.
            *   **Script Ads (Banner):** For ads served via HTML/JavaScript code (e.g., Google AdSense, native banners).
        *   Control ad display on various pages:
            *   Homepage
            *   Match Pages (general area)
            *   TV Channel Pages (general area)
            *   Left of Player (Match Pages)
            *   Right of Player (Match Pages)
            *   Left of Player (TV Pages)
            *   Right of Player (TV Pages)
        *   Activate/Deactivate ads.
        *   Admin interface to add, edit, and manage all ad types and their display options.
*   **User Activity Tracking:** Basic tracking of active user sessions.

## Technical Overview

*   **Backend:** PHP
*   **Database:** MySQL
*   **Frontend:** HTML, CSS, JavaScript (primarily vanilla JS for interactions).

## Setup

1.  **Database Configuration:**
    *   Edit `config.php` in the root directory to set your MySQL database host, username, password, and database name.
    *   Import the database schema. The following SQL files provide the structure:
        *   `schema.sql` or `schema_completo.sql`: Contains the initial full schema for most tables (matches, leagues, teams, channels, etc.). **Review and use the one most up-to-date for your setup.**
        *   `banners_table.sql`: Contains the schema for the `banners` table (this might be part of `schema_completo.sql` as well).
        *   `update_schema_for_ads.sql`: **Crucially, apply this script after the initial schema setup.** It contains all `ALTER TABLE` statements required for the full ad system functionality, including adding new columns and modifying existing ones in the `banners` table.
        *   Other `update_schema_vX.sql` files: These appear to be incremental updates. Apply them in order if they were part of your setup process.

2.  **Web Server:**
    *   Ensure your web server (e.g., Apache, Nginx) is configured to serve PHP files.
    *   The document root should point to the project's root directory.
    *   Ensure URL rewriting (e.g., via `.htaccess` for Apache) is enabled if used by the project (the presence of `.htaccess` suggests it might be).

3.  **File Uploads:**
    *   The `uploads/` directory and its subdirectories (`banners/`, `covers/matches/`, `logos/channels/`, etc.) need to be writable by the web server process for image uploads (banners, team logos, match covers) to work.

## Admin Panel Access

*   The admin panel is typically located at `/admin/` (e.g., `yourdomain.com/admin/`).
*   Default credentials might need to be set up manually in the database or through an initial admin creation script if one exists.

## Main Frontend Pages

*   `index.php`: Homepage, lists upcoming matches and TV channels. Displays homepage banners.
*   `match.php`: Match detail page. Displays match information, video player, stream options, player-side ads, general match page banners, and pop-up ads.
*   `channel_player.php`: TV channel player page. Displays the TV channel stream, player-side ads, general TV page banners, and pop-up ads.
*   `search.php`: Displays search results.

---

This README provides a general overview. Further details on specific functionalities can often be inferred from the code structure and comments.
