# ADR-017: Wave 4 AI qualification flow and insight schema

## Status

Accepted (amended 2026-08-13)

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

2. **Successful qualification advances that opportunity to Contact.**
   - When a qualification job starts, **that** opportunity in `Lead` may move to `Qualification`.
   - When qualification succeeds, **that** opportunity moves to `Contact`.
   - Sibling opportunities on the same client are not moved.
   - `Contact` remains human-driven per ADR-011; AI does not send outreach.

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

### Service opportunity reference examples

These examples guide AI recommendations and internal sales notes. They should frame services as practical opportunities to grow revenue, save time, and reduce friction, not as expenses the prospect is being pressured to buy. Use subtle mental triggers such as relief, clarity, missed opportunity, reciprocity, local trust, simplicity, cost of staying stuck, etc. Do not use fear, shame, urgency pressure, or technical jargon.

| Service | Reference angle | Example email reference |
| ------- | --------------- | ------------------------- |
| `lead_generation` | Show the owner that referrals are valuable, but they should not be the only path to new work. Frame lead generation as a way to create a steadier flow of opportunities. | Subject: "A simple way to bring in more local conversations" Body: "Hi {{contact_name}}, I noticed {{business_name}} already offers the kind of service people look for locally. There may be an opportunity to turn more of that local demand into steady conversations, instead of depending mostly on referrals. Would it be worth taking a quick look at where new leads may be slipping away?" |
| `email_marketing` | Position email as a simple way to stay remembered by people who already know or considered the business. Emphasize follow-up and repeat revenue. | Subject: "Staying in front of customers without adding more work" Body: "Hi {{contact_name}}, many local businesses lose potential jobs simply because busy customers forget to follow up. A simple email follow-up flow can help {{business_name}} stay remembered by people who already showed interest, without adding another task to your week." |
| `website_design_development` | Frame the website as the first trust check before someone calls. Focus on clarity, mobile experience, and making it easy to take the next step. | Subject: "Helping more visitors feel ready to call" Body: "Hi {{contact_name}}, your website is often where a customer quietly decides whether to call or keep looking. There may be an opportunity to make that first impression clearer, more current, and easier to act on, so more visitors become real conversations." |
| `content_creation` | Present content as useful local proof and education, not vanity posting. Emphasize consistency and trust. | Subject: "Turning your know-how into local trust" Body: "Hi {{contact_name}}, businesses like {{business_name}} usually have helpful knowledge customers would value before they ever call. Simple, consistent content can turn that knowledge into trust and make it easier for people nearby to choose you." |
| `business_automation` | Position automation as removing repeated manual work so the owner has more time for customers and sales. | Subject: "Fewer missed follow-ups, less manual work" Body: "Hi {{contact_name}}, if scheduling, quotes, or follow-ups are handled manually, small opportunities can slip through even when the service is great. There may be a simple way to reduce that busywork and help {{business_name}} keep more conversations moving." |
| `custom_software_development` | Use only when there is a clear operational need. Frame as a tailored tool after simpler options are considered, not as the first pitch. | Subject: "When the usual tools keep getting in the way" Body: "Hi {{contact_name}}, if your team is working around the same process problem every day, a small custom tool may eventually make sense. I would only explore that after checking whether a simpler fix could solve it first." |

### Complete email examples

These complete examples are reference material for AI-generated `contact_example` output. The agent must adapt them to the real lead evidence, but every successful qualification must include a complete email example with `subject` and `body`. Email structure must follow `docs/prompts/references/cold-outreach-email-guidelines.md`.

#### Lead generation

**Subject:** A simple way to bring in more local conversations

Hi Sarah,

I'm Roger from Front Porch Creative. I came across GreenSprout Lawn Care while looking at local service businesses around Lakeland.

One thing caught my attention: lawn care is the kind of service homeowners search for regularly, but many companies still depend mostly on referrals or seasonal word of mouth.

It made me wonder if there may be an opportunity to turn more of that local demand into steady quote requests, so GreenSprout is not waiting for the next referral to come in.

One quick idea: your quote request path could highlight the neighborhoods you already serve and make the next step obvious from the first screen. That small change can help local visitors feel like they found the right company faster.

Most of my work is focused on helping small local businesses create simple systems for more leads, clearer follow-up, and less guesswork.

Would you be open to hearing what I noticed? If it is not relevant, no worries at all.

Roger Pereira  
Front Porch Creative  
LinkedIn: linkedin.com/in/rogerio-pereira  
frontporchcreative.io

#### Email marketing

**Subject:** Staying remembered between pool visits

Hi Miguel,

I'm Roger from Front Porch Creative. I found BrightPool Service while researching pool companies near Tampa.

I noticed pool service is naturally recurring, but customers may only think about extra service, repairs, or referrals when someone reminds them at the right time.

There may be an opportunity to use simple email follow-ups to stay remembered by current customers and warm prospects without adding more work to your week.

One simple idea: a short seasonal reminder before heavy pool-use months could help customers schedule early instead of waiting until something feels urgent.

I help small local businesses create practical growth systems, especially around lead generation, follow-up, and customer communication.

Would you be interested in hearing the idea? Happy to share it briefly if it would be useful.

Roger Pereira  
Front Porch Creative  
LinkedIn: linkedin.com/in/rogerio-pereira  
frontporchcreative.io

#### Website design and development

**Subject:** Helping more visitors feel ready to call

Hi Amanda,

I'm Roger from Front Porch Creative. I came across CleanNest Home Services while looking at cleaning companies around Tampa.

While checking your online presence, I was thinking about how often a website becomes the first trust check before someone lets a company into their home.

There may be room to make that first impression clearer, more current, and easier to act on, so more visitors feel comfortable requesting a quote instead of continuing their search.

One small improvement could be moving the request-a-quote action closer to the top of the mobile page and pairing it with a clear service-area note. That gives busy visitors less to figure out.

Most of my work is helping local businesses make their digital presence easier to understand and more useful for bringing in real conversations.

Would you be open to a quick conversation about what I noticed?

Roger Pereira  
Front Porch Creative  
LinkedIn: linkedin.com/in/rogerio-pereira  
frontporchcreative.io

#### Content creation

**Subject:** Turning your know-how into local trust

Hi Daniel,

I'm Roger from Front Porch Creative. I found Happy Paws Pet Sitting while researching pet care businesses near Orlando.

One thing stood out to me: pet sitting is built on trust, but a lot of that trust has to happen before someone ever reaches out.

It made me wonder if there may be an opportunity to turn the knowledge you already share with clients into simple content that helps local pet owners feel more comfortable choosing you.

One quick idea: a couple short story about what first-time clients should prepare before a pet sitting visit could answer a real question and quietly show how thoughtful your process is. Don´t forget to save it as a highlight, maybe named as "Prepare for the first visit" this way you create a "collection" on your Instagram top.

I work with small businesses on practical marketing systems that make their expertise easier for local customers to see and understand.

Would you be interested in hearing a few content ideas specific to Happy Paws?

Roger Pereira  
Front Porch Creative  
LinkedIn: linkedin.com/in/rogerio-pereira  
frontporchcreative.io

#### Business automation

**Subject:** Fewer missed follow-ups, less manual work

Hi Rachel,

I'm Roger from Front Porch Creative. I came across Little Steps Childcare while looking at local childcare businesses around Wesley Chapel.

I was curious about how many parent inquiries, tours, follow-ups, and scheduling details your team may be handling during a normal week.

There may be an opportunity to make some of those repeated steps easier, so interested families do not get lost in the day-to-day and your team has less manual follow-up to track.

One simple idea: a lightweight inquiry follow-up flow could send the right next step after a parent asks about availability, while still keeping the conversation personal.

Most of my work involves helping small businesses simplify lead follow-up, customer communication, and repetitive admin tasks.

Would it be helpful if I shared what that could look like in a simple setup?

Roger Pereira  
Front Porch Creative  
LinkedIn: linkedin.com/in/rogerio-pereira  
frontporchcreative.io

#### Custom software development

**Subject:** When the usual tools keep getting in the way

Hi Chris,

I'm Roger from Front Porch Creative. I found Reliable Home Repair while looking at home service companies near Sarasota.

One thing I was curious about is how your team manages quoting, scheduling, job notes, and customer follow-up when several jobs are moving at once.

Sometimes the usual tools are enough. But if the same process keeps creating extra work or missed details, there may be an opportunity to build something small around the way your team already works.

One small idea: if job notes are being repeated across texts, spreadsheets, and invoices, even a simple shared job tracker could reduce duplicate entry before anything custom is considered.

My background is in software and automation, but I usually start by looking for the simplest fix before suggesting anything custom.

Would you be open to sharing where the current process feels most repetitive?

Roger Pereira  
Front Porch Creative  
LinkedIn: linkedin.com/in/rogerio-pereira  
frontporchcreative.io

## Prompt assets

Wave 4 agent prompts are versioned in:

- `docs/prompts/prospecting-agent.md`
- `docs/prompts/qualification-agent.md`
- `docs/prompts/recommendation-agent.md`

The qualification service catalog is the markdown files in `docs/services/` (read in full; do not parse).

Cold outreach email examples and generated `contact_example` output must follow:

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
