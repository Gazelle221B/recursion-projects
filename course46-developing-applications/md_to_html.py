import sys

import markdown


def main(argv):
    if len(argv) != 3:
        print("Usage: python3 md_to_html.py <input.md> <output.html>")
        return 1

    input_path, output_path = argv[1], argv[2]
    try:
        with open(input_path, "r", encoding="utf-8") as f:
            md_text = f.read()
    except FileNotFoundError:
        print("Input file not found")
        return 1

    html = markdown.markdown(md_text)

    with open(output_path, "w", encoding="utf-8") as f:
        f.write(html)
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
