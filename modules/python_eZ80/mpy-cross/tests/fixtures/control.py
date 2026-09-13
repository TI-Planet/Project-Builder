def generator(values):
    for value in values:
        try:
            yield 100 // value
        except ZeroDivisionError:
            yield None
        finally:
            value = -1


def use_context(manager, *args, **kwargs):
    with manager as resource:
        while resource:
            if len(args) > 3:
                break
            resource = resource.read(8)
        else:
            return [i * i for i in range(5) if i % 2]
    return {key: value for key, value in kwargs.items()}


print(list(generator([1, 0, 2])))
