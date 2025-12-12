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
        // You can add pagination later if you want
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
            'image'       => 'nullable|image|max:2048', // main item photo
        ]);

        $validated['status'] = $validated['status'] ?? 'lost';

        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('lost_items', 'public');
            $validated['image_path'] = $path;
        }

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
     *
     * Used for:
     * - updating status (lost → found)
     * - uploading found proof photo
     * - or editing details (optional)
     */
    public function update(Request $request, LostItem $lostItem)
    {
        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'sometimes|string',
            'location'         => 'sometimes|string|max:255',
            'date_lost'        => 'sometimes|date',
            'contact'          => 'sometimes|string|max:255',
            'status'           => 'sometimes|in:lost,found',
            'found_image'      => 'sometimes|image|max:2048', // proof image
        ]);

        // Handle found proof image upload
        if ($request->hasFile('found_image')) {
            // Optionally delete old proof image
            if ($lostItem->found_image_path) {
                Storage::disk('public')->delete($lostItem->found_image_path);
            }

            $path = $request->file('found_image')->store('found_items', 'public');
            $validated['found_image_path'] = $path;
        }

        $lostItem->update($validated);

        return response()->json($lostItem);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LostItem $lostItem)
    {
        // Delete images from storage too (optional but nice)
        if ($lostItem->image_path) {
            Storage::disk('public')->delete($lostItem->image_path);
        }
        if ($lostItem->found_image_path) {
            Storage::disk('public')->delete($lostItem->found_image_path);
        }

        $lostItem->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function markAsFound(Request $request, \App\Models\LostItem $lostItem)
    {
        // validate image
        $validated = $request->validate([
            'found_image' => 'required|image|max:2048', // 2 MB
        ]);

        // store image in public disk (storage/app/public/lost_items)
        $path = $request->file('found_image')->store('lost_items', 'public');

        // update item
        $lostItem->status = 'found';
        $lostItem->found_image_path = $path;
        $lostItem->save();

        // return updated item (you can wrap in a Resource if you use one)
        return response()->json([
            'message' => 'Item marked as found.',
            'data'    => $lostItem,
        ]);
    }

}
