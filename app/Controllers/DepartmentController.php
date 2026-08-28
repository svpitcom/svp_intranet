<?php

class DepartmentController extends Controller
{
    public function index()
    {
        // Implementation for displaying departments
        $departments = (new Department())->all();
        $this->view('departments/index', ['departments' => $departments]);
    }

    public function create()
    {
        // Implementation for showing department creation form
        $this->view('departments/create');
    }

    public function store()
    {
        // Implementation for storing new department
    }
}
