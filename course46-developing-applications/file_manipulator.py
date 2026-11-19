import sys


def read_file(path):
    with open(path, "r", encoding="utf-8") as f:
        return f.read()


def write_file(path, contents):
    with open(path, "w", encoding="utf-8") as f:
        f.write(contents)


def do_reverse(input_path, output_path):
    contents = read_file(input_path)
    write_file(output_path, contents[::-1])


def do_copy(input_path, output_path):
    contents = read_file(input_path)
    write_file(output_path, contents)


def do_duplicate_contents(input_path, n):
    contents = read_file(input_path)
    write_file(input_path, contents * n)


def do_replace_string(input_path, needle, newstring):
    contents = read_file(input_path)
    write_file(input_path, contents.replace(needle, newstring))


def main(argv):
    if len(argv) < 2:
        print("Usage: python3 file_manipulator.py <subcommand> [args]")
        return 1

    subcommand = argv[1]
    try:
        if subcommand == "reverse":
            if len(argv) != 4:
                print("Usage: reverse <input> <output>")
                return 1
            do_reverse(argv[2], argv[3])
        elif subcommand == "copy":
            if len(argv) != 4:
                print("Usage: copy <input> <output>")
                return 1
            do_copy(argv[2], argv[3])
        elif subcommand == "duplicate-contents":
            if len(argv) != 4:
                print("Usage: duplicate-contents <input> <n>")
                return 1
            try:
                n = int(argv[3])
            except ValueError:
                print("n must be an integer")
                return 1
            do_duplicate_contents(argv[2], n)
        elif subcommand == "replace-string":
            if len(argv) != 5:
                print("Usage: replace-string <input> <needle> <newstring>")
                return 1
            do_replace_string(argv[2], argv[3], argv[4])
        else:
            print(f"Unknown subcommand: {subcommand}")
            return 1
    except FileNotFoundError:
        print("File not found")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
