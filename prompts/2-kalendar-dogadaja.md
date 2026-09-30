# Kalendar događaja: monthly prompt

**Before you start:**
1. Use it in the same Claude Project as the DP moderni style guide (`1-dp-moderni-stil.md`), so the design rules apply.
2. Turn on **web search** in the chat (the research depends on it).
3. Copy everything below the line, change the month and year in the first line, and send.

It gives you, in this order:
1. a checked list of events with sources;
2. the finished carousel as an editor where you add the posters and download PNGs;
3. the caption;
4. links to every poster.

---

Napravi mjesečni **Kalendar događaja** za **[MJESEC] [GODINA]** (npr. mart 2027) za Instagram Druge perspektive. Radi sve sam: istraži, izaberi, napiši i dizajniraj. Tekst na slajdovima je na bosanskom (ijekavica). Dizajn prati DP moderni stil iz uputa ovog projekta. Ako ih ne vidiš, reci mi da ih zalijepim.

## 1. Istraživanje (web search)

Nađi događaje u **Sarajevu** u tom mjesecu koji bi zanimali srednjoškolce, u četiri grupe:

- **Koncerti:** veći koncerti i nastupi (dvorane, BKC, Narodno pozorište, klubovi, festivali), plus Sarajevska filharmonija ako nastupa.
- **Predstave:** repertoar sarajevskih pozorišta za taj mjesec, npr.:
  - Narodno pozorište Sarajevo
  - Kamerni teatar 55
  - SARTR
  - Pozorište mladih
  - Otvorena scena Obala
- **Filmovi:** filmovi koji tog mjeseca izlaze ili su najgledaniji u sarajevskim kinima (provjeri koja kina trenutno rade i njihove programe).
  - Koristi naslove pod kojima se prikazuju kod nas, npr. „Vrisak 7“, „Kućna pomoćnica“.
  - Navedi datum od kada je film u kinima.
- **Ostalo (neobavezno):** izložbe, festivali, sajmovi, veći sportski događaji. Samo ako ima bar tri dobra.

Pravila istraživanja:
- Svaki događaj mora imati **tačan datum i mjesto sa službene stranice** izvođača, dvorane, pozorišta, kina ili prodaje karata (npr. Entrio, Eventim, Karter). Novinski članak je dovoljan samo ako navodi datum i mjesto.
- **Ništa ne izmišljaj.** Ako nešto nije potvrđeno, ne stavljaj ga na slajd, nego ga navedi u listi „Nepotvrđeno“.
- Preskoči događaje koji su već otkazani ili rasprodani, i one koji nisu za mlađe od 18.
- Po grupi izaberi **najviše 6** događaja: prednost imaju poznati izvođači, premijere, jeftine ili besplatne stvari i ono o čemu učenici pričaju.
- Datumi: `07.03.` i dan u sedmici punom riječi (Subota, Nedjelja…). Provjeri da dan u sedmici odgovara datumu te godine.

Prvo mi pokaži **tabelu provjere** (u chatu, ne na slajdu): grupa, datum, dan, naziv, mjesto, link izvora. Ispod nje stavi „Nepotvrđeno“ (ako ima čega). Onda nastavi s dizajnom, bez čekanja.

## 2. Karusel (post 1080 × 1440, isti HTML editor kao u uputama stila)

Isti redoslijed i sadržaj kao dosadašnji kalendari, ali u DP moderni stilu:

1. **Naslovna**
   - Fotografija Sarajeva: slot „Dodaj fotografiju“, ja je dodajem.
   - Oznaka „Vijesti“ (plava).
   - Naslov „Kalendar događaja za mjesec [mjesec]“ (npr. „Kalendar događaja za mjesec mart“).
   - Bez autora.
2. **Koncerti** (Lista, tri kolone): datum i dan | izvođač (podebljano, glavna kolona) | mjesto (mirnije).
   - Chip „[Mjesec] [godina]“, naslov „Koncerti“.
   - Najviše 7 redova, poredano po datumu.
3. **Predstave** (Mreža 3 × 2)
   - Za svaku predstavu: slot za plakat, ispod naziv (podebljano) i datum (npr. „15.03.“).
   - Ako se ista predstava igra više puta, napiši prvi datum i „i dalje“ ili navedi datume („15. i 16.03.“).
   - Chip „[Mjesec] [godina]“, naslov „Predstave“.
4. **Filmovi** (Mreža 3 × 2)
   - Za svaki film: slot za plakat, ispod naziv, a ispod toga „od 12.03.“ ako znaš datum premijere.
   - Chip „[Mjesec] [godina]“, naslov „Filmovi“.
5. **Ostalo** (samo ako ga ima): Lista ili Mreža, naslov npr. „Izložbe i festivali“.

Za slajdove 2–5:
- Pozadina je Ljubičasta.
- Svaki slajd ima i neobavezan slot „Pozadinska fotografija“ (npr. publika na koncertu, pozorišna zavjesa, sjedišta u kinu). Ako je dodam, zatamni je ravnomjerno na 75% crne, da se tekst i plakati uvijek dobro čitaju.
- Plakate ubacujem sam, preko dugmeta na svakom slotu.
- Plakat ima oblik 2:3 i uvijek je cijel: `object-fit: contain` na tamnoj pozadini slota, nikad izrezan.
- Svaki slajd ima DP logo dolje desno, dugme „Preuzmi PNG“ i dugme „Preuzmi sve“ na vrhu.

Nazive i datume napiši tačno kako su na službenim stranicama, s pravim bosanskim slovima i navodnicima „…“.

## 3. Opis za Instagram

Kratak, topao tekst na bosanskom, u stilu:

> Ukoliko pronađete malo slobodnog vremena ovaj mjesec, a niste sigurni šta da radite, možete pronaći ideju u ovom pregledu svih događaja u mjesecu [mjesecu]. …

Zatim jedna rečenica po grupi s po jednim ili dva najzanimljivija događaja, pa „Karte i više informacija potražite na stranicama organizatora.“ Na kraju 3–5 hashtagova (#sarajevo #drugaperspektiva #kalendardogađaja #drugagimnazija).

## 4. Plakati

Na kraju mi daj listu: za svaki događaj na slajdovima naziv i direktan link na službeni plakat, ili stranicu s koje ga mogu preuzeti. Poredaj ih istim redom kao slotove u editoru, da ih mogu redom ubaciti.

## Provjera prije nego završiš

- [ ] Svaki događaj na slajdu je u tabeli provjere sa izvorom.
- [ ] Datumi i dani u sedmici se slažu.
- [ ] Nijedan događaj nije izvan traženog mjeseca.
- [ ] Najviše 6 po mreži i 7 po listi.
- [ ] Svi naslovi su pravilno napisani (č, ć, đ, š, ž; „navodnici“; crtica – u rasponima).
- [ ] Dizajn poštuje DP moderni upute: margine, veličine, boje i sigurne zone.
