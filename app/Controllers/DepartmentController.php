<?php

class DepartmentController extends Controller
{
    public function index(): void
    {
        // Implementation for displaying departments
        $departments = (new Department())->all();
        $departments = Search::rows($departments, Search::term(), ['svp_code_department', 'svp_department_name']);
        $this->view('departments/index', ['departments' => $departments]);
    }

    public function create(): void
    {
        // Implementation for showing department creation form
        $this->view('departments/form');
    }

    public function store(): void
    {
        // Implementation for storing new department
        $errors = $this->validateDepartment();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect('/departments/create');
            return;
        }

        (new Department())->insert([
            'svp_code_department' => $this->input('svp_code_department'),
            'svp_department_name'  => $this->input('svp_department_name'),
        ]);

        Session::flash('success', 'เพิ่มแผนกเรียบร้อยแล้ว');
        $this->redirect('/departments');
    }

    public function edit(int $id): void
    {
        // Implementation for showing department edit form
        $department = (new Department())->find($id);

        if (!$department) {
            Session::flash('errors', 'ไม่พบข้อมูลแผนกนี้');
            $this->redirect('/departments');
            return;
        }

        $this->view('departments/form', ['department' => $department]);
    }

    public function update(int $id): void
    {
        // Implementation for updating an existing department
        $department = (new Department())->find($id);

        if (!$department) {
            Session::flash('errors', 'ไม่พบข้อมูลแผนกนี้');
            $this->redirect('/departments');
            return;
        }

        $errors = $this->validateDepartment();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect("/departments/{$id}/edit");
            return;
        }

        (new Department())->update($id, [
            'svp_code_department' => $this->input('svp_code_department'),
            'svp_department_name'  => $this->input('svp_department_name'),
        ]);

        Session::flash('success', 'แก้ไขข้อมูลแผนกเรียบร้อยแล้ว');
        $this->redirect('/departments');
    }

    public function destroy(int $id): void
    {
        // Implementation for deleting a department
        $department = (new Department())->find($id);

        if (!$department) {
            Session::flash('errors', 'ไม่พบข้อมูลแผนกนี้');
            $this->redirect('/departments');
            return;
        }

        (new Department())->delete($id);

        Session::flash('success', 'ลบแผนกเรียบร้อยแล้ว');
        $this->redirect('/departments');
    }

    /**
     * Validate department input data.
     * Returns an array of error messages (empty array = no errors).
     */
    private function validateDepartment(): array
    {
        $errors = [];

        $code = $this->input('svp_code_department');
        $name = $this->input('svp_department_name');

        if (empty($code)) {
            $errors[] = 'กรุณากรอกรหัสแผนก';
        } elseif (mb_strlen($code) > 50) {
            $errors[] = 'รหัสแผนกต้องไม่เกิน 50 ตัวอักษร';
        }

        if (empty($name)) {
            $errors[] = 'กรุณากรอกชื่อแผนก';
        } elseif (mb_strlen($name) > 255) {
            $errors[] = 'ชื่อแผนกต้องไม่เกิน 255 ตัวอักษร';
        }

        return $errors;
    }
}
