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
    'GET /users/search' => ['UserController', 'search', ['auth']],
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
    // Device Types
    'GET /device_types'        => ['DeviceTypeController', 'index', ['auth']],
    'GET /device_types/create'       => ['DeviceTypeController', 'create', ['auth', 'role:admin']],
    'POST /device_types/create'      => ['DeviceTypeController', 'store', ['auth', 'role:admin']],
    'GET /device_types/{id}/edit'    => ['DeviceTypeController', 'edit', ['auth', 'role:admin']],
    'POST /device_types/{id}/edit'   => ['DeviceTypeController', 'update', ['auth', 'role:admin']],
    'POST /device_types/{id}/delete' => ['DeviceTypeController', 'destroy', ['auth', 'role:admin']],
    // Devices
    'GET /devices'        => ['DeviceController', 'index', ['auth']],
    'GET /devices/create'       => ['DeviceController', 'create', ['auth', 'role:admin']],
    'POST /devices/create'      => ['DeviceController', 'store', ['auth', 'role:admin']],
    'GET /devices/{id}/edit'    => ['DeviceController', 'edit', ['auth', 'role:admin']],
    'POST /devices/{id}/edit'   => ['DeviceController', 'update', ['auth', 'role:admin']],
    'POST /devices/{id}/delete' => ['DeviceController', 'destroy', ['auth', 'role:admin']],
    // ---------- PM Schedule ----------
    'GET /pm-schedules'                 => ['PmScheduleController', 'index', ['auth']],
    'GET /pm-schedules/create'          => ['PmScheduleController', 'create', ['auth', 'role:admin,manager']],
    'POST /pm-schedules/create'         => ['PmScheduleController', 'store', ['auth', 'role:admin,manager']],
    'GET /pm-schedules/{id}/edit'       => ['PmScheduleController', 'edit', ['auth', 'role:admin,manager']],
    'POST /pm-schedules/{id}/edit'      => ['PmScheduleController', 'update', ['auth', 'role:admin,manager']],
    'POST /pm-schedules/{id}/delete'    => ['PmScheduleController', 'destroy', ['auth', 'role:admin']],
    // ---------- PM Record----------
    'GET /pm-schedules/{id}/record'     => ['PmRecordController', 'create', ['auth']],
    'POST /pm-schedules/{id}/record'    => ['PmRecordController', 'store', ['auth']],
    'GET /pm-records'                   => ['PmRecordController', 'index', ['auth']],
    'GET /pm-schedules/export' => ['ExportController', 'schedules', ['auth']],
    'GET /pm-records/export'   => ['ExportController', 'records', ['auth']],
    'GET /pm-schedules/{id}/export' => ['ExportController', 'scheduleForm', ['auth']],
];
