# Position Details Click Fix

Fixed the public Careers job-position click behavior.

## Root cause
The bundled Bootstrap version is 5.0.0-beta1, which does not provide `Modal.getOrCreateInstance()`. The job-details JavaScript called that newer API, causing script initialization to stop before the click handlers were attached.

## Fix
- Replaced `bootstrap.Modal.getOrCreateInstance(...)` with a Bootstrap 5.0-beta-compatible `getInstance(...) || new bootstrap.Modal(...)` pattern.
- Changed the job-details click handling to delegated click handling so the whole job row remains reliably clickable.
- Kept the existing job-details modal, qualifications, poster, metadata, and Apply Now flow unchanged.
