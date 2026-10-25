<?php
class MachineController extends Controller
{
    protected function machines(): Machine { return new Machine(); }
    protected function pm(): MachinePm { return new MachinePm(); }
    private function allowed(bool $manage=false): bool
    {
        if ($manage?MachineAccess::manages($this->currentUser()):MachineAccess::allows($this->currentUser())) return true;
        http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return false;
    }
    private function failure(Throwable $e): string
    {
        if ($e instanceof PDOException) { error_log('Machine module database failure: '.$e->getCode()); return 'ไม่สามารถบันทึกหรืออ่านฐานข้อมูลเครื่องจักรได้ กรุณาตรวจการเชื่อมต่อและลองใหม่'; }
        return $e->getMessage();
    }
    private function unavailable(Throwable $e): void { http_response_code($e instanceof PDOException?503:404); $this->view('machines/error',['error'=>$this->failure($e)]); }
    public function index(): void
    {
        if (!$this->allowed()) return;
        try { $q=$this->input('q',''); $listing=$this->machines()->listing($this->currentUser(),$q,(int)$this->input('page',1)); $this->view('machines/index',$listing+['q'=>$q]); }
        catch(Throwable $e) { $this->unavailable($e); }
    }
    private function machineForm(?array $row,array $data,string $error=''): void
    {
        $this->view('machines/form',compact('row','data','error')+['departments'=>(new Department())->all(),'people'=>array_filter((new User())->all(),static fn($u)=>!empty($u['is_active']))]);
    }
    public function edit(string $id=''): void
    {
        if (!$this->allowed(true)) return;
        try { $row=$id!==''?$this->machines()->detail((int)$id,$this->currentUser()):null; $this->machineForm($row,$row??['is_active'=>1,'version'=>0]); }
        catch(Throwable $e) { $this->unavailable($e); }
    }
    public function save(string $id=''): void
    {
        if (!$this->allowed(true)) return;
        $row=null;
        try { if($id!=='') $row=$this->machines()->detail((int)$id,$this->currentUser()); }
        catch(Throwable $e) { $this->unavailable($e); return; }
        try { $this->machines()->save($id!==''?(int)$id:null,$_POST,$this->currentUser()); }
        catch(Throwable $e) { http_response_code(422); $this->machineForm($row,$_POST,$this->failure($e)); return; }
        Session::flash('success','บันทึกทะเบียนเครื่องจักรแล้ว'); $this->redirect('/machines');
    }
    public function plans(): void { $this->pmListing('plans'); }
    public function records(): void { $this->pmListing('records'); }
    private function pmListing(string $kind): void
    {
        if (!$this->allowed()) return;
        try { $q=$this->input('q',''); $this->view('machines/pm_index',$this->pm()->listing($this->currentUser(),$kind,$q,(int)$this->input('page',1))+compact('q','kind')); }
        catch(Throwable $e) { $this->unavailable($e); }
    }
    private function planForm(?array $row,array $data,string $error=''): void
    {
        $choices=$this->machines()->choices($this->currentUser());
        if ($row && !in_array((int)$row['machine_id'],array_map('intval',array_column($choices,'id')),true)) $choices[]=['id'=>$row['machine_id'],'code'=>$row['code'],'name'=>$row['name'].' (ปิดใช้งาน)'];
        $this->view('machines/pm_form',compact('row','data','error','choices')+['people'=>array_filter((new User())->all(),static fn($u)=>MachineAccess::allows($u))]);
    }
    public function planEdit(string $id=''): void
    {
        if (!$this->allowed(true)) return;
        try { $row=$id!==''?$this->pm()->plan((int)$id,$this->currentUser()):null; $this->planForm($row,$row??['version'=>0,'is_active'=>1,'frequency_days'=>30,'next_pm_date'=>Portal::today(),'machine_id'=>$this->input('machine_id','')]); }
        catch(Throwable $e) { $this->unavailable($e); }
    }
    public function planSave(string $id=''): void
    {
        if (!$this->allowed(true)) return;
        $row=null;
        try { if($id!=='') $row=$this->pm()->plan((int)$id,$this->currentUser()); }
        catch(Throwable $e) { $this->unavailable($e); return; }
        try { $this->pm()->savePlan($id!==''?(int)$id:null,$_POST,$this->currentUser()); }
        catch(Throwable $e) { http_response_code(422); $this->planForm($row,$_POST,$this->failure($e)); return; }
        Session::flash('success','บันทึกแผน PM เครื่องจักรแล้ว'); $this->redirect('/machine-pm');
    }
    public function recordForm(string $id): void
    {
        if (!$this->allowed()) return;
        try {
            $plan=$this->pm()->plan((int)$id,$this->currentUser());
            if (!$plan['is_active'] || !$plan['machine_active']) throw new RuntimeException('เครื่องจักรหรือแผนปิดใช้งานแล้ว');
            $this->view('machines/record_form',['plan'=>$plan,'data'=>['version'=>$plan['version'],'performed_date'=>Portal::today()],'error'=>'']);
        } catch(Throwable $e) { $this->unavailable($e); }
    }
    public function recordSave(string $id): void
    {
        if (!$this->allowed()) return;
        try { $plan=$this->pm()->plan((int)$id,$this->currentUser()); }
        catch(Throwable $e) { $this->unavailable($e); return; }
        try { $record=$this->pm()->record((int)$id,$_POST,$this->currentUser()); }
        catch(Throwable $e) { http_response_code(422); $this->view('machines/record_form',['plan'=>$plan,'data'=>$_POST,'error'=>$this->failure($e)]); return; }
        Session::flash('success','บันทึกผล PM เครื่องจักรแล้ว'); $this->redirect('/machine-pm/records/'.$record);
    }
    public function report(string $id): void
    {
        if (!$this->allowed()) return;
        try { $this->view('machines/report',['record'=>$this->pm()->report((int)$id,$this->currentUser())]); }
        catch(Throwable $e) { $this->unavailable($e); }
    }
}
