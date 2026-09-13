"""Evo OS 7.1.0.4421 frozen loudness.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566288.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566294
class loudness:
    'loudness interface from loudness (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5662c4
    def __init__(self, pin):
        'Calculator API: loudness.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5662ac
    def __enter__(self):
        'Calculator API: loudness.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5662dc
    def measurement(self):
        'Calculator API: loudness.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5662e8
    def range(self, min=None, max=None):
        'Calculator API: loudness.range (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5662f4
    def release(self):
        'Calculator API: loudness.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5662b8
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: loudness.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
