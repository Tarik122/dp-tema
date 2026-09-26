/* Kontekst: pogodi riječ po značenju. */
(function () {
	'use strict';

	var D = window.DPIG;
	var h = D.h;
	var root = document.getElementById('dpig-root');
	if (!root || !D) return;

	var GUEST_KEY = 'dpig_kx_v1';
	var HOT = 300;
	var WARM = 1500;

	var S = { date: null, number: 0, total: 25000, nextIn: 0, player: null, guesses: [], status: 'playing', answer: null, last: null, busy: false };
	var el = {};
	var shell;

	/* ---------- guest storage ---------- */

	function guest() {
		var g = D.store(GUEST_KEY) || {};
		if (g.date !== S.date) { g.date = S.date; g.guesses = []; g.status = 'playing'; g.answer = null; }
		g.stats = g.stats || {};
		return g;
	}

	function saveGuest() {
		if (S.player) return;
		var g = guest();
		g.guesses = S.guesses;
		g.status = S.status;
		g.answer = S.answer;
		if (S.status !== 'playing') D.guestFinished(g.stats, S.date, S.status === 'won', S.guesses.length);
		D.store(GUEST_KEY, g);
	}

	function stats() {
		return S.player ? S.player.stats : D.guestStats(guest().stats, S.date);
	}

	/* ---------- layout ---------- */

	function build() {
		shell = new D.Shell(root, {
			game: 'kontekst',
			onHelp: showHelp,
			onStats: showStats,
			onLogin: function () { load(); },
			getPlayer: function () { return S.player; },
			boardHints: {
				today: 'Najmanje pokušaja danas.',
				streak: 'Najduži niz dana zaredom (odustajanje prekida niz).',
				month: 'Najviše pogođenih dana ovog mjeseca.'
			}
		});
		el.input = h('input', {
			id: 'dpig-kx-input', class: 'dpig-kx-input', type: 'text', autocomplete: 'off', autocapitalize: 'none',
			autocorrect: 'off', spellcheck: 'false', enterkeyhint: 'send', maxlength: '40', placeholder: 'upiši riječ'
		});
		el.send = h('button', { class: 'dpig-kx-send', type: 'submit', text: 'Pogodi' });
		el.form = h('form', { class: 'dpig-kx-form', onsubmit: function (e) { e.preventDefault(); submit(); } }, [
			h('label', { class: 'dpig-sr', for: 'dpig-kx-input', text: 'Tvoja riječ' }), el.input, el.send
		]);
		el.count = h('span', { class: 'dpig-kx-count' });
		el.hint = h('button', { class: 'dpig-pill', type: 'button', onclick: hint, text: 'Pomoć' });
		el.giveup = h('button', { class: 'dpig-pill', type: 'button', onclick: confirmGiveUp, text: 'Odustajem' });
		el.tools = h('div', { class: 'dpig-kx-tools' }, [el.count, h('span', { class: 'dpig-kx-buttons' }, [el.hint, el.giveup])]);
		el.done = h('div', { class: 'dpig-kx-done' });
		el.last = h('ol', { class: 'dpig-kx-list dpig-kx-last', 'aria-label': 'Zadnji pokušaj' });
		el.list = h('ol', { class: 'dpig-kx-list', 'aria-label': 'Svi pokušaji, od najbližeg' });
		el.empty = h('p', { class: 'dpig-kx-empty', text: 'Upiši bilo koju riječ. Igra ti kaže koliko je blizu tajnoj riječi po značenju.' });
		root.insertBefore(h('div', { class: 'dpig-kx-body' }, [el.form, el.tools, el.done, el.last, el.empty, el.list]), shell.toasts);
	}

	function band(rank) {
		return rank <= HOT ? 'hot' : rank <= WARM ? 'warm' : 'cold';
	}

	function row(g, highlight) {
		var pct = g.r === 1 ? 100 : Math.max(4, Math.round((1 - Math.log(g.r) / Math.log(S.total)) * 100));
		var li = h('li', { class: 'dpig-kx-row is-' + band(g.r) + (highlight ? ' is-new' : '') + (g.r === 1 ? ' is-win' : '') }, [
			h('span', { class: 'dpig-kx-bar', style: 'width:' + pct + '%', 'aria-hidden': 'true' }),
			h('span', { class: 'dpig-kx-word' }, [g.w, g.h ? h('span', { class: 'dpig-kx-tag', text: 'pomoć' }) : null]),
			h('span', { class: 'dpig-kx-rank', text: g.r === 1 ? 'Pogođeno!' : '#' + g.r.toLocaleString('bs') })
		]);
		return li;
	}

	function render() {
		shell.setTitle('Kontekst #' + S.number);
		shell.setStreak(stats().currentStreak);
		shell.renderAccount(S.player);

		var n = S.guesses.length;
		var hints = S.guesses.filter(function (g) { return g.h; }).length;
		el.count.textContent = n + ' ' + D.plural(n, 'pokušaj', 'pokušaja') + (hints ? ', ' + hints + ' ' + D.plural(hints, 'pomoć', 'pomoći') : '');

		var over = S.status !== 'playing';
		el.form.hidden = over;
		el.hint.hidden = over;
		el.giveup.hidden = over || !n;
		el.empty.hidden = !!n;

		el.done.innerHTML = '';
		if (over) {
			el.done.appendChild(h('p', { class: 'dpig-stamp' + (S.status === 'won' ? '' : ' is-lost'), text: S.status === 'won' ? 'Pogođeno!' : 'Odustao/la si' }));
			el.done.appendChild(h('p', {}, ['Riječ je bila ', h('strong', { text: (S.answer || '').toUpperCase() }), '.']));
			el.done.appendChild(h('div', { class: 'dpig-done-buttons' }, [
				h('button', { class: 'dpig-btn dpig-primary', type: 'button', onclick: showStats, text: 'Rezultat' }),
				h('button', { class: 'dpig-btn', type: 'button', onclick: showTop, text: 'Najbliže riječi' })
			]));
		}

		el.last.innerHTML = '';
		if (S.last && !over && n > 1) el.last.appendChild(row(S.last, true));

		el.list.innerHTML = '';
		S.guesses.slice().sort(function (a, b) { return a.r - b.r; }).forEach(function (g) {
			el.list.appendChild(row(g, S.last && g.w === S.last.w));
		});
	}

	/* ---------- playing ---------- */

	function best() {
		return S.guesses.reduce(function (m, g) { return m ? Math.min(m, g.r) : g.r; }, 0);
	}

	function add(g) {
		S.guesses.push(g);
		S.last = g;
	}

	function submit() {
		if (S.busy || S.status !== 'playing') return;
		var word = el.input.value.trim().toLowerCase();
		if (!word) return;
		var dupe = S.guesses.filter(function (g) { return g.w === word; })[0];
		if (dupe) { S.last = dupe; render(); shell.toast('Već si probao/la „' + word + '“.'); el.input.select(); return; }
		S.busy = true;
		el.send.disabled = true;
		D.api('kontekst/guess', { date: S.date, word: word }).then(function (data) {
			el.input.value = '';
			var g = data.guess;
			var same = S.guesses.filter(function (x) { return x.w === g.w; })[0];
			if (same || data.repeat) {
				S.last = same || g;
				shell.toast(g.w !== word ? '„' + word + '“ je isto što i „' + g.w + '“, već si je probao/la.' : 'Već si probao/la „' + word + '“.');
			} else {
				add(g);
				if (g.w !== word) shell.toast('„' + word + '“ se računa kao „' + g.w + '“.', 2200);
			}
			if (data.status === 'won') finish('won', data.answer, data.player);
			saveGuest();
			render();
		}).catch(function (e) {
			if (e.code === 'dpig_new_day') { shell.toast(e.message, 4000); return; }
			shell.toast(e.message, 2600);
			el.input.select();
		}).then(function () {
			S.busy = false;
			el.send.disabled = false;
			if (S.status === 'playing' && window.innerWidth >= 600) el.input.focus();
		});
	}

	function hint() {
		if (S.busy || S.status !== 'playing') return;
		S.busy = true;
		D.api('kontekst/hint', { date: S.date, best: best() }).then(function (data) {
			var g = data.guess;
			if (S.guesses.some(function (x) { return x.w === g.w; })) { shell.toast('Probaj nešto blizu „' + g.w + '“.'); return; }
			add(g);
			saveGuest();
			render();
			shell.toast('Pomoć: „' + g.w + '“ je #' + g.r + '.', 2400);
		}).catch(function (e) { shell.toast(e.message, 2600); }).then(function () { S.busy = false; });
	}

	function confirmGiveUp() {
		var yes = h('button', { class: 'dpig-btn dpig-primary', type: 'button', text: 'Da, pokaži riječ' });
		var no = h('button', { class: 'dpig-btn', type: 'button', text: 'Ne, igram dalje', onclick: function () { shell.close(); } });
		yes.addEventListener('click', function () {
			shell.close();
			D.api('kontekst/giveup', { date: S.date }).then(function (data) {
				finish('lost', data.answer, data.player);
				saveGuest();
				render();
			}).catch(function (e) { shell.toast(e.message); });
		});
		shell.open('Odustaješ?', h('div', {}, [
			h('p', { text: 'Vidjet ćeš današnju riječ i najbliže riječi, ali se niz prekida.' }),
			h('div', { class: 'dpig-done-buttons' }, [yes, no])
		]));
	}

	function finish(status, answer, player) {
		S.status = status;
		S.answer = answer;
		S.last = null;
		if (player) S.player = player;
		setTimeout(showStats, status === 'won' ? 900 : 300);
	}

	/* ---------- windows ---------- */

	function showHelp() {
		var ex = [
			{ w: 'gimnazija', r: 11 }, { w: 'torba', r: 1099 }, { w: 'krompir', r: 10363 }
		];
		var list = h('ol', { class: 'dpig-kx-list dpig-kx-example' }, ex.map(function (g) { return row(g, false); }));
		shell.open('Kako se igra', h('div', {}, [
			h('p', { text: 'Pogodi tajnu riječ. Možeš upisati bilo koju riječ, a igra ti kaže koliko je blizu po značenju.' }),
			h('p', { text: 'Sve riječi su poredane: tajna riječ je #1, riječ koja joj je najbliža po značenju #2, i tako dalje do oko ' + Math.round(S.total / 1000) + ' hiljada riječi. Ako je tajna riječ „škola“:' }),
			list,
			h('p', { text: 'Plavo znači vruće (do #' + HOT + '), narandžasto toplo (do #' + WARM + '), sivo hladno.' }),
			h('p', { text: 'Redoslijed je izračunao računar iz miliona tekstova na našem jeziku: riječi koje se koriste u sličnim rečenicama su blizu. Zato je ponekad iznenađujući.' }),
			h('p', { text: 'Oblici riječi se računaju kao osnovna riječ: „kuće“ je isto što i „kuća“. Ako zapneš, klikni Pomoć. Nova riječ stiže svaki dan u ponoć.' })
		]));
	}

	function showStats() {
		var st = stats();
		var over = S.status !== 'playing';
		var body = h('div', {});
		if (over) {
			var n = S.guesses.length;
			body.appendChild(h('p', { class: 'dpig-note', text: S.status === 'won'
				? 'Pogodio/la si „' + S.answer + '“ iz ' + n + '. pokušaja!'
				: 'Riječ je bila „' + S.answer + '“.' }));
		}
		body.appendChild(D.nums([
			[st.played, 'Odigrano'],
			[st.won, 'Pogođeno'],
			[st.currentStreak, 'Trenutni niz'],
			[st.maxStreak, 'Najduži niz']
		]));
		if (st.best) {
			body.appendChild(h('p', { class: 'dpig-small-note', text: 'Najbolje: ' + st.best + ' ' + D.plural(st.best, 'pokušaj', 'pokušaja') + '. Prosjek: ' + st.average + '.' }));
		}
		if (over) {
			var clock = h('div', { class: 'dpig-clock' });
			body.appendChild(h('div', { class: 'dpig-after' }, [
				h('div', {}, [h('div', { class: 'dpig-label', text: 'Sljedeća riječ za' }), clock]),
				S.status === 'won' ? h('button', { class: 'dpig-btn dpig-primary', type: 'button', onclick: shareResult, text: 'Podijeli rezultat' }) : null
			]));
			D.countdown(clock, S.nextIn - (Date.now() - S.loadedAt) / 1000);
			body.appendChild(h('button', { class: 'dpig-btn', type: 'button', onclick: showTop, text: 'Najbliže riječi' }));
		}
		body.appendChild(h('button', { class: 'dpig-btn', type: 'button', onclick: function () { shell.leaderboard('today'); }, text: 'Ljestvica' }));
		var box = shell.accountBox(S.player);
		if (box) body.appendChild(box);
		shell.open('Statistika', body);
	}

	function showTop() {
		var list = h('ol', { class: 'dpig-kx-list' }, [h('li', { class: 'dpig-label', text: 'Učitavam…' })]);
		shell.open('Najbliže riječi', h('div', {}, [
			h('p', { class: 'dpig-label', text: '100 riječi najbližih današnjoj riječi.' }), list
		]));
		D.api('kontekst/top?date=' + S.date).then(function (data) {
			list.innerHTML = '';
			var mine = {};
			S.guesses.forEach(function (g) { mine[g.w] = true; });
			data.words.forEach(function (g) { list.appendChild(row(g, mine[g.w])); });
		}).catch(function (e) { list.innerHTML = ''; list.appendChild(h('li', { text: e.message })); });
	}

	function shareResult() {
		var c = { hot: 0, warm: 0, cold: 0 };
		S.guesses.forEach(function (g) { if (g.r > 1) c[band(g.r)]++; });
		var n = S.guesses.length;
		var hints = S.guesses.filter(function (g) { return g.h; }).length;
		var text = 'Kontekst #' + S.number + '\n' +
			'Pogodak iz ' + n + '. pokušaja' + (hints ? ' (' + hints + ' ' + D.plural(hints, 'pomoć', 'pomoći') + ')' : '') + '\n' +
			'🟦 ' + c.hot + '  🟧 ' + c.warm + '  ⬜ ' + c.cold + '\n' + (D.CFG.pageUrl || location.href);
		D.share(shell, text);
	}

	/* ---------- start ---------- */

	function load() {
		return D.api('kontekst/state').then(function (data) {
			S.date = data.date;
			S.number = data.number;
			S.total = data.total;
			S.nextIn = data.nextIn;
			S.loadedAt = Date.now();
			S.player = data.player;
			if (S.player) {
				var gm = data.game || { guesses: [], status: 'playing' };
				S.guesses = gm.guesses;
				S.status = gm.status;
				S.answer = gm.answer || null;
			} else {
				var g = guest();
				S.guesses = g.guesses || [];
				S.status = g.status || 'playing';
				S.answer = g.answer || null;
			}
			S.last = null;
			render();
			if (!D.store('dpig_kx_seen_help')) { D.store('dpig_kx_seen_help', 1); showHelp(); }
		}).catch(function (e) {
			root.innerHTML = '';
			root.appendChild(h('p', { class: 'dpig-error', text: 'Igra se nije mogla učitati: ' + e.message }));
		});
	}

	build();
	load();
})();
