# Real-time Notification Update

The notification center now refreshes automatically every 3 seconds while the admin/HR page is visible.

Additional behavior:
- No manual page refresh is required.
- Notifications refresh immediately when the browser tab becomes active again.
- Notifications refresh immediately when the browser window regains focus.
- Overlapping requests are prevented.
- Browser caching is disabled for notification requests.
- The bell animates when the unread count increases.
- Clicking a notification still marks it read and redirects to its configured destination.
