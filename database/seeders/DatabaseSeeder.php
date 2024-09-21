<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\City;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */

    public function run(): void
    {
        $admin = Role::create(['name' => 'Admin']);
        $subscriber = Role::create(['name' => 'Subscriber']);

        for($i = 0; $i < 10; $i++) {
            City::create([
                'name' => fake()->city(),
            ]);
        }

        $this->call(SpecialtiesTableSeeder::class);

        User::factory(10)->create();
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $users = User::all();

        foreach ($users as $user) {
            if( $user->id == 1 ) $user->assignRole('Admin');
            else {
                $user->assignRole('Subscriber');
                $user->specialty_id = Specialty::inRandomOrder()->first()->id;
                $user->save();
            }
        }
    }
}
