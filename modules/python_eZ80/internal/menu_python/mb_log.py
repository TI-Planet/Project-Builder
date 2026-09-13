"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class data_log:
    @staticmethod
    def set_duration(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def set_range(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def set_sensor(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def start(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")
