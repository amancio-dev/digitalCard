#!/usr/bin/env python3
import re, sys, pathlib, collections
DRY = '--dry' in sys.argv
ROOT = pathlib.Path('resources/views')

def lum(hexs):
    h = hexs.lstrip('#'); h = ''.join(c*2 for c in h) if len(h) == 3 else h[:6]
    r, g, b = [int(h[i:i+2], 16)/255 for i in (0, 2, 4)]
    f = lambda c: c/12.92 if c <= .03928 else ((c+.055)/1.055)**2.4
    return .2126*f(r)+.7152*f(g)+.0722*f(b)

def hex_to_token(m):
    var, util, hx = m.group(1), m.group(2), m.group(3)
    l = lum(hx)
    tok = 'ink' if l < .06 else 'slate-800' if l < .15 else 'slate-700' if l < .3 else 'paper' if l > .8 else 'slate-200'
    return f'{var}{util}-{tok}'

RULES = [
    ('amber->accent', re.compile(r'\b(amber|yellow|orange)-(\d{2,3})\b'), r'accent-\2'),
    ('emerald->money', re.compile(r'\b(emerald|green|teal|lime)-(\d{2,3})\b'), r'money-\2'),
    ('rose->danger', re.compile(r'\b(rose|red)-(\d{2,3})\b'), r'danger-\2'),
    ('indigo/sky->slate', re.compile(r'\b(indigo|sky|blue|cyan|violet|purple)-(\d{2,3})\b'), r'slate-\2'),
    ('hex->token', re.compile(r'((?:[a-z0-9-]+:)*)(bg|from|via|to|text|border|ring)-\[(#[0-9A-Fa-f]{3,8})\]'), hex_to_token),
    ('bold-diet', re.compile(r'\bfont-(black|extrabold)\b'), 'font-bold'),
    ('px-pequeno', re.compile(r'\btext-\[(?:[0-9]|1[01])px\]'), 'text-xs'),
    ('slate-400-texto', re.compile(r'(?<![:\w-])text-slate-400\b'), 'text-slate-500'),
    ('caixa-alta', re.compile(r'(?<=[\s"\'`])uppercase(?=[\s"\'`])'), ''),
    ('tracking', re.compile(r'(?<=[\s"\'`])tracking-(?:wide|wider|widest)(?=[\s"\'`])'), ''),
    ('blur', re.compile(r'(?<=[\s"\'`])backdrop-blur(?:-[a-z0-9]+)?(?=[\s"\'`])'), ''),
]
stats = collections.Counter(); files = 0
for f in sorted(ROOT.rglob('*.blade.php')):
    s0 = f.read_text(encoding='utf-8'); s = s0
    for name, rx, rep in RULES:
        s, n = rx.subn(rep, s); stats[name] += n
    s = re.sub(r'(?<=class=")([^"]*)"', lambda m: re.sub(r'\s{2,}', ' ', m.group(1)).strip() + '"', s)
    if s != s0:
        files += 1
        if not DRY: f.write_text(s, encoding='utf-8')
print(('[DRY] ' if DRY else '') + f'{files} arquivos alterados')
for k, v in stats.most_common(): print(f'  {k:18} {v}')
