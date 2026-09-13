"""Evo OS 7.1.0.4421 frozen power.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566480.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x56648c
class power:
    'power interface from power (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5664bc
    def __init__(self, pin):
        'Calculator API: power.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5664a4
    def __enter__(self):
        'Calculator API: power.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5664f8
    def set(self, val):
        'Calculator API: power.set (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5664e0
    def on(self):
        'Calculator API: power.on (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5664d4
    def off(self):
        'Calculator API: power.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5664ec
    def release(self):
        'Calculator API: power.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5664b0
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: power.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
