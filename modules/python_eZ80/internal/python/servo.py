"""Evo OS 7.1.0.4421 frozen servo.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x5667b0.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x5667bc
class servo:
    'servo interface from servo (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5667ec
    def __init__(self, pin):
        'Calculator API: servo.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5667d4
    def __enter__(self):
        'Calculator API: servo.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566810
    def set_position(self, pos):
        'Calculator API: servo.set_position (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56681c
    def zero(self):
        'Calculator API: servo.zero (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566804
    def release(self):
        'Calculator API: servo.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5667e0
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: servo.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
