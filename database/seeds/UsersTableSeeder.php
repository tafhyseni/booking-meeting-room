<?php

use Illuminate\Database\Seeder;
use App\Role;
use App\User;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get role admin
        $role_admin = Role::where('name', 'admin')->first();
        $role_user  = Role::where('name', 'customer')->first();

        $admin = new User();
        $admin->name = 'John Doe';
        $admin->email = 'admin@example.com';
        $admin->email_verified_at = Carbon::now();
        $admin->password = bcrypt('temp1234');
        $admin->save();
        $admin->roles()->attach($role_admin);

        $user = new User();
        $user->name = 'Levim Rotem';
        $user->email = 'customer@example.com';
        $user->email_verified_at = Carbon::now();
        $user->password = bcrypt('temp1234');
        $user->save();
        $user->roles()->attach($role_user);
    }
}
