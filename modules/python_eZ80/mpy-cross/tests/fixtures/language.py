import math

LARGE_INTEGER = 123456789012345678901234567890
TEXT = "été / 日本語 / 😀"


def outer(x):
    def inner(y=3):
        return x + y
    return inner


class Counter:
    def __init__(self, value):
        self.value = value

    def tick(self):
        self.value += 1
        return self.value


values = [outer(n)(2) for n in range(10)]
print(TEXT, LARGE_INTEGER, values, Counter(40).tick())
