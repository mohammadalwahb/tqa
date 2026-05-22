<?php

namespace App\Models;

use App\Enums\StaffLookupField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffLookupOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'field',
        'name',
        'is_active',
    ];

    protected $casts = [
        'field'     => StaffLookupField::class,
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForField($query, StaffLookupField $field)
    {
        return $query->where('field', $field->value);
    }

    public function scopeWhereNameInsensitive($query, string $name)
    {
        return $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
    }

    public static function findActiveByNameInsensitive(
        StaffLookupField $field,
        string $name,
        ?int $exceptId = null,
    ): ?self {
        return static::query()
            ->forField($field)
            ->whereNameInsensitive($name)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->first();
    }

    public static function findTrashedByNameInsensitive(StaffLookupField $field, string $name): ?self
    {
        return static::onlyTrashed()
            ->forField($field)
            ->whereNameInsensitive($name)
            ->first();
    }
}
