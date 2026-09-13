"""TI-Innovator built-in RGB output (BLNKSND example p. 52).

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""


def rgb(r, g, b):
    """Set RGB light components. Guide p. 52."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def blink(frequency, time):
    """Blink the RGB light. Guide p. 52."""
    raise NotImplementedError("This API is provided by the TI calculator.")


# Frozen raw-code descriptor: see Evo 7.1 color.py module table.
def off():
    """Turn off the Hub RGB light. Present in the Evo 7.1 frozen color module."""
    raise NotImplementedError("This API is provided by the TI calculator.")
