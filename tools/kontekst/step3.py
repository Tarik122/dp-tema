import pickle, numpy as np
d = pickle.load(open('forms.pkl','rb')); words = d['words']; freq = d['freq']
idx = {w:i for i,w in enumerate(words)}
M = np.load('forms_vec.npy'); M /= np.linalg.norm(M, axis=1, keepdims=True) + 1e-9
L = pickle.load(open('lemmas.pkl','rb')); groups = L['groups']; tot = L['tot']
T = 50
lemmas = sorted([l for l in groups if tot[l] >= T], key=lambda l: -tot[l])
V = np.zeros((len(lemmas), 300), np.float32)
for i, l in enumerate(lemmas):
    fs = groups[l]
    w = np.array([np.sqrt(freq[f]) for f in fs], np.float32)
    v = (M[[idx[f] for f in fs]] * w[:, None]).sum(0)
    V[i] = v / (np.linalg.norm(v) + 1e-9)
np.save('lemma_vec.npy', V); pickle.dump(lemmas, open('lemma_list.pkl','wb'))
li = {l:i for i,l in enumerate(lemmas)}
print(len(lemmas))
for q in ['škola','more','ljeto','mačka','kafa','hljeb','fudbal','ćevap','profesor','ispit','ljubav','tramvaj','telefon','snijeg','planina','pas','muzika','matematika']:
    if q not in li: print(q, 'MISSING'); continue
    s = V @ V[li[q]]; top = np.argsort(-s)[1:16]
    print(q, ':', ', '.join(lemmas[i] for i in top))
