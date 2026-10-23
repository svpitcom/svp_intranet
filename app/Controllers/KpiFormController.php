<?php
class KpiFormController extends Controller
{
    private function allowed(): bool
    {
        if (KpiPolicy::allows($this->currentUser())) return true;
        http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return false;
    }
    public function index(): void
    {
        if (!$this->allowed()) return;
        $this->view('kpi/forms',['forms'=>(new KpiForm())->listing($this->currentUser()),'user'=>$this->currentUser()]);
    }
    public function edit(string $id=''): void
    {
        if (!$this->allowed()) return;
        try { $row=$id!==''?(new KpiForm())->detail((int)$id,$this->currentUser()):null; }
        catch (RuntimeException $e) { http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return; }
        $editable=$row?KpiPolicy::manages($this->currentUser(),(int)$row['department_id']):Portal::manager($this->currentUser());
        if (!$row && !$editable) { http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return; }
        $data=$row['data']??['year'=>(int)date('Y')];
        $data['department_id']=$row['department_id']??($this->currentUser()['svp_department_id']??'');
        $data['version']=$row['version']??0;
        $this->renderForm($row,$data,$editable);
    }
    private function renderForm(?array $row,array $data,bool $editable,?string $error=null): void
    {
        $this->view('kpi/annual_form',compact('row','data','editable','error')+['departments'=>(new Kpi())->departments($this->currentUser())]);
    }
    public function save(string $id=''): void
    {
        if (!$this->allowed()) return;
        $model=new KpiForm(); $row=null;
        // Check existing document access before validation or redisplaying any content.
        if ($id!=='') {
            try { $row=$model->detail((int)$id,$this->currentUser()); }
            catch (RuntimeException $e) { http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return; }
        }
        if (!Portal::manager($this->currentUser()) || ($row && !KpiPolicy::manages($this->currentUser(),(int)$row['department_id']))) { http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return; }
        try { $saved=$model->save($id!==''?(int)$id:null,$this->currentUser(),$_POST); }
        catch (RuntimeException $e) {
            if ($e instanceof PDOException) throw $e;
            http_response_code(422); $this->renderForm($row,$_POST,true,$e->getMessage()); return;
        }
        Session::flash('success','บันทึก KPI & Action plan แล้ว'); $this->redirect('/kpi/forms/'.$saved);
    }
    public function export(string $id): void
    {
        if (!$this->allowed()) return;
        try { $row=(new KpiForm())->detail((int)$id,$this->currentUser()); }
        catch (RuntimeException $e) { http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return; }
        $format=$this->input('format','xlsx');
        if (!in_array($format,['xlsx','pdf'],true)) { http_response_code(400); echo 'Unsupported format'; return; }
        $bytes=$format==='xlsx'?KpiFormExport::xlsx($row['data']):KpiFormExport::pdf($row['data']);
        header('Cache-Control: private, no-store');
        header('Content-Type: '.($format==='xlsx'?'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':'application/pdf'));
        header('Content-Disposition: attachment; filename="MR-F-027-01-'.$row['year'].'-'.$row['id'].'.'.$format.'"');
        header('X-Content-Type-Options: nosniff'); echo $bytes;
    }
}
