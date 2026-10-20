<?php
/**
 * Exports tabular data as PDF, Excel (.xlsx), Word (.docx) or CSV — no external libraries.
 *   nufa_export_send('xlsx', 'Members', ['Name', 'Email'], [['Jane', 'j@x.org'], ...], 'members');
 */

/** Minimal ZIP writer (stored, no compression) — enough for .xlsx / .docx packages. */
class NufaZip {
    private $files = [];
    public function add($name, $data) { $this->files[$name] = $data; }
    public function build() {
        $out = ''; $central = ''; $n = 0;
        $dt = getdate(); $time = (($dt['hours'] << 11) | ($dt['minutes'] << 5) | intdiv($dt['seconds'], 2)) & 0xFFFF; $date = ((($dt['year'] - 1980) << 9) | ($dt['mon'] << 5) | $dt['mday']) & 0xFFFF;
        foreach ($this->files as $name => $data) {
            $crc = crc32($data); $size = strlen($data); $off = strlen($out);
            $out .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $time, $date, $crc, $size, $size, strlen($name), 0) . $name . $data;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $time, $date, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $off) . $name;
            $n++;
        }
        return $out . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $n, $n, strlen($central), strlen($out), 0);
    }
}

function nufa_x($s) { return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
function nufa_clean_cell($v) { $v = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$v))); return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v); }
function nufa_col_letter($i) { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s; return $s; }

function nufa_build_xlsx($title, array $headers, array $rows) {
    $sheetRows = '<row r="1">'; foreach ($headers as $c => $h) $sheetRows .= '<c r="' . nufa_col_letter($c) . '1" t="inlineStr" s="1"><is><t>' . nufa_x($h) . '</t></is></c>'; $sheetRows .= '</row>';
    foreach ($rows as $r => $row) {
        $sheetRows .= '<row r="' . ($r + 2) . '">';
        foreach (array_values($row) as $c => $v) {
            $ref = nufa_col_letter($c) . ($r + 2);
            if (is_numeric($v) && !preg_match('/^0\d/', (string)$v) && strlen((string)$v) < 16 && !preg_match('/[eE+]/', (string)$v)) $sheetRows .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
            else $sheetRows .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . nufa_x($v) . '</t></is></c>';
        }
        $sheetRows .= '</row>';
    }
    $cols = ''; foreach ($headers as $c => $h) { $max = mb_strlen((string)$h); foreach (array_slice($rows, 0, 200) as $row) $max = max($max, mb_strlen((string)(array_values($row)[$c] ?? ''))); $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . min(60, max(10, $max + 2)) . '" customWidth="1"/>'; }
    $z = new NufaZip();
    $z->add('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
    $z->add('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $sheetName = preg_replace('/[\\\\\/?*\[\]:]/', ' ', mb_substr($title !== '' ? $title : 'Export', 0, 31));
    $z->add('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . nufa_x($sheetName) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $z->add('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
    $z->add('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF161616"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
    $z->add('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>' . $cols . '</cols><sheetData>' . $sheetRows . '</sheetData></worksheet>');
    return $z->build();
}

function nufa_build_docx($title, array $headers, array $rows, $subtitle = '') {
    $cell = function ($t, $head = false, $w = 0) {
        return '<w:tc><w:tcPr>' . ($w ? '<w:tcW w:w="' . $w . '" w:type="dxa"/>' : '') . ($head ? '<w:shd w:val="clear" w:color="auto" w:fill="161616"/>' : '') . '</w:tcPr><w:p><w:pPr><w:spacing w:before="40" w:after="40"/></w:pPr><w:r><w:rPr>' . ($head ? '<w:b/><w:color w:val="FFFFFF"/>' : '') . '<w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">' . nufa_x($t) . '</w:t></w:r></w:p></w:tc>';
    };
    $n = max(1, count($headers)); $w = intdiv(15400, $n);
    $tbl = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="CCCCCC"/><w:left w:val="single" w:sz="4" w:color="CCCCCC"/><w:bottom w:val="single" w:sz="4" w:color="CCCCCC"/><w:right w:val="single" w:sz="4" w:color="CCCCCC"/><w:insideH w:val="single" w:sz="4" w:color="CCCCCC"/><w:insideV w:val="single" w:sz="4" w:color="CCCCCC"/></w:tblBorders></w:tblPr><w:tr><w:trPr><w:tblHeader/></w:trPr>';
    foreach ($headers as $h) $tbl .= $cell($h, true, $w); $tbl .= '</w:tr>';
    foreach ($rows as $row) { $tbl .= '<w:tr>'; foreach (array_values($row) as $v) $tbl .= $cell($v, false, $w); $tbl .= '</w:tr>'; }
    $tbl .= '</w:tbl>';
    $body = '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>' . nufa_x($title) . '</w:t></w:r></w:p>'
          . '<w:p><w:pPr><w:spacing w:after="200"/></w:pPr><w:r><w:rPr><w:color w:val="666666"/><w:sz w:val="18"/></w:rPr><w:t>' . nufa_x($subtitle) . '</w:t></w:r></w:p>' . $tbl
          . '<w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="900" w:right="700" w:bottom="900" w:left="700" w:header="400" w:footer="400" w:gutter="0"/></w:sectPr>';
    $z = new NufaZip();
    $z->add('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $z->add('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    $z->add('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $body . '</w:body></w:document>');
    return $z->build();
}

/** Tabular PDF (A4 landscape, Helvetica) with a title, a repeating header row and page numbers. */
function nufa_build_pdf($title, array $headers, array $rows, $subtitle = '') {
    $W = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
    $enc = function ($s) { $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', (string)$s); return $r === false ? preg_replace('/[^\x20-\x7E]/', '?', (string)$s) : $r; };
    $tw = function ($s, $size) use ($W) { $w = 0; for ($i = 0, $n = strlen($s); $i < $n; $i++) { $c = ord($s[$i]); $w += ($c >= 32 && $c <= 126) ? $W[$c - 32] : 556; } return $w * $size / 1000; };
    $esc = fn($s) => strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    $pw = 841.89; $ph = 595.28; $mx = 36; $top = 46; $bot = 40; $usable = $pw - 2 * $mx; $fs = 8; $lh = 10.5; $pad = 3.5;
    $n = max(1, count($headers));
    // column widths from content (header + first 300 rows), clamped, then scaled to the page
    $want = [];
    foreach ($headers as $c => $h) { $m = $tw($enc($h), $fs + 0.5) + 2 * $pad; foreach (array_slice($rows, 0, 300) as $row) { $v = $enc(array_values($row)[$c] ?? ''); $m = max($m, min($tw($v, $fs) + 2 * $pad, 190)); } $want[$c] = max(34, $m); }
    $sum = array_sum($want); $scale = $sum > $usable ? $usable / $sum : 1; $colw = array_map(fn($w) => $w * $scale, $want);
    if ($scale === 1.0 && $sum < $usable) { $extra = ($usable - $sum) / $n; $colw = array_map(fn($w) => $w + $extra, $colw); }
    $wrap = function ($text, $width) use ($tw, $fs) {
        $words = preg_split('/\s+/', $text); $lines = []; $cur = '';
        foreach ($words as $w) {
            while ($tw($w, $fs) > $width && strlen($w) > 1) { $k = max(1, (int)(strlen($w) * $width / max(1, $tw($w, $fs)))); if ($cur !== '') { $lines[] = $cur; $cur = ''; } $lines[] = substr($w, 0, $k); $w = substr($w, $k); }
            $t = $cur === '' ? $w : $cur . ' ' . $w;
            if ($tw($t, $fs) <= $width) $cur = $t; else { if ($cur !== '') $lines[] = $cur; $cur = $w; }
        }
        if ($cur !== '' || !$lines) $lines[] = $cur;
        return array_slice($lines, 0, 8);
    };
    $pages = []; $buf = ''; $y = 0; $pageNo = 0;
    $stamp = $enc('Generated ' . date('d M Y H:i') . ($subtitle !== '' ? '  |  ' . $subtitle : ''));
    $newPage = function () use (&$buf, &$y, &$pageNo, $ph, $pw, $mx, $top, $enc, $esc, $title, $stamp, $headers, $colw, $fs, $pad) {
        $pageNo++; $buf = ''; $y = $ph - $top;
        $buf .= "0.086 0.086 0.086 rg 0 " . ($ph - 30) . " $pw 30 re f BT /F2 12 Tf 0.83 0.69 0.39 rg $mx " . ($ph - 20) . " Td (NUFA) Tj ET\n";
        $buf .= "BT /F2 10 Tf 1 1 1 rg " . ($mx + 44) . " " . ($ph - 20) . " Td (" . $esc($enc($title)) . ") Tj ET\n";
        $y = $ph - 46; $buf .= "BT /F1 7.5 Tf 0.4 0.4 0.4 rg $mx $y Td (" . $esc($stamp) . ") Tj ET\n"; $y -= 12;
        // header row
        $x = $mx; $rowH = 15; $buf .= "0.086 0.086 0.086 rg $mx " . ($y - $rowH) . " " . array_sum($colw) . " $rowH re f\n";
        foreach ($headers as $c => $h) { $txt = $enc($h); $buf .= "BT /F2 " . ($fs + 0.5) . " Tf 1 1 1 rg " . ($x + $pad) . " " . ($y - 10.5) . " Td (" . $esc($txt) . ") Tj ET\n"; $x += $colw[$c]; }
        $y -= $rowH;
    };
    $flush = function () use (&$pages, &$buf, &$pageNo, $pw, $ph, $mx, $enc, $esc) { $buf .= "BT /F1 7.5 Tf 0.4 0.4 0.4 rg " . ($pw - $mx - 30) . " 22 Td (Page " . $pageNo . ") Tj ET\n"; $pages[] = $buf; };
    $newPage();
    foreach ($rows as $ri => $row) {
        $row = array_values($row); $cells = []; $maxL = 1;
        foreach ($headers as $c => $h) { $lines = $wrap($enc($row[$c] ?? ''), $colw[$c] - 2 * $pad); $cells[$c] = $lines; $maxL = max($maxL, count($lines)); }
        $rowH = $maxL * $lh + 2 * 2.5;
        if ($y - $rowH < $bot) { $flush(); $newPage(); }
        if ($ri % 2 === 1) $buf .= "0.965 0.957 0.937 rg $mx " . ($y - $rowH) . " " . array_sum($colw) . " $rowH re f\n";
        $x = $mx;
        foreach ($headers as $c => $h) { $ly = $y - 2.5 - $fs - 0.6; foreach ($cells[$c] as $ln) { $buf .= "BT /F1 $fs Tf 0.1 0.1 0.1 rg " . round($x + $pad, 2) . " " . round($ly, 2) . " Td (" . $esc($ln) . ") Tj ET\n"; $ly -= $lh; } $x += $colw[$c]; }
        $buf .= "0.88 0.87 0.84 RG 0.4 w $mx " . ($y - $rowH) . " m " . ($mx + array_sum($colw)) . " " . ($y - $rowH) . " l S\n";
        $y -= $rowH;
    }
    if (!$rows) $buf .= "BT /F1 9 Tf 0.4 0.4 0.4 rg $mx " . ($y - 14) . " Td (No records.) Tj ET\n";
    $flush();
    // assemble objects: 1 catalog, 2 pages, 3 F1, 4 F2, then page+content pairs
    $objs = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
    $kids = []; $num = 5;
    foreach ($pages as $content) {
        $pn = $num++; $cn = $num++; $kids[] = "$pn 0 R";
        $objs[$pn] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pw $ph] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $cn 0 R >>";
        $objs[$cn] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
    }
    $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
    ksort($objs); $out = "%PDF-1.4\n"; $offs = [];
    foreach ($objs as $k => $body) { $offs[$k] = strlen($out); $out .= "$k 0 obj\n$body\nendobj\n"; }
    $x = strlen($out); $total = max(array_keys($objs)) + 1; $out .= "xref\n0 $total\n0000000000 65535 f \n";
    for ($i = 1; $i < $total; $i++) $out .= sprintf("%010d 00000 n \n", $offs[$i] ?? 0);
    return $out . "trailer\n<< /Size $total /Root 1 0 R /Info << /Title (" . $esc($enc($title)) . ") /Producer (NUFA) >> >>\nstartxref\n$x\n%%EOF\n";
}

function nufa_build_csv(array $headers, array $rows) {
    $f = fopen('php://temp', 'w+'); fwrite($f, "\xEF\xBB\xBF"); fputcsv($f, $headers); foreach ($rows as $r) fputcsv($f, array_values($r)); rewind($f); $s = stream_get_contents($f); fclose($f); return $s;
}

/** Sends the file and exits. $format: pdf | xlsx | docx | csv. */
function nufa_export_send($format, $title, array $headers, array $rows, $filename = 'export', $subtitle = '') {
    $headers = array_map('nufa_clean_cell', array_values($headers));
    $rows = array_map(fn($r) => array_map('nufa_clean_cell', array_values($r)), $rows);
    $slug = trim(preg_replace('/[^a-z0-9]+/i', '-', $filename), '-') ?: 'export';
    $name = $slug . '-' . date('Y-m-d');
    switch ($format) {
        case 'pdf':  $data = nufa_build_pdf($title, $headers, $rows, $subtitle); $mime = 'application/pdf'; $ext = 'pdf'; break;
        case 'xlsx': $data = nufa_build_xlsx($title, $headers, $rows); $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; $ext = 'xlsx'; break;
        case 'docx': $data = nufa_build_docx($title, $headers, $rows, $subtitle ?: 'Generated ' . date('d M Y H:i')); $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; $ext = 'docx'; break;
        default:     $data = nufa_build_csv($headers, $rows); $mime = 'text/csv; charset=utf-8'; $ext = 'csv';
    }
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . $mime); header('Content-Disposition: attachment; filename="' . $name . '.' . $ext . '"'); header('Content-Length: ' . strlen($data)); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
    echo $data; exit;
}

/** Export format requested in the URL (?export=pdf|xlsx|docx|csv; the old ?export=1 means CSV). '' when not exporting. */
function nufa_export_format() {
    $e = $_GET['export'] ?? ''; if ($e === '') return '';
    return in_array($e, ['pdf', 'xlsx', 'docx', 'csv'], true) ? $e : 'csv';
}

/** Small dropdown of export formats for a page whose export handler reads ?export=. $base is the page URL (with existing query). */
function nufa_export_menu($base = '', $label = 'Export') {
    $sep = strpos($base, '?') === false ? '?' : (substr($base, -1) === '?' ? '' : '&');
    $items = ['pdf' => 'PDF document', 'xlsx' => 'Excel spreadsheet', 'docx' => 'Word document', 'csv' => 'CSV file'];
    $h = '<span class="exp-menu"><button type="button" class="admin-btn admin-btn-line admin-btn-sm exp-toggle" aria-haspopup="true">⬇ ' . htmlspecialchars($label) . ' ▾</button><span class="exp-list" hidden>';
    foreach ($items as $k => $l) $h .= '<a href="' . htmlspecialchars($base . $sep . 'export=' . $k) . '">' . $l . '</a>';
    return $h . '</span></span>';
}
