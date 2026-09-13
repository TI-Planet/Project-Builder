"""Evo OS 7.1.0.4421 frozen thermist.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x56699c.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x5669a8
class thermistor:
    'thermistor interface from thermist (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5669d8
    def __init__(self, pin):
        'Calculator API: thermist.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5669c0
    def __enter__(self):
        'Calculator API: thermist.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5669fc
    def measurement(self):
        'Calculator API: thermist.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5669f0
    def calibrate(self, c1, c2, c3, r):
        'Calculator API: thermist.calibrate (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566a08
    def release(self):
        'Calculator API: thermist.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5669cc
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: thermist.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
