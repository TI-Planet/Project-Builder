"""Analysis-only names from bundled Evo menus; not a runtime implementation."""

class radio:
    @staticmethod
    def config(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def off(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def on(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def receive(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def receive_number(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def send(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")

    @staticmethod
    def send_number(*args, **kwargs):
        raise NotImplementedError("Provided by the calculator module.")
