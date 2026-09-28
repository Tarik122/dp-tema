/* Ljestve: od jedne riječi do druge, mijenjajući jedno slovo. */
(function () {
	'use strict';

	var D = window.DPIG;
	var h = D.h;
	var root = document.getElementById('dpig-root');
	if (!root || !D) return;

	var LEN = parseInt(D.CFG.length, 10) || 4;
	var GUEST_KEY = 'dpig_lj_v1';
	var KEY_ROWS = [
		['e', 'r', 't', 'z', 'u', 'i', 'o', 'p', 'š', 'đ', 'ž'],
		['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'č', 'ć'],
		['enter', 'c', 'v', 'b', 'n', 'm', 'lj', 'nj', 'dž', 'back']
	];
	var LETTERS = 'abcčćdđefghijklmnoprsštuvzž';

	var S = {
		date: null, number: 0, start: '', target: '', par: 0, nextIn: 0, player: null,
		steps: [], hints: 0, status: 'playing', path: null,
		input: [], sel: 0, lastKey: null, busy: false
	};
	var el = {};
	var shell;

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

	function up(t) { return t.toUpperCase(); }

	function last() { return S.steps.length ? S.steps[S.steps.length - 1].w : S.start; }

	/* ---------- score: typed steps + hints costing 2, 4, 8… ---------- */

	function typed() { return S.steps.filter(function (s) { return !s.h; }).length; }
	function score() { return typed() + (S.hints ? Math.pow(2, S.hints + 1) - 2 : 0); }
	function stepWord(n) { return D.plural(n, 'korak', 'koraka'); }

	/* ---------- guest storage ---------- */

	function guest() {
		var g = D.store(GUEST_KEY) || {};
		if (g.date !== S.date) { g.date = S.date; g.steps = []; g.hints = 0; g.status = 'playing'; g.path = null; }
		g.stats = g.stats || {};
		return g;
	}

	function saveGuest() {
		if (S.player) return;
		var g = guest();
		g.steps = S.steps;
		g.hints = S.hints;
		g.status = S.status;
		g.path = S.path;
		if (S.status !== 'playing') D.guestFinished(g.stats, S.date, S.status === 'won', score());
		D.store(GUEST_KEY, g);
	}

	function stats() { return S.player ? S.player.stats : D.guestStats(guest().stats, S.date); }

	/* ---------- layout ---------- */

	function build() {
		shell = new D.Shell(root, {
			game: 'ljestve',
			onHelp: showHelp,
			onStats: showStats,
			onLogin: function () { load(); },
			getPlayer: function () { return S.player; },
			boardHints: {
				today: 'Najbolji rezultat danas (koraci plus cijena pomoći).',
				streak: 'Najduži niz dana zaredom.',
				month: 'Najviše riješenih dana ovog mjeseca.'
			}
		});
		el.route = h('p', { class: 'dpig-lj-route' });
		el.ladder = h('div', { class: 'dpig-lj-ladder' });
		el.tip = h('p', { class: 'dpig-lj-tip', text: 'Dodirni slovo koje mijenjaš, izaberi novo slovo, pa Unesi.' });
		el.undo = h('button', { class: 'dpig-pill', type: 'button', onclick: undo, text: 'Vrati korak' });
		el.hint = h('button', { class: 'dpig-pill', type: 'button', onclick: hint, text: 'Pomoć' });
		el.giveup = h('button', { class: 'dpig-pill', type: 'button', onclick: confirmGiveUp, text: 'Odustajem' });
		el.count = h('span', { class: 'dpig-lj-count' });
		el.tools = h('div', { class: 'dpig-lj-tools' }, [el.count, h('span', { class: 'dpig-lj-buttons' }, [el.undo, el.hint, el.giveup])]);
		el.keyboard = h('div', { class: 'dpig-keyboard' });
		KEY_ROWS.forEach(function (row) {
			var r = h('div', { class: 'dpig-krow' });
			row.forEach(function (k) {
				var label = k === 'enter' ? 'UNESI' : k === 'back' ? '⌫' : up(k);
				var b = h('button', { class: 'dpig-key' + (k === 'enter' || k === 'back' ? ' dpig-wide' : ''), type: 'button', 'data-key': k, text: label, 'aria-label': k === 'back' ? 'Vrati slovo' : label });
				b.addEventListener('click', function () { press(k); b.blur(); });
				r.appendChild(b);
			});
			el.keyboard.appendChild(r);
		});
		el.done = h('div', { class: 'dpig-lj-done' });
		root.insertBefore(h('div', { class: 'dpig-lj-body' }, [el.route, el.ladder, el.tip, el.tools, el.done, el.keyboard]), shell.toasts);
		document.addEventListener('keydown', onKey);
	}

	function tileRow(word, cls, changedFrom, label) {
		var t = tiles(word), prev = changedFrom ? tiles(changedFrom) : null;
		var row = h('div', { class: 'dpig-lj-row ' + (cls || '') });
		if (label) row.appendChild(h('span', { class: 'dpig-lj-label', text: label }));
		var box = h('div', { class: 'dpig-lj-tiles' }, [h('span', { class: 'dpig-sr', text: word.toUpperCase() })]);
		t.forEach(function (x, i) {
			box.appendChild(h('span', { class: 'dpig-tile' + (prev && prev[i] !== x ? ' is-changed' : ''), 'aria-hidden': 'true', text: up(x) }));
		});
		row.appendChild(box);
		return row;
	}

	function inputRow() {
		var base = tiles(last());
		var row = h('div', { class: 'dpig-lj-row is-input' });
		row.appendChild(h('span', { class: 'dpig-lj-label', text: 'Tvoj korak' }));
		var box = h('div', { class: 'dpig-lj-tiles', role: 'group', 'aria-label': 'Nova riječ' });
		S.input.forEach(function (x, i) {
			var b = h('button', {
				class: 'dpig-tile' + (i === S.sel ? ' is-selected' : '') + (x !== base[i] ? ' is-changed' : ''),
				type: 'button',
				'aria-label': 'Slovo ' + (i + 1) + ': ' + up(x) + (i === S.sel ? ', izabrano' : ''),
				'aria-pressed': String(i === S.sel),
				text: up(x)
			});
			b.addEventListener('click', function () { S.sel = i; S.lastKey = null; render(); });
			box.appendChild(b);
		});
		row.appendChild(box);
		return row;
	}

	function render() {
		shell.setTitle('Ljestve #' + S.number);
		shell.setStreak(stats().currentStreak);
		shell.renderAccount(S.player);
		el.route.innerHTML = '';
		el.route.appendChild(h('span', {}, ['Od ', h('strong', { text: up(S.start) }), ' do ', h('strong', { text: up(S.target) })]));
		el.route.appendChild(h('span', { class: 'dpig-lj-par', text: 'najkraće ' + S.par + ' ' + stepWord(S.par) }));

		var over = S.status !== 'playing';
		el.ladder.innerHTML = '';
		el.ladder.appendChild(tileRow(S.start, 'is-start', null, 'Start'));
		var prev = S.start;
		S.steps.forEach(function (s, i) {
			var row = tileRow(s.w, (s.h ? 'is-hint' : '') + (s.w === S.target ? ' is-goal' : ''), prev, s.h ? 'pomoć' : String(i + 1));
			el.ladder.appendChild(row);
			prev = s.w;
		});
		if (!over) {
			el.ladder.appendChild(inputRow());
			el.ladder.appendChild(tileRow(S.target, 'is-target', null, 'Cilj'));
		}

		el.tip.hidden = over;
		el.keyboard.hidden = over;
		el.tools.hidden = over;
		el.undo.disabled = !S.steps.length;
		el.hint.textContent = 'Pomoć (+' + Math.pow(2, S.hints + 1) + ')';
		el.count.textContent = S.hints
			? typed() + ' ' + stepWord(typed()) + ', ' + S.hints + ' ' + D.plural(S.hints, 'pomoć', 'pomoći') + ' = ' + score()
			: S.steps.length + ' ' + stepWord(S.steps.length);

		el.done.innerHTML = '';
		if (over) {
			el.done.appendChild(h('p', { class: 'dpig-stamp' + (S.status === 'won' ? '' : ' is-lost'), text: S.status === 'won' ? 'Stigao/la si!' : 'Odustao/la si' }));
			if (S.status === 'won') el.done.appendChild(h('p', { text: resultLine() }));
			if (S.path) {
				el.done.appendChild(h('p', { class: 'dpig-lj-path-title', text: 'Jedan od najkraćih puteva:' }));
				el.done.appendChild(h('p', { class: 'dpig-lj-path', text: S.path.map(up).join(' → ') }));
			}
			el.done.appendChild(h('div', { class: 'dpig-done-buttons' }, [
				h('button', { class: 'dpig-btn dpig-primary', type: 'button', onclick: showStats, text: 'Rezultat' }),
				h('button', { class: 'dpig-btn', type: 'button', onclick: function () { shell.leaderboard('today'); }, text: 'Ljestvica' })
			]));
		}
	}

	function resultLine() {
		var n = S.steps.length;
		var line = n + ' ' + stepWord(n) + ' (najkraće ' + S.par + ').';
		if (S.hints) line = 'Rezultat ' + score() + ': ' + typed() + ' ' + stepWord(typed()) + ' i ' + S.hints + ' ' + D.plural(S.hints, 'pomoć', 'pomoći') + '.';
		else if (n <= S.par) line += ' Savršeno!';
		return line;
	}

	function resetInput() {
		S.input = tiles(last());
		S.sel = 0;
		S.lastKey = null;
	}

	/* ---------- input ---------- */

	function press(k) {
		if (S.status !== 'playing' || S.busy || shell.modal.hidden === false) return;
		if (k === 'enter') return submit();
		if (k === 'back') { S.input[S.sel] = tiles(last())[S.sel]; S.lastKey = null; return render(); }
		// Typing L then J (or N then J, D then Ž) makes one letter, like on the keyboard in Riječ.
		var cur = S.input[S.sel];
		if (S.lastKey && ((cur === 'l' || cur === 'n') && k === 'j' || cur === 'd' && k === 'ž')) k = cur + k;
		S.input[S.sel] = k;
		S.lastKey = k.length === 1 ? k : null;
		render();
	}

	function onKey(e) {
		if (e.ctrlKey || e.metaKey || e.altKey) return;
		if (shell && !shell.modal.hidden) return;
		var tag = (e.target && e.target.tagName) || '';
		if (tag === 'INPUT' || tag === 'TEXTAREA') return;
		var key = e.key;
		if (key === 'Enter') { e.preventDefault(); press('enter'); }
		else if (key === 'Backspace') { e.preventDefault(); press('back'); }
		else if (key === 'ArrowLeft') { S.sel = Math.max(0, S.sel - 1); S.lastKey = null; render(); }
		else if (key === 'ArrowRight') { S.sel = Math.min(S.input.length - 1, S.sel + 1); S.lastKey = null; render(); }
		else if (key.length === 1 && LETTERS.indexOf(key.toLowerCase()) >= 0) press(key.toLowerCase());
	}

	function submit() {
		var base = tiles(last());
		var diff = S.input.filter(function (x, i) { return x !== base[i]; }).length;
		if (diff === 0) return shell.toast('Promijeni jedno slovo.');
		if (diff > 1) return shell.toast('Samo jedno slovo po koraku.');
		var word = S.input.join('');
		if (word === S.start || S.steps.some(function (s) { return s.w === word; })) return shell.toast('Ta riječ je već na ljestvama.');
		S.busy = true;
		D.api('ljestve/step', { date: S.date, word: word, from: last() }).then(function (data) {
			S.steps.push(data.step);
			if (data.status === 'won') finish('won', data.path, data.player);
			resetInput();
			saveGuest();
			render();
			scrollToInput();
		}).catch(function (e) {
			if (e.code === 'dpig_new_day') { load().then(function () { shell.toast('Stigle su nove ljestve!', 2500); }); return; }
			shell.toast(e.message, 2400);
		}).then(function () { S.busy = false; });
	}

	function undo() {
		if (S.busy || S.status !== 'playing' || !S.steps.length) return;
		S.busy = true;
		(S.player ? D.api('ljestve/undo', { date: S.date }) : Promise.resolve()).then(function () {
			S.steps.pop();
			resetInput();
			saveGuest();
			render();
		}).catch(function (e) { shell.toast(e.message); }).then(function () { S.busy = false; });
	}

	function hint() {
		if (S.busy || S.status !== 'playing') return;
		S.busy = true;
		D.api('ljestve/hint', { date: S.date, from: last(), used: [S.start].concat(S.steps.map(function (s) { return s.w; })) }).then(function (data) {
			S.hints++;
			S.steps.push(data.step);
			resetInput();
			saveGuest();
			render();
			scrollToInput();
		}).catch(function (e) {
			if (e.code === 'dpig_new_day') { load(); return; }
			shell.toast(e.message, 2600);
		}).then(function () { S.busy = false; });
	}

	function confirmGiveUp() {
		var yes = h('button', { class: 'dpig-btn dpig-primary', type: 'button', text: 'Da, pokaži put' });
		var no = h('button', { class: 'dpig-btn', type: 'button', text: 'Ne, igram dalje', onclick: function () { shell.close(); } });
		yes.addEventListener('click', function () {
			shell.close();
			D.api('ljestve/giveup', { date: S.date }).then(function (data) {
				finish('lost', data.path, data.player);
				saveGuest();
				render();
			}).catch(function (e) { shell.toast(e.message); });
		});
		shell.open('Odustaješ?', h('div', {}, [
			h('p', { text: 'Vidjet ćeš najkraći put, ali se niz prekida.' }),
			h('div', { class: 'dpig-done-buttons' }, [yes, no])
		]));
	}

	function finish(status, path, player) {
		S.status = status;
		S.path = path || null;
		if (player) S.player = player;
		setTimeout(showStats, status === 'won' ? 900 : 300);
	}

	/* Keep the row being edited and the keyboard on screen (phones scroll the page, the ladder grows). */
	function scrollToInput() {
		if (S.status !== 'playing') {
			if (el.done.scrollIntoView) el.done.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
			return;
		}
		var row = el.ladder.querySelector('.is-input');
		if (!row) return;
		var top = row.getBoundingClientRect().top;
		var bottom = el.keyboard.getBoundingClientRect().bottom + 10;
		var by = bottom - window.innerHeight;
		if (by > 0) window.scrollBy(0, Math.min(by, top - 8));
	}

	/* ---------- windows ---------- */

	function showHelp() {
		var ex = h('div', { class: 'dpig-lj-ladder dpig-lj-example' });
		var prev = null;
		['mali', 'mala', 'pala', 'pila', 'pita'].forEach(function (w, i) {
			ex.appendChild(tileRow(w, i === 0 ? 'is-start' : (i === 4 ? 'is-goal' : ''), prev, i === 0 ? 'Start' : String(i)));
			prev = w;
		});
		shell.open('Kako se igra', h('div', {}, [
			h('p', { text: 'Dođi od prve riječi do cilja mijenjajući po jedno slovo. Svaki korak mora biti prava riječ, a slova ostaju na svom mjestu.' }),
			ex,
			h('p', { text: 'Dodirni slovo koje mijenjaš, izaberi novo na tastaturi i klikni Unesi. LJ, NJ i DŽ su jedno slovo. Svi oblici riječi vrijede (kuća, kuće, kući…).' }),
			h('p', { text: 'Što manje koraka, to bolje. Pomoć ti da sljedeću riječ na najkraćem putu, ali košta sve više: prva 2 koraka, druga 4, treća 8… Nove ljestve stižu svaki dan u ponoć.' })
		]));
	}

	function showStats() {
		var st = stats();
		var body = h('div', {});
		if (S.status === 'won') body.appendChild(h('p', { class: 'dpig-note', text: up(S.start) + ' → ' + up(S.target) + ': ' + resultLine() }));
		body.appendChild(D.nums([[st.played, 'Odigrano'], [st.won, 'Riješeno'], [st.currentStreak, 'Trenutni niz'], [st.maxStreak, 'Najduži niz']]));
		if (st.best) body.appendChild(h('p', { class: 'dpig-small-note', text: 'Najbolji rezultat: ' + st.best + '. Prosjek: ' + st.average + '.' }));
		if (S.status !== 'playing') {
			var c = h('div', { class: 'dpig-clock' });
			body.appendChild(h('div', { class: 'dpig-after' }, [
				h('div', {}, [h('div', { class: 'dpig-label', text: 'Nove ljestve za' }), c]),
				S.status === 'won' ? h('button', { class: 'dpig-btn dpig-primary', type: 'button', onclick: shareResult, text: 'Podijeli rezultat' }) : null
			]));
			D.countdown(c, S.nextIn - (Date.now() - S.loadedAt) / 1000);
		}
		body.appendChild(h('button', { class: 'dpig-btn', type: 'button', onclick: function () { shell.leaderboard('today'); }, text: 'Ljestvica' }));
		var box = shell.accountBox(S.player);
		if (box) body.appendChild(box);
		shell.open('Statistika', body);
	}

	function shareResult() {
		var squares = S.steps.map(function (s) { return s.h ? '🟧' : '🟦'; }).join('');
		D.share(shell, 'Ljestve #' + S.number + '\n' + up(S.start) + ' → ' + up(S.target) + ': ' + resultLine() + '\n' + squares + '\n' + (D.CFG.pageUrl || location.href));
	}

	/* ---------- start ---------- */

	function load() {
		return D.api('ljestve/state').then(function (data) {
			S.date = data.date;
			S.number = data.number;
			S.start = data.start;
			S.target = data.target;
			S.par = data.par;
			S.nextIn = data.nextIn;
			S.loadedAt = Date.now();
			S.player = data.player;
			var g = S.player ? (data.game || {}) : guest();
			S.steps = g.steps || [];
			S.hints = g.hints || 0;
			S.status = g.status || 'playing';
			S.path = g.path || null;
			resetInput();
			render();
			shell.fitOnPhone(el.keyboard);
			if (!D.store('dpig_lj_seen_help')) { D.store('dpig_lj_seen_help', 1); showHelp(); }
		}).catch(function (e) {
			root.innerHTML = '';
			root.appendChild(h('p', { class: 'dpig-error', text: 'Igra se nije mogla učitati: ' + e.message }));
		});
	}

	build();
	load();
})();
