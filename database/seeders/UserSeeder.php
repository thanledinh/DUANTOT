<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 200; $i++) {
            User::create([
                'username' => $faker->userName,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password123'), // Mật khẩu mặc định
                'user_type' => 'user', // Mặc định là 'user'
            ]);
        }
    }
}
