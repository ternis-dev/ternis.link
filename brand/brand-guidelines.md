# ternis.link — Brand Identity Guidelines

A compact guide to using the official **ternis.link** brand identity, logo assets, typography, and color system.

---

## 1. The Logo & Symbol

* **Concept**: **TEL Nexus** — An integrated architectural monogram ligature where **T** forms the header crossbar, **E** forms the 3-tier routing ladder, and **L** forms the base foundation foot.
* **Meaning**: Unifies **TE** (*Ternis* / `ternis-edv.de`) and **L** (*Link* / extension shortcut `tl`) with terminal link eyelets, celebrating deterministic routing and infrastructure.
* **Configurations**:
  * **Horizontal Lockup** (`brand/logo-horizontal.svg`): Primary lockup for headers, navigation bars, developer docs, and email signatures.
  * **Symbol Master** (`brand/symbol-master.svg`): Standalone symbol for app icons, avatars, and social profiles.
  * **Small-Size Optical Cut** (`brand/symbol-small.svg`): Tailored for 16×16 px and 32×32 px displays (browser favicons, tab bars, toolbar extension buttons) with reinforced negative space.
  * **Stacked Lockup** (`brand/logo-stacked.svg`): For centered layouts, stickers, cards, and square containers.

---

## 2. Clear Space & Margins

* Always maintain a minimum clear space zone equal to **1 × X** around the mark, where **X** is the height of the top crossbar (40 units).
* Do not place text, buttons, or intrusive graphic elements inside this perimeter.

```
       ┌───────────────────────────────┐
       │   X                           │
       │ X [ TEL Symbol ] X            │
       │   X                           │
       └───────────────────────────────┘
```

---

## 3. Minimum Sizing

| Asset Version | Digital Screen | Favicon / App |
|---|---|---|
| Horizontal Lockup | 110 px width | — |
| Stacked Lockup | 64 px width | — |
| Symbol Master | 32 px width | — |
| Small Optical Cut | 16 px width | 16×16 px / 32×32 px |

---

## 4. Color Palette

*ternis.link* follows a strict, engineering-grade monochrome palette aligned with Tailwind CSS v4 neutral shades:

| Role | Color Name | HEX | RGB | Use Case |
|---|---|---|---|---|
| **Primary Dark** | Neutral 950 / Black | `#0a0a0a` | `rgb(10, 10, 10)` | Light-mode logo, dark-mode page background |
| **Primary Light** | Pure White | `#ffffff` | `rgb(255, 255, 255)` | Dark-mode logo, light-mode page background |
| **Brand Surface** | Neutral 900 | `#171717` | `rgb(23, 23, 23)` | Cards, app icon tiles, terminal surfaces |
| **Muted Accent** | Neutral 500 | `#737373` | `rgb(115, 115, 115)` | `.link` domain suffix in wordmark, borders |

---

## 5. Typography

* **Display & Wordmark**: **Space Grotesk** (`space-grotesk-var.woff2`) — Geometric, modern sans-serif. Used for headers, brand labels, and wordmarks.
* **Interface & Body**: **Inter** (`inter-var.woff2`) — Highly legible neo-grotesque sans-serif.
* **Code & Monospace**: `ui-monospace`, SFMono-Regular, Menlo, Monaco, Consolas.

---

## 6. Rules of Usage

* **DO**:
  * Use the white reversed mark on dark surfaces and dark mode interfaces.
  * Use the black mark on light surfaces and light mode interfaces.
  * Use `symbol-small.svg` or `favicon.ico` for sub-32px displays.
* **DON'T**:
  * Do not stretch, distort, or skew the symbol.
  * Do not add drop shadows, outer glows, or gradients.
  * Do not change the proportion or placement of T, E, and L.
  * Do not recolor in arbitrary saturated neon colors without strategic brand alignment.
