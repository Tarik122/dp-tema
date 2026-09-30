# Ažuriranje teksta „Da li moj trud vrijedi manje?“

Tekst na stranici: https://drugaperspektiva.org/da-li-moj-trud-vrijedi-manje/

Lični dio priče (ponedjeljak, kafa, razgovor s prijateljem) ostaje kako je napisan jer opisuje šta se desilo u aprilu 2025. Mijenjaju se samo brojke, grafikoni i dva pasusa ispod.

## 1. Napomena na vrhu teksta

Zalijepi kao prvi blok (može pattern „Ispravka / ažuriranje“ iz teme):

> **Ažurirano 30. septembra 2026.** Tekst je objavljen u aprilu 2025. Iznosi stipendija ažurirani su prema konkursima općina za školsku 2025/2026. godinu, a prosječne plate prema podacima Federalnog zavoda za statistiku za 2024. godinu. Mapa i grafikon sada obuhvataju svih devet općina Kantona Sarajevo. Općina Novo Sarajevo je u međuvremenu udvostručila stipendije, pa sada, zajedno s Općinom Centar, daje najviše: 2000 KM.

## 2. Pasus ispod mape (zamijeniti)

Staro:

> Kao što vidite na mapi, raspon stipendija se kreće čak od 675KM u Općini Vogošća, pa sve do 2000KM u Općini Centar, što je skoro trostruko veća vrijednost.

Novo:

> Kao što vidite na mapi, za školsku 2025/2026. godinu raspon stipendija se kreće od 765 KM u Općini Vogošća, pa sve do 2000 KM u općinama Centar i Novo Sarajevo, što je više nego dvostruko veća vrijednost.

(Rečenica koja slijedi, „Smatram, a nadam se da se i vi slažete sa mnom…“, ostaje ista.)

## 3. Pasus ispod grafikona s platama (zamijeniti prvu rečenicu)

Staro:

> Kontradiktorno sa potrebama djece u općinama kao što su Ilijaš i Vogošća, gdje su prosječne mjesečne plate najniže, stipendije su također količinski najmanje.

Novo:

> Kontradiktorno sa potrebama djece u općinama kao što su Vogošća, Hadžići i Trnovo, gdje su prosječne mjesečne plate među najnižima, stipendije su također među najmanjima. Najveće stipendije, po 2000 KM, daju Centar i Novo Sarajevo, dvije općine s najvišim platama u kantonu.

(Ostatak pasusa, od „Ovdje se postavlja pitanje…“, ostaje isti.)

## 4. Mapa (Datawrapper): „Visina stipendije po općinama u KM“

Podaci: `podaci/stipendije-2025-26.csv`. U Datawrapperu otvori postojeću mapu, zamijeni podatke i dodaj Hadžiće i Trnovo, koji na staroj mapi nisu imali iznos.

- Naslov: **Visina stipendije za učenike po općinama, školska 2025/2026.**
- Raspon legende: 765 KM – 2000 KM
- Napomena ispod mape: *Godišnji iznos za učenika s prosjekom 5.0. U Ilidži i Novom Gradu stipendija zavisi i od prihoda domaćinstva. Izvor: konkursi i odluke općina za 2025/2026.*

## 5. Grafikon (Datawrapper): „Usporedba stipendije i prosječne mjesečne plaće po općinama“

Podaci: `podaci/plate-i-stipendije.csv` (devet općina, poredane od najniže plate).

- Kolone: *Prosječna mjesečna neto plata 2024.* i *Iznos stipendije za školsku 2025/2026.*
- Istaknuti (tamnija boja): Centar, Novo Sarajevo, Vogošća
- Napomena: *Svi prikazani iznosi su neto. Plate su za 2024. godinu (FZS, „Kanton Sarajevo u brojkama 2025“). Stipendije su godišnji iznosi za školsku 2025/2026.*
- Usput popravi grešku u zaglavlju kolone: „Prosječna mjesečna **plata plata**“ → „Prosječna mjesečna plata“.

## Izvori (za provjeru)

| Općina | Iznos | Izvor |
|---|---|---|
| Centar | 200 KM × 10 (prosjek 5.00) | [Javni poziv Općine Centar 2025/2026](https://stipendije.ba/konkursi/javni-poziv-opcine-centar-za-stipendiranje-ucenika-studenata-u-skolskoj-akademskoj-2025-2026-godini/), [centar.ba](https://www.centar.ba/vijesti/21554/objavljen-javni-poziv-za-stipendiranje-srednjoskolaca-i-studenata) |
| Novo Sarajevo | 200 KM × 10 (prosjek 5.00) | [Konkurs Općine Novo Sarajevo 2025/26](https://stipendije.ba/konkursi/konkurs-opcine-novo-sarajevo-za-dodjelu-stipendija-ucenicima-srednjih-skola-i-studentima-u-sk-ak-2025-26-godini/) |
| Ilidža | 143 KM × 10 | [Konkurs Općine Ilidža 2025/2026](https://stipendije.ba/konkursi/konkurs-opcine-ilidza-za-stipendiranje-ucenika-u-srednjem-i-studenata-u-visokoskolskom-obrazovanju-koji-su-ostvarili-uspjeh-u-prethodnoj-skolskoj-akademskoj-godini-a-imaju-odredeni-iznos-prihoda-po-c/) |
| Stari Grad | 150 KM × 9 | [Četiri javna poziva Općine Stari Grad](https://stipendije.ba/novosti/opcina-stari-grad-sarajevo-objavila-cetiri-javna-poziva-za-dodjelu-stipendija-u-sk-ak-2025-26-godini/) |
| Ilijaš | 130 KM × 9 | [ilijas.info, 17. 4. 2026.](https://ilijas.info/opcinsko-vijece-ilijas-usvojilo-plan-stipendiranja-421-ucenik-i-student-dobija-podrsku/) (9 mjeseci izračunato iz budžeta u članku) |
| Novi Grad | 100 KM × 10 | [Stipendije Općine Novi Grad 2025/26](https://stipendije.ba/novosti/ucenicke-i-studentske-stipendije-opcine-novi-grad-sarajevo-za-sk-ak-2025-26-godinu/) |
| Trnovo | 100 KM × 10 | [Stipendije u Trnovu 2025/26](https://stipendije.ba/novosti/u-trnovu-dodijeljene-opcinske-stipendije-za-skolsku-akademsku-2025-26-godinu/) |
| Hadžići | 90 KM × 10 | [studomat.ba](https://studomat.ba/konkurs-opcine-hadzici-za-130-stipendija-90-km-za-ucenike-i-190-km-za-studente/199040/), [DP: Informacije o stipendijama](https://drugaperspektiva.org/informacije-o-stipendijama-za-ucenike-u-kantonu-sarajevo/) |
| Vogošća | 85 KM × 9 | [Potpisivanje ugovora, 9. 2. 2026.](https://stipendije.ba/novosti/pocelo-potpisivanje-ugovora-sa-stipendistima-opcine-vogosca/) |
| Plate 2024. | po općinama | [FZS, Kanton Sarajevo u brojkama 2025, tabela 4.2](http://fzs.ba/wp-content/uploads/2026/03/kanton-sarajevo.pdf) |
