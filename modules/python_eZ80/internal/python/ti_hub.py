"""TI-Innovator Hub low-level API (menus pp. 24-25; exports p. 159).

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""

class tihubException(Exception):
    """Hub API exception (guide p. 159)."""


def connect(obj, *args):
    """Connect a Hub object using its port/options. Guide p. 24."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def disconnect(obj, *args):
    """Disconnect a Hub object. Guide p. 24."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def set(obj, *args):
    """Set the value of a Hub object. Guide p. 24."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def read(obj, *args):
    """Read a Hub object. Guide p. 24."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def calibrate(obj, *args):
    """Calibrate a Hub object. Guide p. 24."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def range(obj, *args):
    """Set the range of a Hub object. Guide p. 24."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def version():
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def begin():
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def start():
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def about(*args):
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def isti(*args):
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def what(*args):
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def who(*args):
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def last_error(*args):
    """Hub information/control helper. Guide pp. 24, 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def get(*args):
    """Receive Hub data; detailed signature is not specified in this guide. Guide p. 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def send(command):
    """Send a command to the Hub. Guide p. 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def sleep(seconds):
    """Pause for the given number of seconds. Guide p. 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def wait(*args):
    """Wait helper; the guide does not specify its arguments. Guide p. 159."""
    raise NotImplementedError("This API is provided by the TI calculator.")
