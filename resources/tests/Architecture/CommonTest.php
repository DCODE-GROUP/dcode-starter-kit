<?php

arch('globals')
    ->expect([
        'dd',
        'dump',
        'env',
        'var_dump',
        'print_r',
        'die',
        'exit',
    ])
    ->not->toBeUsed();

arch('Controller have the expected suffix')
    ->expect('App\\Http\\Controllers')
    ->toHaveSuffix('Controller');

arch('Requests have the expected suffix')
    ->expect('App\\Http\\Requests')
    ->toHaveSuffix('Request');

arch('Exceptions have the expected suffix')
    ->expect('App\\Exceptions')
    ->toHaveSuffix('Exception');

arch('Observers have the expected suffix')
    ->expect('App\\Observers')
    ->toHaveSuffix('Observer');

arch('Policies have the expected suffix')
    ->expect('App\\Policies')
    ->toHaveSuffix('Policy');

arch('Providers have the expected suffix')
    ->expect('App\\Providers')
    ->toHaveSuffix('Provider');

arch('Services have the expected suffix')
    ->expect('App\\Services')
    ->toHaveSuffix('Service');

arch('Seeders have the expected suffix')
    ->expect('Database\\Seeders')
    ->toHaveSuffix('Seeder');
