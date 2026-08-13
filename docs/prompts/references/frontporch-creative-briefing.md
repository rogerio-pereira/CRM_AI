# Front Porch Creative — Website Discovery Briefing

**Document version:** 1.0  
**Date:** June 30, 2026  
**Language:** English (website and all public-facing content)  
**Prepared from:** Client discovery interview

---

## 1. Executive Summary

Front Porch Creative is a new, two-person marketing agency based in Plant City, Florida, serving small regional businesses within a ~60-mile radius. The agency combines website design, lead generation, email marketing, custom software development, and business automations into integrated growth systems—not standalone deliverables.

The primary website goal is **generating qualified lead appointments**. The site will function as a long-form landing page supported by service-specific landing pages, an internal CMS-driven blog and portfolio, and legal pages. Design direction emphasizes calm whitespace, a modern futuristic aesthetic, and the "front porch conversation" brand metaphor—approachable, trustworthy, and results-oriented without aggressive sales tactics.

**Key constraints:** No existing content, portfolio, or testimonials; new domain with no SEO history; internal development on Laravel + Inertia + Vue hosted on Laravel Cloud. Brand logos and fonts are available in `docs/branding/`. MVP excludes newsletter signup, live chat, payments, and staging environments.

**Primary success metrics:** 10–20 leads/month, 3–4 scheduled appointments/month, minimum 1 new client conversion/month.

---

## 2. Business Overview

### Company Profile

| Field | Detail |
|-------|--------|
| **Company name** | Front Porch Creative |
| **Industry** | Marketing agency / digital growth |
| **Stage** | Early-stage startup (new business) |
| **Team size** | 2 |
| **Current revenue** | $0 |
| **Current clients** | 0 |
| **Primary market** | Central Florida (~60-mile radius from Plant City) |
| **Service area** | Tampa to Orlando, Sarasota to Wesley Chapel |

### Origin Story

Front Porch Creative was founded on the observation that many small local businesses deliver exceptional services but struggle to stand out and grow in the digital environment. While large companies have entire marketing and technology teams, small businesses often manage outdated websites, manual processes, and marketing strategies that fail to produce measurable results.

After years working in software development, automation, and technology, the founders identified an opportunity to bring modern, accessible solutions to local businesses—combining technology, marketing, and automation in a simple, human way.

The name **"Front Porch"** reflects the belief that the best marketing starts with genuine conversations, trust-based relationships, and a deep understanding of each client's needs—as natural as a conversation on a front porch.

### Mission

Empower small businesses to grow through technology, marketing, and automation by delivering simple, effective, results-driven solutions.

### Vision

Become the most trusted growth partner for local small businesses, making technology and marketing accessible, human, and genuinely useful.

### Values

1. **Relationships first** — Long-term partnerships built on trust, transparency, and open communication.
2. **Results over vanity** — Solutions that generate real business impact, not superficial metrics.
3. **Intentional simplicity** — Technology should simplify life, not complicate it.
4. **Craft excellence** — Every project treated with care, attention to detail, and high quality standards.
5. **Practical innovation** — Modern technology applied strategically and accessibly, focused on real client needs.
6. **Commitment to small business** — Passionate about helping local entrepreneurs compete and thrive digitally.

### Unique Selling Proposition (USP)

Front Porch Creative does not just build websites—it develops **growth systems** for small businesses.

While many agencies focus exclusively on design or digital presence, Front Porch combines marketing strategy, technology, and automation to deliver measurable results.

**Differentiators:**

- **Results-oriented approach** — Every project focused on lead generation, revenue growth, and operational efficiency.
- **Custom technology** — Modern, tailored solutions avoiding over-reliance on generic platforms and plugins.
- **Intelligent automation** — Reducing repetitive tasks, saving time, and improving customer experience through business and operational automations.
- **Close, human partnership** — Long-term relationships working side-by-side as strategic partners, not vendors.
- **Integrated business view** — Website, marketing, automation, and processes unified in one growth strategy.

**Goal:** Let business owners focus on what they do best while Front Porch handles the technology and processes that drive growth.

### Services

1. Website design and development
2. Lead generation
3. Email marketing
4. Custom software development
5. Business automations

### Growth Targets

| Timeframe | Monthly revenue | Monthly retainer clients | Average ticket |
|-----------|-----------------|--------------------------|----------------|
| **1 year** | $15,000 – $30,000 | 15 – 30 | $500 – $1,000 |
| **3 years** | $30,000 – $60,000 | 30 – 60 | $1,000 – $2,000 |

---

## 3. Website Goals

### Primary Purpose

The website serves multiple complementary purposes:

- Generate qualified leads
- Build credibility and authority
- Schedule discovery/strategy sessions
- Educate the market on services and approach
- Showcase portfolio and case studies (as assets become available)

### Desired Visitor Actions

1. Complete a contact form and schedule a meeting
2. Read blog articles
3. Explore portfolio work

### Success Criteria

| Metric | Target |
|--------|--------|
| **Leads per month** | 10 – 20 |
| **Scheduled appointments per month** | 3 – 4 (minimum) |
| **Client conversions per month** | ≥ 1 |

### Single Most Important Goal

**Generate qualified lead appointments.**

All other goals (content, portfolio, credibility) support this primary conversion objective.

---

## 4. Target Audience Profile

### Ideal Customer Profile (ICP)

- **Who:** Owners of small regional businesses
- **Who to avoid:** Medium-sized businesses, large companies, and corporations
- **Decision maker:** Business owner (primarily); occasionally a manager
- **Demographics:** Men and women, ages 30–40
- **Tech literacy:** Basic—comfortable with fundamentals, not technical experts
- **Geography:** Within ~60 miles of Plant City, Florida

### Customer Pain Points

- Outdated website
- Website not optimized for mobile devices
- Need to automate manual business processes
- Need for custom software solutions
- "Hope-based" marketing with no measurable results
- Not attracting customers online
- Low visibility—people don't know the business exists
- Low sales volume

### Buying Behavior

- **Discovery channels:** Google search, social media; referrals produce hot leads but low volume
- **Decision timeline:** Approximately 1–2 weeks after proposal is sent
- **Formal research:** Not yet conducted by the client

### Objections to Address

**Business objections:**

- Distrust because Front Porch is a new business
- "I already have a website"
- Resistance to spending on advertising
- Does not understand what is being sold

**Personal objections:**

- Brazilian accent (founders)—may affect perceived trust with some prospects

### Website Implications

- Clear, jargon-free explanation of services and outcomes
- Emphasis on measurable results and transparent process
- Trust-building elements: process section, FAQ, "Who we are," future testimonials
- Warm, human tone that reduces "vendor" friction
- Local positioning for Central Florida small businesses

---

## 5. Proposed Sitemap

### Site Architecture Overview

```
frontporchcreative.io (canonical)
├── /                          Home (long-form landing page)
├── /services/{service-slug}   Service landing pages (×5)
├── /portfolio                 Full portfolio
├── /blog                      Blog listing
├── /blog/{slug}               Individual articles
├── /privacy                   Privacy Policy
└── /terms                     Terms of Service

Redirects:
  frontporchcreative.agency    → frontporchcreative.io
  frontporchcreative.marketing → frontporchcreative.io
```

### Home Page Sections (in order)

| # | Section | Notes |
|---|---------|-------|
| 1 | Hero + scheduling CTA | Primary conversion point |
| 2 | Problems | Customer pain points |
| 3 | Services | Each service links to dedicated landing page |
| 4 | CTA | |
| 5 | Portfolio | Up to 12 items; "See more" links to full portfolio page |
| 6 | Testimonials | |
| 7 | CTA | |
| 8 | How it works / Process | |
| 9 | FAQ | Accordion on home page only |
| 10 | CTA | |
| 11 | Why Front Porch | |
| 12 | Who we are | |
| 13 | CTA | |
| 14 | Contact | |
| 15 | CTA | |
| 16 | Blog preview | 3–6 latest articles + link to full blog |

### Standalone Pages

| Page | MVP | Notes |
|------|-----|-------|
| Home | ✅ | Long-form landing |
| Service landing pages | ✅ | One per service; also used for paid ads |
| Portfolio (full) | ✅ | Linked from home portfolio section |
| Blog listing | ✅ | |
| Article detail | ✅ | |
| Privacy Policy | ✅ | Content to be drafted |
| Terms of Service | ✅ | Content to be drafted |

### Service Landing Pages

One dedicated landing page per service:

1. Lead generation
2. Email marketing
3. Website design and development
4. Business automations
5. Custom software development

These pages serve both organic traffic and paid ad campaigns.

### Out of Scope (MVP)

- Dedicated About page (covered in "Who we are" home section)
- Dedicated Contact page (covered in home section)
- Newsletter signup
- Live chat
- User accounts / client portal
- Payment processing

---

## 6. Functional Requirements

### Lead Capture Form

| Field | Required |
|-------|----------|
| Name | ✅ |
| Email | ✅ |
| Phone | ✅ |
| Message | ✅ |

- **Delivery:** Email notification (MVP)
- **CRM integration:** Deferred—internal CRM in parallel development
- **Newsletter:** Not in MVP; focus on warm and hot leads only

### Appointment Scheduling

| Requirement | Detail |
|-------------|--------|
| **Provider** | Google Calendar |
| **Implementation** | Redirect to external scheduling link |
| **Session duration** | TBD—initially based on 2-hour briefing model; details to be defined later |
| **User notice** | Site should communicate session length once finalized |

> **Note:** Calendar integration only for MVP. Session duration, meeting type, and copy will be refined post-launch.

### Portfolio (CMS)

- Managed via internal CMS (built with the website)
- Home displays up to 12 featured items
- "See more" links to full portfolio page on the same site
- Admin-only file uploads within CMS—no public upload functionality

### Blog (CMS)

- Managed via internal CMS
- **Comments:** Disabled
- **Categories:** Enabled
- **RSS feed:** TBD
- Publishing workflow: founders

### FAQ

- Accordion component on home page only
- No dedicated FAQ page

### Analytics and Tracking

| Tool | MVP |
|------|-----|
| Google Analytics | ✅ |
| Meta Pixel | ✅ |

### Explicitly Excluded from MVP

| Feature | Status |
|---------|--------|
| Live chat | Future consideration |
| Newsletter / email capture | Future |
| Lead magnets | Future / undecided |
| Public file uploads | No |
| Site search | No |
| Multi-language | No (English only) |
| Online payments | No (Stripe if needed later) |
| User accounts | No |

---

## 7. Required Integrations

| Integration | MVP status | Notes |
|-------------|------------|-------|
| **Google Calendar** | ✅ Required | Scheduling redirect |
| **Email (form delivery)** | ✅ Required | Gmail initially |
| **Google Analytics** | ✅ Required | Account not yet created |
| **Meta Pixel** | ✅ Required | Account not yet created |
| **Internal CRM** | 🔄 Parallel dev | Not blocking website MVP |
| **Mautic / internal email marketing** | ⏳ Undecided | Not blocking website MVP |
| **Google Search Console** | 📋 Planned | Account not yet created |
| **Google Business Profile** | 📋 Planned | Not yet created; decision pending |
| **Google Ads** | 📋 Planned | ~$50/month initial budget |
| **Meta Ads** | 📋 Planned | ~$50/month initial budget |
| **n8n** | ❌ Not MVP | Available if needed later |
| **Stripe** | ❌ Not MVP | If payments needed in future |
| **AWS WorkMail** | ⏳ Future | Possible replacement for Gmail |
| **Third-party APIs** | ❌ None | — |

---

## 8. Branding Requirements

### Brand Asset Inventory

Source files live in **`docs/branding/`**:

| Asset | File | Format | Usage |
|-------|------|--------|-------|
| **Logo (vertical)** | `docs/branding/Logo.png` | PNG | Square/narrow layouts, social previews, mobile |
| **Logo (horizontal)** | `docs/branding/Logo Horizontal.png` | PNG | Header, footer, general horizontal layouts |
| **Montserrat Alt Thin** | `docs/branding/MontserratAlt1-Thin.ttf` | TTF | Logo, headings, accent text (bold weight) |
| **Montserrat SemiBold** | `docs/branding/Montserrat-SemiBold.ttf` | TTF | CTAs, highlights, active elements |
| **Montserrat Light** | `docs/branding/Montserrat-Light.ttf` | TTF | Body text |

> **Implementation note:** Self-host fonts from `docs/branding/` (or copy into the app asset pipeline) via `@font-face` / bundled webfonts. Do not rely on Google Fonts for Montserrat Alt Thin—use the provided TTF.

### Logo

- **Available:** Yes — see `docs/branding/`
- **Formats:** PNG — vertical (`Logo.png`) and horizontal (`Logo Horizontal.png`)
- **SVG:** Not provided — use PNG at appropriate resolutions or export SVG later if needed

### Color Palette

| Role | Hex | Notes |
|------|-----|-------|
| **Background** | `#192630` | Dark base |
| **Accent** | `#72887b` | Sage / muted green-gray |
| **Text** | White or near-white | High contrast on dark background |
| **Avoid** | Yellow | Client preference—do not use |

### Typography

| Usage | Font | Source file |
|-------|------|-------------|
| Logo, headings, accent text | Montserrat Alt Thin (bold weight) | `docs/branding/MontserratAlt1-Thin.ttf` |
| CTAs, highlights, active elements | Montserrat SemiBold | `docs/branding/Montserrat-SemiBold.ttf` |
| Body text | Montserrat Light | `docs/branding/Montserrat-Light.ttf` |

### Brand Personality

| Dimension | Descriptors |
|-----------|-------------|
| **Brand feel** | Light, futuristic, modern, accessible, simple |
| **Visitor feel** | Relief, lightness, ease |
| **Tone of voice** | Friendly, accessible, inspiring, energetic, excited |

### Design Direction

- **Format:** Long-form landing page aesthetic; blog on separate pages
- **Copy style:** Strong and persuasive with natural mental triggers—not aggressive or "hard sell"
- **Visual mood:** Modern, futuristic, and friendly
- **Layout:** Calm, tranquil, generous whitespace—visual representation of a front porch conversation
- **Visitor outcome:** "This is exactly what I needed but didn't know how to find"

### Design Anti-Patterns (Avoid)

- Generic WordPress template look
- Excessive technical jargon
- Aggressive sales copy (car salesman / solar panel tone)
- Yellow color
- Generic stock photography
- Cold, authoritative tone treating visitors as "just another lead"

### Brand Guidelines

- **Status:** Visual assets (logos + fonts) available in `docs/branding/`; no formal brand guidelines document yet—this briefing and asset folder serve as the initial brand reference

---

## 9. Content Requirements

### Current Content Inventory

| Asset | Status |
|-------|--------|
| Logo (vertical + horizontal) | ✅ `docs/branding/Logo.png`, `docs/branding/Logo Horizontal.png` |
| Brand fonts (×3) | ✅ `docs/branding/` (Montserrat Alt Thin, SemiBold, Light) |
| Website copy | ❌ None |
| Professional photography | ❌ None |
| Video content | ❌ None |
| Portfolio / case studies | ❌ None |
| Blog articles | ❌ None |
| Testimonials | ❌ None |
| Legal pages (Privacy, Terms) | ❌ None |

### Content Production

| Responsibility | Detail |
|----------------|--------|
| **Providers** | Founders (2-person team) |
| **Copywriting support** | AI-assisted; human review required |
| **Legal content** | AI draft + founder review |

### Content Development Phases

1. **Phase 1:** Static pages and landing page sections
2. **Phase 2:** Internal CMS (portfolio, blog)
3. **Phase 3:** Blog content and social media

### Content Gaps and Mitigation

Without portfolio, testimonials, or case studies at launch, the site must compensate through:

- Clear value proposition and service explanations
- Transparent process ("How it works")
- Strong FAQ addressing common objections
- "Who we are" section for humanization
- Founder photography (even casual/professional) to build trust
- Consider 2–3 pilot projects or prior work (with permission) for initial portfolio

---

## 10. SEO Requirements

### Geographic Scope

- **Primary radius:** ~60 miles from Plant City, Florida
- **Excluded:** Statewide coverage (no Miami, Jacksonville, or travel beyond radius)
- **Priority:** Closer to Plant City is better; no specific city priority within the radius
- **Example cities:** Plant City, Tampa, Brandon, Lakeland, Wesley Chapel, Orlando (partial), Sarasota (partial)

### Service SEO Priority

Ranked by: (1) recurring revenue potential, (2) ease of implementation, (3) price (higher first).

| Priority | Service | Rationale |
|----------|---------|-----------|
| **1** | Lead generation | High recurring value; aligns with primary site goal |
| **2** | Email marketing | Strong retainer model; complements lead funnel |
| **3** | Website design & development | Easiest to deliver; strong local SEO entry point |
| **4** | Business automations | Higher ticket; more complex implementation |
| **5** | Custom software development | Highest ticket; lowest recurring; most complex |

### Keyword Strategy (Template)

Apply service × city combinations within the 60-mile radius:

- `lead generation [city]`
- `email marketing [city]`
- `web design [city]`
- `business automation [city]`
- `custom software development [city]`
- `marketing agency [city]`
- `small business marketing [city]`

### SEO Scope

- **Traditional search engines:** On-page SEO, local SEO, structured data, technical SEO
- **LLM / AI search optimization:** Clear FAQ content, structured answers, semantic markup, content that directly addresses ICP questions

### Domain and SEO History

| Field | Detail |
|-------|--------|
| **Primary domain** | frontporchcreative.io |
| **Domain age** | New |
| **Previous site on domain** | None |
| **Competitor keyword research** | Not yet conducted |

### Content SEO (Blog)

- Blog topics: Not yet defined
- Blog is Phase 3—after pages and CMS are built
- Categories enabled for topical clustering

---

## 11. Technical Requirements

### Domains

| Domain | Purpose |
|--------|---------|
| `frontporchcreative.io` | **Canonical** production domain |
| `frontporchcreative.agency` | 301 redirect → `.io` |
| `frontporchcreative.marketing` | 301 redirect → `.io` |

### Hosting and Infrastructure

| Component | Detail |
|-----------|--------|
| **Hosting** | Laravel Cloud |
| **Stack** | Laravel + Inertia + Vue |
| **Environments** | Local development + production only (no staging) |
| **CI/CD** | Ready |
| **SSL** | Handled by Laravel Cloud |
| **Backups** | No specific requirements defined |
| **Monitoring** | No specific requirements defined |

### Email

| Phase | Provider |
|-------|----------|
| **Launch** | Gmail |
| **Future** | Possible AWS WorkMail |

### DNS

- Managed by the founder
- Must configure primary domain and redirects for secondary domains

### CMS (Internal)

Built as part of the website project:

- Portfolio management
- Blog management with categories
- Admin file uploads
- No public-facing upload endpoints

---

## 12. Legal and Compliance

### Required Legal Pages (MVP)

| Page | Required | Notes |
|------|----------|-------|
| **Privacy Policy** | ✅ Yes | Form data collection + analytics cookies |
| **Terms of Service** | ✅ Yes | Recommended for liability, IP, and scheduling |
| **Cookie consent banner** | ✅ Yes | Follow applicable regulations |

### Data Collection

- Contact form: name, email, phone, message
- Analytics cookies: Google Analytics, Meta Pixel

### Compliance Scope

| Requirement | Applicability |
|-------------|---------------|
| **Audience** | United States / Florida only |
| **GDPR** | Not primary concern |
| **CCPA** | Not primary concern (Florida-focused) |
| **Florida Digital Bill of Rights** | Follow applicable transparency requirements |
| **ADA / WCAG accessibility** | No specific requirement for MVP |
| **HIPAA / industry regulations** | Not applicable |

### Legal Content Production

- Draft: AI-generated
- Review and approval: Founder(s)

---

## 13. Marketing and Lead Generation

### Current State

- New business with no active marketing channels
- No existing leads or audience

### Planned Channels (Post-Launch)

| Channel | Strategy | Budget |
|---------|----------|--------|
| **Google Ads** | Service landing pages as destinations | ~$50/month initial |
| **Meta Ads** | Service landing pages as destinations | ~$50/month initial |
| **SEO (organic)** | Priority at launch + ongoing via blog | Time investment |
| **Instagram** | Organic posts | — |
| **Facebook** | Organic posts in local groups | — |
| **Google Business Profile** | TBD—not yet created | — |
| **Referrals** | Hot leads, low volume | — |

### Paid Ads Strategy

- Start with ~$100/month total ($50 Google + $50 Meta)
- Scale based on results
- Focus on 1–2 services and 1 geographic area initially given limited budget

### Not in MVP

- Newsletter campaigns
- Lead magnets (PDFs, checklists, audits)
- Email marketing to external lists
- Call tracking

---

## 14. Competitor and Design Reference

### Competitive Landscape

- Client perceives low local competition visibility
- Knows one marketing agency in Tampa
- No formal competitor research conducted
- **Risk:** May be biased; research recommended before launch

### Design Preferences

| Element | Direction |
|---------|-----------|
| **Site format** | Long-form landing page; blog on separate pages |
| **Copy** | Strong, persuasive, natural mental triggers—not aggressive |
| **Aesthetic** | Modern, futuristic, friendly |
| **Layout** | Calm, tranquil, generous whitespace |
| **Metaphor** | Front porch conversation |

### Design Anti-Patterns

- Generic WordPress templates
- Technical jargon
- Aggressive sales copy
- Yellow color
- Stock photography
- Cold, authoritative tone

---

## 15. Future Growth

| Area | Plan |
|------|------|
| **New features** | None planned—add as needed when identified |
| **Geographic expansion** | No—local focus only |
| **CRM/Mautic replacing external tools** | No |
| **Additional domains** | Redirect only to `.io` |
| **Live chat** | Possible future; not MVP |
| **Newsletter / lead magnets** | Undecided future |
| **Team expansion** | Not discussed |

---

## 16. Risks and Missing Information

| Risk / Gap | Impact | Mitigation |
|------------|--------|------------|
| **No portfolio or testimonials** | Low trust conversion | Process transparency, FAQ, founder story, pilot projects |
| **New business distrust** | Objection barrier | Human tone, local positioning, clear value proposition |
| **No content assets** | Delayed launch | AI-assisted copy; phased content rollout |
| **No competitor research** | Weak positioning/SEO | Conduct local competitor and keyword research |
| **Legal pages not drafted** | Launch blocker | AI draft + founder review before go-live |
| **Analytics/ad accounts not created** | No tracking at launch | Create GA, Meta, Search Console, GBP before launch |
| **CRM integration undefined** | Manual lead handling | Email delivery for MVP; CRM integration later |
| **Mautic vs internal email TBD** | Architecture uncertainty | Decide before email automation phase |
| **Brazilian accent concern** | Trust objection for some prospects | Lean into human, local brand; video/audio optional |
| **Gmail for business email** | Less professional perception | Migrate to AWS WorkMail or Google Workspace |
| **No staging environment** | Deployment risk | Rely on CI/CD and thorough local testing |
| **Blog content undefined** | Weak SEO initially | Launch with core pages first; blog in Phase 3 |
| **Meeting duration TBD** | Scheduling friction | Define calendar slots and communicate clearly on site |

---

## 17. Recommended Additional Services

These are recommendations for the founders—not website scope items:

1. **Local competitor and keyword research** — Validate positioning and SEO strategy before launch
2. **Google Business Profile setup** — High impact for local SEO in Central Florida
3. **Professional founder photography** — Humanizes brand; addresses "new business" objection
4. **2–3 pilot case studies** — Even discounted work builds portfolio and testimonials
5. **Google Workspace or AWS WorkMail** — Professional email on domain before heavy outreach
6. **Conversion-focused copy review** — Human review of AI-generated persuasive copy
7. **Legal review of Privacy/Terms** — Optional attorney review for Florida compliance
8. **Schema markup and LLM optimization** — Structured data, FAQ schema, clear service definitions

---

## 18. Suggested Next Steps

### Immediate (Pre-Development)

- [ ] Register brand fonts from `docs/branding/` in the app (self-hosted webfonts)
- [ ] Add horizontal and vertical logos from `docs/branding/` to the design system / public assets
- [ ] Confirm canonical domain setup and DNS redirects (`.agency`, `.marketing` → `.io`)
- [ ] Create Google Analytics, Search Console, and Meta Business/Pixel accounts
- [ ] Set up Google Calendar scheduling link
- [ ] Conduct basic local competitor and keyword research
- [ ] Decide on Google Business Profile creation

### Phase 1 — Pages

- [ ] Write home page copy (all 16 sections)
- [ ] Write 5 service landing page copies
- [ ] Draft Privacy Policy and Terms of Service
- [ ] Design and implement home page (Inertia + Vue)
- [ ] Implement service landing pages
- [ ] Implement lead form with email delivery
- [ ] Integrate Google Calendar redirect
- [ ] Add GA and Meta Pixel with cookie consent banner

### Phase 2 — CMS

- [ ] Build internal CMS for portfolio and blog
- [ ] Implement portfolio page (full) and home portfolio section
- [ ] Implement blog listing and article pages
- [ ] Add category support for blog

### Phase 3 — Content and Marketing

- [ ] Source founder photography
- [ ] Add initial portfolio items (pilot projects or permitted prior work)
- [ ] Collect first testimonials
- [ ] Publish initial blog articles (3–6 for home preview)
- [ ] Launch Google Ads and Meta Ads campaigns
- [ ] Set up Google Business Profile
- [ ] Begin Instagram and Facebook organic posting

### Post-Launch

- [ ] Monitor lead and appointment metrics against targets (10–20 leads, 3–4 appointments, ≥1 conversion/month)
- [ ] Integrate internal CRM when ready
- [ ] Decide Mautic vs internal email marketing platform
- [ ] Refine calendar session duration and meeting type
- [ ] Scale ad budget based on conversion data

---

*End of briefing document.*
