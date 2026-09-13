'Legacy TI graphics API. Source: https://tiplanet.org/forum/viewtopic.php?f=41&t=23791#PY55C1'

import math
import sys
from time import sleep

class Color:
    """RGB color constants from exploration screenshot W7h2RQs."""
    BLACK = (0, 0, 0)
    BLUE = (0, 0, 255)
    CYAN = (0, 255, 255)
    GREEN = (0, 255, 0)
    MAGENTA = (255, 0, 255)
    RED = (255, 0, 0)
    WHITE = (255, 255, 255)
    YELLOW = (255, 255, 0)


class Style:
    """Pen style constants from exploration screenshot MDa6oBk."""
    SMOOTH = 0
    DOTTED = 1
    DASHED = 2


def cls():
    'Clear the screen. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def cursor(c=1):
    'Hide the text cursor with 1 (default), show it with 0. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def getPixel(x, y):
    'Return an RGB tuple for a pixel. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def setPixel(x, y, color):
    'Set a pixel using an RGB tuple. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def setColor(r, g=None, b=None):
    'Set pen RGB; the article also uses a single RGB tuple. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def setPen(size, style):
    'Set numeric pen size and style. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def drawLine(x1, y1, x2, y2):
    'Draw a line segment. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def drawPolyLine(points):
    'Draw connected segments through coordinate pairs. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def fillPolygon(points):
    'Fill a polygon from coordinate pairs. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def drawRect(x, y, w, h):
    'Draw a rectangle. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def fillRect(x, y, w, h):
    'Fill a rectangle. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def drawArc(x, y, w, h, t1, t2):
    'Draw an ellipse arc; angles are tenths of degrees. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def fillArc(x, y, w, h, t1, t2):
    'Fill an ellipse sector; angles are tenths of degrees. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def fillCircle(x, y, r):
    'Fill a circle. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def drawString(text, x, y):
    'Draw text at pixel coordinates. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def hsv_to_rgb(h, s, v):
    'Convert HSV to RGB; hue is in degrees. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def pushImage(x, y, w, h):
    'Save an image region; article example uses x,y,w,h. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def popImage():
    'Restore the saved image region. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def drawImage(*args):
    'Image helper; the article does not establish its signature. Source: TI-Planet ti_graphics exploration (2020).'
    raise NotImplementedError("This API is provided by the TI calculator.")

def _grcmd(*args):
    'Internal graphics transport helper; signature unspecified in the exploration capture.'
    raise NotImplementedError("This API is provided by the TI calculator.")

def _grif(*args):
    'Internal graphics transport helper; signature unspecified in the exploration capture.'
    raise NotImplementedError("This API is provided by the TI calculator.")

def _handshake(*args):
    'Internal graphics transport helper; signature unspecified in the exploration capture.'
    raise NotImplementedError("This API is provided by the TI calculator.")

def _read(*args):
    'Internal graphics transport helper; signature unspecified in the exploration capture.'
    raise NotImplementedError("This API is provided by the TI calculator.")

def _write(*args):
    'Internal graphics transport helper; signature unspecified in the exploration capture.'
    raise NotImplementedError("This API is provided by the TI calculator.")
