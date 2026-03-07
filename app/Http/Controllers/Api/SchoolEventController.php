<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolEvent;
use Illuminate\Http\Request;

class SchoolEventController extends Controller
{
    public function index()
    {
        return response()->json(SchoolEvent::orderBy('date')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'date'        => 'required|date',
            'location'    => 'required|string|max:255',
            'organizer'   => 'required|string|max:255',
            'media'       => 'nullable|array',
        ]);
        $event = SchoolEvent::create($validated);
        return response()->json($event, 201);
    }

    public function show(string $id)
    {
        return response()->json(SchoolEvent::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $event = SchoolEvent::findOrFail($id);
        $validated = $request->validate([
            'title'       => 'string|max:255',
            'description' => 'string',
            'date'        => 'date',
            'location'    => 'string|max:255',
            'organizer'   => 'string|max:255',
            'media'       => 'nullable|array',
        ]);
        $event->update($validated);
        return response()->json($event);
    }

    public function destroy(string $id)
    {
        SchoolEvent::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
