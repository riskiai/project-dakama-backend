<?php

namespace App\Http\Resources\Users;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UsersCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $user) {
            $data[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->role_name,
                'nomor_karyawan' => $user->nomor_karyawan,
                'bank_name' => $user->bank_name,
                'account_number' => $user->account_number,
                'status_users' => $this->statusUsersKubika($user),
                'divisi' => [
                    'name' => $user->divisi->name ?? null,
                    'kode_divisi' => $user->divisi->kode_divisi ?? null,
                ],
                'daily_salary' => $user->salary ? $user->salary->daily_salary : 0,
                'hourly_salary' => $user->salary ? $user->salary->hourly_salary : 0,
                'hourly_overtime_salary' => $user->salary ? $user->salary->hourly_overtime_salary : 0,
                'transport' => $user->salary ? $user->salary->transport : 0,
                'makan' => $user->salary ? $user->salary->makan : 0,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        }

        return $data;
    }

    protected function statusUsersKubika($user): string
    {
        if ((int) $user->status === User::AKTIF) {
            return 'Aktif';
        } elseif ((int) $user->status === User::TIDAK_AKTIF) {
            return 'Tidak Aktif';
        }

        return 'Tidak Diketahui';
    }
}
