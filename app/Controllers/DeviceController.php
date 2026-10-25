<?php

class DeviceController extends Controller
{
    public function index(): void
    {
        if ($this->input('group','')==='machine') { $this->machines(); return; }
        $this->listing(Device::groupFilter($this->input('group', '')));
    }
    public function machines(): void
    {
        (new MachineController())->index();
    }
    public function itDevices(): void
    {
        $this->listing('it');
    }
    private function listing(string $group): void
    {
        if (($group === 'machine' && !MachineAccess::allows($this->currentUser()))
            || ($group === 'it' && !ItDeviceAccess::allows($this->currentUser()))
        ) {
            http_response_code(403);
            require BASE_PATH . '/app/Views/errors/403.php';
            return;
        }
        $perPage = 10;
        $page = max(1, (int) $this->input('page', 1));
        $sort = $this->input('sort', 'svp_device_id');
        $dir  = $this->input('dir', 'asc');

        $deviceModel = new Device();
        $search = Search::term();
        $totalDevices = $deviceModel->countAll($search, $group);
        $totalPages = max(1, (int) ceil($totalDevices / $perPage));
        $page = min($page, $totalPages); // กันเผลอเข้าเลขหน้าที่เกินจำนวนจริง

        $devices = $deviceModel->paginate($page, $perPage, $sort, $dir, $search, $group);

        $this->view('devices/index', [
            'devices'      => $devices,
            'group'        => $group,
            'totalDevices' => $totalDevices,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
            'sort'         => $sort,
            'dir'          => $dir,
        ]);
    }

    public function create(): void
    {
        if ($this->input('group','')==='machine') { $this->redirect('/machines/create'); return; }
        $this->view('devices/form', [
            'device'      => null,
            'defaultGroup' => Device::groupFilter($this->input('group', '')) ?: 'unclassified',
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
            $this->redirect('/devices/create?group=' . rawurlencode(Device::groupFilter($this->input('asset_group', ''))));
        }

        (new Device())->insert([
            'asset_group'        => $this->input('asset_group', 'unclassified'),
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
        $this->redirect(Device::listPath($this->input('asset_group', 'unclassified')));
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
            'asset_group'        => $this->input('asset_group', 'unclassified'),
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
        $this->redirect(Device::listPath($this->input('asset_group', 'unclassified')));
    }

    public function destroy(string $id): void
    {
        $device = (new Device())->findByDeviceId((int)$id);
        (new Device())->delete((int) $id);
        Session::flash('success', 'ลบอุปกรณ์เรียบร้อยแล้ว');
        $this->redirect(Device::listPath($device['asset_group'] ?? ''));
    }

    private function validate(): array
    {
        $errors = [];
        if ($this->input('asset_group')==='machine') $errors[]='กรุณาจัดการเครื่องจักรจากเมนูเครื่องจักร ซึ่งแยกฐานข้อมูลแล้ว';
        if (!isset(Device::GROUPS[$this->input('asset_group', 'unclassified')])) {
            $errors[] = 'กรุณาเลือกกลุ่มอุปกรณ์ที่ถูกต้อง';
        }

        if (!$this->input('svp_device_name')) {
            $errors[] = 'กรุณากรอกชื่ออุปกรณ์';
        }
        if (!$this->input('serial_number')) {
            $errors[] = 'กรุณากรอกหมายเลข Serial Number';
        }

        return $errors;
    }
}
