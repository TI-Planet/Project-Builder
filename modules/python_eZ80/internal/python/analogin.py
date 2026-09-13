"""Evo OS 7.1.0.4421 frozen analogin.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x565d18.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x565d24
class analog_in:
    'analog_in interface from analogin (Evo 7.1).'

    # Frozen raw-code descriptor: 0x565d54
    def __init__(self, pin):
        'Calculator API: analogin.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565d3c
    def __enter__(self):
        'Calculator API: analogin.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x565d6c
    def measurement(self):
        'Calculator API: analogin.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565d78
    def range(self, min=None, max=None):
        'Calculator API: analogin.range (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565d84
    def release(self):
        'Calculator API: analogin.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565d48
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: analogin.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
