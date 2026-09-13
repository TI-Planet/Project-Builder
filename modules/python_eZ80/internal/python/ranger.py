"""Evo OS 7.1.0.4421 frozen ranger.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566504.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566510
class ranger:
    'ranger interface from ranger (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566540
    def __init__(self, pin, echo=None):
        'Calculator API: ranger.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566528
    def __enter__(self):
        'Calculator API: ranger.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566558
    def measurement(self):
        'Calculator API: ranger.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566564
    def measurement_time(self):
        'Calculator API: ranger.measurement_time (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566570
    def release(self):
        'Calculator API: ranger.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566534
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: ranger.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
