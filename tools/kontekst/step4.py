import pickle, numpy as np, gzip, re, random, struct, os
d = pickle.load(open('forms.pkl','rb')); freq = d['freq']
L = pickle.load(open('lemmas.pkl','rb')); groups = L['groups']; tot = L['tot']
lemmas_all = pickle.load(open('lemma_list.pkl','rb'))
Vall = np.load('lemma_vec.npy'); vidx = {l:i for i,l in enumerate(lemmas_all)}

# Words that never appear: slurs, swearing, sexual words.
BLOCK = re.compile(r'^(jeb|kurac|kurč|kurc|pič|pizd|kurv|drolj|peder|sise?$|sisa|guzic|govn|sranj|srat|pišat|kenj|drkat|jebi|pušiti$|balij|čefur|šiptar|cigan|ustaš|četni|debil|kreten|retard|mongol|kurvin|fuf|droca|bludnic|prostitu|seks|porn|orgazm|penis|vagin|erekc|masturb|silov)')

fix = {'stepenice':'stepenica','rukavice':'rukavica','čarape':'čarapa','cipele':'cipela','patike':'patika','čizme':'čizma','snovi':'san'}
drop = {'pizza','plivanje','trčanje','medicinska','sto','luk','list','so'}
answers = []
for w in open('answers_raw.txt').read().split():
    w = fix.get(w, w)
    if w in drop or w in answers or w not in vidx: continue
    answers.append(w)

vocab = [l for l in lemmas_all if tot[l] >= 100 and not BLOCK.match(l)]
for a in answers:
    if a not in vocab: vocab.append(a)
print('vocab', len(vocab), 'answers', len(answers))
V = Vall[[vidx[l] for l in vocab]]

rng = np.random.default_rng(20260926)
R, _ = np.linalg.qr(rng.standard_normal((300, 300)))
X = V @ R
scale = np.abs(X).max(1, keepdims=True)
Q = np.clip(np.round(X / scale * 7), -7, 7).astype(np.int8)
inv = (1.0 / np.linalg.norm(Q.astype(np.float32), axis=1)).astype(np.float32)
out = 'out'; os.makedirs(out, exist_ok=True)
with open(f'{out}/vectors.bin', 'wb') as f:
    for i in range(len(vocab)):
        nib = (Q[i] + 8).astype(np.uint8)
        packed = (nib[0::2] | (nib[1::2] << 4)).astype(np.uint8)
        f.write(struct.pack('<f', inv[i])); f.write(packed.tobytes())
open(f'{out}/words.txt', 'w').write('\n'.join(vocab) + '\n')

# Every known form of a vocabulary word -> its id (the word itself included).
vid = {l:i for i,l in enumerate(vocab)}
rows = []
for l, fs in groups.items():
    if l not in vid: continue
    for f_ in set(fs) | {l}:
        if f_ != l and not BLOCK.match(f_): rows.append((f_, vid[l]))
seen = set(); uniq = []
for f_, i in sorted(rows):
    if f_ in seen or f_ in vid: continue
    seen.add(f_); uniq.append(f'{f_}\t{i}')
with gzip.open(f'{out}/forms.txt.gz', 'wt', encoding='utf-8', compresslevel=9) as g:
    g.write('\n'.join(uniq) + '\n')

random.Random(2026).shuffle(answers)
open(f'{out}/answers.txt', 'w').write('\n'.join(answers) + '\n')
print('forms', len(uniq))
for f_ in os.listdir(out): print(f_, os.path.getsize(f'{out}/{f_}'))
