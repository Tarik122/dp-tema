import re, pickle, numpy as np, collections
OK = re.compile(r'^[a-zčćđšž]{2,}$')
d = pickle.load(open('forms.pkl','rb')); words = d['words']; freq = d['freq']
idx = {w:i for i,w in enumerate(words)}
M = np.load('forms_vec.npy'); M /= np.linalg.norm(M, axis=1, keepdims=True) + 1e-9
stems = collections.defaultdict(list)  # form -> [(stem, source)]
for src in ['bs','hr','sr']:
    for line in open(f'stems_{src}.txt', encoding='utf-8'):
        p = line.split()
        if len(p) == 2 and OK.match(p[1]) and (p[1], src) not in stems[p[0]]:
            stems[p[0]].append((p[1], src))
lemma_of = {}
for w in words:
    cands = stems.get(w)
    if not cands: continue
    # Prefer a stem that is a frequent word itself; Bosnian dictionary wins ties.
    best = max(cands, key=lambda c: (freq.get(c[0], 0), c[1] == 'bs', c[1] == 'hr'))
    lemma_of[w] = best[0]
groups = collections.defaultdict(list)
for w, l in lemma_of.items(): groups[l].append(w)
tot = {l: sum(freq[w] for w in fs) for l, fs in groups.items()}
print('recognized forms', len(lemma_of), 'lemmas', len(groups))
for T in [50, 100, 200, 400, 800]:
    print(T, sum(1 for l in tot if tot[l] >= T))
pickle.dump({'lemma_of': lemma_of, 'groups': dict(groups), 'tot': tot}, open('lemmas.pkl','wb'))
