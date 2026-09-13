"""Evo OS 7.1.0.4421 frozen led.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x56615c.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566168
class led:
    'led interface from led (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566198
    def __init__(self, pin):
        'Calculator API: led.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566180
    def __enter__(self):
        'Calculator API: led.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5661c8
    def on(self):
        'Calculator API: led.on (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5661bc
    def off(self):
        'Calculator API: led.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5661b0
    def blink(self, rate=None, secs=None):
        'Calculator API: led.blink (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5661d4
    def release(self):
        'Calculator API: led.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56618c
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: led.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
