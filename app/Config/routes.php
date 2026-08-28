<?php
return [
    'GET /login'   => ['AuthController', 'showLogin'],
    'POST /login'  => ['AuthController', 'login'],
    'GET /logout'  => ['AuthController', 'logout'],

    'GET /'        => ['UserController', 'index', ['auth']],

    'GET /users'              => ['UserController', 'index', ['auth']],
    'GET /users/create'       => ['UserController', 'create', ['auth', 'role:admin']],
    'POST /users/create'      => ['UserController', 'store', ['auth', 'role:admin']],
    'GET /users/{id}/edit'    => ['UserController', 'edit', ['auth', 'role:admin']],
    'POST /users/{id}/edit'   => ['UserController', 'update', ['auth', 'role:admin']],
    'POST /users/{id}/delete' => ['UserController', 'destroy', ['auth', 'role:admin']],
];
