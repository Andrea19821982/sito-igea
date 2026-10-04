#!/usr/bin/env bash
# Controlli automatici prima di consegnare. Uso: bash strumenti/verifica.sh
# Esce con errore se un controllo fallisce.
set -uo pipefail
cd "$(dirname "$0")/.."
errori=0
ok()  { printf 'OK      %s\n' "$1"; }
ko()  { printf 'ERRORE  %s\n' "$1"; errori=$((errori + 1)); }

# 1. Sintassi PHP di tema e mu-plugin
php_ko=0
while IFS= read -r -d '' f; do
	php -l "$f" >/dev/null 2>&1 || { ko "sintassi PHP: $f"; php_ko=1; }
done < <(find tema mu-plugins -name '*.php' -print0)
[ $php_ko -eq 0 ] && ok "sintassi PHP"

# 2. Sintassi JavaScript
if command -v node >/dev/null; then
	node --check tema/igea/assets/js/igea.js && ok "sintassi JavaScript" || ko "sintassi JavaScript"
else
	echo "SALTATO sintassi JavaScript (node non installato)"
fi

# 3. theme.json valido
python3 -m json.tool tema/igea/theme.json >/dev/null 2>&1 && ok "theme.json" || ko "theme.json non valido"

# 4. Nessuna credenziale: costanti sensibili con un valore, chiavi private, wp-config
if grep -rnE "define\(\s*'(IGEA_SMTP_PASS|IGEA_SMTP_USER|DB_PASSWORD|AUTH_KEY)'\s*," tema mu-plugins docs strumenti >/dev/null 2>&1 \
	|| grep -rnE -- "-----BEGIN [A-Z ]*PRIVATE KEY" . --exclude-dir=.git >/dev/null 2>&1 \
	|| [ -n "$(git ls-files 2>/dev/null | grep -E '(^|/)wp-config\.php$')" ]; then
	ko "possibile credenziale nel repository"
else
	ok "nessuna credenziale"
fi

# 5. Nessuna risorsa da Google Fonts o altri CDN nel tema (privacy dei visitatori)
if grep -rnE "fonts\.(googleapis|gstatic)\.com|cdn\.jsdelivr|unpkg\.com|cdnjs" tema >/dev/null; then
	ko "il tema carica risorse esterne"
else
	ok "nessuna risorsa esterna nel tema"
fi

# 6. Nessuno script in linea nei modelli (Content Security Policy con script-src 'self')
if grep -rnE "<script(\s|>)" tema --include='*.php' | grep -v 'application/ld+json' >/dev/null; then
	ko "script in linea nei modelli"
else
	ok "nessuno script in linea"
fi

# 7. Nessun innerHTML nel JavaScript del tema
grep -nE "\.(inner|outer)HTML\s*=|insertAdjacentHTML|document\.write" tema/igea/assets/js/igea.js >/dev/null && ko "innerHTML nel JavaScript" || ok "nessun innerHTML"

# 8. File citati da CSS e PHP presenti
mancano=0
for f in assets/css/igea.css assets/css/editor.css assets/js/igea.js assets/img/dea.svg assets/img/logo-igea-340.png assets/img/logo-igea-340.webp \
	assets/fonts/inter-latin-wght-normal.woff2 assets/fonts/inter-latin-ext-wght-normal.woff2 assets/fonts/inter-tight-latin-wght-normal.woff2 assets/fonts/inter-tight-latin-ext-wght-normal.woff2 \
	assets/img/dpi.jpg assets/img/dpi.webp assets/img/ambiente.jpg assets/img/ambiente.webp assets/img/alimenti.jpg assets/img/alimenti.webp assets/img/medicina.jpg assets/img/medicina.webp; do
	[ -f "tema/igea/$f" ] || { ko "manca tema/igea/$f"; mancano=1; }
done
[ $mancano -eq 0 ] && ok "file del tema presenti"

# Promemoria: segnaposto ancora nel tema (non è un errore)
n=$(grep -rhoE "DA (CONFERMARE|VALIDARE)" tema | wc -l)
echo "INFO    segnaposto DA CONFERMARE/DA VALIDARE nel tema: $n (elenco in docs/da-confermare.md)"

[ $errori -eq 0 ] && echo "Tutti i controlli superati." || echo "Controlli falliti: $errori"
exit $errori
