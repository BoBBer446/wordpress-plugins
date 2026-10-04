"""Prepare an SVN release tree without uploading anything."""
from pathlib import Path
import shutil
import sys
import re

root = Path(__file__).resolve().parents[1]
slug = sys.argv[1] if len(sys.argv) == 2 else ""
if not re.fullmatch(r"[a-z][a-z0-9-]+", slug):
    raise SystemExit("Aufruf: python3 tools/prepare_svn.py <plugin-ordner>")
plugin = root / "plugins" / slug
main = plugin / (slug + ".php")
if not main.is_file():
    raise SystemExit("Plugin-Hauptdatei fehlt.")
version = re.search(r"^ \* Version: ([0-9.]+)$", main.read_text(), re.MULTILINE)
if not version:
    raise SystemExit("Version fehlt.")
target = root / "dist" / ("svn-" + slug + "-" + version.group(1))
if target.exists():
    raise SystemExit("Vorbereitungsverzeichnis existiert bereits: " + str(target))
shutil.copytree(plugin, target / "trunk")
shutil.copytree(plugin, target / "tags" / version.group(1))
assets = root / "directory-assets" / slug
if assets.is_dir():
    shutil.copytree(assets, target / "assets")
else:
    (target / "assets").mkdir()
print(str(target))
print("Nur lokale Vorbereitung. Vor dem Upload den von WordPress.org vergebenen Slug prüfen.")
