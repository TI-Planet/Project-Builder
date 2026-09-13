#!/usr/bin/env python3
"""Use the pinned upstream MPY reader to check a host-width folding boundary."""
import importlib.util
from pathlib import Path
import sys

scratch, target, native_file, wasm_file = sys.argv[1:]
version = "1.9.3" if target == "ce" else "1.13"
source = Path(scratch) / target / ("micropython-" + version)
sys.path.insert(0, str(source / "py"))
spec = importlib.util.spec_from_file_location("mpy_tool", source / "tools/mpy-tool.py")
tool = importlib.util.module_from_spec(spec)
spec.loader.exec_module(tool)


def decode_assignment(filename):
    code = tool.read_mpy(filename)
    ip = code.ip
    opcode = code.bytecode[ip]
    ip += 1
    small_int_op, object_op, store_op = (0x14, 0x17, 0x24) if target == "ce" else (0x22, 0x23, 0x16)
    if opcode == small_int_op:
        value = -1 if code.bytecode[ip] & 0x40 else 0
    else:
        assert opcode == object_op, hex(opcode)
        value = 0
    while True:
        byte = code.bytecode[ip]
        ip += 1
        value = (value << 7) | (byte & 0x7f)
        if not byte & 0x80:
            break
    if opcode == object_op:
        value = code.objs[value]
    assert isinstance(value, int)
    assert code.bytecode[ip] == store_op
    name = code._unpack_qstr(ip + 1).str
    return name, value, bytes(code.bytecode[ip + 3:])


native = decode_assignment(native_file)
wasm = decode_assignment(wasm_file)
assert native == wasm, (native, wasm)
assert native[:2] == ("value", -1073741824)
print(f"{target}/boundary.py: upstream MPY reader confirms identical assignment and value")
