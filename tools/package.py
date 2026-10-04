"""Create an installable ZIP from one plugin folder."""
from pathlib import Path
import hashlib
import re
import sys
import zipfile

root = Path(__file__).resolve().parents[1]
slug = sys.argv[1] if len(sys.argv) == 2 else ""
if not re.fullmatch(r"[a-z][a-z0-9-]+", slug):
    raise SystemExit("Aufruf: python3 tools/package.py <plugin-ordner>")
source = root / "plugins" / slug
main = source / (slug + ".php")
if not main.is_file():
    raise SystemExit("Plugin-Hauptdatei fehlt.")
header = main.read_text(encoding="utf-8")
match = re.search(r"^ \* Version: ([0-9.]+)$", header, re.MULTILINE)
if not match:
    raise SystemExit("Versionsangabe fehlt.")
version = match.group(1)
readme = (source / "readme.txt").read_text(encoding="utf-8")
if not re.search(r"^Stable tag: " + re.escape(version) + r"$", readme, re.MULTILINE):
    raise SystemExit("Stable tag und Plugin-Version stimmen nicht überein.")
output = root / "dist" / (slug + "-" + version + ".zip")
output.parent.mkdir(exist_ok=True)
files = sorted(path for path in source.rglob("*") if path.is_file())
allowed_suffixes = {".php", ".js", ".css", ".txt", ".md", ".pot", ".po", ".mo", ".json"}
with zipfile.ZipFile(output, "w", zipfile.ZIP_DEFLATED) as archive:
    for path in files:
        if path.is_symlink() or (path.suffix not in allowed_suffixes and path.name != "LICENSE"):
            raise SystemExit("Unerwartete Plugin-Datei: " + str(path.relative_to(source)))
        item = zipfile.ZipInfo(str(Path(slug) / path.relative_to(source)), (2026, 1, 1, 0, 0, 0))
        item.external_attr = 0o100644 << 16
        item.compress_type = zipfile.ZIP_DEFLATED
        archive.writestr(item, path.read_bytes())
with zipfile.ZipFile(output) as archive:
    if archive.testzip() is not None:
        raise SystemExit("ZIP-Prüfung fehlgeschlagen.")
digest = hashlib.sha256(output.read_bytes()).hexdigest()
output.with_suffix(".zip.sha256").write_text(digest + "  " + output.name + "\n", encoding="utf-8")
print(str(output))
print("SHA256: " + digest)
