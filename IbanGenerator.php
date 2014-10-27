<?php

namespace Iban;

/**
 * Class IbanGenerator
 *
 * This class is responsible for generating valid IBAN numbers.
 *
 * @author Franklin Hink
 * @date 25 october 2014
 * @package Iban
 * @version 1.0
 */
class IbanGenerator
{
    /**
     * Generates a random valid IBAN.
     *
     * @param string $bankCode
     * @return string IBAN
     */
    public function getIbanNumber($bankCode = "INGB")
    {
        $countryCode = "NL";
        $accountNumber = $this->getBankAccountNumber();
        $checksum = $this->getChecksum($countryCode, $bankCode, $accountNumber);
        return $countryCode . $checksum . $bankCode . $accountNumber;
    }

    /**
     * Generates a valid bank account number of 10 digits.
     * @return number|string
     */
    public function getBankAccountNumber()
    {
        // generate a random number, this will be the account number
        $accountNumber = $this->prependNumber(rand(1, 100000000) . '0');

        // because it is a random number, it may be a valid account number
        $remains = $this->checkDigit($accountNumber);
        if ($remains === 0) {
            return $accountNumber;
        }

        // number is invalid, increase so it is valid.
        return $this->recalculateToValidOutcome($accountNumber, $remains);
    }

    /**
     * Prepend a number with '0'.
     * @param string $number
     * @param int $length
     * @return string
     */
    private function prependNumber($number, $length = 10)
    {
        return str_pad($number, $length, "0", STR_PAD_LEFT);
    }

    /**
     * Checks if the account number passes the 'elfproef' check. If this function returns 0, the accountNumber is valid.
     *
     * @link http://nl.wikipedia.org/wiki/Elfproef
     *
     * @param string $accountNumber
     * @return int The result of account number % 11
     */
    public function checkDigit($accountNumber)
    {
        $numbers = str_split($accountNumber);
        array_walk(
            $numbers,
            function (&$no, $key) {
                $no = $no * (10 - $key);
            }
        );
        return array_sum($numbers) % 11;
    }

    /**
     * Adds a number based on the remains so the account number will pass the digit check.
     * @param string $accountNumber
     * @param int $remains
     * @return string The new account number
     */
    private function recalculateToValidOutcome($accountNumber, $remains)
    {
        if ($remains == 10) {
            $accountNumber += 1;
        } elseif ($remains == 1 && $accountNumber{8} < 9) {
            $accountNumber += 18;
        } elseif ($remains == 1 && $accountNumber{8} == 9) {
            $accountNumber -= 9;
        } else {
            $accountNumber += 11 - $remains;
        }
        return $this->prependNumber($accountNumber);
    }

    /**
     * Calculates the checksum of an IBAN number.
     * @param string $countryCode
     * @param string $bankCode
     * @param string $accountNumber
     * @return string
     */
    private function getChecksum($countryCode, $bankCode, $accountNumber)
    {
        $iban = $this->convertStringToNumber($bankCode . $accountNumber . $countryCode) . '00';
        $checksum = 98 - $this->bcmod($iban, 97);
        return $this->prependNumber($checksum, 2);
    }

    /**
     * Converts all characters in $string to numbers, A=10, Z=35.
     * @param string $string
     * @return string
     */
    private function convertStringToNumber($string)
    {
        return implode("", array_map(array($this, "base26to10"), str_split($string)));
    }

    /**
     * bcmod method that will also work without the BCMath library.
     *
     * @link http://php.net/manual/en/function.bcmod.php
     *
     * my_bcmod - get modulus (substitute for bcmod)
     * string my_bcmod ( string left_operand, int modulus )
     * left_operand can be really big, but be carefull with modulus :(
     * by Andrius Baranauskas and Laurynas Butkus :) Vilnius, Lithuania
     */
    private function bcmod($x, $y)
    {
        if (function_exists('bcmod')) {
            return bcmod($x, $y);
        }

        // how many numbers to take at once? carefull not to exceed (int)
        $take = 5;
        $mod = '';

        do {
            $a = (int)$mod . substr($x, 0, $take);
            $x = substr($x, $take);
            $mod = $a % $y;
        } while (strlen($x));

        return (int)$mod;
    }

    /**
     * Callback method, converts base 26 to base 10.
     * @param string $char
     * @return string
     */
    private function base26to10($char)
    {
        return base_convert($char, 26, 10);
    }
}
