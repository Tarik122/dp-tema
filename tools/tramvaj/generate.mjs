// Tramvaj puzzles: one line that passes through every cell of an n×n grid exactly
// once and visits the numbered stops in order. Every puzzle has exactly one solution.
// Usage: node generate.mjs <count> <out.json>
import fs from 'fs';

let seed = 20260926;
function rnd() { seed = (seed * 1664525 + 1013904223) >>> 0; return seed / 4294967296; }
function pick(a) { return a[Math.floor(rnd() * a.length)]; }
function shuffle(a) { for (let i = a.length - 1; i > 0; i--) { const j = Math.floor(rnd() * (i + 1)); [a[i], a[j]] = [a[j], a[i]]; } return a; }

export function neighbours(n) {
	const nb = [];
	for (let i = 0; i < n * n; i++) {
		const r = Math.floor(i / n), c = i % n, l = [];
		if (r > 0) l.push(i - n);
		if (r < n - 1) l.push(i + n);
		if (c > 0) l.push(i - 1);
		if (c < n - 1) l.push(i + 1);
		nb.push(l);
	}
	return nb;
}

// Random Hamiltonian path by "backbite" moves, starting from a snake.
function randomPath(n, nb) {
	let p = [];
	for (let r = 0; r < n; r++) for (let c = 0; c < n; c++) p.push(r * n + (r % 2 ? n - 1 - c : c));
	const moves = 30 * n * n * n;
	for (let m = 0; m < moves; m++) {
		if (rnd() < 0.5) p.reverse();
		const x = pick(nb[p[0]]);
		if (x === p[1]) continue;
		const i = p.indexOf(x);
		p = p.slice(0, i).reverse().concat(p.slice(i));
	}
	if (rnd() < 0.5) p.reverse();
	return p;
}

const key = (a, b) => (a < b ? a + ',' + b : b + ',' + a);

// Count solutions, stopping at 2. alt is a second solution when there is one.
export function solve(n, nb, stops, walls, limit = 1500000) {
	const N = n * n, stopAt = new Int16Array(N).fill(-1);
	stops.forEach((cell, k) => (stopAt[cell] = k));
	const blocked = new Set(walls.map(([a, b]) => key(a, b)));
	const adj = nb.map((l, i) => l.filter((j) => !blocked.has(key(i, j))));
	const seen = new Uint8Array(N), path = [];
	const last = stops[stops.length - 1];
	let count = 0, nodes = 0, aborted = false;
	const found = [];

	// Unvisited cells must stay connected, and only the last stop may be a dead end.
	function feasible(head) {
		let start = -1, unvisited = 0;
		for (let i = 0; i < N; i++) {
			if (seen[i]) continue;
			unvisited++;
			if (start < 0) start = i;
			let d = 0, touchesHead = false;
			for (const j of adj[i]) {
				if (!seen[j]) d++;
				else if (j === head) touchesHead = true;
			}
			if (d + (touchesHead ? 1 : 0) === 0) return false;
			// A cell the line can enter but not leave must be where the line ends.
			if (i !== last && (d === 0 || (d === 1 && !touchesHead))) return false;
		}
		if (!unvisited) return true;
		const q = [start], mark = new Uint8Array(N);
		mark[start] = 1;
		let got = 1;
		while (q.length) {
			const i = q.pop();
			for (const j of adj[i]) if (!seen[j] && !mark[j]) { mark[j] = 1; got++; q.push(j); }
		}
		return got === unvisited;
	}

	function dfs(cell, next) {
		if (aborted) return;
		if (++nodes > limit) { aborted = true; return; }
		if (path.length === N) {
			if (cell === last) { count++; found.push(path.slice()); }
			return;
		}
		if (!feasible(cell)) return;
		for (const j of adj[cell]) {
			if (seen[j]) continue;
			const k = stopAt[j];
			if (k >= 0 && k !== next) continue;
			seen[j] = 1; path.push(j);
			dfs(j, k >= 0 ? next + 1 : next);
			path.pop(); seen[j] = 0;
			if (count >= 2 || aborted) return;
		}
	}
	seen[stops[0]] = 1; path.push(stops[0]);
	dfs(stops[0], 1);
	return { count, found, aborted };
}

function makePuzzle(n) {
	const nb = neighbours(n);
	for (let attempt = 0; ; attempt++) {
		if (attempt && attempt % 50 === 0) console.error('  still looking for a ' + n + '×' + n + ' puzzle, attempt', attempt);
		const path = randomPath(n, nb);
		const onPath = new Set();
		for (let i = 1; i < path.length; i++) onPath.add(key(path[i - 1], path[i]));
		let walls = [];
		if (rnd() < 0.5) {
			const cand = [];
			for (let i = 0; i < n * n; i++) for (const j of nb[i]) if (i < j && !onPath.has(key(i, j))) cand.push([i, j]);
			walls = shuffle(cand).slice(0, 2 + Math.floor(rnd() * (n - 2)));
		}
		// Start with a stop every few cells, add stops until the solution is unique,
		// then take away the ones that are not needed.
		const idx = new Set([0, path.length - 1]);
		for (let i = n; i < path.length - 2; i += n - 1 + Math.floor(rnd() * 3)) idx.add(i);
		const stopsOf = () => [...idx].sort((a, b) => a - b).map((i) => path[i]);
		let res = solve(n, nb, stopsOf(), walls);
		let guard = 0;
		while (!res.aborted && res.count > 1 && guard++ < 40) {
			// Use the solution that is not ours and add a stop where it leaves our route.
			const alt = res.found.find((f) => f.some((c, i) => c !== path[i]));
			let d = 0;
			while (alt[d] === path[d]) d++;
			idx.add(Math.min(path.length - 2, d + Math.floor(rnd() * 3)));
			res = solve(n, nb, stopsOf(), walls);
		}
		if (res.aborted || res.count !== 1) continue;
		const minStops = n + 1;
		for (const i of shuffle([...idx].filter((i) => i !== 0 && i !== path.length - 1))) {
			if (idx.size <= minStops) break;
			idx.delete(i);
			const r = solve(n, nb, stopsOf(), walls);
			if (r.aborted || r.count !== 1) idx.add(i);
		}
		return { n, s: stopsOf(), w: walls };
	}
}

if (!process.argv[1].endsWith('generate.mjs')) {
	// Imported by check.mjs: only the helpers are needed.
} else {
const count = parseInt(process.argv[2] || '10', 10);
const out = process.argv[3] || 'puzzles.json';
const sizes = [6, 6, 7, 6, 7, 7, 7];
const list = [];
const t0 = Date.now();
for (let i = 0; i < count; i++) {
	list.push(makePuzzle(sizes[i % 7]));
	if (i % 50 === 0) console.error(i, ((Date.now() - t0) / 1000).toFixed(1) + 's');
}
fs.writeFileSync(out, JSON.stringify(list));
console.error('done', list.length, 'puzzles', fs.statSync(out).size, 'bytes', ((Date.now() - t0) / 1000).toFixed(1) + 's');
}
