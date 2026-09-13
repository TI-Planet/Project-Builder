"""Evo OS 7.1.0.4421 frozen magnetic.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566300.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x56630c
class magnetic:
    'magnetic interface from magnetic (Evo 7.1).'

    # Frozen raw-code descriptor: 0x56633c
    def __init__(self, pin):
        'Calculator API: magnetic.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566324
    def __enter__(self):
        'Calculator API: magnetic.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566384
    def trigger(self, t=None):
        'Calculator API: magnetic.trigger (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566360
    def magnet_close(self):
        'Calculator API: magnetic.magnet_close (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56636c
    def measurement(self):
        'Calculator API: magnetic.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566378
    def release(self):
        'Calculator API: magnetic.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566330
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: magnetic.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
