<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->delete();

        $user = new User();
        $user->forceFill([
            'name' => 'Andrea',
            'email' => 'al222411215@gmail.com',
            'email_verified_at' => Carbon::now(),
            'password' => bcrypt('password'),
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ])->save();
    }
}
