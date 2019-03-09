<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
  //roles are granted through the AuthServiceProvider

    $role = Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'api']);
    $role->givePermissionTo(
      'read-training',
      'create-training',
      'checkin-training',
      'read-training',
      'update-training',
      'read-user',
      'update-user',
      'create-user'
    );

    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);
    $role->givePermissionTo('read-training', 'checkin-training');

  }
}
