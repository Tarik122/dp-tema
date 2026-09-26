# Kontekst data

How `dp-igre/data/kontekst/` was made. You only need this to rebuild the word data.

1. `step1.py`: word frequencies from FrequencyWords (OpenSubtitles bs/hr/sr, CC-BY-SA 4.0) and the first million word vectors of fastText `cc.hr.300` (Common Crawl, CC-BY-SA 3.0). It keeps lowercase words that also appear in the subtitle lists.
2. `step2.py`: groups word forms under their base word (kuće → kuća) using the LibreOffice hunspell dictionaries `bs_BA`, `hr_HR` and `sr-Latn`. First run `hunspell -s -d <dict> < forms.txt > stems_<bs|hr|sr>.txt` with `LC_ALL=C.UTF-8`.
3. `step3.py`: one vector per base word, as the frequency-weighted average of its forms.
4. `step4.py`: the final files.
   - `words.txt`: the base words, one per line; the line number is the word id.
   - `vectors.bin`: per word, a float32 (1 / vector length), then 150 bytes holding 300 4-bit values (value + 8). The vectors are randomly rotated first, so the 4 bits are used evenly.
   - `forms.txt`: `form<TAB>word id` for every form and base word, sorted by UTF-8 bytes so the plugin can binary-search the file.
   - `answers.txt`: the daily words in order (from `answers_raw.txt`, shuffled).

Similarity between two words is the dot product of their 4-bit values times both `1 / length` numbers.
