<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


/**
 * Class OnlineShop_Framework_VoucherService_Default
 */
class OnlineShop_Framework_VoucherService_Default implements OnlineShop_Framework_IVoucherService
{

    public $sysConfig;

    public function __construct($config)
    {
        $this->sysConfig = $config;
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public function checkToken($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->checkToken($code, $cart);
        }
        throw new OnlineShop_Framework_Exception_VoucherServiceException('No Token for code ' .$code . ' exists.', 3);
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public function reserveToken($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->reserveToken($code, $cart);
        }
        return false;
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @return bool
     */
    public function releaseToken($code, \OnlineShop\Framework\CartManager\ICart $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->releaseToken($code, $cart);
        }
        return false;
    }

    /**
     * @param string $code
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return bool
     */
    public function applyToken($code, \OnlineShop\Framework\CartManager\ICart $cart, Onlineshop_Framework_AbstractOrder $order)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            if ($orderToken = $tokenManager->applyToken($code, $cart, $order)) {
                $voucherTokens = $order->getVoucherTokens();
                $voucherTokens[] = $orderToken;
                $order->setVoucherTokens($voucherTokens);

                $this->releaseToken($code, $cart);
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the correct token manager and calls removeAppliedTokenFromOrder(), which cleans up the
     * token usage and the ordered token object if necessary, removes the token object from the order.
     *
     * @param \Pimcore\Model\Object\OnlineShopVoucherToken $tokenObject
     * @param OnlineShop_Framework_AbstractOrder $order
     * @return bool
     */
    public function removeAppliedTokenFromOrder(\Pimcore\Model\Object\OnlineShopVoucherToken $tokenObject, OnlineShop_Framework_AbstractOrder $order)
    {
        if ($tokenManager = $tokenObject->getVoucherSeries()->getTokenManager()) {
            $tokenManager->removeAppliedTokenFromOrder($tokenObject, $order);

            $voucherTokens = $order->getVoucherTokens();

            $newVoucherTokens = [];
            foreach($voucherTokens as $voucherToken) {
                if($voucherToken->getId() != $tokenObject->getId()) {
                    $newVoucherTokens[] = $voucherToken;
                }
            }

            $order->setVoucherTokens($newVoucherTokens);

            return true;
        }
        return false;
    }

    /**
     * @param null $seriesId
     * @return bool
     */
    public function cleanUpReservations($seriesId = null)
    {
        if (isset($seriesId)) {
            return OnlineShop_Framework_VoucherService_Reservation::cleanUpReservations($this->sysConfig->reservations->duration, $seriesId);
        } else {
            return OnlineShop_Framework_VoucherService_Reservation::cleanUpReservations($this->sysConfig->reservations->duration);
        }
    }

    /**
     * @param \Pimcore\Model\Object\OnlineShopVoucherSeries $series
     * @return bool
     */
    public function cleanUpVoucherSeries(\Pimcore\Model\Object\OnlineShopVoucherSeries $series)
    {
        return OnlineShop_Framework_VoucherService_Token_List::cleanUpAllTokens($series->getId());
    }

    /**
     * @param null|string $seriesId
     * @return bool
     */
    public function cleanUpStatistics($seriesId = null){
        if (isset($seriesId) ) {
            return OnlineShop_Framework_VoucherService_Statistic::cleanUpStatistics($this->sysConfig->statistics->duration, $seriesId);
        } else {
            return OnlineShop_Framework_VoucherService_Statistic::cleanUpStatistics($this->sysConfig->statistics->duration);
        }
    }

    /**
     * @param $code
     * @return bool|OnlineShop_Framework_VoucherService_ITokenManager
     */
    public function getTokenManager($code)
    {
        if ($token = OnlineShop_Framework_VoucherService_Token::getByCode($code)) {
            if ($series = \Pimcore\Model\Object\OnlineShopVoucherSeries::getById($token->getVoucherSeriesId())) {
                return $series->getTokenManager();
            }
        }
        return false;
    }

}