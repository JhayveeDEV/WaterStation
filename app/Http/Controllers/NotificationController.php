<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->appNotifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /** Marks one as read, used by the live bell dropdown via a small fetch call. */
    public function markRead(Request $request, int $id)
    {
        $request->user()->appNotifications()->whereKey($id)->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->appNotifications()->where('is_read', false)->update(['is_read' => true]);

        return back();
    }
}
