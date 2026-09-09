# Prospecting Discovery Prompt

**Version:** 1.1  
**Status:** Approved for Wave 4 implementation  
**Owner:** Product owner  
**Related:** FDR-010, ADR-015, `docs/prompts/prospecting-agent.md`

## Purpose

Ask the Prospecting Discovery Agent for one contactable lead in a single job run.

## User Prompt

Discover 1 lead candidate with a public email.

Analyze the full business against the catalog in your instructions. Do not apply a global service ranking. A functional website does not disqualify a lead. Recurring needs (lead generation, content, email, automations) are valid reasons to add a lead. Do not use custom software as the only reason unless the operational need is obvious.

Never invent an email; it must appear on a public page you fetched.

Return structured JSON only.
