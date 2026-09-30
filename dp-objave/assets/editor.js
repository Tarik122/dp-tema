/* DP objave: uređivač Instagram objava. Crta slajdove na <canvas> i preuzima ih kao PNG. */
(function ($) {
	'use strict';

	var CFG = window.DPO || {};
	var SIZES = { post: [1080, 1350], story: [1080, 1920] };
	var FONT = 'DPO Lato';
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
		var s = { t: t, bg: '#402f65', logo: true, title: '' };
		if (t === 'cover') {
			s.title = 'Naslov objave'; s.chip = 'Vijesti'; s.chipColor = '#5271fe'; s.credit = '';
			s.darken = 0.6; s.blur = 0; s.size = 1; s.photos = [];
		} else if (t === 'text') {
			s.title = 'Kratka, jaka rečenica kao naslov'; s.body = 'Ovdje ide tekst. Najbolje je dva do četiri kraća pasusa iz članka.'; s.align = 'left';
		} else {
			s.quote = 'Ovdje ide citat iz članka.'; s.who = 'Ime Prezime, učenica 3. razreda';
		}
		return s;
	}

	/* ---------- Pomoćne za crtanje ---------- */

	function font(weight, size, italic) { return (italic ? 'italic ' : '') + weight + ' ' + Math.round(size) + 'px "' + FONT + '"'; }

	function isLight(hex) {
		var n = parseInt(hex.slice(1), 16);
		return (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) > 180;
	}

	/** Prelama tekst u redove zadate širine; poštuje nove redove koje je autor upisao. */
	function wrap(ctx, text, maxW) {
		var lines = [];
		String(text || '').split('\n').forEach(function (para) {
			var words = para.split(/\s+/).filter(Boolean), line = '';
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

	function drawLogo(ctx, s, W, H, format, dark) {
		if (!s.logo) return;
		var img = logos[dark ? 'black' : 'white'];
		if (!img || !img.complete) return;
		var w = format === 'story' ? 150 : 128, h = w * img.naturalHeight / img.naturalWidth;
		var bottom = format === 'story' ? 90 : 34;
		ctx.drawImage(img, W - w - 34, H - h - bottom, w, h);
	}

	/* ---------- Tri vrste slajdova ---------- */

	function drawCover(ctx, s, W, H, format) {
		ctx.fillStyle = s.bg || '#402f65';
		ctx.fillRect(0, 0, W, H);
		var photos = s.photos || [], sl = slots(s, W, H), any = false;
		photos.forEach(function (p, i) {
			var img = images[p.url];
			if (!img || !img.complete || !img.naturalWidth) return;
			any = true;
			var b = photoBox(img, sl[i], p);
			ctx.save();
			ctx.beginPath(); ctx.rect(sl[i].x, sl[i].y, sl[i].w, sl[i].h); ctx.clip();
			if (s.blur > 0) ctx.filter = 'blur(' + s.blur + 'px)';
			ctx.drawImage(img, b.x, b.y, b.w, b.h);
			ctx.restore();
		});
		// Zatamnjenje samo odozdo, iza teksta, da naslov bude čitljiv.
		if (any && s.darken > 0) {
			var g = ctx.createLinearGradient(0, H * 0.35, 0, H);
			g.addColorStop(0, 'rgba(0,0,0,0)');
			g.addColorStop(0.55, 'rgba(0,0,0,' + (s.darken * 0.7).toFixed(3) + ')');
			g.addColorStop(1, 'rgba(0,0,0,' + s.darken.toFixed(3) + ')');
			ctx.fillStyle = g;
			ctx.fillRect(0, H * 0.35, W, H * 0.65);
		}

		var dark = !any && isLight(s.bg || '#000000');
		var left = 80, maxW = W - 160;
		var bottom = H - (format === 'story' ? 250 : 150);
		var size = (format === 'story' ? 90 : 84) * (s.size || 1), lines;
		ctx.font = font(700, size);
		lines = wrap(ctx, s.title, maxW);
		while (lines.length > 5 && size > 50) { size -= 4; ctx.font = font(700, size); lines = wrap(ctx, s.title, maxW); }
		var lh = size * 1.08;
		ctx.fillStyle = dark ? '#141414' : '#ffffff';
		ctx.textBaseline = 'alphabetic';
		lines.forEach(function (l, i) { ctx.fillText(l.text, left, bottom - (lines.length - 1 - i) * lh); });

		// Oznaka rubrike iznad naslova.
		if (s.chip) {
			var cs = format === 'story' ? 62 : 56;
			ctx.font = font(400, cs);
			var cw = ctx.measureText(s.chip).width + 30, ch = cs * 1.3;
			var cy = bottom - (lines.length - 1) * lh - size * 0.95 - 26 - ch;
			ctx.fillStyle = s.chipColor || '#5271fe';
			ctx.fillRect(left, cy, cw, ch);
			ctx.fillStyle = '#ffffff';
			ctx.fillText(s.chip, left + 15, cy + ch * 0.76);
		}

		if (s.credit) {
			ctx.font = font(400, 24);
			ctx.fillStyle = dark ? 'rgba(20,20,20,.6)' : 'rgba(255,255,255,.75)';
			ctx.fillText(s.credit, left, H - (format === 'story' ? 110 : 52));
		}
		drawLogo(ctx, s, W, H, format, dark);
	}

	function drawText(ctx, s, W, H, format) {
		var dark = isLight(s.bg || '#402f65');
		ctx.fillStyle = s.bg || '#402f65';
		ctx.fillRect(0, 0, W, H);
		var left = 150, maxW = W - 300, room = H - (format === 'story' ? 560 : 300);
		var ts = 58, bs = 44, t, b, total;
		function measure() {
			ctx.font = font(700, ts); t = s.title ? wrap(ctx, s.title, maxW) : [];
			ctx.font = font(400, bs); b = s.body ? wrap(ctx, s.body, maxW) : [];
			total = t.length * ts * 1.06 + (t.length && b.length ? 18 : 0) + b.length * bs * 1.2;
		}
		measure();
		while (total > room && bs > 26) { ts -= 2; bs -= 2; measure(); }
		var y = (H - total) / 2 + ts * 0.8;
		ctx.fillStyle = dark ? '#141414' : '#ffffff';
		ctx.font = font(700, ts);
		t.forEach(function (l) { drawLine(ctx, l, left, y, maxW, s.align === 'justify'); y += ts * 1.06; });
		if (t.length && b.length) y += 18 + (bs - ts) * 0.8;
		else if (!t.length) y += (bs - ts) * 0.8;
		ctx.fillStyle = dark ? 'rgba(20,20,20,.72)' : 'rgba(255,255,255,.68)';
		ctx.font = font(400, bs);
		b.forEach(function (l) { drawLine(ctx, l, left, y, maxW, s.align === 'justify'); y += bs * 1.2; });
		drawLogo(ctx, s, W, H, format, dark);
	}

	function drawQuote(ctx, s, W, H, format) {
		var dark = isLight(s.bg || '#402f65');
		ctx.fillStyle = s.bg || '#402f65';
		ctx.fillRect(0, 0, W, H);
		var left = 172, maxW = W - 330, qs = 60, q, room = H - (format === 'story' ? 600 : 360);
		ctx.font = font(400, qs); q = wrap(ctx, s.quote, maxW);
		while (q.length * qs * 1.15 > room && qs > 28) { qs -= 2; ctx.font = font(400, qs); q = wrap(ctx, s.quote, maxW); }
		var lh = qs * 1.15, ws = Math.max(30, Math.round(qs * 0.66)), who = [];
		if (s.who) { ctx.font = font(400, ws, true); who = wrap(ctx, '— ' + s.who, maxW); }
		var total = q.length * lh + (who.length ? 24 + who.length * ws * 1.25 : 0);
		var top = (H - total) / 2;
		// Narandžasta crta uz citat.
		ctx.fillStyle = '#ee8031';
		ctx.fillRect(left - 30, top + qs * 0.1, 10, q.length * lh - qs * 0.05);
		ctx.fillStyle = dark ? '#141414' : '#ffffff';
		ctx.font = font(400, qs);
		q.forEach(function (l, i) { ctx.fillText(l.text, left, top + qs * 0.95 + i * lh); });
		// Ko je rekao: kurziv, poravnato desno (i u više redova ako je dugo).
		ctx.font = font(400, ws, true);
		ctx.fillStyle = dark ? 'rgba(20,20,20,.8)' : 'rgba(255,255,255,.9)';
		who.forEach(function (l, i) {
			ctx.fillText(l.text, left + maxW - ctx.measureText(l.text).width, top + q.length * lh + 24 + ws + i * ws * 1.25);
		});
		drawLogo(ctx, s, W, H, format, dark);
	}

	function draw(canvas, s) {
		var size = SIZES[D.format] || SIZES.post;
		if (canvas.width !== size[0] || canvas.height !== size[1]) { canvas.width = size[0]; canvas.height = size[1]; }
		var ctx = canvas.getContext('2d');
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		({ cover: drawCover, text: drawText, quote: drawQuote })[s.t](ctx, s, canvas.width, canvas.height, D.format);
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
		ui.format = h('div', { class: 'dpo-seg' });
		ui.fill = h('select', { class: 'dpo-article' }, [h('option', { value: '', text: 'Popuni iz članka…' })].concat((CFG.articles || []).map(function (a) {
			return h('option', { value: a.id, text: a.title });
		})));
		ui.fill.addEventListener('change', fillFromArticle);
		ui.strip = h('div', { class: 'dpo-strip' });
		ui.canvas = h('canvas', { class: 'dpo-preview', 'aria-label': 'Pregled slajda' });
		ui.fields = h('div', { class: 'dpo-fields' });

		app.appendChild(h('div', { class: 'dpo-top' }, [
			ui.format,
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
		// Format
		ui.format.innerHTML = '';
		[['post', 'Objava 4:5'], ['story', 'Story 9:16']].forEach(function (f) {
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
			draw(c, s);
		});
		ui.strip.appendChild(h('div', { class: 'dpo-add' }, [
			h('button', { type: 'button', class: 'button', onclick: function () { add('cover'); }, text: '+ Naslovna' }),
			h('button', { type: 'button', class: 'button', onclick: function () { add('text'); }, text: '+ Tekst' }),
			h('button', { type: 'button', class: 'button', onclick: function () { add('quote'); }, text: '+ Citat' })
		]));
		fields();
		draw(ui.canvas, D.slides[cur]);
	}

	function label(t) { return { cover: 'Naslovna', text: 'Tekst', quote: 'Citat' }[t]; }

	function add(t) {
		var s = newSlide(t);
		var prev = D.slides[cur];
		if (prev && prev.bg && t !== 'cover') s.bg = prev.t === 'cover' ? s.bg : prev.bg;
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
			F.appendChild(field('Veličina naslova', range(s, 'size', 0.6, 1.4, 0.05)));
			F.appendChild(photoFields(s));
			if ((s.photos || []).length) {
				F.appendChild(field('Zatamnjenje ispod naslova', range(s, 'darken', 0, 1, 0.05)));
				F.appendChild(field('Zamućenje fotografije', range(s, 'blur', 0, 20, 1)));
			}
			F.appendChild(field('Potpis fotografije', textInput(s, 'credit'), 'Npr. "Foto: Ime Prezime". Prazno = bez potpisa.'));
			F.appendChild(field((s.photos || []).length ? 'Pozadina (vidi se samo bez fotografije)' : 'Pozadina', swatches(s, 'bg', C.bg)));
		} else if (s.t === 'text') {
			F.appendChild(field('Naslov (podebljano)', textInput(s, 'title', true, 2)));
			F.appendChild(field('Tekst', textInput(s, 'body', true, 8), 'Tekst se sam smanji ako ga je previše.'));
			var al = h('select', {}, [h('option', { value: 'left', text: 'Lijevo' }), h('option', { value: 'justify', text: 'Obostrano (kao u novinama)' })]);
			al.value = s.align || 'left';
			al.addEventListener('change', function () { s.align = al.value; changed(); });
			F.appendChild(field('Poravnanje', al));
			F.appendChild(field('Pozadina', swatches(s, 'bg', C.bg)));
		} else {
			F.appendChild(field('Citat', textInput(s, 'quote', true, 5)));
			F.appendChild(field('Ko je rekao', textInput(s, 'who'), 'Npr. "Sema Čeljo, učenica 3. razreda". Crtica se doda sama.'));
			F.appendChild(field('Pozadina', swatches(s, 'bg', C.bg)));
		}
		F.appendChild(checkbox(s, 'logo', 'DP logo u uglu'));
	}

	function photoFields(s) {
		s.photos = s.photos || [];
		var box = h('div', { class: 'dpo-photos' }, [h('span', { class: 'dpo-label', text: 'Fotografije (1 do 3, jedna ispod druge)' })]);
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
			}, text: s.photos.length ? '+ Dodaj još jednu fotografiju' : '+ Dodaj fotografiju' }));
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
			if (s.t !== 'cover' || !(s.photos || []).length) return;
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
	function changed(full) {
		input.value = JSON.stringify(D);
		if (full) { render(); return; }
		draw(ui.canvas, D.slides[cur]);
		clearTimeout(thumbTimer);
		thumbTimer = setTimeout(function () {
			var c = ui.strip.querySelectorAll('.dpo-thumb')[cur];
			if (c) draw(c, D.slides[cur]);
		}, 250);
	}

	function redraw() {
		if (!ui.canvas) return;
		draw(ui.canvas, D.slides[cur]);
		var thumbs = ui.strip.querySelectorAll('.dpo-thumb');
		D.slides.forEach(function (s, i) { if (thumbs[i]) draw(thumbs[i], s); });
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
			draw(c, s);
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
			new FontFace(FONT, 'url(' + CFG.fonts.regular + ')', { weight: '400' }),
			new FontFace(FONT, 'url(' + CFG.fonts.bold + ')', { weight: '700' }),
			new FontFace(FONT, 'url(' + CFG.fonts.italic + ')', { weight: '400', style: 'italic' })
		];
		Promise.all(faces.map(function (f) { return f.load().then(function (l) { document.fonts.add(l); }); }))
			.catch(function () {})
			.then(build);
	}

	start();
})(jQuery);
