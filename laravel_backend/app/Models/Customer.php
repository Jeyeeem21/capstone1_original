<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasArchiving;

class Customer extends Model
{
    use SoftDeletes, HasArchiving;

    protected $fillable = [
        'name',
        'contact',
        'phone',
        'email',
        'address',
        'address_landmark',
        'status',
        'is_archived',
        'archived_at',
        'orders',
    ];

    protected $casts = [
        'orders' => 'integer',
    ];

    public function getDisplayNameAttribute(): string
    {
        $businessName = trim((string) ($this->attributes['name'] ?? ''));
        if ($businessName !== '') {
            return $businessName;
        }

        $contactName = trim((string) ($this->attributes['contact'] ?? ''));
        if ($contactName !== '') {
            return $contactName;
        }

        $email = trim((string) ($this->attributes['email'] ?? ''));
        if ($email !== '') {
            return $email;
        }

        return 'Customer #' . ($this->id ?? '');
    }

    /**
     * Get all sales/orders for this customer.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the user account linked to this customer (if any).
     */
    public function user()
    {
        return $this->hasOne(User::class, 'email', 'email');
    }
}
