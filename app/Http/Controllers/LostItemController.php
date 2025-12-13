<?php

namespace App\Http\Controllers;

use App\Models\LostItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class LostItemController extends Controller
{
    private function normalizePath(?string $path): ?string
    {
        if (!$path) return null;
        return str_replace('\\', '/', $path);
    }

    public function index()
    {
        $items = LostItem::orderByDesc('created_at')->get();

        return response()->json($items);
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

        // ===== Cloudinary upload (LOST ITEM PHOTO) =====
        if ($file) {
            $upload = Cloudinary::upload(
                $file->getRealPath(),
                ['folder' => 'lost_and_found/lost_items']
            );

            $validated['image_url'] = $upload->getSecurePath();
        }

        $item = LostItem::create($validated);

        return response()->json($item, 201);
    }

    public function show(LostItem $lostItem)
    {
        return response()->json($lostItem);
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

        // ===== Cloudinary upload (FOUND PROOF PHOTO) =====
        if ($request->hasFile('found_image')) {
            $upload = Cloudinary::upload(
                $request->file('found_image')->getRealPath(),
                ['folder' => 'lost_and_found/found_proofs']
            );

            $validated['found_image_url'] = $upload->getSecurePath();
        }

        $lostItem->update($validated);

        return response()->json($lostItem);
    }

    public function markAsFound(Request $request, LostItem $lostItem)
    {
        $request->validate([
            'found_image' => 'required|image|max:10240',
        ]);

        $upload = Cloudinary::upload(
            $request->file('found_image')->getRealPath(),
            ['folder' => 'lost_and_found/found_proofs']
        );

        $lostItem->status = 'found';
        $lostItem->found_image_url = $upload->getSecurePath();
        $lostItem->save();

        return response()->json([
            'message' => 'Item marked as found.',
            'data' => $lostItem,
        ]);
    }

    public function destroy(LostItem $lostItem)
    {
        $lostItem->delete();
        return response()->json(['message' => 'Deleted']);
    }
}