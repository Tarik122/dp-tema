# Druga perspektiva: design plan (draft for approval)

Nothing here is built yet. Mark anything you want changed and I'll adjust before coding.

---

## 1. Colors

Sampled from your Instagram posts, then adjusted where small white text would be hard to read. WCAG (the accessibility standard) wants at least 4.5:1 contrast for normal text.

| Token | Hex | Used for | White text on it |
|---|---|---|---|
| `plava` (Instagram blue) | `#5271FE` | big color moments only: lead-story rule, Igre tile, focus ring | 4.1:1, too low for small text |
| `plava-chip` | `#3B57E8` | chips for **Vijesti o školi** and **Ostale vijesti**, links on hover | 5.7:1 ✓ |
| `narandza` (Instagram orange) | `#EE8031` | big accents only, never behind small text | 2.7:1 ✗ |
| `narandza-chip` | `#B34E0F` | chips for **Sport** and **Umjetnost i kultura** | 5.2:1 ✓ |
| `zelena` (new) | `#1F6B4F` | chip for **Nauka i tehnologija** | 6.4:1 ✓ |
| `navy` | `#16215C` | footer, "Mišljenje" block, **Mišljenje** chip, headline hover | 14.9:1 ✓ |
| `tinta` (ink) | `#141414` | headlines and body text | 18.4:1 ✓ |
| `siva` (grey) | `#5E5E5E` | dates, captions, photo credits | 6.5:1 ✓ |
| `linija` (hairline) | `#D9D9D6` | the thin separators between list items | none (decorative) |
| `papir` (paper) | `#FFFFFF` | page background | none |
| `papir-2` | `#F4F4F1` | fact box, editor's note, author box | none |

**Why white rather than cream:** `CLAUDE.md` lists cream with terracotta as an AI-site cliché, and Instagram is white. The orange stays a true orange next to the blue, so it never reads as terracotta.

**Why deeper chip colors:** the exact Instagram blue and orange are too light behind small white text. The deeper versions read as the same colors, and the exact ones still appear in large decorative spots.

---

## 2. Typefaces

> **Update after the first build:** the first version looked too much like The Confluence (serif headlines, a big navy block, thick black rules). Big headlines now use **Bodoni Moda**, a high-contrast serif in the spirit of the DP logo. Section titles are large Bodoni italics over a thin rule. Mišljenje sits on a light panel with author names in orange Bodoni italics. Navy appears only in the footer and the Mišljenje chip. Smaller headlines and article text stay in Source Serif 4, and labels stay in Lato.

| Role | Font | Why |
|---|---|---|
| Headlines, deks, article text | **Source Serif 4** (free, variable) | Excellent č ć đ š ž. It has an *optical size* setting that makes big headlines sharper and more elegant, much like The Confluence's serif. It isn't one of the overused defaults. |
| Chips, bylines, navigation, dates, captions, buttons | **Lato** | The exact font of your Instagram posts, so the web and Instagram visibly match. |

Both fonts are self-hosted as small `.woff2` files, trimmed to the letters Bosnian and English need. Nothing loads from Google.

**Rules**
- Headlines in bold serif. Deks in *italic serif*.
- Chips in Lato Bold, sentence case ("Umjetnost i kultura"), with square corners, exactly like Instagram.
- Author names in Lato Bold, in normal case, never ALL CAPS.
- The only small caps are in the footer's "Rubrike" heading. Nowhere else.

---

## 3. Type scale

Sizes grow smoothly from phone to desktop.

| Name | Phone → desktop | Font | Where |
|---|---|---|---|
| Display | 36 → 64px | Serif 700, tight line height | lead story, article headline |
| H1 | 30 → 44px | Serif 700 | category page title, 2nd feature |
| H2 | 24 → 30px | Serif 700 | secondary features, article subheadings |
| H3 | 20 → 22px | Serif 600 | text-list headlines, Mišljenje titles |
| Dek | 19 → 22px | Serif italic 400 | standfirst under headlines |
| Body | 18 → 20px, line height 1.6 | Serif 400 | article text (about 680px column) |
| UI | 15px | Lato 700 / 400 | bylines, navigation, buttons |
| Small | 13px | Lato 700 | chips, dates, captions, credits |

---

## 4. How the homepage hides low volume

Every module needs a minimum number of posts, or it disappears completely (no empty heading):

| Module | Content | Needs |
|---|---|---|
| Lead | sticky post → newest "Izdvojeno" → newest post | 1 |
| Features | next 3 "Izdvojeno" posts, excluding the lead | 2 (shows 2 or 3) |
| Najnovije (text list) | 5 newest not already shown | 3 |
| Mišljenje | 3 newest from `opinion` | 2 |
| Igre | a static tile linking to /igre | always shown |
| Iz arhive | 1 random "Izdvojeno" post older than 6 months, changing daily | 1 |
| Rubrike | categories that have posts, with no counts | 1 |

With your 39 real articles, every module fills. With 12 it still looks complete.

**Dates:** shown only on the article page and in the text lists, small and grey. None on the lead or features.

---

## 5. Homepage

**One design, two widths.** Phone and desktop have the same modules, in the same order, with the same treatment for each. The only thing that changes is how many columns sit side by side. A reader who sees the site on their phone in the morning and on a laptop in the afternoon should recognise every piece.

### Phone (390px)

```
┌───────────────────────────────┐
│ [DP logo]         Rubrike  ⌕  │  header: logo left, menu + search right
├───────────────────────────────┤
│ ┌───────────────────────────┐ │
│ │                           │ │  LEAD: Instagram cover treatment
│ │          PHOTO            │ │  (same on desktop). Photo 4:5,
│ │                           │ │  soft gradient only on the bottom
│ │ [Vijesti o školi]         │ │  third, chip + big white bold
│ │ Uz performans Dramobrazbe │ │  headline sitting on it
│ │ započeo Dan otvorenih…    │ │
│ └───────────────────────────┘ │
│ Italic dek below the photo,   │  dek + author on white, under
│ on white.                     │  the photo (same on desktop)
│ Nadin Janjoš                  │
│ ───────────────────────────── │
│ FEATURE A  (photo on top)     │  3 features, each different:
│ ┌───────────────────────────┐ │  A: 3:2 photo, chip, H2, author
│ └───────────────────────────┘ │
│ [Sport]                       │
│ Headline in serif H2          │
│ Autor                         │
│ ───────────────────────────── │
│ FEATURE B  (text only, big)   │  B: no photo, big serif headline,
│ [Mišljenje]                   │     italic dek
│ Big serif headline without    │
│ a photo                       │
│ Dek… Autor                    │
│ ───────────────────────────── │
│ FEATURE C  (small photo right)│  C: headline left, square photo right
│ [Kultura]          ┌─────┐    │
│ Headline           │ img │    │
│ Autor              └─────┘    │
│                               │
│ Najnovije                     │  text list, Confluence-style
│ ───────────────────────────── │
│ Headline one                  │
│ Autor, 3. decembar 2025.      │
│ ───────────────────────────── │  hairlines between items
│ Headline two                  │
│ Autor, 22. novembar 2025.     │
├───────────────────────────────┤
│▓▓▓▓▓▓▓ NAVY BLOCK ▓▓▓▓▓▓▓▓▓▓▓▓│  Mišljenje: author name first,
│ Mišljenje                     │  then title
│ Sema Čeljo                    │
│ Kako odabrati budući fakultet?│
│ ───────────                   │
│ Sarah Kadić                   │
│ Da li su ocjene zaista…       │
├───────────────────────────────┤
│ ┌── IGRE (blue) ────────────┐ │  Igre tile
│ │ Igre  beta     ▢▢▢▢▢      │ │
│ │ Nova riječ svaki dan.     │ │
│ └───────────────────────────┘ │
│ Iz arhive                     │  one older piece, square photo left
│ [img] Headline                │
│       Autor, 2025.            │
│ Rubrike                       │  list with hairlines, no counts
│ ───────────────────────────── │
│ Vijesti o školi               │
│ ───────────────────────────── │
│ Mišljenje …                   │
├───────────────────────────────┤
│▓▓▓▓▓▓ NAVY FOOTER ▓▓▓▓▓▓▓▓▓▓▓▓│  white logo, about, sections,
└───────────────────────────────┘  Instagram, contact (stacked)
```

### Desktop (1440px, content max 1240px)

The same pieces, now side by side:

```
┌──────────────────────────────────────────────────────────────────────┐
│ [DP logo]   Vijesti  Mišljenje  Sport  Kultura  Nauka  Igre beta   ⌕ │  same header; the menu
├──────────────────────────────────────────────────────────────────────┤  items are visible
│ ┌──────────────────────────────────────────────────────────────────┐ │
│ │                                                                  │ │  LEAD: same Instagram
│ │                          PHOTO (16:9)                            │ │  cover treatment. The
│ │                                                                  │ │  headline stays in the
│ │ [Vijesti o školi]                                                │ │  bottom-left, max ~60%
│ │ Uz performans Dramobrazbe započeo                                │ │  of the width, so lines
│ │ je Dan otvorenih vrata                                           │ │  stay short and legible
│ └──────────────────────────────────────────────────────────────────┘ │
│ Italic dek below the photo, on white.            Nadin Janjoš       │  dek + author under
├──────────────────────────────────────────────────────────────────────┤  the photo, as on phone
│ FEATURE A              │ FEATURE B                │ FEATURE C          │  the same 3 features,
│ ┌──────────────────┐   │ [Mišljenje]              │ [Kultura]  ┌────┐  │  in one row
│ │      3:2         │   │ Big serif headline       │ Headline   │img │  │
│ └──────────────────┘   │ without a photo          │ Autor      └────┘  │
│ [Sport]                │ Dek… Autor               │                    │
│ H2 headline, Autor     │                          │                    │
├──────────────────────────────────────────────────────────────────────┤
│ Najnovije                                                            │  same text list,
│ ─────────────────────────────────  ───────────────────────────────── │  in 2 columns
│ Headline one                       Headline two                      │
│ Autor, datum                       Autor, datum                      │
├──────────────────────────────────────────────────────────────────────┤
│▓▓ Mišljenje ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│  same navy block,
│▓▓ Sema Čeljo        ▓ Sarah Kadić         ▓ Lejla Bučo           ▓▓▓│  3 columns
│▓▓ Kako odabrati…    ▓ Da li su ocjene…    ▓ Zašto učenici sve…   ▓▓▓│
├──────────────────────────────────────────────────────────────────────┤
│ ┌── IGRE (blue) ─────────┐  Iz arhive               Rubrike          │  Igre, Iz arhive and
│ │ Igre beta   ▢▢▢▢▢      │  [img] Kako je Zlatno    ──────────────── │  Rubrike in one row,
│ │ Nova riječ svaki dan.  │        doba islama…      Vijesti o školi  │  each looking exactly
│ └────────────────────────┘        Autor, 2025.      ──────────────── │  as on the phone
│                                                     Mišljenje …      │
├──────────────────────────────────────────────────────────────────────┤
│▓▓ FOOTER navy: logo (white) │ O nama │ Rubrike │ Instagram, kontakt ▓│  same content, in columns
└──────────────────────────────────────────────────────────────────────┘
```


---

## 6. Article page

### Phone

```
┌───────────────────────────────┐
│ [DP logo]         Rubrike  ⌕  │
├───────────────────────────────┤
│ [Sport]                       │  chip
│ Bračković i Kadribegović      │  Display serif headline
│ najavili Gimnazijadu          │
│                               │
│ Prvo kolo donosi duele s      │  italic dek (manual excerpt only)
│ domaćinom, Prvom gimnazijom…  │
│                               │
│ Mustafa Vrabac                │  author (Lato bold)
│ 26. oktobar 2025. 4 min čit.  │  date + reading time (grey)
├───────────────────────────────┤
│         FEATURED PHOTO        │  full width
│ Caption. Foto: Ime Prezime    │
├───────────────────────────────┤
│ Body text, serif 18px…        │  ~680px column on desktop
│                               │
│ Podnaslov (H2 serif)          │
│                               │
│ ┃ "Pull quote in large        │  pull quote: big serif italic,
│ ┃  italic serif"              │  orange rule on the left
│                               │
│ ┌ Fact box (grey) ──────────┐ │  existing pattern, restyled
│ └───────────────────────────┘ │
│                               │
│ ── Autor ──────────────────── │  author box: name, bio,
│ Mustafa Vrabac                │  link to all their articles
│ Short bio…                    │
├───────────────────────────────┤
│ Pročitajte još                │  3 from same category, or
│ Headline                      │  latest if too few; text list
│ ───────────────────────────── │  with the first one having
│ Headline                      │  a small photo
└───────────────────────────────┘
```

### Desktop

Exactly the same order and treatments as the phone, centered. The headline block sits in a wider 860px column, the photo spans 1080px, and the text runs in a 680px column. Pull quotes can stick out slightly into the left margin. "Pročitajte još" uses 3 columns.

---

## 7. Category page (e.g. Sport)

```
Phone                              Desktop
┌─────────────────────────┐        ┌───────────────────────────────────────────┐
│ Sport                   │        │ Sport                                     │  H1 serif, colored
│ (optional description)  │        │ (description)                             │  underline in chip color
├─────────────────────────┤        ├──────────────────────────┬────────────────┤
│ ┌─────────────────────┐ │        │ ┌──────────────────────┐ │ Headline 2     │
│ │    NEWEST (3:2)     │ │        │ │   NEWEST, big photo  │ │ Autor, datum   │
│ └─────────────────────┘ │        │ └──────────────────────┘ │ ────────────── │
│ H1 headline             │        │ H1 headline              │ Headline 3     │
│ Dek, Autor              │        │ Dek, Autor               │ ────────────── │
├─────────────────────────┤        ├──────────────────────────┴────────────────┤
│ Headline 2              │        │ Headline 4 … (text list, hairlines)       │
│ Autor, datum            │        │                                           │
│ ─────────────────────── │        │                                           │
│ Headline 3 …            │        │                                           │
├─────────────────────────┤        ├───────────────────────────────────────────┤
│ ‹ Novije     Starije ›  │        │ ‹ Novije                         Starije › │
└─────────────────────────┘        └───────────────────────────────────────────┘
```

On page 2 and later, the big first item disappears and it's just the list.

---

## 8. Igre page (/igre)

```
┌──────────────────────────────────────┐
│ header                               │
├──────────────────────────────────────┤
│ Igre  [beta]                         │  H1 serif + small blue "beta" chip
│ Kratak opis u jednoj rečenici.       │  one-line intro (editable)
│ ┌──────────────────────────────────┐ │
│ │     [dp_wordle] plugin output    │ │  centered, max 560px wide,
│ │                                  │ │  no byline, date or reading time
│ └──────────────────────────────────┘ │
│ Pravila (optional, editable text)    │
├──────────────────────────────────────┤
│ footer                               │
└──────────────────────────────────────┘
```

It's a page template called "Igre" that editors choose in the Site Editor. I'll add light CSS so the plugin's game picks up the theme's fonts and colors where that's safe. Because I can't see the plugin, I'll test with a stand-in and you should check the real game after installing.

---

## 9. Search, 404, empty states (Bosnian)

- **Search:** a large search field at the top, results as a text list (headline, author, date), and a "no results" message: "Nismo pronašli ništa za „…“. Pokušajte s drugom riječi ili pogledajte rubrike ispod."
- **404:** "Ova stranica ne postoji. Možda je link pogrešan ili je članak premješten." followed by a search field and 3 latest articles.

---

## 10. Things I'll avoid

- No ALL CAPS labels, no "→" on links, no " · " between every meta item.
- No identical cards, no shadows, no rounded corners, no gradients except the one bottom fade on the phone lead photo.
- No scroll animations and no hover lift. Hover only underlines headlines.
- No numbered markers.

---

## 11. Build notes (technical, short)

- Theme folder `druga-perspektiva`, version `1.0.0`, theme.json v3, WordPress 6.6+.
- Kept from the starter: reading time, sticky lead, dek only for manual excerpts, Bosnian strings, patterns.
- New patterns: photo gallery with credits, correction/update note, editor's note.
- Category colors are set in one place in `functions.php`, mapped by slug (`vijestiskola`, `ostale-vijesti`, `opinion`, `sport`, `kultura`, `nauka`). A new category falls back to navy.
- Empty and junk categories (Uncategorized, Movies, Music, News, zzdvojeno, Arhiva) are hidden from navigation and Rubrike.
- No jQuery and no sliders. The only JavaScript is the small menu toggle WordPress already ships with.
