<?php
/**
 * PdfStamper — writes personalised text (a guest's name and title) onto a page of an existing PDF.
 *
 * It does not rebuild the document: it appends a standard PDF "incremental update" that adds one text annotation
 * (with its own appearance stream) per item to the chosen page, so the original template is left byte-for-byte intact
 * and any design, fonts or images in it are preserved. Understands classic cross-reference tables, cross-reference
 * streams and object streams (PDF 1.5+). Encrypted or damaged files raise a PdfStampException, so callers can fall
 * back to sending the template unchanged.
 */
class PdfStampException extends Exception {}

class PdfStamper {
    private $d;            // original bytes
    private $len;
    private $xref = [];    // objnum => [type, a, b]   type 1: offset/gen, type 2: objstm/index
    private $trailer = [];
    private $newestIsStream = false;
    private $startxref = 0;
    private $objstmCache = [];

    // Helvetica / Helvetica-Bold advance widths (1/1000 em) for ASCII 32..126
    private static $W = [
        'r' => [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584],
        'b' => [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584],
    ];

    public function __construct($bytes) {
        $this->d = $bytes; $this->len = strlen($bytes);
        if (substr($bytes, 0, 5) !== '%PDF-') throw new PdfStampException('Not a PDF file.');
        $this->readXrefChain();
        if (isset($this->trailer['Encrypt'])) throw new PdfStampException('The PDF is password-protected/encrypted.');
        if (!isset($this->trailer['Root'])) throw new PdfStampException('The PDF has no document catalog.');
    }

    /* ------------------------------------------------------------------ low-level syntax helpers */
    private function ws($s, $p) { $n = strlen($s); while ($p < $n) { $c = $s[$p]; if ($c === "\0" || $c === "\t" || $c === "\n" || $c === "\f" || $c === "\r" || $c === ' ') $p++; elseif ($c === '%') { while ($p < $n && $s[$p] !== "\n" && $s[$p] !== "\r") $p++; } else break; } return $p; }
    private static function delim($c) { return strpos("()<>[]{}/%", $c) !== false; }

    /** Returns the end position of the PDF value starting at $p (after whitespace). */
    private function valueEnd($s, $p) {
        $n = strlen($s); $p = $this->ws($s, $p); if ($p >= $n) throw new PdfStampException('Unexpected end of data.');
        $c = $s[$p];
        if ($c === '<' && ($s[$p + 1] ?? '') === '<') {
            $p += 2;
            while (true) { $p = $this->ws($s, $p); if ($p >= $n) throw new PdfStampException('Unterminated dictionary.'); if ($s[$p] === '>' && ($s[$p + 1] ?? '') === '>') return $p + 2; $p = $this->valueEnd($s, $p); }
        }
        if ($c === '[') { $p++; while (true) { $p = $this->ws($s, $p); if ($p >= $n) throw new PdfStampException('Unterminated array.'); if ($s[$p] === ']') return $p + 1; $p = $this->valueEnd($s, $p); } }
        if ($c === '(') { $depth = 0; for (; $p < $n; $p++) { $ch = $s[$p]; if ($ch === '\\') { $p++; continue; } if ($ch === '(') $depth++; elseif ($ch === ')') { $depth--; if ($depth === 0) return $p + 1; } } throw new PdfStampException('Unterminated string.'); }
        if ($c === '<') { $e = strpos($s, '>', $p); if ($e === false) throw new PdfStampException('Unterminated hex string.'); return $e + 1; }
        if ($c === '/') { $p++; while ($p < $n && !self::delim($s[$p]) && !ctype_space($s[$p]) && $s[$p] !== "\0") $p++; return $p; }
        // number, keyword or indirect reference "n g R"
        if (preg_match('/\G(\d+)\s+(\d+)\s+R(?![A-Za-z0-9])/', $s, $m, 0, $p)) return $p + strlen($m[0]);
        $q = $p; while ($q < $n && !self::delim($s[$q]) && !ctype_space($s[$q]) && $s[$q] !== "\0") $q++;
        if ($q === $p) throw new PdfStampException('Unexpected character in PDF syntax.');
        return $q;
    }

    /** Top-level key => [rawValue, start, end] of a dictionary text (which must start with <<). */
    private function parseDict($s) {
        $p = $this->ws($s, 0); if (substr($s, $p, 2) !== '<<') throw new PdfStampException('Expected a dictionary.');
        $p += 2; $out = [];
        while (true) {
            $p = $this->ws($s, $p);
            if (substr($s, $p, 2) === '>>') break;
            if (($s[$p] ?? '') !== '/') throw new PdfStampException('Malformed dictionary key.');
            $ke = $this->valueEnd($s, $p); $key = substr($s, $p + 1, $ke - $p - 1);
            $vs = $this->ws($s, $ke); $ve = $this->valueEnd($s, $vs);
            $out[$key] = [substr($s, $vs, $ve - $vs), $vs, $ve]; $p = $ve;
        }
        return $out;
    }
    private static function refNum($v) { return preg_match('/^\s*(\d+)\s+(\d+)\s+R\s*$/', $v, $m) ? (int)$m[1] : null; }

    /* ------------------------------------------------------------------ streams */
    private function decodeStream($dict, $raw) {
        $filter = isset($dict['Filter']) ? trim($dict['Filter'][0]) : '';
        $parms = isset($dict['DecodeParms']) ? $dict['DecodeParms'][0] : '';
        if ($filter === '') return $raw;
        if (!preg_match('#^(/FlateDecode|/Fl|\[\s*/FlateDecode\s*\])$#', $filter)) throw new PdfStampException('Unsupported stream filter ' . $filter);
        $out = @gzuncompress($raw);
        if ($out === false) $out = @gzinflate(substr($raw, 2));
        if ($out === false) throw new PdfStampException('Could not decompress a PDF stream.');
        if ($parms !== '') {
            $pd = $this->parseDict(trim($parms, "[] \r\n\t")); $pred = isset($pd['Predictor']) ? (int)$pd['Predictor'][0] : 1;
            if ($pred >= 10) { $cols = isset($pd['Columns']) ? (int)$pd['Columns'][0] : 1; $out = $this->pngPredictor($out, $cols); }
            elseif ($pred > 1) throw new PdfStampException('Unsupported stream predictor.');
        }
        return $out;
    }
    private function pngPredictor($data, $cols) {
        $rowLen = $cols + 1; $rows = intdiv(strlen($data), $rowLen); $out = ''; $prev = array_fill(0, $cols, 0);
        for ($r = 0; $r < $rows; $r++) {
            $ft = ord($data[$r * $rowLen]); $row = array_values(unpack('C*', substr($data, $r * $rowLen + 1, $cols))); $cur = [];
            for ($i = 0; $i < $cols; $i++) {
                $a = $i > 0 ? $cur[$i - 1] : 0; $b = $prev[$i]; $c = $i > 0 ? $prev[$i - 1] : 0; $x = $row[$i];
                switch ($ft) { case 0: $v = $x; break; case 1: $v = $x + $a; break; case 2: $v = $x + $b; break; case 3: $v = $x + intdiv($a + $b, 2); break;
                    case 4: $p = $a + $b - $c; $pa = abs($p - $a); $pb = abs($p - $b); $pc = abs($p - $c); $v = $x + ($pa <= $pb && $pa <= $pc ? $a : ($pb <= $pc ? $b : $c)); break;
                    default: throw new PdfStampException('Bad predictor row.'); }
                $cur[$i] = $v & 255;
            }
            $out .= pack('C*', ...$cur); $prev = $cur;
        }
        return $out;
    }

    /** Parses "N G obj <<dict>> [stream]" at an absolute offset. Returns [dictText, streamRaw|null, num]. */
    private function objectAt($off) {
        if (!preg_match('/\G\s*(\d+)\s+(\d+)\s+obj\b/', $this->d, $m, 0, $off)) throw new PdfStampException('Bad object at offset ' . $off . '.');
        $p = $off + strlen($m[0]); $p = $this->ws($this->d, $p);
        $ve = $this->valueEnd($this->d, $p); $val = substr($this->d, $p, $ve - $p); $stream = null;
        if (substr($val, 0, 2) === '<<') {
            $q = $this->ws($this->d, $ve);
            if (substr($this->d, $q, 6) === 'stream') {
                $q += 6; if (($this->d[$q] ?? '') === "\r") $q++; if (($this->d[$q] ?? '') === "\n") $q++;
                $dict = $this->parseDict($val); $L = null;
                if (isset($dict['Length'])) { $lv = trim($dict['Length'][0]); if (ctype_digit($lv)) $L = (int)$lv; elseif (($rn = self::refNum($lv)) !== null) { $L = (int)trim($this->objectText($rn)); } }
                if ($L !== null && substr($this->d, $q + $L, 20) !== false && preg_match('/^\s*endstream/', substr($this->d, $q + $L, 20))) $stream = substr($this->d, $q, $L);
                else { $e = strpos($this->d, 'endstream', $q); if ($e === false) throw new PdfStampException('Unterminated stream.'); $stream = rtrim(substr($this->d, $q, $e - $q), "\r\n"); }
            }
        }
        return [$val, $stream, (int)$m[1]];
    }

    /* ------------------------------------------------------------------ cross-reference data */
    private function readXrefChain() {
        $tail = substr($this->d, -2048);
        if (!preg_match_all('/startxref\s+(\d+)/', $tail, $m) ) throw new PdfStampException('No startxref found.');
        $this->startxref = (int)end($m[1]);
        $off = $this->startxref; $seen = []; $first = true;
        while ($off !== null && !isset($seen[$off])) {
            $seen[$off] = 1; $prev = null;
            $p = $this->ws($this->d, $off);
            if (substr($this->d, $p, 4) === 'xref') {
                $p += 4; $p = $this->ws($this->d, $p);
                while (preg_match('/\G(\d+)\s+(\d+)\s*/', $this->d, $m, 0, $p)) {
                    $start = (int)$m[1]; $count = (int)$m[2]; $p += strlen($m[0]);
                    for ($i = 0; $i < $count; $i++) {
                        if (!preg_match('/\G(\d{10})\s(\d{5})\s([nf])\s*/', $this->d, $e, 0, $p)) throw new PdfStampException('Damaged cross-reference table.');
                        $p += strlen($e[0]);
                        if ($e[3] === 'n' && !isset($this->xref[$start + $i])) $this->xref[$start + $i] = [1, (int)$e[1], (int)$e[2]];
                    }
                }
                $p = $this->ws($this->d, $p);
                if (substr($this->d, $p, 7) !== 'trailer') throw new PdfStampException('Missing trailer.');
                $td = $this->parseDict(substr($this->d, $p + 7, 4096 + 0)); // dict is short
                if ($first) $this->newestIsStream = false;
                foreach ($td as $k => $v) if (!isset($this->trailer[$k]) && in_array($k, ['Root', 'Size', 'Encrypt', 'ID', 'Info'], true)) $this->trailer[$k] = $v[0];
                if (isset($td['XRefStm'])) $this->readXrefStream((int)$td['XRefStm'][0], false);
                $prev = isset($td['Prev']) ? (int)$td['Prev'][0] : null;
            } else {
                [$val, $stream] = $this->objectAt($off);
                $dict = $this->parseDict($val); if ($stream === null || trim($dict['Type'][0] ?? '') !== '/XRef') throw new PdfStampException('Unrecognised cross-reference section.');
                if ($first) $this->newestIsStream = true;
                $prev = $this->readXrefStreamData($dict, $stream);
            }
            $first = false; $off = $prev;
        }
    }
    private function readXrefStream($off, $setTrailer) { [$val, $stream] = $this->objectAt($off); $this->readXrefStreamData($this->parseDict($val), $stream); }
    private function readXrefStreamData($dict, $stream) {
        foreach ($dict as $k => $v) if (!isset($this->trailer[$k]) && in_array($k, ['Root', 'Size', 'Encrypt', 'ID', 'Info'], true)) $this->trailer[$k] = $v[0];
        $data = $this->decodeStream($dict, $stream);
        $w = array_map('intval', preg_split('/\s+/', trim($dict['W'][0], '[] '))); if (count($w) !== 3) throw new PdfStampException('Bad xref /W.');
        $size = (int)$dict['Size'][0];
        $index = isset($dict['Index']) ? array_map('intval', preg_split('/\s+/', trim($dict['Index'][0], '[] '))) : [0, $size];
        $pos = 0; $rl = array_sum($w);
        for ($i = 0; $i + 1 < count($index); $i += 2) for ($j = 0; $j < $index[$i + 1]; $j++) {
            if ($pos + $rl > strlen($data)) break 2;
            $f = []; foreach ($w as $wi) { $v = 0; for ($b = 0; $b < $wi; $b++) $v = $v * 256 + ord($data[$pos++]); $f[] = $v; }
            $type = $w[0] === 0 ? 1 : $f[0]; $num = $index[$i] + $j;
            if (!isset($this->xref[$num]) && $type !== 0) $this->xref[$num] = [$type, $f[1], $f[2]];
        }
        return isset($dict['Prev']) ? (int)$dict['Prev'][0] : null;
    }

    /** Text (dictionary / array / value) of an object by number. */
    private function objectText($num) {
        if (!isset($this->xref[$num])) throw new PdfStampException("Object $num not found.");
        [$t, $a, $b] = $this->xref[$num];
        if ($t === 1) return $this->objectAt($a)[0];
        if (!isset($this->objstmCache[$a])) {
            if (!isset($this->xref[$a]) || $this->xref[$a][0] !== 1) throw new PdfStampException('Bad object stream.');
            [$val, $stream] = $this->objectAt($this->xref[$a][1]); $dict = $this->parseDict($val);
            $this->objstmCache[$a] = [$this->decodeStream($dict, $stream), (int)$dict['N'][0], (int)$dict['First'][0]];
        }
        [$data, $n, $first] = $this->objstmCache[$a];
        preg_match_all('/\d+/', substr($data, 0, $first), $m); $nums = $m[0]; $found = null;
        for ($i = 0; $i < $n; $i++) if ((int)$nums[$i * 2] === $num) { $found = $i; break; }
        if ($found === null) throw new PdfStampException("Object $num not in its object stream.");
        $start = $first + (int)$nums[$found * 2 + 1]; $p = $this->ws($data, $start); $e = $this->valueEnd($data, $p);
        return substr($data, $p, $e - $p);
    }

    /* ------------------------------------------------------------------ page lookup */
    /** Returns [pageObjNum, inheritedAttrs[MediaBox, Rotate]] for the zero-based page index. */
    private function findPage($index) {
        $root = self::refNum($this->trailer['Root']); $cat = $this->parseDict($this->objectText($root));
        $pagesRef = self::refNum($cat['Pages'][0] ?? ''); if ($pagesRef === null) throw new PdfStampException('No page tree.');
        $count = 0; $hit = null;
        $walk = function ($num, $inh, $depth) use (&$walk, &$count, &$hit, $index) {
            if ($depth > 40 || $hit) return;
            $dict = $this->parseDict($this->objectText($num));
            foreach (['MediaBox', 'Rotate', 'CropBox'] as $k) if (isset($dict[$k])) $inh[$k] = $dict[$k][0];
            if (isset($dict['Kids'])) {
                preg_match_all('/(\d+)\s+\d+\s+R/', $dict['Kids'][0], $m);
                foreach ($m[1] as $kid) { $walk((int)$kid, $inh, $depth + 1); if ($hit) return; }
            } else { if ($count === $index) $hit = [$num, $inh]; $count++; }
        };
        $walk($pagesRef, [], 0);
        if (!$hit) throw new PdfStampException('The PDF has no page ' . ($index + 1) . '.');
        return $hit;
    }

    /* ------------------------------------------------------------------ public API */
    /** Page size in points: [width, height, rotate]. */
    public function pageSize($pageIndex = 0) {
        [, $inh] = $this->findPage($pageIndex);
        $mb = isset($inh['MediaBox']) ? $inh['MediaBox'] : '[0 0 595.28 841.89]';
        if (!preg_match_all('/-?\d+(?:\.\d+)?/', $mb, $m) || count($m[0]) < 4) { $mb = '[0 0 595.28 841.89]'; preg_match_all('/-?\d+(?:\.\d+)?/', $mb, $m); }
        $v = array_map('floatval', $m[0]);
        return [abs($v[2] - $v[0]), abs($v[3] - $v[1]), (int)($inh['Rotate'] ?? 0), min($v[0], $v[2]), min($v[1], $v[3])];
    }

    /**
     * Adds text boxes to a page. Each item: text, x, y, w (mm from the top-left of the page), size (pt), align L|C|R, color '#rrggbb', bold (bool).
     * Returns the new PDF bytes.
     */
    public function stamp(array $items, $pageIndex = 0) {
        [$pageNum, $inh] = $this->findPage($pageIndex);
        [$pw, $ph, $rot, $ox, $oy] = $this->pageSize($pageIndex);
        if ($rot % 360 !== 0) throw new PdfStampException('The template page is rotated; please re-save it upright.');
        $pageDict = $this->objectText($pageNum); $pd = $this->parseDict($pageDict);
        $maxNum = max(array_keys($this->xref)); $sizeTrailer = (int)($this->trailer['Size'] ?? 0); $next = max($maxNum + 1, $sizeTrailer);
        $new = []; $annRefs = [];   // objnum => body
        $mm = 72 / 25.4;
        foreach ($items as $it) {
            $text = trim((string)$it['text']); if ($text === '') continue;
            $size = max(6, min(120, (float)$it['size'])); $bold = !empty($it['bold']); $bw = max(10, (float)$it['w']) * $mm;
            $enc = self::toWinAnsi($text);
            // shrink to fit the box
            $tw = $this->textWidth($enc, $size, $bold); while ($tw > $bw && $size > 6) { $size -= 0.5; $tw = $this->textWidth($enc, $size, $bold); }
            $h = $size * 1.35; $align = in_array($it['align'] ?? 'C', ['L', 'C', 'R'], true) ? $it['align'] : 'C';
            $tx = $align === 'L' ? 0 : ($align === 'R' ? $bw - $tw : ($bw - $tw) / 2);
            [$r, $g, $b] = self::rgb($it['color'] ?? '#000000');
            $stream = sprintf("q BT /F1 %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET Q", $size, $r, $g, $b, $tx, $size * 0.28, self::esc($enc));
            $x = $ox + (float)$it['x'] * $mm; $yTop = $oy + $ph - (float)$it['y'] * $mm; $y = $yTop - $h;
            $ap = $next++; $an = $next++;
            $new[$ap] = sprintf("<< /Type /XObject /Subtype /Form /BBox [0 0 %.2F %.2F] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /%s /Encoding /WinAnsiEncoding >> >> >> /Length %d >>\nstream\n%s\nendstream",
                $bw, $h, $bold ? 'Helvetica-Bold' : 'Helvetica', strlen($stream), $stream);
            $new[$an] = sprintf("<< /Type /Annot /Subtype /FreeText /Rect [%.2F %.2F %.2F %.2F] /F 196 /Border [0 0 0] /C [] /DA (/F1 %.2F Tf 0 g) /Contents <%s> /AP << /N %d 0 R >> >>",
                $x, $y, $x + $bw, $y + $h, $size, 'FEFF' . strtoupper(bin2hex(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8'))), $ap);
            $annRefs[] = $an;
        }
        if (!$annRefs) return $this->d;
        $refText = implode(' ', array_map(fn($n) => "$n 0 R", $annRefs));

        // update the page's /Annots
        if (isset($pd['Annots'])) {
            $av = trim($pd['Annots'][0]); $arn = self::refNum($av);
            if ($arn !== null) {   // the array is its own object: write a new version of it
                $arr = trim($this->objectText($arn)); if ($arr === '' || $arr[0] !== '[') throw new PdfStampException('Unexpected /Annots value.');
                $new[$arn] = substr($arr, 0, -1) . ' ' . $refText . ']';
            } elseif ($av !== '' && $av[0] === '[') {
                $newPage = substr($pageDict, 0, $pd['Annots'][1]) . substr($av, 0, -1) . ' ' . $refText . ']' . substr($pageDict, $pd['Annots'][2]);
            } else throw new PdfStampException('Unexpected /Annots value.');
        } else {
            $close = strrpos($pageDict, '>>'); $newPage = substr($pageDict, 0, $close) . ' /Annots [' . $refText . '] ' . substr($pageDict, $close);
        }
        if (isset($newPage)) $new[$pageNum] = $newPage;

        // ---- append the incremental update
        $out = $this->d; if (substr($out, -1) !== "\n") $out .= "\n";
        $offsets = [];
        ksort($new);
        foreach ($new as $num => $body) {
            $gen = ($this->xref[$num][0] ?? 0) === 1 ? $this->xref[$num][2] : 0;
            $offsets[$num] = [strlen($out), $gen];
            $out .= "$num $gen obj\n$body\nendobj\n";
        }
        $nums = array_keys($new); $newSize = max($next, $sizeTrailer, max($nums) + 1);
        $idPart = isset($this->trailer['ID']) ? ' /ID ' . $this->trailer['ID'] : '';
        $infoPart = isset($this->trailer['Info']) ? ' /Info ' . $this->trailer['Info'] : '';
        if (!$this->newestIsStream) {
            $xrefOff = strlen($out); $out .= "xref\n";
            // group consecutive object numbers into subsections
            $groups = []; foreach ($nums as $n) { if ($groups && end($groups)[0] + count(end($groups)[1]) === $n) $groups[count($groups) - 1][1][] = $n; else $groups[] = [$n, [$n]]; }
            foreach ($groups as [$start, $list]) { $out .= "$start " . count($list) . "\n"; foreach ($list as $n) $out .= sprintf("%010d %05d n \n", $offsets[$n][0], $offsets[$n][1]); }
            $out .= "trailer\n<< /Size $newSize /Root {$this->trailer['Root']} /Prev {$this->startxref}$infoPart$idPart >>\nstartxref\n$xrefOff\n%%EOF\n";
        } else {
            $xnum = $newSize; $newSize++; $xrefOff = strlen($out);
            $offsets[$xnum] = [$xrefOff, 0]; $allNums = $nums; $allNums[] = $xnum; sort($allNums);
            $index = []; $rows = '';
            foreach ($allNums as $n) { $index[] = "$n 1"; $rows .= chr(1) . pack('N', $offsets[$n][0]) . pack('n', $offsets[$n][1]); }
            $out .= "$xnum 0 obj\n<< /Type /XRef /Size $newSize /W [1 4 2] /Index [" . implode(' ', $index) . "] /Root {$this->trailer['Root']} /Prev {$this->startxref}$infoPart$idPart /Length " . strlen($rows) . " >>\nstream\n" . $rows . "\nendstream\nendobj\nstartxref\n$xrefOff\n%%EOF\n";
        }
        return $out;
    }

    /* ------------------------------------------------------------------ text helpers */
    private function textWidth($enc, $size, $bold) {
        $t = self::$W[$bold ? 'b' : 'r']; $w = 0;
        for ($i = 0, $n = strlen($enc); $i < $n; $i++) { $c = ord($enc[$i]); $w += ($c >= 32 && $c <= 126) ? $t[$c - 32] : 556; }
        return $w * $size / 1000;
    }
    private static function toWinAnsi($s) {
        $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s); if ($r === false) $r = preg_replace('/[^\x20-\x7E]/', '?', $s);
        return $r;
    }
    private static function esc($s) { return strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => ' ', "\n" => ' ']); }
    private static function rgb($hex) { $hex = ltrim($hex, '#'); if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) $hex = '000000'; return [hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255]; }
}
