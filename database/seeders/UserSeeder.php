<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;

class UserSeeder extends Seeder
{
    /**
     * Canonical local/demo Admin identities in stable actor-pool order.
     *
     * @var list<array{name: string, email: string}>
     */
    public const DEMO_ADMINS = [
        ['name' => 'Abel', 'email' => 'abel@tambak.local'],
        ['name' => 'Admin 01', 'email' => 'admin01@tambak.local'],
        ['name' => 'Admin 02', 'email' => 'admin02@tambak.local'],
        ['name' => 'Admin 03', 'email' => 'admin03@tambak.local'],
        ['name' => 'Admin 04', 'email' => 'admin04@tambak.local'],
        ['name' => 'Admin 05', 'email' => 'admin05@tambak.local'],
        ['name' => 'Admin 06', 'email' => 'admin06@tambak.local'],
        ['name' => 'Admin 07', 'email' => 'admin07@tambak.local'],
        ['name' => 'Admin 08', 'email' => 'admin08@tambak.local'],
        ['name' => 'Admin 09', 'email' => 'admin09@tambak.local'],
    ];

    /** @var array<string, string> */
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
            $abel = $this->ensureAbel($adminRole);

            $this->retireVendorManager($abel);
            $this->retireLegacyIdentities(
                replacement: $abel,
                emails: ['admin@tambak.local', 'budi@tambak.local', 'fikri@tambak.local', 'andi@tambak.local'],
            );
            $this->retireLegacyOperationalRole($abel);

            foreach (array_slice(self::DEMO_ADMINS, 1) as $identity) {
                $this->ensureCanonicalAdmin($identity, $adminRole);
            }
        });
    }

    private function ensureAbel(Role $adminRole): User
    {
        $abel = User::query()->whereKey(2)->lockForUpdate()->first();

        if (! $abel) {
            $emailOwner = User::query()->where('email', self::DEMO_ADMINS[0]['email'])->lockForUpdate()->first();

            if ($emailOwner) {
                throw new LogicException(
                    "Tidak dapat membuat Abel dengan ID 2: email abel@tambak.local dimiliki oleh ID {$emailOwner->id}.",
                );
            }

            return User::query()->forceCreate([
                'id' => 2,
                'role_id' => $adminRole->id,
                'name' => self::DEMO_ADMINS[0]['name'],
                'email' => self::DEMO_ADMINS[0]['email'],
                'password' => Hash::make('password'),
                'status' => 'ACTIVE',
            ]);
        }

        if (! in_array($abel->email, ['abel@tambak.local', 'fikri@tambak.local', 'budi@tambak.local'], true)) {
            throw new LogicException(
                'Pengguna ID 2 bukan identitas demo yang dikenali; transisi dibatalkan untuk melindungi data.',
            );
        }

        $emailOwner = User::query()
            ->where('email', self::DEMO_ADMINS[0]['email'])
            ->where('id', '!=', $abel->id)
            ->lockForUpdate()
            ->first();

        if ($emailOwner) {
            throw new LogicException(
                "Email target abel@tambak.local masih dimiliki oleh ID {$emailOwner->id}; transisi dibatalkan.",
            );
        }

        $identityChanged = $abel->name !== self::DEMO_ADMINS[0]['name']
            || $abel->email !== self::DEMO_ADMINS[0]['email']
            || $abel->role_id !== $adminRole->id;

        $abel->forceFill([
            'name' => self::DEMO_ADMINS[0]['name'],
            'email' => self::DEMO_ADMINS[0]['email'],
            'role_id' => $adminRole->id,
            ...($identityChanged ? ['remember_token' => null] : []),
        ])->save();

        if ($identityChanged) {
            $this->deleteSessionsFor($abel);
        }

        return $abel;
    }

    /** @param array{name: string, email: string} $identity */
    private function ensureCanonicalAdmin(array $identity, Role $adminRole): User
    {
        $admin = User::query()->where('email', $identity['email'])->lockForUpdate()->first();

        if (! $admin) {
            return User::query()->create([
                'role_id' => $adminRole->id,
                'name' => $identity['name'],
                'email' => $identity['email'],
                'password' => Hash::make('password'),
                'status' => 'ACTIVE',
            ]);
        }

        $identityChanged = $admin->name !== $identity['name'] || $admin->role_id !== $adminRole->id;
        $admin->forceFill([
            'role_id' => $adminRole->id,
            'name' => $identity['name'],
            ...($identityChanged ? ['remember_token' => null] : []),
        ])->save();

        if ($identityChanged) {
            $this->deleteSessionsFor($admin);
        }

        return $admin;
    }

    private function retireVendorManager(User $replacement): void
    {
        $vendor = User::query()->where('email', 'vendor@tambak.local')->lockForUpdate()->first();

        if (! $vendor) {
            return;
        }

        if ($vendor->is($replacement)) {
            throw new LogicException('Vendor Manager tidak boleh sama dengan akun pengganti Abel.');
        }

        $vendor->forceFill(['remember_token' => null])->save();
        $this->reassignHistoricalReferences($vendor, $replacement);
        $this->deleteSessionsFor($vendor);
        DB::table('password_reset_tokens')->where('email', $vendor->email)->delete();
        $vendor->delete();
    }

    /** @param list<string> $emails */
    private function retireLegacyIdentities(User $replacement, array $emails): void
    {
        $legacyUsers = User::query()
            ->whereIn('email', $emails)
            ->where('id', '!=', $replacement->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($legacyUsers as $legacyUser) {
            $this->reassignHistoricalReferences($legacyUser, $replacement);
            $this->deleteSessionsFor($legacyUser);
            DB::table('password_reset_tokens')->where('email', $legacyUser->email)->delete();
            $legacyUser->delete();
        }
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
            DB::table('password_reset_tokens')->where('email', $legacyUser->email)->delete();
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
