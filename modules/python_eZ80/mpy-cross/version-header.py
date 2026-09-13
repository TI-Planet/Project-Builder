#!/usr/bin/env python3
"""Replace upstream's git/cwd/current-date detection with locked build metadata."""
import os
from pathlib import Path
import sys

version = os.environ["MPY_CROSS_VERSION"]
content = (
    "// Deterministic Project Builder compiler metadata.\n"
    f'#define MICROPY_GIT_TAG "v{version}"\n'
    f'#define MICROPY_GIT_HASH "{os.environ["MPY_CROSS_COMMIT"]}"\n'
    '#define MICROPY_BUILD_DATE "1970-01-01"\n'
)
if version == "1.9.3":
    for name, value in zip(("MAJOR", "MINOR", "MICRO"), version.split(".")):
        content += f"#define MICROPY_VERSION_{name} ({value})\n"
    content += f'#define MICROPY_VERSION_STRING "{version}"\n'
destination = Path(sys.argv[1])
if not destination.exists() or destination.read_text() != content:
    destination.write_text(content)
