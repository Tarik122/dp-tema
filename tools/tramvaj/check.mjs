// Checks that every puzzle has exactly one solution. With an index, prints that puzzle's solution.
// Usage: node check.mjs <puzzles.json> [index]
import fs from 'fs';
import { neighbours, solve } from './generate.mjs';
const list = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
const only = process.argv[3];
if (only !== undefined) {
	const p = list[+only];
	const r = solve(p.n, neighbours(p.n), p.s, p.w, 1e8);
	console.log(JSON.stringify({ n: p.n, count: r.count, path: r.found[0] }));
} else {
	let bad = 0;
	const sizes = {};
	list.forEach((p, i) => {
		const r = solve(p.n, neighbours(p.n), p.s, p.w, 1e8);
		if (r.count !== 1 || r.aborted) { bad++; console.log('puzzle', i, 'solutions', r.count, r.aborted ? '(aborted)' : ''); }
		const k = p.n + 'x' + p.n;
		sizes[k] = (sizes[k] || 0) + 1;
	});
	const stops = list.map((p) => p.s.length), walls = list.map((p) => p.w.length);
	console.log('puzzles', list.length, 'not unique', bad, 'sizes', JSON.stringify(sizes),
		'stops', Math.min(...stops) + '-' + Math.max(...stops), 'with walls', walls.filter((w) => w).length);
}
