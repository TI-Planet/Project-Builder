"""Evo OS 7.1.0.4421 frozen bbport.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x565d90.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x565d9c
class bb_port:
    'bb_port interface from bbport (Evo 7.1).'

    # Frozen raw-code descriptor: 0x565dcc
    def __init__(self, mask=None):
        'Calculator API: bbport.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565db4
    def __enter__(self):
        'Calculator API: bbport.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x565dd8
    def read_port(self, mask=None):
        'Calculator API: bbport.read_port (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565df0
    def write_port(self, value, mask=None):
        'Calculator API: bbport.write_port (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565de4
    def release(self):
        'Calculator API: bbport.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565dc0
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: bbport.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
