"""Evo OS 7.1.0.4421 frozen temperat.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566930.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x56693c
class temperature:
    'temperature interface from temperat (Evo 7.1).'

    # Frozen raw-code descriptor: 0x56696c
    def __init__(self, pin, opt=None):
        'Calculator API: temperat.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566954
    def __enter__(self):
        'Calculator API: temperat.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566984
    def measurement(self):
        'Calculator API: temperat.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566990
    def release(self):
        'Calculator API: temperat.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566960
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: temperat.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
