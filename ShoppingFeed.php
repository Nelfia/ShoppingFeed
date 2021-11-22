<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace ShoppingFeed;

use Propel\Runtime\Connection\ConnectionInterface;
use ShoppingFeed\Model\ShoppingfeedFeedQuery;
use Symfony\Component\Finder\Finder;
use Thelia\Install\Database;
use Thelia\Model\Customer;
use Thelia\Model\CustomerQuery;
use Thelia\Model\CustomerTitleQuery;
use Thelia\Model\LangQuery;
use Thelia\Model\Order;
use Thelia\Module\AbstractPaymentModule;
use Thelia\Module\BaseModule;

class ShoppingFeed extends AbstractPaymentModule
{
    /** @var string */
    const DOMAIN_NAME = 'shoppingfeed';

    /*
     * You may now override BaseModuleInterface methods, such as:
     * install, destroy, preActivation, postActivation, preDeactivation, postDeactivation
     *
     * Have fun !
     */

    public static function getShoppingFeedCustomer()
    {
        $customer = CustomerQuery::create()
            ->filterByRef("SHOPPING_FEED")
            ->findOne();

        if (null !== $customer) {
            return $customer;
        }

        $lang = LangQuery::create()
            ->filterByByDefault(true)
            ->findOne();

        $customerTitle = CustomerTitleQuery::create()
            ->filterByByDefault(true)
            ->findOne();

        $customer = (new Customer())
            ->setLangId($lang->getId())
            ->setTitleId($customerTitle->getId())
            ->setEmail('module-shoppingfeed@thelia.net')
            ->setRef("SHOPPING_FEED");

        $customer->save();

        return $customer;
    }

    /**
     * @param ConnectionInterface|null $con
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function postActivation(ConnectionInterface $con = null)
    {
        // Once activated, create the module schema in the Thelia database.
        $database = new Database($con);

        try {
            ShoppingfeedFeedQuery::create()->findOne();
        } catch (\Exception $e) {
            $database->insertSql(null, array(
                __DIR__ . DS . 'Config' . DS . 'thelia.sql' // The module schema
            ));
        }
    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null)
    {
        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in(__DIR__ . DS . 'Config' . DS . 'update');

        $database = new Database($con);

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    public function pay(Order $order)
    {}

    public function isValidPayment()
    {}

    public function manageStockOnCreation()
    {
        return true;
    }
}
