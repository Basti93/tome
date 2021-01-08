<?php


use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;


class PermissionsTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Permission::firstOrCreate(['name' => 'create-training', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'read-training', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-training', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-training', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'checkin-training', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'create-training-series', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'read-training-series', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-training-series', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-training-series', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'create-user', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'read-user', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-user', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-user', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'assign-role-member', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'assign-role-group', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'create-permission', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'read-permission', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-permission', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-permission', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'create-role', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'read-role', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-role', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-role', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'create-branch', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-branch', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-branch', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'create-group', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-group', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-group', 'guard_name' => 'api']);

    Permission::firstOrCreate(['name' => 'create-content', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'update-content', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete-content', 'guard_name' => 'api']);

  }
}
