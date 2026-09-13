"""Evo OS 7.1.0.4421 frozen vernier.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x567884.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x567890
class vernier:
    'vernier interface from vernier (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5678c0
    def __init__(self, pin, type=None):
        'Calculator API: vernier.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5678a8
    def __enter__(self):
        'Calculator API: vernier.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5678e4
    def measurement(self):
        'Calculator API: vernier.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5678d8
    def calibrate(self, a, b, c=None, r=None):
        'Calculator API: vernier.calibrate (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5678f0
    def release(self):
        'Calculator API: vernier.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5678b4
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: vernier.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
