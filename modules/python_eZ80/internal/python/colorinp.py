"""Evo OS 7.1.0.4421 frozen colorinp.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x565f28.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x565f34
class color_input:
    'color_input interface from colorinp (Evo 7.1).'

    # Frozen raw-code descriptor: 0x565f64
    def __init__(self, pin=None):
        'Calculator API: colorinp.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565f4c
    def __enter__(self):
        'Calculator API: colorinp.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x565f88
    def color_number(self):
        'Calculator API: colorinp.color_number (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565fac
    def red(self):
        'Calculator API: colorinp.red (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565f7c
    def blue(self):
        'Calculator API: colorinp.blue (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565fa0
    def green(self):
        'Calculator API: colorinp.green (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565f94
    def gray(self):
        'Calculator API: colorinp.gray (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565fb8
    def release(self):
        'Calculator API: colorinp.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565f58
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: colorinp.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
