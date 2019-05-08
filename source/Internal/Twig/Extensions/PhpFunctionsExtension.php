<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class PhpFunctionsExtension
 *
 * @package OxidEsales\EshopCommunity\Internal\Twig\Extensions
 * @author  Jędrzej Skoczek
 * @deprecated
 */
class PhpFunctionsExtension extends AbstractExtension
{

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('count', 'count', ['deprecated' => true, 'alternative' => 'length']),
            new TwigFunction('empty', 'empty', ['deprecated' => true, 'alternative' => 'length']),
            new TwigFunction('isset', [$this, 'twigIsset', ['deprecated' => true, 'alternative' => 'is defined']])
        ];
    }

    /**
     * @param null $value
     *
     * @return bool
     */
    public function twigIsset($value = null): bool
    {
        return isset($value);
    }
}
