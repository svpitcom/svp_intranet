<?php

class DeviceController extends Controller
{
    public function index(): void
    {
        $perPage = 10;
        $page = max(1, (int) $this->input('page', 1));
        $sort = $this->input('sort', 'svp_device_id');
        $dir  = $this->input('dir', 'asc');

        $deviceModel = new Device();
        $totalDevices = $deviceModel->countAll();
        $totalPages = max(1, (int) ceil($totalDevices / $perPage));
        $page = min($page, $totalPages); // กันเผลอเข้าเลขหน้าที่เกินจำนวนจริง

        $devices = $deviceModel->paginate($page, $perPage, $sort, $dir);

        $this->view('devices/index', [
            'devices'      => $devices,
            'totalDevices' => $totalDevices,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'sort'         => $sort,
            'dir'          => $dir,
        ]);
    }

    public function create(): void
    {
        $this->view('devices/form', [
            'device'      => null,
            'deviceTypes' => (new DeviceType())->all(),
            'departments' => (new Department())->all(),
            'users'       => (new User())->all(),
        ]);
    }

    public function store(): void
    {
        $errors = $this->validate();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect('/devices/create');
        }

        (new Device())->insert([
            'svp_device_name'    => $this->input('svp_device_name'),
            'brand_name'         => $this->input('brand_name'),
            'model_name'         => $this->input('model_name'),
            'serial_number'      => $this->input('serial_number'),
            'device_type_id'     => $this->input('device_type_id') ?: null,
            'svp_department_id'  => $this->input('svp_department_id') ?: null,
            'svp_user_id'        => $this->input('svp_user_id') ?: null,
            'is_active'          => 1,
        ]);

        Session::flash('success', 'เพิ่มอุปกรณ์เรียบร้อยแล้ว');
        $this->redirect('/devices');
    }

    public function edit(string $id): void
    {
        $device = (new Device())->findByDeviceId((int) $id);

        if (!$device) {
            Session::flash('error', 'ไม่พบอุปกรณ์ที่ต้องการแก้ไข');
            $this->redirect('/devices');
        }

        $this->view('devices/form', [
            'device'      => $device,
            'deviceTypes' => (new DeviceType())->all(),
            'departments' => (new Department())->all(),
            'users'       => (new User())->all(),
        ]);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $errors = $this->validate();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect("/devices/{$id}/edit");
        }

        (new Device())->update($id, [
            'svp_device_name'    => $this->input('svp_device_name'),
            'brand_name'         => $this->input('brand_name'),
            'model_name'         => $this->input('model_name'),
            'serial_number'      => $this->input('serial_number'),
            'device_type_id'     => $this->input('device_type_id') ?: null,
            'svp_department_id'  => $this->input('svp_department_id') ?: null,
            'svp_user_id'        => $this->input('svp_user_id') ?: null,
            'is_active'          => $this->input('is_active') ? 1 : 0,
        ]);

        Session::flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
        $this->redirect('/devices');
    }

    public function destroy(string $id): void
    {
        (new Device())->delete((int) $id);
        Session::flash('success', 'ลบอุปกรณ์เรียบร้อยแล้ว');
        $this->redirect('/devices');
    }

    private function validate(): array
    {
        $errors = [];

        if (!$this->input('svp_device_name')) {
            $errors[] = 'กรุณากรอกชื่ออุปกรณ์';
        }
        if (!$this->input('serial_number')) {
            $errors[] = 'กรุณากรอกหมายเลข Serial Number';
        }

        return $errors;
    }
}
