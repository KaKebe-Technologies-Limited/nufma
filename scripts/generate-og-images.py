#!/usr/bin/env python3
"""
Generate branded 1200x630 Open Graph / Twitter share images for NUFA.

Each card = source photo (cover-cropped) + dark scrim + NUFA lockup +
kicker + headline + brand rule + site URL.

Usage:  python scripts/generate-og-images.py
Output: assets/images/og/og-*.jpg   (also .png master kept out — jpg only)

Requires Pillow (pip install pillow). Fonts: Arial + Georgia (Windows).
Re-run any time the source photos or copy below change.
"""
import os
from PIL import Image, ImageDraw, ImageFont, ImageFilter

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
IMG = os.path.join(ROOT, "assets", "images")
OUT = os.path.join(IMG, "og")
os.makedirs(OUT, exist_ok=True)

W, H = 1200, 630
BRAND = (245, 130, 31)        # --brand-500  #F5821F
BRAND_LT = (255, 176, 110)    # lighter orange for kicker
INK = (24, 18, 12)            # near --ink
WHITE = (248, 245, 238)

FONTS = r"C:\Windows\Fonts"
def font(name, size):
    return ImageFont.truetype(os.path.join(FONTS, name), size)

F_WORD   = font("arialbd.ttf", 34)   # "NUFA" lockup
F_ORG    = font("arial.ttf",   15)   # small caps under lockup
F_KICK   = font("arialbd.ttf", 22)   # kicker
F_HEAD   = font("georgiab.ttf", 62)  # headline
F_HEAD_S = font("georgiab.ttf", 52)  # headline (long)
F_URL    = font("arial.ttf",   20)   # footer url

LOGO = os.path.join(IMG, "logo", "nufa-logo.png")

# page key -> (source image relative to assets/images, kicker, headline)
CARDS = {
    "home": (
        "production/on-set-action.webp",
        "HOME OF NORTHERN STORYTELLERS",
        "Celebrating the spirit of Northern cinema",
    ),
    "default": (
        "nufa3.webp",
        "NORTHERN UGANDA FILMMAKERS ASSOCIATION",
        "Uniting, training & celebrating Northern storytellers",
    ),
    "about": (
        "production/crew-monitor-check.webp",
        "ABOUT NUFA",
        "Our story, mission and the team behind NUFA",
    ),
    "awards": (
        "slider/hero/hero-slide-3.webp",
        "THE NUFA AWARDS",
        "Honouring the best of Northern Uganda film",
    ),
    "gallery": (
        "slider/hero/hero-slide-1.webp",
        "GALLERY",
        "Inside the NUFA Awards \u2014 the red carpet & the gala",
    ),
    "news": (
        "nufa1.webp",
        "NEWS & STORIES",
        "Recaps, training and the road to NUFA Awards 2027",
    ),
    "partners": (
        "production/karamoja-shoot-1.webp",
        "PARTNERS & SPONSORS",
        "Back Northern Uganda's growing film industry",
    ),
    "contact": (
        "slider/hero/hero-slide-2.webp",
        "CONTACT NUFA",
        "Membership, training, press & partnership enquiries",
    ),
}

SITE = "nufawards.com"


def cover(src, w, h):
    im = src.convert("RGB")
    sr, tr = im.width / im.height, w / h
    if sr > tr:
        nh = h
        nw = int(h * sr)
    else:
        nw = w
        nh = int(w / sr)
    im = im.resize((nw, nh), Image.LANCZOS)
    x = (nw - w) // 2
    y = (nh - h) // 2
    # bias vertical crop slightly toward faces (upper third)
    y = max(0, y - int(h * 0.08))
    return im.crop((x, y, x + w, y + h))


def scrim(im):
    """Darken overall + heavy bottom-left gradient for text."""
    base = Image.new("RGB", (W, H), INK)
    im = Image.blend(im, base, 0.32)

    grad = Image.new("L", (1, H), 0)
    for yy in range(H):
        t = yy / H
        # transparent top -> opaque bottom, easing in from ~36%
        a = 0 if t < 0.36 else int(((t - 0.36) / 0.64) ** 1.3 * 248)
        grad.putpixel((0, yy), a)
    grad = grad.resize((W, H))
    shade = Image.new("RGB", (W, H), (12, 9, 6))
    im = Image.composite(shade, im, grad)

    # subtle left vignette
    lg = Image.new("L", (W, 1), 0)
    for xx in range(W):
        t = xx / W
        a = int(max(0.0, (0.55 - t) / 0.55) ** 1.6 * 120)
        lg.putpixel((xx, 0), a)
    lg = lg.resize((W, H))
    im = Image.composite(Image.new("RGB", (W, H), (10, 8, 5)), im, lg)
    return im


def draw_card(key, src_rel, kicker, headline):
    src = Image.open(os.path.join(IMG, src_rel))
    im = scrim(cover(src, W, H))
    im = im.filter(ImageFilter.UnsharpMask(radius=1.2, percent=60, threshold=2))
    d = ImageDraw.Draw(im)

    M = 64  # margin

    # --- top-left lockup: logo + NUFA + org line ---
    try:
        logo = Image.open(LOGO).convert("RGBA")
        ls = 52
        logo = logo.resize((ls, ls), Image.LANCZOS)
        im.paste(logo, (M, M - 4), logo)
        tx = M + ls + 14
    except FileNotFoundError:
        tx = M
    d.text((tx, M - 2), "NUFA", font=F_WORD, fill=WHITE)
    d.text((tx + 2, M + 34), "NORTHERN UGANDA FILMMAKERS ASSOCIATION",
           font=F_ORG, fill=(214, 205, 194))

    # --- bottom block ---
    # kicker
    ky = 372
    d.text((M, ky), _track(kicker, 2), font=F_KICK, fill=BRAND_LT)

    # headline (wrap)
    hf = F_HEAD
    lines = _wrap(d, headline, hf, W - M * 2 - 40)
    if len(lines) > 3:
        hf = F_HEAD_S
        lines = _wrap(d, headline, hf, W - M * 2 - 40)
    hy = ky + 44
    for ln in lines:
        d.text((M, hy), ln, font=hf, fill=WHITE)
        hy += hf.size + 8

    # brand rule
    ry = hy + 10
    d.rectangle([M, ry, M + 96, ry + 5], fill=BRAND)

    # url
    d.text((M, ry + 22), SITE, font=F_URL, fill=(224, 216, 206))

    out = os.path.join(OUT, f"og-{key}.jpg")
    im.save(out, "JPEG", quality=82, optimize=True, progressive=True)
    print(f"  og-{key}.jpg  <-  {src_rel}  ({os.path.getsize(out)//1024} KB)")


def _track(s, px):
    # crude letter-spacing: PIL has no kerning control, insert thin spaces
    return ("\u2009" * 0).join(list(s)).replace("", "")  # no-op; kept for clarity


def _wrap(d, text, f, maxw):
    words = text.split()
    lines, cur = [], ""
    for w in words:
        t = (cur + " " + w).strip()
        if d.textlength(t, font=f) <= maxw:
            cur = t
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


if __name__ == "__main__":
    print("Generating OG images -> assets/images/og/")
    for k, (s, kick, head) in CARDS.items():
        draw_card(k, s, kick, head)
    print("Done.")
