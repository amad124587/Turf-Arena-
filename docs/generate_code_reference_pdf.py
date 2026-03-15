from pathlib import Path
import textwrap


ROOT = Path(__file__).resolve().parents[1]
DOCS_DIR = ROOT / "docs"
OUTPUT_PDF = DOCS_DIR / "project-code-reference.pdf"

INCLUDE_GLOBS = [
    "src/**/*.vue",
    "src/**/*.js",
    "src/**/*.css",
    "backend/**/*.php",
]

EXCLUDE_PARTS = {
    "node_modules",
    "dist",
}

PAGE_WIDTH = 612
PAGE_HEIGHT = 792
LEFT_MARGIN = 36
TOP_MARGIN = 756
BOTTOM_MARGIN = 36
FONT_SIZE = 8
LEADING = 10
MAX_CHARS = 110


def collect_files():
    files = []
    for pattern in INCLUDE_GLOBS:
        for path in ROOT.glob(pattern):
            if not path.is_file():
                continue
            if any(part in EXCLUDE_PARTS for part in path.parts):
                continue
            files.append(path)
    return sorted({path for path in files}, key=lambda p: str(p.relative_to(ROOT)).lower())


def normalize_line(text):
    return text.replace("\t", "    ").rstrip("\n").replace("\r", "")


def build_lines(files):
    lines = [
        "TurfBooking Project Code Reference",
        "",
        "This PDF was generated automatically from the project code files.",
        "Order: serial number, file path, then source code.",
        "",
        "Included folders: src, backend",
        "Included code types: .vue, .js, .css, .php",
        "",
    ]

    for index, path in enumerate(files, start=1):
        rel = path.relative_to(ROOT).as_posix()
        lines.append("=" * 110)
        lines.append(f"{index}. {rel}")
        lines.append("=" * 110)
        lines.append("")
        try:
            content = path.read_text(encoding="utf-8")
        except UnicodeDecodeError:
            content = path.read_text(encoding="utf-8", errors="replace")

        for raw_line in content.splitlines():
            normalized = normalize_line(raw_line)
            wrapped = textwrap.wrap(
                normalized,
                width=MAX_CHARS,
                replace_whitespace=False,
                drop_whitespace=False,
                break_long_words=False,
                break_on_hyphens=False,
            )
            if wrapped:
                lines.extend(wrapped)
            else:
                lines.append("")
        lines.extend(["", "", ""])

    return lines


def paginate(lines):
    usable_height = TOP_MARGIN - BOTTOM_MARGIN
    lines_per_page = usable_height // LEADING
    pages = []
    for start in range(0, len(lines), lines_per_page):
        pages.append(lines[start:start + lines_per_page])
    return pages


def pdf_escape(text):
    return text.replace("\\", "\\\\").replace("(", "\\(").replace(")", "\\)")


def build_stream(page_lines):
    parts = ["BT", f"/F1 {FONT_SIZE} Tf", f"{LEADING} TL", f"{LEFT_MARGIN} {TOP_MARGIN} Td"]
    for line in page_lines:
        safe = pdf_escape(line)
        parts.append(f"({safe}) Tj")
        parts.append("T*")
    parts.append("ET")
    return "\n".join(parts).encode("latin-1", errors="replace")


def build_pdf(pages):
    objects = []

    def add_object(data):
        objects.append(data)
        return len(objects)

    font_id = add_object(b"<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>")
    placeholder_pages_id = add_object(b"<< /Type /Pages /Count 0 /Kids [] >>")

    page_object_ids = []
    page_entries = []

    for page_lines in pages:
        stream = build_stream(page_lines)
        content_id = add_object(
            b"<< /Length " + str(len(stream)).encode("ascii") + b" >>\nstream\n" + stream + b"\nendstream"
        )
        page_dict = (
            f"<< /Type /Page /Parent {placeholder_pages_id} 0 R /MediaBox [0 0 {PAGE_WIDTH} {PAGE_HEIGHT}] "
            f"/Resources << /Font << /F1 {font_id} 0 R >> >> /Contents {content_id} 0 R >>"
        ).encode("ascii")
        page_id = add_object(page_dict)
        page_object_ids.append(page_id)
        page_entries.append(f"{page_id} 0 R")

    pages_dict = f"<< /Type /Pages /Count {len(page_object_ids)} /Kids [{' '.join(page_entries)}] >>".encode("ascii")
    objects[placeholder_pages_id - 1] = pages_dict

    catalog_id = add_object(f"<< /Type /Catalog /Pages {placeholder_pages_id} 0 R >>".encode("ascii"))

    pdf = bytearray(b"%PDF-1.4\n%\xe2\xe3\xcf\xd3\n")
    offsets = [0]
    for idx, obj in enumerate(objects, start=1):
        offsets.append(len(pdf))
        pdf.extend(f"{idx} 0 obj\n".encode("ascii"))
        pdf.extend(obj)
        pdf.extend(b"\nendobj\n")

    xref_start = len(pdf)
    pdf.extend(f"xref\n0 {len(objects) + 1}\n".encode("ascii"))
    pdf.extend(b"0000000000 65535 f \n")
    for offset in offsets[1:]:
        pdf.extend(f"{offset:010d} 00000 n \n".encode("ascii"))

    trailer = (
        f"trailer\n<< /Size {len(objects) + 1} /Root {catalog_id} 0 R >>\nstartxref\n{xref_start}\n%%EOF\n"
    ).encode("ascii")
    pdf.extend(trailer)
    return pdf


def main():
    files = collect_files()
    lines = build_lines(files)
    pages = paginate(lines)
    pdf_bytes = build_pdf(pages)
    OUTPUT_PDF.write_bytes(pdf_bytes)
    print(f"Generated: {OUTPUT_PDF}")
    print(f"Files included: {len(files)}")
    print(f"Pages: {len(pages)}")


if __name__ == "__main__":
    main()
