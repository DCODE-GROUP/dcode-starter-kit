<?php

namespace App\Support;

final class DcodeStarterKitPermissions
{
    public const string USERS_VIEW_LIST = 'admin-portal-users-view-list';

    public const string USERS_VIEW = 'admin-portal-users-view';

    public const string USERS_CREATE = 'admin-portal-users-create';

    public const string USERS_UPDATE = 'admin-portal-users-update';

    public const string USERS_DELETE = 'admin-portal-users-delete';

    public const string ROLES_VIEW_LIST = 'admin-portal-roles-permissions-view-list';

    public const string ROLES_VIEW = 'admin-portal-roles-permissions-view';

    public const string ROLES_CREATE = 'admin-portal-roles-permissions-create';

    public const string ROLES_UPDATE = 'admin-portal-roles-permissions-update';

    public const string ROLES_DELETE = 'admin-portal-roles-permissions-delete';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::USERS_VIEW_LIST,
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::USERS_DELETE,
            self::ROLES_VIEW_LIST,
            self::ROLES_VIEW,
            self::ROLES_CREATE,
            self::ROLES_UPDATE,
            self::ROLES_DELETE,
        ];
    }
}
