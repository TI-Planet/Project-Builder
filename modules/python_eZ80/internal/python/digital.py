"""Evo OS 7.1.0.4421 frozen digital.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x5660cc.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x5660d8
class digital:
    'digital interface from digital (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566108
    def __init__(self, pin):
        'Calculator API: digital.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5660f0
    def __enter__(self):
        'Calculator API: digital.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566120
    def measurement(self):
        'Calculator API: digital.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566150
    def set(self, val):
        'Calculator API: digital.set (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566138
    def on(self):
        'Calculator API: digital.on (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56612c
    def off(self):
        'Calculator API: digital.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566144
    def release(self):
        'Calculator API: digital.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5660fc
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: digital.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
