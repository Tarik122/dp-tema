// Svaki <section class="slide"> iz dizajn.html postaje PNG u dvostrukoj rezoluciji (2160 px širine za post).
// Pokretanje iz korijena repozitorija: node promo/<naziv>/render.mjs
import { chromium } from 'playwright';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath, pathToFileURL } from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const name = path.basename(dir);
const chrome = process.env.CHROME || ['/opt/pw-browsers'].flatMap((b) => {
	try { return fs.readdirSync(b).filter((d) => /^chromium-\d+$/.test(d)).map((d) => path.join(b, d, 'chrome-linux/chrome')); } catch { return []; }
}).find((p) => fs.existsSync(p));

const browser = await chromium.launch(chrome ? { executablePath: chrome } : {});
const page = await browser.newPage({ viewport: { width: 2000, height: 2200 }, deviceScaleFactor: 2 });
await page.goto(pathToFileURL(path.join(dir, 'dizajn.html')).href, { waitUntil: 'networkidle' });
await page.evaluate(async () => {
	await document.fonts.ready;
	await Promise.all([...document.images].map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; }))));
});

const broken = await page.evaluate(() => [...document.images].filter((i) => !i.naturalWidth).map((i) => i.src.slice(0, 120)));
const slides = page.locator('section.slide');
const n = await slides.count();
for (let i = 0; i < n; i++) {
	const f = path.join(dir, `${name}-${i + 1}.png`);
	await slides.nth(i).screenshot({ path: f });
	console.log(path.relative(process.cwd(), f));
}
await browser.close();
if (broken.length) console.log('\nSlike koje se nisu učitale:\n  ' + broken.join('\n  '));
