"""Evo OS 7.1.0.4421 frozen lightlvl.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566210.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x56621c
class light_level:
    'light_level interface from lightlvl (Evo 7.1).'

    # Frozen raw-code descriptor: 0x56624c
    def __init__(self, pin):
        'Calculator API: lightlvl.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566234
    def __enter__(self):
        'Calculator API: lightlvl.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566264
    def measurement(self):
        'Calculator API: lightlvl.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566270
    def range(self, min=None, max=None):
        'Calculator API: lightlvl.range (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56627c
    def release(self):
        'Calculator API: lightlvl.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566240
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: lightlvl.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
