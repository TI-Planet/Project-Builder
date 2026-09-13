"""Evo OS 7.1.0.4421 frozen rgb.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566600.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x56660c
class rgb:
    'rgb interface from rgb (Evo 7.1).'

    # Frozen raw-code descriptor: 0x56663c
    def __init__(self, r, g, b):
        'Calculator API: rgb.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566624
    def __enter__(self):
        'Calculator API: rgb.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566654
    def off(self):
        'Calculator API: rgb.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56666c
    def rgb(self, r, g, b):
        'Calculator API: rgb.rgb (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566648
    def blink(self, rate=None, secs=None):
        'Calculator API: rgb.blink (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566660
    def release(self):
        'Calculator API: rgb.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566630
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: rgb.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
