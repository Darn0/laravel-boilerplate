<?php

namespace Tests\Feature\Backend\Role;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class DeleteRoleTest.
 */
class DeleteRoleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_role_can_be_deleted()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $role = factory(Role::class)->create();

        $this->loginAsAdmin();

        $this->assertDatabaseHas(config('permission.table_names.roles'), ['id' => $role->id]);

        $this->delete("/admin/auth/role/{$role->id}");

        $this->assertDatabaseMissing(config('permission.table_names.roles'), ['id' => $role->id]);
    }

    /** @test */
    public function the_admin_role_can_not_be_deleted()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->loginAsAdmin();

        $role = Role::whereName(config('boilerplate.access.role.admin'))->first();

        $response = $this->delete('/admin/auth/role/'.$role->id);

        $response->assertSessionHas(['flash_danger' => __('You can not delete the Administrator role.')]);

        $this->assertDatabaseHas(config('permission.table_names.roles'), ['id' => $role->id]);
    }

    /** @test */
    public function a_role_with_assigned_users_cant_be_deleted()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->loginAsAdmin();

        $role = factory(Role::class)->create();
        $user = factory(User::class)->create();
        $user->assignRole($role);

        $response = $this->delete('/admin/auth/role/'.$role->id);

        $response->assertSessionHas(['flash_danger' => __('You can not delete a role with associated users.')]);

        $this->assertDatabaseHas(config('permission.table_names.roles'), ['id' => $role->id]);
    }

    /** @test */
    public function only_admin_can_delete_roles()
    {
        $this->actingAs(factory(User::class)->create());

        $role = factory(Role::class)->create();

        $response = $this->delete('/admin/auth/role/'.$role->id);

        $response->assertSessionHas('flash_danger', __('You do not have access to do that.'));

        $this->assertDatabaseHas(config('permission.table_names.roles'), ['id' => $role->id]);
    }
}
