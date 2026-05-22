<?php

namespace App\Services\Staff;

use App\Enums\StaffLookupField;
use App\Models\StaffLookupOption;
use App\Models\StaffStatus;

class StaffOptionProvisioningService
{
    /**
     * @param  array{field: StaffLookupField|string, name: string, is_active?: bool}  $validated
     * @return array{option: StaffLookupOption, restored: bool}
     */
    public function storeLookupOption(array $validated): array
    {
        $field = $validated['field'] instanceof StaffLookupField
            ? $validated['field']->value
            : (string) $validated['field'];

        $fieldEnum = StaffLookupField::from($field);
        $trashed   = StaffLookupOption::findTrashedByNameInsensitive($fieldEnum, $validated['name']);

        if ($trashed) {
            $trashed->restore();
            $trashed->update([
                'name'      => trim($validated['name']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return ['option' => $trashed->fresh(), 'restored' => true];
        }

        return [
            'option'    => StaffLookupOption::create($validated),
            'restored'  => false,
        ];
    }

    /**
     * @param  array{name: string, color?: string, is_active?: bool}  $validated
     * @return array{status: StaffStatus, restored: bool}
     */
    public function storeStatus(array $validated): array
    {
        $trashed = StaffStatus::findTrashedByNameInsensitive($validated['name']);

        if ($trashed) {
            $trashed->restore();
            $trashed->update([
                'name'      => trim($validated['name']),
                'color'     => $validated['color'] ?? $trashed->color,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return ['status' => $trashed->fresh(), 'restored' => true];
        }

        return [
            'status'   => StaffStatus::create($validated),
            'restored' => false,
        ];
    }
}
