<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prospecting Agent Prompt
    |--------------------------------------------------------------------------
    |
    | Path to the stakeholder-approved system prompt (ADR-015 / FDR-010).
    | Relative paths are resolved from the application base path.
    |
    */

    'prompt_path' => env(
        'PROSPECTING_PROMPT_PATH',
        'docs/prompts/prospecting-agent.md',
    ),

    'default_limit' => (int) env('PROSPECTING_DEFAULT_LIMIT', 20),

];
