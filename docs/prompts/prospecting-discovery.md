# Prospecting Discovery Prompt

**Version:** 1.2  
**Status:** Approved for Wave 4 implementation  
**Owner:** Product owner  
**Related:** FDR-010, ADR-015, `docs/prompts/prospecting-agent.md`

## Purpose

Ask the Prospecting Discovery Agent for one contactable lead in a single job run.

## User Prompt

Discover 1 lead candidate with a public email.

Rank website work first even for a simple institutional site. Prefer businesses whose site is missing, outdated, unclear, or merely “fine.” Treat other services as cross-sell. Do not add a lead whose website already looks strong when the only remaining work is ads, content, email, or custom software.

Never invent an email; it must appear on a public page you fetched.

Return structured JSON only.
