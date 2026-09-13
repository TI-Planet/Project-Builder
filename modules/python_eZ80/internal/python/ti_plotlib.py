"""TI plotting: setup, drawing, regression and window properties.

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""

xmin = -10.0
xmax = 10.0
ymin = -6.56
ymax = 6.56
a = 0.0
b = 0.0


def cls():
    """Clear the plotting screen. Guide p. 65."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def grid(xscl=1.0, yscl=1.0, style="dot", color=(192, 192, 192)):
    """Draw a grid with dot/dash/solid/point style and optional RGB color. Guide pp. 87-88."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def window(xmin, xmax, ymin, ymax):
    """Define the plotting window before plotting. Guide p. 136."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def auto_window(xlist, ylist):
    """Fit the plotting window to the data. Guide p. 61."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def axes(mode="on"):
    """Draw axes: off, on, axes, or window. Guide p. 62."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def labels(xlabel, ylabel, x=12, y=2):
    """Label the axes at the given screen rows. Guide p. 98."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def title(text):
    """Set the centered plot title. Guide p. 130."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def show_plot():
    """Display the completed plot and wait for Clear. Guide p. 123."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def color(r, g, b):
    """Set subsequent plotting RGB color, components 0-255. Guide p. 66."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def scatter(xlist, ylist, mark="o"):
    """Plot points with o, +, x, or . markers. Guide p. 121."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def plot(x, y, mark="o"):
    """Plot a point or connected coordinate lists with a marker. Guide pp. 110-111."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def line(x1, y1, x2, y2, mode=""):
    """Draw a segment, or an arrow when mode is arrow. Guide p. 99."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def lin_reg(xlist, ylist, display="center", row=11):
    """Draw linear regression and update a (slope) and b (intercept). Guide p. 100."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pen(size="thin", style="solid"):
    """Set thin/medium/thick pen and solid/dot/dash style. Guide p. 108."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def text_at(row, text, align="left", clear=1):
    """Draw text on row 1-12; optional clear controls line clearing. Guide p. 129."""
    raise NotImplementedError("This API is provided by the TI calculator.")
