# Form Template Drag + Display Order Fix

## Updated
- Added drag-and-drop reordering for Form Template cards using a dedicated grip handle.
- Added a backend reorder endpoint for both Admin and HR routes.
- Display order values are normalized to unique 1-based positions.
- Editing a template to an occupied display order swaps the two templates instead of creating duplicate orders.
- Creating a template at an occupied display order swaps the existing template to the new template's end position.
- Dragging template cards immediately saves their new display order.
- Template cards now show their current display order.
- Existing section/field drag-and-drop remains intact, with grip handles emphasized for safer dragging.

## Database
No new migration is required.
