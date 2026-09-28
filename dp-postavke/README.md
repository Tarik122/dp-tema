# DP postavke

Companion plugin for the Druga perspektiva theme. WP admin → **Druga perspektiva**.

- **Naslovnica:** pick the lead story, reorder or hide homepage sections, change section subtitles, show or hide Najnovije.
- **Opcije:** the date in the header, and how many days games keep the "Novo!" badge.
- **Čišćenje:** scans for what the old theme (tagDiv Newspaper) and removed plugins left behind: demo pages, unused demo images, empty categories, old theme options, post meta of inactive plugins, and optionally classic menus, revisions and trash. Nothing is removed until the editor ticks items and confirms a backup. The front page, one privacy page and the Igre pages are always protected. Pages go to the trash; everything else is deleted permanently.

The theme reads these settings through filters (`dp_home_lead_id`, `dp_home_parts`, `dp_section_subtitle`, `dp_home_show_latest`, `dp_show_header_date`, `dp_game_new_days`), so it works the same without the plugin, using its defaults.
