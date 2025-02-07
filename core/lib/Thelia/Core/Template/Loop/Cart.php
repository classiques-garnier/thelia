<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Core\Template\Loop;

use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Cart as CartModel;
use Thelia\Model\CartItem as CartItemModel;
use Thelia\Model\ConfigQuery;
use Thelia\Type;

/**
 * Cart Loop.
 *
 * Class Cart
 *
 * @method string[] getOrder()
 */
class Cart extends BaseLoop implements ArraySearchLoopInterface
{
    /**
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            new Argument(
                'order',
                new Type\TypeCollection(
                    new Type\EnumListType(['normal', 'reverse'])
                ),
                'normal'
            )
        );
    }

    public function buildArray()
    {
        /** @var CartModel $cart */
        $cart = $this->getCurrentRequest()->getSession()->getSessionCart($this->getDispatcher());

        if (null === $cart) {
            return [];
        }

        $returnArray = iterator_to_array($cart->getCartItems());

        $orders = $this->getOrder();

        foreach ($orders as $order) {
            switch ($order) {
                case 'reverse':
                    $returnArray = array_reverse($returnArray, false);
                    break;
            }
        }

        return $returnArray;
    }

    /**
     * @return LoopResult
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function parseResults(LoopResult $loopResult)
    {
        $taxCountry = $this->container->get('thelia.taxEngine')->getDeliveryCountry();
        $locale = $this->getCurrentRequest()->getSession()->getLang()->getLocale();
        $checkAvailability = ConfigQuery::checkAvailableStock();
        $defaultAvailability = (int) ConfigQuery::read('default-available-stock', 100);

        /** @var CartItemModel $cartItem */
        foreach ($loopResult->getResultDataCollection() as $cartItem) {
            $product = $cartItem->getProduct(null, $locale);
            $productSaleElement = $cartItem->getProductSaleElements();

            $loopResultRow = new LoopResultRow($cartItem);

            $loopResultRow->set('ITEM_ID', $cartItem->getId());
            $loopResultRow->set('TITLE', $product->getTitle());
            $loopResultRow->set('REF', $product->getRef());
            $loopResultRow->set('QUANTITY', $cartItem->getQuantity());
            $loopResultRow->set('PRODUCT_ID', $product->getId());
            $loopResultRow->set('PRODUCT_URL', $product->getUrl($this->getCurrentRequest()->getSession()->getLang()->getLocale()));
            if (!$checkAvailability || $product->getVirtual() === 1) {
                $loopResultRow->set('STOCK', $defaultAvailability);
            } else {
                $loopResultRow->set('STOCK', $productSaleElement->getQuantity());
            }
            $loopResultRow
                ->set('PRICE', $cartItem->getPrice())
                ->set('PROMO_PRICE', $cartItem->getPromoPrice())
                ->set('TAXED_PRICE', $cartItem->getTaxedPrice($taxCountry))
                ->set('PROMO_TAXED_PRICE', $cartItem->getTaxedPromoPrice($taxCountry))
                ->set('IS_PROMO', $cartItem->getPromo() === 1 ? 1 : 0)
            ;

            $loopResultRow
                ->set('TOTAL_PRICE', $cartItem->getTotalPrice())
                ->set('TOTAL_PROMO_PRICE', $cartItem->getTotalPromoPrice())
                ->set('TOTAL_TAXED_PRICE', $cartItem->getTotalTaxedPrice($taxCountry))
                ->set('TOTAL_PROMO_TAXED_PRICE', $cartItem->getTotalTaxedPromoPrice($taxCountry))
            ;

            $loopResultRow
                ->set('REAL_PRICE', $cartItem->getRealPrice())
                ->set('REAL_TAXED_PRICE', $cartItem->getRealTaxedPrice($taxCountry))
                ->set('REAL_TOTAL_PRICE', $cartItem->getTotalRealPrice($taxCountry))
                ->set('REAL_TOTAL_TAXED_PRICE', $cartItem->getTotalRealTaxedPrice($taxCountry))
            ;

            $loopResultRow->set('PRODUCT_SALE_ELEMENTS_ID', $productSaleElement->getId());
            $loopResultRow->set('PRODUCT_SALE_ELEMENTS_REF', $productSaleElement->getRef());
            $this->addOutputFields($loopResultRow, $cartItem);
            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

    /**
     * Return the event dispatcher,.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
