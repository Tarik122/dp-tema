/* Riječ dana – bosanski Wordle za Drugu Perspektivu. */
(function () {
	'use strict';

	var CFG = window.DPIG_CONFIG || {};
	var root = document.getElementById('dpig-root');
	if (!root) return;

	var KEY_ROWS = [
		['e', 'r', 't', 'z', 'u', 'i', 'o', 'p', 'š', 'đ', 'ž'],
		['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'č', 'ć'],
		['enter', 'c', 'v', 'b', 'n', 'm', 'lj', 'nj', 'dž', 'back']
	];
	var LETTERS = 'abcčćdđefghijklmnoprsštuvzž';
	var GUEST_KEY = 'dpig_guest_v1';
	var EMOJI = ['⬜', '🟦', '🟧'];

	var S = {
		date: null, number: 0, length: 5, maxGuesses: 6, nextIn: 0, special: false,
		player: null,
		game: { rows: [], status: 'playing' },
		input: [],
		busy: false
	};
	var el = {};

	/* ---------- helpers ---------- */

	function h(tag, attrs, children) {
		var node = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			if (k === 'class') node.className = attrs[k];
			else if (k === 'text') node.textContent = attrs[k];
			else if (k.slice(0, 2) === 'on') node.addEventListener(k.slice(2), attrs[k]);
			else node.setAttribute(k, attrs[k]);
		});
		(children || []).forEach(function (c) {
			if (c) node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
		});
		return node;
	}

	function tiles(word) {
		var out = [];
		word = String(word).toLowerCase();
		for (var i = 0; i < word.length; i++) {
			var pair = word.substr(i, 2);
			if (pair === 'lj' || pair === 'nj' || pair === 'dž') { out.push(pair); i++; }
			else out.push(word[i]);
		}
		return out;
	}

	function upper(t) { return t.toUpperCase(); }

	function store(key, value) {
		try {
			if (value === undefined) return JSON.parse(localStorage.getItem(key) || 'null');
			localStorage.setItem(key, JSON.stringify(value));
		} catch (e) { return null; }
	}

	function api(path, body) {
		var opts = { credentials: 'same-origin', headers: { 'X-DPIG': '1' } };
		if (body) {
			opts.method = 'POST';
			opts.headers['Content-Type'] = 'application/json';
			opts.body = JSON.stringify(body);
		}
		return fetch(CFG.api + path, opts).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (data) {
				if (!r.ok) {
					var err = new Error(data.message || 'Greška. Pokušaj ponovo.');
					err.code = data.error;
					throw err;
				}
				return data;
			});
		});
	}

	function toast(msg, ms) {
		var t = h('div', { class: 'dpig-toast', text: msg });
		el.toasts.prepend(t);
		setTimeout(function () { t.classList.add('dpig-fade'); }, ms || 1800);
		setTimeout(function () { t.remove(); }, (ms || 1800) + 400);
	}

	/* ---------- guest storage ---------- */

	function guest() {
		var g = store(GUEST_KEY) || {};
		g.stats = g.stats || { played: 0, won: 0, currentStreak: 0, maxStreak: 0, distribution: [0, 0, 0, 0, 0, 0], lastWon: null };
		if (g.date !== S.date) { g.date = S.date; g.game = { rows: [], status: 'playing' }; }
		return g;
	}

	function saveGuest(g) { store(GUEST_KEY, g); }

	function guestFinished(g) {
		var st = g.stats;
		if (st.recorded === S.date) return;
		st.recorded = S.date;
		st.played++;
		if (g.game.status === 'won') {
			var y = new Date(S.date + 'T12:00:00Z');
			y.setUTCDate(y.getUTCDate() - 1);
			st.currentStreak = st.lastWon === y.toISOString().slice(0, 10) ? st.currentStreak + 1 : 1;
			st.maxStreak = Math.max(st.maxStreak, st.currentStreak);
			st.lastWon = S.date;
			st.won++;
			st.distribution[g.game.rows.length - 1]++;
		} else {
			st.currentStreak = 0;
		}
	}

	function stats() {
		if (S.player) return S.player.stats;
		var st = guest().stats;
		// A streak ends when a day is skipped.
		var y = new Date(S.date + 'T12:00:00Z');
		y.setUTCDate(y.getUTCDate() - 1);
		if (st.lastWon !== S.date && st.lastWon !== y.toISOString().slice(0, 10)) st.currentStreak = 0;
		return st;
	}

	/* ---------- layout ---------- */

	var ICONS = {
		help: '<path d="M9.2 9a3 3 0 1 1 4.3 2.7c-.9.4-1.5 1.1-1.5 2.1v.7"/><circle cx="12" cy="18" r=".6" fill="currentColor"/>',
		trophy: '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M8.5 20h7M10 17h4"/>',
		stats: '<path d="M5 20V12M10 20V6M15 20v-9M20 20V9M3.5 20h18"/>'
	};

	function iconButton(label, paths, onclick) {
		var b = h('button', { class: 'dpig-icon', type: 'button', 'aria-label': label, title: label, onclick: onclick });
		b.innerHTML = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + paths + '</svg>';
		return b;
	}

	function build() {
		root.innerHTML = '';
		el.title = h('div', { class: 'dpig-title' });
		el.streak = h('span', { class: 'dpig-streak-badge', title: 'Trenutni niz pobjeda' });
		var header = h('div', { class: 'dpig-header' }, [
			el.title,
			h('div', { class: 'dpig-header-right' }, [
				el.streak,
				iconButton('Pravila', ICONS.help, showHelp),
				iconButton('Ljestvica', ICONS.trophy, function () { showLeaderboard('today'); }),
				iconButton('Statistika', ICONS.stats, showStats)
			])
		]);
		el.account = h('div', { class: 'dpig-account' });
		el.board = h('div', { class: 'dpig-board' });
		el.toasts = h('div', { class: 'dpig-toasts', 'aria-live': 'polite' });
		el.keyboard = h('div', { class: 'dpig-keyboard' });
		el.modal = h('div', { class: 'dpig-modal', hidden: '' });
		el.modal.addEventListener('click', function (e) { if (e.target === el.modal) closeModal(); });

		KEY_ROWS.forEach(function (row) {
			var r = h('div', { class: 'dpig-krow' });
			row.forEach(function (k) {
				var label = k === 'enter' ? 'UNESI' : k === 'back' ? '⌫' : upper(k);
				var b = h('button', { class: 'dpig-key' + (k.length > 1 && k !== 'lj' && k !== 'nj' && k !== 'dž' ? ' dpig-wide' : ''), 'data-key': k, text: label, 'aria-label': k === 'back' ? 'Obriši' : label });
				b.addEventListener('click', function () { press(k); b.blur(); });
				r.appendChild(b);
			});
			el.keyboard.appendChild(r);
		});

		root.appendChild(header);
		root.appendChild(el.account);
		root.appendChild(h('div', { class: 'dpig-stage' }, [el.toasts, el.board]));
		root.appendChild(el.keyboard);
		root.appendChild(el.modal);
	}

	function render() {
		el.title.textContent = (CFG.title || 'Riječ dana') + ' #' + S.number + (S.special ? ' ⭐' : '');
		var st = stats();
		el.streak.textContent = st.currentStreak ? '🔥 ' + st.currentStreak : '';
		renderAccount();
		renderBoard();
		renderKeys();
	}

	function renderBoard() {
		el.board.innerHTML = '';
		for (var r = 0; r < S.maxGuesses; r++) {
			var row = h('div', { class: 'dpig-row' });
			var data = S.game.rows[r];
			var isCurrent = !data && r === S.game.rows.length && S.game.status === 'playing';
			for (var c = 0; c < S.length; c++) {
				var tile = h('div', { class: 'dpig-tile' });
				if (data) {
					tile.textContent = upper(data.tiles[c]);
					tile.className += ' dpig-s' + data.result[c];
				} else if (isCurrent && S.input[c]) {
					tile.textContent = upper(S.input[c]);
					tile.className += ' dpig-filled';
				}
				row.appendChild(tile);
			}
			el.board.appendChild(row);
		}
	}

	function renderKeys() {
		var best = {};
		S.game.rows.forEach(function (row) {
			row.tiles.forEach(function (t, i) { best[t] = Math.max(best[t] === undefined ? -1 : best[t], row.result[i]); });
		});
		el.keyboard.querySelectorAll('.dpig-key').forEach(function (b) {
			var k = b.getAttribute('data-key');
			b.classList.remove('dpig-s0', 'dpig-s1', 'dpig-s2');
			if (best[k] !== undefined) b.classList.add('dpig-s' + best[k]);
		});
	}

	function renderAccount() {
		el.account.innerHTML = '';
		if (S.player) {
			el.account.appendChild(h('span', {}, [
				'Igraš kao ',
				h('strong', { text: S.player.anonymous ? 'Anonimni igrač' : S.player.name }),
				S.player.anonymous ? ' (' + S.player.name + ')' : ''
			]));
			return;
		}
		if (!CFG.clientId) {
			el.account.appendChild(h('span', { text: 'Igraš anonimno.' }));
			return;
		}
		// Na telefonu nema mjesta za Google dugme ispod naslova: prijava je u prozoru Statistika.
		if (window.innerWidth < 600) {
			el.account.appendChild(h('span', {}, [
				'Igraš anonimno. ',
				h('button', { class: 'dpig-link', type: 'button', onclick: showStats, text: 'Prijavi se' }),
				' za ljestvicu i niz.'
			]));
			return;
		}
		el.account.appendChild(h('span', { text: 'Igraš anonimno. Prijavi se školskim mailom za ljestvicu i niz:' }));
		var slot = h('div', { class: 'dpig-google' });
		el.account.appendChild(slot);
		renderGoogleButton(slot);
	}

	/* ---------- Google sign-in ---------- */

	var googleReady = false;
	function renderGoogleButton(slot, tries) {
		if (!window.google || !google.accounts || !google.accounts.id) {
			if ((tries || 0) < 50) setTimeout(function () { renderGoogleButton(slot, (tries || 0) + 1); }, 200);
			return;
		}
		if (!googleReady) {
			google.accounts.id.initialize({
				client_id: CFG.clientId,
				callback: onGoogleLogin,
				hd: CFG.domain || undefined,
				ux_mode: 'popup',
				auto_select: false
			});
			googleReady = true;
		}
		google.accounts.id.renderButton(slot, { theme: 'outline', size: 'medium', text: 'signin_with', shape: 'pill', locale: 'bs' });
	}

	function onGoogleLogin(resp) {
		var g = guest();
		api('login', {
			credential: resp.credential,
			date: S.date,
			history: g.game.rows.map(function (r) { return r.word; })
		}).then(function () {
			toast('Prijavljen/a si! 🎉');
			closeModal();
			return load();
		}).catch(function (e) { toast(e.message, 4000); });
	}

	/* ---------- input ---------- */

	function press(k) {
		if (S.busy || S.game.status !== 'playing' || !el.modal.hidden) return;
		if (k === 'enter') return submit();
		if (k === 'back') { S.input.pop(); return renderBoard(); }
		if (S.input.length >= S.length) return;
		S.input.push(k);
		renderBoard();
	}

	document.addEventListener('keydown', function (e) {
		if (e.ctrlKey || e.metaKey || e.altKey) return;
		var t = e.target;
		if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
		if (e.key === 'Escape' && !el.modal.hidden) return closeModal();
		if (!el.modal.hidden || !root.isConnected) return;
		var key = e.key.toLowerCase();
		if (key === 'enter') { e.preventDefault(); return press('enter'); }
		if (key === 'backspace') { e.preventDefault(); return press('back'); }
		if (key.length !== 1 || LETTERS.indexOf(key) === -1) return;
		// Typing L+J, N+J or D+Ž on a physical keyboard makes one letter.
		var last = S.input[S.input.length - 1];
		if ((key === 'j' && (last === 'l' || last === 'n')) || (key === 'ž' && last === 'd')) {
			S.input[S.input.length - 1] = last + key;
			return renderBoard();
		}
		press(key);
	});

	function shake(msg) {
		toast(msg);
		var row = el.board.children[S.game.rows.length];
		if (row) { row.classList.remove('dpig-shake'); void row.offsetWidth; row.classList.add('dpig-shake'); }
	}

	function submit() {
		if (S.input.length < S.length) return shake('Premalo slova');
		var word = S.input.join('');
		var g = S.player ? null : guest();
		S.busy = true;
		api('guess', {
			date: S.date,
			guess: word,
			history: g ? g.game.rows.map(function (r) { return r.word; }) : undefined
		}).then(function (data) {
			var before = S.game.rows.length;
			S.game = data.game;
			S.input = [];
			if (data.player) S.player = data.player;
			if (g) {
				g.game = data.game;
				if (data.game.status !== 'playing') guestFinished(g);
				saveGuest(g);
			}
			reveal(before, function () {
				S.busy = false;
				render();
				if (S.game.status !== 'playing') finished();
			});
		}).catch(function (e) {
			S.busy = false;
			if (e.code === 'dpig_new_day') { toast(e.message, 3000); setTimeout(load, 1500); return; }
			shake(e.message);
		});
	}

	function reveal(index, done) {
		renderBoard();
		var row = el.board.children[index];
		if (!row) return done();
		Array.prototype.forEach.call(row.children, function (tile, i) {
			var cls = tile.className;
			tile.className = 'dpig-tile dpig-filled';
			tile.textContent = upper(S.game.rows[index].tiles[i]);
			setTimeout(function () {
				tile.classList.add('dpig-flip');
				setTimeout(function () { tile.className = cls + ' dpig-flip'; }, 250);
			}, i * 280);
		});
		setTimeout(done, S.length * 280 + 300);
	}

	var PRAISE = ['Genijalno!', 'Veličanstveno!', 'Odlično!', 'Sjajno!', 'Bravo!', 'Uf, za dlaku!'];
	function finished() {
		if (S.game.status === 'won') {
			var row = el.board.children[S.game.rows.length - 1];
			if (row) row.classList.add('dpig-win');
			toast(PRAISE[S.game.rows.length - 1], 2000);
		} else {
			toast('Riječ je bila: ' + S.game.answer.toUpperCase(), 3500);
		}
		setTimeout(showStats, 1600);
	}

	/* ---------- modals ---------- */

	function openModal(title, body) {
		el.modal.innerHTML = '';
		el.modal.appendChild(h('div', { class: 'dpig-dialog', role: 'dialog', 'aria-modal': 'true', 'aria-label': title }, [
			h('button', { class: 'dpig-close', 'aria-label': 'Zatvori', onclick: closeModal, text: '×' }),
			h('h2', { text: title }),
			body
		]));
		el.modal.hidden = false;
		el.modal.querySelector('.dpig-close').focus();
	}

	function closeModal() {
		el.modal.hidden = true;
		el.modal.innerHTML = '';
		stopCountdown();
	}

	function example(word, idx, state, text) {
		var row = h('div', { class: 'dpig-row dpig-small' });
		tiles(word).forEach(function (t, i) {
			row.appendChild(h('div', { class: 'dpig-tile ' + (i === idx ? 'dpig-s' + state : 'dpig-filled'), text: upper(t) }));
		});
		return h('div', { class: 'dpig-example' }, [row, h('p', { text: text })]);
	}

	function showHelp() {
		openModal('Kako se igra', h('div', {}, [
			h('p', { text: 'Pogodi riječ od ' + S.length + ' slova u ' + S.maxGuesses + ' pokušaja. Svaki pokušaj mora biti prava riječ. Nova riječ stiže svaki dan u ponoć.' }),
			h('p', { text: 'LJ, NJ i DŽ su jedno slovo (kao u abecedi), zato imaju svoje tipke.' }),
			example('ljubav', 0, 2, 'LJ je u riječi i na pravom mjestu.'),
			example('škola', 2, 1, 'O je u riječi, ali na drugom mjestu.'),
			example('radio', 3, 0, 'I nije u riječi.'),
			h('p', { text: 'Prijavi se školskim mailom (@' + (CFG.domain || '') + ') da bi tvoji rezultati ušli na ljestvicu. Ime se uzima iz maila, a u statistici možeš izabrati da budeš anoniman/na. Bodovi: 6 za pogodak iz prvog pokušaja, … 1 za pogodak iz šestog.' })
		]));
	}

	var countdownTimer = null;
	function stopCountdown() { if (countdownTimer) clearInterval(countdownTimer); countdownTimer = null; }
	function countdown(node) {
		var end = Date.now() + S.nextIn * 1000;
		function tick() {
			var s = Math.max(0, Math.round((end - Date.now()) / 1000));
			if (s === 0) { stopCountdown(); node.textContent = 'Nova riječ je stigla – osvježi stranicu!'; return; }
			var p = function (n) { return (n < 10 ? '0' : '') + n; };
			node.textContent = p(Math.floor(s / 3600)) + ':' + p(Math.floor(s / 60) % 60) + ':' + p(s % 60);
		}
		stopCountdown();
		tick();
		countdownTimer = setInterval(tick, 1000);
	}

	function showStats() {
		var st = stats();
		var over = S.game.status !== 'playing';
		var body = h('div', {});

		if (over && S.game.note) body.appendChild(h('p', { class: 'dpig-note', text: S.game.note }));
		if (over && S.game.status === 'lost') body.appendChild(h('p', { class: 'dpig-answer' }, ['Riječ je bila ', h('strong', { text: S.game.answer.toUpperCase() })]));

		var nums = [
			[st.played, 'Odigrano'],
			[st.played ? Math.round(100 * st.won / st.played) : 0, '% pobjeda'],
			[st.currentStreak, 'Trenutni niz 🔥'],
			[st.maxStreak, 'Najduži niz']
		];
		if (S.player) nums.push([S.player.stats.points, 'Bodova']);
		body.appendChild(h('div', { class: 'dpig-nums' }, nums.map(function (n) {
			return h('div', {}, [h('div', { class: 'dpig-num', text: String(n[0]) }), h('div', { class: 'dpig-label', text: n[1] })]);
		})));

		body.appendChild(h('h3', { text: 'Raspodjela pokušaja' }));
		var dist = st.distribution || [];
		var most = Math.max.apply(null, dist.concat([1]));
		body.appendChild(h('div', { class: 'dpig-dist' }, dist.map(function (n, i) {
			var mine = over && S.game.status === 'won' && S.game.rows.length === i + 1;
			var bar = h('div', { class: 'dpig-bar' + (mine ? ' dpig-mine' : ''), text: String(n) });
			bar.style.width = Math.max(8, Math.round(100 * n / most)) + '%';
			return h('div', { class: 'dpig-drow' }, [h('span', { text: String(i + 1) }), bar]);
		})));

		if (over) {
			var clock = h('div', { class: 'dpig-clock' });
			body.appendChild(h('div', { class: 'dpig-after' }, [
				h('div', {}, [h('div', { class: 'dpig-label', text: 'Sljedeća riječ za' }), clock]),
				h('button', { class: 'dpig-btn dpig-primary', onclick: share, text: 'Podijeli rezultat' })
			]));
			countdown(clock);
		}

		body.appendChild(h('button', { class: 'dpig-btn', onclick: function () { showLeaderboard('today'); }, text: 'Ljestvica' }));

		if (S.player) {
			var toggle = h('input', { type: 'checkbox', id: 'dpig-anon' });
			toggle.checked = S.player.anonymous;
			toggle.addEventListener('change', function () {
				api('preferences', { anonymous: toggle.checked }).then(function () {
					S.player.anonymous = toggle.checked;
					renderAccount();
					toast(toggle.checked ? 'Na ljestvici si sada anoniman/na.' : 'Na ljestvici se vidi tvoje ime.');
				}).catch(function (e) { toast(e.message); });
			});
			body.appendChild(h('div', { class: 'dpig-account-box' }, [
				h('div', {}, [h('strong', { text: S.player.name }), h('div', { class: 'dpig-label', text: S.player.email })]),
				h('label', { for: 'dpig-anon' }, [toggle, ' Sakrij moje ime na ljestvici']),
				h('button', { class: 'dpig-link', onclick: logout, text: 'Odjava' })
			]));
		} else if (CFG.clientId) {
			var slot = h('div', { class: 'dpig-google' });
			body.appendChild(h('div', { class: 'dpig-account-box' }, [
				h('p', { text: 'Ova statistika je sačuvana samo u ovom pregledniku. Prijavi se školskim mailom da uđeš na ljestvicu' + (over ? ' – današnja igra će se računati.' : '.') }),
				slot
			]));
			renderGoogleButton(slot);
		}
		openModal('Statistika', body);
	}

	function share() {
		var lines = [(CFG.title || 'Riječ dana') + ' #' + S.number + ' ' + (S.game.status === 'won' ? S.game.rows.length : 'X') + '/' + S.maxGuesses];
		var st = stats();
		if (st.currentStreak > 1) lines[0] += ' 🔥' + st.currentStreak;
		lines.push('');
		S.game.rows.forEach(function (r) { lines.push(r.result.map(function (x) { return EMOJI[x]; }).join('')); });
		if (CFG.pageUrl) { lines.push(''); lines.push(CFG.pageUrl); }
		var text = lines.join('\n');
		if (navigator.share && /Mobi|Android/i.test(navigator.userAgent)) {
			navigator.share({ text: text }).catch(function () {});
		} else if (navigator.clipboard) {
			navigator.clipboard.writeText(text).then(function () { toast('Kopirano! Zalijepi rezultat prijateljima.'); });
		} else {
			window.prompt('Kopiraj rezultat:', text);
		}
	}

	var BOARDS = [['today', 'Danas'], ['streak', 'Niz 🔥'], ['month', 'Ovaj mjesec'], ['all', 'Ukupno']];
	var BOARD_HINTS = {
		today: 'Današnji pogoci: najmanje pokušaja, pa ko je prvi pogodio.',
		streak: 'Najviše pogođenih dana zaredom (niz se prekida ako propustiš dan ili ne pogodiš).',
		month: 'Bodovi ovaj mjesec (6 za prvi pokušaj … 1 za šesti). Pobjede/igre.',
		all: 'Ukupni bodovi od početka. Pobjede/igre.'
	};

	function showLeaderboard(type) {
		var tabs = h('div', { class: 'dpig-tabs', role: 'tablist' }, BOARDS.map(function (b) {
			return h('button', { class: 'dpig-tab' + (b[0] === type ? ' dpig-active' : ''), role: 'tab', 'aria-selected': String(b[0] === type), onclick: function () { showLeaderboard(b[0]); }, text: b[1] });
		}));
		var list = h('div', { class: 'dpig-lb' }, [h('p', { class: 'dpig-label', text: 'Učitavam…' })]);
		var body = h('div', {}, [tabs, h('p', { class: 'dpig-label', text: BOARD_HINTS[type] }), list]);
		if (!S.player) body.appendChild(h('p', { class: 'dpig-label', text: 'Na ljestvici su samo igrači prijavljeni školskim mailom.' }));
		openModal('Ljestvica', body);

		api('leaderboard?type=' + type).then(function (data) {
			list.innerHTML = '';
			if (!data.rows.length) {
				list.appendChild(h('p', { text: type === 'today' ? 'Još niko nije pogodio današnju riječ. Budi prvi/a!' : 'Još nema rezultata.' }));
				return;
			}
			var medals = ['🥇', '🥈', '🥉'];
			data.rows.forEach(function (r) {
				list.appendChild(h('div', { class: 'dpig-lrow' + (r.you ? ' dpig-you' : '') }, [
					h('span', { class: 'dpig-rank', text: medals[r.rank - 1] || String(r.rank) }),
					h('span', { class: 'dpig-name', text: r.name + (r.you ? ' (ti)' : '') }),
					r.extra ? h('span', { class: 'dpig-label', text: r.extra }) : null,
					h('span', { class: 'dpig-value', text: String(r.value) })
				]));
			});
		}).catch(function (e) { list.innerHTML = ''; list.appendChild(h('p', { text: e.message })); });
	}

	function logout() {
		api('logout', {}).then(function () {
			if (window.google && google.accounts && google.accounts.id) google.accounts.id.disableAutoSelect();
			closeModal();
			toast('Odjavljen/a si.');
			load();
		});
	}

	/* ---------- start ---------- */

	function load() {
		return api('state').then(function (data) {
			S.date = data.date;
			S.number = data.number;
			S.length = data.length;
			S.maxGuesses = data.maxGuesses;
			S.nextIn = data.nextIn;
			S.special = data.special;
			S.player = data.player;
			S.input = [];
			S.game = data.player ? data.game : guest().game;
			if (!S.game) S.game = { rows: [], status: 'playing' };
			render();
			fitOnPhone();
			if (!store('dpig_seen_help')) { store('dpig_seen_help', 1); showHelp(); }
		}).catch(function (e) {
			root.innerHTML = '';
			root.appendChild(h('p', { class: 'dpig-error', text: 'Igra se nije mogla učitati: ' + e.message }));
		});
	}

	/* Na telefonu stranicu pomjeri taman toliko da se vidi cijela tastatura. */
	var fitted = false;
	function fitOnPhone() {
		if (fitted || window.innerWidth >= 600 || window.scrollY > 0) return;
		fitted = true;
		var bottom = el.keyboard.getBoundingClientRect().bottom + 12;
		var top = root.getBoundingClientRect().top;
		var by = Math.min(bottom - window.innerHeight, top);
		if (by > 0) window.scrollTo(0, by);
	}

	build();
	load();
})();
