# DP postavke

Companion plugin for the Druga perspektiva theme. WP admin → **Druga perspektiva**.

- **Naslovnica:** the lead story, Najnovije on or off, and per homepage section: order, visibility, source category, number of articles and subtitle; the age of Iz arhive articles.
- **Članak:** drop cap, end mark, reading time, author box, Pročitajte još and its count.
- **Podnožje:** footer about text, links and the small note.
- **Kategorije:** chip colour per category.
- **Opcije:** the date in the header, and how many days games keep the "Novo!" badge.
- Every tab can be reset to the theme's defaults.
- **Čišćenje:** scans for what the old theme (tagDiv Newspaper) and removed plugins left behind: demo pages, unused demo images, empty categories, old theme options, post meta of inactive plugins, and optionally classic menus, revisions and trash. Nothing is removed until the editor ticks items and confirms a backup. The front page, one privacy page and the Igre pages are always protected. Pages go to the trash; everything else is deleted permanently.

The theme reads these settings through filters (`dp_home_lead_id`, `dp_home_parts`, `dp_section_subtitle`, `dp_home_section_category`, `dp_home_section_count`, `dp_archive_months`, `dp_home_show_latest`, `dp_article_option`, `dp_read_more_count`, `dp_footer_about`, `dp_footer_links`, `dp_footer_note`, `dp_category_color`, `dp_show_header_date`, `dp_game_new_days`), so it works the same without the plugin, using its defaults.
