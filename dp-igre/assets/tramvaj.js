/* Tramvaj: provuci liniju kroz sva polja, stanicama redom. */
(function () {
	'use strict';

	var D = window.DPIG;
	var h = D.h;
	var root = document.getElementById('dpig-root');
	if (!root || !D) return;

	var GUEST_KEY = 'dpig_tv_v1';
	var SVG = 'http://www.w3.org/2000/svg';

	var S = {
		date: null, number: 0, n: 7, route: {}, nextIn: 0, player: null,
		puzzle: null, path: [], status: 'idle', t0: 0, ms: 0, busy: false
	};
	var el = {};
	var shell;
	var P = {}; // Puzzle helpers: stopAt, walls.
	var ticker = null;
	var dragging = false;

	/* ---------- guest storage ---------- */

	function guest() {
		var g = D.store(GUEST_KEY) || {};
		if (g.date !== S.date) { g.date = S.date; g.path = []; g.status = 'idle'; g.t0 = 0; g.ms = 0; }
		g.stats = g.stats || {};
		return g;
	}

	function saveGuest() {
		if (S.player) return;
		var g = guest();
		g.path = S.path;
		g.status = S.status;
		g.t0 = S.t0;
		g.ms = S.ms;
		if (S.status === 'won') D.guestFinished(g.stats, S.date, true, S.ms);
		D.store(GUEST_KEY, g);
	}

	function stats() {
		return S.player ? S.player.stats : D.guestStats(guest().stats, S.date);
	}

	function clock(ms) {
		var s = Math.max(0, Math.round(ms / 1000));
		return Math.floor(s / 60) + ':' + (s % 60 < 10 ? '0' : '') + (s % 60);
	}

	/* ---------- layout ---------- */

	function build() {
		shell = new D.Shell(root, {
			game: 'tramvaj',
			onHelp: showHelp,
			onStats: showStats,
			onLogin: function () { load(); },
			getPlayer: function () { return S.player; },
			boardHints: {
				today: 'Najbrži danas. Vrijeme ide od klika na Kreni.',
				streak: 'Najduži niz dana zaredom.',
				month: 'Najviše odvoženih dana ovog mjeseca.'
			}
		});
		el.timer = h('span', { class: 'dpig-tv-timer', role: 'timer', 'aria-label': 'Vrijeme' });
		shell.extra.appendChild(el.timer);
		el.route = h('p', { class: 'dpig-tv-route' });
		el.board = h('div', {
			class: 'dpig-tv-board', tabindex: '0', role: 'application',
			'aria-label': 'Tabla. Strelicama vodiš liniju, Backspace vraća korak.'
		});
		el.start = h('div', { class: 'dpig-tv-start' });
		el.wrap = h('div', { class: 'dpig-tv-wrap' }, [el.board, el.start]);
		el.progress = h('span', { class: 'dpig-tv-progress' });
		el.undo = h('button', { class: 'dpig-pill dpig-tv-small', type: 'button', onclick: undo, text: 'Poništi' });
		el.clear = h('button', { class: 'dpig-pill dpig-tv-small', type: 'button', onclick: clearPath, text: 'Obriši' });
		el.tools = h('div', { class: 'dpig-tv-tools' }, [el.progress, h('span', { class: 'dpig-tv-buttons' }, [el.undo, el.clear])]);
		el.done = h('div', { class: 'dpig-tv-done' });
		root.insertBefore(h('div', { class: 'dpig-tv-body' }, [el.route, el.wrap, el.tools, el.done]), shell.toasts);

		el.board.addEventListener('pointerdown', onDown);
		el.board.addEventListener('pointermove', onMove);
		el.board.addEventListener('pointerup', onUp);
		el.board.addEventListener('pointercancel', onUp);
		el.board.addEventListener('keydown', onKey);
	}

	function preparePuzzle() {
		var pz = S.puzzle;
		P.stopAt = {};
		pz.s.forEach(function (cell, k) { P.stopAt[cell] = k; });
		P.walls = {};
		pz.w.forEach(function (w) { P.walls[Math.min(w[0], w[1]) + ',' + Math.max(w[0], w[1])] = true; });
		P.last = pz.s[pz.s.length - 1];
	}

	/* Draw the grid: cells, stops, walls and the line (an SVG on top of the cells). */
	function drawBoard() {
		var n = S.n;
		el.board.style.setProperty('--n', n);
		el.board.innerHTML = '';
		var inPath = {};
		S.path.forEach(function (c, i) { inPath[c] = i + 1; });
		var nextStop = stopsVisited();
		for (var i = 0; i < n * n; i++) {
			var cell = h('div', { class: 'dpig-tv-cell' + (inPath[i] ? ' is-on' : '') });
			if (S.puzzle && P.stopAt[i] !== undefined) {
				var k = P.stopAt[i];
				var cls = 'dpig-tv-stop' + (inPath[i] ? ' is-done' : '') + (k === nextStop && S.status === 'playing' ? ' is-next' : '');
				cell.appendChild(h('span', { class: cls, text: String(k + 1) }));
			}
			el.board.appendChild(cell);
		}
		if (!S.puzzle) return;
		var svg = document.createElementNS(SVG, 'svg');
		svg.setAttribute('viewBox', '0 0 ' + n + ' ' + n);
		svg.setAttribute('class', 'dpig-tv-svg');
		svg.setAttribute('aria-hidden', 'true');
		if (S.path.length > 1) {
			var line = document.createElementNS(SVG, 'polyline');
			line.setAttribute('points', S.path.map(function (c) { return (c % n + 0.5) + ',' + (Math.floor(c / n) + 0.5); }).join(' '));
			line.setAttribute('class', 'dpig-tv-line');
			svg.appendChild(line);
		}
		S.puzzle.w.forEach(function (w) {
			var a = Math.min(w[0], w[1]), b = Math.max(w[0], w[1]);
			var r = Math.floor(a / n), c = a % n;
			var seg = document.createElementNS(SVG, 'line');
			if (b === a + 1) { seg.setAttribute('x1', c + 1); seg.setAttribute('x2', c + 1); seg.setAttribute('y1', r); seg.setAttribute('y2', r + 1); }
			else { seg.setAttribute('x1', c); seg.setAttribute('x2', c + 1); seg.setAttribute('y1', r + 1); seg.setAttribute('y2', r + 1); }
			seg.setAttribute('class', 'dpig-tv-wall');
			svg.appendChild(seg);
		});
		if (S.path.length) {
			var head = S.path[S.path.length - 1];
			if (P.stopAt[head] === undefined) {
				var dot = document.createElementNS(SVG, 'circle');
				dot.setAttribute('cx', head % n + 0.5);
				dot.setAttribute('cy', Math.floor(head / n) + 0.5);
				dot.setAttribute('r', 0.2);
				dot.setAttribute('class', 'dpig-tv-head');
				svg.appendChild(dot);
			}
		}
		el.board.appendChild(svg);
	}

	function render() {
		shell.setTitle('Tramvaj #' + S.number);
		shell.setStreak(stats().currentStreak);
		shell.renderAccount(S.player);
		el.route.innerHTML = '';
		el.route.appendChild(h('span', { class: 'dpig-tv-line-no', text: String(S.number % 6 + 1) }));
		el.route.appendChild(document.createTextNode((S.route.from || '') + ' – ' + (S.route.to || '')));

		drawBoard();
		var started = S.status !== 'idle';
		el.start.hidden = started;
		el.board.classList.toggle('is-idle', !started);
		el.tools.hidden = S.status !== 'playing';
		if (S.puzzle) {
			var total = S.puzzle.s.length;
			el.progress.textContent = 'Stanica ' + Math.min(stopsVisited(), total) + ' od ' + total + ', polja ' + S.path.length + ' od ' + S.n * S.n;
		}
		renderTimer();

		el.start.innerHTML = '';
		if (!started) {
			el.start.appendChild(h('div', { class: 'dpig-tv-card' }, [
				h('p', { class: 'dpig-tv-card-title', text: 'Današnja linija' }),
				h('p', { class: 'dpig-tv-card-route', text: (S.route.from || '') + ' – ' + (S.route.to || '') }),
				h('button', { class: 'dpig-btn dpig-primary dpig-tv-go', type: 'button', onclick: start, text: 'Kreni!' }),
				h('p', { class: 'dpig-label', text: 'Vrijeme počinje kad klikneš.' })
			]));
		}

		el.done.innerHTML = '';
		if (S.status === 'won') {
			el.done.appendChild(h('p', { class: 'dpig-stamp', text: 'Stigli ste!' }));
			el.done.appendChild(h('p', {}, ['Na stanici ', h('strong', { text: S.route.to || '' }), ' za ', h('strong', { text: clock(S.ms) }), '.']));
			el.done.appendChild(h('div', { class: 'dpig-done-buttons' }, [
				h('button', { class: 'dpig-btn dpig-primary', type: 'button', onclick: showStats, text: 'Rezultat' }),
				h('button', { class: 'dpig-btn', type: 'button', onclick: function () { shell.leaderboard('today'); }, text: 'Ljestvica' })
			]));
		}
	}

	function renderTimer() {
		if (S.status === 'idle') { el.timer.textContent = ''; return; }
		el.timer.textContent = clock(S.status === 'won' ? S.ms : Date.now() - S.t0);
	}

	function startTicker() {
		stopTicker();
		ticker = setInterval(renderTimer, 500);
	}

	function stopTicker() { if (ticker) clearInterval(ticker); ticker = null; }

	/* ---------- rules ---------- */

	function stopsVisited() {
		var k = 0;
		S.path.forEach(function (c) { if (P.stopAt && P.stopAt[c] !== undefined) k++; });
		return k;
	}

	function adjacent(a, b) {
		var n = S.n;
		var dr = Math.abs(Math.floor(a / n) - Math.floor(b / n)), dc = Math.abs(a % n - b % n);
		return dr + dc === 1 && !P.walls[Math.min(a, b) + ',' + Math.max(a, b)];
	}

	function canEnter(cell) {
		if (!S.path.length) return cell === S.puzzle.s[0];
		var head = S.path[S.path.length - 1];
		if (S.path.indexOf(cell) >= 0 || !adjacent(head, cell)) return false;
		if (head === P.last) return false; // The line ends at the last stop.
		var k = P.stopAt[cell];
		return k === undefined || k === stopsVisited();
	}

	/* Move the head towards a cell, one step at a time (fast swipes skip cells). */
	function extendTowards(cell) {
		var n = S.n, guard = 0, changed = false;
		while (S.path.length && guard++ < 2 * n) {
			var head = S.path[S.path.length - 1];
			if (head === cell) break;
			var prev = S.path[S.path.length - 2];
			if (cell === prev) { S.path.pop(); changed = true; break; }
			var i = S.path.indexOf(cell);
			if (i >= 0) { S.path.length = i + 1; changed = true; break; }
			var dr = Math.floor(cell / n) - Math.floor(head / n), dc = cell % n - head % n;
			var steps = [];
			if (dr) steps.push(head + (dr > 0 ? n : -n));
			if (dc) steps.push(head + (dc > 0 ? 1 : -1));
			if (Math.abs(dc) > Math.abs(dr)) steps.reverse();
			var next = steps.filter(canEnter)[0];
			if (next === undefined) break;
			S.path.push(next);
			changed = true;
			if (P.stopAt[next] !== undefined) announceStop(next);
		}
		return changed;
	}

	function announceStop(cell) {
		var k = P.stopAt[cell];
		if (cell === P.last && S.path.length < S.n * S.n) shell.toast('Posljednja stanica, ali ostala su prazna polja.', 2200);
		else if (k > 0 && cell !== P.last) shell.toast('Stanica ' + (k + 1), 900);
	}

	function changed() {
		saveGuest();
		render();
		if (S.path.length === S.n * S.n && S.path[S.path.length - 1] === P.last) solved();
	}

	/* ---------- input ---------- */

	function cellAt(e) {
		var r = el.board.getBoundingClientRect();
		var x = Math.floor((e.clientX - r.left) / r.width * S.n);
		var y = Math.floor((e.clientY - r.top) / r.height * S.n);
		if (x < 0 || y < 0 || x >= S.n || y >= S.n) return -1;
		return y * S.n + x;
	}

	function onDown(e) {
		if (S.status !== 'playing') return;
		var cell = cellAt(e);
		if (cell < 0) return;
		var i = S.path.indexOf(cell);
		if (i >= 0) {
			S.path.length = i + 1;
		} else if (!S.path.length && cell === S.puzzle.s[0]) {
			S.path.push(cell);
		} else if (!S.path.length) {
			shell.toast('Kreni od stanice 1.', 1400);
			return;
		} else if (!extendTowards(cell)) {
			return;
		}
		dragging = true;
		el.board.setPointerCapture(e.pointerId);
		e.preventDefault();
		changed();
	}

	function onMove(e) {
		if (!dragging || S.status !== 'playing') return;
		var cell = cellAt(e);
		if (cell < 0 || cell === S.path[S.path.length - 1]) return;
		if (extendTowards(cell)) changed();
	}

	function onUp() { dragging = false; }

	function onKey(e) {
		if (S.status !== 'playing') return;
		var moves = { ArrowUp: -S.n, ArrowDown: S.n, ArrowLeft: -1, ArrowRight: 1 };
		if (e.key === 'Backspace' || e.key === 'Delete') { e.preventDefault(); undo(); return; }
		if (!(e.key in moves)) return;
		e.preventDefault();
		if (!S.path.length) { S.path.push(S.puzzle.s[0]); changed(); return; }
		var head = S.path[S.path.length - 1];
		var next = head + moves[e.key];
		if ((e.key === 'ArrowLeft' || e.key === 'ArrowRight') && Math.floor(next / S.n) !== Math.floor(head / S.n)) return;
		if (next < 0 || next >= S.n * S.n) return;
		if (next === S.path[S.path.length - 2]) { S.path.pop(); changed(); return; }
		if (canEnter(next)) {
			S.path.push(next);
			if (P.stopAt[next] !== undefined) announceStop(next);
			changed();
		}
	}

	function undo() {
		if (S.status !== 'playing' || S.path.length <= 1) return;
		S.path.pop();
		changed();
	}

	function clearPath() {
		if (S.status !== 'playing') return;
		S.path = S.path.length ? [S.puzzle.s[0]] : [];
		changed();
	}

	/* ---------- start and finish ---------- */

	function start() {
		if (S.busy) return;
		S.busy = true;
		D.api('tramvaj/start', { date: S.date }).then(function (data) {
			S.puzzle = data.puzzle;
			S.n = S.puzzle.n;
			preparePuzzle();
			S.status = 'playing';
			S.t0 = Date.now();
			S.path = [S.puzzle.s[0]];
			saveGuest();
			render();
			startTicker();
			el.board.focus({ preventScroll: true });
		}).catch(function (e) { shell.toast(e.message, 2600); }).then(function () { S.busy = false; });
	}

	function solved() {
		S.status = 'won';
		S.ms = Date.now() - S.t0;
		stopTicker();
		render();
		D.api('tramvaj/solve', { date: S.date, path: S.path, ms: S.ms }).then(function (data) {
			S.ms = data.ms;
			if (data.player) S.player = data.player;
			saveGuest();
			render();
			setTimeout(showStats, 700);
		}).catch(function (e) {
			shell.toast(e.message, 2600);
			S.status = 'playing';
			startTicker();
			render();
		});
	}

	/* ---------- windows ---------- */

	function showHelp() {
		shell.open('Kako se vozi', h('div', {}, [
			h('p', { text: 'Provuci liniju tramvaja od stanice 1 do posljednje stanice.' }),
			h('ul', { class: 'dpig-tv-rules' }, [
				h('li', { text: 'Linija mora proći kroz sva polja, kroz svako samo jednom.' }),
				h('li', { text: 'Stanice obilazi redom: 1, 2, 3…' }),
				h('li', { text: 'Debele crne crte su zidovi. Kroz njih se ne može.' })
			]),
			h('p', { text: 'Vuci prstom ili mišem. Vrati se unazad da obrišeš dio linije. Na tastaturi: strelice, a Backspace vraća korak.' }),
			h('p', { text: 'Vrijeme počinje kad klikneš Kreni. Nova linija stiže svaki dan u ponoć. Neki dani imaju veću tablu.' })
		]));
	}

	function showStats() {
		var st = stats();
		var body = h('div', {});
		if (S.status === 'won') {
			body.appendChild(h('p', { class: 'dpig-note', text: 'Stigli ste na stanicu ' + (S.route.to || '') + ' za ' + clock(S.ms) + '!' }));
		}
		body.appendChild(D.nums([
			[st.played, 'Odvoženo'],
			[st.best ? clock(st.best) : '–', 'Najbrže'],
			[st.currentStreak, 'Trenutni niz'],
			[st.maxStreak, 'Najduži niz']
		]));
		if (st.average) body.appendChild(h('p', { class: 'dpig-small-note', text: 'Prosječno vrijeme: ' + clock(st.average) + '.' }));
		if (S.status === 'won') {
			var c = h('div', { class: 'dpig-clock' });
			body.appendChild(h('div', { class: 'dpig-after' }, [
				h('div', {}, [h('div', { class: 'dpig-label', text: 'Sljedeća linija za' }), c]),
				h('button', { class: 'dpig-btn dpig-primary', type: 'button', onclick: shareResult, text: 'Podijeli rezultat' })
			]));
			D.countdown(c, S.nextIn - (Date.now() - S.loadedAt) / 1000);
		}
		body.appendChild(h('button', { class: 'dpig-btn', type: 'button', onclick: function () { shell.leaderboard('today'); }, text: 'Ljestvica' }));
		var box = shell.accountBox(S.player);
		if (box) body.appendChild(box);
		shell.open('Statistika', body);
	}

	function shareResult() {
		D.share(shell, 'Tramvaj #' + S.number + ' 🚋\n' + (S.route.from || '') + ' – ' + (S.route.to || '') + ' za ' + clock(S.ms) + '\n' + (D.CFG.pageUrl || location.href));
	}

	/* ---------- load ---------- */

	function load() {
		return D.api('tramvaj/state').then(function (data) {
			S.date = data.date;
			S.number = data.number;
			S.n = data.n;
			S.route = data.route;
			S.nextIn = data.nextIn;
			S.loadedAt = Date.now();
			S.player = data.player;
			S.puzzle = data.puzzle;
			var g = data.game;
			if (S.player && g) {
				S.status = g.status === 'won' ? 'won' : 'playing';
				S.ms = g.ms || 0;
				S.t0 = Date.now() - (g.elapsed || 0);
				S.path = g.path || [];
			} else if (!S.player) {
				var gs = guest();
				S.status = gs.status || 'idle';
				S.path = gs.path || [];
				S.t0 = gs.t0 || 0;
				S.ms = gs.ms || 0;
			} else {
				S.status = 'idle';
				S.path = [];
			}
			if (S.status !== 'idle' && !S.puzzle) {
				// A guest who already started: fetch the puzzle again (the clock keeps running).
				return D.api('tramvaj/start', { date: S.date }).then(function (d) { S.puzzle = d.puzzle; });
			}
		}).then(function () {
			if (S.puzzle) {
				S.n = S.puzzle.n;
				preparePuzzle();
				if (S.status === 'playing' && !S.path.length) S.path = [S.puzzle.s[0]];
			}
			render();
			if (S.status === 'playing') startTicker();
			shell.fitOnPhone(S.status === 'playing' ? el.tools : el.wrap);
			if (!D.store('dpig_tv_seen_help')) { D.store('dpig_tv_seen_help', 1); showHelp(); }
		}).catch(function (e) {
			root.innerHTML = '';
			root.appendChild(h('p', { class: 'dpig-error', text: 'Igra se nije mogla učitati: ' + e.message }));
		});
	}

	build();
	load();
})();
