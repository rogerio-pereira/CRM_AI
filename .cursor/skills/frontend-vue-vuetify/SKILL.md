---
name: frontend-vue-vuetify
description: >
  Guides all frontend development in the project using Vue 3 and Vuetify 3
  aligned with the branding manual. Use this skill whenever creating, changing
  or reviewing screens, Vue components, layouts, frontend navigation or UI
  tests, ensuring exclusive use of Vuetify components, respect for GoViral’s
  dark neon theme, Pest Browser tests only (no Laravel Dusk), and stable E2E selectors.
---

# Frontend Vue 3 + Vuetify 3 (GoViral)

## When to use this skill

Use this skill whenever you:

- Create **new screens** or **navigation flows**.
- Create or change **Vue components** (`.vue`) or Inertia layouts.
- Work with **tables, forms, buttons, dialogs, visual feedback**.
- Create or update **frontend tests**, including Browser tests.

If the task touches UI/UX, Vue, Vuetify or navigation, follow these instructions.

---

## Mandatory foundations

1. **Stack**
   - Always use **Vue 3** (preferably Composition API).
   - Always use **Vuetify 3** for ALL UI elements.

2. **Branding**
   - Follow the manual in `docs/03 - Branding Manual.md`.
   - Base UI in **dark mode** with:
     - Main background: `#121212`.
     - Neon accents using:
       - **Primary Pink**: `#FE2C55`
       - **Primary Teal**: `#25F4EE`
   - Visual style:
     - **High contrast** layouts.
     - **Smooth hover transitions**.
     - **Subtle glow** on interactive elements (buttons, CTAs).
     - Modern micro-interactions with minimal clutter.
   - Typography:
     - Headline: **Space Grotesk**.
     - Body: **Inter**.
     - Strong CTAs may use UPPERCASE, but sparingly.

3. **Voice & microcopy**
   - Modern, tech-forward, friendly, energetic.
   - Avoid “guru” language or exaggerated promises.
   - Prefer the suggested microcopy:
     - Instead of "Submit": **"Start My Growth"**.
     - Instead of "Generate Report": **"Generate My Growth Blueprint"**.
     - Instead of "Processing": **"Analyzing Your Growth Potential..."**.

Whenever in doubt about colors, tone or style, read
`docs/03 - Branding Manual.md` again before proposing a solution.

---

## UI components rules (Vuetify 3)

### Exclusive use of Vuetify

- **Always** use Vuetify components for:
  - **Layout**: `v-app`, `v-main`, `v-container`, `v-row`, `v-col`, `v-card`, etc.
  - **Typography**: `v-text`, `v-alert` (as a message component only, not `window.alert`), titles in slots or typographic components.
  - **Inputs**: `v-text-field`, `v-select`, `v-autocomplete`, `v-textarea`,
    `v-checkbox`, `v-radio-group`, `v-switch`, etc.
  - **Buttons**: `v-btn`, with variants and icons (`v-icon`) when it makes sense.
  - **Tables**: **must** be `v-data-table` (or Vuetify variants, such as `v-data-table-server`).
  - **Feedback**: `v-snackbar`, `v-tooltip`, `v-alert`, `v-progress-linear`,
    `v-progress-circular`, etc.
  - **Dialogs**: `v-dialog` (including confirmations).

### Prohibitions

- **Never** use native browser APIs for UX:
  - `alert(...)` is forbidden → use `v-snackbar`, `v-tooltip` or `v-alert`.
  - `confirm(...)` is forbidden → use `v-dialog` with clear actions (e.g. "Cancel", "Confirm").
  - `prompt(...)` is forbidden → use Vuetify forms inside `v-dialog`.
- Avoid raw HTML manually styled for components that already exist in
  Vuetify (inputs, buttons, cards, badges, etc). If there is a Vuetify component,
  it must be used as the base.

### Tables (`v-data-table`)

For any tabular listing:

- Use `v-data-table` as the default component.
- Use:
  - Pagination, sorting and display props (e.g. `items-per-page`, `items`,
    `headers`).
  - Slots (`item.<field>`, `top`, `bottom`) for actions, filters or CTAs.
- Style it respecting the dark theme (dark background, soft contrast between rows
  and hovers with a light glow/highlight).

---

## Layout and experience patterns

- Base everything on a root layout (`AppLayout`, etc.) that:
  - Uses `v-app` and `v-main`.
  - Keeps the dark background (`#121212`).
  - Applies primary colors according to branding.
- For key CTAs:
  - Use `v-btn` with `color` bound to the primary colors (Pink/Teal).
  - Include micro-interactions (hover, ripple, light shadow/glow).
- Navigation:
  - Use appropriate Vuetify components: `v-app-bar`, `v-navigation-drawer`,
    `v-tabs`, etc., when needed.

---

## Stable selectors for E2E

- Expose **stable** attributes on interactive elements used in tests: prefer **`data-test="..."`** (Pest Browser resolves `@name` to `[data-test="name"]`) and/or explicit `v-text-field` / form **`name`** props.
- Keeps tests resilient to copy and layout changes. Aligns with `.cursor/AGENTS.md` and `.cursor/rules/starting-environment.mdc`.

---

## Frontend tests: Pest Browser (mandatory)

All frontend flows/screens must have **Browser tests** using **Pest Browser** only:

- **Do not** add or use **Laravel Dusk** (`dusk` selectors, Dusk test cases, or Dusk-only setup).
- Use the **Pest Browser plugin** as per official documentation:
  - Reference: [`https://pestphp.com/docs/browser-testing`](https://pestphp.com/docs/browser-testing)
- For each new page or relevant flow:
  - Create **at least one navigation and smoke test**:
    - Check that the route opens without errors.
    - Check presence of key texts, CTAs, main UI elements.
    - Optionally check absence of console / JS errors when it makes sense.

### Specific test rules

- **All frontend tests** (for new UI features) must include:
  - A Browser test that:
    - Uses `visit('/route')`.
    - Uses assertions like `assertSee`, `assertUrlIs` / `assertPathIs`,
      and validates important elements.
  - When there are forms:
    - Fill fields with `fill` / `type`.
    - Submit (e.g. `click('Start My Growth')`).
    - Validate results: messages, redirects, data presence.
- Maintain at least:
  - **Navigation smoke tests** between main pages (landing,
    forms, dashboards, etc.).
  - **Basic accessibility smoke tests** when appropriate
    (`assertNoSmoke` or `assertNoAccessibilityIssues`) if they are
    configured.

### Examples of common scenarios (high level)

- Page opens correctly:
  - Visit the route.
  - See a headline aligned with branding (e.g. "Engineered for Viral Growth.").
  - See the main CTA (e.g. "Start My Growth").
- Form flow:
  - Fill in required fields.
  - Validate validation errors when fields are missing.
  - Confirm success (message, redirect).
- Tables:
  - Validate that the table renders (`v-data-table`).
  - Check that columns and some rows are visible.
  - (Optional) Test basic pagination/sorting.

---

## How to apply this skill to tasks

When implementing or reviewing frontend work:

1. **Confirm stack and dependencies**
   - Ensure the component/screen uses **Vue 3** + **Vuetify 3**.
   - Check that there is no raw HTML that could be replaced by Vuetify in key elements.

2. **Apply branding**
   - Ensure dark mode and use of primary Pink/Teal colors.
   - Review text to follow the modern, direct and friendly tone.
   - Prefer the microcopy suggested by the Branding Manual for CTAs.

3. **Check prohibitions**
   - Ensure there is **no** `alert`, `confirm`, `prompt`.
   - Confirm feedback uses `v-snackbar`, `v-tooltip`, `v-alert`, etc.
   - Confirm confirmations use `v-dialog`.

4. **Ensure correct tables**
   - If there is tabular data, ensure use of `v-data-table`.
   - Check headers, columns and pagination UX.

5. **Create / update Browser tests**
   - Add Pest Browser tests for every new page/flow.
   - Cover at least:
     - Route access.
     - Essential elements on screen.
     - Main flows (e.g. form submit, main navigation).

6. **Review overall experience**
   - Check focus, basic accessibility and clear feedback.
   - Avoid clutter and keep the experience fast, clear and smart,
     aligned with the brand positioning.

---

## Quick summary (checklist)

- [ ] Using **Vue 3 + Vuetify 3**?
- [ ] All UI elements are Vuetify components (including tables, inputs, buttons)?
- [ ] No native `alert` / `confirm` / `prompt`?
- [ ] Confirmations via `v-dialog`?
- [ ] Feedback via `v-snackbar` / `v-tooltip` / `v-alert` etc.?
- [ ] Dark mode `#121212` with highlights `#FE2C55` and `#25F4EE`?
- [ ] Typography aligned (Space Grotesk + Inter) and CTAs with brand microcopy?
- [ ] Tabular data using `v-data-table`?
- [ ] **Pest Browser tests** created/updated covering navigation and smoke (no Dusk)?
- [ ] Main flows (forms, navigation, dashboards) covered with Browser tests?
- [ ] Interactive elements use **`data-test`** (or equivalent stable hooks) where E2E needs them?

