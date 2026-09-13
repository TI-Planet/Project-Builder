"""TI-Innovator built-in sound output (menus p. 25).

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""


def tone(frequency, time, tempo=None):
    """Play a frequency for a duration, optionally with tempo. Guide p. 25."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def note(note, time, tempo=None):
    """Play a named note for a duration, optionally with tempo. Guide p. 25."""
    raise NotImplementedError("This API is provided by the TI calculator.")
