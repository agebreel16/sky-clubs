<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RolesAndPermissionsSeeder
 *
 * Seeds all RBAC roles and permissions for the Sky Clubs Campaign system.
 * Also assigns the correct permissions to each role.
 *
 * ─── Roles ───────────────────────────────────────────────────────────────────
 * super_admin    : Full system access. Max 2 users. Can do everything.
 * admin          : Full access except user management (cannot create/delete users).
 * supervisor     : Team management, reporting, and read access to all data.
 * data_entry     : Can upload import files and view basic agent/club data.
 * viewer         : Read-only access to all non-sensitive data.
 * finance_officer: Can process payments and view reward data.
 *
 * ─── Permission Groups ───────────────────────────────────────────────────────
 * agents   : Agent CRUD and management
 * clubs    : Club configuration management
 * reports  : Report generation and export
 * users    : User account management
 * settings : System settings and configuration
 * audit    : Audit log access
 * payments : Financial reward management
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // Step 1: Define and seed all permissions
        // ─────────────────────────────────────────────────────────────────────
        $permissions = [
            // ── Agents ────────────────────────────────────────────────────────
            ['name' => 'view_agents',           'group' => 'agents',   'description' => 'View agent list and individual agent details'],
            ['name' => 'create_agent',          'group' => 'agents',   'description' => 'Manually add a new agent to the system'],
            ['name' => 'edit_agent',            'group' => 'agents',   'description' => 'Edit agent notes and administrative fields'],
            ['name' => 'delete_agent',          'group' => 'agents',   'description' => 'Soft-delete an agent from the system'],
            ['name' => 'view_agent_history',    'group' => 'agents',   'description' => 'View agent promotion/demotion history log'],
            ['name' => 'view_agent_snapshots',  'group' => 'agents',   'description' => 'View daily data snapshots for any agent'],

            // ── Clubs ──────────────────────────────────────────────────────────
            ['name' => 'view_clubs',            'group' => 'clubs',    'description' => 'View club configurations and statistics'],
            ['name' => 'edit_club',             'group' => 'clubs',    'description' => 'Edit club reward amounts, thresholds, and settings'],
            ['name' => 'toggle_club',           'group' => 'clubs',    'description' => 'Enable or disable a club (is_active toggle)'],

            // ── Reports ────────────────────────────────────────────────────────
            ['name' => 'view_reports',          'group' => 'reports',  'description' => 'View all generated reports and dashboards'],
            ['name' => 'export_reports',        'group' => 'reports',  'description' => 'Export reports to Excel/PDF/CSV'],
            ['name' => 'view_daily_snapshots',  'group' => 'reports',  'description' => 'Access historical daily snapshot data'],

            // ── Users ──────────────────────────────────────────────────────────
            ['name' => 'view_users',            'group' => 'users',    'description' => 'View list of system users and their roles'],
            ['name' => 'create_user',           'group' => 'users',    'description' => 'Create new system user accounts'],
            ['name' => 'edit_user',             'group' => 'users',    'description' => 'Edit user profile, role, and department'],
            ['name' => 'delete_user',           'group' => 'users',    'description' => 'Soft-delete (deactivate) a user account'],
            ['name' => 'reset_user_password',   'group' => 'users',    'description' => 'Force a password reset for any user'],
            ['name' => 'lock_unlock_user',      'group' => 'users',    'description' => 'Manually lock or unlock a user account'],

            // ── Settings ──────────────────────────────────────────────────────
            ['name' => 'view_settings',         'group' => 'settings', 'description' => 'View system configuration settings'],
            ['name' => 'edit_settings',         'group' => 'settings', 'description' => 'Modify system configuration settings'],

            // ── Data Import ────────────────────────────────────────────────────
            ['name' => 'import_data',           'group' => 'agents',   'description' => 'Upload and process Excel/API data imports'],
            ['name' => 'view_imports',          'group' => 'agents',   'description' => 'View import history and processing statistics'],
            ['name' => 'rollback_import',       'group' => 'agents',   'description' => 'Roll back a data import to previous state'],

            // ── Audit ──────────────────────────────────────────────────────────
            ['name' => 'view_audit_logs',       'group' => 'audit',    'description' => 'Read the immutable audit log trail'],
            ['name' => 'export_audit_logs',     'group' => 'audit',    'description' => 'Export audit logs for compliance reporting'],

            // ── Payments ──────────────────────────────────────────────────────
            ['name' => 'view_rewards',          'group' => 'payments', 'description' => 'View agent reward records and payment status'],
            ['name' => 'process_payment',       'group' => 'payments', 'description' => 'Mark rewards as paid or failed'],
            ['name' => 'export_payments',       'group' => 'payments', 'description' => 'Export payment data for finance processing'],

            // ── Lottery ────────────────────────────────────────────────────────
            ['name' => 'view_opportunities',    'group' => 'reports',  'description' => 'View agent lottery tickets and opportunity history'],
            ['name' => 'manage_lottery',        'group' => 'settings', 'description' => 'Run and manage lottery draws per club'],
        ];

        $permissionIds = []; // name => UUID map for role assignment

        foreach ($permissions as $perm) {
            $id = Str::uuid()->toString();
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'id'          => $id,
                    'name'        => $perm['name'],
                    'description' => $perm['description'],
                    'group'       => $perm['group'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
            // Fetch actual ID (in case it already existed from a prior seed run)
            $permissionIds[$perm['name']] = DB::table('permissions')
                ->where('name', $perm['name'])
                ->value('id');
        }

        $this->command->info('✅ Permissions seeded: ' . count($permissions) . ' permissions.');

        // ─────────────────────────────────────────────────────────────────────
        // Step 2: Define and seed all roles
        // ─────────────────────────────────────────────────────────────────────
        $roles = [
            [
                'name'        => 'super_admin',
                'description' => 'Full unrestricted system access. Maximum 2 users should hold this role.',
            ],
            [
                'name'        => 'admin',
                'description' => 'Complete administrative access including club edits, reports, and imports. Cannot manage user accounts.',
            ],
            [
                'name'        => 'supervisor',
                'description' => 'Team management access. Can view all data, run reports, and oversee data entry.',
            ],
            [
                'name'        => 'data_entry',
                'description' => 'Can upload Excel files, process daily imports, and view basic agent and club data.',
            ],
            [
                'name'        => 'viewer',
                'description' => 'Read-only access to agents, clubs, and reports. Cannot modify any data.',
            ],
            [
                'name'        => 'finance_officer',
                'description' => 'Can view and process financial rewards. Access to payment export functionality.',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'id'          => Str::uuid()->toString(),
                    'name'        => $role['name'],
                    'description' => $role['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }

        // Fetch role IDs after upsert
        $roleIds = DB::table('roles')->pluck('id', 'name')->toArray();

        $this->command->info('✅ Roles seeded: ' . count($roles) . ' roles.');

        // ─────────────────────────────────────────────────────────────────────
        // Step 3: Define permission assignments per role
        // ─────────────────────────────────────────────────────────────────────

        /**
         * Helper: get all permission names as an array.
         */
        $all = array_column($permissions, 'name');

        /**
         * Permissions by role:
         */
        $rolePermissions = [

            // super_admin → ALL permissions
            'super_admin' => $all,

            // admin → all EXCEPT user management
            'admin' => array_diff($all, [
                'create_user',
                'edit_user',
                'delete_user',
                'reset_user_password',
                'lock_unlock_user',
            ]),

            // supervisor → read/view + reports + no destructive actions
            'supervisor' => [
                'view_agents',
                'view_agent_history',
                'view_agent_snapshots',
                'view_clubs',
                'view_reports',
                'export_reports',
                'view_daily_snapshots',
                'view_users',
                'view_imports',
                'view_audit_logs',
                'view_rewards',
                'export_payments',
                'view_opportunities',
            ],

            // data_entry → import + basic views
            'data_entry' => [
                'view_agents',
                'view_agent_history',
                'view_clubs',
                'import_data',
                'view_imports',
                'view_rewards',
                'view_opportunities',
            ],

            // viewer → read-only everything (no exports, no sensitive)
            'viewer' => [
                'view_agents',
                'view_agent_history',
                'view_clubs',
                'view_reports',
                'view_imports',
                'view_rewards',
                'view_opportunities',
            ],

            // finance_officer → payment actions + views
            'finance_officer' => [
                'view_agents',
                'view_rewards',
                'process_payment',
                'export_payments',
                'view_clubs',
                'view_reports',
            ],
        ];

        // ─────────────────────────────────────────────────────────────────────
        // Step 4: Seed the role_has_permissions junction table
        // ─────────────────────────────────────────────────────────────────────
        $assignments = 0;

        foreach ($rolePermissions as $roleName => $permNames) {
            $roleId = $roleIds[$roleName] ?? null;
            if (! $roleId) {
                $this->command->warn("⚠️  Role not found: {$roleName}");
                continue;
            }

            foreach ($permNames as $permName) {
                $permId = $permissionIds[$permName] ?? null;
                if (! $permId) {
                    $this->command->warn("⚠️  Permission not found: {$permName}");
                    continue;
                }

                // Upsert to avoid duplicates on re-seeding
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                    ]);
                    $assignments++;
                }
            }
        }

        $this->command->info("✅ role_has_permissions: {$assignments} new assignments seeded.");
        $this->command->newLine();
        $this->command->table(
            ['Role', 'Permission Count'],
            array_map(
                fn ($role, $perms) => [$role, count($perms)],
                array_keys($rolePermissions),
                array_values($rolePermissions)
            )
        );
    }
}
