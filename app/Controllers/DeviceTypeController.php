<?php

class DeviceTypeController extends Controller
{
    public function index(): void
    {
        $sort = $this->input('sort', 'device_type_id');
        $dir  = $this->input('dir', 'asc');

        $devicetypes = (new DeviceType())->allSorted($sort, $dir);
        $devicetypes = Search::rows($devicetypes, Search::term(), ['device_type_id', 'device_type_name']);

        $this->view('device_types/index', ['devicetypes' => $devicetypes]);
    }

    public function create(): void
    {
        $this->view('device_types/form');
    }

    public function store(): void
    {
        $errors = $this->validateDeviceType();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect('/device_types/create');
            return;
        }

        (new DeviceType())->insert([
            'device_type_name' => $this->input('device_type_name'),
        ]);

        Session::flash('success', 'เพิ่มประเภทอุปกรณ์เรียบร้อยแล้ว');
        $this->redirect('/device_types');
    }

    public function edit(int $id): void
    {
        $devicetypes = (new DeviceType())->find($id);

        if (!$devicetypes) {
            Session::flash('errors', 'ไม่พบข้อมูลประเภทอุปกรณ์นี้');
            $this->redirect('/device_types');
            return;
        }

        $this->view('device_types/form', ['devicetypes' => $devicetypes]);
    }

    public function update(int $id): void
    {
        $devicetypes = (new DeviceType())->find($id);

        if (!$devicetypes) {
            Session::flash('errors', 'ไม่พบข้อมูลประเภทอุปกรณ์นี้');
            $this->redirect('/device_types');
            return;
        }

        $errors = $this->validateDeviceType();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect("/device_types/{$id}/edit");
            return;
        }

        (new DeviceType())->update($id, [
            'device_type_name' => $this->input('device_type_name'),
        ]);

        Session::flash('success', 'แก้ไขประเภทอุปกรณ์เรียบร้อยแล้ว');
        $this->redirect('/device_types');
    }

    public function destroy(int $id): void
    {
        $devicetypes = (new DeviceType())->find($id);

        if (!$devicetypes) {
            Session::flash('errors', 'ไม่พบข้อมูลประเภทอุปกรณ์นี้');
            $this->redirect('/device_types');
            return;
        }

        (new DeviceType())->delete($id);

        Session::flash('success', 'ลบประเภทอุปกรณ์เรียบร้อยแล้ว');
        $this->redirect('/device_types');
    }

    private function validateDeviceType(): array
    {
        $errors = [];
        if (!$this->input('device_type_name')) $errors[] = 'กรุณากรอกชื่อประเภทอุปกรณ์';
        return $errors;
    }
}
