<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return response()->json(Announcement::with('creator')->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            'type'       => 'required|in:info,warning,success,error',
            'channels'   => 'nullable|array',
            'recipients' => 'nullable|array',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['sent_at'] = now();

        $announcement = Announcement::create($validated);
        return response()->json($announcement->load('creator'), 201);
    }

    public function show(string $id)
    {
        return response()->json(Announcement::with('creator')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $validated = $request->validate([
            'title'   => 'string|max:255',
            'message' => 'string',
            'type'    => 'in:info,warning,success,error',
        ]);
        $announcement->update($validated);
        return response()->json($announcement);
    }

    public function destroy(string $id)
    {
        Announcement::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * Mark announcement as read for current user.
     */
    public function markRead(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $readBy = $announcement->read_by ?? [];
        $userId = (string) $request->user()->id;
        if (!in_array($userId, $readBy)) {
            $readBy[] = $userId;
            $announcement->update(['read_by' => $readBy]);
        }
        return response()->json(['message' => 'Marked as read']);
    }
}
