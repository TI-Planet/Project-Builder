"""Evo OS 7.1.0.4421 frozen moisture.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566390.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x56639c
class moisture:
    'moisture interface from moisture (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5663cc
    def __init__(self, pin):
        'Calculator API: moisture.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5663b4
    def __enter__(self):
        'Calculator API: moisture.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5663e4
    def measurement(self):
        'Calculator API: moisture.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5663f0
    def range(self, min=None, max=None):
        'Calculator API: moisture.range (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5663fc
    def release(self):
        'Calculator API: moisture.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5663c0
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: moisture.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
