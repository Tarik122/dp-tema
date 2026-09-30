// Kalendar događaja: od JSON datoteke do gotovih PNG slajdova (DP moderni, 2160 px širine).
//
// Upotreba (iz korijena repozitorija):
//   cd tools/kalendar && npm install && cd ../..
//   node tools/kalendar/render.mjs tools/kalendar/primjer.json
//
// Skine sve fotografije i plakate (URL ili lokalna putanja), ugradi ih u stranicu,
// i spremi slajdove u tools/kalendar/izlaz/<ime-datoteke>/.
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(dir, '../..');
const file = process.argv[2];
if (!file) {
	console.error('Upotreba: node tools/kalendar/render.mjs <kalendar.json>');
	process.exit(1);
}
const data = JSON.parse(fs.readFileSync(file, 'utf8'));
const MJESECI = ['januar', 'februar', 'mart', 'april', 'maj', 'juni', 'juli', 'august', 'septembar', 'oktobar', 'novembar', 'decembar'];
data.mjesecVeliko = data.mjesec ? data.mjesec.charAt(0).toUpperCase() + data.mjesec.slice(1) : '';
if (data.mjesec && !MJESECI.includes(data.mjesec.toLowerCase())) console.warn(`Upozorenje: "${data.mjesec}" nije naziv mjeseca.`);

const problems = [];

/** Učita sliku s interneta ili s diska i vrati je kao data: URL (ili null ako ne uspije). */
async function load(src, what) {
	if (!src) return null;
	try {
		let buf, type;
		if (/^https?:\/\//.test(src)) {
			const res = await fetch(src, {
				headers: { 'User-Agent': 'Mozilla/5.0 (DrugaPerspektiva kalendar; +https://drugaperspektiva.org)', Accept: 'image/*' },
				redirect: 'follow',
				signal: AbortSignal.timeout(30000),
			});
			if (!res.ok) throw new Error('HTTP ' + res.status);
			type = (res.headers.get('content-type') || '').split(';')[0];
			buf = Buffer.from(await res.arrayBuffer());
		} else {
			const p = path.resolve(path.dirname(file), src);
			buf = fs.readFileSync(p);
			type = { '.png': 'image/png', '.webp': 'image/webp', '.gif': 'image/gif' }[path.extname(p).toLowerCase()] || 'image/jpeg';
		}
		if (!type.startsWith('image/')) throw new Error('nije slika (' + (type || 'nepoznat tip') + ')');
		if (buf.length < 2000) throw new Error('premala datoteka');
		return `data:${type};base64,${buf.toString('base64')}`;
	} catch (e) {
		problems.push(`${what}: ${src} (${e.message})`);
		return null;
	}
}

if (data.naslovna) data.naslovna.fotografija = await load(data.naslovna.fotografija, 'Naslovna fotografija');
for (const sec of data.sekcije || []) {
	sec.pozadina = await load(sec.pozadina, `Pozadina "${sec.naslov}"`);
	for (const it of sec.stavke || []) it.plakat = await load(it.plakat, `Plakat "${it.naziv}"`);
}
data.logo = 'data:image/png;base64,' + fs.readFileSync(path.join(root, 'dp-objave/assets/logo-bijeli.png')).toString('base64');

const out = path.join(dir, 'izlaz', path.basename(file, '.json'));
fs.mkdirSync(out, { recursive: true });

const browser = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
const page = await browser.newPage({ viewport: { width: 1200, height: 2000 }, deviceScaleFactor: 2 });
await page.addInitScript((d) => { window.DATA = d; }, data);
await page.goto(pathToFileURL(path.join(dir, 'template.html')).href);
await page.waitForFunction(() => window.READY === true);
await page.evaluate(async () => {
	await document.fonts.ready;
	await Promise.all([...document.images].map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; }))));
});

const slides = page.locator('.slide');
const n = await slides.count();
const base = `kalendar-${(data.mjesec || 'mjesec').toLowerCase()}-${data.godina || ''}`.replace(/-$/, '');
const files = [];
for (let i = 0; i < n; i++) {
	const f = path.join(out, `${base}-${i + 1}.png`);
	await slides.nth(i).screenshot({ path: f });
	files.push(path.relative(root, f));
}

// Provjera: tekst ili plakati ne smiju izaći izvan slajda.
const overflow = await page.evaluate(() => [...document.querySelectorAll('.slide')].map((s, i) => {
	const r = s.getBoundingClientRect();
	const bad = [...s.querySelectorAll('.body, .text, .head')].some((e) => e.getBoundingClientRect().bottom > r.bottom - 60);
	return bad ? i + 1 : null;
}).filter(Boolean));
await browser.close();

console.log('Slajdovi:\n  ' + files.join('\n  '));
if (overflow.length) console.log('\nPAŽNJA: sadržaj je preblizu dna ili izlazi na slajdu ' + overflow.join(', ') + '. Skrati tekst ili smanji broj stavki.');
if (problems.length) console.log('\nNisu učitane (umjesto njih je prazan okvir s nazivom):\n  ' + problems.join('\n  '));
