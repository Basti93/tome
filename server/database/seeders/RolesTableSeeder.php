<?php

namespace Database\Seeders;

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
      app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
  //roles are granted through the AuthServiceProvider

    $role = Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'api']);
    $role->givePermissionTo(
      'read-training',
      'create-training',
      'checkin-training',
      'read-training',
      'update-training',
      'delete-training',
      'read-user',
      'update-user',
      'create-user',
      'read-training-series',
      'update-training-series',
      'create-training-series',
      'delete-training-series',
      'create-group',
      'delete-group',
      'update-group'
    );

    $role = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);
    $role->givePermissionTo('read-training', 'checkin-training');

  }
}
