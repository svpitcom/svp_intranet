<?php

class DeviceController extends Controller
{
    private const PER_PAGE = 20;

    /** คอลัมน์ที่อนุญาตให้ sort ได้ (กัน SQL injection) */
    private const SORTABLE = [
        'svp_device_id',
        'svp_device_name',
        'brand_name',
        'device_type_name',
        'svp_department_name',
        'is_active',
    ];

    public function index(): void
    {
        $sort = (string) $this->input('sort', 'svp_device_name');
        if (!in_array($sort, self::SORTABLE, true)) {
            $sort = 'svp_device_name';
        }
        $dir = strtolower((string) $this->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $search = trim((string) $this->input('q', ''));
        $page   = max(1, (int) $this->input('page', 1));

        $model = new Device();

        $totalDevices = $model->countAll($search);
        $totalPages   = max(1, (int) ceil($totalDevices / self::PER_PAGE));
        $currentPage  = min($page, $totalPages);

        $devices = $model->paginate(
            $currentPage,
            self::PER_PAGE,
            $sort,
            $dir,
            $search
        );

        $this->view('devices/index', compact(
            'devices',
            'totalDevices',
            'totalPages',
            'currentPage',
            'sort',
            'dir'
        ));
    }

    public function create(): void
    {
        $this->view('devices/form', [
            'deviceTypes' => (new DeviceType())->all(),
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
            'brand_name'         => $this->input('brand_name', ''),
            'model_name'         => $this->input('model_name', ''),
            'serial_number'      => $this->input('serial_number'),
            'svp_device_type_id' => $this->input('svp_device_type_id') ?: null,
            'svp_user_id'        => $this->input('svp_user_id') ?: null,
            'location_name'      => $this->input('location_name', ''),
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
            'users'       => (new User())->all(),
        ]);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        if (!(new Device())->findByDeviceId($id)) {
            Session::flash('error', 'ไม่พบอุปกรณ์ที่ต้องการแก้ไข');
            $this->redirect('/devices');
        }

        $errors = $this->validate();
        if ($errors) {
            Session::flash('errors', implode(' / ', $errors));
            $this->redirect("/devices/{$id}/edit");
        }

        (new Device())->update($id, [
            'svp_device_name'    => $this->input('svp_device_name'),
            'brand_name'         => $this->input('brand_name', ''),
            'model_name'         => $this->input('model_name', ''),
            'serial_number'      => $this->input('serial_number'),
            'svp_device_type_id' => $this->input('svp_device_type_id') ?: null,
            'svp_user_id'        => $this->input('svp_user_id') ?: null,
            'location_name'      => $this->input('location_name', ''),
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
