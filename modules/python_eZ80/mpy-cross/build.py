#!/usr/bin/env python3
"""Build pinned browser mpy-cross modules in scratch; never modify upstream trees."""
import argparse
import gzip
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tarfile
import tempfile

HERE = Path(__file__).resolve().parent
INITIAL_MEMORY = 32 * 1024 * 1024
MAXIMUM_MEMORY = 64 * 1024 * 1024
STACK_SIZE = 1024 * 1024
LINK_FLAGS = [
    "-Os", "-sASSERTIONS=0", "-sMODULARIZE=1", "-sEXPORT_NAME=createMpyCross",
    "-sFORCE_FILESYSTEM=1", '-sEXPORTED_RUNTIME_METHODS=["FS","callMain"]',
    "-sINVOKE_RUN=0", "-sEXIT_RUNTIME=0", "-sALLOW_MEMORY_GROWTH=1",
    f"-sINITIAL_MEMORY={INITIAL_MEMORY}", f"-sMAXIMUM_MEMORY={MAXIMUM_MEMORY}",
    f"-sSTACK_SIZE={STACK_SIZE}", "-sABORTING_MALLOC=0",
    "-sENVIRONMENT=web,worker,node",
]


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def run(command, **kwargs):
    subprocess.run(command, check=True, **kwargs)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--cache-dir", type=Path, required=True,
                        help="Directory containing/downloaded micropython-vVERSION.tar.gz archives")
    parser.add_argument("--build-dir", type=Path,
                        help="New or empty scratch directory; defaults to a new temporary directory")
    parser.add_argument("--output-dir", type=Path, default=HERE)
    parser.add_argument("--native", action="store_true",
                        help="Also build native reference executables in the scratch directory")
    args = parser.parse_args()
    lock = json.loads((HERE / "sources.json").read_text())
    version_line = subprocess.check_output(["emcc", "--version"], text=True).splitlines()[0]
    match = re.search(r"\) (\d+\.\d+\.\d+) \(", version_line)
    if not match or match.group(1) != lock["emscripten"]:
        parser.error(f"Emscripten {lock['emscripten']} required; got {version_line}")
    work = (args.build_dir or Path(tempfile.mkdtemp(prefix="pb-mpy-cross-"))).resolve()
    if work.exists() and any(work.iterdir()):
        parser.error("--build-dir must be empty to avoid mixing compiler builds")
    work.mkdir(parents=True, exist_ok=True)
    args.cache_dir.mkdir(parents=True, exist_ok=True)
    args.output_dir.mkdir(parents=True, exist_ok=True)
    manifest = {
        "schema": 1,
        "emscripten": version_line,
        "factory": "createMpyCross",
        "runtimeMethods": ["FS", "callMain"],
        "memory": {"initialBytes": INITIAL_MEMORY, "maximumBytes": MAXIMUM_MEMORY,
                   "stackBytes": STACK_SIZE, "compilerArenaBytes": 16 * 1024 * 1024,
                   "automaticGarbageCollection": False},
        "buildInputs": {},
        "targets": {},
    }
    for relative in ("build.py", "sources.json", "version-header.py", "wasm-gccollect.c"):
        manifest["buildInputs"][relative] = digest(HERE / relative)
    for target, source in lock["targets"].items():
        version = source["version"]
        archive = args.cache_dir / f"micropython-v{version}.tar.gz"
        if not archive.exists():
            run(["curl", "--fail", "--location", "--retry", "2", "--connect-timeout", "10",
                 "--max-time", "120", source["url"], "-o", str(archive)])
        if digest(archive) != source["sha256"]:
            raise RuntimeError(f"Source archive checksum mismatch: {archive}")
        target_work = work / target
        target_work.mkdir()
        with tarfile.open(archive) as package:
            # Python's data filter rejects traversal, escaping links and devices.
            package.extractall(target_work, filter="data")
        tree = target_work / f"micropython-{version}"
        patch_name = f"patches/micropython-{version}.patch"
        manifest["buildInputs"][patch_name] = digest(HERE / patch_name)
        run(["patch", "--batch", "--forward", "-p1", "-i", str(HERE / patch_name)], cwd=tree)
        shutil.copyfile(HERE / "version-header.py", tree / "py/makeversionhdr.py")
        shutil.copyfile(HERE / "wasm-gccollect.c", tree / "mpy-cross/wasm-gccollect.c")
        env = dict(os.environ, MPY_CROSS_VERSION=version, MPY_CROSS_COMMIT=source["commit"],
                   SOURCE_DATE_EPOCH="0", PYTHONHASHSEED="0", LC_ALL="C")
        common = ["make", "-j8", f"PYTHON={sys.executable}",
                  "CFLAGS_EXTRA=-Wno-unused-but-set-variable"]
        filename = f"{target}-mpy-cross"
        with (target_work / "wasm-build.log").open("w") as log:
            run(common + ["CC=emcc", "BUILD=build-wasm", f"PROG={filename}.js",
                          "STRIP=true", "SIZE=true", "SRC_C=main.c wasm-gccollect.c",
                          "LDFLAGS_ARCH=", "LDFLAGS_EXTRA=" + " ".join(LINK_FLAGS)],
                cwd=tree / "mpy-cross", env=env, stdout=log, stderr=subprocess.STDOUT)
        result = dict(source, files={})
        for suffix in ("js", "wasm"):
            destination = args.output_dir / f"{filename}.{suffix}"
            shutil.copyfile(tree / "mpy-cross" / destination.name, destination)
            payload = destination.read_bytes()
            result["files"][suffix] = {"name": destination.name, "bytes": len(payload),
                                       "gzipBytes": len(gzip.compress(payload, mtime=0)),
                                       "sha256": digest(destination)}
        manifest["targets"][target] = result
        if args.native:
            with (target_work / "native-build.log").open("w") as log:
                run(common + ["CC=cc", "BUILD=build-native", f"PROG={filename}-native",
                              "CFLAGS_EXTRA=-D__unix__ -Wno-unused-but-set-variable"],
                    cwd=tree / "mpy-cross", env=env, stdout=log, stderr=subprocess.STDOUT)
            shutil.copyfile(tree / "mpy-cross" / f"{filename}-native", work / f"{filename}-native")
            (work / f"{filename}-native").chmod(0o755)
        print(f"Built {target}: {result['files']}", flush=True)
    # Stable identifier covers source locks, patches, settings and build scripts.
    manifest["buildId"] = hashlib.sha256(json.dumps(
        manifest["buildInputs"], sort_keys=True, separators=(",", ":")
    ).encode()).hexdigest()
    (args.output_dir / "versions.json").write_text(json.dumps(manifest, indent=2) + "\n")
    print(f"Scratch sources/logs: {work}")


if __name__ == "__main__":
    main()
