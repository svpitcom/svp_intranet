<?php
return [
    // Auth
    'GET /login'   => ['AuthController', 'showLogin'],
    'POST /login'  => ['AuthController', 'login'],
    'GET /logout'  => ['AuthController', 'logout'],
    // Home
    'GET /'        => ['UserController', 'index', ['auth']],
    // Users
    'GET /users'              => ['UserController', 'index', ['auth']],
    'GET /users/create'       => ['UserController', 'create', ['auth', 'role:admin']],
    'POST /users/create'      => ['UserController', 'store', ['auth', 'role:admin']],
    'GET /users/{id}/edit'    => ['UserController', 'edit', ['auth', 'role:admin']],
    'POST /users/{id}/edit'   => ['UserController', 'update', ['auth', 'role:admin']],
    'POST /users/{id}/delete' => ['UserController', 'destroy', ['auth', 'role:admin']],
    // Departments
    'GET /departments'        => ['DepartmentController', 'index', ['auth']],
    'GET /departments/create'       => ['DepartmentController', 'create', ['auth', 'role:admin']],
    'POST /departments/create'      => ['DepartmentController', 'store', ['auth', 'role:admin']],
    'GET /departments/{id}/edit'    => ['DepartmentController', 'edit', ['auth', 'role:admin']],
    'POST /departments/{id}/edit'   => ['DepartmentController', 'update', ['auth', 'role:admin']],
    'POST /departments/{id}/delete' => ['DepartmentController', 'destroy', ['auth', 'role:admin']],
    // Positions
    'GET /positions'        => ['PositionController', 'index', ['auth']],
    'GET /positions/create'       => ['PositionController', 'create', ['auth', 'role:admin']],
    'POST /positions/create'      => ['PositionController', 'store', ['auth', 'role:admin']],
    'GET /positions/{id}/edit'    => ['PositionController', 'edit', ['auth', 'role:admin']],
    'POST /positions/{id}/edit'   => ['PositionController', 'update', ['auth', 'role:admin']],
    'POST /positions/{id}/delete' => ['PositionController', 'destroy', ['auth', 'role:admin']],
];
