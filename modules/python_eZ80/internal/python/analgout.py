"""Evo OS 7.1.0.4421 frozen analgout.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x565c88.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x565c94
class analog_out:
    'analog_out interface from analgout (Evo 7.1).'

    # Frozen raw-code descriptor: 0x565cc4
    def __init__(self, pin):
        'Calculator API: analgout.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565cac
    def __enter__(self):
        'Calculator API: analgout.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x565d0c
    def set(self, pwm):
        'Calculator API: analgout.set (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565ce8
    def off(self):
        'Calculator API: analgout.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565cf4
    def on(self):
        'Calculator API: analgout.on (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565d00
    def release(self):
        'Calculator API: analgout.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565cb8
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: analgout.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
