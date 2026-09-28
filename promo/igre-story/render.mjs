// Renders story.html into five 1080×1920 PNGs, with and without the link placeholder.
// Run from the repo root: node promo/igre-story/render.mjs
import { chromium } from 'playwright';
import { fileURLToPath } from 'node:url';

const dir = fileURLToPath(new URL('.', import.meta.url));
const b = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
const p = await b.newPage({ viewport: { width: 1200, height: 2000 } });
await p.goto('file://' + dir + 'story.html');
await p.evaluate(() => document.fonts.ready);
for (const clean of [false, true]) {
	await p.evaluate((c) => document.body.classList.toggle('clean', c), clean);
	for (let i = 1; i <= 5; i++) {
		await p.locator('#f' + i).screenshot({ path: `${dir}story-${i}${clean ? '-bez-okvira' : ''}.png` });
	}
}
await b.close();

// 16:9 slide for the school display (1920 × 1080).
const b2 = await chromium.launch(process.env.CHROME ? { executablePath: process.env.CHROME } : {});
const p2 = await b2.newPage({ viewport: { width: 1920, height: 1080 } });
await p2.goto('file://' + dir + 'ekran.html');
await p2.evaluate(() => document.fonts.ready);
await p2.locator('#ekran').screenshot({ path: `${dir}ekran-igre.png` });
await b2.close();
