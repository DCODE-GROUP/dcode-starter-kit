<?php

use Spatie\LoginLink\Http\Controllers\LoginLinkController;

return [
    'allowed_environments' => ['local'],
    'allowed_hosts' => ['localhost', '127.0.0.1', '*.test', '*.dcode'],
    'automatically_create_missing_users' => true,
    'user_model' => null,
    'redirect_route_name' => null,
    'login_link_controller' => LoginLinkController::class,
    'middleware' => ['web'],
];
