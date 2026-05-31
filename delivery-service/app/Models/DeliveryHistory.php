<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'old_status',
        'new_status',
        'changed_by',
        'comment',
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
