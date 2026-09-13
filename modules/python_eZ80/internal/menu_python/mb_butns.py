"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class button_a:
    @staticmethod
    def get_presses(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def is_pressed(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def was_pressed(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")


class button_b:
    @staticmethod
    def get_presses(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def is_pressed(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def was_pressed(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")


class pin_logo:
    @staticmethod
    def is_touched(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")
