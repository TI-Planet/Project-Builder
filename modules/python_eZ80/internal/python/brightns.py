"""TI-Innovator built-in brightness sensor (example p. 25).

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""


def range(minimum, maximum):
    """Set the brightness range, typically 0 to 100. Guide p. 25."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def measurement():
    """Read the brightness measurement. Guide pp. 25, 50."""
    raise NotImplementedError("This API is provided by the TI calculator.")
