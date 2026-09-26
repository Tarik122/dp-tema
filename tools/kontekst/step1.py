# Frequencies + stream 1M hr vectors, keep plausible words.
import re, gzip, numpy as np, subprocess, sys, pickle, urllib.request, io
OK = re.compile(r'^[a-zčćđšž]{2,}$')
freq = {}
for l in ['bs','hr','sr']:
    with open(f'{l}_full.txt', encoding='utf-8') as f:
        for line in f:
            p = line.split()
            if len(p) != 2: continue
            w, c = p[0], int(p[1])
            if OK.match(w):
                freq[w] = freq.get(w, 0) + c
print('freq words', len(freq))
MIN = 8
words, vecs = [], []
proc = subprocess.Popen("curl -sS https://dl.fbaipublicfiles.com/fasttext/vectors-crawl/cc.hr.300.vec.gz | gunzip | head -n 1000001", shell=True, stdout=subprocess.PIPE)
first = True
for raw in proc.stdout:
    if first: first = False; continue
    line = raw.decode('utf-8', 'ignore')
    sp = line.find(' ')
    w = line[:sp]
    if not OK.match(w) or freq.get(w, 0) < MIN: continue
    p = line.rstrip().split(' ')
    if len(p) != 301: continue
    words.append(w); vecs.append(np.asarray(p[1:], dtype=np.float32))
M = np.vstack(vecs).astype(np.float32)
print('kept', len(words))
np.save('forms_vec.npy', M)
with open('forms.pkl', 'wb') as f: pickle.dump({'words': words, 'freq': {w: freq[w] for w in words}}, f)
