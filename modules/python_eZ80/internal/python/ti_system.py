"""TI calculator keyboard, display, OS lists and timing.

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""


def escape():
    """Return whether Clear was pressed, then reset that flag. Guide p. 75."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def wait_key():
    """Wait for a key and return its keycode, including modifier keys. Guide p. 135."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def recall_list(name):
    """Return an OS list: 1-6 or its custom uppercase name. Guide p. 118."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def store_list(name, values):
    """Store a Python list of up to 100 elements in an OS list. Guide p. 127."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def recall_RegEQ():
    """Return the regression equation previously computed in the OS. Guide p. 119."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def sleep(seconds):
    """Pause for the given number of seconds. Guide p. 124."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def wait(*args):
    """Timing helper exported by ti_system. Guide p. 156."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def disp_at(row, *args):
    """Display text: (row, col, text[, align, color, background]) or (row, text[, align, color, background]). Guide pp. 69-70."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def disp_clr(row=None):
    """Clear the text screen, or one row when supplied. Guide p. 71."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def disp_wait():
    """Display the screen and wait for Clear. Guide p. 73."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def disp_cursor(state=1):
    """Show (1) or hide (0) the Shell cursor. Guide p. 72."""
    raise NotImplementedError("This API is provided by the TI calculator.")
