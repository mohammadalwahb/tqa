<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoordinatorUserProvisioner
{
    /**
     * @param  array{name:string,email:string,college_id:int,is_active?:bool}  $validated
     * @return array{user: User, restored: bool}
     */
    public function provision(array $validated): array
    {
        $payload = array_merge($validated, [
            'password' => Hash::make(Str::random(40)),
        ]);

        $trashed = User::onlyTrashed()
            ->where('email', $validated['email'])
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->fill($payload);
            $trashed->save();

            return ['user' => $trashed->fresh(), 'restored' => true];
        }

        return ['user' => User::create($payload), 'restored' => false];
    }
}
