"""Evo OS 7.1.0.4421 frozen squarewv.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x5668b8.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x5668c4
class squarewave:
    'squarewave interface from squarewv (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5668f4
    def __init__(self, pin):
        'Calculator API: squarewv.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5668dc
    def __enter__(self):
        'Calculator API: squarewv.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566924
    def set(self, freq, duty=None, t=None):
        'Calculator API: squarewv.set (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56690c
    def off(self):
        'Calculator API: squarewv.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566918
    def release(self):
        'Calculator API: squarewv.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5668e8
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: squarewv.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
