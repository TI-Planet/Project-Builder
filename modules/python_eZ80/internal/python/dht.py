"""Evo OS 7.1.0.4421 frozen dht.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566048.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566054
class dht:
    'dht interface from dht (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566084
    def __init__(self, pin, option=None):
        'Calculator API: dht.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56606c
    def __enter__(self):
        'Calculator API: dht.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x5660c0
    def temp_measurement(self):
        'Calculator API: dht.temp_measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56609c
    def humidity_measurement(self):
        'Calculator API: dht.humidity_measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5660b4
    def t_h_measurements(self):
        'Calculator API: dht.t_h_measurements (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5660a8
    def release(self):
        'Calculator API: dht.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566078
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: dht.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
