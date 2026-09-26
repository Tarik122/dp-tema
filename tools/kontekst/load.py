import numpy as np, re, sys
OK = re.compile(r'^[a-zčćđšž]+$')
def load(path, n=200000, filt=True):
    words=[]; vecs=[]
    with open(path, encoding='utf-8', errors='ignore') as f:
        f.readline()
        for i,line in enumerate(f):
            if i>=n: break
            p=line.rstrip().split(' ')
            w=p[0]
            if filt and not OK.match(w): continue
            if len(p)!=301: continue
            words.append(w); vecs.append(np.asarray(p[1:],dtype=np.float32))
    M=np.vstack(vecs); M/=np.linalg.norm(M,axis=1,keepdims=True)+1e-9
    return words,M
