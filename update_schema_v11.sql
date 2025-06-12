-- Schema update v11: Add settings table
-- This table is intended to store general site settings as key-value pairs.
-- Example usage: storing the filename of a default match cover image.

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(255) PRIMARY KEY NOT NULL UNIQUE,
    setting_value TEXT
);

-- Note: The table was referred to as `settings` in initial planning,
-- but implemented as `site_settings` to align with existing patterns
-- found in `manage_settings.php` during development.
-- If you have an existing `settings` table with a different structure,
-- please review carefully. This script uses `site_settings`.
