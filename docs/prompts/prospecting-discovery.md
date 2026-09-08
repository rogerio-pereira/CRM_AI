# Prospecting Discovery Prompt

**Version:** 1.0  
**Status:** Approved for Wave 4 implementation  
**Owner:** Product owner  
**Related:** FDR-010, ADR-015, `docs/prompts/prospecting-agent.md`

## Purpose

Ask the Prospecting Discovery Agent for one contactable lead in a single job run.

## User Prompt

Discover 1 lead candidate with a public email.

Rank website work first even for a simple institutional site. Treat other services as cross-sell. Do not use custom software as the primary reason to add a lead.

Never invent an email; it must appear on a public page you fetched.

Return structured JSON only.
