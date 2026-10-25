<?php
class KpiFormDelivery extends Model
{
    protected string $table='kpi_form_deliveries';

    public function recent(int $formId): array
    {
        return $this->query('SELECT version,format,destination,file_name,web_url,sent_at FROM kpi_form_deliveries WHERE form_id=? ORDER BY id DESC LIMIT 10',[$formId])->fetchAll();
    }

    public function record(array $row,string $format,string $destination,array $file,int $actor): void
    {
        $this->insert(['form_id'=>(int)$row['id'],'version'=>(int)$row['version'],'format'=>$format,'destination'=>$destination,'item_id'=>$file['id'],'file_name'=>$file['name'],'web_url'=>$file['webUrl'],'sent_by'=>$actor,'sent_at'=>Portal::now()]);
    }
}
