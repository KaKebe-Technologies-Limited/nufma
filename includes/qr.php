<?php
/**
 * Small QR code generator (byte mode, error correction M, versions 1–9 → up to 180 characters). No libraries needed.
 *   qr_svg('https://example.org/card.php?t=abc', 220)   → inline <svg>
 */
function qr_matrix($text) {
    $data = array_values(unpack('C*', $text));
    $n = count($data);
    // [version => [ecPerBlock, [[blocks, dataPerBlock], ...], alignment positions]]
    $T = [
        1 => [10, [[1, 16]], []], 2 => [16, [[1, 28]], [6, 18]], 3 => [26, [[1, 44]], [6, 22]], 4 => [18, [[2, 32]], [6, 26]],
        5 => [24, [[2, 43]], [6, 30]], 6 => [16, [[4, 27]], [6, 34]], 7 => [18, [[4, 31]], [6, 22, 38]],
        8 => [22, [[2, 38], [2, 39]], [6, 24, 42]], 9 => [22, [[3, 36], [2, 37]], [6, 26, 46]],
    ];
    $ver = 0;
    foreach ($T as $v => [$ec, $groups]) { $cap = 0; foreach ($groups as [$b, $d]) $cap += $b * $d; if ($n <= $cap - 2) { $ver = $v; break; } }
    if (!$ver) throw new RuntimeException('QR text too long');
    [$ecPer, $groups, $align] = $T[$ver];
    $dataCw = 0; foreach ($groups as [$b, $d]) $dataCw += $b * $d;

    // --- bit stream ---
    $bits = '0100' . str_pad(decbin($n), 8, '0', STR_PAD_LEFT);
    foreach ($data as $byte) $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
    $bits .= str_repeat('0', min(4, $dataCw * 8 - strlen($bits)));
    $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);
    $cw = array_map('bindec', str_split($bits, 8));
    for ($pad = 0; count($cw) < $dataCw; $pad++) $cw[] = $pad % 2 ? 0x11 : 0xEC;

    // --- Reed-Solomon over GF(256) ---
    $exp = []; $log = []; $x = 1;
    for ($i = 0; $i < 255; $i++) { $exp[$i] = $x; $log[$x] = $i; $x <<= 1; if ($x & 0x100) $x ^= 0x11D; }
    for ($i = 255; $i < 512; $i++) $exp[$i] = $exp[$i - 255];
    $gen = [1];
    for ($i = 0; $i < $ecPer; $i++) {
        $next = array_fill(0, count($gen) + 1, 0);
        foreach ($gen as $j => $c) { $next[$j] ^= $c; $next[$j + 1] ^= $c ? $exp[$log[$c] + $i] : 0; }
        $gen = $next;
    }
    $blocksD = []; $blocksE = []; $pos = 0;
    foreach ($groups as [$b, $d]) for ($k = 0; $k < $b; $k++) {
        $blk = array_slice($cw, $pos, $d); $pos += $d;
        $rem = array_fill(0, $ecPer, 0);
        foreach ($blk as $byte) {
            $f = $byte ^ $rem[0]; array_shift($rem); $rem[] = 0;
            if ($f) for ($j = 0; $j < $ecPer; $j++) $rem[$j] ^= $exp[$log[$gen[$j + 1]] + $log[$f]];
        }
        $blocksD[] = $blk; $blocksE[] = $rem;
    }
    $final = [];
    $maxD = max(array_map('count', $blocksD));
    for ($i = 0; $i < $maxD; $i++) foreach ($blocksD as $blk) if (isset($blk[$i])) $final[] = $blk[$i];
    for ($i = 0; $i < $ecPer; $i++) foreach ($blocksE as $blk) $final[] = $blk[$i];
    $stream = ''; foreach ($final as $byte) $stream .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);

    // --- matrix ---
    $size = 17 + 4 * $ver;
    $M = array_fill(0, $size, array_fill(0, $size, null));   // null = free, 0/1 = fixed module
    $setFinder = function ($r0, $c0) use (&$M, $size) {
        for ($r = -1; $r <= 7; $r++) for ($c = -1; $c <= 7; $c++) {
            $rr = $r0 + $r; $cc = $c0 + $c; if ($rr < 0 || $cc < 0 || $rr >= $size || $cc >= $size) continue;
            $M[$rr][$cc] = ($r >= 0 && $r <= 6 && ($c === 0 || $c === 6)) || ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6)) || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4) ? 1 : 0;
        }
    };
    $setFinder(0, 0); $setFinder(0, $size - 7); $setFinder($size - 7, 0);
    for ($i = 8; $i < $size - 8; $i++) { $M[6][$i] = $M[$i][6] = ($i % 2 === 0) ? 1 : 0; }
    foreach ($align as $ar) foreach ($align as $ac) {
        if (($ar === 6 && $ac === 6) || ($ar === 6 && $ac === end($align)) || ($ac === 6 && $ar === end($align))) continue;
        for ($r = -2; $r <= 2; $r++) for ($c = -2; $c <= 2; $c++) $M[$ar + $r][$ac + $c] = (max(abs($r), abs($c)) !== 1) ? 1 : 0;
    }
    $M[$size - 8][8] = 1;   // dark module
    // reserve format / version areas
    $reserve = [];
    for ($i = 0; $i < 9; $i++) { $reserve[] = [8, $i]; $reserve[] = [$i, 8]; }
    for ($i = 0; $i < 8; $i++) { $reserve[] = [8, $size - 1 - $i]; $reserve[] = [$size - 1 - $i, 8]; }
    if ($ver >= 7) for ($i = 0; $i < 6; $i++) for ($j = 0; $j < 3; $j++) { $reserve[] = [$i, $size - 11 + $j]; $reserve[] = [$size - 11 + $j, $i]; }
    $fixed = [];
    foreach ($reserve as [$r, $c]) { $fixed[$r][$c] = true; if ($M[$r][$c] === null) $M[$r][$c] = 0; }
    for ($r = 0; $r < $size; $r++) for ($c = 0; $c < $size; $c++) if ($M[$r][$c] !== null && !isset($fixed[$r][$c])) $fixed[$r][$c] = true;
    // data placement
    $place = function ($mask) use ($M, $fixed, $size, $stream) {
        $m = $M; $i = 0; $len = strlen($stream); $up = true;
        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) $col--;
            for ($k = 0; $k < $size; $k++) {
                $r = $up ? $size - 1 - $k : $k;
                for ($d = 0; $d < 2; $d++) {
                    $c = $col - $d; if (isset($fixed[$r][$c])) continue;
                    $bit = $i < $len ? (int)$stream[$i] : 0; $i++;
                    $flip = match ($mask) { 0 => ($r + $c) % 2 === 0, 1 => $r % 2 === 0, 2 => $c % 3 === 0, 3 => ($r + $c) % 3 === 0, 4 => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0, 5 => ($r * $c) % 2 + ($r * $c) % 3 === 0, 6 => (($r * $c) % 2 + ($r * $c) % 3) % 2 === 0, default => (($r + $c) % 2 + ($r * $c) % 3) % 2 === 0 };
                    $m[$r][$c] = $bit ^ ($flip ? 1 : 0);
                }
            }
            $up = !$up;
        }
        return $m;
    };
    $bch = function ($v, $poly, $bitsLen) { $d = $v << $bitsLen; $pl = strlen(decbin($poly)); while (strlen(decbin($d)) >= $pl) $d ^= $poly << (strlen(decbin($d)) - $pl); return ($v << $bitsLen) | $d; };
    $penalty = function ($m) use ($size) {
        $p = 0;
        for ($pass = 0; $pass < 2; $pass++) {
            for ($a = 0; $a < $size; $a++) {
                $run = 1;
                for ($b = 1; $b < $size; $b++) {
                    $cur = $pass ? $m[$b][$a] : $m[$a][$b]; $prev = $pass ? $m[$b - 1][$a] : $m[$a][$b - 1];
                    if ($cur === $prev) { $run++; if ($b === $size - 1 && $run >= 5) $p += $run - 2; } else { if ($run >= 5) $p += $run - 2; $run = 1; }
                }
                $line = ''; for ($b = 0; $b < $size; $b++) $line .= $pass ? $m[$b][$a] : $m[$a][$b];
                $p += 40 * (preg_match_all('/(?=10111010000|00001011101)/', $line));
            }
        }
        for ($r = 0; $r < $size - 1; $r++) for ($c = 0; $c < $size - 1; $c++) if ($m[$r][$c] === $m[$r][$c + 1] && $m[$r][$c] === $m[$r + 1][$c] && $m[$r][$c] === $m[$r + 1][$c + 1]) $p += 3;
        $dark = 0; foreach ($m as $row) $dark += array_sum($row);
        $p += 10 * intdiv(abs($dark * 100 / ($size * $size) - 50), 5);
        return $p;
    };
    $best = null; $bestP = PHP_INT_MAX;
    for ($mask = 0; $mask < 8; $mask++) {
        $m = $place($mask);
        $fmt = $bch((0 << 3) | $mask, 0x537, 10) ^ 0x5412;   // ECC level M = 00
        for ($i = 0; $i < 15; $i++) {
            $bit = ($fmt >> $i) & 1;
            // around top-left finder
            if ($i < 6) $m[$i][8] = $bit; elseif ($i === 6) $m[7][8] = $bit; elseif ($i === 7) $m[8][8] = $bit; elseif ($i === 8) $m[8][7] = $bit; else $m[8][14 - $i] = $bit;
            // split copy
            if ($i < 8) $m[8][$size - 1 - $i] = $bit; else $m[$size - 15 + $i][8] = $bit;
        }
        if ($ver >= 7) {
            $vi = $bch($ver, 0x1F25, 12);
            for ($i = 0; $i < 18; $i++) { $bit = ($vi >> $i) & 1; $m[intdiv($i, 3)][$size - 11 + $i % 3] = $bit; $m[$size - 11 + $i % 3][intdiv($i, 3)] = $bit; }
        }
        $p = $penalty($m); if ($p < $bestP) { $bestP = $p; $best = $m; }
    }
    return $best;
}

/** Inline SVG of the QR code ($px = rendered width; quiet zone included). */
function qr_svg($text, $px = 200, $fg = '#111111', $bg = '#ffffff') {
    $m = qr_matrix($text); $n = count($m); $q = 2; $t = $n + 2 * $q;
    $path = '';
    for ($r = 0; $r < $n; $r++) { $c = 0; while ($c < $n) { if ($m[$r][$c]) { $s = $c; while ($c < $n && $m[$r][$c]) $c++; $path .= 'M' . ($s + $q) . ' ' . ($r + $q) . 'h' . ($c - $s) . 'v1h-' . ($c - $s) . 'z'; } else $c++; } }
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $t . ' ' . $t . '" width="' . $px . '" height="' . $px . '" shape-rendering="crispEdges" role="img" aria-label="QR code"><rect width="' . $t . '" height="' . $t . '" fill="' . $bg . '"/><path d="' . $path . '" fill="' . $fg . '"/></svg>';
}
