# ADR-017: Wave 4 AI qualification flow and insight schema

## Status

Accepted (amended 2026-08-13; service catalog interpretation partially superseded by [ADR-020](ADR-020-commercial-service-catalog-boundary.md); amended 2026-09-08 to drop a fixed service ranking)

ADR-017 remains authoritative for qualification. Where the historical text calls `docs/services/` the service source of truth, ADR-020 narrows that role to qualification categories and makes the database catalog authoritative for priced proposal line items.

## Context

Wave 4 implements automated prospecting, automated lead qualification, and AI recommendations. Earlier planning left several Wave 4 product decisions open: whether manually created leads are qualified automatically, which pipeline stage follows successful qualification, how AI status/errors are displayed, and what schema should be used for persisted AI insight data.

Stakeholder review on 2026-07-31 closed the original gaps with a **client-scoped** qualification model: all created leads were qualified automatically, users did not manually start qualification, and AI state was shown through database status values rendered as labels/chips in the UI.

Stakeholder review on 2026-08-13 superseded the storage owner. A Client is the company. The same company can have many commercial deals over time (for example a website now, content creation months later, email marketing and automation after that, and a new website years later). Prospecting finds the company; it does not freeze that company to a single opportunity. Qualification is therefore an **Opportunity** concern.

The 2026-07-31 decisions below that placed qualification status, errors, timestamps, and schema v1 insights on the Client are **superseded** by the 2026-08-13 amendment. Pipeline target **Contact**, status vocabulary, retry count, schema version 1, required `contact_example`, and “no automatic outreach” still stand.

## Decision

### Amendment 2026-08-13 — qualification belongs to the opportunity

1. **All created opportunities are automatically qualified.**
   - Any new Opportunity enters the qualification flow, whether created by prospecting or manually by a user.
   - Creating a Client without an Opportunity does not start qualification.
   - The UI must not require users to click a "Qualify" action for normal processing.
   - A client that already has a qualified opportunity must not skip qualification of a later opportunity.

2. **Successful qualification keeps that opportunity in Qualification until the first-contact email job finishes.**
   - When a qualification job starts, **that** opportunity in `Lead` may move to `Qualification`.
   - When qualification succeeds, **that** opportunity stays in `Qualification` and a first-contact email job is dispatched.
   - When that email is written, **that** opportunity moves to `Contact`.
   - Sibling opportunities on the same client are not moved.
   - `Contact` remains human-driven per [ADR-019](ADR-019-human-controlled-proposal-delivery.md); AI does not send outreach.

3. **Use a simple qualification status column and UI chips on the opportunity.**
   - Add dedicated opportunity qualification fields rather than overloading client lifecycle `status` or treating the company as the qualified record.
   - Values:
     - `pending` - opportunity exists and qualification is waiting to run.
     - `processing` - qualification job is running.
     - `qualified` - AI qualification completed successfully for this opportunity (`qualified` is job success, not “good commercial fit”).
     - `failed` - AI qualification failed after retries or terminal error.
        - Should retry 3 times.
   - Render the status as a compact label/chip on the Kanban and opportunity detail.

4. **Persist user-safe AI error state on the opportunity.**
   - Store a short, non-sensitive error message for failed qualification.
   - Keep detailed stack traces and provider diagnostics in Laravel logs/Horizon, not in CRM UI.
   - Fields on **opportunities**:
     - `qualification_status`
     - `qualification_last_error`
     - `qualified_at`
     - `qualification_notes` (AI summary for this deal)

5. **Persist AI insights using schema version 1 on the opportunity.**
   - Store the canonical qualification payload in `opportunities` (schema version 1 JSON; `ai_insights` on the opportunity).
   - Store later opportunity-specific recommendations in `opportunities.ai_recommendations` when the recommendation depends on that opportunity ([12 AI recommendations and insights](../05%20-%20Feature%20List.md#f12-ai-recommendations)).
   - Schema versioning allows later extension without breaking old records.
   - Every successful qualification must include an email `contact_example` inside `outreach_strategy`.
   - The email example is required for internal guidance only; it is never sent automatically.

6. **Initial prospecting qualification uses the full `docs/services/` catalog on one opportunity.**
   - When the lead is created by the Prospecting Agent, the first opportunity is qualified against **every** service markdown file in `docs/services/` (read each file in full).
   - Do **not** create one opportunity per service for a new client. Prospecting still creates a single opportunity; that record stores the catalog scan in `ai_insights.opportunities` (one entry per service, including low-fit).
   - Later opportunities on the same client are qualified as **that** deal only. The catalog files remain the service source of truth.

```json
{
  "schema_version": 1,
  "generated_at": "2026-07-31T00:00:00Z",
  "source_agent": "qualification|recommendation",
  "language": "en",
  "summary": "Short plain-language business summary.",
  "fit": {
    "level": "high|medium|low",
    "label": "Ready to Contact|Worth Watching|Low Fit",
    "reason": "Plain-language reason for the fit level."
  },
  "pain_points": [
    {
      "title": "Outdated website",
      "evidence": "Observed public signal.",
      "business_impact": "Why this may reduce leads, sales, or time."
    }
  ],
  "opportunities": [
    {
      "service": "lead_generation|email_marketing|website_design_development|content_creation|business_automation|custom_software_development",
      "title": "Practical opportunity title.",
      "why_it_matters": "Simple business outcome.",
      "priority": "high|medium|low"
    }
  ],
  "outreach_strategy": {
    "positioning": "Simple friendly angle for the first conversation.",
    "talking_points": ["Plain-language point."],
    "contact_example": {
      "channel": "email",
      "subject": "Short friendly email subject a human may adapt later.",
      "body": "Required friendly internal email example a human may adapt later. This is not sent automatically."
    },
    "avoid": ["Anything that would sound too technical or aggressive."]
  },
  "sources": [
    {
      "label": "Public source name",
      "url": "https://example.com",
      "observed_at": "2026-07-31T00:00:00Z"
    }
  ],
  "confidence": "high|medium|low"
}
```

### Amendment 2026-09-08 — no fixed service ranking

Prospecting, qualification, and recommendation analyze the **whole** business. They do not apply a global service ranking and do not treat website work as the required commercial opening.

Priority comes from evidence on this company. Recurring catalog work (lead generation, content, email, automations) is often the better first engagement. A website rebuild is an opening only when the public site is missing, broken, or clearly blocking inquiries; otherwise it is a later upsell. Custom software stays in the catalog scan and stays low unless a simpler service cannot cover a clear operational need.

Do not invent benefits such as “a branded mailbox raises prices” or “a new site will convert because it is custom.” Order `ai_insights.opportunities` by actual need for this company, highest first. The top pain, top opportunity, talking points, and first-contact email must describe the same opening.

### Service opportunity reference examples

These examples guide AI recommendations and internal sales notes. They are tone references, not a ranking and not an opening order. They should frame services as practical opportunities to grow revenue, save time, and reduce friction, not as expenses the prospect is being pressured to buy. Mental triggers may be used with a light hand. Never sound like someone selling insurance, a car, or solar panels.

| Service | Reference angle |
| ------- | --------------- |
| `lead_generation` | Show the owner that referrals are valuable, but they should not be the only path to new work. Frame lead generation as a way to create a steadier flow of opportunities. |
| `email_marketing` | Position email as a simple way to stay remembered by people who already know or considered the business. Emphasize follow-up and repeat revenue. |
| `website_design_development` | Frame the website as the first trust check before someone calls. Recommend a rebuild only when the current site is actually in the way. |
| `content_creation` | Present content as useful local proof and education, not vanity posting. Emphasize consistency and trust. |
| `business_automation` | Position automation as removing repeated manual work so the owner has more time for customers and sales. |
| `custom_software_development` | Use only when there is a clear operational need. Frame as a tailored tool after simpler options are considered, not as the first pitch. |

Qualification analysis does **not** write the finished `contact_example`. A dedicated first-contact email job runs after qualification succeeds: it calls `write_first_contact_email`, stores `contact_example`, then moves the opportunity to `Contact` and dispatches recommendation. Gemini rejects mixing built-in `WebSearch` / `WebFetch` with a custom function on the same request, so the qualification analysis agent keeps only those provider tools. Recommendation analysis has no built-in tools, so it still calls `write_first_contact_email` itself. Both email paths must pass `line_of_business` (what the client does, not a Front Porch service name). The subject uses one emoji that belongs to that email. The body names the client's trade in plain language.

## Prompt assets

Wave 4 agent prompts are versioned in:

- `docs/prompts/prospecting-agent.md`
- `docs/prompts/prospecting-discovery.md`
- `docs/prompts/qualification-agent.md`
- `docs/prompts/recommendation-agent.md`
- `docs/prompts/laravel_tools/write-first-contact-email.md`

The qualification service catalog is the markdown files in `docs/services/` (read in full; do not parse).

Cold outreach and generated `contact_example` output must follow:

- `docs/prompts/laravel_tools/write-first-contact-email.md`
- `docs/prompts/references/frontporch-creative-briefing.md`
- `docs/prompts/references/frontporch-creative-design-system.md`
- `docs/prompts/references/cold-outreach-email-guidelines.md`

Prompt content is source-controlled, but production logs must not include full prompt text or sensitive lead content.

### Original 2026-07-31 decisions (client-scoped; superseded)

The following text is retained for history. Do not implement it.

1. ~~All created leads are automatically qualified.~~
2. ~~Successful qualification advances linked opportunities to Contact.~~
3. ~~Dedicated qualification status column on the lead/client.~~
4. ~~Persist user-safe AI error state on the lead.~~
5. ~~Store the canonical payload in `clients.ai_insights`.~~

## Consequences

- **Positive:**
  - Removes manual qualification decisions from the user workflow.
  - Keeps the CRM simple for a non-specialist sales team.
  - Makes UI states easy to understand through status chips.
  - Gives tests and Livewire components a stable AI payload contract.
  - A returning client can open a new opportunity months or years later and receive a fresh analysis for that deal.
- **Negative:**
  - Automatically qualifying every created opportunity can increase AI usage over the client-scoped model.
  - Simple status values do not expose detailed AI observability in the product UI.
- **Neutral:**
  - Deduplication remains per ADR-015 at **company** create time; additional valid opportunities on an existing client are new sales cycles, not duplicates.
  - PRD/HLD still list qualification notes and AI insights on Lead/Client as company attributes; this amendment stores **job** qualification state and schema v1 analysis on the Opportunity. Client may keep free-text company notes from prospecting or humans; those notes are not the qualification chip.
