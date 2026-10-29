<?php

/** Fill a server-owned XLSX template while retaining its drawings, merges and page layout. */
final class FormTemplateWorkbook
{
    public static function fill(string $template, array $values, array $formats = [], array $heights = [], array $layout = []): string
    {
        $temp = tempnam(sys_get_temp_dir(), 'svp-form-');
        if ($temp === false) throw new RuntimeException('สร้างไฟล์ชั่วคราวไม่ได้');
        $source = $temp . '-source.zip';
        $path = $temp . '.zip';
        try {
            if (!copy($template, $source)) throw new RuntimeException('ไม่พบแม่แบบ Excel');
            // Repack instead of mutating Excel's original ZIP compression headers.
            $original = new PharData($source);
            $zip = new PharData($path);
            foreach (new RecursiveIteratorIterator($original) as $entry) {
                $name = substr(str_replace('\\', '/', $entry->getPathname()), strlen('phar://' . str_replace('\\', '/', $source)) + 1);
                $zip[$name] = $entry->getContent();
            }
            unset($original, $entry);
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            $doc = new DOMDocument();
            $doc->loadXML($zip['xl/worksheets/sheet1.xml']->getContent(), LIBXML_NONET);
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('x', $ns);
            // Explicit, server-owned layout fixes for unmerged blank input areas in source forms.
            foreach ($layout['cells'] ?? [] as $ref => $styleRef) {
                if (!preg_match('/^([A-Z]+)([1-9]\d*)$/D', $ref, $parts)) throw new LogicException('Invalid layout cell');
                if ($xp->query('//x:c[@r="' . $ref . '"]')->length) continue;
                $sample = $xp->query('//x:c[@r="' . $styleRef . '"]')->item(0);
                $row = $xp->query('//x:row[@r="' . $parts[2] . '"]')->item(0);
                if (!$row || !$sample) throw new LogicException('Missing layout style or row');
                $cell = $doc->createElementNS($ns, 'c');
                $cell->setAttribute('r', $ref);
                $cell->setAttribute('s', $sample->getAttribute('s'));
                $before = null;
                foreach ($row->getElementsByTagNameNS($ns, 'c') as $existing) {
                    $col = preg_replace('/\d/', '', $existing->getAttribute('r'));
                    if (strlen($col) > strlen($parts[1]) || (strlen($col) === strlen($parts[1]) && strcmp($col, $parts[1]) > 0)) {
                        $before = $existing;
                        break;
                    }
                }
                $row->insertBefore($cell, $before);
            }
            $mergeCells = $xp->query('/x:worksheet/x:mergeCells')->item(0);
            if (!empty($layout['merges']) && !$mergeCells) throw new LogicException('Template has no merge container');
            foreach ($layout['merges'] ?? [] as $ref) {
                if (!preg_match('/^[A-Z]+[1-9]\d*:[A-Z]+[1-9]\d*$/D', $ref)) throw new LogicException('Invalid merge');
                if ($xp->query('//x:mergeCell[@ref="' . $ref . '"]')->length) continue;
                $merge = $doc->createElementNS($ns, 'mergeCell');
                $merge->setAttribute('ref', $ref);
                $mergeCells->appendChild($merge);
            }
            if ($mergeCells) $mergeCells->setAttribute('count', (string)$mergeCells->childElementCount);
            $styles = new DOMDocument();
            $styles->loadXML($zip['xl/styles.xml']->getContent(), LIBXML_NONET);
            $sx = new DOMXPath($styles);
            $sx->registerNamespace('x', $ns);
            $xfs = $sx->query('/x:styleSheet/x:cellXfs')->item(0);
            $numFmts = $sx->query('/x:styleSheet/x:numFmts')->item(0);
            if (!$numFmts) {
                $numFmts = $styles->createElementNS($ns, 'numFmts');
                $styles->documentElement->insertBefore($numFmts, $styles->documentElement->firstChild);
            }
            $formatIds = [];
            $nextId = 164;
            foreach ($numFmts->getElementsByTagNameNS($ns, 'numFmt') as $f) {
                $formatIds[$f->getAttribute('formatCode')] = (int)$f->getAttribute('numFmtId');
                $nextId = max($nextId, (int)$f->getAttribute('numFmtId') + 1);
            }
            $styleCache = [];
            foreach ($values as $ref => $value) {
                if (!preg_match('/^[A-Z]+[1-9]\d*$/D', $ref)) throw new LogicException('Invalid template cell');
                $cell = $xp->query('//x:c[@r="' . $ref . '"]')->item(0);
                // A missing cell indicates a changed template; fail rather than silently shifting fields.
                if (!$cell) throw new RuntimeException('แม่แบบไม่มีช่อง ' . $ref);
                $old = (int)$cell->getAttribute('s');
                $format = $formats[$ref] ?? null;
                $styleKey = $old . '|' . ($format ?? '');
                if (!isset($styleCache[$styleKey])) {
                    $xf = $xfs->getElementsByTagNameNS($ns, 'xf')->item($old)->cloneNode(true);
                    $alignment = $xf->getElementsByTagNameNS($ns, 'alignment')->item(0);
                    if (!$alignment) {
                        $alignment = $styles->createElementNS($ns, 'alignment');
                        $xf->appendChild($alignment);
                    }
                    $alignment->setAttribute('wrapText', '1');
                    $alignment->setAttribute('vertical', 'center');
                    $xf->setAttribute('applyAlignment', '1');
                    if ($format !== null) {
                        if (!isset($formatIds[$format])) {
                            $formatIds[$format] = $nextId++;
                            $f = $styles->createElementNS($ns, 'numFmt');
                            $f->setAttribute('numFmtId', (string)$formatIds[$format]);
                            $f->setAttribute('formatCode', $format);
                            $numFmts->appendChild($f);
                        }
                        $xf->setAttribute('numFmtId', (string)$formatIds[$format]);
                        $xf->setAttribute('applyNumberFormat', '1');
                    }
                    $styleCache[$styleKey] = $xfs->getElementsByTagNameNS($ns, 'xf')->length;
                    $xfs->appendChild($xf);
                }
                $cell->setAttribute('s', (string)$styleCache[$styleKey]);
                while ($cell->firstChild) $cell->removeChild($cell->firstChild);
                if (is_int($value) || is_float($value)) {
                    $cell->setAttribute('t', 'n');
                    $cell->appendChild($doc->createElementNS($ns, 'v', (string)$value));
                } else {
                    $cell->setAttribute('t', 'inlineStr');
                    $inline = $doc->createElementNS($ns, 'is');
                    $text = $doc->createElementNS($ns, 't');
                    $text->setAttribute('xml:space', 'preserve');
                    $text->appendChild($doc->createTextNode((string)$value));
                    $inline->appendChild($text);
                    $cell->appendChild($inline);
                }
            }
            foreach ($heights as $r => $height) {
                $node = $xp->query('//x:row[@r="' . (int)$r . '"]')->item(0);
                if ($node) {
                    $node->setAttribute('ht', (string)min(409, max((float)$node->getAttribute('ht'), $height)));
                    $node->setAttribute('customHeight', '1');
                }
            }
            $page = $xp->query('/x:worksheet/x:pageSetup')->item(0);
            if ($page) {
                $page->removeAttribute('scale');
                $page->setAttribute('fitToWidth', '1');
                $page->setAttribute('fitToHeight', '0');
            }
            $sheetPr = $xp->query('/x:worksheet/x:sheetPr')->item(0);
            if (!$sheetPr) {
                $sheetPr = $doc->createElementNS($ns, 'sheetPr');
                $doc->documentElement->insertBefore($sheetPr, $doc->documentElement->firstChild);
            }
            $setup = $sheetPr->getElementsByTagNameNS($ns, 'pageSetUpPr')->item(0);
            if (!$setup) {
                $setup = $doc->createElementNS($ns, 'pageSetUpPr');
                $sheetPr->appendChild($setup);
            }
            $setup->setAttribute('fitToPage', '1');
            $xfs->setAttribute('count', (string)$xfs->getElementsByTagNameNS($ns, 'xf')->length);
            $numFmts->setAttribute('count', (string)$numFmts->getElementsByTagNameNS($ns, 'numFmt')->length);
            $zip['xl/worksheets/sheet1.xml'] = $doc->saveXML();
            $zip['xl/styles.xml'] = $styles->saveXML();
            unset($zip);
            return file_get_contents($path);
        } finally {
            unset($zip, $original, $entry);
            foreach ([$path, $source, $temp] as $file) if (is_file($file)) unlink($file);
        }
    }
}
