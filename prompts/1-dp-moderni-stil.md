# DP dizajn: new graphics in DP moderni style

The DP moderni style guide now lives in the repository as a **Claude Code skill**, in `.claude/skills/dp-dizajn/`. Every Claude Code session on the `dp-tema` repository knows it automatically.

**How to use it:**

1. Start a new session in Claude Code (claude.ai/code) on `tarik122/dp-tema`.
2. Type `/dp-dizajn` and describe what you need. You can also just describe it; Claude picks up the skill by itself when you ask for a graphic. For example:

   > /dp-dizajn Imamo intervju s direktoricom povodom 120 godina škole. Treba mi nešto posebno za Instagram, post i story. Članak: https://drugaperspektiva.org/…

3. Claude reads the article, asks a couple of questions, and proposes 2–3 ideas. You pick one, and it builds it and sends you the PNGs (2160 px). Then you tell it what to change.

It is for **new** kinds of graphics (like the games promo, or a special article). The everyday slides stay in the DP objave plugin.

What the skill contains:
- `SKILL.md`: the rules. Colours, Lato sizes, spacing, logo placement, Bosnian typography, formats, what you liked and disliked, and how to work with you.
- `primjeri/`: the approved designs it has to match.
- `predlozak/`: a starter file with the fonts, colours, logo and shadow already set up, plus a script that turns it into PNGs.
