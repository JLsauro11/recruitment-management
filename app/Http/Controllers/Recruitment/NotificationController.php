<?php

namespace App\Http\Controllers\Recruitment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\Application;
use App\Models\Interview;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $expanded = $request->boolean('expanded');
        $query = $user->notifications()->latest();
        $totalCount = (clone $query)->count();

        $notifications = ($expanded ? $query->get() : $query->limit(5)->get())
            ->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Recruitment Update',
                'message' => $notification->data['message'] ?? '',
                'icon' => $notification->data['icon'] ?? 'bi-bell-fill',
                'color' => $notification->data['color'] ?? 'primary',
                'is_read' => !is_null($notification->read_at),
                'created_at' => $notification->created_at?->diffForHumans(),
                'open_url' => route($this->prefix($request) . '.notifications.open', $notification->id),
            ]);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $totalCount,
            'expanded' => $expanded,
            'notifications' => $notifications,
        ]);
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        $destination = (string) ($item->data['destination'] ?? 'dashboard');
        $parameters = (array) ($item->data['parameters'] ?? []);
        // Backward compatibility for older interview notifications that stored an
        // application id. Resolve the exact latest interview so the edit modal
        // loads the correct saved interview record instead of a blank schedule form.
        if ($destination === 'interviews.index' && empty($parameters['interview']) && !empty($parameters['application'])) {
            $latestInterviewId = Interview::query()
                ->where('application_id', $parameters['application'])
                ->latest('created_at')
                ->latest('id')
                ->value('id');

            if ($latestInterviewId) {
                $parameters = ['interview' => $latestInterviewId];
            }
        }

        $routeName = $this->prefix($request) . '.' . $destination;

        if (!app('router')->has($routeName)) {
            $routeName = $this->prefix($request) . '.dashboard';
            $parameters = [];
        }

        if (!$this->targetStillExists($destination, $parameters)) {
            // The linked record was already deleted. Remove the stale notification
            // so it cannot be opened repeatedly, then return to the module safely.
            $item->delete();

            $safeRoute = app('router')->has($routeName)
                ? $routeName
                : $this->prefix($request) . '.dashboard';

            return redirect()
                ->route($safeRoute)
                ->with('toast_warning', 'This record has already been deleted. The old notification was removed.');
        }

        return redirect()->route($routeName, $parameters);
    }


    private function targetStillExists(string $destination, array $parameters): bool
    {
        if ($destination === 'interviews.index' && !empty($parameters['interview'])) {
            return Interview::query()->whereKey($parameters['interview'])->exists();
        }

        if ($destination === 'interviews.index' && !empty($parameters['application'])) {
            return Application::query()->whereKey($parameters['application'])->exists();
        }

        if (in_array($destination, ['applicants.index', 'hiring-status.index'], true)
            && !empty($parameters['application'])) {
            return Application::query()->whereKey($parameters['application'])->exists();
        }

        return true;
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'All notifications cleared.']);
    }

    private function prefix(Request $request): string
    {
        return $request->routeIs('hr.*') ? 'hr' : 'admin';
    }
}
