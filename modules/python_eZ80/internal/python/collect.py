"""Evo OS 7.1.0.4421 frozen collect.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x565e20.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x565eec
class ihsensors:
    'ihsensors interface from collect (Evo 7.1).'

    DHT = 'dht'
    LOUDNESS = 'loudness'
    MAGNETIC = 'magnetic'
    POTENTIOMETER = 'potentiometer'
    TEMPERATURE = 'temperature'
    RANGER = 'ranger'
    LIGHTLEVEL = 'lightlevel'
    MOISTURE = 'moisture'
    VERNIER = 'vernier'
    ANALOG = 'analog.in'
    DIGITAL = 'digital.in'
    THERMISTOR = 'thermistor'
    BBPORT = 'bbport.in'
    BRIGHTNESS = 'brightness'
    SENSORS = ('dht', 'loudness', 'magnetic', 'potentiometer', 'temperature', 'ranger', 'lightlevel', 'moisture', 'vernier', 'analog.in', 'digital.in', 'thermistor', 'bbport.in', 'brightness')


# Frozen raw-code descriptor: 0x565e2c
class collect:
    'collect interface from collect (Evo 7.1).'

    # Frozen raw-code descriptor: 0x565e98
    def done(self):
        'Calculator API: collect.done (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565e5c
    def __init__(self, *args):
        'Calculator API: collect.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565ebc
    def set_sensors(self, *args):
        'Calculator API: collect.set_sensors (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565ec8
    def set_time(self, time=10):
        'Calculator API: collect.set_time (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565eb0
    def set_rate(self, rate=4):
        'Calculator API: collect.set_rate (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565ed4
    def set_wait(self, wait=True):
        'Calculator API: collect.set_wait (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565ee0
    def start(self, wait=None):
        'Calculator API: collect.start (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565e44
    def __enter__(self):
        'Calculator API: collect.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x565e50
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: collect.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x565ea4
    def measurements(self, sensor, opt=None):
        'Calculator API: collect.measurements (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
