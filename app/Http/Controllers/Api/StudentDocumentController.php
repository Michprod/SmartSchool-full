<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentDocumentResource;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    public function index(string $studentId)
    {
        Student::findOrFail($studentId);

        $documents = StudentDocument::where('student_id', $studentId)
            ->with('uploader')
            ->orderByDesc('created_at')
            ->get();

        return StudentDocumentResource::collection($documents);
    }

    public function store(Request $request, string $studentId)
    {
        Student::findOrFail($studentId);

        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'file' => 'required|file|max:10240',
        ]);

        $file = $validated['file'];
        $path = $file->store("students/{$studentId}/documents", 'public');

        $document = StudentDocument::create([
            'student_id' => $studentId,
            'type' => $validated['type'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()?->id,
        ]);

        return (new StudentDocumentResource($document->load('uploader')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(string $studentId, string $documentId)
    {
        Student::findOrFail($studentId);
        $document = StudentDocument::where('student_id', $studentId)->findOrFail($documentId);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(null, 204);
    }

    public function download(string $studentId, string $documentId)
    {
        Student::findOrFail($studentId);
        $document = StudentDocument::where('student_id', $studentId)->findOrFail($documentId);

        abort_unless(Storage::disk('public')->exists($document->file_path), 404, 'Document introuvable.');

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }
}
