"""Evo OS 7.1.0.4421 frozen conservo.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x565fc4.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x565fd0
class continuous_servo:
    'continuous_servo interface from conservo (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566000
    def __init__(self, pin):
        'Calculator API: conservo.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565fe8
    def __enter__(self):
        'Calculator API: conservo.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566030
    def set_cw(self, speed, time=None):
        'Calculator API: conservo.set_cw (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566024
    def set_ccw(self, speed, time=None):
        'Calculator API: conservo.set_ccw (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56603c
    def stop(self):
        'Calculator API: conservo.stop (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566018
    def release(self):
        'Calculator API: conservo.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565ff4
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: conservo.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
