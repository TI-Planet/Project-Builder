"""Evo OS 7.1.0.4421 frozen relay.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x56657c.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566588
class relay:
    'relay interface from relay (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5665b8
    def __init__(self, pin):
        'Calculator API: relay.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5665a0
    def __enter__(self):
        'Calculator API: relay.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5665e8
    def on(self, time=None):
        'Calculator API: relay.on (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5665dc
    def off(self, time=None):
        'Calculator API: relay.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5665f4
    def release(self):
        'Calculator API: relay.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5665ac
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: relay.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
