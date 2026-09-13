"""Evo OS 7.1.0.4421 frozen speaker.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x56684c.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566858
class speaker:
    'speaker interface from speaker (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566888
    def __init__(self, pin):
        'Calculator API: speaker.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566870
    def __enter__(self):
        'Calculator API: speaker.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566894
    def note(self, note=None, dur=None, tempo=None):
        'Calculator API: speaker.note (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5668ac
    def tone(self, freq=None, dur=None, tempo=None):
        'Calculator API: speaker.tone (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5668a0
    def release(self):
        'Calculator API: speaker.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56687c
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: speaker.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
