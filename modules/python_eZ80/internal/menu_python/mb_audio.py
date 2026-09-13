"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class Sound:
    GIGGLE = None

    HAPPY = None

    HELLO = None

    MYSTERIOUS = None

    SAD = None

    SLIDE = None

    SOARING = None

    SPRING = None

    TWINKLE = None

    YAWN = None


class audio:
    @staticmethod
    def play(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def stop(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")
