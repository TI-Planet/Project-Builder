"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class SoundEvent:
    LOUD = None

    QUIET = None


class microphone:
    @staticmethod
    def current_event(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def is_event(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def set_threshold(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def sound_level(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def was_event(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")
