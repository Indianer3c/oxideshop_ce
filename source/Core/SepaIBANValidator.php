<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * SEPA (Single Euro Payments Area) validation class
 *
 */
class SepaIBANValidator
{
    const IBAN_ALGORITHM_MOD_VALUE = 97;

    protected $_aCodeLengths = [];

    /**
     * International bank account number validation
     *
     * An IBAN is validated by converting it into an integer and performing a basic mod-97 operation (as described in ISO 7064) on it.
     * If the IBAN is valid, the remainder equals 1.
     *
     * @param string $sIBAN code to check
     *
     * @return bool
     */
    public function isValid($sIBAN)
    {
        $blValid = false;
        $sIBAN = strtoupper(trim($sIBAN));

        if ($this->_isLengthValid($sIBAN)) {
            $blValid = $this->_isAlgorithmValid($sIBAN);
        }

        return $blValid;
    }

    /**
     * Validation of IBAN registry
     *
     * @param array $aCodeLengths
     *
     * @return bool
     */
    public function isValidCodeLengths($aCodeLengths)
    {
        $blValid = false;
        if ($this->_isNotEmptyArray($aCodeLengths)) {
            $blValid = $this->_isEachCodeLengthValid($aCodeLengths);
        }

        return $blValid;
    }

    /**
     * Set IBAN Registry
     *
     * @param array $aCodeLengths
     *
     * @return bool
     */
    public function setCodeLengths($aCodeLengths)
    {
        if ($this->isValidCodeLengths($aCodeLengths)) {
            $this->_aCodeLengths = $aCodeLengths;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Get IBAN length by country data
     *
     * @return array
     */
    public function getCodeLengths()
    {
        return $this->_aCodeLengths;
    }
    /**
     * @deprecated use self::isLengthValid instead
     */
    protected function _isLengthValid($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isLengthValid($sIBAN);
    }


    /**
     * Check if the total IBAN length is correct as per country. If not, the IBAN is invalid.
     *
     * @param string $sIBAN IBAN
     *
     * @return bool
     */
    protected function isLengthValid($sIBAN)
    {
        $iActualLength = getStr()->strlen($sIBAN);

        $iCorrectLength = $this->_getLengthForCountry($sIBAN);

        return !is_null($iCorrectLength) && $iActualLength === $iCorrectLength;
    }
    /**
     * @deprecated use self::getLengthForCountry instead
     */
    protected function _getLengthForCountry($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->getLengthForCountry($sIBAN);
    }


    /**
     * Gets length for country.
     *
     * @param string $sIBAN IBAN
     *
     * @return null
     */
    protected function getLengthForCountry($sIBAN)
    {
        $aIBANRegistry = $this->getCodeLengths();

        $sCountryCode = getStr()->substr($sIBAN, 0, 2);

        $iCorrectLength = (isset($aIBANRegistry[$sCountryCode])) ? $aIBANRegistry[$sCountryCode] : null;

        return $iCorrectLength;
    }
    /**
     * @deprecated use self::isAlgorithmValid instead
     */
    protected function _isAlgorithmValid($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isAlgorithmValid($sIBAN);
    }

    /**
     * Checks if IBAN is valid according to checksum algorithm
     *
     * @param string $sIBAN IBAN
     *
     * @return bool
     */
    protected function isAlgorithmValid($sIBAN)
    {
        $sIBAN = $this->_moveInitialCharactersToEnd($sIBAN);

        $sIBAN = $this->_replaceLettersToNumbers($sIBAN);

        return $this->_isIBANChecksumValid($sIBAN);
    }
    /**
     * @deprecated use self::moveInitialCharactersToEnd instead
     */
    protected function _moveInitialCharactersToEnd($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->moveInitialCharactersToEnd($sIBAN);
    }

    /**
     * Move the four initial characters to the end of the string.
     *
     * @param string $sIBAN IBAN
     *
     * @return string
     */
    protected function moveInitialCharactersToEnd($sIBAN)
    {
        $oStr = getStr();

        $sInitialChars = $oStr->substr($sIBAN, 0, 4);
        $sIBAN = $oStr->substr($sIBAN, 4);

        return $sIBAN . $sInitialChars;
    }
    /**
     * @deprecated use self::replaceLettersToNumbers instead
     */
    protected function _replaceLettersToNumbers($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->replaceLettersToNumbers($sIBAN);
    }

    /**
     * Replace each letter in the string with two digits, thereby expanding the string, where A = 10, B = 11, ..., Z = 35.
     *
     * @param string $sIBAN IBAN
     *
     * @return string
     */
    protected function replaceLettersToNumbers($sIBAN)
    {
        $aReplaceArray = [
            'A' => 10,
            'B' => 11,
            'C' => 12,
            'D' => 13,
            'E' => 14,
            'F' => 15,
            'G' => 16,
            'H' => 17,
            'I' => 18,
            'J' => 19,
            'K' => 20,
            'L' => 21,
            'M' => 22,
            'N' => 23,
            'O' => 24,
            'P' => 25,
            'Q' => 26,
            'R' => 27,
            'S' => 28,
            'T' => 29,
            'U' => 30,
            'V' => 31,
            'W' => 32,
            'X' => 33,
            'Y' => 34,
            'Z' => 35
        ];

        return str_replace(
            array_keys($aReplaceArray),
            $aReplaceArray,
            $sIBAN
        );
    }
    /**
     * @deprecated use self::isIBANChecksumValid instead
     */
    protected function _isIBANChecksumValid($sIBAN) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isIBANChecksumValid($sIBAN);
    }

    /**
     * Interpret the string as a decimal integer and compute the remainder of that number on division by 97.
     *
     * @param string $sIBAN IBAN
     *
     * @return bool
     */
    protected function isIBANChecksumValid($sIBAN)
    {
        return (int) bcmod($sIBAN, self::IBAN_ALGORITHM_MOD_VALUE) === 1;
    }
    /**
     * @deprecated use self::isNotEmptyArray instead
     */
    protected function _isNotEmptyArray($aCodeLengths) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isNotEmptyArray($aCodeLengths);
    }

    /**
     * Checks if Code length is non empty array
     *
     * @param array $aCodeLengths Code lengths
     *
     * @return bool
     */
    protected function isNotEmptyArray($aCodeLengths)
    {
        return is_array($aCodeLengths) && !empty($aCodeLengths);
    }
    /**
     * @deprecated use self::isEachCodeLengthValid instead
     */
    protected function _isEachCodeLengthValid($aCodeLengths) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isEachCodeLengthValid($aCodeLengths);
    }

    /**
     * Checks if each code length is valid.
     *
     * @param array $aCodeLengths Code lengths
     *
     * @return bool
     */
    protected function isEachCodeLengthValid($aCodeLengths)
    {
        $blValid = true;

        foreach ($aCodeLengths as $sCountryAbbr => $iLength) {
            if (
                !$this->_isCodeLengthKeyValid($sCountryAbbr) ||
                !$this->_isCodeLengthValueValid($iLength)
            ) {
                $blValid = false;
                break;
            }
        }

        return $blValid;
    }
    /**
     * @deprecated use self::isCodeLengthKeyValid instead
     */
    protected function _isCodeLengthKeyValid($sCountryAbbr) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isCodeLengthKeyValid($sCountryAbbr);
    }

    /**
     * Checks if country code is valid
     *
     * @param string $sCountryAbbr Country abbreviation
     *
     * @return bool
     */
    protected function isCodeLengthKeyValid($sCountryAbbr)
    {
        return (int) preg_match("/^[A-Z]{2}$/", $sCountryAbbr) !== 0;
    }
    /**
     * @deprecated use self::isCodeLengthValueValid instead
     */
    protected function _isCodeLengthValueValid($iLength) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return $this->isCodeLengthValueValid($iLength);
    }

    /**
     * Checks if value is numeric and does not contain whitespaces
     *
     * @param integer $iLength Length
     *
     * @return bool
     */
    protected function isCodeLengthValueValid($iLength)
    {
        return is_numeric($iLength) && (int) preg_match("/\./", $iLength) !== 1;
    }
}
