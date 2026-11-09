// Pravi paket za After Effects u promo/email-haos/ae/:
//   kartice/kartica-NN.png  (svaki mail kao PNG s providnom pozadinom, 2x)
//   panel.png               (ljubičasti panel s naslovom i logom, bez brojača, 2x)
//   email-haos.jsx          (skripta koja u AE napravi kompoziciju s keyframeovima iz simulacije)
// Pokretanje iz korijena repozitorija: node promo/email-haos/ae-export.mjs
import { chromium } from 'playwright';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath, pathToFileURL } from 'node:url';

const dir = path.dirname(fileURLToPath(import.meta.url));
const out = path.join(dir, 'ae');
fs.rmSync(out, { recursive: true, force: true });
fs.mkdirSync(path.join(out, 'kartice'), { recursive: true });

const chrome = process.env.CHROME || ['/opt/pw-browsers'].flatMap((b) => {
	try { return fs.readdirSync(b).filter((d) => /^chromium-\d+$/.test(d)).map((d) => path.join(b, d, 'chrome-linux/chrome')); } catch { return []; }
}).find((p) => fs.existsSync(p));
const browser = await chromium.launch(chrome ? { executablePath: chrome } : {});
const page = await browser.newPage({ viewport: { width: 1080, height: 1440 }, deviceScaleFactor: 2 });
await page.goto(pathToFileURL(path.join(dir, 'dizajn.html')).href, { waitUntil: 'networkidle' });
await page.evaluate(async () => {
	await Promise.all(['400 20px Lato', '700 20px Lato', '900 20px Lato'].map((f) => document.fonts.load(f, 'čćđšž')));
	await document.fonts.ready;
});

const frames = await page.evaluate(() => window.dp.frames);
await page.evaluate((n) => window.dp.step(n), frames);
const track = await page.evaluate(() => window.dp.track());
const labels = await page.evaluate(() => window.dp.labels());

// Kartice
for (let i = 0; i < track.length; i++) {
	const data = await page.evaluate((n) => window.dp.cardPng(n), i);
	fs.writeFileSync(path.join(out, 'kartice', `kartica-${String(i + 1).padStart(2, '0')}.png`), Buffer.from(data.split(',')[1], 'base64'));
}

// Panel bez brojača (brojač i narandžastu oznaku pravi skripta, da se mogu mijenjati u AE).
// Panel (naslov, podnaslov, logo) s providnom pozadinom; ljubičastu pozadinu pravi skripta.
await page.evaluate(() => {
	document.getElementById('brojac').style.visibility = 'hidden';
	document.getElementById('c').style.visibility = 'hidden';
	document.body.style.background = 'transparent';
	document.getElementById('slide').style.background = 'transparent';
});
const box = await page.locator('.panel').boundingBox();
await page.locator('.panel').screenshot({ path: path.join(out, 'panel.png'), omitBackground: true });
await browser.close();

// Podaci za skriptu. Sličica f u simulaciji = vrijeme (f - 1) / 30 u AE.
const FPS = 30;
const cards = track.map((t, i) => ({
	file: `kartice/kartica-${String(i + 1).padStart(2, '0')}.png`,
	name: `${String(i + 1).padStart(2, '0')} ${t.mail.from}: ${t.mail.subj}`,
	k: t.k.map(([f, x, y, r, g]) => [+((f - 1) / FPS).toFixed(4), x, y, r, g])
}));
const counter = labels.map(([f, l]) => [+((f - 1) / FPS).toFixed(4), l]);
const ascii = (s) => s.replace(/[\u0080-￿]/g, (c) => '\\u' + c.charCodeAt(0).toString(16).padStart(4, '0'));
const DATA = ascii(JSON.stringify({ duration: frames / FPS, cards, counter, panel: [box.x + box.width / 2, box.y + box.height / 2], chipTop: box.y }));

const jsx = `// Druga perspektiva: „Digitalni haos u školskim klupama“, animirani cover.
// U After Effectsu: File > Scripts > Run Script File... i odaberi ovaj fajl.
// Fajl mora ostati u istom folderu kao panel.png i folder kartice/.
// Napravi kompoziciju "Digitalni haos" (1080x1440, 30 fps): svaka kartica je
// zaseban layer s keyframeovima za poziciju, rotaciju i veličinu iz fizičke simulacije.
// Brojač je tekst layer (font Lato Bold), a narandžasta oznaka se sama širi uz tekst.
(function () {
	var D = ${DATA};
	var dir = File($.fileName).parent;
	function file(rel) {
		var f = new File(dir.fsName + "/" + rel);
		if (!f.exists) throw new Error("Nema fajla: " + rel + "\\nSkripta mora biti u istom folderu kao panel.png i kartice/.");
		return f;
	}
	app.beginUndoGroup("DP: Digitalni haos");
	var proj = app.project || app.newProject();
	var folder = proj.items.addFolder("DP Digitalni haos");
	var cardFolder = proj.items.addFolder("kartice");
	cardFolder.parentFolder = folder;
	function imp(rel, parent) {
		var it = proj.importFile(new ImportOptions(file(rel)));
		it.parentFolder = parent;
		return it;
	}

	var comp = proj.items.addComp("Digitalni haos", 1080, 1440, 1, D.duration, 30);
	comp.parentFolder = folder;
	comp.motionBlur = true;
	comp.bgColor = [64 / 255, 47 / 255, 101 / 255];

	var bg = comp.layers.addSolid([64 / 255, 47 / 255, 101 / 255], "Pozadina", 1080, 1440, 1, D.duration);
	bg.locked = true;

	for (var i = 0; i < D.cards.length; i++) {
		var c = D.cards[i];
		if (!c.k.length) continue;
		var L = comp.layers.add(imp(c.file, cardFolder));
		L.name = c.name;
		L.motionBlur = true;
		var times = [], pos = [], rot = [], scl = [];
		for (var j = 0; j < c.k.length; j++) {
			var k = c.k[j];
			times.push(k[0]); pos.push([k[1], k[2]]); rot.push(k[3]); scl.push([k[4] * 0.5, k[4] * 0.5]);
		}
		var T = L.property("ADBE Transform Group");
		T.property("ADBE Position").setValuesAtTimes(times, pos);
		T.property("ADBE Rotate Z").setValuesAtTimes(times, rot);
		T.property("ADBE Scale").setValuesAtTimes(times, scl);
		L.inPoint = times[0];
		if (times[times.length - 1] < D.duration - 0.05) L.outPoint = times[times.length - 1] + 1 / 30;
	}

	var panel = comp.layers.add(imp("panel.png", folder));
	panel.name = "Panel (naslov i logo)";
	panel.property("ADBE Transform Group").property("ADBE Position").setValue(D.panel);
	panel.property("ADBE Transform Group").property("ADBE Scale").setValue([50, 50]);

	// Narandžasta oznaka iza brojača; širina prati tekst.
	var sh = comp.layers.addShape();
	sh.name = "Oznaka (narandžasta)";
	var grp = sh.property("ADBE Root Vectors Group").addProperty("ADBE Vector Group");
	var cont = grp.property("ADBE Vectors Group");
	var rect = cont.addProperty("ADBE Vector Shape - Rect");
	rect.property("ADBE Vector Rect Size").expression =
		'var r = thisComp.layer("Brojač").sourceRectAtTime(time, false);\\n[r.width + 34.2, 55.1]';
	rect.property("ADBE Vector Rect Position").expression =
		'var r = thisComp.layer("Brojač").sourceRectAtTime(time, false);\\n[64 + (r.width + 34.2) / 2, ' + (D.chipTop + 27.55) + ']';
	var fill = cont.addProperty("ADBE Vector Graphic - Fill");
	fill.property("ADBE Vector Fill Color").setValue([238 / 255, 128 / 255, 49 / 255]);
	sh.property("ADBE Transform Group").property("ADBE Position").setValue([0, 0]);

	var tl = comp.layers.addText(D.counter[0][1]);
	tl.name = "Brojač";
	var src = tl.property("ADBE Text Properties").property("ADBE Text Document");
	var td = src.value;
	td.resetCharStyle();
	td.font = "Lato-Bold";
	td.fontSize = 38;
	td.applyFill = true;
	td.fillColor = [1, 1, 1];
	td.applyStroke = false;
	td.tracking = 0;
	td.justification = ParagraphJustification.LEFT_JUSTIFY;
	for (var n = 0; n < D.counter.length; n++) {
		td.text = D.counter[n][1];
		src.setValueAtTime(D.counter[n][0], td);
	}
	tl.property("ADBE Transform Group").property("ADBE Position").setValue([81.1, D.chipTop + 40.5]);

	comp.openInViewer();
	app.endUndoGroup();
	alert("Gotovo: kompozicija \\"Digitalni haos\\" je napravljena (" + D.cards.length + " kartica).\\nAko brojač nije u Lato fontu, instaliraj Lato (fonts.google.com/specimen/Lato) i ponovo pokreni skriptu.");
})();
`;
fs.writeFileSync(path.join(out, 'email-haos.jsx'), jsx);
console.log(`${path.relative(process.cwd(), out)}: ${track.length} kartica, ${counter.length} promjena brojača, ${(jsx.length / 1024).toFixed(0)} KB skripta`);
