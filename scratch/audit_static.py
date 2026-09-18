#!/usr/bin/env python3
"""Auditoría estática del ERP: truncamientos, balance de llaves, rutas vs clases/métodos, vistas requeridas."""
import os, re, sys, json

ROOT = "/home/user/administrativo"
php_files = []
for dirpath, dirnames, filenames in os.walk(ROOT):
    dirnames[:] = [d for d in dirnames if d not in (".git", "vendor", "node_modules")]
    for f in filenames:
        if f.endswith(".php"):
            php_files.append(os.path.join(dirpath, f))

def strip_code(src: str) -> str:
    """Elimina comentarios y contenido de strings (heurístico) para conteos de llaves."""
    out = []
    i, n = 0, len(src)
    state = "code"  # code | sq | dq | line | block | heredoc
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
            if re.match(r"<<<", src[i:i+3]):
                m = re.match(r"<<<['\"]?(\w+)['\"]?", src[i:])
                if m:
                    state = "heredoc"; heredoc_end = m.group(1)
                    i += m.end(); continue
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

print("=" * 70)
print("1) BALANCE DE LLAVES / PÁRENTESIS (código limpio de strings/comentarios)")
print("=" * 70)
bad = []
for p in sorted(php_files):
    src = open(p, encoding="utf-8", errors="replace").read()
    code = strip_code(src)
    for a, b in [("{", "}"), ("(", ")"), ("[", "]")]:
        if code.count(a) != code.count(b):
            bad.append((p, a, b, code.count(a), code.count(b)))
if bad:
    for p, a, b, ca, cb in bad:
        print(f"[DESBALANCE] {os.path.relpath(p, ROOT)}  '{a}{b}': {ca} vs {cb}")
else:
    print("OK: todos los archivos balanceados.")

print()
print("=" * 70)
print("2) POSIBLES ARCHIVOS TRUNCADOS (última línea sospechosa / sin cierre)")
print("=" * 70)
trunc = []
for p in sorted(php_files):
    src = open(p, encoding="utf-8", errors="replace").read()
    lines = src.rstrip("\n").split("\n")
    last = lines[-1].strip() if lines else ""
    code = strip_code(src)
    code_last = code.rstrip()[-1:] if code.rstrip() else ""
    suspicious = False
    if last in ("", ",", "->", "=>", "{", "}", ":", "&&", "||", "."):
        suspicious = True
    if not src.rstrip().endswith((";", "}", ")", "]", "?>")):
        suspicious = True
    if suspicious:
        trunc.append((os.path.relpath(p, ROOT), last[:80]))
for rel, last in trunc:
    print(f"[SOSPECHOSO] {rel}  -> última línea: {last!r}")
if not trunc:
    print("OK: ningún final de archivo sospechoso.")

print()
print("=" * 70)
print("3) RUTAS -> CONTROLADOR::METODO  (verificación de existencia)")
print("=" * 70)
index_src = open(f"{ROOT}/public/index.php", encoding="utf-8").read()
handlers = re.findall(r'\[(\w+Controller)::class,\s*\'(\w+)\'\]', index_src)
controller_methods = {}
for cls, m in handlers:
    controller_methods.setdefault(cls, set()).add(m)

missing = []
for cls, methods in controller_methods.items():
    path = f"{ROOT}/app/Controllers/{cls}.php"
    if not os.path.exists(path):
        missing.append((cls, "ARCHIVO NO EXISTE", []))
        continue
    csrc = open(path, encoding="utf-8").read()
    for m in sorted(methods):
        if not re.search(r"function\s+" + re.escape(m) + r"\s*\(", csrc):
            missing.append((cls, m, []))
if missing:
    for cls, m, _ in missing:
        print(f"[FALTA] {cls}::{m}")
else:
    print(f"OK: {sum(len(v) for v in controller_methods.values())} handlers, todos existen.")

print()
print("=" * 70)
print("4) VISTAS REGISTRADAS EN index.php vs EXISTENTES")
print("=" * 70)
views = re.findall(r"\\$router->view\(\s*'[^']*'\s*,\s*'([^']+)'", index_src)
views += re.findall(r"require\s+ROOT_PATH\s*\.\s*'/views/([^']+\.php)'", index_src)
mv = []
for v in set(views):
    if not os.path.exists(f"{ROOT}/views/{v}"):
        mv.append(v)
if mv:
    for v in sorted(mv):
        print(f"[FALTA VISTA] views/{v}")
else:
    print(f"OK: {len(set(views))} vistas registradas, todas existen.")

print()
print("=" * 70)
print("5) USO DE API Database (instanciable vs estático)")
print("=" * 70)
db_src = open(f"{ROOT}/app/Core/Database.php", encoding="utf-8").read()
print("Métodos definidos en App\\Core\\Database:")
for m in re.findall(r"function\s+(\w+)\s*\(", db_src):
    print(f"   - {m}")
has_static_getconn = re.search(r"(static\s+)?function\s+getConnection", db_src) is not None
print(f"getConnection definido: {'SÍ' if has_static_getconn else 'NO'}")
usages = {}
for p in sorted(php_files):
    src = open(p, encoding="utf-8").read()
    for pat, label in [(r"Database::getConnection\(\)", "Database::getConnection()"),
                       (r"Database::getInstance\(\)", "Database::getInstance()")]:
        n = len(re.findall(pat, src))
        if n:
            usages[os.path.relpath(p, ROOT)] = usages.get(os.path.relpath(p, ROOT), {})
            usages[os.path.relpath(p, ROOT)][label] = n
for p, d in sorted(usages.items()):
    print(f"   {p}: {d}")

print()
print("=" * 70)
print("6) TABLAS REFERENCIADAS EN CÓDIGO vs CREADAS EN MIGRACIONES")
print("=" * 70)
created = set()
mig_dir = f"{ROOT}/database/migrations"
for f in sorted(os.listdir(mig_dir)):
    if f.endswith(".sql"):
        src = open(os.path.join(mig_dir, f), encoding="utf-8", errors="replace").read()
        for m in re.finditer(r"CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?", src, re.I):
            created.add(m.group(1).lower())
print(f"Tablas creadas por migraciones: {len(created)}")

referenced = set()
for p in php_files:
    src = open(p, encoding="utf-8", errors="replace").read()
    for pat in [r"FROM\s+`?(\w+)`?", r"INTO\s+`?(\w+)`?", r"UPDATE\s+`?(\w+)`?", r"JOIN\s+`?(\w+)`?"]:
        for m in re.finditer(pat, src, re.I):
            referenced.add(m.group(1).lower())
keywords = {"select", "where", "and", "or", "on", "as", "set", "values", "dual", "information_schema", "sleep"}
referenced -= keywords
missing_tables = sorted(t for t in referenced if t not in created and not t.startswith("("))
print("Tablas referenciadas en PHP pero NUNCA creadas por migraciones:")
for t in missing_tables:
    print(f"   [SIN TABLA] {t}")
if not missing_tables:
    print("   (ninguna)")

print()
print("=" * 70)
print("7) REFERENCIAS A MARCAS PROHIBIDAS / ARTIFACTOS RESIDUALES")
print("=" * 70)
artifacts = []
for p in php_files + [f"{ROOT}/{f}" for f in os.listdir(ROOT) if f.endswith(".md")]:
    src = open(p, encoding="utf-8", errors="replace").read()
    low = src.lower()
    for needle in ["a2lic", "a2r", "a2 "]:
        for m in re.finditer(needle, low):
            ln = src[:m.start()].count("\n") + 1
            line = src.split("\n")[ln-1].strip()[:100]
            artifacts.append((os.path.relpath(p, ROOT), needle, ln, line))
seen = set()
for rel, needle, ln, line in artifacts:
    key = (rel, needle, ln)
    if key in seen: continue
    seen.add(key)
    print(f"[ARTIFACTO] {rel}:{ln}  /{needle}/  -> {line}")
if not artifacts:
    print("OK: sin residuos.")
print()
print(f"TOTAL archivos PHP analizados: {len(php_files)}")
