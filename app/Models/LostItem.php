<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LostItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'date_lost',
        'contact',
        'status',
        'image_path',       // original lost-item photo
        'found_image_path', // proof photo when found
        'image_url',        // Cloudinary URL
        'found_image_url',   // Cloudinary URL for found proof
    ];
}
