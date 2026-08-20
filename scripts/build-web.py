#!/usr/bin/env python3
"""Assemble web-src templates into static HTML in web/.

Templates use two directives:
    <!--include NAME-->      inline web-src/partials/NAME.html
    {{key}}                  substitute a var

Vars are declared in an optional block at the top of a page:
    <!--vars
    camera: whitianga
    pageName: Whitianga - CoroLive
    -->

{{gitRev}} is always available and holds the short commit the build came from
(suffixed "-dirty" when the tree had uncommitted changes).

Everything else in web-src/ (img/, libs/, sitemap.xml) is copied across as-is.

web/ is generated in full and is not tracked by git - never edit it by hand.
Run this after editing anything under web-src/.
"""
import pathlib
import re
import shutil
import subprocess

SRC = pathlib.Path(__file__).resolve().parent.parent / "web-src"
OUT = SRC.parent / "web"

# Not deployed: partials are inlined into pages, README documents this dir.
NOT_ASSETS = {"partials", "README.md"}


def git_rev():
    """Short commit this build came from, so the footer can show what's deployed."""
    def git(*args):
        return subprocess.run(
            ("git",) + args, cwd=SRC.parent, capture_output=True, text=True, check=True
        ).stdout.strip()

    try:
        rev = git("rev-parse", "--short", "HEAD")
        return rev + "-dirty" if git("status", "--porcelain") else rev
    except (OSError, subprocess.CalledProcessError):
        return "unknown"


# Available to every page on top of its own <!--vars--> block.
GLOBALS = {"gitRev": git_rev()}


def render(text, ctx, depth=0):
    assert depth < 10, "include loop"
    text = re.sub(
        r"<!--include (\S+)-->",
        lambda m: render((SRC / "partials" / (m.group(1) + ".html")).read_text(encoding="utf-8"), ctx, depth + 1),
        text,
    )
    return re.sub(r"\{\{(\w+)\}\}", lambda m: ctx[m.group(1)], text)


OUT.mkdir(exist_ok=True)

for page in sorted(SRC.glob("*.html")):
    raw = page.read_text(encoding="utf-8")
    ctx = dict(GLOBALS)
    block = re.match(r"<!--vars\n(.*?)-->\n?", raw, re.S)
    if block:
        ctx.update(
            (k.strip(), v.strip())
            for k, v in (line.split(":", 1) for line in block.group(1).splitlines() if line.strip())
        )
        raw = raw[block.end():]
    (OUT / page.name).write_text(render(raw, ctx), encoding="utf-8")
    print("built", page.name)

# Static assets: everything that isn't a page template, a partial, or the README.
for item in sorted(SRC.iterdir()):
    if item.name in NOT_ASSETS or item.suffix == ".html":
        continue
    dest = OUT / item.name
    if item.is_dir():
        shutil.rmtree(dest, ignore_errors=True)
        shutil.copytree(item, dest)
    else:
        shutil.copy2(item, dest)
    print("copied", item.name)
