"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class Image:
    def __init__(self, *args, **kwargs):
        pass
    ANGRY = None

    ASLEEP = None

    BUTTERFLY = None

    CHESSBOARD = None

    CONFUSED = None

    COW = None

    DIAMOND = None

    DIAMOND_SMALL = None

    DUCK = None

    FABULOUS = None

    HAPPY = None

    HEART = None

    HEART_SMALL = None

    HOUSE = None

    MEH = None

    MUSIC_CROTCHET = None

    MUSIC_QUAVER = None

    MUSIC_QUAVERS = None

    NO = None

    PACMAN = None

    PITCHFORK = None

    RABBIT = None

    ROLLERSKATE = None

    SAD = None

    SILLY = None

    SMILE = None

    SQUARE = None

    SQUARE_SMALL = None

    SURPRISED = None

    TARGET = None

    TORTOISE = None

    TRIANGLE = None

    TRIANGLE_LEFT = None

    TSHIRT = None

    XMAS = None

    YES = None


class display:
    @staticmethod
    def clear(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def read_light_level(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def scroll(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def set_pixel(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def show(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")
