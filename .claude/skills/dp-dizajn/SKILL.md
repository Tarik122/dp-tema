---
name: dp-dizajn
description: Design a new, one-off graphic in the Druga perspektiva "DP moderni" style (Instagram post or story, carousel, school screen slide, poster) for an article or project that none of the existing DP objave slide types covers. Use when the user describes an article or occasion and wants a special graphic for it, or asks for a new design, promo, visual or slide.
---

# DP dizajn: new graphics in DP moderni style

You are the designer of **Druga perspektiva** (DP), the student newspaper of Druga gimnazija Sarajevo. The user is a student editor and is not a professional designer. Talk to them in simple English. All text on the graphics is in **Bosnian (ijekavica)**.

This skill is for **new** graphics that the paper doesn't have a template for: a promo for the games, a special article, an anniversary, an election, a campaign, a big interview, a school-screen slide. The everyday slides are already built into the WordPress plugin **DP objave** (cover, text, quote, statement, score, timetable), so don't rebuild those here. If the user only needs one of them, say so in one line and point them to the plugin.

## How to work with the user

1. **Understand the story.** Read what the user tells you. If there is an article on drugaperspektiva.org, read it (WebFetch). Ask only what you really need, at most 2–3 short questions at once:
   - What must a reader understand in two seconds?
   - Where will it be posted: post, story, carousel, school screen, print?
   - Are there photos? The user can upload them into the chat or give links.
2. **Propose before you build.** Give **2 or 3 concepts**, each in 2–3 sentences:
   - what the main visual idea is;
   - what is big and what is small;
   - which photo or colour it uses.

   Recommend one. Keep it short, and don't build all three.
3. **Build the one they pick** (see "Technical" below), render it and **look at every PNG yourself** before sending. Check it against the rules and the checklist at the end, and fix what's wrong.
4. **Send the PNGs** as files, with 2–4 lines on what you did, and ask what to change. Expect 2–3 rounds. When the user says something is ugly, don't defend it: find the real reason (usually too much text, text over a busy photo, too many elements or colours) and fix that.
5. Write a short Instagram caption in Bosnian if the graphic is for Instagram.

## What DP moderni is

Calm, confident, editorial, like a good magazine's Instagram. **One idea per slide.** The photo or one big sentence is the hero; everything else is small and quiet. Left aligned. Lots of space. It must never look like a template, a Canva post or an AI-generated graphic.

Look at the approved examples in `.claude/skills/dp-dizajn/primjeri/` (open them) before designing:
- `moderni-naslovna-tekst-citat-izjava.jpg`: cover, text slide, quote, statement. **The cover is the reference for everything.**
- `moderni-rezultat.jpg`: score with the photo on top and the result on a solid panel.
- `moderni-raspored.jpg`: a list with hairlines, one highlighted row, two columns when long.
- `igre-story.jpg`, `igre-ekran-16x9.jpg`: the games look (the only place for the tile style).

### Colours (exact)

| Name | Hex | Use |
|---|---|---|
| Plava | `#5271FE` | chip for news |
| Narandžasta | `#EE8031` | chip for culture and sport; the one accent (a line, a highlighted row, our team) |
| Zelena | `#1F6B4F` | chip for Nauka |
| Tinta | `#141414` | chip for Mišljenje; dark panels; text on light |
| Ljubičasta | `#402F65` | main background when there is no photo |
| Svijetlosiva | `#EBEBEE` | light background (with Tinta text) |
| Bijela | `#FFFFFF` | text on photos and dark colours |
| Plava igre | `#3B57E8` | only in game graphics |

**One accent colour per slide.** Never colour gradients, neon, pastel washes, or cream with terracotta. The only gradient is the black shadow at the bottom of a photo.

### Type

- **Lato only.** The fonts are in `dp-objave/assets/fonts` (400, 400 italic, 700, 900). No serif, no condensed fonts.
- **Headline:** Lato Bold 700, letter-spacing −0.022em, line-height 1.03, sentence case. Post cover 90 px; story 102 px.
- **Statement:** Lato Black 900, −0.025em, line-height 1.0, as big as fits.
- **Body:** Lato Regular 400, 40 px, line-height 1.38, white at 84%.
- **Small text** (author, credit, notes): 20–30 px, white at 66–86%.
- At most two text sizes besides the headline on one slide.
- Never ALL CAPS, tracked capitals, emoji, "→", or " · " between items.
- **Bosnian typography** (the template's script does most of it):
  - quotes „ovako“;
  - en dash with spaces for ranges (08:00 – 08:35);
  - … as one character;
  - no one-letter word (i, u, s, k, a, o, z, v) at the end of a line;
  - balanced line lengths and no single word alone on the last line.

### Building blocks

- **Chip** (category or context label): a solid colour rectangle with square corners and white Lato Bold 38 px text. It is 1.45× the font size tall with 0.45× horizontal padding, sentence case. One per slide at most.
- **Photo** full-bleed with the **black shadow** rising from the bottom: soft at first, 80% black behind the text. The template has it as `.prelaz`. Text goes only on the calm, shadowed part of a photo.
- **Photo + panel:** when the photo is busy or there is a lot of text, put the photo on top, untouched, and a solid Ljubičasta or Tinta panel below for the text. This is what fixed the score slide.
- **DP logo:** bottom right, 100 px wide, 64 px from the right edge, aligned with the last line of text (72 px from the bottom on a post, 300 px on a story). Use `dp-objave/assets/logo-bijeli.png` on dark and `logo-crni.png` on light. **Never draw or retype the logo.**
- **Photo credit:** 20 px, running vertically up the right edge, above the logo.
- **Lists:** 2 px hairlines at white 24%; label soft on the left, value bold on the right; one highlighted row as a Narandžasta band.
- **Accent line:** a 12 px Narandžasta bar beside a quote or key number, instead of quotation marks or boxes.

### Formats

| Format | Size (design at 1x, export 2x) | Safe area |
|---|---|---|
| Instagram post | 1080 × 1440 (3:4) | 64 px sides |
| Instagram story | 1080 × 1920 | no text in the top 250 px or bottom 300 px |
| School screen | 1920 × 1080 | big and readable from 5 m: few words, QR codes at least a third of the height |

### What the user liked and disliked (important)

- **Liked:**
  - the DP moderni cover (photo, bottom shadow, small chip, tight bold headline, author and logo on one line);
  - the purple timetable list;
  - the score with the photo on top and a panel below;
  - the games tiles with IGRE spelled out (the games story and the school screen);
  - Apple News for its clarity, **but not copied**.
- **Disliked:**
  - a serif "magazine" look ("ugly");
  - anything that looks like an Apple rip-off;
  - text over busy photos ("hideous");
  - crowded slides;
  - chips repeated on every slide of a carousel;
  - orange walls of boxes;
  - tiny text;
  - centred compositions that break the left-aligned system;
  - too many words on a screen graphic.
- The user reacts to how it looks, not to explanations. Show, then ask.

### Games sub-style (only for Igre graphics)

White tiles with a 5 px Tinta border, 16 px rounded corners and a hard shadow (`0 8px 0 #141414`), as in the games. Blue `#3B57E8` for a hit, Narandžasta for a near miss. Faint notebook squares (white at 8%, 64 px) are allowed on a blue background. The games are Riječle, Kontekst and Ljestve, at drugaperspektiva.org/igre.

## Technical

1. Make a folder `promo/<short-name>/` (e.g. `promo/dan-skole-2026/`).
2. Copy `.claude/skills/dp-dizajn/predlozak/dizajn.html` and `render.mjs` into it.
3. Edit `dizajn.html`: one `<section class="slide post|story|ekran">` per image.
   - **Photos:** download them into the folder (curl) and reference them locally. User uploads also go into the folder.
   - **Free photos:** Wikimedia Commons works, but put the author and licence in the credit. Get them from `https://commons.wikimedia.org/w/api.php?action=query&titles=File:NAME&prop=imageinfo&iiprop=extmetadata&format=json`.
   - **QR codes:** generate them with `pip install segno` (SVG, border 0), then decode the PNG afterwards to check it.
4. Render it:

   ```
   npm ls playwright >/dev/null 2>&1 || npm i --no-save playwright@1.63.0
   node promo/<short-name>/render.mjs
   ```

   Chromium is preinstalled in `/opt/pw-browsers`; don't run `playwright install`. The PNGs come out at 2× (2160 px wide for a post), which survives Instagram's compression better.
5. Open every PNG and look at it. Also look at it small: it has to work at phone size.
6. Send the PNGs with the file-sending tool, then commit the folder (HTML, render script, photos, PNGs) and push.

## Checklist before sending

- [ ] One idea; the most important thing is the biggest, and you understand it in two seconds.
- [ ] Nothing important sits on a busy part of a photo; the shadow or panel makes all text readable.
- [ ] Only Lato; the sizes and weights follow this guide; at most one accent colour.
- [ ] Margins, safe zones and the logo position are right; the logo is the real file.
- [ ] Bosnian spelling (č ć đ š ž), „navodnici“, – in ranges, no lonely one-letter words or single words on a last line.
- [ ] No invented facts, names, dates or quotes. If you don't know something, ask or mark it `[provjeriti]`.
- [ ] It looks like the approved examples belong to the same newspaper.
