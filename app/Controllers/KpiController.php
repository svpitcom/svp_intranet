<?php
class KpiController extends Controller
{
    private function allowed(): bool
    {
        if (KpiPolicy::allows($this->currentUser())) return true;
        http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return false;
    }
    public function index(): void
    {
        if (!$this->allowed()) return;
        $period=$this->input('period',substr(Portal::today(),0,7));
        try { KpiPolicy::period($period); } catch (RuntimeException $e) { $period=substr(Portal::today(),0,7); Session::flash('error',$e->getMessage()); }
        $model=new Kpi();
        $this->view('kpi/index',$model->listing($this->currentUser(),$period)+['period'=>$period,'user'=>$this->currentUser()]);
    }
    public function create(): void
    {
        if (!$this->allowed()) return;
        if (!Portal::manager($this->currentUser())) { http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return; }
        $m=new Kpi();
        $data=Session::get('_kpi_form',[]); Session::remove('_kpi_form');
        $this->view('kpi/form',['departments'=>$m->departments($this->currentUser()),'people'=>$m->people($this->currentUser()),'data'=>$data]);
    }
    public function store(): void
    {
        if (!$this->allowed()) return;
        $data=[]; foreach (['period','title','department_id','user_id','source','direction','target','weight','unit'] as $key) $data[$key]=$this->input($key,'');
        try { (new Kpi())->createDefinition($this->currentUser(),$data); }
        catch (RuntimeException $e) { if ($e instanceof PDOException) throw $e; Session::flash('error',$e->getMessage()); Session::set('_kpi_form',$data); $this->redirect('/kpi/create'); return; }
        Session::flash('success','สร้าง KPI แล้ว'); $this->redirect('/kpi?period='.rawurlencode($data['period']));
    }
    public function result(string $id): void
    {
        if (!$this->allowed()) return;
        try {
            $m=new Kpi(); $row=$m->detail((int)$id,$this->currentUser());
            $m->saveResult((int)$id,$this->currentUser(),['actual'=>$this->input('actual',''),'note'=>$this->input('note',''),'version'=>$this->input('version','')]);
            Session::flash('success','บันทึกผลงาน KPI แล้ว');
        } catch (RuntimeException $e) { if ($e instanceof PDOException) throw $e; Session::flash('error',$e->getMessage()); }
        $this->redirect('/kpi?period='.rawurlencode($row['period']??substr(Portal::today(),0,7)));
    }
    public function history(string $id): void
    {
        if (!$this->allowed()) return;
        $m=new Kpi();
        try { $row=$m->detail((int)$id,$this->currentUser()); $history=$m->history((int)$id,$this->currentUser()); }
        catch (RuntimeException $e) { if ($e instanceof PDOException) throw $e; http_response_code(403); require BASE_PATH.'/app/Views/errors/403.php'; return; }
        $this->view('kpi/history',compact('row','history'));
    }
}
