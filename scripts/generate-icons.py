"""Generate Bathys' icon in every format from one set of shapes.

Run from the repository root:  python3 scripts/generate-icons.py  (needs Pillow)

The design lives in SHAPES, on a 64 x 64 grid: a deep-blue tile, an open
book, and three bars below it that narrow and darken with depth (a book's
one rating is how deep it has been read). The same coordinates produce the
SVG (drawn by browsers) and the PNG and ICO files (drawn here with Pillow),
so every format matches. Edit SHAPES, rerun, and commit the outputs.

Outputs, all in public/:
  icon.svg              the icon, used on the coming-soon page
  favicon.svg           the browser-tab icon (same artwork)
  favicon.ico           16, 32, and 48 px, for browsers that want ICO
  apple-touch-icon.png  180 px, square corners (iOS rounds them itself)
"""

from pathlib import Path

from PIL import Image, ImageDraw

GRID = 64
PUBLIC = Path("public")

TILE = "#12304d"
PAPER = "#f4efe4"
DEPTH = ["#7cc4e6", "#4f9fc8", "#2f7aa8"]  # shallow to deep

SHAPES = [
    # Open book: two pages meeting at the spine (x = 32).
    ("polygon", PAPER, [(10, 14), (30, 18), (30, 35), (10, 31)]),
    ("polygon", PAPER, [(34, 18), (54, 14), (54, 31), (34, 35)]),
    # Depth bars: (x, y, width, height), each one narrower and darker.
    ("bar", DEPTH[0], (12, 40, 40, 5)),
    ("bar", DEPTH[1], (18, 48, 28, 5)),
    ("bar", DEPTH[2], (24, 56, 16, 4)),
]


def svg(rounded: bool = True) -> str:
    radius = 14 if rounded else 0
    parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {GRID} {GRID}">',
        f'<rect width="{GRID}" height="{GRID}" rx="{radius}" fill="{TILE}"/>',
    ]
    for kind, color, geometry in SHAPES:
        if kind == "polygon":
            points = " ".join(f"{x},{y}" for x, y in geometry)
            parts.append(f'<polygon points="{points}" fill="{color}"/>')
        else:
            x, y, w, h = geometry
            parts.append(
                f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{h / 2}" fill="{color}"/>'
            )
    parts.append("</svg>")
    return "\n".join(parts) + "\n"


def bitmap(size: int, rounded: bool = True) -> Image.Image:
    """Draw at 8x and scale down, which smooths the edges (anti-aliasing)."""
    scale = size * 8 / GRID
    big = Image.new("RGBA", (size * 8, size * 8), (0, 0, 0, 0))
    draw = ImageDraw.Draw(big)
    radius = 14 * scale if rounded else 0
    draw.rounded_rectangle([0, 0, size * 8 - 1, size * 8 - 1], radius=radius, fill=TILE)
    for kind, color, geometry in SHAPES:
        if kind == "polygon":
            draw.polygon([(x * scale, y * scale) for x, y in geometry], fill=color)
        else:
            x, y, w, h = geometry
            draw.rounded_rectangle(
                [x * scale, y * scale, (x + w) * scale, (y + h) * scale],
                radius=h * scale / 2,
                fill=color,
            )
    return big.resize((size, size), Image.Resampling.LANCZOS)


def main() -> None:
    (PUBLIC / "icon.svg").write_text(svg())
    (PUBLIC / "favicon.svg").write_text(svg())
    bitmap(180, rounded=False).convert("RGB").save(PUBLIC / "apple-touch-icon.png")
    bitmap(48).save(PUBLIC / "favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)])
    print("Wrote icon.svg, favicon.svg, favicon.ico, apple-touch-icon.png in public/")


if __name__ == "__main__":
    main()
