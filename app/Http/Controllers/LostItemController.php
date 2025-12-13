<?php

namespace App\Http\Controllers;

use App\Models\LostItem;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

class LostItemController extends Controller
{
    private function normalizePath(?string $path): ?string
    {
        if (!$path) return null;
        return str_replace('\\', '/', $path);
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

            'photo'      => 'nullable|image|max:10240',
            'image'      => 'nullable|image|max:10240',
            'item_photo' => 'nullable|image|max:10240',
        ]);

        $validated['status'] = $validated['status'] ?? 'lost';

        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }

        $file =
            $request->file('image') ??
            $request->file('photo') ??
            $request->file('item_photo');

        // Default
        $validated['image_url']  = null;
        $validated['image_path'] = null;

        if ($file) {
            try {
                if (!$file->isValid()) {
                    Log::error('Upload not valid', [
                        'error' => $file->getError(),
                        'name'  => $file->getClientOriginalName(),
                        'size'  => $file->getSize(),
                    ]);

                    return response()->json([
                        'message' => 'Upload failed before Cloudinary (file invalid). Try a smaller image.',
                    ], 422);
                }

                $uploaded = Cloudinary::upload($file->getPathname(), [
                    'folder' => 'lost_items',
                    'resource_type' => 'auto',
                ]);

                $validated['image_url']  = $uploaded->getSecurePath();
                $validated['image_path'] = $this->normalizePath($uploaded->getPublicId()); // optional
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload failed (store)', [
                    'message' => $e->getMessage(),
                    'class'   => get_class($e),
                    'trace'   => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Cloudinary upload failed. Check Railway logs for details.',
                    'error'   => $e->getMessage(),
                ], 500);
            }
        }

        $item = LostItem::create($validated);

        // normalize any paths for consistency
        $item->image_path = $this->normalizePath($item->image_path);
        $item->found_image_path = $this->normalizePath($item->found_image_path);

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
            $file = $request->file('found_image');

            try {
                if (!$file->isValid()) {
                    Log::error('Found upload not valid (update)', [
                        'error' => $file->getError(),
                        'name'  => $file->getClientOriginalName(),
                        'size'  => $file->getSize(),
                    ]);

                    return response()->json([
                        'message' => 'Proof upload failed (file invalid). Try a smaller image.',
                    ], 422);
                }

                $uploaded = Cloudinary::upload($file->getPathname(), [
                    'folder' => 'found_items',
                ]);

                $validated['found_image_url']  = $uploaded->getSecurePath();
                $validated['found_image_path'] = $this->normalizePath($uploaded->getPublicId());
                $validated['status'] = 'found';
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload failed (update)', [
                    'message' => $e->getMessage(),
                    'class'   => get_class($e),
                    'trace'   => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Cloudinary proof upload failed. Check logs.',
                    'error'   => $e->getMessage(),
                ], 500);
            }
        }

        $lostItem->update($validated);

        $lostItem->image_path = $this->normalizePath($lostItem->image_path);
        $lostItem->found_image_path = $this->normalizePath($lostItem->found_image_path);

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

        $file = $request->file('found_image');

        try {
            if (!$file->isValid()) {
                Log::error('Found upload not valid (markAsFound)', [
                    'error' => $file->getError(),
                    'name'  => $file->getClientOriginalName(),
                    'size'  => $file->getSize(),
                ]);

                return response()->json([
                    'message' => 'Proof upload failed (file invalid). Try a smaller image.',
                ], 422);
            }

            $uploaded = Cloudinary::upload($file->getPathname(), [
                'folder' => 'found_items',
                'resource_type' => 'auto',
            ]);

            $lostItem->status = 'found';
            $lostItem->found_image_url = $uploaded->getSecurePath();
            $lostItem->found_image_path = $this->normalizePath($uploaded->getPublicId());
            $lostItem->save();

            $lostItem->image_path = $this->normalizePath($lostItem->image_path);
            $lostItem->found_image_path = $this->normalizePath($lostItem->found_image_path);

            return response()->json([
                'message' => 'Item marked as found.',
                'data' => $lostItem,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cloudinary found_image upload failed (markAsFound)', [
                'message' => $e->getMessage(),
                'class'   => get_class($e),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Cloudinary proof upload failed. Check logs.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}