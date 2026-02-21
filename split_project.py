#!/usr/bin/env python3
"""
split_project.py
----------------
Scans the project, splits all non-ignored files into NUM_PARTS chunks,
writes them to copper/part1.txt … copper/partN.txt, and generates
copper/project_tree.txt.

The copper/ folder is wiped clean on every run.

Usage:
    python3 split_project.py
"""

import os
import shutil
from pathlib import Path

# ─── Configuration (edit freely) ────────────────────────────────────────────

NUM_PARTS = 10  # number of output part files

# Directories to skip entirely (relative names, not full paths)
IGNORE_DIRS = {
    "copper",           # our own output folder
    "vendor",
    "node_modules",
    "storage",
    ".git",
    ".github",
    ".idea",
    ".vscode",
    "__pycache__",
    "bootstrap/cache",
}

# Specific file names to skip (exact match, any directory)
IGNORE_FILES = {
    ".env",
    ".env.example",
    ".env.backup",
    ".env.testing",
    ".DS_Store",
    "Thumbs.db",
    "package-lock.json",
    "yarn.lock",
    "composer.lock",
    "split_project.py",  # skip this script itself
}

# File extensions to skip
IGNORE_EXTENSIONS = {
    ".log",
    ".cache",
    # images
    ".png", ".jpg", ".jpeg", ".gif", ".svg", ".ico", ".webp", ".bmp",
    # fonts
    ".woff", ".woff2", ".ttf", ".eot", ".otf",
    # binaries / archives
    ".pdf", ".zip", ".tar", ".gz", ".rar", ".7z",
    # compiled / generated
    ".map", ".min.js", ".min.css",
    # databases
    ".sqlite", ".db",
}

SEPARATOR = "\n" + "─" * 60 + "\n"

# ─── Project root & script-dir auto-ignore ───────────────────────────────────

def _find_project_root() -> Path:
    """
    Walk up from this script's location looking for a Laravel/PHP project
    marker (artisan or composer.json).  Falls back to the script's own parent.
    """
    candidate = Path(__file__).resolve().parent
    for _ in range(5):
        if (candidate / "artisan").exists() or (candidate / "composer.json").exists():
            return candidate
        candidate = candidate.parent
    return Path(__file__).resolve().parent


SCRIPT_DIR   = Path(__file__).resolve().parent
PROJECT_ROOT = _find_project_root()

# If the script lives in a subfolder (e.g. tools/), auto-ignore that folder
if SCRIPT_DIR != PROJECT_ROOT:
    try:
        _rel = SCRIPT_DIR.relative_to(PROJECT_ROOT)
        if _rel.parts:
            IGNORE_DIRS.add(_rel.parts[0])
            print(f"[auto-ignore] script subfolder: {_rel.parts[0]}/")
    except ValueError:
        pass

OUTPUT_DIR = PROJECT_ROOT / "copper"
TREE_FILE  = OUTPUT_DIR / "project_tree.txt"

# ─── Helpers ────────────────────────────────────────────────────────────────

def should_ignore_dir(rel_parts: tuple) -> bool:
    """Return True if any component of the path is in IGNORE_DIRS."""
    return any(part in IGNORE_DIRS for part in rel_parts)


def should_ignore_file(path: Path) -> bool:
    if path.name in IGNORE_FILES:
        return True
    if path.suffix.lower() in IGNORE_EXTENSIONS:
        return True
    return False


def collect_files() -> list[Path]:
    """Walk the project tree and return all files that pass the ignore filters."""
    collected = []
    for dirpath, dirnames, filenames in os.walk(PROJECT_ROOT):
        current  = Path(dirpath)
        rel      = current.relative_to(PROJECT_ROOT)
        rel_parts = rel.parts  # () for root, ('app',), ('app','Http'), …

        # Prune ignored directories in-place so os.walk skips them
        dirnames[:] = [
            d for d in dirnames
            if d not in IGNORE_DIRS
            and not should_ignore_dir(rel_parts + (d,))
        ]
        dirnames.sort()

        for filename in sorted(filenames):
            fpath = current / filename
            if should_ignore_file(fpath):
                continue
            collected.append(fpath)

    return collected


def read_file_safe(path: Path) -> str:
    """Read a file as UTF-8, falling back to latin-1, returning a placeholder on error."""
    for enc in ("utf-8", "latin-1"):
        try:
            return path.read_text(encoding=enc)
        except UnicodeDecodeError:
            continue
        except Exception as exc:
            return f"[Could not read file: {exc}]"
    return "[Binary or unreadable file – skipped]"


# ─── Output writers ──────────────────────────────────────────────────────────

def reset_output_dir() -> None:
    """Delete and recreate the copper/ folder."""
    if OUTPUT_DIR.exists():
        shutil.rmtree(OUTPUT_DIR)
    OUTPUT_DIR.mkdir(parents=True)


def write_parts(files: list[Path]) -> None:
    total = len(files)
    if total == 0:
        print("No files found – nothing to write.")
        return

    chunk_size = max(1, -(-total // NUM_PARTS))  # ceiling division

    # Create empty part files up-front
    for i in range(1, NUM_PARTS + 1):
        (OUTPUT_DIR / f"part{i}.txt").write_text("", encoding="utf-8")

    for idx, fpath in enumerate(files):
        part_number = min(idx // chunk_size + 1, NUM_PARTS)
        out_file    = OUTPUT_DIR / f"part{part_number}.txt"

        rel_path = fpath.relative_to(PROJECT_ROOT)
        content  = read_file_safe(fpath)

        block = f"#{rel_path}\n\n{content}"
        with out_file.open("a", encoding="utf-8") as f:
            f.write(block)
            f.write(SEPARATOR)

    # Report
    for i in range(1, NUM_PARTS + 1):
        out_file = OUTPUT_DIR / f"part{i}.txt"
        size_kb  = out_file.stat().st_size / 1024
        print(f"  part{i}.txt  ({size_kb:.1f} KB)")


# ─── Project tree ────────────────────────────────────────────────────────────

def build_tree(root: Path, prefix: str = "") -> list[str]:
    """
    Recursively build a tree structure, skipping IGNORE_DIRS.
    Returns a list of lines (children only; the caller already printed root's name).
    """
    lines = []

    try:
        entries = sorted(root.iterdir(), key=lambda p: (p.is_file(), p.name.lower()))
    except PermissionError:
        return lines

    entries = [
        e for e in entries
        if not (e.is_dir() and e.name in IGNORE_DIRS)
        and not (e.is_file() and should_ignore_file(e))
    ]

    for i, entry in enumerate(entries):
        connector    = "└── " if i == len(entries) - 1 else "├── "
        child_prefix = prefix + ("    " if i == len(entries) - 1 else "│   ")
        if entry.is_dir():
            lines.append(prefix + connector + entry.name + "/")
            lines.extend(build_tree(entry, child_prefix))
        else:
            lines.append(prefix + connector + entry.name)

    return lines


def write_tree() -> None:
    lines = [str(PROJECT_ROOT.name) + "/"] + build_tree(PROJECT_ROOT)
    TREE_FILE.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"  project_tree.txt  ({TREE_FILE.stat().st_size / 1024:.1f} KB)")


# ─── .gitignore ──────────────────────────────────────────────────────────────

def ensure_gitignore() -> None:
    """Add /copper/ to .gitignore if it isn't there already."""
    gitignore = PROJECT_ROOT / ".gitignore"
    entry     = "/copper/"

    if gitignore.exists():
        existing = gitignore.read_text(encoding="utf-8")
        if entry in existing:
            return
        with gitignore.open("a", encoding="utf-8") as f:
            f.write(f"\n# Split-script output\n{entry}\n")
    else:
        gitignore.write_text(f"# Split-script output\n{entry}\n", encoding="utf-8")

    print(f"  added '{entry}' to .gitignore")


# ─── Entry point ─────────────────────────────────────────────────────────────

def main() -> None:
    print(f"Project root : {PROJECT_ROOT}")
    print(f"Output dir   : {OUTPUT_DIR}")
    print(f"Splitting into {NUM_PARTS} parts …\n")

    print("Ensuring .gitignore:")
    ensure_gitignore()

    print("\nResetting copper/ folder …")
    reset_output_dir()

    files = collect_files()
    print(f"Files collected : {len(files)}\n")

    print("Writing part files:")
    write_parts(files)

    print("\nGenerating project tree:")
    write_tree()

    print("\nDone.")


if __name__ == "__main__":
    main()
