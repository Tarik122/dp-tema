// Pravi PDF od HTML dokumenata u ovom folderu: node dokumenti/render-pdf.mjs
import { chromium } from 'playwright';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
const dir = path.dirname(fileURLToPath(import.meta.url));
const docs = [['vodic-za-pisanje', 'DP – Kako pisati za Drugu perspektivu'], ['sa-sastanka', 'DP – Sa prvog sastanka redakcije']];
const browser = await chromium.launch();
const page = await browser.newPage();
for (const [name, title] of docs) {
	await page.goto(pathToFileURL(path.join(dir, `${name}.html`)).href, { waitUntil: 'networkidle' });
	await page.evaluate(async () => { await document.fonts.ready; });
	await page.pdf({
		path: path.join(dir, `${name}.pdf`), preferCSSPageSize: true, printBackground: true,
		displayHeaderFooter: true, headerTemplate: '<span></span>',
		footerTemplate: '<div style="width:100%;font-size:7.5pt;color:#57516a;padding:0 24mm;display:flex;justify-content:space-between;font-family:sans-serif"><span>Druga perspektiva</span><span class="pageNumber"></span></div>',
	});
	console.log(`${name}.pdf`);
}
await browser.close();
