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

Igru **Riječ** pravi dodatak **DP igre** (`dp-igre.zip`, izvorni kod u folderu `dp-igre/`). Ako još nije instaliran: **Dodaci → Dodaj novi → Otpremi dodatak** → izaberite `dp-igre.zip` → **Instaliraj** → **Aktiviraj**. Kontrolni panel je u meniju **Riječ dana** (posebne riječi za određeni dan, liste riječi, igrači, Google prijava). Detaljne upute su u `dp-igre/README.md`.

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

**Kontekst (nova igra u dodatku DP igre)**

Prvo ažurirajte dodatak: **Dodaci → Dodaj novi → Otpremi dodatak** → `dp-igre.zip` → **Zamijeni postojeći**. Dodatak je sada oko 4 MB (u njemu je rječnik za Kontekst). Ako WordPress kaže da je fajl prevelik, pitajte hosting da povećaju "upload_max_filesize" (dovoljno je 16 MB).

Zatim napravite stranicu kao u koracima 1 do 5 iznad:

| Naslov | Kratki kod | Izvod (za karticu) |
|---|---|---|
| Kontekst | `[dp_kontekst]` | Pogodi tajnu riječ po značenju. |

Roditeljska stranica je **Igre**, a šablon **Igre: jedna igra**. Igra radi sama: nova riječ stiže svaki dan u ponoć (riječi ima za oko godinu dana, a onda kreću ispočetka). Brojanje (#1, #2…) počinje od dana kad ste ažurirali dodatak. Pomoć košta sve više: prva 2 pokušaja, druga 4, treća 8.

**Ljestve (nova igra, verzija 1.4.0)**

Ažurirajte dodatak isto kao za Kontekst (`dp-igre.zip` → **Zamijeni postojeći**), pa napravite još jednu stranicu:

| Naslov | Kratki kod | Izvod (za karticu) |
|---|---|---|
| Ljestve | `[dp_ljestve]` | Od riječi do riječi, slovo po slovo. |

Roditeljska stranica je **Igre**, šablon **Igre: jedna igra**. Svaki dan su nove ljestve (ima ih za oko dvije godine). Brojanje počinje od dana kad ste ažurirali dodatak.

Ako ste napravili stranicu **Tramvaj**, obrišite je: ta igra je izbačena. Prijava školskim mailom, ljestvice i nizovi rade isto kao u Riječi, bez dodatnog podešavanja.

**Redoslijed i boje:** igre idu redom kojim su objavljene, pa nova igra ide na kraj. Kartice su redom plava, narandžasta, zelena i crna. Za drugi redoslijed koristite polje **Redoslijed** (Order) u postavkama stranice: manji broj ide prvi.

## 9. Dodatak DP postavke (kontrolna ploča teme)

Dodatak **DP postavke** (`dp-postavke.zip`) daje više kontrole nad temom bez diranja koda. Instalacija: **Dodaci → Dodaj novi → Otpremi dodatak** → `dp-postavke.zip` → **Instaliraj** → **Aktiviraj**. U meniju se pojavi **Druga perspektiva**.

- **Naslovnica:**
  - **Glavna priča:** birate članak iz liste, bez zakačivanja.
  - **Najnovije:** uključujete ili isključujete stupac desno od glavne priče.
  - **Rubrike:** za svaku birate redoslijed, prikaz, iz koje kategorije uzima članke, koliko ih prikazuje i podnaslov.
  - **Iz arhive:** birate koliko stari tekstovi dolaze.
- **Članak:** uključujete ili isključujete:
  - veliko početno slovo i DP znak na kraju;
  - vrijeme čitanja i okvir o autoru;
  - "Pročitajte još", i birate koliko članaka prikazuje.
- **Podnožje:** tekst o novinama, linkovi (Instagram, e-mail, TikTok…) i mala poruka na dnu.
- **Kategorije:** boja oznake za svaku rubriku (plava, narandžasta, zelena, crna).
- **Opcije:** datum u zaglavlju, i koliko dana igre nose oznaku "Novo!".

Svaka kartica ima link **Vrati postavke teme za ovu karticu**, ako nešto želite vratiti na početno.
- **Čišćenje:** briše ono što je ostalo od stare teme:
  - demo stranice ("Homepage", "Checkout", "My account"…);
  - demo slike koje nijedan članak ne koristi;
  - prazne kategorije i postavke stare teme;
  - podatke isključenih dodataka uz članke;
  - po želji još stare menije, stare verzije članaka i Otpad.

  Kako se koristi:
  1. **Prvo napravite sigurnosnu kopiju baze** (na hostingu ili dodatkom UpdraftPlus).
  2. Pregledajte listu i otvorite "Pogledaj šta je pronađeno".
  3. Skinite kvačice sa onoga što želite zadržati.
  4. Označite "Imam sigurnosnu kopiju baze" i kliknite **Očisti izabrano**.

  Naslovna stranica, jedna politika privatnosti i stranice Igre se nikad ne diraju. Podaci dodataka koji su još uključeni (npr. LiteSpeed Cache) se također ne diraju. Stranice idu u Otpad, a sve ostalo se briše trajno.

Tema radi i bez ovog dodatka; tada vrijede zadane postavke.

## 8. Uređivanje izgleda

**Izgled → Editor** (Site Editor). Tu se mijenjaju zaglavlje (meni), podnožje (tekst "O nama", linkovi) i rasporedi. Meni je u **Šabloni → Zaglavlje**.

Korisni blokovi za pisanje su u editoru članka pod **Uzorci → Druga perspektiva**: info okvir, intervju, foto galerija, ispravka, napomena redakcije.

Meni se mijenja u **Zaglavlje**.

Ako nešto pođe po zlu u Site Editoru: otvorite šablon → tri tačke → **Resetuj** i vraća se originalni izgled teme.

## 10. Dodatak DP objave (Instagram objave i storyji)

**Dodaci → Dodaj novi → Otpremi dodatak** → `dp-objave.zip` → **Aktiviraj**. U meniju se pojavi **Objave za mreže**.

Kod svakog članka (u listi članaka, kad pređete mišem preko naslova) je link **Napravi objavu**: naslov, rubrika, fotografija i citati se popune sami. Tekst, boje i fotografije se mijenjaju desno od pregleda, fotografija se pomjera povlačenjem. **Preuzmi sve** spremi slike (PNG) u tačnoj veličini za Instagram. Kliknite **Objavi** da se dizajn spremi za kasnije; na sajtu se ne pojavljuje.

