# RareImagery — MySpace Theme Bot Rules

> This document defines the complete ruleset for the AI theme generation bot.
> The bot reads these rules, takes creator input, and outputs a single JSON theme config.
> Next.js reads the config and renders the storefront. The bot never writes JSX or CSS directly.

---

## 1. What the Bot Does

The bot receives three inputs:
1. **Creator's X profile data** (avatar, banner, bio, top posts, follower count)
2. **Mood quiz answers** (2–5 questions answered in console onboarding)
3. **Optional: named preset** (e.g. "make me emo", "make me scene queen")

It outputs one thing: a **valid JSON object** matching the schema in Section 3.

---

## 2. Core Philosophy

Peak MySpace (2006–2008) was defined by **maximum self-expression within a fixed grid.**
Every profile had the same structure — two columns, profile pic left, content right — but no two looked alike.

The bot must honor both sides of this:
- **Fixed grid:** layout structure, column positions, and product placement are non-negotiable
- **Maximum expression:** color, font, texture, decoration, animation class, and tone are fully open

The bot is NOT recreating MySpace. It is translating the *spirit* of MySpace into a modern Tailwind storefront.

---

## 3. JSON Theme Config Schema

This is the complete output the bot produces. Every key is required.

```json
{
  "meta": {
    "preset_name": "string",
    "subculture": "string",
    "generated_from": "x_profile | quiz | named_preset | combined",
    "version": "1.0"
  },

  "layout": {
    "structure": "two_col_left | two_col_right | three_col | sidebar_heavy",
    "sidebar_width": "narrow | medium | wide",
    "content_density": "sparse | balanced | packed",
    "section_order": ["header", "player", "about", "top8", "products", "reviews"]
  },

  "background": {
    "type": "solid | gradient | tiled_pattern | image_url",
    "color_primary": "#hex",
    "color_secondary": "#hex",
    "gradient_direction": "to-b | to-br | to-r | null",
    "pattern_style": "stars | hearts | skulls | plaid | dots | lightning | null",
    "pattern_opacity": 0.0
  },

  "colors": {
    "page_bg": "#hex",
    "sidebar_bg": "#hex",
    "content_bg": "#hex",
    "card_bg": "#hex",
    "border": "#hex",
    "accent_primary": "#hex",
    "accent_secondary": "#hex",
    "text_body": "#hex",
    "text_heading": "#hex",
    "text_link": "#hex",
    "text_link_hover": "#hex",
    "scrollbar_track": "#hex",
    "scrollbar_thumb": "#hex"
  },

  "typography": {
    "font_heading": "string (Tailwind font class or Google Font name)",
    "font_body": "string",
    "font_accent": "string (used for username, badges, decorative text)",
    "heading_size": "text-2xl | text-3xl | text-4xl | text-5xl",
    "body_size": "text-sm | text-base | text-lg",
    "letter_spacing": "tracking-tight | tracking-normal | tracking-wide | tracking-widest",
    "text_transform": "none | uppercase | lowercase",
    "text_shadow": "none | soft | glow | hard"
  },

  "header": {
    "layout": "banner_behind | banner_above | no_banner | split",
    "avatar_shape": "circle | square | rounded | hexagon",
    "avatar_border_style": "solid | dashed | double | glitter | none",
    "avatar_border_color": "#hex",
    "avatar_size": "sm | md | lg | xl",
    "username_style": "plain | glitter | outlined | shadow | neon",
    "show_follower_count": true,
    "show_x_handle": true,
    "show_mood": true,
    "mood_icon": "string (emoji or null)",
    "mood_text": "string or null"
  },

  "player": {
    "enabled": true,
    "type": "x_spaces_link | x_post_embed | disabled",
    "position": "sidebar_top | sidebar_bottom | header_inline | floating_bottom",
    "style": "retro_cassette | pill | minimal | chunky_button",
    "label": "string (e.g. 'Now Playing', 'Tune In', 'Live on Spaces')",
    "color_bg": "#hex",
    "color_text": "#hex",
    "color_button": "#hex",
    "show_waveform_decoration": true
  },

  "about": {
    "enabled": true,
    "title": "string (e.g. 'About Me', 'Who I Am', 'The Vibe')",
    "source": "x_bio | custom | null",
    "box_style": "bordered | shadowed | transparent | glassy",
    "show_top_posts": true,
    "top_posts_count": 3,
    "show_interests": true
  },

  "top8": {
    "enabled": true,
    "title": "string (e.g. 'Top 8', 'My Drops', 'The Essentials')",
    "count": 8,
    "grid_cols": "2 | 4 | 8",
    "card_style": "polaroid | flat | rounded | bordered | floating",
    "card_hover": "scale | glow | shake | none",
    "show_product_name": true,
    "show_price": true,
    "label_style": "tag | badge | inline | none"
  },

  "products": {
    "grid_cols_desktop": "2 | 3 | 4",
    "grid_cols_mobile": "1 | 2",
    "card_style": "flat | raised | polaroid | bordered",
    "card_bg": "#hex",
    "card_border_color": "#hex",
    "card_border_radius": "none | sm | md | lg | xl | full",
    "image_aspect": "square | portrait | landscape",
    "hover_effect": "scale | shadow | border_glow | flip | none",
    "show_add_to_cart": true,
    "add_to_cart_style": "pill | square | ghost | chunky",
    "add_to_cart_color": "#hex",
    "add_to_cart_text": "string (e.g. 'Add to Cart', 'Grab It', 'Cop It')"
  },

  "decorations": {
    "use_sparkle_dividers": true,
    "use_animated_border": false,
    "cursor_style": "default | star | heart | skull | crosshair",
    "show_blinkies": false,
    "corner_decoration": "none | stars | hearts | skulls | lightning",
    "section_divider_style": "none | line | dotted | hearts | stars | zigzag",
    "section_divider_color": "#hex",
    "glitter_text_on": ["username"],
    "badge_style": "pixel | rounded | sticker | minimal"
  },

  "animation": {
    "page_entrance": "none | fade_in | slide_up | glitch",
    "header_animation": "none | pulse | float | shimmer",
    "product_card_entrance": "none | stagger_fade | pop_in",
    "reduced_motion_fallback": true
  },

  "mobile": {
    "stack_order": ["header", "player", "top8", "products", "about"],
    "sidebar_collapse": "hide | accordion | drawer",
    "touch_target_size": "comfortable | compact"
  }
}
```

---

## 4. Section Mappings (MySpace → RareImagery)

| MySpace Original | RareImagery Equivalent | Variable Key |
|---|---|---|
| Profile pic + display name | Creator avatar + X handle | `header` |
| Top 8 friends | Top 8 featured products | `top8` |
| Music player (autoplay) | X Spaces link or embedded X post | `player` |
| About Me | Bio pulled from X + top posts | `about` |
| Mood status | Store status / vibe tag | `header.mood_text` |
| Comments section | Product reviews | *(handled by Next.js, not theme config)* |
| Friend count | X follower count | `header.show_follower_count` |
| Background tile | Background pattern | `background.pattern_style` |

**Note:** No actual autoplay audio. The player widget links to X Spaces or embeds an X post. X handles audio entirely.

---

## 5. Subculture Presets

Each preset defines default values across the full schema. The bot uses these as starting points and modifies them based on the creator's X profile signals.

---

### 5.1 Emo / Dark Emo

**Visual DNA:** Black everything, red accents, broken hearts, heavy emotion, serif + handwritten mix

```json
{
  "background": { "type": "solid", "color_primary": "#0a0a0a", "pattern_style": "null" },
  "colors": {
    "page_bg": "#0a0a0a", "sidebar_bg": "#111111", "content_bg": "#0f0f0f",
    "card_bg": "#1a0a0a", "border": "#3d0000", "accent_primary": "#cc0000",
    "accent_secondary": "#ff4444", "text_body": "#cccccc", "text_heading": "#ffffff",
    "text_link": "#ff4444", "text_link_hover": "#ff8888",
    "scrollbar_track": "#0a0a0a", "scrollbar_thumb": "#cc0000"
  },
  "typography": {
    "font_heading": "Cinzel", "font_body": "Inter", "font_accent": "Dancing Script",
    "heading_size": "text-3xl", "letter_spacing": "tracking-wide",
    "text_transform": "none", "text_shadow": "glow"
  },
  "decorations": {
    "use_sparkle_dividers": false, "cursor_style": "crosshair",
    "corner_decoration": "skulls", "section_divider_style": "hearts",
    "section_divider_color": "#3d0000", "glitter_text_on": [],
    "badge_style": "pixel"
  },
  "player": { "style": "retro_cassette", "label": "Now Playing" }
}
```

**X profile signals that map to this preset:** dark imagery, MCR/Panic references, black/red banner, emo hashtags

---

### 5.2 Scene Kid / Scene Queen

**Visual DNA:** Neon chaos, hot pink + lime + electric blue, stars everywhere, glitter text, maximum energy

```json
{
  "background": { "type": "tiled_pattern", "color_primary": "#ff00cc", "color_secondary": "#00ffcc", "pattern_style": "stars", "pattern_opacity": 0.15 },
  "colors": {
    "page_bg": "#1a001a", "sidebar_bg": "#2d0029", "content_bg": "#1f001f",
    "card_bg": "#2a0030", "border": "#ff00cc", "accent_primary": "#ff00cc",
    "accent_secondary": "#00ff99", "text_body": "#ffffff", "text_heading": "#ff00cc",
    "text_link": "#00ff99", "text_link_hover": "#ffffff",
    "scrollbar_track": "#1a001a", "scrollbar_thumb": "#ff00cc"
  },
  "typography": {
    "font_heading": "Boogaloo", "font_body": "Nunito", "font_accent": "Pacifico",
    "heading_size": "text-4xl", "letter_spacing": "tracking-widest",
    "text_transform": "none", "text_shadow": "glow"
  },
  "decorations": {
    "use_sparkle_dividers": true, "cursor_style": "star",
    "corner_decoration": "stars", "section_divider_style": "stars",
    "section_divider_color": "#ff00cc", "glitter_text_on": ["username", "top8.title"],
    "badge_style": "sticker"
  },
  "player": { "style": "chunky_button", "label": "Tune In 🌟" }
}
```

**X profile signals:** rainbow/neon banners, scene hashtags, band mentions, chaotic bios with emoticons

---

### 5.3 Pop Princess

**Visual DNA:** Bubblegum pink, glitter, hearts, stars, soft gradients, playful fonts, pastel everything

```json
{
  "background": { "type": "gradient", "color_primary": "#ffb3de", "color_secondary": "#fff0f8", "gradient_direction": "to-b" },
  "colors": {
    "page_bg": "#fff0f8", "sidebar_bg": "#ffe4f4", "content_bg": "#ffffff",
    "card_bg": "#fff5fb", "border": "#ffaadd", "accent_primary": "#ff69b4",
    "accent_secondary": "#ff99cc", "text_body": "#6b3a5e", "text_heading": "#cc2277",
    "text_link": "#ff69b4", "text_link_hover": "#ff99cc",
    "scrollbar_track": "#ffe4f4", "scrollbar_thumb": "#ff69b4"
  },
  "typography": {
    "font_heading": "Pacifico", "font_body": "Quicksand", "font_accent": "Dancing Script",
    "heading_size": "text-4xl", "letter_spacing": "tracking-wide",
    "text_transform": "none", "text_shadow": "soft"
  },
  "decorations": {
    "use_sparkle_dividers": true, "cursor_style": "heart",
    "corner_decoration": "hearts", "section_divider_style": "hearts",
    "section_divider_color": "#ffaadd", "glitter_text_on": ["username"],
    "badge_style": "rounded"
  },
  "player": { "style": "pill", "label": "Now Playing 💖" }
}
```

**X profile signals:** pink banners, selfies, pop music references, heart emojis, beauty/fashion content

---

### 5.4 Hip-Hop / Rap

**Visual DNA:** Black + gold, graffiti-inspired, bold everything, street credibility, clean but heavy

```json
{
  "background": { "type": "solid", "color_primary": "#0d0d0d" },
  "colors": {
    "page_bg": "#0d0d0d", "sidebar_bg": "#1a1400", "content_bg": "#111111",
    "card_bg": "#1a1400", "border": "#c9a800", "accent_primary": "#f0c000",
    "accent_secondary": "#ffffff", "text_body": "#e0e0e0", "text_heading": "#f0c000",
    "text_link": "#f0c000", "text_link_hover": "#ffffff",
    "scrollbar_track": "#0d0d0d", "scrollbar_thumb": "#f0c000"
  },
  "typography": {
    "font_heading": "Black Han Sans", "font_body": "Inter", "font_accent": "Bebas Neue",
    "heading_size": "text-5xl", "letter_spacing": "tracking-widest",
    "text_transform": "uppercase", "text_shadow": "hard"
  },
  "decorations": {
    "use_sparkle_dividers": false, "cursor_style": "default",
    "corner_decoration": "none", "section_divider_style": "line",
    "section_divider_color": "#c9a800", "glitter_text_on": [],
    "badge_style": "minimal"
  },
  "player": { "style": "minimal", "label": "Now Bumpin'" }
}
```

**X profile signals:** rap/hip-hop mentions, gold/black imagery, city references, bars in bio

---

### 5.5 Indie / Alt

**Visual DNA:** Film grain, muted tones, off-white, vintage warmth, typewriter fonts, understated cool

```json
{
  "background": { "type": "solid", "color_primary": "#f5f0e8" },
  "colors": {
    "page_bg": "#f5f0e8", "sidebar_bg": "#ede8dc", "content_bg": "#faf7f2",
    "card_bg": "#ffffff", "border": "#c8b89a", "accent_primary": "#8b5e3c",
    "accent_secondary": "#c4956a", "text_body": "#3d3228", "text_heading": "#2a1f15",
    "text_link": "#8b5e3c", "text_link_hover": "#c4956a",
    "scrollbar_track": "#ede8dc", "scrollbar_thumb": "#8b5e3c"
  },
  "typography": {
    "font_heading": "Playfair Display", "font_body": "Lora", "font_accent": "Special Elite",
    "heading_size": "text-3xl", "letter_spacing": "tracking-normal",
    "text_transform": "none", "text_shadow": "none"
  },
  "decorations": {
    "use_sparkle_dividers": false, "cursor_style": "default",
    "corner_decoration": "none", "section_divider_style": "dotted",
    "section_divider_color": "#c8b89a", "glitter_text_on": [],
    "badge_style": "minimal"
  },
  "player": { "style": "minimal", "label": "Now Playing" }
}
```

**X profile signals:** vinyl/film references, muted profile images, poetry bios, indie band mentions

---

### 5.6 Gamer / Neon Cyber

**Visual DNA:** Dark background, electric neon (cyan/purple/green), pixel aesthetics, terminal energy

```json
{
  "background": { "type": "solid", "color_primary": "#050510", "pattern_style": "dots", "pattern_opacity": 0.05 },
  "colors": {
    "page_bg": "#050510", "sidebar_bg": "#0a0a1a", "content_bg": "#080818",
    "card_bg": "#0f0f22", "border": "#00ffff", "accent_primary": "#00ffff",
    "accent_secondary": "#cc00ff", "text_body": "#a0f0f0", "text_heading": "#00ffff",
    "text_link": "#cc00ff", "text_link_hover": "#ff00ff",
    "scrollbar_track": "#050510", "scrollbar_thumb": "#00ffff"
  },
  "typography": {
    "font_heading": "Orbitron", "font_body": "Share Tech Mono", "font_accent": "Orbitron",
    "heading_size": "text-4xl", "letter_spacing": "tracking-widest",
    "text_transform": "uppercase", "text_shadow": "glow"
  },
  "decorations": {
    "use_sparkle_dividers": false, "cursor_style": "crosshair",
    "corner_decoration": "lightning", "section_divider_style": "zigzag",
    "section_divider_color": "#00ffff", "glitter_text_on": [],
    "badge_style": "pixel"
  },
  "player": { "style": "retro_cassette", "label": "BROADCAST" }
}
```

**X profile signals:** gaming hashtags, twitch/streaming mentions, dark profile imagery, tech/sci-fi bio language

---

### 5.7 Cottagecore / Soft Aesthetic

**Visual DNA:** Sage green, warm creams, florals, handwritten fonts, gentle and earthy

```json
{
  "background": { "type": "tiled_pattern", "color_primary": "#f0f4e8", "color_secondary": "#e8f0e0", "pattern_style": "dots", "pattern_opacity": 0.1 },
  "colors": {
    "page_bg": "#f0f4e8", "sidebar_bg": "#e4edd8", "content_bg": "#f8fbf4",
    "card_bg": "#ffffff", "border": "#a8c090", "accent_primary": "#5a8a4a",
    "accent_secondary": "#c8a878", "text_body": "#3d4a30", "text_heading": "#2a3820",
    "text_link": "#5a8a4a", "text_link_hover": "#3d6030",
    "scrollbar_track": "#e4edd8", "scrollbar_thumb": "#5a8a4a"
  },
  "typography": {
    "font_heading": "Playfair Display", "font_body": "Lato", "font_accent": "Dancing Script",
    "heading_size": "text-3xl", "letter_spacing": "tracking-normal",
    "text_transform": "none", "text_shadow": "soft"
  },
  "decorations": {
    "use_sparkle_dividers": true, "cursor_style": "default",
    "corner_decoration": "hearts", "section_divider_style": "dotted",
    "section_divider_color": "#a8c090", "glitter_text_on": [],
    "badge_style": "rounded"
  },
  "player": { "style": "pill", "label": "Playing Now 🌿" }
}
```

**X profile signals:** nature photography, plant/garden references, soft color banners, slow living bios

---

### 5.8 Y2K / McBling

**Visual DNA:** Chrome, holographic, pink + silver + white, Juicy Couture energy, butterfly motifs

```json
{
  "background": { "type": "gradient", "color_primary": "#e8e0ff", "color_secondary": "#ffe8f4", "gradient_direction": "to-br" },
  "colors": {
    "page_bg": "#f0ecff", "sidebar_bg": "#e8e0ff", "content_bg": "#ffffff",
    "card_bg": "#f8f4ff", "border": "#c0a8e8", "accent_primary": "#9966cc",
    "accent_secondary": "#ff99cc", "text_body": "#4a3060", "text_heading": "#6633aa",
    "text_link": "#9966cc", "text_link_hover": "#ff99cc",
    "scrollbar_track": "#e8e0ff", "scrollbar_thumb": "#9966cc"
  },
  "typography": {
    "font_heading": "Boogaloo", "font_body": "Poppins", "font_accent": "Pacifico",
    "heading_size": "text-4xl", "letter_spacing": "tracking-wide",
    "text_transform": "none", "text_shadow": "glow"
  },
  "decorations": {
    "use_sparkle_dividers": true, "cursor_style": "star",
    "corner_decoration": "stars", "section_divider_style": "stars",
    "section_divider_color": "#c0a8e8", "glitter_text_on": ["username"],
    "badge_style": "sticker"
  },
  "player": { "style": "pill", "label": "✨ Now Playing ✨" }
}
```

**X profile signals:** Y2K references, butterfly emojis, fashion content, holographic/chrome imagery

---

### 5.9 Goth / Dark Romantic

**Visual DNA:** Deep purple + black, victorian, candles, roses, velvet energy

```json
{
  "background": { "type": "solid", "color_primary": "#0a0008" },
  "colors": {
    "page_bg": "#0a0008", "sidebar_bg": "#120010", "content_bg": "#0f000d",
    "card_bg": "#1a0018", "border": "#5a0060", "accent_primary": "#9900aa",
    "accent_secondary": "#cc44cc", "text_body": "#d4a0d4", "text_heading": "#e8a8e8",
    "text_link": "#cc44cc", "text_link_hover": "#ff88ff",
    "scrollbar_track": "#0a0008", "scrollbar_thumb": "#5a0060"
  },
  "typography": {
    "font_heading": "Cinzel Decorative", "font_body": "IM Fell English", "font_accent": "MedievalSharp",
    "heading_size": "text-3xl", "letter_spacing": "tracking-wide",
    "text_transform": "none", "text_shadow": "glow"
  },
  "decorations": {
    "use_sparkle_dividers": false, "cursor_style": "crosshair",
    "corner_decoration": "skulls", "section_divider_style": "line",
    "section_divider_color": "#5a0060", "glitter_text_on": [],
    "badge_style": "pixel"
  },
  "player": { "style": "retro_cassette", "label": "Now Playing" }
}
```

---

### 5.10 Skate / Streetwear

**Visual DNA:** White + black + one bold pop color, asymmetric, raw, spray paint energy

```json
{
  "background": { "type": "solid", "color_primary": "#f5f5f5" },
  "colors": {
    "page_bg": "#f5f5f5", "sidebar_bg": "#eeeeee", "content_bg": "#ffffff",
    "card_bg": "#f0f0f0", "border": "#222222", "accent_primary": "#ff3300",
    "accent_secondary": "#222222", "text_body": "#222222", "text_heading": "#000000",
    "text_link": "#ff3300", "text_link_hover": "#cc2200",
    "scrollbar_track": "#eeeeee", "scrollbar_thumb": "#222222"
  },
  "typography": {
    "font_heading": "Bebas Neue", "font_body": "Inter", "font_accent": "Permanent Marker",
    "heading_size": "text-5xl", "letter_spacing": "tracking-widest",
    "text_transform": "uppercase", "text_shadow": "hard"
  },
  "decorations": {
    "use_sparkle_dividers": false, "cursor_style": "default",
    "corner_decoration": "none", "section_divider_style": "line",
    "section_divider_color": "#222222", "glitter_text_on": [],
    "badge_style": "minimal"
  },
  "player": { "style": "chunky_button", "label": "Drop" }
}
```

---

## 6. X Profile Signal → Theme Mapping Rules

The bot reads the creator's X profile data and uses these rules to auto-select and modify a preset.

| X Profile Signal | Bot Action |
|---|---|
| Banner is predominantly dark (< 20% brightness) | Bias toward emo, goth, gamer, hip-hop |
| Banner has neon/high saturation colors | Bias toward scene, neon cyber, Y2K |
| Banner is pink/pastel | Bias toward pop princess, cottagecore, Y2K |
| Bio contains band names (MCR, Panic, BmtH) | Apply emo or scene preset |
| Bio contains 3+ emojis | Increase `decoration.use_sparkle_dividers`, `glitter_text_on` |
| Bio is very short (< 10 words) | Set `content_density: sparse`, `about.enabled: false` |
| Follower count > 10k | Show follower count prominently (`header.avatar_size: xl`) |
| Top posts are video/music | Set `player.type: x_post_embed` |
| Top posts are product images | Set `top8.card_style: polaroid`, `layout.content_density: packed` |
| Username contains "xx", "xo", or "official" | Bias toward scene or pop princess |
| Username is all caps | Bias toward hip-hop or gamer |

---

## 7. Mood Quiz → Theme Mapping

The quiz runs in the console onboarding flow. Bot reads these answers.

### Question 1: "Pick a vibe"
- Dark & moody → emo, goth
- Loud & chaotic → scene, Y2K
- Clean & minimal → indie, skate
- Soft & warm → cottagecore, pop princess
- Bold & aggressive → hip-hop, gamer

### Question 2: "Pick a color"
- Black → emo, goth, hip-hop
- Pink → pop princess, Y2K, scene
- Neon → scene, gamer, neon cyber
- Earth tones → indie, cottagecore
- White + one pop → skate, streetwear

### Question 3: "Your store is…"
- A stage → player prominent, header large, sidebar_width: wide
- A shop → products above fold, top8 first, header compact
- A vibe → decorations max, about prominent, products secondary

### Question 4: "Your audience mostly knows you for…"
- Music → `player.enabled: true`, `player.position: header_inline`
- Looks / fashion → `header.avatar_size: xl`, `header.layout: banner_behind`
- Thoughts / takes → `about.show_top_posts: true`, `about.top_posts_count: 5`
- Products / merch → `top8.enabled: true`, `layout.section_order` starts with top8

---

## 8. Hard Limits (Bot Must Never Violate)

These rules override every preset and every creative choice.

1. **Products must render above the fold on mobile.** `top8` or `products` must appear in positions 1–3 of `mobile.stack_order`
2. **No layout breaks.** `layout.structure` must always be one of the four defined values. No custom strings.
3. **No actual autoplay audio.** `player.type` is always `x_spaces_link`, `x_post_embed`, or `disabled`. Never a raw audio file URL.
4. **Mobile responsive always.** `mobile.touch_target_size` defaults to `comfortable`. Bot cannot set it to any unlisted value.
5. **`reduced_motion_fallback` is always `true`.** Non-negotiable.
6. **GIF use is limited.** `decorations.show_blinkies` defaults to `false`. Bot may only set it `true` if the creator explicitly requests it AND the subculture is scene, Y2K, or pop princess.
7. **All hex values must be valid 6-character hex codes.** Bot must not output shorthand (e.g. `#fff`) or named colors (e.g. `"white"`).
8. **`meta.version` is always `"1.0"`.** Bot does not modify this field.

---

## 9. best_creations.json Structure

The bot references this file for inspiration when generating themes. Saved at `docs/best_creations.json`.

```json
{
  "version": "1.0",
  "creations": [
    {
      "id": "bc_001",
      "name": "string (human-readable name for this creation)",
      "subculture": "string",
      "rating": 0,
      "creator_x_handle": "string or null",
      "why_it_works": "string (plain English explanation — this is what the bot reads)",
      "standout_variables": {
        "colors.accent_primary": "#hex",
        "typography.font_heading": "string",
        "decorations.corner_decoration": "string",
        "player.style": "string"
      },
      "full_config": { }
    }
  ]
}
```

**`why_it_works` examples the bot will learn from:**
- "The contrast between black background and hot pink text creates instant scene recognition. The star pattern at 0.15 opacity adds texture without killing readability."
- "Bebas Neue at tracking-widest in all-caps transforms any storefront into a streetwear drop page. The single red accent on white makes products pop."
- "Cinzel + glow text shadow on a near-black background is the fastest way to signal dark romanticism without any imagery."

**Saving policy:** A theme gets added to `best_creations.json` when:
- A creator explicitly rates it 5 stars in console
- Admin manually flags it as exemplary
- It is shared publicly by the creator on X

---

## 10. Bot Instruction Summary (System Prompt Seed)

When initializing the Grok bot for theme generation, prepend this instruction block:

```
You are the RareImagery theme bot. You generate MySpace-era storefront themes for X creators.

You output ONLY valid JSON matching the schema in the theme rules doc.
You never write JSX, CSS, or HTML.
You never explain your choices unless asked.
You never produce placeholder values — every field must have a real value.
You never violate the 8 hard limits defined in Section 8.

Your inputs are:
1. Creator X profile data (provided as JSON)
2. Quiz answers (provided as key-value pairs)
3. Optional named preset (a string like "emo" or "scene queen")

Your output is one JSON object. Nothing else.

Reference the subculture presets to set defaults.
Apply X profile signal rules to modify defaults.
Apply quiz answers to further modify.
If a named preset is given, it overrides signal detection but quiz answers still apply on top.

Peak era: 2006–2008 MySpace. Maximum self-expression. Fixed grid.
```

---

*Docs path: `docs/MYSPACE_THEME_BOT_RULES.md`*
*Reference file: `docs/best_creations.json`*
