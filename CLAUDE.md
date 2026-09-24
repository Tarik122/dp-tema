# Druga perspektiva: custom WordPress theme

## Who this is for

*Druga perspektiva* is the student newspaper of Druga gimnazija Sarajevo, run by students. The live site is drugaperspektiva.org (WordPress). Readers are mostly students, parents and teachers, and most of them arrive from Instagram on a phone. All site text is in Bosnian (ijekavica).

The goal is a custom block theme that replaces the current generic news theme.

## The problem with the current site

See `references/current-site-*-DISLIKED.jpeg`. It looks like every other WordPress news portal (closer to a tabloid like The Sun than to a magazine):

- walls of identical thumbnail + headline rows, so every story has the same weight
- headline text dumped on top of photos with a muddy dark overlay
- dates on everything, which makes gaps between articles obvious
- ALL CAPS section title with a blue bar, default Roboto/Open Sans, no typographic personality
- a wrong date format ("11 Aprila, 2026")

We also don't publish very often. The design must hide low volume instead of exposing it.

## What we want

Editorial, not news portal: think The New Yorker, NYT Magazine and The Confluence (`references/confluence-*.jpeg`). It should feel extremely professional and deliberately made, never like an AI-generated or template site. Not too minimal, not too busy. Editorial, fun and simple.

What I like in each reference:

- **Instagram** (`references/instagram-*.png`): this is our brand and I like it. The solid colored category chip (periwinkle blue for news, orange for arts/culture and sport) with white sans text in sentence case, bold sans headlines, and the DP logo in the corner. The web design must feel like the same publication. Sample exact colors from the screenshots.
- **The Confluence**: serif headlines with real presence, calm sans or serif body, generous whitespace, text-only headline lists separated by hairlines, a strong navy used for one full-width block, author names given prominence.
- **The New Yorker**: confidence, white space, stories that each get their own treatment instead of a uniform grid, italic deks (standfirsts), author-forward bylines.

## Brand assets

- Logo: `logo/` (the "DP" high-contrast italic serif monogram with "Druga perspektiva" set small around it). Use it in the header; do not recreate it in CSS.
- Colors: derive from the Instagram screenshots (chip blue, chip orange) plus a deep navy and off-white/white base. Give me hex values.
- Type: one serif family for headlines and article body, and one sans for labels, chips, bylines and navigation. The Instagram posts use Lato, so a Lato-like sans ties web and Instagram together. For the serif, choose a free font with excellent Bosnian glyphs (č ć đ š ž Č Ć Đ Š Ž) and good display sizes, for example Source Serif 4, Newsreader or Libre Caslon. Self-host fonts as subsetted woff2 (no Google Fonts CDN). Avoid the usual defaults (Inter, Playfair Display, Fraunces).

## Hiding low volume (important)

- Homepage is curated modules, not an endless grid. No pagination on the homepage.
- Every module degrades gracefully: if there aren't enough posts for it, it renders nothing (never an empty heading). The homepage must look complete with about 12–15 articles in total.
- Show dates small and only where useful (article page, lists). Never show article counts per category.
- Category pages: first article large, the rest as a compact text list, not a grid of cards.
- Hide empty categories from navigation.
- "Pročitajte još" under articles falls back to latest articles if the category has too few.
- Consider an "Iz arhive" module that resurfaces good older pieces, so the homepage doesn't look stale between publications.

## Homepage idea (propose your own, but cover this)

1. Header: DP logo, section navigation, search. Clean, not a news-portal bar.
2. Lead story: the one big moment. It may use the Instagram cover treatment (photo, chip, big headline) if done with care and legible contrast, or a New Yorker-style large image with serif headline and italic dek beside or below.
3. Two or three secondary features with individual layouts, not identical cards.
4. A text-only headline list (Confluence style, hairline separators).
5. Mišljenje/columns module that puts author names first.
6. Section links ("Rubrike") without counts.
7. Navy footer: short about text, Instagram link, sections, contact.

## Article page

Category chip, large serif headline, italic dek (only when the author wrote a manual excerpt), byline with author name, date and reading time, wide featured image with caption and photo credit, ~680px reading column, well-styled pull quotes, block quotes, image captions and subheadings, author box at the end, then "Pročitajte još".

## Content structure

Current categories (keep the live site's slugs so old links don't break): Vijesti o školi, Ostale vijesti, Mišljenje, Sport, Umjetnost i kultura. Map each to a chip color.

## Language

All interface text in Bosnian, ijekavica ("Pročitajte još", "Pretraži", "Sljedeća", "Novije", "Starije"). Date format `j. F Y.` which with the site language set to Bosanski gives "11. april 2026.". Write empty states and the 404 page in plain, helpful Bosnian.

## Technical requirements

- Block theme (theme.json v3, WordPress 6.6+), fully editable in the Site Editor by non-technical student editors. No page builders, no required plugins.
- `starter-theme/` is a working starter block theme built earlier. Keep its useful plumbing: reading-time block binding, sticky post as lead story, standfirst shown only for manual excerpts, Bosnian strings, self-hosted fonts setup, patterns (fact box, interview Q&A). Replace its visual design completely (its fonts, colors and masthead effect were placeholders).
- Useful block patterns for editors: fact box, interview Q&A, photo gallery with credits, correction/update note, editor's note.
- Mobile first. Fast (no jQuery, no sliders, minimal JS). Accessible: contrast, visible keyboard focus, alt text, reduced motion respected.
- Rename the theme folder to `druga-perspektiva` and bump the version.

## Things that make a site look AI-made or templated: avoid

- tracked-out ALL CAPS labels above every heading (small caps or caps only where they carry meaning, and sparingly)
- "→" appended to links, meta joined with " · " everywhere
- identical rounded cards with the same soft shadow, gradient washes
- fade-and-slide-up animations on every section, hover effects on every card
- cream background with terracotta accent, or near-black with one neon accent
- numbered markers (01/02/03) on content that isn't a sequence

## How to work

1. Look at every image in `references/` and `logo/`. Tell me briefly what you take from each.
2. Propose a design plan before coding: color tokens (hex), typefaces and roles, type scale, ASCII wireframes of homepage, article page and category page on mobile and desktop. Wait for my approval.
3. Set up a local WordPress to test (WordPress Playground CLI via `npx`, or wp-env). If `export.xml` exists in this folder, import it; otherwise create realistic Bosnian dummy content: about 12 posts across the categories, some without featured images, some very long and very short headlines, a few authors.
4. Build the theme.
5. Take Playwright screenshots at 390px and 1440px of homepage, article, category, search and 404. Critique them honestly against the references and iterate at least twice.
6. Package `druga-perspektiva.zip` and write short install notes for uploading it to the live site.

Explain things simply; I'm a student, not a professional developer. English is fine for our conversation; the site itself is in Bosnian.
