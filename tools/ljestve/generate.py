"""Ljestve: word list and daily puzzles.

Allowed steps: every 4-letter word form from the Kontekst data (checked
against the bs/hr/sr spelling dictionaries). LJ, NJ and DŽ are one letter.
Each day's start and end word come from ENDS (everyday words), and the
shortest ladder between them is 4 to 6 steps.

The day's solution may only use common words (seen often in the
FrequencyWords subtitle lists), so a player can find it; any real word is
still accepted as a step.

Usage: python3 generate.py <kontekst data dir> <out dir> <freq dir with bs_full.txt, hr_full.txt, sr_full.txt> [days]
"""
import collections, json, random, sys

ENDS = """kuća žena otac mama tata brat baka grad selo more voda noga ruka kosa lice vrat srce krov soba stan
park most auto brod igra film broj zima ljeto kiša nebo polje šuma drvo list ruža kraj riba kost meso hljeb
kafa juha supa pita jaje voće tuga trka skok kino luka kula vila kesa kapa boja ples nota zvuk glas pero vaza
brdo otok mraz duga grom munja zora veče drug ujak beba cura tava pila igla kaiš mapa kora mali star nada šala
sova rosa lipa kruh koza ovca orao mrav lala vuna kuna sat dom pod plan rana lopa mjesto zlato laž cvijet
čaša šolja lula meda seka noć dan put sir med gol tim trg nos zub oko uho led sol val zid pas vuk zec žaba
mačka ptica tigar lav kit lama sofa vaga kula dugme kamen tabla kreda klupa sveska"""

LENGTH = 4

# Rude words: never allowed as a step, a hint or in a solution.
BLOCK = set('muda mudo mudi mudu guza guzu guze kita kite kitu kiti seks sise sisa sisu siše piša pišu drka jebe jebo jeba jebi srat sere seri kurc kurv droc peni pera'.split())


def tiles(w):
    out, i = [], 0
    while i < len(w):
        if w[i:i + 2] in ('lj', 'nj', 'dž'):
            out.append(w[i:i + 2]); i += 2
        else:
            out.append(w[i]); i += 1
    return tuple(out)


MIN_FREQ = 2000


def main(kdir, out, fdir, days=730):
    forms = [l.split('\t')[0] for l in open(kdir + '/forms.txt', encoding='utf-8') if l.strip()]
    words = sorted({w for w in forms if len(tiles(w)) == LENGTH and w not in BLOCK})
    freq = collections.Counter()
    known = set(words)
    for lang in ('bs', 'hr'):  # Ijekavian only: Serbian lists would bring cena, tela…
        for line in open(f'{fdir}/{lang}_full.txt', encoding='utf-8'):
            p = line.split()
            if len(p) == 2 and p[0] in known:
                freq[p[0]] += int(p[1])
    buckets = collections.defaultdict(list)
    for w in words:
        t = tiles(w)
        for i in range(LENGTH):
            buckets[t[:i] + ('*',) + t[i + 1:]].append(w)

    def nbrs(w):
        t, r = tiles(w), set()
        for i in range(LENGTH):
            r.update(buckets[t[:i] + ('*',) + t[i + 1:]])
        r.discard(w)
        return r

    common = {w for w in words if freq[w] >= MIN_FREQ}
    print('common words', len(common))

    def bfs(s):
        # Shortest ladders through common words only.
        prev, q = {s: None}, collections.deque([s])
        while q:
            u = q.popleft()
            for v in sorted(nbrs(u) & common, key=lambda x: -freq[x]):
                if v not in prev:
                    prev[v] = u; q.append(v)
        return prev

    wordset = set(words)
    ends = []
    for w in ENDS.split():
        if w in wordset and w in common and w not in ends:
            ends.append(w)
    print('words', len(words), 'end words', len(ends))

    rng = random.Random(2026)
    targets = [4, 5, 4, 6, 5, 4, 5]
    pairs, used = [], set()
    trees = {e: bfs(e) for e in ends}
    candidates = collections.defaultdict(list)
    for a in ends:
        tree = trees[a]
        for b in ends:
            if a == b or b not in tree:
                continue
            path = [b]
            while tree[path[-1]] is not None:
                path.append(tree[path[-1]])
            steps = len(path) - 1
            if 4 <= steps <= 6:
                candidates[steps].append((a, b, list(reversed(path))))
    for k in candidates:
        rng.shuffle(candidates[k])
        print('pairs with', k, 'steps:', len(candidates[k]))
    i = 0
    while len(pairs) < days:
        want = targets[i % len(targets)]
        i += 1
        pool = candidates[want] or next((c for c in candidates.values() if c), [])
        while pool:
            a, b, path = pool.pop()
            if (a, b) in used or (b, a) in used:
                continue
            used.add((a, b))
            pairs.append({'a': a, 'b': b, 'n': len(path) - 1, 'p': path})
            break
        if not any(candidates.values()):
            break
    open(out + '/words.txt', 'w', encoding='utf-8').write('\n'.join(words) + '\n')
    open(out + '/common.txt', 'w', encoding='utf-8').write('\n'.join(sorted(common)) + '\n')
    json.dump(pairs, open(out + '/puzzles.json', 'w', encoding='utf-8'), ensure_ascii=False)
    print('puzzles', len(pairs))
    for p in pairs[:8]:
        print(' ', ' → '.join(p['p']))


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2], sys.argv[3], int(sys.argv[4]) if len(sys.argv) > 4 else 730)
