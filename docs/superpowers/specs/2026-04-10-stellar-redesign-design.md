# Design Spec — Stellar.ai Redesign for demo-php-frankenphp

**Date:** 2026-04-10  
**Project:** demo-php-frankenphp (Symfony + FrankenPHP on Clever Cloud)  
**Approach:** Stellar.ai visual design system applied to FrankenPHP/Clever Cloud content  
**Implementation:** Twig natif + vanilla JS, zéro dépendance externe

---

## Contexte

Remplacement complet du design actuel "Track Night" (fond sombre, orange) par le design system "Stellar.ai" (fond blanc, Inter, animations fadeInUp) tout en conservant le contenu FrankenPHP + Symfony + Clever Cloud.

---

## Design System

| Token | Valeur |
|-------|--------|
| Background | `#ffffff` |
| Text primary | `#111827` |
| Text muted | `#6b7280` |
| Border | `#f0f0f0` / `#e5e7eb` |
| Accent | `#000000` (boutons, CTA) |
| Font | Inter (400, 500, 600, 700) via Google Fonts |
| Max-width | `1280px` (`max-w-7xl`) |
| Border-radius CTA | `9999px` (rounded-full) |

---

## Animations CSS

Définies dans `base.html.twig` (ou bloc `<style>`) :

```css
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fadeInUp 0.6s ease-out forwards;
}

@keyframes fadeInOverlay {
  from { opacity: 0; }
  to   { opacity: 1; }
}
.animate-fade-in-overlay {
  animation: fadeInOverlay 0.4s ease-out forwards;
}

@keyframes fadeInDialog {
  from { opacity: 0; }
  to   { opacity: 1; }
}
.animate-slide-up-overlay {
  animation: fadeInDialog 0.5s ease-out forwards;
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
}
```

Chaque section majeure utilise `.animate-fade-in-up` avec `animationDelay` inline croissant (0.1s, 0.2s…). Chaque élément part avec `opacity: 0` inline.

---

## Section 1 — Navigation

**Style :** `bg-white`, `border-bottom: 1px solid #f0f0f0`, sticky top  
**Layout :** `max-width: 1280px`, `padding: 16px 24px`, flex space-between  
**Animation :** `.animate-fade-in-up`, delay 0.1s

| Zone | Contenu |
|------|---------|
| Gauche | Icône éclair SVG (fill noir, 20×20) + `"FrankenPHP"` (font-weight 600, font-size 18px) |
| Centre (≥768px) | `Runtimes ▾` · `Add-ons ▾` · `Deploy` · `Docs` — text-sm, color `#374151`, hover `#111827` |
| Droite | Lien `"clever-cloud.com"` (text-sm, #374151) + bouton `"Open Swagger UI"` (bg `#111827`, text blanc, px-5 py-2.5, rounded-full, font-size 14px) |

---

## Section 2 — Hero

**Layout :** centré, `padding-top: 96px`, `padding-bottom: 128px`, `text-align: center`  
**Container :** `max-width: 1280px`, `padding: 0 24px`

### Badge (delay 0.2s)
- `inline-flex`, items-center, gap 8px, `margin-bottom: 32px`
- Carré `24×24px`, `border: 1px solid #d1d5db`, `border-radius: 6px`, icône éclair inside
- Texte : `"FrankenPHP · Worker Mode · Early Hints"` (text-sm, font-medium, `#111827`)

### Heading (delay 0.3s)
- `font-size: 80px` (desktop) / `60px` (tablet) / `48px` (mobile)
- `font-weight: 400`, `line-height: 1.1`, `letter-spacing: -0.02em`
- `margin-bottom: 20px`
- Ligne 1 : `"Deploy Faster. Scale Smarter."` — couleur `#111827`
- Ligne 2 : `"PHP Powers You Up."` — gradient `linear-gradient(to right, #111827, #9ca3af, #d1d5db)`, `background-clip: text`, `color: transparent`

### Sous-titre (delay 0.4s)
- `font-size: 18px`, `color: #6b7280`, `max-width: 672px`, `margin: 0 auto 32px`, `line-height: 1.6`
- Texte : `"FrankenPHP + Symfony on Clever Cloud — worker mode, early hints, and API Platform out of the box."`

### CTAs (delay 0.5s)
- Bouton primaire : `"Open Swagger UI"` → `/api` — bg `#111827`, text blanc, `px-8 py-3`, rounded-full, font-medium, hover bg `#374151`
- Lien secondaire : `"Monsters API"` → `/api/monsters.jsonld` — text `#111827`, underline offset, margin-left 24px

---

## Section 3 — Tabs + Vidéo

**Container :** `position: relative`, `border-radius: 24px`, `overflow: hidden`, `height: 500px` (desktop) / `400px` (mobile)  
**Animation :** `.animate-fade-in-up`, delay 0.7s

### Vidéo fond
```html
<video src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260319_165750_358b1e72-c921-48b7-aaac-f200994f32fb.mp4"
  autoplay loop muted playsinline
  style="width:100%;height:100%;object-fit:cover;">
</video>
```

### Barre de tabs (delay 0.6s)
- Container : `bg: #f3f4f6`, `border-radius: 8px`, `padding: 4px`, centré au-dessus de la vidéo
- **Mobile** : grille 2×2
- **Desktop** : flex row avec séparateurs `1px solid #d1d5db` entre chaque tab
- Tab actif : `bg: white`, `color: #111827`, `box-shadow: 0 1px 3px rgba(0,0,0,0.1)`
- Tab inactif : `color: #6b7280`
- Auto-cycle : `setInterval` 4000ms via vanilla JS, state géré avec `data-active` attribut

| Tab | Icône SVG | Label |
|-----|-----------|-------|
| `worker` | Éclair | Worker Mode |
| `api` | Lien | API Platform |
| `perf` | Bar chart | Performance |
| `deploy` | Fusée | Deploy |

### Overlays (un par tab, hidden/shown via JS)

**Worker Mode** (`.overlay-worker`)  
Card blanche, `border-radius: 12px`, `padding: 24px`, `width: 320px`  
- Titre : `"FrankenPHP Worker"` (font-weight 600)
- Barre de progression violette : 25%
- 4 steps : `Boot · Warm · Ready · Serving` (checkmark sur les 2 premiers, spinner sur "Ready")

**API Platform** (`.overlay-api`)  
Card blanche, même dimensions  
- Titre : `"API Platform"` (font-weight 600)
- Barre orange : 67%
- 4 endpoints : `GET /monsters` · `POST /monsters` · `GET /monsters/{id}` · `DELETE /monsters/{id}`
- Badge méthode coloré (GET=vert, POST=bleu, DELETE=rouge)

**Performance** (`.overlay-perf`)  
Card blanche  
- Titre : `"Benchmark Results"` — badge vert `"✓ Passed"`
- 4 métriques : `12 400 req/s` · `P99 < 2ms` · `0 errors` · `Worker mode ON`
- Fond vert très léger sur container

**Deploy** (`.overlay-deploy`)  
Card blanche  
- Titre : `"Deploy to Clever Cloud"`
- 4 checklist items (tous cochés) :
  - `✓ Dockerfile detected`
  - `✓ FrankenPHP runtime`
  - `✓ Environment variables set`
  - `✓ Health check passing`
- Bouton : `"View on Clever Cloud"` — bg noir, rounded-full, text blanc, text-sm

---

## Section 4 — Bande de logos tech

**Layout :** `margin-top: 96px`, flex centré, `gap: 48px`, `flex-wrap: wrap`  
**Animation :** `.animate-fade-in-up`, delay 0.8s  
**Style :** typos variées (uppercase, serif italic, bold) sur fond blanc, couleur `#9ca3af`

| Logo | Style |
|------|-------|
| `FRANKENPHP` | uppercase, font-weight 700, letter-spacing wide |
| `Symfony` | italic serif |
| `Clever Cloud` | dots grid + texte |
| `API Platform` | M3-style, serif italic |
| `CADDY` | uppercase, monospace |
| `Docker` | circle icon + texte |

---

## Section 5 — Footer

**Layout :** `border-top: 1px solid #f0f0f0`, `padding-top: 22px`, `margin-top: 32px`  
**2 lignes :**

1. Liens flex wrappés, centré :
   `clever-cloud.com` · `Docs PHP` · `FrankenPHP` · `Symfony` · `API Platform` · `Certification CC`  
   Style : text-sm, color `#9ca3af`, hover `#111827`, gap 16px

2. Copyright : `"Open source demo · Deployed on Clever Cloud"` — text-xs, color `#d1d5db`, centré

---

## Fichiers modifiés

| Fichier | Action |
|---------|--------|
| `templates/base.html.twig` | Réécriture complète CSS (Stellar.ai) + import Inter |
| `templates/homepage/index.html.twig` | Réécriture complète contenu + JS tabs |

---

## Résultat attendu

Page blanche, Inter, navigation sticky, hero animé avec gradient text, section tabs interactive (auto-cycle 4s) avec vidéo fond et overlays contextuels FrankenPHP, bande logos tech, footer minimal. Aucune dépendance nouvelle, déploiement immédiat via `git push` sur Clever Cloud.
