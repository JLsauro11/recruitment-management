<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Evidence Interpreter
    |--------------------------------------------------------------------------
    |
    | AI is used only to semantically normalize applicant evidence. The
    | deterministic AssessmentInsightService remains responsible for every
    | qualification status, weight, cap, score, fit label, and ranking rule.
    |
    | If Gemini is unavailable, disabled, out of quota, or below the configured
    | confidence threshold, the system automatically uses the original rule-based
    | evidence interpreter.
    |
    */
    'enabled' => env('RECRUITMENT_AI_ENABLED', true),
    'provider' => env('AI_PROVIDER', 'gemini'),
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
    'base_url' => rtrim(env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 30),
    'cache_days' => (int) env('RECRUITMENT_AI_CACHE_DAYS', 30),
    'minimum_confidence' => (float) env('RECRUITMENT_AI_MIN_CONFIDENCE', 0.80),
];
