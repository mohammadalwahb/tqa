<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_statuses';

    protected $fillable = [
        'name',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWhereNameInsensitive($query, string $name)
    {
        return $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
    }

    public static function findActiveByNameInsensitive(string $name, ?int $exceptId = null): ?self
    {
        return static::query()
            ->whereNameInsensitive($name)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->first();
    }

    public static function findTrashedByNameInsensitive(string $name): ?self
    {
        return static::onlyTrashed()
            ->whereNameInsensitive($name)
            ->first();
    }
}
