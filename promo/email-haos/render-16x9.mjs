// Statična naslovna slika 16:9 za članak na sajtu: zadnja sličica simulacije iz naslovna-16x9.html (2x).
// Pokretanje iz korijena repozitorija: node promo/email-haos/render-16x9.mjs
import { chromium } from 'playwright';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath, pathToFileURL } from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const chrome = ['/opt/pw-browsers'].flatMap((b) => {
	try { return fs.readdirSync(b).filter((d) => /^chromium-\d+$/.test(d)).map((d) => path.join(b, d, 'chrome-linux/chrome')); } catch { return []; }
}).find((p) => fs.existsSync(p));
const browser = await chromium.launch(chrome ? { executablePath: chrome } : {});
const page = await browser.newPage({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 2 });
await page.goto(pathToFileURL(path.join(dir, 'naslovna-16x9.html')).href, { waitUntil: 'networkidle' });
await page.evaluate(async () => {
	await Promise.all(['400 20px Lato', '700 20px Lato'].map((f) => document.fonts.load(f, 'čćđšž')));
	await document.fonts.ready;
	window.dp.step(window.dp.frames);
});
const out = path.join(dir, 'email-haos-16x9.png');
await page.locator('#slide').screenshot({ path: out });
await browser.close();
console.log(path.relative(process.cwd(), out));
