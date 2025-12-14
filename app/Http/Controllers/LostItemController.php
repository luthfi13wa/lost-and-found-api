<?php

namespace App\Http\Controllers;

use App\Models\LostItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Cloudinary\Api\Upload\UploadApi;

class LostItemController extends Controller
{
    private function normalizePath(?string $path): ?string
    {
        if (!$path) return null;
        return str_replace('\\', '/', $path);
    }

    private function isHttpUrl(?string $v): bool
    {
        return is_string($v) && preg_match('/^https?:\/\//i', $v) === 1;
    }

    /**
     * Upload file to Cloudinary safely using official SDK
     */
    private function uploadToCloudinary(\Illuminate\Http\UploadedFile $file, string $folder): array
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('Uploaded file is not valid.');
        }

        $uploader = new UploadApi();

        $result = $uploader->upload($file->getPathname(), [
            'folder'        => $folder,
            'resource_type' => 'auto',
        ]);

        $secureUrl = $result['secure_url'] ?? null;
        $publicId  = $result['public_id'] ?? null;

        if (!$secureUrl || !$this->isHttpUrl($secureUrl)) {
            throw new \RuntimeException('Cloudinary upload did not return a secure URL.');
        }

        return [
            'secure_url' => $secureUrl,
            'public_id'  => $publicId,
        ];
    }

    public function index()
    {
        return response()->json(
            LostItem::orderByDesc('created_at')->get()
        );
    }

    public function show(LostItem $lostItem)
    {
        return response()->json($lostItem);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'location'    => 'required|string|max:255',
            'date_lost'   => 'required|date',
            'contact'     => 'required|string|max:255',
            'status'      => 'nullable|in:lost,found',

            // frontend sends ONE of these
            'photo'      => 'nullable|image|max:10240',
            'image'      => 'nullable|image|max:10240',
            'item_photo' => 'nullable|image|max:10240',
        ]);

        $validated['status'] = $validated['status'] ?? 'lost';

        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }

        $file =
            $request->file('photo') ??
            $request->file('image') ??
            $request->file('item_photo');

        $validated['image_url']  = null;
        $validated['image_path'] = null;

        if ($file) {
            try {
                $uploaded = $this->uploadToCloudinary($file, 'lost_items');

                $validated['image_url']  = $uploaded['secure_url'];
                $validated['image_path'] = $this->normalizePath($uploaded['public_id']);
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload failed (store)', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Cloudinary upload failed.',
                    'error'   => $e->getMessage(),
                ], 500);
            }
        }

        $item = LostItem::create($validated);

        return response()->json($item, 201);
    }

    public function update(Request $request, LostItem $lostItem)
    {
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location'    => 'sometimes|string|max:255',
            'date_lost'   => 'sometimes|date',
            'contact'     => 'sometimes|string|max:255',
            'status'      => 'sometimes|in:lost,found',
            'found_image' => 'sometimes|image|max:10240',
        ]);

        if ($request->hasFile('found_image')) {
            try {
                $uploaded = $this->uploadToCloudinary(
                    $request->file('found_image'),
                    'found_items'
                );

                $validated['found_image_url']  = $uploaded['secure_url'];
                $validated['found_image_path'] = $this->normalizePath($uploaded['public_id']);
                $validated['status'] = 'found';
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload failed (update)', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Cloudinary proof upload failed.',
                    'error'   => $e->getMessage(),
                ], 500);
            }
        }

        $lostItem->update($validated);

        return response()->json($lostItem);
    }

    public function destroy(LostItem $lostItem)
    {
        $lostItem->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function markAsFound(Request $request, LostItem $lostItem)
    {
        $request->validate([
            'found_image' => 'required|image|max:10240',
        ]);

        try {
            $uploaded = $this->uploadToCloudinary(
                $request->file('found_image'),
                'found_items'
            );

            $lostItem->status = 'found';
            $lostItem->found_image_url  = $uploaded['secure_url'];
            $lostItem->found_image_path = $this->normalizePath($uploaded['public_id']);
            $lostItem->save();

            return response()->json([
                'message' => 'Item marked as found.',
                'data'    => $lostItem,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cloudinary upload failed (markAsFound)', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Cloudinary proof upload failed.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
