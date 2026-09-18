#!/usr/bin/env python3
"""Verificación final post-correcciones: HTML, rutas, vistas, tablas, handlers."""
import os, re
from html.parser import HTMLParser

ROOT = "/home/user/administrativo"
ok = True

def section(t): print("\n" + "="*70 + f"\n{t}\n" + "="*70)

def strip_code(src: str) -> str:
    """Elimina comentarios y strings PHP para conteos de llaves (heurístico)."""
    out = []
    i, n = 0, len(src)
    state = "code"
    heredoc_end = ""
    while i < n:
        c = src[i]
        nxt = src[i+1] if i+1 < n else ""
        if state == "code":
            if c == "/" and nxt == "/":
                state = "line"; i += 2; continue
            if c == "/" and nxt == "*":
                state = "block"; i += 2; continue
            if c == "#":
                state = "line"; i += 1; continue
            if c == "'":
                state = "sq"; i += 1; continue
            if c == '"':
                state = "dq"; i += 1; continue
            m = re.match(r"<<<['\"]?(\w+)['\"]?", src[i:])
            if m:
                state = "heredoc"; heredoc_end = m.group(1); i += m.end(); continue
            out.append(c); i += 1
        elif state == "line":
            if c == "\n":
                state = "code"; out.append("\n")
            i += 1
        elif state == "block":
            if c == "*" and nxt == "/":
                state = "code"; i += 2; continue
            if c == "\n": out.append("\n")
            i += 1
        elif state == "sq":
            if c == "\\":
                i += 2; continue
            if c == "'":
                state = "code"
            i += 1
        elif state == "dq":
            if c == "\\":
                i += 2; continue
            if c == '"':
                state = "code"
            i += 1
        elif state == "heredoc":
            m = re.match(r"^\s*(" + re.escape(heredoc_end) + r")\s*;", src[i:])
            if m:
                state = "code"; i += m.end(); continue
            if c == "\n": out.append("\n")
            i += 1
    return "".join(out)

# ---------- 1. ESTRUCTURA HTML DE VISTAS ----------
section("1) ESTRUCTURA HTML DE TODAS LAS VISTAS")
VOID = {"area","base","br","col","embed","hr","img","input","link","meta","param","source","track","wbr"}
class Checker(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=False)
        self.stack = []; self.errors = []
    def handle_starttag(self, tag, attrs):
        if tag not in VOID: self.stack.append((tag, self.getpos()))
    def handle_endtag(self, tag):
        if tag in VOID: return
        if not self.stack: self.errors.append(f"</{tag}> sin apertura (l. {self.getpos()[0]})"); return
        if self.stack[-1][0] == tag: self.stack.pop(); return
        names = [t for t,_ in self.stack]
        if tag in names:
            while self.stack and self.stack[-1][0] != tag:
                t,pos = self.stack.pop(); self.errors.append(f"<{t}> (l.{pos[0]}) no cerrada antes de </{tag}>")
            self.stack.pop()
        else: self.errors.append(f"</{tag}> inesperado (l. {self.getpos()[0]})")
bad_views = 0
for dirpath,_,files in os.walk(f"{ROOT}/views"):
    for f in sorted(files):
        if not f.endswith(".php"): continue
        p = os.path.join(dirpath,f)
        src = open(p, encoding="utf-8", errors="replace").read()
        src = re.sub(r"<\?php.*?\?>", " ", src, flags=re.S)
        src = re.sub(r"<\?=[^<]*?\?>", " ", src, flags=re.S)
        c = Checker(); c.feed(src); c.close()
        errs = c.errors + [f"<{t}> (l.{pos[0]}) abierta hasta EOF" for t,pos in c.stack]
        if errs:
            bad_views += 1
            print(f"[ROTA] {os.path.relpath(p, ROOT)}: {errs[:3]}")
print("OK: todas las vistas con HTML bien formado." if bad_views==0 else f"!! {bad_views} vistas rotas")
ok &= bad_views==0

# ---------- 2. RUTAS -> CLASES/MÉTODOS ----------
section("2) RUTAS -> CONTROLADOR::METODO")
index_src = open(f"{ROOT}/public/index.php", encoding="utf-8").read()
handlers = re.findall(r"\[(\w+Controller)::class,\s*'(\w+)'\]", index_src)
missing = []
seen = set()
for cls, m in handlers:
    if (cls,m) in seen: continue
    seen.add((cls,m))
    path = f"{ROOT}/app/Controllers/{cls}.php"
    if not os.path.exists(path): missing.append(f"{cls}.php NO EXISTE"); continue
    if not re.search(r"function\s+"+re.escape(m)+r"\s*\(", open(path, encoding="utf-8").read()):
        missing.append(f"{cls}::{m}")
print("FALTANTES:", missing if missing else f"ninguno ({len(seen)} handlers verificados)")
ok &= not missing

# ---------- 3. VISTAS REGISTRADAS ----------
section("3) VISTAS REGISTRADAS EN index.php")
views = re.findall(r"\$router->view\(\s*'[^']*'\s*,\s*'([^']+)'", index_src)
mv = [v for v in set(views) if not os.path.exists(f"{ROOT}/views/{v}")]
print(f"Vistas: {len(set(views))} | FALTANTES:", mv if mv else "ninguna")
ok &= not mv

# ---------- 4. TABLAS (incluye vistas SQL y excluye ON DUPLICATE KEY UPDATE) ----------
section("4) TABLAS REFERENCIADAS vs CREADAS (incl. vistas SQL)")
created = set()
mig = f"{ROOT}/database/migrations"
for f in sorted(os.listdir(mig)):
    if f.endswith(".sql"):
        s = open(os.path.join(mig,f), encoding="utf-8", errors="replace").read()
        for m in re.finditer(r"CREATE\s+(?:TABLE|OR\s+REPLACE\s+VIEW|VIEW)\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?", s, re.I):
            created.add(m.group(1).lower())
referenced = set()
php_files = []
for dirpath,_,files in os.walk(f"{ROOT}/app"):
    for f in files:
        if f.endswith(".php"): php_files.append(os.path.join(dirpath,f))
php_files.append(f"{ROOT}/public/index.php")
for p in php_files:
    s = open(p, encoding="utf-8", errors="replace").read()
    s = re.sub(r"ON\s+DUPLICATE\s+KEY\s+UPDATE", " ONDKU ", s)
    for m in re.finditer(r"(?:FROM|INTO|UPDATE|JOIN)\s+`?([a-z_][a-z0-9_]*)`?", s, re.I):
        referenced.add(m.group(1).lower())
kw = {"select","where","and","or","on","as","set","values","dual","information_schema","values"}
missing_t = sorted(t for t in referenced if t not in created and t not in kw and not t.startswith("onk"))
print(f"Tablas/vistas creadas: {len(created)}")
print("Referenciadas sin creación:", missing_t if missing_t else "ninguna ✔")
ok &= not missing_t

# ---------- 5. BALANCE DE LLAVES EN PHP PURO (app/ + public/ + tools/) ----------
section("5) BALANCE DE LLAVES (archivos PHP de lógica)")
bad = []
for base in (f"{ROOT}/app", f"{ROOT}/public", f"{ROOT}/tools", f"{ROOT}/database/seeders"):
    for dirpath,_,files in os.walk(base):
        for f in files:
            if not f.endswith(".php"): continue
            p = os.path.join(dirpath,f)
            code = strip_code(open(p, encoding="utf-8", errors="replace").read())
            if code.count("{") != code.count("}"):
                bad.append((os.path.relpath(p, ROOT), code.count("{"), code.count("}")))
print("DESBALANCE:", bad if bad else "ninguno ✔")
ok &= not bad

# ---------- 6. LÍNEAS QUE TERMINAN DENTRO DE UNA ETIQUETA HTML ----------
section("6) LÍNEAS QUE TERMINAN DENTRO DE UNA ETIQUETA (vistas)")
sus = []
for dirpath,_,files in os.walk(f"{ROOT}/views"):
    for f in sorted(files):
        if not f.endswith(".php"): continue
        p = os.path.join(dirpath,f)
        lines = open(p, encoding="utf-8", errors="replace").read().split("\n")
        for i,line in enumerate(lines):
            ls = line.strip()
            if ls.startswith("<?php") or ls.startswith("<?="): continue
            if not re.search(r"<[a-zA-Z/!][^<>]*$", line.rstrip()): continue
            nxt = lines[i+1].strip() if i+1 < len(lines) else ""
            # si la siguiente línea continúa la etiqueta (atributo) o cierra el tag, está OK
            if re.match(r"^(class|x-|:|@|style|href|id|type|value|title|disabled|placeholder|min|max|step|rows|target|rel|src|srcset|for|selected|checked|open|hidden|multiple|readonly|autofocus|required|inputmode|autocomplete|pattern|list|data-[a-z]+|:class|:key|:value|:src|:href|max|rows)\s*[= ]|^(</|\w[\w-]*\s*=|>|\.)", nxt, re.I):
                continue
            # también OK si la línea termina en un atributo completo y la siguiente es otro atributo o cierre
            if re.search(r"\w\"?$", line.rstrip()) and re.match(r"^[a-zA-Z:_][\w:.=-]*\s*(=.*)?$", nxt):
                continue
            sus.append((os.path.relpath(p, ROOT), i+1, line.rstrip()[-60:]))
print("Sospechosas restantes:", sus if sus else "ninguna ✔")

print()
print("RESULTADO GLOBAL:", "TODAS LAS VERIFICACIONES CLAVES PASAN ✔" if ok else "QUEDAN PENDIENTES")
