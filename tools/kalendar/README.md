# Kalendar događaja: generator

Turns one JSON file into finished Instagram slides in DP moderni style (PNG, 2160 px wide, 3:4 or 9:16). Photos and posters are given as web addresses (or files next to the JSON). The script downloads them itself, so nothing has to be added by hand.

```
cd tools/kalendar && npm install && cd ../..
node tools/kalendar/render.mjs tools/kalendar/2026-10.json
```

The slides land in `tools/kalendar/izlaz/<file name>/` (not committed). The script reports:
- any photo or poster it could not download (that slot shows the title in a dashed frame instead);
- any slide where the content runs too close to the bottom.

## JSON format

```json
{
  "mjesec": "oktobar",
  "godina": 2026,
  "format": "post",
  "naslovna": {
    "oznaka": "Vijesti",
    "naslov": "Kalendar događaja za mjesec oktobar",
    "fotografija": "https://…/sarajevo.jpg",
    "potpis": "Foto: Ime Prezime"
  },
  "sekcije": [
    {
      "tip": "lista",
      "naslov": "Koncerti",
      "redovi": [
        { "datum": "07.10.", "dan": "Srijeda", "naziv": "Izvođač", "mjesto": "Dvorana" }
      ]
    },
    {
      "tip": "mreza",
      "naslov": "Predstave",
      "stavke": [
        { "naziv": "Naziv predstave", "datum": "15.10.", "plakat": "https://…/plakat.jpg", "izvor": "https://…" }
      ]
    }
  ]
}
```

- `format`: `post` (1080 × 1440) or `story` (1080 × 1920).
- Each section becomes one slide:
  - `lista`: rows of date and day, name, and venue; up to about 7 rows.
  - `mreza`: up to 6 posters in a 3 × 2 grid, each with its name and an optional date line.
- Optional per section:
  - `oznaka`: text of the chip above the title; defaults to "Mjesec godina".
  - `boja`: `plava` for a blue chip instead of orange.
  - `pozadina`: a photo shown behind the slide, darkened to 75%.
- `izvor` is not drawn. It records where the information came from, so the facts can be checked.
- Bosnian typography (quotes „…“, –, …, one-letter words kept with the next word) is applied automatically.
