# Desktop Chrome Notification Update

Added native browser/OS notifications to the Admin/HR notification center.

Behavior:
- Existing bell notifications still refresh every 3 seconds.
- New unread notifications that arrive after the page is running also trigger a native Chrome/Windows notification when permission is granted.
- Clicking the native notification opens the exact recruitment notification destination and marks it read through the existing open route.
- Clicking the notification bell requests browser notification permission if the site has not asked yet.
- Existing unread notifications are used as a baseline on first load, so old alerts are not replayed every time the page refreshes.
- A small localStorage history reduces duplicate desktop popups across reloads/tabs.

Note: Chrome/Windows must allow notifications for the site. On localhost, Chrome normally treats localhost as a secure context, but Windows Focus Assist / Do Not Disturb can still suppress the visible OS popup.
