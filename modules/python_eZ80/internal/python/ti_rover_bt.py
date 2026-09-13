"""Evo OS 7.1.0.4421 frozen ti_rover_bt.py.

Analysis-only definitions, not a hardware driver.
Package SHA-256: c706d8555e41325fbd6f9c03a9fc87a04c6e1d0875031020d1bbc9b6a08584df
Root raw-code descriptor: 0x566de0.
TI-Rover Bluetooth module version 1.0.0.65.
Signatures and defaults decoded from the supplied firmware.
"""


# Frozen raw-code descriptor: 0x56765c
def get_locale():
    'Calculator API: ti_rover_bt.get_locale (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x566dec
class RoverError(Exception):
    'RoverError interface from ti_rover_bt (Evo 7.1).'

    # Frozen raw-code descriptor: 0x566df8
    def __init__(self, value):
        'Calculator API: ti_rover_bt.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")


# Frozen raw-code descriptor: 0x5677dc
def ver():
    'Calculator API: ti_rover_bt.ver (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5677e8
def version():
    'Calculator API: ti_rover_bt.version (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567680
def isConnected():
    'Calculator API: ti_rover_bt.isConnected (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x56774c
def roverCommand(cmd, waitForMoveTime=0.5):
    'Calculator API: ti_rover_bt.roverCommand (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567758
def roverCommand_noWait(cmd):
    'Calculator API: ti_rover_bt.roverCommand_noWait (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567770
def roverStatus():
    'Calculator API: ti_rover_bt.roverStatus (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567764
def roverLongStatus():
    'Calculator API: ti_rover_bt.roverLongStatus (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567578
def delay(seconds):
    'Calculator API: ti_rover_bt.delay (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x56768c
def isti():
    'Calculator API: ti_rover_bt.isti (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567800
def who():
    'Calculator API: ti_rover_bt.who (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5677f4
def what():
    'Calculator API: ti_rover_bt.what (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567524
def about():
    'Calculator API: ti_rover_bt.about (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x56777c
def rover_version():
    'Calculator API: ti_rover_bt.rover_version (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676e0
def pen_up():
    'Raise the Rover pen. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676d4
def pen_down():
    'Lower the Rover pen. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5677a0
def set_speed(speed):
    'Calculator API: ti_rover_bt.set_speed (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567788
def set_acceleration(acc):
    'Calculator API: ti_rover_bt.set_acceleration (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567644
def forward(dist=1, speed=None, acc=None):
    'Move forward by dist, optionally specifying speed and acceleration. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567530
def backward(dist=1, speed=None, acc=None):
    'Move backward by dist, optionally specifying speed and acceleration. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567650
def forward_time(time=5, speed=None):
    'Calculator API: ti_rover_bt.forward_time (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x56753c
def backward_time(time=5, speed=None):
    'Calculator API: ti_rover_bt.backward_time (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676a4
def left(angle=90.0, units='DEGREES'):
    'Calculator API: ti_rover_bt.left (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567740
def right(angle=90.0, units='DEGREES'):
    'Calculator API: ti_rover_bt.right (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567674
def grid_origin():
    'Calculator API: ti_rover_bt.grid_origin (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676ec
def position(x, y, heading=0.0, units='DEGREES'):
    'Set x/y position, optionally heading and angle units. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567668
def grid_m_unit(value=0.01):
    'Calculator API: ti_rover_bt.grid_m_unit (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5677ac
def stay(time=30):
    'Calculator API: ti_rover_bt.stay (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5677b8
def to_angle(angle=90.0, units='DEGREES'):
    'Calculator API: ti_rover_bt.to_angle (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5677d0
def to_xy(x=0.0, y=0.0):
    'Calculator API: ti_rover_bt.to_xy (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5677c4
def to_polar(r=0.0, t=0.0, units='DEGREES'):
    'Calculator API: ti_rover_bt.to_polar (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676b0
def motor_left(speed=0, time=5):
    'Calculator API: ti_rover_bt.motor_left (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676bc
def motor_right(speed=0, time=5):
    'Calculator API: ti_rover_bt.motor_right (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676c8
def motors(ldir='CCW', lspd=0, rdir='CW', rspd=0, time=5):
    'Calculator API: ti_rover_bt.motors (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x56756c
def color_rgb(r, g, b):
    'Calculator API: ti_rover_bt.color_rgb (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567554
def color_blink(frequency=None, time=None):
    'Calculator API: ti_rover_bt.color_blink (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567560
def color_off():
    'Calculator API: ti_rover_bt.color_off (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567794
def set_domain(newXMin=0, newXMax=10):
    'Set the domain for mathematical paths; defaults to 0 through 10. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567620
def drive_line(m, b):
    'Drive the line y = m*x + b within the selected domain. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x56762c
def drive_parabola(a, b, c):
    'Drive the parabola y = a*x**2 + b*x + c. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567608
def drive_cubic(a, b, c, d):
    'Drive the cubic y = a*x**3 + b*x**2 + c*x + d. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567638
def drive_sin(a, b, c, d):
    'Calculator API: ti_rover_bt.drive_sin (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5675fc
def drive_cos(a, b, c, d):
    'Calculator API: ti_rover_bt.drive_cos (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5675f0
def drive_circle(*args):
    'Drive a circle: drive_circle(radius) or drive_circle(radius, h, k). Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567614
def drive_ellipse(orientation, a, b, h=0, k=0):
    'Calculator API: ti_rover_bt.drive_ellipse (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567710
def ranger_measurement():
    'Calculator API: ti_rover_bt.ranger_measurement (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x567734
def ranger_time():
    'Calculator API: ti_rover_bt.ranger_time (Evo 7.1).'
    raise NotImplementedError("Provided by the calculator and connected hardware.")

# Frozen raw-code descriptor: 0x5676f8
class ranger:
    'ranger interface from ti_rover_bt (Evo 7.1).'

    # Frozen raw-code descriptor: 0x567704
    def __init__(self, port):
        'Calculator API: ti_rover_bt.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56771c
    def measurement(self):
        'Calculator API: ti_rover_bt.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x567728
    def measurement_time(self):
        'Calculator API: ti_rover_bt.measurement_time (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")


# Frozen raw-code descriptor: 0x567584
class digital:
    'digital interface from ti_rover_bt (Evo 7.1).'

    # Frozen raw-code descriptor: 0x567590
    def __init__(self, port):
        'Calculator API: ti_rover_bt.__init__ (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5675a8
    def measurement(self):
        'Calculator API: ti_rover_bt.measurement (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5675d8
    def set(self, val):
        'Calculator API: ti_rover_bt.set (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5675cc
    def pwm(self, freq, duty=None, time=None):
        'Set PWM using frequency first, then optional duty and time. Evo 7.1.'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5675e4
    def toggle(self, frequency=1, time=None):
        'Calculator API: ti_rover_bt.toggle (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x56759c
    def blink(self, frequency, time):
        'Calculator API: ti_rover_bt.blink (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5675b4
    def off(self):
        'Calculator API: ti_rover_bt.off (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")

    # Frozen raw-code descriptor: 0x5675c0
    def on(self):
        'Calculator API: ti_rover_bt.on (Evo 7.1).'
        raise NotImplementedError("Provided by the calculator and connected hardware.")


# Frozen raw-code descriptor: 0x567698
class led(digital):
    'led interface from ti_rover_bt (Evo 7.1).'


# Frozen raw-code descriptor: 0x567548
def battery_level():
    'Read the Rover battery level. Evo 7.1.'
    raise NotImplementedError("Provided by the calculator and connected hardware.")
