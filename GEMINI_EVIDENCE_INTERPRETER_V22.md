# Gemini Evidence Interpreter V22 - Language Parity Hardening

## Goal
Prevent semantically equivalent English, Tagalog, and Taglish answers from receiving extra competency credit because Gemini chose a broader umbrella label in only one language.

## Changes
- Bumped the AI evidence interpreter cache/version from 8 to 9 so old normalized results are not reused.
- Hardened `system administration` canonicalization. The competency now requires concrete administration activity (for example managing/administering systems, servers, user accounts, permissions, Group Policy, or Active Directory), not merely the umbrella phrase.
- If Gemini returns `system administration` without concrete administration evidence, the label is removed before deterministic scoring.
- Preserved genuine system-administration evidence when a concrete administration action is present.
- Added regression checks for English/Tagalog/Taglish canonical parity, synonym deduplication, ambiguous evidence, language-only system-administration inflation, and genuine administration evidence.

## Expected effect
Equivalent support/troubleshooting answers should converge on the same canonical competency set. A language variant should no longer gain a Role-Specific Skills advantage solely because the model emitted `system administration` as an extra label.

## Validation
`php tests/manual_ai_language_invariance_v22.php` passes all V22 language-invariance checks.

Full Laravel assessment tests require `vendor/` dependencies, which were not included in the uploaded archive.
