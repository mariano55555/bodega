<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function upload(Request $request): Response
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'document_type' => 'required|string|in:invoice,receipt,ccf,delivery_note,photo,contract,certificate,report,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document_number' => 'nullable|string|max:100',
            'document_date' => 'nullable|date',
            'document_amount' => 'nullable|numeric|min:0',
            'issuer' => 'nullable|string|max:255',
            'recipient' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'requires_approval' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $user = auth()->user();
        $file = $request->file('file');

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            .'-'.time().'.'.$extension;

        // Store file
        $path = $file->storeAs(
            'documents/'.$validated['documentable_type'].'/'.$validated['documentable_id'],
            $filename,
            'local'
        );

        // Create document record
        $document = Document::create([
            'company_id' => $user->company_id,
            'uploaded_by' => $user->id,
            'created_by' => $user->id,
            'documentable_type' => $validated['documentable_type'],
            'documentable_id' => $validated['documentable_id'],
            'document_type' => $validated['document_type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $extension,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'local',
            'document_date' => $validated['document_date'] ?? null,
            'document_amount' => $validated['document_amount'] ?? null,
            'issuer' => $validated['issuer'] ?? null,
            'recipient' => $validated['recipient'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'status' => 'active',
            'is_public' => $validated['is_public'] ?? false,
            'requires_approval' => $validated['requires_approval'] ?? false,
        ]);

        return response()->noContent();
    }

    public function download(Document $document): StreamedResponse
    {
        $user = auth()->user();

        // Check if user can download
        if (! $document->is_public && $document->company_id !== $user->company_id) {
            abort(403, 'No tienes permiso para descargar este documento.');
        }

        if (! $document->canBeDownloaded()) {
            abort(404, 'El archivo no está disponible.');
        }

        return Storage::disk($document->disk)->download(
            $document->file_path,
            $document->file_name
        );
    }

    public function view(Document $document): StreamedResponse
    {
        $user = auth()->user();

        // Check if user can view
        if (! $document->is_public && $document->company_id !== $user->company_id) {
            abort(403, 'No tienes permiso para ver este documento.');
        }

        if (! Storage::disk($document->disk)->exists($document->file_path)) {
            abort(404, 'El archivo no está disponible.');
        }

        return Storage::disk($document->disk)->response(
            $document->file_path,
            $document->file_name
        );
    }

    public function destroy(Document $document): Response
    {
        $user = auth()->user();

        // Check if user can delete
        if (! $document->canBeDeleted($user)) {
            abort(403, 'No tienes permiso para eliminar este documento.');
        }

        $document->update(['deleted_by' => $user->id]);
        $document->delete();

        return response()->noContent();
    }

    public function approve(Request $request, Document $document): Response
    {
        $user = auth()->user();

        // Check if user can approve (must be manager or super-admin)
        if (! $user->hasRole(['super-admin', 'manager'])) {
            abort(403, 'No tienes permiso para aprobar documentos.');
        }

        if ($document->company_id !== $user->company_id && ! $user->isSuperAdmin()) {
            abort(403, 'No tienes permiso para aprobar este documento.');
        }

        if (! $document->requires_approval) {
            abort(400, 'Este documento no requiere aprobación.');
        }

        if ($document->isApproved()) {
            abort(400, 'Este documento ya ha sido aprobado.');
        }

        $document->approve($user);

        return response()->noContent();
    }

    public function createVersion(Request $request, Document $document): Response
    {
        $validated = $request->validate([
            'file' => 'required|file|max:51200',
        ]);

        $user = auth()->user();

        // Check if user can create version
        if ($document->company_id !== $user->company_id && ! $user->isSuperAdmin()) {
            abort(403, 'No tienes permiso para crear versiones de este documento.');
        }

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            .'-v'.($document->version + 1).'-'.time().'.'.$extension;

        $path = $file->storeAs(
            'documents/'.$document->documentable_type.'/'.$document->documentable_id,
            $filename,
            'local'
        );

        // Create new version
        $newVersion = Document::create([
            'company_id' => $document->company_id,
            'uploaded_by' => $user->id,
            'created_by' => $user->id,
            'documentable_type' => $document->documentable_type,
            'documentable_id' => $document->documentable_id,
            'document_type' => $document->document_type,
            'title' => $document->title,
            'description' => $document->description,
            'document_number' => $document->document_number,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $extension,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'local',
            'document_date' => $document->document_date,
            'document_amount' => $document->document_amount,
            'issuer' => $document->issuer,
            'recipient' => $document->recipient,
            'metadata' => $document->metadata,
            'status' => 'active',
            'version' => $document->version + 1,
            'previous_version_id' => $document->id,
            'is_public' => $document->is_public,
            'requires_approval' => $document->requires_approval,
        ]);

        // Archive old version
        $document->update(['status' => 'archived']);

        return response()->noContent();
    }
}
