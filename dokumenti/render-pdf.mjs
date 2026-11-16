// Pravi PDF od HTML dokumenata u ovom folderu: node dokumenti/render-pdf.mjs
import { chromium } from 'playwright';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
const dir = path.dirname(fileURLToPath(import.meta.url));
const docs = [['prirucnik-za-redakciju', 'Priručnik za redakciju Druge perspektive']];
const browser = await chromium.launch();
const page = await browser.newPage();
for (const [name, title] of docs) {
	await page.goto(pathToFileURL(path.join(dir, `${name}.html`)).href, { waitUntil: 'networkidle' });
	await page.evaluate(async () => { await document.fonts.ready; });
	await page.pdf({
		path: path.join(dir, `${name}.pdf`), preferCSSPageSize: true, printBackground: true,
	});
	console.log(`${name}.pdf`);
}
await browser.close();
