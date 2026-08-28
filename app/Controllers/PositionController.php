<?php

class PositionController extends Controller
{
    public function index(): void
    {
        $positions = (new Position())->all();
        $this->view('positions/index', ['positions' => $positions]);
    }

    public function create(): void
    {
        $this->view('positions/form');
    }

    public function store(): void
    {
        // Implementation for storing new department
        $errors = $this->validatePosition();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect('/positions/create');
            return;
        }

        (new Position())->insert([
            'position_name'  => $this->input('position_name'),
        ]);

        Session::flash('success', 'เพิ่มตำแหน่งเรียบร้อยแล้ว');
        $this->redirect('/positions');
    }

    public function edit(int $id): void
    {
        // Implementation for showing department edit form
        $positions = (new Position())->find($id);

        if (!$positions) {
            Session::flash('errors', 'ไม่พบข้อมูลแผนกนี้');
            $this->redirect('/positions');
            return;
        }

        $this->view('positions/form', ['positions' => $positions]);
    }

    public function update(int $id): void
    {
        // Implementation for updating an existing department
        $positions = (new Position())->find($id);

        if (!$positions) {
            Session::flash('errors', 'ไม่พบข้อมูลแผนกนี้');
            $this->redirect('/positions');
            return;
        }

        $errors = $this->validatePosition();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect("/positions/{$id}/edit");
            return;
        }

        (new Position())->update($id, [
            'position_name'  => $this->input('position_name'),
        ]);

        Session::flash('success', 'แก้ไขข้อมูลแผนกเรียบร้อยแล้ว');
        $this->redirect('/positions');
    }

    public function delete(int $id): void
    {
        $position = (new Position())->find($id);

        if (!$position) {
            Session::flash('errors', 'ไม่พบข้อมูลตำแหน่งนี้');
            $this->redirect('/positions');
            return;
        }

        (new Position())->delete($id);

        Session::flash('success', 'ลบตำแหน่งเรียบร้อยแล้ว');
        $this->redirect('/positions');
    }

    private function validatePosition(): array
    {
        $errors = [];
        $name = $this->input('position_name');

        if (empty($name)) {
            $errors[] = 'กรุณากรอกชื่อตำแหน่ง';
        } elseif (mb_strlen($name) > 255) {
            $errors[] = 'ชื่อตำแหน่งต้องไม่เกิน 255 ตัวอักษร';
        }

        return $errors;
    }
}
