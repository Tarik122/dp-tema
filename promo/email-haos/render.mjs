// Pravi animirani cover: email-haos.mp4 (1080×1440, 30 fps) i email-haos.png (zadnja sličica, 2x).
// Pokretanje iz korijena repozitorija: node promo/email-haos/render.mjs
// Za brzi pregled samo nekoliko sličica: node promo/email-haos/render.mjs --pregled
import { chromium } from 'playwright';
import path from 'node:path';
import fs from 'node:fs';
import os from 'node:os';
import { execFileSync } from 'node:child_process';
import { fileURLToPath, pathToFileURL } from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const name = path.basename(dir);
const preview = process.argv.includes('--pregled');
const chrome = process.env.CHROME || ['/opt/pw-browsers'].flatMap((b) => {
	try { return fs.readdirSync(b).filter((d) => /^chromium-\d+$/.test(d)).map((d) => path.join(b, d, 'chrome-linux/chrome')); } catch { return []; }
}).find((p) => fs.existsSync(p));
const browser = await chromium.launch(chrome ? { executablePath: chrome } : {});

async function open(scale) {
	const page = await browser.newPage({ viewport: { width: 1080, height: 1440 }, deviceScaleFactor: scale });
	await page.goto(pathToFileURL(path.join(dir, 'dizajn.html')).href, { waitUntil: 'networkidle' });
	await page.evaluate(async () => {
		await Promise.all(['400 20px Lato', '700 20px Lato', '900 20px Lato'].map((f) => document.fonts.load(f, 'čćđšž')));
		await document.fonts.ready;
	});
	return page;
}

const frames = await (await open(1)).evaluate(() => window.dp.frames);

if (preview) {
	const page = await open(1);
	let done = 0;
	for (const f of [30, 75, 120, 160, 200, frames - 1]) {
		await page.evaluate((n) => window.dp.step(n), f - done); done = f;
		const out = path.join(dir, `pregled-${String(f).padStart(3, '0')}.png`);
		await page.locator('#slide').screenshot({ path: out });
		console.log(path.relative(process.cwd(), out));
	}
	await browser.close();
	process.exit(0);
}

// Video: sličica po sličica, pa ffmpeg.
const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'dp-frames-'));
const page = await open(1);
for (let i = 0; i < frames; i++) {
	await page.evaluate(() => window.dp.step(1));
	await page.locator('#slide').screenshot({ path: path.join(tmp, `f${String(i).padStart(4, '0')}.png`) });
}
const mp4 = path.join(dir, `${name}.mp4`);
execFileSync('ffmpeg', ['-y', '-loglevel', 'error', '-framerate', '30', '-i', path.join(tmp, 'f%04d.png'),
	'-c:v', 'libx264', '-preset', 'slow', '-crf', '17', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', mp4]);
fs.rmSync(tmp, { recursive: true, force: true });
console.log(path.relative(process.cwd(), mp4));

// Slika zadnje sličice u dvostrukoj rezoluciji (naslovna slika članka, ili cover u Instagramu).
const still = await open(2);
await still.evaluate((n) => window.dp.step(n), frames);
const png = path.join(dir, `${name}.png`);
await still.locator('#slide').screenshot({ path: png });
console.log(path.relative(process.cwd(), png));
await browser.close();
