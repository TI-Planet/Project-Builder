"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class music:
    @staticmethod
    def pitch(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def play(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def set_tempo(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def set_volume(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")
