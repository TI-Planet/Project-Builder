"""Evo OS 7.1.0.4421 frozen vibmotor.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x5678fc.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x567908
class vibration_motor:
    'vibration_motor interface from vibmotor (Evo 7.1).'

    # Frozen raw-code descriptor: 0x567938
    def __init__(self, pin):
        'Calculator API: vibmotor.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x567920
    def __enter__(self):
        'Calculator API: vibmotor.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x567974
    def set(self, pwm):
        'Calculator API: vibmotor.set (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x567950
    def off(self):
        'Calculator API: vibmotor.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56795c
    def on(self):
        'Calculator API: vibmotor.on (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x567968
    def release(self):
        'Calculator API: vibmotor.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56792c
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: vibmotor.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
