"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class Color:
    def __init__(self, *args, **kwargs):
        pass
    @staticmethod
    def rgb(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")


class NeoPixel:
    def __init__(self, *args, **kwargs):
        pass
    def __setitem__(self, index, value):
        pass
    @staticmethod
    def clear(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def show(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")


class color:
    @staticmethod
    def rgb(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")


pin0 = None

pin1 = None

pin13 = None

pin2 = None

pin8 = None
