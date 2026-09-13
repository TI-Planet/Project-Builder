"""TI drawing add-on: shapes, text, pen and drawing window.

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""


def draw_line(x1, y1, x2, y2):
    """Draw a line between two points. Guide p. 29."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def draw_rect(x, y, w, h):
    """Draw a rectangle border. Guide p. 29."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def fill_rect(x, y, w, h):
    """Fill a rectangle with the current pen color. Guide p. 30."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def draw_circle(x, y, r):
    """Draw a circle border. Guide p. 30."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def fill_circle(x, y, r):
    """Fill a circle with the current pen color. Guide p. 30."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def draw_text(x, y, text):
    """Draw text at the upper-left pixel position (x, y). Guide p. 30."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def draw_poly(xlist, ylist):
    """Draw connected polygon edges. Guide p. 30."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def fill_poly(xlist, ylist):
    """Fill the polygon defined by equal-length coordinate lists. Guide p. 30."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def plot_xy(x, y, shape):
    """Draw marker 1-13 at (x, y). Guide p. 30 calls this poly_xy; the supplied ti_draw.menu uses plot_xy."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def clear():
    """Clear the Shell drawing area before drawing. Guide p. 31."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def clear_rect(x, y, w, h, color=(255, 255, 255)):
    """Clear a pixel rectangle, optionally using an RGB tuple. Guide p. 31."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def set_color(r, g, b):
    """Set pen RGB components in the range 0-255. Guide p. 31."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def set_pen(size="thin", style="solid"):
    """Set thin/medium/thick pen and solid/dotted/dashed style. Guide p. 31."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def set_window(xmin, xmax, ymin, ymax):
    """Set drawing coordinates; the default window is (0, 319, 0, 209). Guide p. 31."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def show_draw():
    """Display the drawing and wait for Clear. Guide pp. 31-32."""
    raise NotImplementedError("This API is provided by the TI calculator.")


version = ""  # Version string exposed by the Evo module menu.
