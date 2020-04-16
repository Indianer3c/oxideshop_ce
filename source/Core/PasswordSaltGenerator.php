<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * Generates Salt for the user password
 *
 * @deprecated since v6.4.0 (2019-03-15); `\OxidEsales\EshopCommunity\Internal\Domain\Authentication\Bridge\PasswordServiceBridgeInterface`
 *                                        was added as the new default for hashing passwords. Hashing passwords with
 *                                        MD5 and SHA512 is still supported in order support login with older
 *                                        password hashes. Therefor this class might not be
 *                                        compatible with the current passhword hash any more.
 */
class PasswordSaltGenerator
{
    /**
     * @var \OxidEsales\Eshop\Core\OpenSSLFunctionalityChecker
     */
    private $_openSSLFunctionalityChecker;

    /**
     * Sets dependencies.
     *
     * @param \OxidEsales\Eshop\Core\OpenSSLFunctionalityChecker $openSSLFunctionalityChecker
     */
    public function __construct(\OxidEsales\Eshop\Core\OpenSSLFunctionalityChecker $openSSLFunctionalityChecker)
    {
        $this->_openSSLFunctionalityChecker = $openSSLFunctionalityChecker;
    }

    /**
     * Generates salt. If openssl_random_pseudo_bytes function is not available,
     * than fallback to custom salt generator.
     *
     * @return string
     */
    public function generate()
    {
        if ($this->_getOpenSSLFunctionalityChecker()->isOpenSslRandomBytesGeneratorAvailable()) {
            $sSalt = bin2hex(openssl_random_pseudo_bytes(16));
        } else {
            $sSalt = $this->_customSaltGenerator();
        }

        return $sSalt;
    }
    /**
     * @deprecated use self::getOpenSSLFunctionalityChecker instead
     */
    protected function _getOpenSSLFunctionalityChecker() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return self::getOpenSSLFunctionalityChecker();
    }

    /**
     * Gets open SSL functionality checker.
     *
     * @return \OxidEsales\Eshop\Core\OpenSSLFunctionalityChecker
     */
    protected function getOpenSSLFunctionalityChecker()
    {
        return $this->_openSSLFunctionalityChecker;
    }
    /**
     * @deprecated use self::customSaltGenerator instead
     */
    protected function _customSaltGenerator() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return self::customSaltGenerator();
    }

    /**
     * Generates custom salt.
     *
     * @return string
     */
    protected function customSaltGenerator()
    {
        $sHash = '';
        $sSalt = '';
        for ($i = 0; $i < 32; $i++) {
            $sHash = hash('sha256', $sHash . mt_rand());
            $iPosition = mt_rand(0, 62);
            $sSalt .= $sHash[$iPosition];
        }

        return $sSalt;
    }
}
