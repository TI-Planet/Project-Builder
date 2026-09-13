"""Evo OS 7.1.0.4421 frozen light.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x5661e0.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566204
def on():
    'Calculator API: light.on (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5661f8
def off():
    'Calculator API: light.off (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5661ec
def blink(rate=None, secs=None):
    'Calculator API: light.blink (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")
