<?php

namespace App\Http\Controllers;

use App\Models\LostItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LostItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return LostItem::orderByDesc('created_at')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'location'    => 'required|string|max:255',
            'date_lost'   => 'required|date',
            'contact'     => 'required|string|max:255',
            'status'      => 'nullable|in:lost,found',

            // accept all possible image keys from frontend
            'photo'      => 'nullable|image|max:10240',
            'image'      => 'nullable|image|max:10240',
            'item_photo' => 'nullable|image|max:10240',
        ]);

        $validated['status'] = $validated['status'] ?? 'lost';

        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }

        // pick whichever file key exists
        $file = $request->file('image')
            ?? $request->file('photo')
            ?? $request->file('item_photo');

        if ($file) {
            $path = $file->store('lost_items', 'public');
            $validated['image_path'] = $path;
        }

        // IMPORTANT: don't try to insert these into DB columns
        unset($validated['photo'], $validated['image'], $validated['item_photo']);

        $item = LostItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LostItem $lostItem)
    {
        return response()->json($lostItem);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LostItem $lostItem)
    {
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location'    => 'sometimes|string|max:255',
            'date_lost'   => 'sometimes|date',
            'contact'     => 'sometimes|string|max:255',
            'status'      => 'sometimes|in:lost,found',
            'found_image' => 'sometimes|image|max:2048', // proof image
        ]);

        if ($request->hasFile('found_image')) {
            if ($lostItem->found_image_path) {
                Storage::disk('public')->delete($lostItem->found_image_path);
            }

            $path = $request->file('found_image')->store('found_items', 'public');
            $validated['found_image_path'] = $path;
        }

        unset($validated['found_image']);

        $lostItem->update($validated);

        return response()->json($lostItem);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LostItem $lostItem)
    {
        if ($lostItem->image_path) {
            Storage::disk('public')->delete($lostItem->image_path);
        }
        if ($lostItem->found_image_path) {
            Storage::disk('public')->delete($lostItem->found_image_path);
        }

        $lostItem->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function markAsFound(Request $request, LostItem $lostItem)
    {
        $request->validate([
            'found_image' => 'required|image|max:2048',
        ]);

        // store image in public disk
        $path = $request->file('found_image')->store('found_items', 'public');

        $lostItem->status = 'found';
        $lostItem->found_image_path = $path;
        $lostItem->save();

        return response()->json([
            'message' => 'Item marked as found.',
            'data'    => $lostItem,
        ]);
    }
}
