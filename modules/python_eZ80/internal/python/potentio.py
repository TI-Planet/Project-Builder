"""Evo OS 7.1.0.4421 frozen potentio.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566408.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566414
class potentiometer:
    'potentiometer interface from potentio (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566444
    def __init__(self, pin):
        'Calculator API: potentio.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56642c
    def __enter__(self):
        'Calculator API: potentio.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x56645c
    def measurement(self):
        'Calculator API: potentio.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566468
    def range(self, min=None, max=None):
        'Calculator API: potentio.range (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566474
    def release(self):
        'Calculator API: potentio.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566438
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: potentio.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
