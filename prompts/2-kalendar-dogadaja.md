# Kalendar događaja: monthly prompt (Claude Code)

This version does **everything**, posters included.

**How to run it:**
1. Start a session in **Claude Code** (claude.ai/code) on the `tarik122/dp-tema` repository.
2. Paste everything below the line, with the month and year changed in the first line.

Claude Code can search the web and download images itself. It fills in the generator in `tools/kalendar`, which turns the events into finished slides, and sends you the PNGs.

What you get back:
- a table of every event with its source link, so you can check it quickly;
- the finished slides (2160 px wide);
- the Instagram caption.

(If you ever have to use regular claude.ai instead, use the style guide project and ask for the same slides. There you will have to add the posters by hand.)

---

Napravi mjesečni **Kalendar događaja** za **[MJESEC] [GODINA]** (npr. novembar 2026) za Instagram Druge perspektive. Uradi sve sam: istraži, izaberi, skini plakate, napravi slajdove i pošalji mi ih. Tekst na slajdovima je na bosanskom (ijekavica). Sa mnom pričaj na engleskom, jednostavno.

Koristi generator u `tools/kalendar` (pročitaj `tools/kalendar/README.md` i primjer `tools/kalendar/primjer-oktobar-2026.json`). On pravi slajdove u DP moderni stilu i sam skida slike s adresa koje mu daš.

## 1. Istraživanje

Nađi događaje u **Sarajevu** u tom mjesecu koji bi zanimali srednjoškolce:

- **Koncerti:** veći koncerti i nastupi (Zetra, Skenderija, BKC, Narodno pozorište, klubovi, festivali), plus Sarajevska filharmonija.
- **Predstave:** repertoari pozorišta. Stranice s repertoarom najbolje je otvoriti direktno (curl ili WebFetch), jer web pretraga slabo nalazi lokalne stvari. Na primjer:
  - Narodno pozorište Sarajevo, `nps.ba/repertoar`
  - Kamerni teatar 55
  - SARTR
  - Pozorište mladih
  - Otvorena scena Obala
- **Filmovi:** filmovi koji tog mjeseca izlaze u sarajevskim kinima, s našim naslovima (npr. „Vrisak 7“, „Kućna pomoćnica“). Za datum premijere provjeri stranicu kina ili distributera.
- **Ostalo (neobavezno):** izložbe, festivali, sajmovi. Samo ako ima bar tri dobra.

Pravila:
- Svaki događaj mora imati **tačan datum i mjesto iz službenog izvora**: stranica izvođača, dvorane, pozorišta, kina ili prodaje karata. Novinski članak je dovoljan samo ako navodi datum i mjesto.
- **Ništa ne izmišljaj.** Ako nešto nisi mogao potvrditi, ne ide na slajd, nego na listu „Nepotvrđeno“.
- Preskoči otkazano, rasprodano i događaje samo za 18+.
- Najviše **6 po mreži** i **7 po listi**. Prednost imaju poznati izvođači, premijere, jeftine i besplatne stvari.
- Dan u sedmici izračunaj iz datuma (npr. u Pythonu), ne pogađaj.

## 2. Plakati i fotografije (sve sam)

- **Predstave:** otvori stranicu predstave i uzmi njenu glavnu sliku (najčešće `og:image` u HTML-u). Tako rade stranice Narodnog pozorišta.
- **Filmovi:** službeni plakat, s naslovom na našem jeziku ako postoji. Izvor je stranica kina ili distributera; ako nema, uzmi međunarodni plakat iz pouzdane baze filmova (npr. TMDB, `image.tmdb.org`).
- **Koncerti:** lista ne treba plakate. Ako želiš pozadinsku fotografiju (`pozadina`), uzmi je samo iz slobodnog izvora.
- **Naslovna:** fotografija Sarajeva sa slobodnom licencom sa Wikimedia Commons, najmanje 1600 px široka. U `potpis` napiši autora i licencu (npr. „Foto: Ime Prezime, CC BY-SA 4.0“). Autora i licencu dobiješ preko Commons API-ja (`prop=imageinfo&iiprop=extmetadata`). Svaki mjesec uzmi drugu fotografiju ako možeš.
- Prije nego staviš adresu u JSON, provjeri je (npr. `curl -sI`): mora vraćati sliku, a ne HTML stranicu.

## 3. Slajdovi

1. Napravi `tools/kalendar/[godina]-[mjesec broj].json` (npr. `2026-11.json`). Redoslijed:
   1. naslovna: oznaka „Vijesti“, naslov „Kalendar događaja za mjesec [mjesec]“;
   2. Koncerti (`lista`);
   3. Predstave (`mreza`, s datumom ispod naziva);
   4. Filmovi (`mreza`, ispod naziva „od 12.11.“ ako znaš premijeru);
   5. Ostalo, ako ga ima.

   Na svaku stavku stavi `izvor`.
2. Pokreni generator:

   ```
   cd tools/kalendar && npm install && cd ../..
   node tools/kalendar/render.mjs tools/kalendar/[datoteka].json
   ```

3. **Pogledaj svaki PNG** (otvori sliku i stvarno je pogledaj). Ako neki plakat nije učitan, izgleda loše izrezan ili je mutan, ili tekst ide preblizu dna, popravi JSON (druga slika, kraći naziv, manje stavki) i pokreni ponovo. Nemoj stati dok svi slajdovi ne izgledaju uredno.
4. Commitaj JSON (slike u `izlaz/` se ne commitaju) i pushaj na svoj branch.

## 4. Šta mi pošalješ

1. **Tabelu provjere:** grupa, datum, dan, naziv, mjesto, link izvora. Ispod nje listu „Nepotvrđeno“, ako ima čega.
2. **Sve PNG slajdove**, redom, kao datoteke koje mogu skinuti.
3. **Opis za Instagram**, u stilu:

   > Ukoliko pronađete malo slobodnog vremena ovaj mjesec, a niste sigurni šta da radite, možete pronaći ideju u ovom pregledu svih događaja u mjesecu [mjesecu]. …

   Zatim po jedna rečenica o najzanimljivijem koncertu, predstavi i filmu, pa „Karte i više informacija potražite na stranicama organizatora.“ Na kraju 3–5 hashtagova (#sarajevo #drugaperspektiva #drugagimnazija).
4. Kratko, na engleskom: šta si izostavio i zašto, i šta da provjerim prije objave.
