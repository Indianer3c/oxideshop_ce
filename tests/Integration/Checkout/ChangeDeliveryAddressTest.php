<?php
/**
 * #PHPHEADER_OXID_LICENSE_INFORMATION#
 */
namespace OxidEsales\EshopCommunity\Tests\Integration\Checkout;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\VatSelector;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\ShopIdCalculator;

class ChangeDeliveryAddressTest extends \OxidTestCase
{
    private const TEST_ARTICLE_ID = '1951';
    private const GERMANY_COUNTRY_ID = 'a7c40f631fc920687.20179984';
    private const AUSTRIA_COUNTRY_ID = 'a7c40f6320aeb2ec2.72885259';
    private const SWITZERLAND_COUNTRY_ID = 'a7c40f6321c6f6109.43859248';
    private const BELGIUM_COUNTRY_ID = 'a7c40f632e04633c9.47194042';

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxuser');
        $this->cleanUpTable('oxuserbaskets');
        $this->cleanUpTable('oxuserbasketitems');

        parent::tearDown();
    }

    public function testVatForBelgiumCountry(): void
    {
        $user = $this->createActiveBelgiumUser();

        // Vat will be False if country is EU and has not a valid VatID
        $vatSelector = Registry::get(VatSelector::class);
        $this->assertSame(0, (int)$vatSelector->getUserVat($user));
        $this->assertSame(0, (int)$vatSelector->getUserVat($user, true)); //no cache
    }

    /**
     * Verify that the oxVatSelector respects user country changes.
     */
    public function testVatSelectorOnActiveUserCountryChange(): void
    {
        //create active user
        $user = $this->createSwitzerlandActiveUser(); //Switzerland user

        //assert zero VAT for Switzerland
        $vatSelector = Registry::get(VatSelector::class);
        $this->assertSame(0, $vatSelector->getUserVat($user));
        $this->assertSame(0, $vatSelector->getUserVat($user, true)); //no cache

        //Change to german address
        $this->changeUserAddressToGermany();

        //verify that the active user was updated
        $user = oxNew(User::class);
        $user->loadActiveUser();
        $this->assertSame(self::GERMANY_COUNTRY_ID, $user->oxuser__oxcountryid->value);

        //verify german VAT
        $vatSelector = Registry::get('oxVatSelector');
        $this->assertFalse($vatSelector->getUserVat($user, true));
        $this->assertFalse($vatSelector->getUserVat($user));

        $this->changeUserAddressToAustria();

        //verify that the active user was updated
        $user = oxNew(User::class);
        $user->loadActiveUser();
        $this->assertSame(self::AUSTRIA_COUNTRY_ID, $user->oxuser__oxcountryid->value);

        //verify Austria VAT
        $vatSelector = Registry::get(VatSelector::class);
        $this->assertSame(0, (int)$vatSelector->getUserVat($user));
        $this->assertSame(0, (int)$vatSelector->getUserVat($user, true)); //no cache
    }

    /**
     * Test basket calculation when user country changes during checkout.
     * Test case when we explicitly set user via oxBasket::setBasketUser.
     */
    public function testBasketCalculationOnUserCountryChangeExplicitlySetBasketUser(): void
    {
        //no user logged in atm, create a basket
        $basket = oxNew(Basket::class);
        $basket->addToBasket(self::TEST_ARTICLE_ID, 1); //14 EUR
        $this->getSession()->setBasket($basket);

        //create user, as soon at it is set in session, it is available for basket as well
        $user = $this->createSwitzerlandActiveUser(); //Switzerland user
        $basket->setBasketUser($user);

        //verify basket calculation results
        $basket->calculateBasket(true);
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(11.76, $basket->getBruttoSum()); //no VAT for Switzerland

        //Change to german address
        $this->changeUserAddressToGermany();

        //verify that the basket user is up to date
        $basket = $this->getSession()->getBasket();
        $this->assertSame('Hahnentritt', $basket->getUser()->oxuser__oxlname->value);
        $this->assertSame('Hahnentritt', $basket->getBasketUser()->oxuser__oxlname->value);
        $basket->calculateBasket(true); //basket calculation triggers basket item user update

        //check basket calculation results, should now be with VAT due to german delivery address
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(14.0, $basket->getBruttoSum());
    }

    /**
     * Test basket calculation when user country changes during checkout.
     */
    public function testBasketCalculationOnUserCountryChange(): void
    {
        //no user logged in atm, create a basket
        $basket = oxNew(Basket::class);
        $basket->addToBasket(self::TEST_ARTICLE_ID, 1); //14 EUR
        $this->getSession()->setBasket($basket);

        //create user, as soon at it is set in session, it is available for basket as well
        $this->createSwitzerlandActiveUser(); //Switzerland user

        //verify basket calculation results
        $basket->calculateBasket(true);
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(11.76, $basket->getBruttoSum()); //no VAT for Switzerland

        //Change to german address
        $this->changeUserAddressToGermany();

        //verify that the basket user is up to date
        $basket = $this->getSession()->getBasket();
        $basket->calculateBasket(true); //basket calculation triggers basket item user update

        //check basket calculation results, should now be with VAT due to german delivery address
        $this->assertSame(11.76, $basket->getNettoSum());
        $this->assertSame(14.0, $basket->getBruttoSum());
    }

    /**
     * Insert test user, set to session
     *
     * @return User
     */
    private function createSwitzerlandActiveUser(): User
    {
        $sTestUserId = substr_replace(Registry::getUtilsObject()->generateUId(), '_', 0, 1);

        $user = oxNew(User::class);
        $user->setId($sTestUserId);

        $user->oxuser__oxactive = new Field('1');
        $user->oxuser__oxrights = new Field('user');
        $user->oxuser__oxshopid = new Field(ShopIdCalculator::BASE_SHOP_ID);
        $user->oxuser__oxusername = new Field('testuser@oxideshop.dev');
        $user->oxuser__oxpassword = new Field(
            'c630e7f6dd47f9ad60ece4492468149bfed3da3429940181464baae99941d0ffa5562' .
                                                'aaecd01eab71c4d886e5467c5fc4dd24a45819e125501f030f61b624d7d'
        ); //password is asdfasdf
        $user->oxuser__oxpasssalt = new Field('3ddda7c412dbd57325210968cd31ba86');
        $user->oxuser__oxcustnr = new Field('667');
        $user->oxuser__oxfname = new Field('Erna');
        $user->oxuser__oxlname = new Field('Helvetia');
        $user->oxuser__oxstreet = new Field('Dorfstrasse');
        $user->oxuser__oxstreetnr = new Field('117');
        $user->oxuser__oxcity = new Field('Oberbuchsiten');
        $user->oxuser__oxcountryid = new Field(self::SWITZERLAND_COUNTRY_ID);
        $user->oxuser__oxzip = new Field('4625');
        $user->oxuser__oxsal = new Field('MRS');
        $user->oxuser__oxactive = new Field('1');
        $user->oxuser__oxboni = new Field('1000');
        $user->oxuser__oxcreate = new Field('2015-05-20 22:10:51');
        $user->oxuser__oxregister = new Field('2015-05-20 22:10:51');
        $user->oxuser__oxboni = new Field('1000');

        $user->save();

        $this->getSession()->setVariable('usr', $user->getId());

        return $user;
    }

    /**
     * @return User
     */
    private function createActiveBelgiumUser(): User
    {
        $sTestUserId = substr_replace(Registry::getUtilsObject()->generateUId(), '_', 0, 1);

        $user = oxNew(User::class);
        $user->setId($sTestUserId);

        $user->oxuser__oxactive = new Field('1');
        $user->oxuser__oxrights = new Field('user');
        $user->oxuser__oxshopid = new Field(ShopIdCalculator::BASE_SHOP_ID);
        $user->oxuser__oxusername = new Field('testuser@oxideshop.dev');
        $user->oxuser__oxpassword = new Field(
            'c630e7f6dd47f9ad60ece4492468149bfed3da3429940181464baae99941d0ffa5562' .
            'aaecd01eab71c4d886e5467c5fc4dd24a45819e125501f030f61b624d7d'
        ); //password is asdfasdf
        $user->oxuser__oxpasssalt = new Field('3ddda7c412dbd57325210968cd31ba86');
        $user->oxuser__oxcustnr = new Field('667');
        $user->oxuser__oxfname = new Field('Erna');
        $user->oxuser__oxlname = new Field('Helvetia');
        $user->oxuser__oxstreet = new Field('Dorfstrasse');
        $user->oxuser__oxstreetnr = new Field('117');
        $user->oxuser__oxcity = new Field('Oberbuchsiten');
        $user->oxuser__oxcountryid = new Field(self::BELGIUM_COUNTRY_ID);
        $user->oxuser__oxzip = new Field('4625');
        $user->oxuser__oxsal = new Field('MRS');
        $user->oxuser__oxactive = new Field('1');
        $user->oxuser__oxcreate = new Field('2015-05-20 22:10:51');
        $user->oxuser__oxregister = new Field('2015-05-20 22:10:51');
        $user->oxuser__oxboni = new Field('1000');
        $user->oxuser__oxustid = new  Field("BE0410521222");

        $user->save();

        $this->getSession()->setVariable('usr', $user->getId());

        return $user;
    }

    private function changeUserAddressToGermany(): void
    {
        $this->setRequestParameter('invadr', [
            'oxuser__oxfname'     => 'Erna',
            'oxuser__oxlname'     => 'Hahnentritt',
            'oxuser__oxstreetnr'  => '117',
            'oxuser__oxstreet'    => 'Landstrasse',
            'oxuser__oxzip'       => '22769',
            'oxuser__oxcity'      => 'Hamburg',
            'oxuser__oxcountryid' => self::GERMANY_COUNTRY_ID
        ]);
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());

        $userComponent = oxNew('oxcmp_user');
        $this->assertSame('payment', $userComponent->changeUser());
    }

    private function changeUserAddressToAustria(): void
    {
        $this->setRequestParameter('invadr', [
            'oxuser__oxfname'     => 'Erna',
            'oxuser__oxlname'     => 'Hahnentritt',
            'oxuser__oxstreetnr'  => '117',
            'oxuser__oxstreet'    => 'Landstrasse',
            'oxuser__oxzip'       => '3741',
            'oxuser__oxcity'      => 'PULKAU',
            'oxuser__oxcountryid' => self::AUSTRIA_COUNTRY_ID
        ]);
        $this->setRequestParameter('stoken', $this->getSession()->getSessionChallengeToken());

        $userComponent = oxNew('oxcmp_user');
        $this->assertSame('payment', $userComponent->changeUser());
    }
}
