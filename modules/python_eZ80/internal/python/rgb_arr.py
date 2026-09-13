"""Evo OS 7.1.0.4421 frozen rgb_arr.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566678.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x566684
class rgb_array:
    'rgb_array interface from rgb_arr (Evo 7.1).'

    # Frozen raw-code descriptor: 0x5666b4
    def __init__(self, options=None):
        'Calculator API: rgb_arr.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56669c
    def __enter__(self):
        'Calculator API: rgb_arr.__enter__ (Evo 7.1).'
        return self

    # Frozen raw-code descriptor: 0x566774
    def set(self, pix, r, g, b):
        'Calculator API: rgb_arr.set (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566780
    def set_all(self, r, g, b):
        'Calculator API: rgb_arr.set_all (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5666c0
    def all_off(self):
        'Calculator API: rgb_arr.all_off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566714
    def measurement(self):
        'Calculator API: rgb_arr.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56672c
    def pattern(self, pattern, r=None, g=None, b=None):
        'Calculator API: rgb_arr.pattern (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5667a4
    def value(self, pix):
        'Calculator API: rgb_arr.value (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566720
    def off(self, pix):
        'Calculator API: rgb_arr.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5666d8
    def draw(self):
        'Calculator API: rgb_arr.draw (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5666cc
    def count(self, value):
        'Calculator API: rgb_arr.count (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566744
    def power(self, value):
        'Calculator API: rgb_arr.power (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566708
    def left(self, count):
        'Calculator API: rgb_arr.left (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56675c
    def right(self, count):
        'Calculator API: rgb_arr.right (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566768
    def rotate(self, count):
        'Calculator API: rgb_arr.rotate (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56678c
    def shift(self, count):
        'Calculator API: rgb_arr.shift (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5666fc
    def interval(self, val):
        'Calculator API: rgb_arr.interval (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5666e4
    def duration(self, val):
        'Calculator API: rgb_arr.duration (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566798
    def stop(self):
        'Calculator API: rgb_arr.stop (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5666f0
    def intensity(self, value):
        'Calculator API: rgb_arr.intensity (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566738
    def pixel(self, pix, colorname, value):
        'Calculator API: rgb_arr.pixel (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x566750
    def release(self):
        'Calculator API: rgb_arr.release (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5666a8
    def __exit__(self, exc_type, exc_val, exc_tb):
        'Calculator API: rgb_arr.__exit__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")
