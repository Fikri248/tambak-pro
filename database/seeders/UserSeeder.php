<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Historical ownership columns that reference the exact legacy demo users.
     *
     * @var array<string, string>
     */
    private const USER_REFERENCES = [
        'stocking_transactions' => 'created_by',
        'stock_movements' => 'created_by',
        'stock_adjustments' => 'created_by',
        'feeding_transactions' => 'created_by',
        'audit_logs' => 'user_id',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $adminRole = Role::query()->where('name', 'Admin')->firstOrFail();
            $managerRole = Role::query()->where('name', 'Manager')->firstOrFail();

            $fikri = $this->transitionDemoIdentity(
                legacyEmail: 'budi@tambak.local',
                targetEmail: 'fikri@tambak.local',
                targetName: 'Fikri',
                targetRole: $adminRole,
            );

            $this->transitionDemoIdentity(
                legacyEmail: 'andi@tambak.local',
                targetEmail: 'abel@tambak.local',
                targetName: 'Abel',
                targetRole: $managerRole,
            );

            $this->removeLegacyAdministrator($fikri);
            $this->retireLegacyOperationalRole($fikri);
        });
    }

    private function transitionDemoIdentity(
        string $legacyEmail,
        string $targetEmail,
        string $targetName,
        Role $targetRole,
    ): User {
        $target = User::query()->where('email', $targetEmail)->lockForUpdate()->first();
        $legacy = User::query()->where('email', $legacyEmail)->lockForUpdate()->first();

        if (! $target && $legacy) {
            $roleChanged = $legacy->role_id !== $targetRole->id;

            $legacy->forceFill([
                'email' => $targetEmail,
                'name' => $targetName,
                'role_id' => $targetRole->id,
                ...($roleChanged ? ['remember_token' => null] : []),
            ])->save();

            if ($roleChanged) {
                $this->deleteSessionsFor($legacy);
            }

            return $legacy;
        }

        if (! $target) {
            return User::query()->create([
                'email' => $targetEmail,
                'name' => $targetName,
                'role_id' => $targetRole->id,
                'password' => Hash::make('password'),
                'status' => 'ACTIVE',
            ]);
        }

        $roleChanged = $target->role_id !== $targetRole->id;

        $target->forceFill([
            'name' => $targetName,
            'role_id' => $targetRole->id,
            ...($roleChanged ? ['remember_token' => null] : []),
        ])->save();

        if ($roleChanged) {
            $this->deleteSessionsFor($target);
        }

        if ($legacy && $legacy->isNot($target)) {
            $this->reassignHistoricalReferences($legacy, $target);
            $this->deleteSessionsFor($legacy);
            $legacy->delete();
        }

        return $target;
    }

    private function removeLegacyAdministrator(User $replacement): void
    {
        $legacyAdministrator = User::query()
            ->where('email', 'admin@tambak.local')
            ->lockForUpdate()
            ->first();

        if (! $legacyAdministrator || $legacyAdministrator->is($replacement)) {
            return;
        }

        $this->reassignHistoricalReferences($legacyAdministrator, $replacement);
        $this->deleteSessionsFor($legacyAdministrator);
        $legacyAdministrator->delete();
    }

    private function retireLegacyOperationalRole(User $replacement): void
    {
        $legacyRole = Role::query()->where('name', 'Operator')->lockForUpdate()->first();

        if (! $legacyRole) {
            return;
        }

        $legacyUsers = User::query()
            ->where('role_id', $legacyRole->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($legacyUsers as $legacyUser) {
            $this->reassignHistoricalReferences($legacyUser, $replacement);
            $this->deleteSessionsFor($legacyUser);
            $legacyUser->delete();
        }

        $legacyRole->delete();
    }

    private function reassignHistoricalReferences(User $from, User $to): void
    {
        foreach (self::USER_REFERENCES as $table => $column) {
            if (Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $from->id)->update([$column => $to->id]);
            }
        }
    }

    private function deleteSessionsFor(User $user): void
    {
        if (Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }
}
