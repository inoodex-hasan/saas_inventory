<?php

if (!function_exists('numberToWords')) {
    function numberToWords($number): string
    {
        $ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine"];
        $tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
        $teens = ["Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];

        if ($number == 0) {
            return "Zero";
        }

        $words = "";
        $number = (float) $number;

        // Handle crores (10,000,000)
        if ($number >= 10000000) {
            $crores = floor($number / 10000000);
            $words .= numberToWords($crores) . " Crore ";
            $number %= 10000000;
        }

        // Handle lakhs (100,000)
        if ($number >= 100000) {
            $lakhs = floor($number / 100000);
            $words .= numberToWords($lakhs) . " Lakh ";
            $number %= 100000;
        }

        // Handle thousands
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            $words .= numberToWords($thousands) . " Thousand ";
            $number %= 1000;
        }

        // Handle hundreds
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            $words .= $ones[$hundreds] . " Hundred ";
            $number %= 100;
        }

        // Handle tens and ones
        if ($number >= 20) {
            $tensDigit = floor($number / 10);
            $words .= $tens[$tensDigit] . " ";
            $number %= 10;
        } elseif ($number >= 10) {
            $words .= $teens[$number - 10] . " ";
            $number = 0;
        }

        if ($number > 0) {
            $words .= $ones[(int) $number] . " ";
        }

        return trim($words);
    }
}

