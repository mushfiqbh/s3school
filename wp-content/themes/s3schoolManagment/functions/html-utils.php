<?php
/**
 * Shared HTML helper utilities for admin printable documents.
 */

if (!function_exists('formatBirthDate')) {
    function formatBirthDate($birthDate): string
    {
        $timestamp = strtotime($birthDate);
        if ($timestamp !== false) {
            $day = (int) date('j', $timestamp);
            $month = date('F', $timestamp);
            $year = (int) date('Y', $timestamp);

            $ordinals = [
                1 => 'st',
                2 => 'nd',
                3 => 'rd'
            ];
            $daySuffix = $ordinals[$day % 10] ?? 'th';
            if (in_array($day % 100, [11, 12, 13], true)) {
                $daySuffix = 'th';
            }

            $ones = [
                0 => 'Zero',
                1 => 'One',
                2 => 'Two',
                3 => 'Three',
                4 => 'Four',
                5 => 'Five',
                6 => 'Six',
                7 => 'Seven',
                8 => 'Eight',
                9 => 'Nine',
                10 => 'Ten',
                11 => 'Eleven',
                12 => 'Twelve',
                13 => 'Thirteen',
                14 => 'Fourteen',
                15 => 'Fifteen',
                16 => 'Sixteen',
                17 => 'Seventeen',
                18 => 'Eighteen',
                19 => 'Nineteen'
            ];

            $tens = [
                20 => 'Twenty',
                30 => 'Thirty',
                40 => 'Forty',
                50 => 'Fifty',
                60 => 'Sixty',
                70 => 'Seventy',
                80 => 'Eighty',
                90 => 'Ninety'
            ];

            $yearWords = [];

            $thousands = intdiv($year, 1000);
            $remainder = $year % 1000;

            if ($thousands > 0) {
                $yearWords[] = $ones[$thousands] . ' Thousand';
            }

            $hundreds = intdiv($remainder, 100);
            $remainder %= 100;

            if ($hundreds > 0) {
                $yearWords[] = $ones[$hundreds] . ' Hundred';
            }

            if ($remainder > 0) {
                if ($remainder < 20) {
                    $yearWords[] = $ones[$remainder];
                } else {
                    $tenPart = intdiv($remainder, 10) * 10;
                    $onePart = $remainder % 10;
                    $segment = $tens[$tenPart];
                    if ($onePart > 0) {
                        $segment .= ' ' . $ones[$onePart];
                    }
                    $yearWords[] = $segment;
                }
            }

            if (empty($yearWords)) {
                $yearWords[] = $ones[0];
            }

            return sprintf('%d%s %s %s', $day, $daySuffix, $month, implode(' ', $yearWords));
        }

        return '';
    }
}
