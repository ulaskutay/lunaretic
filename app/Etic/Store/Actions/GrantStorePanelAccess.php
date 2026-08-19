<?php

namespace App\Etic\Store\Actions;

use Lunar\Admin\Models\Staff;
use Lunar\Admin\Support\Facades\LunarAccessControl;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantStorePanelAccess
{
    /**
     * @var list<string>
     */
    private array $excluded = [
        'settings:manage-staff',
        'settings:core',
    ];

    public function handle(Staff $staff): Staff
    {
        if ($staff->admin) {
            return $staff;
        }

        $guard = 'staff';

        Role::findOrCreate('staff', $guard);

        $permissions = collect(LunarAccessControl::getBasePermissions())
            ->reject(fn (string $permission) => in_array($permission, $this->excluded, true))
            ->map(fn (string $permission) => Permission::findOrCreate($permission, $guard));

        $staff->syncPermissions($permissions);
        $staff->assignRole('staff');

        return $staff->fresh();
    }
}
