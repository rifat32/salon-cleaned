<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlotHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'expert_id',
        'held_slots',
        'held_until',
    ];

    protected $casts = [
        'held_slots' => 'array', // Cast held_slots to an array
        'held_until' => 'datetime', // Automatically cast this to a Carbon instance
    ];

    // Define the relationship with the Customer
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Define the relationship with the Expert
    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
}
