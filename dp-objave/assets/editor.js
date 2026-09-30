/* DP objave: uređivač Instagram objava. Crta slajdove na <canvas> i preuzima ih kao PNG. */
(function ($) {
	'use strict';

	var CFG = window.DPO || {};
	// Instagram: objava 3:4 (nova mreža profila) i story 9:16.
	var SIZES = { post: [1080, 1440], story: [1080, 1920] };
	var SANS = 'DPO Lato';
	var input = document.getElementById('dpo-design');
	var app = document.getElementById('dpo-app');
	if (!input || !app) return;

	var D = parse(input.value);
	var cur = 0;
	var images = {};   // url -> Image (učitane fotografije)
	var logos = {};

	function parse(v) {
		try { var d = JSON.parse(v); if (d && d.slides && d.slides.length) return d; } catch (e) {}
		return { format: 'post', article: 0, slides: [newSlide('cover')] };
	}

	function newSlide(t) {
		if (t === 'cover') {
			return { t: t, bg: '#141414', logo: true, title: 'Naslov objave', chip: 'Vijesti', chipColor: '#5271fe', byline: '', credit: '', darken: 0.8, blur: 0, size: 1, photos: [] };
		}
		if (t === 'text') {
			return { t: t, bg: '#402f65', logo: true, title: 'Kratka, jaka rečenica kao naslov.', body: 'Ovdje ide tekst iz članka. Najbolje su dva do četiri kraća pasusa.', align: 'left', caps: false };
		}
		if (t === 'score') {
			return { t: t, bg: '#141414', logo: true, title: '', chip: 'Odbojka', chipColor: '#ee8031', home: 'Peta gimnazija', homeScore: '2', away: 'Druga gimnazija', awayScore: '1', ours: 'away', detail: '', credit: '', darken: 0.8, blur: 0, photos: [] };
		}
		if (t === 'table') {
			return { t: t, bg: '#402f65', logo: true, title: 'Raspored', sub: '08.09. – 09.09.', rows: 'Prvi | 08:00 – 08:35\nDrugi | 08:40 – 09:15\nTreći | 09:20 – 09:55\nČetvrti | 10:00 – 10:35\n*Veliki odmor | 10:35 – 10:55\nPeti | 10:55 – 11:30\nŠesti | 11:35 – 12:10\nSedmi | 12:15 – 12:50', cellColor: '#ee8031', credit: '', darken: 0.6, blur: 0, photos: [] };
		}
		return { t: t, bg: '#ebebee', logo: true, title: '', quote: 'Ovdje ide citat iz članka.', who: 'Ime Prezime', whoInfo: 'učenica 3. razreda' };
	}

	/* ---------- Pomoćne za crtanje ---------- */

	function font(family, weight, size, italic) {
		return (italic ? 'italic ' : '') + weight + ' ' + Math.round(size) + 'px "' + family + '"';
	}

	// Razmak između slova (gdje ga preglednik podržava; inače bez njega).
	function track(ctx, px) { if ('letterSpacing' in ctx) ctx.letterSpacing = (px || 0) + 'px'; }

	function isLight(hex) {
		var n = parseInt(String(hex || '#000000').slice(1), 16);
		return (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) > 170;
	}

	/**
	 * Bosanska tipografija pri crtanju (tekst u poljima se ne mijenja):
	 * navodnici „…“, tri tačke …, crtica –, i jednoslovne riječi (i, u, s, k, a, o…)
	 * vezane za sljedeću riječ da ne ostanu same na kraju reda.
	 */
	function typo(t) {
		return String(t || '')
			.replace(/\.\.\./g, '…')
			.replace(/(^|[\s(\[\u00A0])["“”″˝]/g, '$1„')
			.replace(/["“”″˝]/g, '“')
			.replace(/([A-Za-zčćđšžČĆĐŠŽ])'([A-Za-zčćđšžČĆĐŠŽ])/g, '$1’$2')
			.replace(/ - /g, ' – ')
			.replace(/(?<=^|[\s\u00A0(„])([aiouskvzAIOUSKVZ]) +/g, '$1\u00A0');
	}

	/** Prelama tekst u redove zadate širine; poštuje nove redove koje je autor upisao. */
	function wrap(ctx, text, maxW) {
		var lines = [];
		String(text || '').split('\n').forEach(function (para) {
			var words = para.split(/[ \t]+/).filter(Boolean), line = '';
			if (!words.length) { lines.push({ text: '', end: true }); return; }
			words.forEach(function (w) {
				var test = line ? line + ' ' + w : w;
				if (line && ctx.measureText(test).width > maxW) { lines.push({ text: line, end: false }); line = w; }
				else line = test;
			});
			lines.push({ text: line, end: true });
		});
		return lines;
	}

	/** Kao wrap, ali redovi su podjednako dugi (bez jedne riječi same u zadnjem redu). */
	function wrapBalanced(ctx, text, maxW) {
		var lines = wrap(ctx, text, maxW), n = lines.length;
		if (n < 2) return lines;
		var lo = maxW * 0.5, hi = maxW;
		for (var i = 0; i < 14; i++) {
			var mid = (lo + hi) / 2;
			if (wrap(ctx, text, mid).length <= n) hi = mid; else lo = mid;
		}
		return wrap(ctx, text, hi);
	}

	/** Kao wrap, ali za tekst iz više dijelova različite debljine (npr. podebljano ime pa opis). */
	function wrapRuns(ctx, runs, maxW) {
		var words = [];
		track(ctx, 0);
		runs.forEach(function (r) {
			String(r.text || '').split(/[ \t]+/).filter(Boolean).forEach(function (w) { words.push({ text: w, font: r.font, color: r.color }); });
		});
		var lines = [], line = [], width = 0;
		words.forEach(function (w) {
			ctx.font = w.font;
			w.w = ctx.measureText(w.text).width;
			w.space = ctx.measureText(' ').width;
			var add = (line.length ? w.space : 0) + w.w;
			if (line.length && width + add > maxW) { lines.push({ parts: line, w: width }); line = []; width = 0; add = w.w; }
			line.push(w); width += add;
		});
		if (line.length) lines.push({ parts: line, w: width });
		return lines;
	}

	function drawRunsLine(ctx, line, x, y) {
		line.parts.forEach(function (w, i) {
			if (i) x += w.space;
			ctx.font = w.font; ctx.fillStyle = w.color;
			ctx.fillText(w.text, x, y);
			x += w.w;
		});
	}

	/** Crta red; kod poravnanja "obostrano" razvlači razmake (osim u zadnjem redu pasusa). */
	function drawLine(ctx, line, x, y, maxW, justify) {
		var words = line.text.split(' ');
		if (!justify || line.end || words.length < 2) { ctx.fillText(line.text, x, y); return; }
		var total = words.reduce(function (a, w) { return a + ctx.measureText(w).width; }, 0);
		var gap = (maxW - total) / (words.length - 1);
		words.forEach(function (w) { ctx.fillText(w, x, y); x += ctx.measureText(w).width + gap; });
	}

	/** Fotografija popunjava okvir (kao "cover"), s uvećanjem i pomakom. */
	function photoBox(img, slot, p) {
		var scale = Math.max(slot.w / img.naturalWidth, slot.h / img.naturalHeight) * (p.zoom || 1);
		var w = img.naturalWidth * scale, h = img.naturalHeight * scale;
		var ovX = w - slot.w, ovY = h - slot.h;
		return { x: slot.x - ovX * (1 + (p.ox || 0)) / 2, y: slot.y - ovY * (1 + (p.oy || 0)) / 2, w: w, h: h, ovX: ovX, ovY: ovY };
	}

	function slots(s, W, H) {
		var n = Math.max(1, (s.photos || []).length), out = [];
		for (var i = 0; i < n; i++) {
			var y0 = Math.round(H * i / n), y1 = Math.round(H * (i + 1) / n);
			out.push({ x: 0, y: y0, w: W, h: y1 - y0 });
		}
		return out;
	}

	/** Crni prelaz odozdo: mekan na vrhu, pun ispod teksta. */
	function scrim(ctx, W, H, top, strength) {
		var g = ctx.createLinearGradient(0, top, 0, H);
		for (var i = 0; i <= 12; i++) {
			var t = i / 12, e = Math.min(1, t / 0.75);
			g.addColorStop(t, 'rgba(0,0,0,' + (strength * e * e * (3 - 2 * e)).toFixed(3) + ')');
		}
		ctx.fillStyle = g;
		ctx.fillRect(0, top, W, H - top);
	}

	/** DP znak dolje desno. Vraća njegovu gornju ivicu, da tekst ne ide preko. */
	function drawLogo(ctx, s, W, H, format, dark, opt) {
		var story = format === 'story';
		opt = opt || {};
		var w = opt.w || (story ? 124 : 108), img = logos[dark ? 'black' : 'white'];
		var h = img && img.naturalWidth ? w * img.naturalHeight / img.naturalWidth : w * 0.84;
		var x = W - w - (opt.right != null ? opt.right : 56);
		var y = H - h - (opt.bottom != null ? opt.bottom : (story ? 96 : 40));
		if (s.logo && img && img.complete && img.naturalWidth) ctx.drawImage(img, x, y, w, h);
		return { x: x, y: y, w: w, h: h, on: !!s.logo };
	}

	/** Potpis fotografije uspravno uz desnu ivicu, iznad logotipa. */
	function sideCredit(ctx, text, W, fromY, dark) {
		ctx.save();
		ctx.translate(W - 30, fromY);
		ctx.rotate(-Math.PI / 2);
		ctx.font = font(SANS, 400, 20);
		ctx.fillStyle = dark ? 'rgba(20,20,20,.55)' : 'rgba(255,255,255,.66)';
		ctx.fillText(text, 0, 0);
		ctx.restore();
	}

	/* ---------- Tri stila i tri vrste slajdova ---------- */

	var SC = 1; // koliko puta je slika veća od 1080 px (preuzimanje je 2x, zbog Instagram kompresije)

	// Zadane pozadine po stilu (tekst i citat).
	var STYLE_BG = {
		moderni: { text: '#402f65', quote: '#402f65' },
		dp: { text: '#402f65', quote: '#402f65' }
	};

	// Stariji dizajni (Apple, Magazin) prikazuju se kao DP moderni.
	function style() { return D.style === 'dp' ? 'dp' : 'moderni'; }

	function drawPhotos(ctx, s, W, H) {
		var photos = s.photos || [], sl = slots(s, W, H), any = false;
		photos.forEach(function (p, i) {
			var img = images[p.url];
			if (!img || !img.complete || !img.naturalWidth) return;
			any = true;
			var b = photoBox(img, sl[i], p);
			ctx.save();
			ctx.beginPath(); ctx.rect(sl[i].x, sl[i].y, sl[i].w, sl[i].h); ctx.clip();
			if (s.blur > 0) ctx.filter = 'blur(' + (s.blur * SC) + 'px)';
			ctx.drawImage(img, b.x, b.y, b.w, b.h);
			ctx.restore();
		});
		return any;
	}

	/**
	 * Slaže blokove teksta odozdo prema gore (zadnji red na "bottom").
	 * Svaki blok: { h: visina, gap: razmak iznad, draw: function (top) }.
	 * Vraća gornju ivicu cijelog teksta.
	 */
	function stackUp(blocks, bottom) {
		var y = bottom;
		for (var i = blocks.length - 1; i >= 0; i--) {
			var b = blocks[i];
			b.top = y - b.h;
			y = b.top - (i > 0 ? b.gap : 0);
		}
		return blocks.length ? blocks[0].top : bottom;
	}

	/** Blok teksta: redovi s visinom reda lh; osnovna linija prvog reda je top + size*0.8. */
	function textBlock(ctx, fontStr, size, lh, text, maxW, trackPx, color, gap, align, W) {
		ctx.font = fontStr; track(ctx, trackPx);
		var lines = wrapBalanced(ctx, text, maxW);
		track(ctx, 0);
		return {
			h: (lines.length - 1) * lh + size, gap: gap, lines: lines,
			draw: function (top) {
				ctx.font = fontStr; track(ctx, trackPx); ctx.fillStyle = color;
				lines.forEach(function (l, i) {
					var x = align === 'center' ? (W - ctx.measureText(l.text).width) / 2 : align;
					ctx.fillText(l.text, x, top + size * 0.8 + i * lh);
				});
				track(ctx, 0);
			}
		};
	}

	/** Naslov koji se sam smanji dok ne stane u najviše maxLines redova. */
	function fitTitle(ctx, family, weight, size, min, maxLines, text, maxW, trackEm) {
		var lines;
		do {
			ctx.font = font(family, weight, size); track(ctx, size * trackEm);
			lines = wrap(ctx, text, maxW);
			if (lines.length <= maxLines) break;
			size -= 4;
		} while (size > min);
		track(ctx, 0);
		return size;
	}

	function chipBlock(ctx, text, color, size, weight, gap, M) {
		ctx.font = font(SANS, weight, size);
		var w = ctx.measureText(text).width + size * 0.9, h = Math.round(size * 1.45);
		return {
			h: h, gap: gap,
			draw: function (top) {
				ctx.fillStyle = color; ctx.fillRect(M, top, w, h);
				ctx.font = font(SANS, weight, size); ctx.fillStyle = '#ffffff';
				ctx.fillText(text, M + size * 0.45, top + h * 0.7);
			}
		};
	}

	// ---- Naslovna ----
	function drawCover(ctx, s, W, H, format) {
		var st = style(), story = format === 'story';
		ctx.fillStyle = s.bg || '#141414';
		ctx.fillRect(0, 0, W, H);
		var any = drawPhotos(ctx, s, W, H);
		var dark = !any && isLight(s.bg);
		var ink = dark ? '#141414' : '#ffffff', soft = dark ? 'rgba(20,20,20,.72)' : 'rgba(255,255,255,.86)';
		var k = s.size || 1, blocks = [], M, bottom, hs;

		if (st === 'moderni') {
			// DP moderni: manja oznaka, zbijen i jak naslov; autor i logo u istom redu na dnu.
			M = 64;
			var base = H - (story ? 300 : 72);  // osnovna linija autora = dno logotipa
			var logo = drawLogo(ctx, { logo: false }, W, H, format, dark, { w: 100, right: M, bottom: H - base });
			var chip, head, by;
			if (s.chip) blocks.push(chip = chipBlock(ctx, s.chip, s.chipColor || '#5271fe', story ? 42 : 38, 700, 0, M));
			hs = fitTitle(ctx, SANS, 700, (story ? 102 : 90) * k, 50, 5, s.title, W - 2 * M, -0.022);
			blocks.push(head = textBlock(ctx, font(SANS, 700, hs), hs, hs * 1.03, s.title, W - 2 * M, -hs * 0.022, ink, 22, M, W));
			if (s.byline) blocks.push(by = textBlock(ctx, font(SANS, 400, 28), 28, 34, s.byline, logo.x - M - 32, 0, soft, 26, M, W));
			bottom = by ? base + 28 * 0.2 : base + hs * 0.2;
			// Ako bi zadnji red naslova udario u logo, podigni naslov.
			stackUp(blocks, bottom);
			ctx.font = font(SANS, 700, hs); track(ctx, -hs * 0.022);
			var lastW = ctx.measureText(head.lines[head.lines.length - 1].text).width;
			track(ctx, 0);
			var headBottom = head.top + head.h;
			if (s.logo && M + lastW > logo.x - 28 && headBottom > logo.y - 18) {
				if (by) by.gap += headBottom - (logo.y - 18);
				else bottom -= headBottom - (logo.y - 18);
			}
		} else {
			// DP klasik: kao dosadašnje objave na Instagramu.
			M = 80; bottom = H - (story ? 300 : 150);
			if (s.chip) blocks.push(chipBlock(ctx, s.chip, s.chipColor || '#5271fe', story ? 60 : 54, 400, 0, M));
			hs = fitTitle(ctx, SANS, 700, (story ? 94 : 86) * k, 50, 5, s.title, W - 2 * M, 0);
			blocks.push(textBlock(ctx, font(SANS, 700, hs), hs, hs * 1.08, s.title, W - 2 * M, 0, ink, 20, M, W));
			if (s.byline) blocks.push(textBlock(ctx, font(SANS, 400, 30), 30, 36, s.byline, W - 2 * M, 0, soft, 24, M, W));
		}

		var textTop = stackUp(blocks, bottom);
		if (any && s.darken > 0) scrim(ctx, W, H, Math.max(H * 0.15, textTop - H * 0.2), s.darken);
		blocks.forEach(function (b) { b.draw(b.top); });

		if (st === 'moderni') {
			var lg = drawLogo(ctx, s, W, H, format, dark, { w: 100, right: M, bottom: H - base });
			if (s.credit) sideCredit(ctx, s.credit, W, (s.logo ? lg.y : base) - 28, dark);
			return;
		}
		drawLogo(ctx, s, W, H, format, dark);
		if (s.credit) {
			ctx.font = font(SANS, 400, 22);
			ctx.fillStyle = dark ? 'rgba(20,20,20,.55)' : 'rgba(255,255,255,.62)';
			ctx.fillText(s.credit, M, H - (story ? 120 : 50));
		}
	}

	// ---- Tekst (i velika izjava kad nema pasusa) ----
	function drawText(ctx, s, W, H, format) {
		var st = style(), story = format === 'story', dark = isLight(s.bg);
		ctx.fillStyle = s.bg || STYLE_BG[st].text;
		ctx.fillRect(0, 0, W, H);
		var modern = st === 'moderni';
		var M = 96, maxW = W - 2 * M, room = H - (story ? 640 : 380);
		var ink = dark ? '#141414' : '#ffffff';

		if (!String(s.body || '').trim()) {
			// Velika izjava, kao plakat.
			var text = s.caps ? String(s.title || '').toLocaleUpperCase('bs') : s.title;
			var wt = modern ? 900 : 700, tr = modern ? -0.025 : 0, lhk = modern ? 1.0 : 1.08;
			var size = 176, lines;
			do {
				size -= 6; ctx.font = font(SANS, wt, size); track(ctx, size * tr); lines = wrap(ctx, text, maxW);
			} while ((lines.length * size * lhk > room || lines.some(function (l) { return ctx.measureText(l.text).width > maxW; })) && size > 50);
			track(ctx, 0);
			var blk = textBlock(ctx, font(SANS, wt, size), size, size * lhk, text, maxW, size * tr, ink, 0, modern ? M : 'center', W);
			blk.draw((H - blk.h) / 2 - H * 0.02);
			drawLogo(ctx, s, W, H, format, dark);
			return;
		}

		var T = modern
			? { ts: 64, bs: 40, blh: 1.38, gap: 30, body: dark ? 'rgba(20,20,20,.78)' : 'rgba(255,255,255,.84)', tr: -0.02 }
			: { ts: 58, bs: 42, blh: 1.24, gap: 18, body: dark ? 'rgba(20,20,20,.72)' : 'rgba(255,255,255,.7)', tr: 0 };
		var ts = T.ts, bs = T.bs, t, b, total;
		function measure() {
			t = s.title ? textBlock(ctx, font(SANS, 700, ts), ts, ts * 1.06, s.title, maxW, ts * T.tr, ink, 0, M, W) : null;
			ctx.font = font(SANS, 400, bs); track(ctx, 0);
			b = wrap(ctx, s.body, maxW);
			total = (t ? t.h + T.gap : 0) + (b.length - 1) * bs * T.blh + bs;
		}
		measure();
		while (total > room && bs > 34) { ts -= 2; bs -= 1; measure(); }
		OVER.set(ORIG, total > room);
		var top = (H - total) / 2 - H * 0.02;
		if (t) { t.draw(top); top += t.h + T.gap; }
		ctx.fillStyle = T.body;
		ctx.font = font(SANS, 400, bs);
		b.forEach(function (l, i) { drawLine(ctx, l, M, top + bs * 0.8 + i * bs * T.blh, maxW, s.align === 'justify'); });
		drawLogo(ctx, s, W, H, format, dark);
	}

	// ---- Citat: narandžasta crta uz tekst (znak DP citata) ----
	function drawQuote(ctx, s, W, H, format) {
		var st = style(), story = format === 'story', dark = isLight(s.bg);
		ctx.fillStyle = s.bg || STYLE_BG[st].quote;
		ctx.fillRect(0, 0, W, H);
		var ink = dark ? '#141414' : '#ffffff', soft = dark ? 'rgba(20,20,20,.62)' : 'rgba(255,255,255,.72)';
		var q = String(s.quote || '').trim().replace(/^[„“"”»«]+|[“”"«»]+$/g, '');
		var room = H * (story ? 0.5 : 0.56);

		if (st !== 'moderni') {
			// DP klasik: kao dosadašnji citati, ime kurzivom desno.
			var left = 172, w2 = W - 330, qs = 76, blk;
			do { blk = textBlock(ctx, font(SANS, 400, qs), qs, qs * 1.15, q, w2, 0, ink, 0, left, W); qs -= 2; } while (blk.h > room && qs > 28);
			var ws = Math.max(30, Math.round(qs * 0.62)), who = [];
			if (s.who) { ctx.font = font(SANS, 400, ws, true); who = wrapBalanced(ctx, '— ' + s.who + (s.whoInfo ? ', ' + s.whoInfo : ''), w2); }
			var total = blk.h + (who.length ? 30 + who.length * ws * 1.25 : 0), top = (H - total) / 2;
			ctx.fillStyle = '#ee8031'; ctx.fillRect(left - 32, top, 10, blk.h);
			blk.draw(top);
			ctx.font = font(SANS, 400, ws, true); ctx.fillStyle = dark ? 'rgba(20,20,20,.8)' : 'rgba(255,255,255,.9)';
			who.forEach(function (l, i) { ctx.fillText(l.text, left + w2 - ctx.measureText(l.text).width, top + blk.h + 30 + ws * 0.8 + i * ws * 1.25); });
			drawLogo(ctx, s, W, H, format, dark);
			return;
		}

		// DP moderni: velik i zbijen citat, crta uz lijevu ivicu, ime podebljano pa opis.
		var L = 124, maxW = W - L - 88, size = 124, qb;
		do { size -= 4; qb = textBlock(ctx, font(SANS, 900, size), size, size * 1.04, q, maxW, -size * 0.02, ink, 0, L, W); } while (qb.h > room && size > 44);
		var as = story ? 36 : 34, runs = [];
		if (s.who) runs.push({ text: s.who + (s.whoInfo ? ',' : ''), font: font(SANS, 700, as), color: ink });
		if (s.whoInfo) runs.push({ text: s.whoInfo, font: font(SANS, 400, as), color: soft });
		var att = runs.length ? wrapRuns(ctx, runs, maxW) : [];
		var attH = att.length ? 44 + (att.length - 1) * as * 1.3 + as : 0;
		var y0 = (H - qb.h - attH) / 2 - H * 0.02;
		ctx.fillStyle = '#ee8031';
		ctx.fillRect(L - 44, y0 + size * 0.06, 12, qb.h - size * 0.12);
		qb.draw(y0);
		att.forEach(function (l, i) { drawRunsLine(ctx, l, L, y0 + qb.h + 44 + as * 0.8 + i * as * 1.3); });
		drawLogo(ctx, s, W, H, format, dark);
	}

	/** Boja pomiješana s bijelom (amount 0..1), za svjetliji istaknuti red. */
	function tint(hex, amount) {
		var n = parseInt(String(hex || '#ee8031').slice(1), 16);
		var c = [n >> 16, (n >> 8) & 255, n & 255].map(function (v) { return Math.round(v + (255 - v) * amount); });
		return 'rgb(' + c.join(',') + ')';
	}

	/** Tekst u sredini okvira (po širini), osnovna linija y. */
	function centerText(ctx, text, cx, y) { ctx.fillText(text, cx - ctx.measureText(text).width / 2, y); }

	// ---- Rezultat utakmice ----
	function drawScore(ctx, s, W, H, format) {
		var st = style(), story = format === 'story';
		ctx.fillStyle = s.bg || '#141414';
		ctx.fillRect(0, 0, W, H);
		var any = drawPhotos(ctx, s, W, H);
		var ours = s.ours, orange = s.chipColor || '#ee8031';
		var colL = W * 0.27, colR = W * 0.73, nameW = W * 0.42;

		if (st === 'dp') {
			// DP klasik: ljubičasta traka preko donjeg dijela fotografije, kao dosadašnji rezultati.
			var bandH = story ? 640 : 560, bandTop = H - bandH - (story ? 300 : 150);
			ctx.fillStyle = 'rgba(64,47,101,0.9)';
			ctx.fillRect(0, bandTop, W, bandH);
			ctx.fillStyle = '#ffffff';
			ctx.font = font(SANS, 400, 88);
			if (s.chip) centerText(ctx, s.chip, W / 2, bandTop + 130);
			var ny = bandTop + 360;
			ctx.font = font(SANS, 700, 250);
			ctx.fillStyle = 'rgba(0,0,0,.25)';
			centerText(ctx, s.homeScore || '', colL + 6, ny + 8); centerText(ctx, s.awayScore || '', colR + 6, ny + 8);
			ctx.fillStyle = orange;
			centerText(ctx, s.homeScore || '', colL, ny); centerText(ctx, s.awayScore || '', colR, ny);
			ctx.fillStyle = '#ffffff';
			ctx.beginPath(); ctx.arc(W / 2, ny - 150, 16, 0, Math.PI * 2); ctx.arc(W / 2, ny - 60, 16, 0, Math.PI * 2); ctx.fill();
			[[s.home, colL, 'home'], [s.away, colR, 'away']].forEach(function (t) {
				if (!t[0]) return;
				ctx.font = font(SANS, 400, 52);
				var w = ctx.measureText(t[0]).width, y = ny + 110;
				if (ours === t[2]) { ctx.fillStyle = orange; ctx.fillRect(t[1] - w / 2 - 14, y - 50, w + 28, 68); }
				ctx.fillStyle = '#ffffff';
				centerText(ctx, t[0], t[1], y);
			});
			if (s.detail) { ctx.font = font(SANS, 400, 32); ctx.fillStyle = 'rgba(255,255,255,.8)'; centerText(ctx, s.detail, W / 2, bandTop + bandH - 40); }
			drawLogo(ctx, s, W, H, format, false);
			return;
		}

		// DP moderni: fotografija, crni prelaz, sve centrirano; naš tim ima oznaku u boji.
		var base = H - (story ? 300 : 72);
		var ns = story ? 46 : 42, big = story ? 280 : 250;
		ctx.font = font(SANS, 700, ns);
		var hl = wrapBalanced(ctx, s.home || '', nameW), al = wrapBalanced(ctx, s.away || '', nameW);
		var nLines = Math.max(hl.length, al.length, 1), nameLH = ns * 1.25;
		var y = base;
		var detailY = null;
		if (s.detail) { detailY = y; y -= 34 + 40; }
		var namesTop = y - (nLines - 1) * nameLH - ns;
		var numBase = namesTop - 46;
		var chipBottom = numBase - big * 0.74 - 40;
		var cs = story ? 42 : 38, ch = Math.round(cs * 1.45);
		var top = s.chip ? chipBottom - ch : chipBottom;
		if (any && s.darken > 0) scrim(ctx, W, H, Math.max(H * 0.1, top - H * 0.2), s.darken);

		if (s.chip) {
			ctx.font = font(SANS, 700, cs);
			var cw = ctx.measureText(s.chip).width + cs * 0.9;
			ctx.fillStyle = orange; ctx.fillRect(W / 2 - cw / 2, top, cw, ch);
			ctx.fillStyle = '#ffffff'; ctx.fillText(s.chip, W / 2 - cw / 2 + cs * 0.45, top + ch * 0.7);
		}
		ctx.font = font(SANS, 900, big); track(ctx, -big * 0.02);
		ctx.fillStyle = '#ffffff';
		centerText(ctx, s.homeScore || '', colL, numBase);
		centerText(ctx, s.awayScore || '', colR, numBase);
		track(ctx, 0);
		// Dvotačka: dvije tačke između rezultata.
		ctx.beginPath();
		ctx.arc(W / 2, numBase - big * 0.52, big * 0.055, 0, Math.PI * 2);
		ctx.arc(W / 2, numBase - big * 0.14, big * 0.055, 0, Math.PI * 2);
		ctx.fill();
		[[hl, colL, 'home'], [al, colR, 'away']].forEach(function (t) {
			ctx.font = font(SANS, 700, ns);
			t[0].forEach(function (l, i) {
				var ly = namesTop + ns * 0.8 + i * nameLH, w = ctx.measureText(l.text).width;
				if (ours === t[2]) { ctx.fillStyle = orange; ctx.fillRect(t[1] - w / 2 - 14, ly - ns * 0.86, w + 28, ns * 1.2); }
				ctx.fillStyle = '#ffffff';
				centerText(ctx, l.text, t[1], ly);
			});
		});
		if (detailY) { ctx.font = font(SANS, 400, 30); ctx.fillStyle = 'rgba(255,255,255,.82)'; centerText(ctx, s.detail, W / 2, detailY); }
		var lg = drawLogo(ctx, s, W, H, format, false, { w: 100, right: 64, bottom: H - base });
		if (s.credit) sideCredit(ctx, s.credit, W, (s.logo ? lg.y : base) - 28, false);
	}

	/** Redovi rasporeda: "lijevo | desno"; zvjezdica na početku ističe red. */
	function tableRows(text) {
		return String(text || '').split('\n').map(function (l) { return l.trim(); }).filter(Boolean).map(function (l) {
			var hi = l.charAt(0) === '*';
			if (hi) l = l.slice(1).trim();
			var parts = l.split('|').map(function (p) { return p.trim(); });
			return { a: parts[0] || '', b: parts.slice(1).join(' | '), hi: hi };
		});
	}

	// ---- Raspored / tabela ----
	function drawTable(ctx, s, W, H, format) {
		var st = style(), story = format === 'story';
		ctx.fillStyle = s.bg || '#402f65';
		ctx.fillRect(0, 0, W, H);
		var any = drawPhotos(ctx, s, W, H);
		if (any && s.darken > 0) { ctx.fillStyle = 'rgba(0,0,0,' + (s.darken * 0.6).toFixed(3) + ')'; ctx.fillRect(0, 0, W, H); }
		var dark = !any && isLight(s.bg), ink = dark ? '#141414' : '#ffffff';
		var cell = s.cellColor || '#ee8031', rows = tableRows(s.rows);
		var M = 72, topY = story ? 250 : 96, bottomY = H - (story ? 300 : 150);
		var y;

		if (st === 'dp') {
			// DP klasik: naslov u narandžastom okviru, datum ukoso, ćelije u sredini.
			ctx.font = font(SANS, 400, 110);
			var tw = ctx.measureText(s.title || '').width, tx = W / 2 - tw / 2;
			if (s.title) {
				ctx.fillStyle = cell; ctx.fillRect(tx - 28, topY, tw + 56, 140);
				ctx.fillStyle = '#ffffff'; ctx.fillText(s.title, tx, topY + 108);
			}
			y = topY + (s.title ? 140 : 0);
			if (s.sub) {
				ctx.save();
				ctx.translate(W / 2, y + 50); ctx.rotate(-0.07);
				ctx.font = font(SANS, 400, 64);
				var sw = ctx.measureText(s.sub).width;
				ctx.fillStyle = tint(cell, 0.45); ctx.fillRect(-sw / 2 - 22, -44, sw + 44, 92);
				ctx.fillStyle = '#ffffff'; ctx.fillText(s.sub, -sw / 2, 24);
				ctx.restore();
				y += 110;
			}
			y += 70;
			var n = rows.length || 1, gap = 18, colW = 300;
			var rh = Math.min(76, (bottomY - y - gap * (n - 1)) / n), fs = Math.min(38, rh * 0.5);
			rows.forEach(function (r, i) {
				var ry = y + i * (rh + gap), fill = r.hi ? tint(cell, 0.4) : cell;
				ctx.font = font(SANS, 700, fs);
				if (r.b) {
					ctx.fillStyle = fill; ctx.fillRect(W / 2 - colW - 12, ry, colW, rh); ctx.fillRect(W / 2 + 12, ry, colW, rh);
					ctx.fillStyle = '#ffffff';
					centerText(ctx, r.a, W / 2 - 12 - colW / 2, ry + rh / 2 + fs * 0.36);
					centerText(ctx, r.b, W / 2 + 12 + colW / 2, ry + rh / 2 + fs * 0.36);
				} else {
					ctx.fillStyle = fill; ctx.fillRect(W / 2 - colW - 12, ry, colW * 2 + 24, rh);
					ctx.fillStyle = '#ffffff'; centerText(ctx, r.a, W / 2, ry + rh / 2 + fs * 0.36);
				}
			});
			drawLogo(ctx, s, W, H, format, dark);
			return;
		}

		// DP moderni: datum kao oznaka, velik naslov, pa redovi preko cijele širine.
		y = topY;
		if (s.sub) { var c = chipBlock(ctx, s.sub, cell, story ? 42 : 38, 700, 0, M); c.draw(y); y += c.h + 22; }
		if (s.title) {
			var ts = fitTitle(ctx, SANS, 700, story ? 120 : 108, 56, 2, s.title, W - 2 * M, -0.022);
			var tb = textBlock(ctx, font(SANS, 700, ts), ts, ts * 1.02, s.title, W - 2 * M, -ts * 0.022, ink, 0, M, W);
			tb.draw(y); y += tb.h;
		}
		y += story ? 80 : 56;
		var n2 = rows.length || 1, g = 10, lw = (W - 2 * M - g) * 0.5;
		var rh2 = Math.min(88, (bottomY - y - g * (n2 - 1)) / n2), fs2 = Math.min(40, rh2 * 0.46);
		rows.forEach(function (r, i) {
			var ry = y + i * (rh2 + g), base2 = ry + rh2 / 2 + fs2 * 0.36;
			var fill = r.hi ? '#ffffff' : cell, txt = r.hi ? '#141414' : '#ffffff';
			ctx.fillStyle = fill;
			if (r.b) { ctx.fillRect(M, ry, lw, rh2); ctx.fillRect(M + lw + g, ry, lw, rh2); }
			else ctx.fillRect(M, ry, lw * 2 + g, rh2);
			ctx.fillStyle = txt;
			ctx.font = font(SANS, 700, fs2);
			ctx.fillText(r.a, M + 26, base2);
			if (r.b) { ctx.font = font(SANS, 400, fs2); ctx.fillText(r.b, M + lw + g + 26, base2); }
		});
		var lg = drawLogo(ctx, s, W, H, format, dark, { w: 100, right: 64, bottom: story ? 300 : 72 });
		if (s.credit && any) sideCredit(ctx, s.credit, W, lg.y - 28, false);
	}

	var OVER = new WeakMap(); // slajd -> tekst ne staje ni u najmanjoj čitljivoj veličini
	var ORIG = null;

	/** Crta slajd. scale 2 = dvostruka rezolucija (za preuzimanje). */
	function draw(canvas, s, scale) {
		ORIG = s;
		var t = {};
		Object.keys(s).forEach(function (k) { t[k] = s[k]; });
		['title', 'body', 'quote', 'who', 'whoInfo', 'byline', 'chip', 'credit', 'home', 'away', 'detail', 'sub', 'rows'].forEach(function (k) { if (t[k]) t[k] = typo(t[k]); });
		s = t;
		var size = SIZES[D.format] || SIZES.post;
		SC = scale || 1;
		var w = Math.round(size[0] * SC), hgt = Math.round(size[1] * SC);
		if (canvas.width !== w || canvas.height !== hgt) { canvas.width = w; canvas.height = hgt; }
		var ctx = canvas.getContext('2d');
		ctx.setTransform(SC, 0, 0, SC, 0, 0);
		ctx.clearRect(0, 0, size[0], size[1]);
		ctx.textBaseline = 'alphabetic';
		track(ctx, 0);
		({ cover: drawCover, text: drawText, quote: drawQuote, score: drawScore, table: drawTable })[s.t](ctx, s, size[0], size[1], D.format);
		SC = 1;
	}

	/* ---------- Učitavanje ---------- */

	function loadImage(url) {
		if (!url || images[url]) return;
		var img = new Image();
		img.crossOrigin = 'anonymous';
		img.onload = function () { redraw(); };
		img.onerror = function () {
			// Bez CORS zaglavlja: prikaži bar sliku (preuzimanje tada ne radi, javljamo pri preuzimanju).
			if (img.crossOrigin) { var b = new Image(); b.onload = function () { images[url] = b; b.dpoTainted = true; redraw(); }; b.src = url; }
		};
		img.src = url;
		images[url] = img;
	}

	function loadAll() {
		D.slides.forEach(function (s) { (s.photos || []).forEach(function (p) { loadImage(p.url); }); });
	}

	/* ---------- Sučelje ---------- */

	function h(tag, attrs, kids) {
		var el = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			if (k === 'text') el.textContent = attrs[k];
			else if (k.slice(0, 2) === 'on') el.addEventListener(k.slice(2), attrs[k]);
			else if (k === 'value') el.value = attrs[k];
			else if (k === 'checked' || k === 'disabled') el[k] = !!attrs[k];
			else el.setAttribute(k, attrs[k]);
		});
		(kids || []).forEach(function (c) { if (c) el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
		return el;
	}

	var ui = {};

	function build() {
		app.innerHTML = '';
		ui.format = h('div', { class: 'dpo-seg', role: 'group', 'aria-label': 'Format' });
		ui.style = h('div', { class: 'dpo-seg', role: 'group', 'aria-label': 'Stil' });
		ui.fill = h('select', { class: 'dpo-article' }, [h('option', { value: '', text: 'Popuni iz članka…' })].concat((CFG.articles || []).map(function (a) {
			return h('option', { value: a.id, text: a.title });
		})));
		ui.fill.addEventListener('change', fillFromArticle);
		ui.strip = h('div', { class: 'dpo-strip' });
		ui.canvas = h('canvas', { class: 'dpo-preview', 'aria-label': 'Pregled slajda' });
		ui.fields = h('div', { class: 'dpo-fields' });

		app.appendChild(h('div', { class: 'dpo-top' }, [
			h('span', { class: 'dpo-label', text: 'Stil' }), ui.style,
			h('span', { class: 'dpo-label', text: 'Format' }), ui.format,
			ui.fill,
			h('span', { class: 'dpo-spacer' }),
			h('button', { type: 'button', class: 'button', onclick: downloadCurrent, text: 'Preuzmi ovaj slajd' }),
			h('button', { type: 'button', class: 'button button-primary', onclick: downloadAll, text: 'Preuzmi sve' })
		]));
		app.appendChild(ui.strip);
		app.appendChild(h('div', { class: 'dpo-main' }, [
			h('div', { class: 'dpo-stage' }, [ui.canvas, h('p', { class: 'dpo-hint', text: 'Povucite fotografiju mišem ili prstom da odaberete koji dio se vidi.' })]),
			ui.fields
		]));
		app.appendChild(h('p', { class: 'dpo-save-note', text: 'Kliknite "Objavi" ili "Ažuriraj" desno da spremite dizajn. Objava se ne pojavljuje na sajtu; ovdje je samo za preuzimanje.' }));
		setupDrag();
		render();
	}

	function render() {
		// Stil i format
		ui.style.innerHTML = '';
		[['moderni', 'DP moderni'], ['dp', 'DP klasik']].forEach(function (f) {
			ui.style.appendChild(h('button', {
				type: 'button', class: 'button' + (style() === f[0] ? ' is-on' : ''), 'aria-pressed': style() === f[0] ? 'true' : 'false',
				onclick: function () { setStyle(f[0]); }, text: f[1]
			}));
		});
		ui.format.innerHTML = '';
		[['post', 'Objava 3:4'], ['story', 'Story 9:16']].forEach(function (f) {
			ui.format.appendChild(h('button', {
				type: 'button', class: 'button' + (D.format === f[0] ? ' is-on' : ''), 'aria-pressed': D.format === f[0] ? 'true' : 'false',
				onclick: function () { D.format = f[0]; changed(true); }, text: f[1]
			}));
		});
		// Slajdovi
		ui.strip.innerHTML = '';
		D.slides.forEach(function (s, i) {
			var c = h('canvas', { class: 'dpo-thumb' });
			var b = h('button', { type: 'button', class: 'dpo-thumb-btn' + (i === cur ? ' is-on' : ''), 'aria-label': 'Slajd ' + (i + 1), onclick: function () { cur = i; render(); } }, [c, h('span', { text: (i + 1) + '. ' + label(s.t) })]);
			ui.strip.appendChild(b);
			draw(c, s, 0.2);
		});
		ui.strip.appendChild(h('div', { class: 'dpo-add' }, [
			h('button', { type: 'button', class: 'button', onclick: function () { add('cover'); }, text: '+ Naslovna' }),
			h('button', { type: 'button', class: 'button', onclick: function () { add('text'); }, text: '+ Tekst' }),
			h('button', { type: 'button', class: 'button', onclick: function () { add('quote'); }, text: '+ Citat' }),
			h('button', { type: 'button', class: 'button', onclick: function () { add('score'); }, text: '+ Rezultat' }),
			h('button', { type: 'button', class: 'button', onclick: function () { add('table'); }, text: '+ Raspored' })
		]));
		ui.over = null;
		fields();
		draw(ui.canvas, D.slides[cur]);
		overNote();
	}

	/** Mijenja stil; pozadine teksta i citata koje su bile zadane prelaze na zadane novog stila. */
	function setStyle(st) {
		var old = STYLE_BG[style()];
		D.slides.forEach(function (s) {
			if ((s.t === 'text' || s.t === 'quote') && (!s.bg || s.bg === old[s.t])) s.bg = STYLE_BG[st][s.t];
		});
		D.style = st;
		changed(true);
	}

	function label(t) { return { cover: 'Naslovna', text: 'Tekst', quote: 'Citat', score: 'Rezultat', table: 'Raspored' }[t]; }

	function add(t) {
		var s = newSlide(t);
		var prev = D.slides[cur];
		if (t === 'text' || t === 'quote') s.bg = prev && (prev.t === 'text' || prev.t === 'quote') && prev.bg ? prev.bg : STYLE_BG[style()][t];
		D.slides.splice(cur + 1, 0, s);
		cur++;
		changed(true);
	}

	function move(d) {
		var j = cur + d;
		if (j < 0 || j >= D.slides.length) return;
		var t = D.slides[cur]; D.slides[cur] = D.slides[j]; D.slides[j] = t; cur = j;
		changed(true);
	}

	/* ---------- Polja za trenutni slajd ---------- */

	function field(labelText, control, note) {
		return h('label', { class: 'dpo-field' }, [h('span', { class: 'dpo-label', text: labelText }), control, note ? h('span', { class: 'dpo-note', text: note }) : null]);
	}

	function textInput(s, key, multi, rows) {
		var el = h(multi ? 'textarea' : 'input', multi ? { rows: rows || 3 } : { type: 'text' });
		el.value = s[key] || '';
		el.addEventListener('input', function () { s[key] = el.value; changed(); });
		return el;
	}

	function range(s, key, min, max, step, obj) {
		obj = obj || s;
		var el = h('input', { type: 'range', min: min, max: max, step: step });
		el.value = obj[key];
		el.addEventListener('input', function () { obj[key] = parseFloat(el.value); changed(); });
		return el;
	}

	function swatches(s, key, set) {
		var box = h('div', { class: 'dpo-swatches', role: 'radiogroup' });
		Object.keys(set).forEach(function (k) {
			var name = set[k][0], hex = set[k][1];
			box.appendChild(h('button', {
				type: 'button', class: 'dpo-swatch' + (s[key] === hex ? ' is-on' : ''), role: 'radio', 'aria-checked': s[key] === hex ? 'true' : 'false',
				title: name, 'aria-label': name, style: 'background:' + hex,
				onclick: function () { s[key] = hex; changed(true); }
			}));
		});
		return box;
	}

	function checkbox(s, key, text) {
		var el = h('input', { type: 'checkbox', checked: s[key] });
		el.addEventListener('change', function () { s[key] = el.checked; changed(); });
		return h('label', { class: 'dpo-check' }, [el, ' ' + text]);
	}

	function fields() {
		var s = D.slides[cur], F = ui.fields, C = CFG.colors || { chip: {}, bg: {} };
		F.innerHTML = '';
		F.appendChild(h('div', { class: 'dpo-slide-tools' }, [
			h('strong', { text: 'Slajd ' + (cur + 1) + ': ' + label(s.t) }),
			h('span', { class: 'dpo-spacer' }),
			h('button', { type: 'button', class: 'button button-small', onclick: function () { move(-1); }, disabled: cur === 0, text: '← Lijevo' }),
			h('button', { type: 'button', class: 'button button-small', onclick: function () { move(1); }, disabled: cur === D.slides.length - 1, text: 'Desno →' }),
			h('button', { type: 'button', class: 'button button-small', onclick: function () { D.slides.splice(cur + 1, 0, JSON.parse(JSON.stringify(s))); cur++; changed(true); }, text: 'Kopiraj' }),
			h('button', { type: 'button', class: 'button button-small dpo-danger', disabled: D.slides.length === 1, onclick: function () {
				if (!window.confirm('Obrisati ovaj slajd?')) return;
				D.slides.splice(cur, 1); cur = Math.max(0, cur - 1); changed(true);
			}, text: 'Obriši' })
		]));

		if (s.t === 'cover') {
			F.appendChild(field('Oznaka (rubrika)', textInput(s, 'chip'), 'Ostavite prazno da nema oznake.'));
			F.appendChild(field('Boja oznake', swatches(s, 'chipColor', C.chip)));
			F.appendChild(field('Naslov', textInput(s, 'title', true, 3), 'Enter pravi novi red, ako želite sami prelomiti naslov.'));
			F.appendChild(field('Veličina naslova', range(s, 'size', 0.6, 1.3, 0.05)));
			F.appendChild(field('Autor', textInput(s, 'byline'), 'Npr. "Piše: Amina Hodžić". Prazno = bez autora.'));
			F.appendChild(photoFields(s));
			if ((s.photos || []).length) {
				F.appendChild(field('Zatamnjenje ispod naslova', range(s, 'darken', 0, 1, 0.05)));
				F.appendChild(field('Zamućenje fotografije', range(s, 'blur', 0, 20, 1)));
			}
			F.appendChild(field('Potpis fotografije', textInput(s, 'credit'), 'Npr. "Foto: Ime Prezime". Prazno = bez potpisa.'));
			F.appendChild(field((s.photos || []).length ? 'Pozadina (vidi se samo bez fotografije)' : 'Pozadina', swatches(s, 'bg', C.bg)));
		} else if (s.t === 'score') {
			F.appendChild(field('Sport', textInput(s, 'chip'), 'Npr. "Odbojka" ili "Košarka, polufinale".'));
			F.appendChild(field('Boja oznake i našeg tima', swatches(s, 'chipColor', C.chip)));
			F.appendChild(h('div', { class: 'dpo-two' }, [field('Domaći', textInput(s, 'home')), field('Rezultat', textInput(s, 'homeScore'))]));
			F.appendChild(h('div', { class: 'dpo-two' }, [field('Gosti', textInput(s, 'away')), field('Rezultat', textInput(s, 'awayScore'))]));
			var ou = h('select', {}, [h('option', { value: 'away', text: 'Gosti' }), h('option', { value: 'home', text: 'Domaći' }), h('option', { value: '', text: 'Nijedan' })]);
			ou.value = s.ours || '';
			ou.addEventListener('change', function () { s.ours = ou.value; changed(); });
			F.appendChild(field('Naš tim (istaknut bojom)', ou));
			F.appendChild(field('Dodatak ispod', textInput(s, 'detail'), 'Neobavezno, npr. "Gimnazijada 2025, polufinale".'));
			F.appendChild(photoFields(s));
			if ((s.photos || []).length) F.appendChild(field('Zatamnjenje', range(s, 'darken', 0, 1, 0.05)));
			F.appendChild(field('Potpis fotografije', textInput(s, 'credit')));
		} else if (s.t === 'table') {
			F.appendChild(field('Naslov', textInput(s, 'title')));
			F.appendChild(field('Podnaslov (npr. datum)', textInput(s, 'sub')));
			F.appendChild(field('Redovi', textInput(s, 'rows', true, 9), 'Jedan red po liniji: lijevo | desno. Zvjezdica na početku ističe red, npr. "*Veliki odmor | 10:35 – 10:55". Bez | red ide preko cijele širine.'));
			F.appendChild(field('Boja ćelija', swatches(s, 'cellColor', C.chip)));
			F.appendChild(photoFields(s));
			if ((s.photos || []).length) F.appendChild(field('Zatamnjenje fotografije', range(s, 'darken', 0, 1, 0.05)));
			F.appendChild(field((s.photos || []).length ? 'Pozadina (vidi se samo bez fotografije)' : 'Pozadina', swatches(s, 'bg', C.bg)));
		} else if (s.t === 'text') {
			F.appendChild(field('Naslov (podebljano)', textInput(s, 'title', true, 2)));
			F.appendChild(field('Tekst', textInput(s, 'body', true, 8), 'Ako tekst ostavite prazan, naslov postaje velika izjava preko cijelog slajda.'));
			ui.over = h('div', { class: 'dpo-over', hidden: 'hidden' }, [
				h('span', { text: 'Teksta je previše za jedan slajd, pa bi slova bila premala za čitanje. ' }),
				h('button', { type: 'button', class: 'button button-small', onclick: function () { splitText(s); }, text: 'Podijeli na dva slajda' })
			]);
			F.appendChild(ui.over);
			F.appendChild(checkbox(s, 'caps', 'Velika slova (samo za veliku izjavu)'));
			var al = h('select', {}, [h('option', { value: 'left', text: 'Lijevo' }), h('option', { value: 'justify', text: 'Obostrano (kao u novinama)' })]);
			al.value = s.align || 'left';
			al.addEventListener('change', function () { s.align = al.value; changed(); });
			F.appendChild(field('Poravnanje', al));
			F.appendChild(field('Pozadina', swatches(s, 'bg', C.bg)));
		} else {
			F.appendChild(field('Citat', textInput(s, 'quote', true, 5)));
			F.appendChild(field('Ime', textInput(s, 'who'), 'Podebljano ispod citata.'));
			F.appendChild(field('Opis', textInput(s, 'whoInfo'), 'Npr. "učenica 3. razreda". Piše se sivo, poslije imena.'));
			F.appendChild(field('Pozadina', swatches(s, 'bg', C.bg)));
		}
		F.appendChild(checkbox(s, 'logo', 'DP logo u uglu'));
	}

	function photoFields(s) {
		s.photos = s.photos || [];
		var box = h('div', { class: 'dpo-photos' }, [h('span', { class: 'dpo-label', text: s.photos.length > 1 ? 'Fotografije (kolaž, jedna ispod druge)' : 'Fotografija' })]);
		s.photos.forEach(function (p, i) {
			box.appendChild(h('div', { class: 'dpo-photo' }, [
				h('img', { src: p.url, alt: '' }),
				h('div', { class: 'dpo-photo-ctl' }, [
					h('span', { class: 'dpo-note', text: 'Uvećanje' }),
					range(p, 'zoom', 1, 4, 0.05, p),
					h('div', {}, [
						h('button', { type: 'button', class: 'button button-small', onclick: function () { pick(function (a) { s.photos[i] = photo(a); creditFrom(s, a); changed(true); }); }, text: 'Promijeni' }),
						' ',
						h('button', { type: 'button', class: 'button button-small', onclick: function () { p.zoom = 1; p.ox = 0; p.oy = 0; changed(true); }, text: 'Poravnaj' }),
						' ',
						h('button', { type: 'button', class: 'button button-small dpo-danger', onclick: function () { s.photos.splice(i, 1); changed(true); }, text: 'Ukloni' })
					])
				])
			]));
		});
		if (s.photos.length < 3) {
			box.appendChild(h('button', { type: 'button', class: 'button', onclick: function () {
				pick(function (a) { s.photos.push(photo(a)); creditFrom(s, a); changed(true); });
			}, text: s.photos.length ? '+ Kolaž: dodaj još jednu fotografiju' : '+ Dodaj fotografiju' }));
		}
		return box;
	}

	function photo(a) {
		var url = (a.sizes && a.sizes.full && a.sizes.full.url) || a.url;
		loadImage(url);
		return { id: a.id, url: url, zoom: 1, ox: 0, oy: 0 };
	}

	function creditFrom(s, a) {
		var m = String(a.caption || '').replace(/<[^>]+>/g, '').match(/(Foto|Fotografija|Photo)\s*:\s*(.+)$/i);
		if (m && !s.credit) s.credit = 'Foto: ' + m[2].trim();
	}

	var frame;
	function pick(done) {
		frame = wp.media({ title: 'Izaberite fotografiju', button: { text: 'Koristi ovu fotografiju' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () { done(frame.state().get('selection').first().toJSON()); });
		frame.open();
	}

	/* ---------- Pomjeranje fotografije mišem ---------- */

	function setupDrag() {
		var drag = null;
		function point(e) {
			var r = ui.canvas.getBoundingClientRect();
			return { x: (e.clientX - r.left) * ui.canvas.width / r.width, y: (e.clientY - r.top) * ui.canvas.height / r.height };
		}
		ui.canvas.addEventListener('pointerdown', function (e) {
			var s = D.slides[cur];
			if (!(s.photos || []).length) return;
			var pt = point(e), sl = slots(s, ui.canvas.width, ui.canvas.height);
			var i = Math.min(sl.length - 1, Math.floor(pt.y / (ui.canvas.height / sl.length)));
			var p = s.photos[i], img = images[p.url];
			if (!img || !img.naturalWidth) return;
			var b = photoBox(img, sl[i], p);
			drag = { p: p, start: pt, ox: p.ox || 0, oy: p.oy || 0, ovX: b.ovX, ovY: b.ovY };
			ui.canvas.setPointerCapture(e.pointerId);
			e.preventDefault();
		});
		ui.canvas.addEventListener('pointermove', function (e) {
			if (!drag) return;
			var pt = point(e);
			if (drag.ovX > 1) drag.p.ox = Math.max(-1, Math.min(1, drag.ox - 2 * (pt.x - drag.start.x) / drag.ovX));
			if (drag.ovY > 1) drag.p.oy = Math.max(-1, Math.min(1, drag.oy - 2 * (pt.y - drag.start.y) / drag.ovY));
			draw(ui.canvas, D.slides[cur]);
		});
		function end() { if (drag) { drag = null; changed(true); } }
		ui.canvas.addEventListener('pointerup', end);
		ui.canvas.addEventListener('pointercancel', end);
	}

	/* ---------- Spremanje stanja i crtanje ---------- */

	var thumbTimer;
	/** Dijeli predugačak tekst na dva slajda, na kraju rečenice najbliže sredini. */
	function splitText(s) {
		var parts = String(s.body || '').split(/(?<=[.!?…])\s+/), half = String(s.body).length / 2, a = [], len = 0;
		while (parts.length > 1 && len + parts[0].length / 2 < half) { len += parts[0].length + 1; a.push(parts.shift()); }
		if (!a.length) a.push(parts.shift());
		var next = JSON.parse(JSON.stringify(s));
		s.body = a.join(' ');
		next.title = ''; next.body = parts.join(' ');
		D.slides.splice(cur + 1, 0, next);
		changed(true);
	}

	function overNote() {
		if (ui.over) ui.over.hidden = !OVER.get(D.slides[cur]);
	}

	function changed(full) {
		input.value = JSON.stringify(D);
		if (full) { render(); overNote(); return; }
		draw(ui.canvas, D.slides[cur]);
		overNote();
		clearTimeout(thumbTimer);
		thumbTimer = setTimeout(function () {
			var c = ui.strip.querySelectorAll('.dpo-thumb')[cur];
			if (c) draw(c, D.slides[cur], 0.2);
		}, 250);
	}

	function redraw() {
		if (!ui.canvas) return;
		draw(ui.canvas, D.slides[cur]);
		var thumbs = ui.strip.querySelectorAll('.dpo-thumb');
		D.slides.forEach(function (s, i) { if (thumbs[i]) draw(thumbs[i], s, 0.2); });
	}

	/* ---------- Popuni iz članka ---------- */

	function fillFromArticle() {
		var id = ui.fill.value;
		ui.fill.value = '';
		if (!id) return;
		if (!window.confirm('Ovo zamjenjuje sve slajdove podacima iz članka. Nastaviti?')) return;
		$.getJSON(window.ajaxurl, { action: 'dpo_article', id: id, nonce: $('#dpo_nonce').val() }).done(function (r) {
			if (!r || !r.success) { window.alert((r && r.data) || 'Članak se nije mogao učitati.'); return; }
			var format = D.format;
			D = r.data; D.format = format; cur = 0;
			loadAll();
			changed(true);
		}).fail(function () { window.alert('Članak se nije mogao učitati.'); });
	}

	/* ---------- Preuzimanje ---------- */

	function fileName(i) {
		var title = ($('#title').val() || 'objava').toLowerCase()
			.replace(/[čć]/g, 'c').replace(/đ/g, 'dj').replace(/š/g, 's').replace(/ž/g, 'z')
			.replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 50) || 'objava';
		return title + (D.format === 'story' ? '-story' : '') + '-' + (i + 1) + '.png';
	}

	function tainted(s) {
		return (s.photos || []).some(function (p) { return images[p.url] && images[p.url].dpoTainted; });
	}

	function download(i) {
		return new Promise(function (resolve) {
			var s = D.slides[i];
			if (tainted(s)) {
				window.alert('Ova fotografija dolazi s drugog servera pa se slika ne može preuzeti. Otpremite je u Biblioteku medija i izaberite je ponovo.');
				resolve(); return;
			}
			var c = document.createElement('canvas');
			draw(c, s, 2);
			c.toBlob(function (blob) {
				var a = document.createElement('a');
				a.href = URL.createObjectURL(blob);
				a.download = fileName(i);
				document.body.appendChild(a);
				a.click();
				setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); resolve(); }, 300);
			}, 'image/png');
		});
	}

	function downloadCurrent() { download(cur); }

	function downloadAll() {
		var i = 0;
		(function next() {
			if (i >= D.slides.length) return;
			download(i++).then(function () { setTimeout(next, 400); });
		})();
	}

	/* ---------- Start ---------- */

	function start() {
		['white', 'black'].forEach(function (k) {
			var img = new Image();
			img.onload = redraw;
			img.src = CFG.logo[k];
			logos[k] = img;
		});
		loadAll();
		input.value = JSON.stringify(D);
		var faces = [
			new FontFace(SANS, 'url(' + CFG.fonts.regular + ')', { weight: '400' }),
			new FontFace(SANS, 'url(' + CFG.fonts.bold + ')', { weight: '700' }),
			new FontFace(SANS, 'url(' + CFG.fonts.black + ')', { weight: '900' }),
			new FontFace(SANS, 'url(' + CFG.fonts.italic + ')', { weight: '400', style: 'italic' })
		];
		Promise.all(faces.map(function (f) { return f.load().then(function (l) { document.fonts.add(l); }); }))
			.catch(function () {})
			.then(build);
	}

	start();
})(jQuery);
