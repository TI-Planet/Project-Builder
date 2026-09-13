"""TI image add-on: archived images and RGB pixels.

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""


def load_image(name):
    """Load the case-sensitive name of an archived Python image AppVar. Guide p. 34."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def show_image(x, y):
    """Display the loaded image with its upper-left corner at (x, y). Guide p. 34."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def clear_image(x=0, y=0, w=320, h=210, color=(255, 255, 255)):
    """Clear the screen, or (x, y, w, h) with an optional RGB tuple. Guide p. 35."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def get_pixel(x, y):
    """Return the pixel color as an (r, g, b) tuple. Guide p. 35."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def set_pixel(x, y, color):
    """Set a pixel using an (r, g, b) tuple. Guide p. 35."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def show_screen():
    """Keep the drawing visible until Clear is pressed. Guide p. 35."""
    raise NotImplementedError("This API is provided by the TI calculator.")


version = ""  # Version string exposed by the Evo module menu.
