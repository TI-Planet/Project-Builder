"""Evo OS 7.1.0.4421 frozen timer.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x567818.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x567824
class hub_time:
    'hub_time interface from timer (Evo 7.1).'

    # Frozen raw-code descriptor: 0x567854
    def __init__(self):
        'Calculator API: timer.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56783c
    def __enter__(self):
        'Calculator API: timer.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x567860
    def measurement(self):
        'Calculator API: timer.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x567878
    def reset_time(self):
        'Calculator API: timer.reset_time (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56786c
    def release(self):
        'Calculator API: timer.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x567848
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: timer.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
