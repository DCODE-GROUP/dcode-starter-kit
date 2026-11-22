<?php

test('Query Filter have the expected suffix')
    ->expect('App\\Support\\Filters\\Query')
    ->toHaveSuffix('Query');

test('Query Sorts have the expected suffix')
    ->expect('App\\Support\\Filters\\Sorts')
    ->toHaveSuffix('Sort');
