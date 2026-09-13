'TI Turtle 2.0.0 API (not desktop Python turtle). Analysis-only definitions.'

from ti_system import escape


class Turtle:
    """TI Turtle object; Getting Started Guide p. 3, TURTLE menu version 2.0.0."""

    def __init__(self):
        'Create a TI turtle with its grid background. Guide p. 3.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def forward(self, distance):
        'Move forward by a distance in pixels. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def backward(self, distance):
        'Move backward in pixels. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def right(self, degrees):
        'Turn right in degrees. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def left(self, degrees):
        'Turn left in degrees. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def goto(self, x, y=None):
        'Move to x,y or a coordinate tuple; tuple form in Guide p. 17. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def done(self):
        'Keep the drawing visible; place at the end of the program. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def fillcolor(self, r, g, b):
        'Set the fill RGB color, 0-255 per component. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def begin_fill(self):
        'Start a filled shape. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def end_fill(self):
        'Finish and fill the shape. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def circle(self, radius, arc=360):
        'Draw a circle or optional arc in degrees. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def dot(self, diameter):
        'Draw a filled dot. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def write(self, text):
        'Write text at the turtle position. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def penup(self):
        'Lift the pen. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def pendown(self):
        'Lower the pen. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def pencolor(self, r, g, b):
        'Set the pen RGB color, 0-255 per component. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def pensize(self, size):
        'Set pen size 1-4. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def clear(self):
        'Clear the drawing. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def hideturtle(self):
        'Hide the turtle cursor. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def showturtle(self):
        'Show the turtle cursor. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def hidegrid(self):
        'Hide the grid background. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def speed(self, value):
        'Set speed 0-10. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def home(self):
        'Return to the origin. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def setheading(self, degrees):
        'Set the heading in degrees. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def xcor(self):
        'Return the x coordinate. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def ycor(self):
        'Return the y coordinate. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def pos(self):
        'Return the coordinate pair. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")

    def heading(self):
        'Return the heading in degrees. Source: TURTLE.8xv menu, Turtle Getting Started Guide.'
        raise NotImplementedError("This API is provided by the TI calculator.")
