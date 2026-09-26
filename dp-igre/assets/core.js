/* Shared pieces for Kontekst and Tramvaj: requests, windows, messages, sign-in and leaderboards. */
window.DPIG = (function () {
	'use strict';

	var CFG = window.DPIG_CONFIG || {};

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

	var ICONS = {
		help: '<path d="M9.2 9a3 3 0 1 1 4.3 2.7c-.9.4-1.5 1.1-1.5 2.1v.7"/><circle cx="12" cy="18" r=".6" fill="currentColor"/>',
		trophy: '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M8.5 20h7M10 17h4"/>',
		stats: '<path d="M5 20V12M10 20V6M15 20v-9M20 20V9M3.5 20h18"/>'
	};

	function iconButton(label, name, onclick) {
		var b = h('button', { class: 'dpig-icon', type: 'button', 'aria-label': label, title: label, onclick: onclick });
		b.innerHTML = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + ICONS[name] + '</svg>';
		return b;
	}

	/* 1 pokušaj, 2 pokušaja, 11 pokušaja, 21 pokušaj. */
	function plural(n, one, many) {
		return n % 10 === 1 && n % 100 !== 11 ? one : many;
	}

	/* ---------- the frame every game uses ---------- */

	function Shell(root, opts) {
		var self = this;
		this.root = root;
		this.opts = opts;
		root.innerHTML = '';
		this.title = h('div', { class: 'dpig-title' });
		this.streak = h('span', { class: 'dpig-streak-badge', title: 'Trenutni niz' });
		this.extra = h('div', { class: 'dpig-header-extra' });
		this.header = h('div', { class: 'dpig-header' }, [
			this.title,
			h('div', { class: 'dpig-header-right' }, [
				this.extra,
				this.streak,
				iconButton('Pravila', 'help', function () { opts.onHelp(); }),
				iconButton('Ljestvica', 'trophy', function () { self.leaderboard('today'); }),
				iconButton('Statistika', 'stats', function () { opts.onStats(); })
			])
		]);
		this.account = h('div', { class: 'dpig-account' });
		this.toasts = h('div', { class: 'dpig-toasts', 'aria-live': 'polite' });
		this.modal = h('div', { class: 'dpig-modal', hidden: '' });
		this.modal.addEventListener('click', function (e) { if (e.target === self.modal) self.close(); });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !self.modal.hidden) self.close(); });
		root.appendChild(this.header);
		root.appendChild(this.account);
		root.appendChild(this.toasts);
		root.appendChild(this.modal);
	}

	Shell.prototype.toast = function (msg, ms) {
		var t = h('div', { class: 'dpig-toast', text: msg });
		this.toasts.prepend(t);
		setTimeout(function () { t.classList.add('dpig-fade'); }, ms || 1800);
		setTimeout(function () { t.remove(); }, (ms || 1800) + 400);
	};

	Shell.prototype.open = function (title, body) {
		var self = this;
		this.lastFocus = document.activeElement;
		this.modal.innerHTML = '';
		var close = h('button', { class: 'dpig-close', type: 'button', 'aria-label': 'Zatvori', onclick: function () { self.close(); }, text: '×' });
		this.modal.appendChild(h('div', { class: 'dpig-dialog', role: 'dialog', 'aria-modal': 'true', 'aria-label': title }, [
			close, h('h2', { text: title }), body
		]));
		this.modal.hidden = false;
		close.focus();
	};

	Shell.prototype.close = function () {
		this.modal.hidden = true;
		this.modal.innerHTML = '';
		stopCountdown();
		if (this.lastFocus && this.lastFocus.focus) this.lastFocus.focus();
	};

	Shell.prototype.setTitle = function (text) { this.title.textContent = text; };

	Shell.prototype.setStreak = function (n) { this.streak.textContent = n ? '🔥 ' + n : ''; };

	/* "Igraš anonimno" line; on phones the Google button lives in the Statistika window. */
	Shell.prototype.renderAccount = function (player) {
		var self = this;
		var el = this.account;
		el.innerHTML = '';
		if (player) {
			el.appendChild(h('span', {}, ['Igraš kao ', h('strong', { text: player.anonymous ? 'Anonimni igrač' : player.name })]));
			return;
		}
		if (!CFG.clientId) {
			el.appendChild(h('span', { text: 'Igraš anonimno.' }));
			return;
		}
		if (window.innerWidth < 600) {
			el.appendChild(h('span', {}, [
				'Igraš anonimno. ',
				h('button', { class: 'dpig-link', type: 'button', onclick: function () { self.opts.onStats(); }, text: 'Prijavi se' }),
				' za ljestvicu i niz.'
			]));
			return;
		}
		el.appendChild(h('span', { text: 'Igraš anonimno. Prijavi se školskim mailom za ljestvicu i niz:' }));
		var slot = h('div', { class: 'dpig-google' });
		el.appendChild(slot);
		googleButton(slot, this.opts.onLogin);
	};

	/* Account box at the bottom of the Statistika window. */
	Shell.prototype.accountBox = function (player) {
		var self = this;
		if (player) {
			var toggle = h('input', { type: 'checkbox', id: 'dpig-anon' });
			toggle.checked = !!player.anonymous;
			toggle.addEventListener('change', function () {
				api('preferences', { anonymous: toggle.checked }).then(function () {
					player.anonymous = toggle.checked;
					self.renderAccount(player);
					self.toast(toggle.checked ? 'Na ljestvici si sada anoniman/na.' : 'Na ljestvici se vidi tvoje ime.');
				}).catch(function (e) { self.toast(e.message); });
			});
			return h('div', { class: 'dpig-account-box' }, [
				h('div', {}, [h('strong', { text: player.name }), h('div', { class: 'dpig-label', text: player.email })]),
				h('label', { for: 'dpig-anon' }, [toggle, ' Sakrij moje ime na ljestvici']),
				h('button', { class: 'dpig-link', type: 'button', onclick: function () { api('logout', {}).then(function () { location.reload(); }); }, text: 'Odjava' })
			]);
		}
		if (!CFG.clientId) return null;
		var slot = h('div', { class: 'dpig-google' });
		var box = h('div', { class: 'dpig-account-box' }, [
			h('p', { text: 'Prijavi se školskim mailom (@' + (CFG.domain || '') + ') da uđeš na ljestvicu i da ti se niz pamti na svakom uređaju.' }),
			slot
		]);
		setTimeout(function () { googleButton(slot, self.opts.onLogin); }, 0);
		return box;
	};

	var BOARD_TABS = [['today', 'Danas'], ['streak', 'Niz 🔥'], ['month', 'Ovaj mjesec']];

	Shell.prototype.leaderboard = function (type) {
		var self = this;
		var hints = this.opts.boardHints || {};
		var tabs = h('div', { class: 'dpig-tabs', role: 'tablist' }, BOARD_TABS.map(function (b) {
			return h('button', { class: 'dpig-tab' + (b[0] === type ? ' dpig-active' : ''), type: 'button', role: 'tab', 'aria-selected': String(b[0] === type), onclick: function () { self.leaderboard(b[0]); }, text: b[1] });
		}));
		var list = h('div', { class: 'dpig-lb' }, [h('p', { class: 'dpig-label', text: 'Učitavam…' })]);
		var body = h('div', {}, [tabs, h('p', { class: 'dpig-label', text: hints[type] || '' }), list]);
		if (!this.opts.getPlayer()) body.appendChild(h('p', { class: 'dpig-label', text: 'Na ljestvici su samo igrači prijavljeni školskim mailom.' }));
		this.open('Ljestvica', body);
		api('board?game=' + this.opts.game + '&type=' + type).then(function (data) {
			list.innerHTML = '';
			if (!data.rows.length) { list.appendChild(h('p', { text: 'Još niko. Budi prvi/a!' })); return; }
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
	};

	/* Scroll a phone just far enough that the whole game is visible, once. */
	Shell.prototype.fitOnPhone = function (bottomEl) {
		if (this.fitted || window.innerWidth >= 600 || window.scrollY > 0) return;
		this.fitted = true;
		var bottom = bottomEl.getBoundingClientRect().bottom + 12;
		var top = this.root.getBoundingClientRect().top;
		var by = Math.min(bottom - window.innerHeight, top);
		if (by > 0) window.scrollTo(0, by);
	};

	/* ---------- Google sign-in ---------- */

	var googleReady = false;
	var loginCallback = null;
	function googleButton(slot, onLogin, tries) {
		loginCallback = onLogin;
		if (!CFG.clientId) return;
		if (!window.google || !google.accounts || !google.accounts.id) {
			if ((tries || 0) < 50) setTimeout(function () { googleButton(slot, onLogin, (tries || 0) + 1); }, 200);
			return;
		}
		if (!googleReady) {
			google.accounts.id.initialize({
				client_id: CFG.clientId,
				hd: CFG.domain || undefined,
				callback: function (resp) {
					api('login', { credential: resp.credential }).then(function () {
						if (loginCallback) loginCallback();
					}).catch(function (e) { alert(e.message); });
				}
			});
			googleReady = true;
		}
		google.accounts.id.renderButton(slot, { theme: 'outline', size: 'medium', text: 'signin_with', shape: 'pill', locale: 'bs' });
	}

	/* ---------- small helpers ---------- */

	var countdownTimer = null;
	function stopCountdown() { if (countdownTimer) clearInterval(countdownTimer); countdownTimer = null; }
	function countdown(node, seconds) {
		var end = Date.now() + seconds * 1000;
		function tick() {
			var s = Math.max(0, Math.round((end - Date.now()) / 1000));
			if (s === 0) { stopCountdown(); node.textContent = 'Stigla je nova igra. Osvježi stranicu!'; return; }
			var p = function (n) { return (n < 10 ? '0' : '') + n; };
			node.textContent = p(Math.floor(s / 3600)) + ':' + p(Math.floor(s / 60) % 60) + ':' + p(s % 60);
		}
		stopCountdown();
		tick();
		countdownTimer = setInterval(tick, 1000);
	}

	function share(shell, text) {
		if (navigator.share && window.innerWidth < 600) {
			navigator.share({ text: text }).catch(function () {});
			return;
		}
		(navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () {
			shell.toast('Kopirano! Zalijepi u poruku.');
		}).catch(function () { window.prompt('Kopiraj rezultat:', text); });
	}

	function nums(list) {
		return h('div', { class: 'dpig-nums' }, list.map(function (n) {
			return h('div', {}, [h('div', { class: 'dpig-num', text: String(n[0]) }), h('div', { class: 'dpig-label', text: n[1] })]);
		}));
	}

	/* Guest statistics kept in the browser: a won day continues the streak only if yesterday was won too. */
	function guestFinished(st, date, won, score) {
		if (st.recorded === date) return st;
		st.recorded = date;
		st.played = (st.played || 0) + 1;
		if (won) {
			var y = new Date(date + 'T12:00:00Z');
			y.setUTCDate(y.getUTCDate() - 1);
			st.currentStreak = st.lastWon === y.toISOString().slice(0, 10) ? (st.currentStreak || 0) + 1 : 1;
			st.maxStreak = Math.max(st.maxStreak || 0, st.currentStreak);
			st.lastWon = date;
			st.won = (st.won || 0) + 1;
			st.best = st.best ? Math.min(st.best, score) : score;
			st.sum = (st.sum || 0) + score;
			st.average = Math.round(st.sum / st.won);
		} else {
			st.currentStreak = 0;
		}
		return st;
	}

	function guestStats(st, date) {
		st = st || {};
		var y = new Date(date + 'T12:00:00Z');
		y.setUTCDate(y.getUTCDate() - 1);
		if (st.lastWon !== date && st.lastWon !== y.toISOString().slice(0, 10)) st.currentStreak = 0;
		return {
			played: st.played || 0, won: st.won || 0, currentStreak: st.currentStreak || 0,
			maxStreak: st.maxStreak || 0, best: st.best || null, average: st.average || null
		};
	}

	return {
		CFG: CFG, h: h, api: api, store: store, plural: plural, Shell: Shell,
		countdown: countdown, share: share, nums: nums,
		guestFinished: guestFinished, guestStats: guestStats
	};
})();
