# Front Porch Creative — Design System

**Version:** 1.0  
**Date:** June 30, 2026  
**Status:** Active — some tokens marked **Pending choice** until first page review  
**References:** [Briefing](Briefing.md)  
**Stack:** Laravel + Inertia + Vue 3 · Tailwind CSS 4 · Reka UI · Lucide icons

---

## 1. Purpose and scope

This document translates the [Briefing](Briefing.md) into implementable design rules for the public marketing site (`frontporchcreative.io`). It covers visual language, tokens, layout, components, motion, imagery, and implementation conventions.

**In scope:** Public site (home long-form landing, service pages, portfolio, blog, legal pages, cookie consent, lead form).

**Out of scope (MVP):** Admin/CMS UI theming (may reuse tokens later), newsletter, live chat, payments, user accounts.

**Primary conversion goal:** Qualified lead appointments — every pattern should support trust, clarity, and scheduling/contact CTAs without aggressive sales tone.

---

## 2. Design principles

Derived from the briefing and confirmed decisions:

| Principle | Meaning in UI |
|-----------|----------------|
| **Front porch conversation** | Calm layout, generous whitespace, human copy, approachable hierarchy — not a cold funnel. |
| **Intentional simplicity** | Few decorative layers; subtle futurism (gradient, border, glow) — never noisy. |
| **Results-oriented clarity** | Jargon-free service explanations; scannable sections; obvious next steps. |
| **Trust without hype** | Process, FAQ, and “Who we are” carry weight when portfolio/testimonials are thin at launch. |
| **Local and human** | Central Florida positioning; founder presence; warm tone — visitors should feel relief and ease. |

### Anti-patterns (do not use)

- Generic WordPress-template look
- Excessive technical jargon
- Aggressive / hard-sell copy
- **Yellow** in brand or decorative UI (see §3.1)
- Generic stock photography
- Cold, authoritative tone that treats visitors as “just another lead”

---

## 3. Color system

### 3.1 Brand colors (locked)

| Token | Hex | Role |
|-------|-----|------|
| `brand-bg` | `#192630` | Primary dark background — deep blue-gray |
| `brand-accent` | `#72887b` | Sage / muted green-gray — CTAs, links, highlights, focus |
| `brand-accent-hover` | `#5f7266` | **Proposed** — darker accent for hover (derive ~12% darker) |
| `brand-accent-muted` | `#72887b` at 60% opacity | Borders, dividers, subtle UI on dark |

**Forbidden:** Yellow (`#FFFF00` and close hues) for brand, marketing, or decorative elements.

### 3.2 Color mode

**Confirmed:** Predominantly **dark** (`#192630`), with **occasional light sections or pages** for rhythm and readability (e.g. testimonial band, blog article body, legal pages — exact placement decided per page in implementation).

Dark sections are the default. Light sections are intentional accents, not a full light-mode toggle for visitors.

### 3.3 Text on dark backgrounds — **Pending choice**

Implement all four options as CSS variables / Tailwind theme aliases so the live page can be compared before locking one token as `text-primary-on-dark`.

| Option | Hex | Character |
|--------|-----|-----------|
| **A — Pure white** | `#FFFFFF` | Maximum contrast; crisp, tech-forward |
| **B — Off-white cool** | `#F5F5F5` | Slightly softer; reduces glare |
| **C — Off-white neutral** | `#FAFAFA` | Between A and B |
| **D — Warm white** | `#F0EDE8` | Aligns with “front porch” warmth |

**Supporting text on dark** (not pending — use until revised):

| Token | Value | Use |
|-------|-------|-----|
| `text-muted-on-dark` | `#FFFFFF` at 70% opacity | Secondary copy, captions |
| `text-subtle-on-dark` | `#FFFFFF` at 50% opacity | Placeholders, meta labels |

**Default for development:** Option **B** (`#F5F5F5`) as `--text-on-dark` until founder picks after page review.

### 3.4 Light section backgrounds — **Pending choice**

Same approach: expose all options in theme; pick after first page is built.

| Option | Hex | Character |
|--------|-----|-----------|
| **L1 — Warm off-white** | `#F5F3EF` | Porch / conversation warmth |
| **L2 — Cool off-white** | `#F8FAFC` | Neutral, clean, modern |
| **L3 — Pure white** | `#FFFFFF` | Maximum clarity |
| **L4 — Soft gray** | `#EEF1F0` | Slight sage tint toward accent |

**Text on light sections** (paired with any L* background):

| Token | Hex | Use |
|-------|-----|-----|
| `text-primary-on-light` | `#192630` | Headings, body |
| `text-muted-on-light` | `#192630` at 65% opacity | Secondary copy |
| `text-accent-on-light` | `#72887b` | Links, labels |

**Default for development:** Option **L1** (`#F5F3EF`) as `--section-light-bg` until founder picks.

### 3.5 Surfaces and elevation (dark theme)

| Token | Value | Use |
|-------|-------|-----|
| `surface-base` | `#192630` | Page background |
| `surface-raised` | `#1e2f3c` | Cards, form fields on dark |
| `surface-overlay` | `#243847` | Modals, cookie banner, dropdowns |
| `border-subtle` | `#FFFFFF` at 8% opacity | Section dividers |
| `border-default` | `#72887b` at 35% opacity | Inputs, cards |
| `border-strong` | `#72887b` at 60% opacity | Focus rings, active nav |

### 3.6 Semantic / feedback colors

**Confirmed:** Derive from accent palette — no standalone yellow warning brand color.

| State | Token | Proposed hex | Notes |
|-------|-------|--------------|-------|
| Success | `success` | `#8fa894` | Lighter sage |
| Success bg | `success-muted` | `#8fa894` at 15% on dark | Form success messages |
| Error | `error` | `#c47a7a` | Muted red — not aggressive |
| Error bg | `error-muted` | `#c47a7a` at 15% on dark | Validation |
| Warning | `warning` | `#b8a088` | Warm neutral (not yellow) | Rare; prefer copy over color |
| Info | `info` | `#72887b` | Same as accent | Informational alerts |

### 3.7 Subtle futurism (decorative)

Use sparingly on dark sections only:

- **Gradient accent:** `linear-gradient(135deg, #72887b 0%, #192630 100%)` at 8–15% opacity as section overlay
- **Glow:** `box-shadow: 0 0 40px rgba(114, 136, 123, 0.15)` on hero or primary CTA hover
- **Thin lines:** 1px borders in `border-default`; optional geometric SVG patterns at ≤5% opacity

---

## 4. Typography

### 4.1 Font families

Self-host from `docs/branding/` — **do not** use Google Fonts for Montserrat Alt Thin.

| Role | Family | File | CSS weight |
|------|--------|------|------------|
| Display / headings / logo text | Montserrat Alt Thin | `MontserratAlt1-Thin.ttf` | `font-weight: 700` (bold treatment per briefing) |
| UI emphasis / CTAs | Montserrat SemiBold | `Montserrat-SemiBold.ttf` | `600` |
| Body | Montserrat Light | `Montserrat-Light.ttf` | `300` |

```css
/* Implementation reference — register in app asset pipeline */
@font-face {
    font-family: 'Montserrat Alt';
    src: url('/fonts/MontserratAlt1-Thin.ttf') format('truetype');
    font-weight: 100 700;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Montserrat';
    src: url('/fonts/Montserrat-SemiBold.ttf') format('truetype');
    font-weight: 600;
    font-display: swap;
}
@font-face {
    font-family: 'Montserrat';
    src: url('/fonts/Montserrat-Light.ttf') format('truetype');
    font-weight: 300;
    font-display: swap;
}
```

**Tailwind theme aliases:**

- `font-display` → Montserrat Alt Thin (headings)
- `font-sans` → Montserrat Light / SemiBold (body and UI)

> **Note:** The Laravel starter currently uses Instrument Sans in `resources/css/app.css`. Replace with the Montserrat stack when implementing the marketing site theme.

### 4.2 Type scale (proposed — responsive)

Headings use `font-display` + bold weight. Body uses `font-light`. CTAs and nav use `font-semibold`.

| Token | Mobile | Desktop (≥1024px) | Line height | Use |
|-------|--------|-------------------|-------------|-----|
| `text-display` | 2.25rem (36px) | 3.5rem (56px) | 1.1 | Hero headline |
| `text-h1` | 1.875rem (30px) | 2.75rem (44px) | 1.15 | Page titles |
| `text-h2` | 1.5rem (24px) | 2rem (32px) | 1.2 | Section titles |
| `text-h3` | 1.25rem (20px) | 1.5rem (24px) | 1.3 | Subsections, cards |
| `text-h4` | 1.125rem (18px) | 1.25rem (20px) | 1.35 | FAQ questions, labels |
| `text-body-lg` | 1.125rem (18px) | 1.25rem (20px) | 1.6 | Lead paragraphs |
| `text-body` | 1rem (16px) | 1.0625rem (17px) | 1.65 | Default body |
| `text-body-sm` | 0.875rem (14px) | 0.875rem (14px) | 1.5 | Captions, legal |
| `text-overline` | 0.75rem (12px) | 0.8125rem (13px) | 1.4 | Eyebrows, tags — `uppercase tracking-widest` |

**Letter-spacing:** Headings `tracking-tight` (−0.02em). Overlines `tracking-widest` (0.08em).

### 4.3 Tone in copy (content system)

Friendly, accessible, inspiring, energetic — strong persuasion with natural mental triggers, **not** hard sell. Write for small-business owners (30–40, basic tech literacy). Address objections in FAQ and process copy.

---

## 5. Spacing, layout, and grid

### 5.1 Spacing scale

Use Tailwind default spacing (4px base). Preferred section rhythm:

| Token | Value | Use |
|-------|-------|-----|
| `section-y` | `py-16` mobile · `py-24` desktop | Between major home sections |
| `section-y-tight` | `py-12` mobile · `py-16` desktop | Nested blocks, CTA bands |
| `stack-default` | `space-y-6` | Content within a section |
| `stack-loose` | `space-y-10` | Hero, feature lists |

### 5.2 Content width — **Confirmed: mixed**

| Context | Max width | Tailwind |
|---------|-----------|----------|
| Full-bleed hero, portfolio grid, blog listing | 1280px (`max-w-7xl`) | `mx-auto px-4 sm:px-6 lg:px-8` |
| Long-form body copy, FAQ answers, legal prose | 720px (`max-w-3xl`) | Centered inside section |
| Form fields (contact) | 560px (`max-w-xl`) | Readable field length |
| Service landing narrative | 800px (`max-w-3xl` to `max-w-4xl`) | Balance scan + detail |

Hero may use full viewport width for background treatments; text column still respects `max-w-3xl` or `max-w-4xl`.

### 5.3 Breakpoints

Align with Tailwind defaults:

| Name | Min width | Layout notes |
|------|-----------|--------------|
| `sm` | 640px | Stack → 2 columns for cards |
| `md` | 768px | Nav expands; side-by-side hero optional |
| `lg` | 1024px | Full desktop nav; portfolio 3-col |
| `xl` | 1280px | Container caps at `max-w-7xl` |
| `2xl` | 1536px | Optional extra horizontal padding |

### 5.4 Border radius — **Confirmed: soft (6–8px)**

| Token | Value | Use |
|-------|-------|-----|
| `radius-sm` | 4px | Chips, small badges |
| `radius-md` | 6px | Inputs, buttons (Reka default `rounded-md`) |
| `radius-lg` | 8px | Cards, images |
| `radius-xl` | 12px | Featured portfolio items, modals |

Map to existing `--radius: 0.5rem` (8px) in `app.css` for Reka UI compatibility.

---

## 6. Motion and interaction

### 6.1 Level — **Confirmed: rich**

Animations support the modern, futuristic feel while keeping layout calm. Prefer **motion on interaction and scroll reveal**, not constant background noise.

| Pattern | Spec | Respect `prefers-reduced-motion` |
|---------|------|----------------------------------|
| Page load (hero) | Stagger fade-up 400–600ms, `ease-out` | Instant show |
| Section reveal (scroll) | `opacity 0→1`, `translateY 16px→0`, 500ms | No transform |
| CTA hover | Scale `1.02`, glow shadow (§3.7), 200ms | Color change only |
| Link hover | Underline offset or accent color, 150ms | — |
| FAQ accordion | Height + chevron rotate 250ms `ease-in-out` | Instant expand |
| Form focus | Ring `3px` accent at 50% opacity | Keep focus ring |

Use `tw-animate-css` and Vue transitions already in the project. Avoid parallax that harms readability or mobile performance.

### 6.2 Focus and accessibility

- Visible focus rings on all interactive elements (`focus-visible:ring-[3px]` — matches Reka Button).
- Minimum touch target **44×44px** on mobile for CTAs and nav.
- Color contrast: aim for **WCAG AA** on text pairs even though formal ADA audit is not MVP-required.

---

## 7. Imagery and illustration

### 7.1 Direction — **Confirmed**

1. **Founder photography** — authentic, professional or polished casual; builds trust for a new agency.
2. **Abstract / geometric** — subtle shapes, lines, grids; support “futuristic” without stock clichés.
3. **Custom illustrations** — when needed for services or empty states; same palette (`#192630`, `#72887b`, neutrals).
4. **Portfolio** — real project screenshots when available; no fake work.

**Do not use:** Generic stock photos of handshakes, laptops, or “diverse team at table.”

### 7.2 Placeholders (until real assets)

Use [placehold.co](https://placehold.co) with brand colors, e.g.:

- `https://placehold.co/1200x800/192630/72887b?text=Portfolio`
- `https://placehold.co/800x800/F5F3EF/192630?text=Team`

Replace before launch where real assets exist.

### 7.3 Image treatment

- Border radius: `radius-lg` (8px)
- Optional thin border: `border-default`
- Lazy-load below fold; explicit `width` / `height` to reduce CLS
- Alt text: descriptive, localized in English for MVP

---

## 8. Logo usage

**Assets:** `docs/branding/Logo.png` (horizontal), `docs/branding/Logo Vertical.png` (vertical). PNG only until SVG is exported.

### 8.1 Recommended usage (layout-dependent)

| Context | Asset | Rationale |
|---------|-------|-----------|
| Desktop header | Horizontal | Fits nav row; clear wordmark |
| Mobile header (&lt; `md`) | Vertical or horizontal scaled down | Vertical if horizontal feels cramped — **choose in implementation review** |
| Footer | Horizontal, smaller | Consistent brand lockup |
| Social / OG | Vertical or horizontal per crop | Square-friendly: vertical |

### 8.2 Clear space and size

- **Clear space:** Minimum padding around logo = height of the “F” in the mark (or ~16px minimum).
- **Min height:** 32px mobile header; 40px desktop header.
- **Do not:** Stretch, recolor, add effects, or place on busy backgrounds without contrast overlay.

---

## 9. Components

Built with **Reka UI** primitives under `resources/js/components/ui/`, styled via **Tailwind** and tokens above. Public site may add marketing-specific wrappers (e.g. `MarketingButton`, `SectionShell`).

### 9.1 Buttons / CTAs — **Confirmed**

| Variant | Style | Use |
|---------|-------|-----|
| **Primary** | Filled `brand-accent` bg; text `#192630` or white (pick highest contrast in review) | Schedule, main conversion |
| **Secondary** | Outline `brand-accent` border; transparent bg; hover fill at 10% | Alternate actions |
| **Tertiary** | Link style — accent text, underline on hover | “Learn more”, in-copy links |

Map to Reka `Button` variants:

- Primary → customize `default` variant with accent tokens
- Secondary → `outline`
- Tertiary → `link`

**Sizes:** `lg` for hero CTAs; `default` elsewhere.

**Icons:** Lucide, 16–20px, trailing for external links (e.g. calendar).

### 9.2 Links

- Default: `brand-accent` on dark; underline on hover
- On light sections: `text-accent-on-light` or `#72887b`
- External scheduling link: optional `ExternalLink` icon

### 9.3 Form fields (lead capture)

Fields: Name, Email, Phone, Message — all required.

| Element | Spec |
|---------|------|
| Input | `surface-raised` bg, `border-default`, `radius-md`, `text-body` |
| Label | `text-h4` weight semibold, above field |
| Placeholder | `text-subtle-on-dark` |
| Error | `error` text + `error-muted` bg; `aria-invalid` per Reka patterns |
| Success | Toast or inline `success` message after submit |

Use Reka `Input`, `Label`, `Textarea` (add if missing). Stable E2E selectors: `data-test="contact-name"`, etc.

### 9.4 Navigation

- Sticky header on scroll with `surface-base` at 95% opacity + backdrop blur
- Items: Services (dropdown or scroll), Portfolio, Blog, Contact (anchor), primary CTA button
- Mobile: sheet/drawer menu (Reka `Dialog` or `DropdownMenu` pattern)
- Footer: logo, key links, Privacy, Terms, local service area mention

### 9.5 Section shell (home + landing pages)

Reusable `SectionShell` pattern:

```
[optional overline]
[heading — text-h2]
[body — text-body-lg, max-w-3xl]
[content slot]
[optional CTA row]
```

Alternate dark / light backgrounds per §3.2 for visual rhythm.

### 9.6 Home page sections (from briefing)

| # | Section | Component notes |
|---|---------|-----------------|
| 1 | Hero + scheduling CTA | `text-display`, primary + secondary CTA, subtle gradient bg |
| 2 | Problems | Icon list (Lucide), 3–5 pain points |
| 3 | Services | Card grid → links to `/services/{slug}` |
| 4 | CTA | Narrow band, primary button |
| 5 | Portfolio | Up to 12 items; grid; “See more” → `/portfolio` |
| 6 | Testimonials | Carousel or stack; light section candidate |
| 7 | CTA | |
| 8 | How it works | Numbered steps, horizontal on desktop |
| 9 | FAQ | Accordion (home only) |
| 10 | CTA | |
| 11 | Why Front Porch | Differentiators list |
| 12 | Who we are | Founder photo + copy |
| 13 | CTA | |
| 14 | Contact | Form + calendar link notice |
| 15 | CTA | |
| 16 | Blog preview | 3–6 cards → `/blog` |

### 9.7 FAQ accordion

- Reka `Collapsible` or `Accordion`
- One or multiple open — **default: single** (cleaner for long home page)
- Question: `text-h4` semibold; answer: `text-body`

### 9.8 Portfolio card

- Thumbnail `aspect-video` or `aspect-[4/3]`
- Title + short tag (service type)
- Hover: subtle scale `1.02` + border accent

### 9.9 Blog card

- Title, excerpt, category badge, date
- Category badge: `radius-sm`, accent outline

### 9.10 Cookie consent banner

- `surface-overlay`, bottom bar or corner card
- Primary: Accept; Secondary: Manage / Reject if required
- Link to Privacy Policy
- Must load GA / Meta only after consent (implementation detail)

### 9.11 Scheduling CTA

- Redirect to Google Calendar (external)
- Button copy TBD when session length is finalized
- Show duration notice near CTA when copy is ready

---

## 10. Page-level patterns

### 10.1 Service landing pages (`/services/{slug}`)

- Paid-traffic ready: clear headline, benefit bullets, social proof when available, single primary CTA
- Repeatable template; swap copy and imagery per service
- SEO: one H1, local keywords in copy (see Briefing §10)

### 10.2 Portfolio (`/portfolio`)

- Full grid; filter by service optional (post-MVP)
- Empty state: illustration + “Projects coming soon” + CTA to contact

### 10.3 Blog

- Listing: card grid; categories filter
- Article: light background for reading comfort (`L*` token); `max-w-3xl` prose
- No comments (MVP)

### 10.4 Legal (`/privacy`, `/terms`)

- Light section background (pending L* choice)
- `text-body-sm`, clear headings, minimal decoration

---

## 11. Icons

**Library:** Lucide (`@lucide/vue`) — already in project.

| Context | Style |
|---------|-------|
| UI chrome | 20px stroke, 1.5–2px stroke width |
| Service / problem icons | 24–32px, accent color |
| Decorative | Outline only; no filled icon sets |

---

## 12. Implementation map (Tailwind / Reka)

### 12.1 Theme variables to add (`resources/css/app.css`)

Replace starter palette with brand tokens. Suggested mapping:

| Design token | CSS variable | Tailwind |
|--------------|--------------|----------|
| `brand-bg` | `--background` (dark default) | `bg-background` |
| Text on dark (pending) | `--foreground` | `text-foreground` |
| `brand-accent` | `--primary` | `bg-primary`, `text-primary` |
| Light section | `--section-light` (custom) | `bg-section-light` |
| Border | `--border` | `border-border` |

Expose pending text/background options as:

```css
:root {
    --text-on-dark-a: #ffffff;
    --text-on-dark-b: #f5f5f5;
    --text-on-dark-c: #fafafa;
    --text-on-dark-d: #f0ede8;
    --section-light-l1: #f5f3ef;
    --section-light-l2: #f8fafc;
    --section-light-l3: #ffffff;
    --section-light-l4: #eef1f0;
    /* Active defaults until review: */
    --text-on-dark: var(--text-on-dark-b);
    --section-light-bg: var(--section-light-l1);
}
```

### 12.2 File conventions

- Marketing layout: `resources/js/layouts/MarketingLayout.vue`
- Page sections: `resources/js/components/marketing/`
- Use `clsx` + `tailwind-merge` + `class-variance-authority` (existing pattern)
- E2E: `data-test` attributes on CTAs, form fields, nav

### 12.3 Dark default

Marketing pages use dark base on `<body>` or root layout — not the admin `.dark` class toggle. Light sections override background locally.

---

## 13. Open decisions (track before v1.0 lock)

| Item | Status | Action |
|------|--------|--------|
| Text on dark (A–D) | **Pending** | Compare on built home hero + body |
| Light section bg (L1–L4) | **Pending** | Compare on testimonial/blog band |
| Primary CTA text color on accent fill | **Pending** | `#192630` vs white — contrast check |
| Logo vertical vs horizontal on mobile | **Pending** | Decide in header implementation |
| Calendar session duration copy | **Pending** | Briefing — post-launch refinement |
| Custom illustration source | **Open** | Commission vs founder-created |

---

## 14. Document history

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-06-30 | Initial system from Briefing + stakeholder Q&A |

---

*End of design system.*
