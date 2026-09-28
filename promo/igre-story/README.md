# Instagram story: Igre

Five story frames (1080 × 1920) that introduce the games.

| Frame | What it shows |
|---|---|
| `story-1.png` | Svaki dan nova zagonetka: the three games |
| `story-2.png` | Riječle: an example board |
| `story-3.png` | Kontekst: an example ranking |
| `story-4.png` | Ljestve: an example ladder |
| `story-5.png` | Ko je danas prvi na ljestvici: call to play |

## Posting

1. Upload the frames as stories, in order.
2. On each frame, add a **Link** sticker (sticker icon → Link) with `https://drugaperspektiva.org/igre/`. On frames 2 to 4 you can link straight to that game's page instead (copy its address from the browser).
3. Drag the sticker onto the dashed box "Ovdje ide link" and pinch it until it covers the box.
   If the sticker doesn't cover the box completely, use the `-bez-okvira` version of that frame, which has an empty space in the same place instead.

Suggested sticker text: **Igraj ovdje**. Add the stories to a "Igre" highlight so they stay on the profile.

## Changing the text

Edit `story.html` and run `node promo/igre-story/render.mjs` from the repo root (needs Playwright).

## School display (16:9)

`ekran-igre.png` is a 1920 × 1080 slide for the school LED display, in the same look, with a QR code that opens `https://drugaperspektiva.org/igre/`. The QR code itself is `qr-igre.svg` (it was checked by scanning the finished image). If the display has a different size, the slide can be scaled; keep the QR code at least about a fifth of the screen height so it scans from a few metres away.
