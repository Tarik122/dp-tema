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
- **/igre**: da li igra radi normalno. Plugin za igru mora ostati uključen.
- Na telefonu: dugme **Rubrike** otvara meni.

## 4. Stara tema i njeni dodaci

Stara tema (tagDiv Newspaper) je instalirala svoje dodatke (tagDiv Composer, tagDiv Cloud Library i slično). Kad nova tema radi nekoliko dana bez problema, te dodatke možete **deaktivirati** u **Dodaci**. Ne brišite ih odmah.

Stranice "Homepage", "Homepage 2…4", "Checkout", "My account" i "Login/Register" ostaci su stare teme. Nova naslovnica ih ne koristi, pa ih kasnije možete prebaciti u smeće.

## 5. Sitnice koje popravljaju izgled

- **Izvodi (podnaslovi):** u `excerpts.md` je gotov izvod za svaki članak. Otvorite članak → desna kolona **Članak → Izvod** → zalijepite → **Ažuriraj**. Izvod se prikazuje kao kurzivni podnaslov ispod naslova. Bez njega podnaslova nema.
- **Članak "Druga gimnazija odnijela pobjedu u pripremnoj utakmici…"** ima autora "admin". Promijenite autora u pravo ime.
- **Opis i autor fotografije:** u **Mediji** kliknite sliku i u polje **Opis slike** (Caption) upišite npr. "Učenici na Igmanu. Foto: Ime Prezime". Tema to prikazuje ispod naslovne fotografije članka.
- **Slike iz Google Docsa:** članak o Gimnazijadi ima 3 slike zalijepljene direktno iz Google Docsa. Takve slike mogu prestati raditi. Preuzmite ih i ponovo ubacite kroz **Dodaj medij**.
- **Viška kategorije** (Movies, Music, News, zzdvojeno, Arhiva, Uncategorized) tema ne prikazuje, ali ih možete i obrisati u **Članci → Kategorije**.
- **Biografija autora:** svako u **Korisnici → Profil → Biografske informacije** može napisati rečenicu-dvije o sebi. Prikazuje se na kraju članka.

## 6. Kako naslovnica bira članke

- **Glavna priča:** članak označen kao "Zalijepljen" (Sticky, u desnoj koloni članka). Ako takvog nema, najnoviji članak iz kategorije **Izdvojeno**, a ako ni njega nema, najnoviji članak.
- **Tri izdvojena teksta:** sljedeći članci iz **Izdvojeno**.
- **Mišljenje:** najnoviji tekstovi iz rubrike Mišljenje koji već nisu gore.
- **Iz arhive:** jedan stariji izdvojeni tekst, mijenja se svaki dan.

Ako neki modul nema dovoljno članaka, ne prikazuje se (nema praznih naslova).

## 7. Uređivanje izgleda

**Izgled → Editor** (Site Editor). Tu se mijenjaju zaglavlje (meni), podnožje (tekst "O nama", linkovi) i rasporedi. Meni je u **Šabloni → Zaglavlje**.

Korisni blokovi za pisanje su u editoru članka pod **Uzorci → Druga perspektiva**: info okvir, intervju, foto galerija, ispravka, napomena redakcije.

Ako nešto pođe po zlu u Site Editoru: otvorite šablon → tri tačke → **Resetuj** i vraća se originalni izgled teme.
