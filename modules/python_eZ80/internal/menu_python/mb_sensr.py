"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class accelerometer:
    @staticmethod
    def current_gesture(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_values(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_x(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_y(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_z(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def is_gesture(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def magnitude(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def was_gesture(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")


class compass:
    @staticmethod
    def calibrate(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def clear_calibration(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_field_strength(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_x(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_y(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def get_z(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def heading(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def is_calibrated(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")


def temperature(*args, **kwargs):
    raise NotImplementedError("Provided by the calculator module.")
