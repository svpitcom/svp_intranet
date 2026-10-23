<?php
/** Fill the supplied workbook in-place, retaining its chart, drawing, styles and print layout. */
final class KpiFormExport
{
    public static function xlsx(array $d): string
    {
        $temp=tempnam(sys_get_temp_dir(),'svp-kpi-');
        if ($temp===false) throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่ได้');
        $path=$temp.'.zip'; $source=$temp.'-source.zip';
        try {
            if (!copy(BASE_PATH.'/app/Templates/kpi-action-plan.xlsx',$source)) throw new RuntimeException('ไม่พบแม่แบบ KPI');
            // Repack all entries: editing some Excel ZIPs directly with Phar preserves invalid deflate headers.
            $original=new PharData($source); $zip=new PharData($path);
            foreach (new RecursiveIteratorIterator($original) as $entry) {
                $name=substr(str_replace('\\','/',$entry->getPathname()),strlen('phar://'.str_replace('\\','/',$source))+1);
                $zip[$name]=$entry->getContent();
            }
            unset($entry,$original);
            $ns='http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $doc=new DOMDocument(); $doc->loadXML($zip['xl/worksheets/sheet1.xml']->getContent(),LIBXML_NONET);
            $xp=new DOMXPath($doc); $xp->registerNamespace('x',$ns);
            $put=static function(string $ref,$value,bool $numeric=false) use($doc,$xp,$ns): void {
                $cell=$xp->query('//x:c[@r="'.$ref.'"]')->item(0);
                if (!$cell) {
                    $row=$xp->query('//x:row[@r="'.preg_replace('/\D/','',$ref).'"]')->item(0);
                    $cell=$doc->createElementNS($ns,'c'); $cell->setAttribute('r',$ref); $row->appendChild($cell);
                }
                while ($cell->firstChild) $cell->removeChild($cell->firstChild);
                $cell->setAttribute('t',$numeric && $value!==''?'n':'inlineStr');
                if ($numeric && $value!=='') $cell->appendChild($doc->createElementNS($ns,'v',(string)(float)$value));
                else {
                    $is=$doc->createElementNS($ns,'is'); $t=$doc->createElementNS($ns,'t'); $t->setAttribute('xml:space','preserve');
                    $t->appendChild($doc->createTextNode(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',(string)$value))); $is->appendChild($t); $cell->appendChild($is);
                }
            };
            $merges=$xp->query('//x:mergeCells')->item(0);
            // Combine only blank input areas to support ordinary multi-line form answers.
            foreach (iterator_to_array($merges->childNodes) as $m) if ($m instanceof DOMElement && preg_match('/^A(?:6|1[89]|2\d|30):/',$m->getAttribute('ref'))) $merges->removeChild($m);
            foreach (['B2:C2','B3:C3','A5:C8','A10:C13','A15:C16','A18:G30'] as $range) { $m=$doc->createElementNS($ns,'mergeCell'); $m->setAttribute('ref',$range); $merges->appendChild($m); }
            $merges->setAttribute('count',(string)$merges->getElementsByTagNameNS($ns,'mergeCell')->length);
            foreach (['J1'=>$d['title'],'I1'=>$d['year'],'B2'=>$d['department'],'B3'=>$d['owner'],'H2'=>$d['effective_date'],'K2'=>$d['prepared_by'],'Q2'=>$d['approved_by'],'F2'=>$d['qms']?'☑':'☐','F3'=>$d['ems']?'☑':'☐','A5'=>$d['target'],'A10'=>$d['measurement'],'A15'=>$d['frequency'],'A18'=>$d['action_taken'],'I30'=>$d['ytd']] as $ref=>$value) $put($ref,$value,$ref==='I1');
            foreach ($d['activities'] as $i=>$a) {
                $r=$i+6; $put('D'.$r,$i+1,true); $put('E'.$r,$a['activity']); $put('H'.$r,$a['owner']); $put('I'.$r,$a['resources']);
                for ($m=1;$m<=12;$m++) $put(chr(73+$m).$r,in_array($m,$a['months'],true)?'●':'');
                $height=max(26,18*max(ceil(mb_strlen($a['activity'])/45),ceil(mb_strlen($a['owner'])/12),ceil(mb_strlen($a['resources'])/15)));
                $row=$xp->query('//x:row[@r="'.$r.'"]')->item(0); $row->setAttribute('ht',(string)$height); $row->setAttribute('customHeight','1');
            }
            foreach ($d['results'] as $i=>$r) $put('I'.($i+18),$r['result'],true);
            // Preserve existing styles, but turn wrapping on for populated input cells.
            $styles=new DOMDocument(); $styles->loadXML($zip['xl/styles.xml']->getContent(),LIBXML_NONET);
            $sx=new DOMXPath($styles); $sx->registerNamespace('x',$ns); $xfs=$sx->query('//x:cellXfs')->item(0); $cache=[];
            foreach ($xp->query('//x:c[@t="inlineStr"]') as $cell) {
                $old=(int)$cell->getAttribute('s');
                if (!isset($cache[$old])) {
                    $xf=$xfs->childNodes->item($old)->cloneNode(true); $align=$xf->getElementsByTagNameNS($ns,'alignment')->item(0);
                    if (!$align) { $align=$styles->createElementNS($ns,'alignment'); $xf->appendChild($align); }
                    $align->setAttribute('wrapText','1'); $align->setAttribute('vertical','top'); $xf->setAttribute('applyAlignment','1');
                    $cache[$old]=$xfs->childNodes->length; $xfs->appendChild($xf);
                }
                $cell->setAttribute('s',(string)$cache[$old]);
            }
            foreach ($xp->query('//x:c[@t="n"]') as $cell) {
                if (!preg_match('/^I(?:1[89]|2[0-9])$/',$cell->getAttribute('r'))) continue;
                $xf=$xfs->childNodes->item((int)$cell->getAttribute('s'))->cloneNode(true);
                $xf->setAttribute('numFmtId','0'); $xf->setAttribute('applyNumberFormat','1');
                $cell->setAttribute('s',(string)$xfs->childNodes->length); $xfs->appendChild($xf);
            }
            $xfs->setAttribute('count',(string)$xfs->childNodes->length); $zip['xl/styles.xml']=$styles->saveXML();
            $height=static function(int $r,int $minimum) use($xp): void {
                $row=$xp->query('//x:row[@r="'.$r.'"]')->item(0);
                $row->setAttribute('ht',(string)min(409,max((float)$row->getAttribute('ht'),$minimum))); $row->setAttribute('customHeight','1');
            };
            $lines=static fn(string $s,int $width)=>array_sum(array_map(static fn($line)=>max(1,ceil(mb_strlen($line)/$width)),explode("\n",$s)));
            $height(1,(int)(18*$lines($d['title'],40)));
            $height(2,(int)(18*max($lines($d['department'],20),$lines($d['prepared_by'],22),$lines($d['approved_by'],22))));
            $height(3,(int)(18*$lines($d['owner'],20)));
            foreach ([['target',8,30,60],['measurement',13,30,78],['frequency',16,30,26],['action_taken',30,100,190]] as [$key,$r,$width,$space]) $height($r,(int)(18*$lines($d[$key],$width)-$space));
            $height(30,(int)(18*$lines($d['ytd'],15)));
            // Ensure cell ordering remains valid after filling formerly absent cells.
            foreach ($xp->query('//x:row') as $row) {
                $cells=iterator_to_array($row->childNodes);
                usort($cells,static function($a,$b) { $col=static function($n) { $v=0; foreach(str_split(preg_replace('/\d/','',$n->getAttribute('r'))) as $c) $v=$v*26+ord($c)-64; return $v; }; return $col($a)<=>$col($b); });
                foreach ($cells as $c) $row->appendChild($c);
            }
            $zip['xl/worksheets/sheet1.xml']=$doc->saveXML();
            $chart=new DOMDocument(); $chart->loadXML($zip['xl/charts/chart1.xml']->getContent(),LIBXML_NONET);
            $cn='http://schemas.openxmlformats.org/drawingml/2006/chart'; $cx=new DOMXPath($chart); $cx->registerNamespace('c',$cn);
            $cacheNode=$cx->query('//c:numCache')->item(0);
            foreach (iterator_to_array($cx->query('./c:pt',$cacheNode)) as $pt) $cacheNode->removeChild($pt);
            $cx->query('./c:formatCode',$cacheNode)->item(0)->textContent='General';
            foreach ($d['results'] as $i=>$r) if ($r['result']!=='') { $pt=$chart->createElementNS($cn,'c:pt'); $pt->setAttribute('idx',(string)$i); $pt->appendChild($chart->createElementNS($cn,'c:v',(string)(float)$r['result'])); $cacheNode->appendChild($pt); }
            $cx->query('//c:dispBlanksAs')->item(0)->setAttribute('val','gap');
            foreach ($cx->query('//c:numFmt') as $fmt) $fmt->setAttribute('formatCode','General');
            $zip['xl/charts/chart1.xml']=$chart->saveXML();
            unset($zip); return file_get_contents($path);
        } finally { unset($zip,$original,$entry); if (is_file($path)) unlink($path); if (is_file($source)) unlink($source); unlink($temp); }
    }
    public static function graph(array $d): string
    {
        $values=array_map(static fn($r)=>$r['result']===''?null:(float)$r['result'],$d['results']);
        $known=array_values(array_filter($values,static fn($v)=>$v!==null));
        $low=$known?min(0,min($known)):0; $high=$known?max(0,max($known)):1; if ($high===$low) $high=$low+1;
        $svg='<svg xmlns="http://www.w3.org/2000/svg" width="480" height="230" viewBox="0 0 480 230"><rect width="480" height="230" fill="white"/>';
        for ($i=0;$i<=4;$i++) { $y=20+$i*43; $v=round($high-($high-$low)*$i/4,2); $svg.='<path d="M65 '.$y.' H465" stroke="#ddd"/><text x="3" y="'.($y+4).'" font-size="10">'.$v.'</text>'; }
        $previous=null;
        foreach ($values as $i=>$v) {
            $x=70+$i*35; $svg.='<text x="'.($x-5).'" y="215" font-size="11">'.($i+1).'</text>';
            if ($v===null) { $previous=null; continue; }
            $y=20+($high-$v)/($high-$low)*172;
            if ($previous) $svg.='<path d="M'.$previous[0].' '.$previous[1].' L'.$x.' '.$y.'" fill="none" stroke="#176b88" stroke-width="2"/>';
            $svg.='<circle cx="'.$x.'" cy="'.$y.'" r="3" fill="#176b88"/>'; $previous=[$x,$y];
        }
        return $svg.'</svg>';
    }
    public static function pdf(array $data): string
    {
        ob_start(); require BASE_PATH.'/app/Views/exports/kpi_form_pdf.php'; $html=ob_get_clean();
        $options=new \Dompdf\Options(); $options->set('isRemoteEnabled',false); $options->setChroot(BASE_PATH.'/public'); $options->set('isFontSubsettingEnabled',true);
        $fontCache=sys_get_temp_dir().'/svp-kpi-pdf-fonts';
        if (!is_dir($fontCache) && !mkdir($fontCache,0700,true) && !is_dir($fontCache)) throw new RuntimeException('สร้างโฟลเดอร์ฟอนต์ PDF ไม่สำเร็จ');
        $options->setFontDir($fontCache); $options->setFontCache($fontCache);
        $pdf=new \Dompdf\Dompdf($options); $pdf->loadHtml($html,'UTF-8'); $pdf->setPaper('A4','landscape'); $pdf->render(); return $pdf->output();
    }
}
