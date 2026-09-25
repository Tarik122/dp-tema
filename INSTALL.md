# Kako instalirati temu na drugaperspektiva.org

Ukupno oko 20 minuta. Uradite to kad je malo posjeta (npr. navečer).

## 1. Prije svega: rezervna kopija

U WordPressu idite na **Alati → Izvoz → Sav sadržaj → Preuzmi datoteku izvoza**. Ako hosting nudi "Backup" dugme, kliknite i njega. Tako se uvijek možete vratiti.

## 2. Postavite temu

1. **Izgled → Teme → Dodaj novu temu → Pošalji temu** (Upload Theme).
2. Odaberite `druga-perspektiva.zip` i kliknite **Instaliraj sada**.
3. Kliknite **Pregled uživo** (Live Preview) da vidite kako izgleda prije nego što je uključite.
4. Ako je sve u redu, kliknite **Aktiviraj**.

DP logo se sam postavlja kao logo sajta. Datumi se sami pišu kao "11. april 2026.".

## 3. Provjerite ove stranice

- Naslovnica, jedan članak, rubrika (npr. Sport), pretraga i stranica koja ne postoji (npr. `/abc`).
- **/igre**: da li igra radi normalno (ispod plave trake "IGRE"). Plugin za igru mora ostati uključen. Zatim uradite korake iz dijela **Igre** ispod.
- Na telefonu: dugme **Rubrike** otvara meni.

## 4. Stara tema i njeni dodaci

Stara tema (tagDiv Newspaper) je instalirala svoje dodatke (tagDiv Composer, tagDiv Cloud Library i slično). Kad nova tema radi nekoliko dana bez problema, te dodatke možete **deaktivirati** u **Dodaci**. Ne brišite ih odmah.

Stranice "Homepage", "Homepage 2…4", "Checkout", "My account" i "Login/Register" ostaci su stare teme. Nova naslovnica ih ne koristi, pa ih kasnije možete prebaciti u smeće.

## 5. Sitnice koje popravljaju izgled

- **Izvodi (podnaslovi):** gotov izvod za svaki članak upisuje dodatak **DP izvodi** (`dp-izvodi.zip`): **Dodaci → Dodaj novi → Otpremi dodatak** → izaberite `dp-izvodi.zip` → **Instaliraj** → **Aktiviraj**. Zatim **Alati → DP izvodi** → **Upiši izvode**. Članci koji već imaju izvod se ne diraju. Poslije toga dodatak možete deaktivirati i obrisati; izvodi ostaju. (Isti izvodi su i u `excerpts.md`, ako ih želite ručno lijepiti.) Izvod se prikazuje kao kurzivni podnaslov ispod naslova, a na naslovnici, u rubrikama i u pretrazi kao kratak opis. **Novi izgled se najviše oslanja na izvode.** Za nove članke izvod pišete sami: članak → desna kolona **Članak → Izvod**.
- **Članak "Druga gimnazija odnijela pobjedu u pripremnoj utakmici…"** ima autora "admin". Promijenite autora u pravo ime.
- **Opis i autor fotografije:** u **Mediji** kliknite sliku i u polje **Opis slike** (Caption) upišite npr. "Učenici na Igmanu. Foto: Ime Prezime". Tema to prikazuje ispod naslovne fotografije članka.
- **Slike iz Google Docsa:** članak o Gimnazijadi ima 3 slike zalijepljene direktno iz Google Docsa. Takve slike mogu prestati raditi. Preuzmite ih i ponovo ubacite kroz **Dodaj medij**.
- **Viška kategorije** (Movies, Music, News, zzdvojeno, Arhiva, Uncategorized) tema ne prikazuje, ali ih možete i obrisati u **Članci → Kategorije**.
- **Biografija autora:** svako u **Korisnici → Profil → Biografske informacije** može napisati rečenicu-dvije o sebi. Prikazuje se na kraju članka.

## 6. Kako naslovnica bira članke

- **Glavna priča:** najnoviji članak. **Vi birate drugu:** otvorite članak → desna kolona **Članak** → uključite **Postavi na vrh bloga** (u novijim verzijama piše **Sticky** ili **Zalijepi**) → **Ažuriraj**. Taj članak ostaje glavna priča dok ga ne isključite. Ako ih je uključeno više, gore je najnoviji od njih.
- **Dva teksta lijevo od glavne priče i Najnovije (desno):** sljedećih šest članaka, strogo od najnovijeg prema starijem.
- **Rubrike (Vijesti, Kultura, Sport, Nauka):** po četiri najnovija teksta iz svake rubrike koji već nisu gore. Prvi je velik, sa fotografijom. Rubrika bez tekstova se ne prikazuje.
- **Podnaslovi rubrika** (npr. "Šta se dešava u školi i u gradu."): mijenjaju se u **Izgled → Uređivač → Šabloni → Naslovnica**, klikom na tekst. Rubrika bez dovoljno tekstova se ne prikazuje. Koje se rubrike prikazuju mijenja se u `functions.php` (`DP_HOME_SECTIONS`) i u šablonu naslovnice.
- **Mišljenje:** najnoviji tekstovi iz rubrike Mišljenje koji već nisu gore.
- **Iz arhive:** tri starija teksta iz kategorije **Izdvojeno**, mijenjaju se svaki dan.

Ako neki modul nema dovoljno članaka, ne prikazuje se (nema praznih naslova).

## 7. Vijesti

"Vijesti o školi" i "Ostale vijesti" se na sajtu prikazuju kao jedna rubrika: **Vijesti** (plava oznaka). Stari linkovi i dalje rade. Za nove vijesti dovoljno je označiti kategoriju **Vijesti**. Podrubrike ne morate brisati.

## 7a. Citat na naslovnici

Naslovnica prikazuje jedan veliki citat ("Rečeno"), crno-bijelo, između Kulture i Sporta, iz najnovijeg članka koji ima **istaknuti citat**, pored crno-bijele naslovne fotografije tog članka. U članku dodajte blok **Pullquote** (Istaknuti citat), upišite rečenicu i ispod nje ime osobe (npr. "Lejla Sajra Ramović, učenica Druge gimnazije"). Citat se tada pojavi i u članku i na naslovnici, sa linkom na tekst. Ako nijedan članak nema istaknuti citat, taj dio naslovnice se ne prikazuje.

Dobar kandidat za prvi citat: tekst "Održan performans u Drugoj gimnaziji s porukom ljubavi i mira" počinje rečenicom „Ni jedna ljudska duša u sebi istinski ne može da nosi mržnju.“ Pretvorite taj prvi pasus u blok Pullquote.

## Igre (/igre)

Stranica **/igre** je sada početna za sve igre, u stilu kockaste sveske: naslov od pločica sa slovima, a ispod kartica za svaku igru. Svaka igra je posebna stranica ispod stranice Igre (npr. `/igre/rijec/`).

**Jednom, poslije instalacije teme: premjestite Wordle na njegovu stranicu**

1. **Stranice → Dodaj novu.** Naslov: **Riječ** (ili kako god želite da se igra zove).
2. U sadržaj dodajte blok **Shortcode** i upišite `[dp_wordle]`.
3. U desnoj koloni: **Roditeljska stranica** (Parent) → **Igre**.
4. **Izvod** (Excerpt): jedna rečenica o igri, npr. "Pogodite skrivenu riječ u šest pokušaja." Ona se piše na kartici igre.
5. **Objavi.** Igra je sada na `/igre/rijec/`.
6. Otvorite stranicu **Igre**, obrišite `[dp_wordle]` iz nje i kliknite **Ažuriraj**. Stranica može ostati prazna ili dodajte kratak uvod.

Dok to ne uradite, igra se i dalje prikazuje na `/igre`, pa ništa ne prestaje raditi. Poslije premještanja provjerite da li igra radi na novoj adresi (npr. da li dugme za dijeljenje rezultata i dalje radi).

**Nova igra**

Isto kao koraci 1 do 5: nova stranica, roditelj **Igre**, izvod, objavi. Tema je sama:

- doda karticu na `/igre` i link u plavu traku na naslovnici (do pet igara),
- napravi sliku kartice od slova naziva igre (ako želite pravu sliku, postavite **Istaknutu sliku**),
- stavi oznaku **Novo** prvih 30 dana,
- ispod svake igre prikaže **Više igara**.

**Redoslijed i boje:** igre idu redom kojim su objavljene, pa nova igra ide na kraj. Kartice su redom plava, narandžasta, zelena i navy. Za drugi redoslijed koristite polje **Redoslijed** (Order) u postavkama stranice: manji broj ide prvi.

## 8. Uređivanje izgleda

**Izgled → Editor** (Site Editor). Tu se mijenjaju zaglavlje (meni), podnožje (tekst "O nama", linkovi) i rasporedi. Meni je u **Šabloni → Zaglavlje**.

Korisni blokovi za pisanje su u editoru članka pod **Uzorci → Druga perspektiva**: info okvir, intervju, foto galerija, ispravka, napomena redakcije.

Meni se mijenja u **Zaglavlje**.

Ako nešto pođe po zlu u Site Editoru: otvorite šablon → tri tačke → **Resetuj** i vraća se originalni izgled teme.
