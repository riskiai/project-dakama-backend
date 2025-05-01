<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Divisi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat roles
        $roles = ['Owner', 'Admin',  'Supervisor', 'Karyawan',];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'role_name' => $role
            ]);
        }

        // Buat divisi CAT jika belum ada
        $divisiCat = Divisi::firstOrCreate([
            'name' => 'CAT',
            'kode_divisi' => 'CAT-001',
        ]);

        // Buat User Owner
        User::factory()->create([
            'email' => 'owner@mailinator.com',
            'role_id' => Role::where('role_name', 'Owner')->first()->id,
            'name' => 'Owner'
        ]);

        User::factory()->create([
            'email' => 'admin@mailinator.com',
            'role_id' => Role::where('role_name', 'Admin')->first()->id,
            'name' => 'Admin'
        ]);

        User::factory()->create([
            'email' => 'supervisor@mailinator.com',
            'role_id' => Role::where('role_name', 'Supervisor')->first()->id,
            'name' => 'Supervisor'
        ]);

        User::factory()->create([
            'email' => 'karyawan@mailinator.com',
            'role_id' => Role::where('role_name', 'Karyawan')->first()->id,
            'divisi_id' => $divisiCat->id,
            'name' => 'Karyawan'
        ]);
    }
}
