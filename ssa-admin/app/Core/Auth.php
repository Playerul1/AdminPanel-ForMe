<?php
/**
 * SSA Admin Panel - Auth
 *
 * Login methods:
 *  - WHMCS Staff (tbladmins) username/email + password
 *  - Master Key (fallback / emergency)
 */

class Auth
{
    private const SESSION_OK_KEY   = 'auth_ok';
    private const SESSION_USER_KEY = 'auth_user';

    /**
     * Is user authenticated?
     */
    public static function check(): bool
    {
        return (bool)Session::get(self::SESSION_OK_KEY, false);
    }

    /**
     * Current user array.
     */
    public static function user(): array
    {
        return (array)Session::get(self::SESSION_USER_KEY, []);
    }

    public static function id(): int
    {
        return (int)(self::user()['id'] ?? 0);
    }

    public static function whmcsAdminId(): int
    {
        return (int)(self::user()['whmcs_admin_id'] ?? 0);
    }

    public static function role(): string
    {
        return (string)(self::user()['role'] ?? 'Staff');
    }

    /**
     * Simple privileged rule (we refine later with real permissions UI).
     */
    public static function isPrivileged(): bool
    {
        $role = strtolower(self::role());
        if (in_array($role, ['owner', 'admin', 'administrator', 'manager', 'master'], true)) {
            return true;
        }

        // optional permission blob (future)
        $perms = self::user()['perms'] ?? [];
        if (is_array($perms) && !empty($perms['leads.view_phone_all'])) {
            return true;
        }

        return false;
    }

    /**
     * Main login entry.
     */
    public static function attempt(string $identifier, string $password = '', string $masterKey = ''): bool
    {
        $identifier = trim($identifier);

        // 1) Master key (emergency)
        if ($masterKey !== '') {
            if (self::loginWithMasterKey($masterKey)) {
                return true;
            }
        }

        // 2) WHMCS staff
        if ($identifier === '' || $password === '') {
            return false;
        }

        return self::loginWithWhmcsStaff($identifier, $password);
    }

    /**
     * Master key login (fallback).
     * - dacă auth.master_key e gol, folosim app.app_key ca fallback.
     */
    public static function loginWithMasterKey(string $masterKey): bool
    {
        $expected = (string)ssa_config('auth.master_key', '');
        if ($expected === '') {
            $expected = (string)ssa_config('app.app_key', '');
        }

        if ($expected === '' || !hash_equals($expected, (string)$masterKey)) {
            return false;
        }

        self::setUser([
            'id' => (int)ssa_config('auth.master_uid', 1),
            'whmcs_admin_id' => 0,
            'name' => (string)ssa_config('auth.master_name', 'Owner'),
            'username' => 'owner',
            'email' => null,
            'role' => (string)ssa_config('auth.master_role', 'Owner'),
            'perms' => ['*' => true],
            'login_method' => 'master',
        ]);

        return true;
    }

    /**
     * WHMCS staff login.
     */
    public static function loginWithWhmcsStaff(string $identifier, string $password): bool
    {
        $row = self::fetchWhmcsAdmin($identifier);
        if (!$row) {
            Logger::warning('WHMCS login failed: admin not found', ['identifier' => $identifier]);
            return false;
        }

        if ((int)($row['disabled'] ?? 0) === 1) {
            Logger::warning('WHMCS login blocked: admin disabled', ['identifier' => $identifier, 'whmcs_admin_id' => (int)($row['id'] ?? 0)]);
            return false;
        }

        // WHMCS are de obicei passwordhash; păstrăm fallback pe password.
        $hashNew = (string)($row['passwordhash'] ?? '');
        $hashOld = (string)($row['password'] ?? '');

        $passOk = false;
        if ($hashNew !== '' && self::verifyWhmcsPassword($password, $hashNew)) {
            $passOk = true;
        } elseif ($hashOld !== '' && self::verifyWhmcsPassword($password, $hashOld)) {
            $passOk = true;
        }

        if (!$passOk) {
            Logger::warning('WHMCS login failed: password mismatch', ['identifier' => $identifier, 'whmcs_admin_id' => (int)($row['id'] ?? 0)]);
            return false;
        }

        // sync to panel_db (so later we can assign panel roles/perms)
        $panelUser = self::syncStaffUserToPanel($row);

        $displayName = trim(((string)($row['firstname'] ?? '') . ' ' . (string)($row['lastname'] ?? '')));
        if ($displayName === '') {
            $displayName = (string)($row['username'] ?? 'Staff');
        }

        self::setUser([
            'id' => (int)($panelUser['id'] ?? 0),
            'whmcs_admin_id' => (int)($row['id'] ?? 0),
            'name' => $displayName,
            'username' => (string)($row['username'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'role' => (string)($panelUser['panel_role'] ?? 'Staff'),
            'whmcs_role_id' => (int)($row['roleid'] ?? 0),
            'whmcs_role_name' => (string)($row['role_name'] ?? ''),
            'perms' => self::decodePerms((string)($panelUser['perms_json'] ?? '')),
            'login_method' => 'whmcs',
        ]);

        Logger::info('WHMCS login OK', ['identifier' => $identifier, 'whmcs_admin_id' => (int)($row['id'] ?? 0), 'panel_user_id' => (int)($panelUser['id'] ?? 0)]);

        return true;
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_OK_KEY);
        Session::forget(self::SESSION_USER_KEY);
    }

    // -------------------- internals --------------------

    private static function setUser(array $user): void
    {
        Session::set(self::SESSION_OK_KEY, true);
        Session::set(self::SESSION_USER_KEY, $user);
    }

    private static function fetchWhmcsAdmin(string $identifier): ?array
    {
        $pdo = DB::pdo('whmcs_db');

        // WHMCS: tbladmins + tbladminroles
        // IMPORTANT: nu folosim același placeholder de 2 ori (PDO + emulate_prepares=false => HY093)
        $sql = "SELECT a.id, a.username, a.password, a.passwordhash, a.email, a.firstname, a.lastname, a.roleid, a.disabled, r.name AS role_name
                FROM tbladmins a
                LEFT JOIN tbladminroles r ON r.id = a.roleid
                WHERE a.username = :id1 OR a.email = :id2
                LIMIT 1";

        $st = $pdo->prepare($sql);
        $st->execute([':id1' => $identifier, ':id2' => $identifier]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * WHMCS hashes: bcrypt/argon (în special în coloana passwordhash)
     * + fallback legacy md5 (în unele instalări vechi)
     */
    private static function verifyWhmcsPassword(string $plain, string $hash): bool
    {
        $hash = trim($hash);
        if ($hash === '') {
            return false;
        }

        // bcrypt / argon
        if (preg_match('~^\$2[aby]\$~', $hash) || preg_match('~^\$argon2~', $hash)) {
            return password_verify($plain, $hash);
        }

        // legacy md5
        if (preg_match('~^[a-f0-9]{32}$~i', $hash)) {
            return hash_equals(strtolower($hash), strtolower(md5($plain)));
        }

        // unknown
        return false;
    }

    private static function ensureStaffSchema(): void
    {
        $pdo = DB::pdo('panel_db');
        $pdo->exec("CREATE TABLE IF NOT EXISTS ssa_staff_users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            whmcs_admin_id INT UNSIGNED NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(190) NULL,
            first_name VARCHAR(100) NULL,
            last_name VARCHAR(100) NULL,
            whmcs_role_id INT UNSIGNED NULL,
            whmcs_role_name VARCHAR(190) NULL,
            panel_role VARCHAR(50) NOT NULL DEFAULT 'Staff',
            perms_json TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_whmcs_admin_id (whmcs_admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function syncStaffUserToPanel(array $whmcsRow): array
    {
        self::ensureStaffSchema();
        $pdo = DB::pdo('panel_db');

        $whmcsId = (int)($whmcsRow['id'] ?? 0);
        $now = date('Y-m-d H:i:s');

        // find existing
        $st = $pdo->prepare('SELECT * FROM ssa_staff_users WHERE whmcs_admin_id = :id LIMIT 1');
        $st->execute([':id' => $whmcsId]);
        $existing = $st->fetch(PDO::FETCH_ASSOC);

        $data = [
            ':whmcs_admin_id' => $whmcsId,
            ':username' => (string)($whmcsRow['username'] ?? ''),
            ':email' => (string)($whmcsRow['email'] ?? ''),
            ':first_name' => (string)($whmcsRow['firstname'] ?? ''),
            ':last_name' => (string)($whmcsRow['lastname'] ?? ''),
            ':whmcs_role_id' => (int)($whmcsRow['roleid'] ?? 0),
            ':whmcs_role_name' => (string)($whmcsRow['role_name'] ?? ''),
            ':updated_at' => $now,
            ':last_login_at' => $now,
        ];

        if ($existing) {
            $sql = 'UPDATE ssa_staff_users
                    SET username=:username, email=:email, first_name=:first_name, last_name=:last_name,
                        whmcs_role_id=:whmcs_role_id, whmcs_role_name=:whmcs_role_name,
                        updated_at=:updated_at, last_login_at=:last_login_at
                    WHERE whmcs_admin_id=:whmcs_admin_id';
            $pdo->prepare($sql)->execute($data);

            $st = $pdo->prepare('SELECT * FROM ssa_staff_users WHERE whmcs_admin_id = :id LIMIT 1');
            $st->execute([':id' => $whmcsId]);
            return (array)$st->fetch(PDO::FETCH_ASSOC);
        }

        // default role heuristic: dacă rolul WHMCS conține admin keywords -> Owner
        $roleName = strtolower((string)($whmcsRow['role_name'] ?? ''));
        $panelRole = (strpos($roleName, 'administrator') !== false || strpos($roleName, 'admin') !== false)
            ? 'Owner'
            : 'Staff';

        $sql = 'INSERT INTO ssa_staff_users
                (whmcs_admin_id, username, email, first_name, last_name, whmcs_role_id, whmcs_role_name, panel_role, perms_json, is_active, last_login_at, created_at, updated_at)
                VALUES
                (:whmcs_admin_id, :username, :email, :first_name, :last_name, :whmcs_role_id, :whmcs_role_name, :panel_role, :perms_json, 1, :last_login_at, :created_at, :updated_at)';

        $dataIns = $data + [
            ':panel_role' => $panelRole,
            ':perms_json' => null,
            ':created_at' => $now,
        ];

        $pdo->prepare($sql)->execute($dataIns);
        $newId = (int)$pdo->lastInsertId();
        $st = $pdo->prepare('SELECT * FROM ssa_staff_users WHERE id = :id LIMIT 1');
        $st->execute([':id' => $newId]);
        return (array)$st->fetch(PDO::FETCH_ASSOC);
    }

    private static function decodePerms(string $json): array
    {
        $json = trim($json);
        if ($json === '') return [];
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }
}
