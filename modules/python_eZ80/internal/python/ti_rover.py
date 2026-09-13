"""TI-Innovator Rover API (menu reference pp. 26-27).

Editor-only API definitions; not a calculator emulator.
Source: TI-PyAppPrgG_v570_EN.pdf (printed page numbers below).
"""

class tiroverException(Exception):
    """Rover API exception (guide p. 161)."""


def forward(distance, *options):
    """Move forward; optional distance unit, speed and speed unit. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def backward(distance, *options):
    """Move backward; optional distance unit, speed and speed unit. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def left(angle, unit="degrees"):
    """Turn left, optionally specifying angle units. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def right(angle, unit="degrees"):
    """Turn right, optionally specifying angle units. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def stay(seconds):
    """Remain in place for the given time. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def to_xy(x, y):
    """Move to the given coordinates. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def to_polar(r, theta):
    """Move to polar coordinates, theta in degrees. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def to_angle(angle):
    """Turn to the given heading. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def forward_time(time, *options):
    """Move forward for a duration, optionally with speed and unit. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def backward_time(time, *options):
    """Move backward for a duration, optionally with speed and unit. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def position(x, y, heading, *options):
    """Set position and heading, optionally specifying units. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def grid_m_unit(scale_value):
    """Set grid scale in meters. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def color_rgb(r, g, b):
    """Set the Rover RGB light (0-255 per component). Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def color_blink(frequency, time):
    """Blink the Rover light. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def motor_left(speed, time):
    """Run the left motor at a signed speed (-255 to 255). Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def motor_right(speed, time):
    """Run the right motor at a signed speed (-255 to 255). Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def motors(left_direction, left_speed, right_direction, right_speed, time):
    """Control both motors. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def stop():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def resume():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def disconnect_rv():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def wait_until_done():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def path_done():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def grid_origin():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def path_clear():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def zero_gyro():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def color_off():
    """Rover control/status helper. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def ranger_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def color_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def red_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def green_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def blue_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def gray_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def encoders_gyro_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def gyro_measurement():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def ranger_time():
    """Read a Rover sensor value. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_xythdrn():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_prev():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_eta():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pathlist_x():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pathlist_y():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pathlist_time():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pathlist_heading():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pathlist_distance():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pathlist_revs():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def pathlist_cmdnum():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_x():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_y():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_time():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_heading():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_distance():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")


def waypoint_revs():
    """Read Rover path or waypoint data. Guide p. 26."""
    raise NotImplementedError("This API is provided by the TI calculator.")
