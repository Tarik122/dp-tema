# Digitalni haos: After Effects paket

## Brzi put (skripta)

1. Instaliraj font **Lato** (besplatan: fonts.google.com/specimen/Lato), ako ga već nemaš.
2. Otvori After Effects (radi u verzijama 2020 i novijim).
3. **File > Scripts > Run Script File…** i odaberi `email-haos.jsx`.
   Fajl mora ostati u ovom folderu, pored `panel.png` i foldera `kartice/`.
4. Skripta napravi kompoziciju **Digitalni haos** (1080×1440, 30 fps, 8,8 s):
   - svaki mail je zaseban layer s keyframeovima za poziciju, rotaciju i veličinu (iz fizičke simulacije);
   - motion blur je uključen;
   - **Brojač** je tekst layer (mijenja se sam kroz vrijeme), a **Oznaka** iza njega se sama širi prema dužini teksta;
   - **Panel** je slika s naslovom i logom.
5. Export: **Composition > Add to Render Queue**, format H.264 (ili Adobe Media Encoder), 1080×1440.

Ako skripta javi grešku o dozvolama: Edit (Mac: After Effects) > Settings > Scripting & Expressions >
uključi „Allow Scripts to Write Files and Access Network“, pa pokreni ponovo.

## Šta se lako mijenja u AE

- **Brzina:** selektuj sve layere s karticama i koristi Time Stretch, ili pomjeri cijelu kompoziciju.
- **Ritam:** pomjeraj layere kartica po vremenu (svaka se pojavljuje u svom trenutku).
- **Izgled kartica:** kartice su PNG slike; dodaj im npr. Drop Shadow efekat ako želiš.
- **Brojač:** tekst je u keyframeovima „Source Text“ na layeru Brojač.

## Newton

U folderu `kartice/` je svaki mail kao zaseban PNG s providnom pozadinom (2x rezolucija, koristi 50 % scale).
Za Newton: napravi kompoziciju s tim slikama, obriši keyframeove (ili ne pokreći skriptu), u Newtonu
postavi gravitaciju na 0 i pusti da se kartice pojavljuju u sredini.
