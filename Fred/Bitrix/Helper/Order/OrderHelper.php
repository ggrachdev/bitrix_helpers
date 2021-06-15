<?

namespace Fred\Bitrix\Helper\Order;

use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Order;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;
use Bitrix\Main\Application;

class OrderHelper
{

    /**
     * Создать заказ с товаром id
     *
     * @param int $userId
     * @param $itemId
     */
    public static function createBasketFromProductId(int $userId, int $itemId)
    {
        if(self::includeModules())
        {
            $basket = Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

            // Очищаем корзину
            foreach ($basket as $basketItem) {
                $basketItem->delete();
            }

            $item = $basket->createItem('catalog', $itemId);
            $item->setFields([
                'QUANTITY' => 1,
                'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                'PRODUCT_PROVIDER_CLASS' => \CCatalogProductProvider::class,
            ]);

            $item->setField("QUANTITY", $quantity);

            $basket->save();
        }

        return false;
    }

    public static function createOrderFromBasketUser($orderData) {

        if(self::includeModules())
        {
            global $USER;

            $currencyCode = Option::get('sale', 'default_currency', 'RUB');

            $basket = Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

            $order = Order::create(SITE_ID, $USER->GetID());
            $order->setPersonTypeId(1);

            $order->setBasket($basket);

            $propertyCollection = $order->getPropertyCollection();

            // Устанавливаем оплату
            $paymentCollection = $order->getPaymentCollection();
            $payment = $paymentCollection->createItem(
                Manager::getObjectById($orderData['PAYMENT_ID']) // ID платежной системы
            );
            $payment->setField("SUM", $order->getPrice());
            $payment->setField("CURRENCY", $order->getCurrency());

            // Устанавливаем доставку
            $shipmentCollection = $order->getShipmentCollection();
            $shipment = $shipmentCollection->createItem(
                DeliveryManager::getObjectById(EmptyDeliveryService::getEmptyDeliveryServiceId())
            );

            $shipmentItemCollection = $shipment->getShipmentItemCollection();
            foreach ($basket as $basketItem)
            {
                $item = $shipmentItemCollection->createItem($basketItem);
                $item->setQuantity($basketItem->getQuantity());
            }

            if(!empty($orderData['NAME']))
                $propertyCollection->getItemByOrderPropertyId(1)->setValue($orderData['NAME']);

            if(!empty($orderData['EMAIL']))
                $propertyCollection->getItemByOrderPropertyId(2)->setValue($orderData['EMAIL']);

            if(!empty($orderData['PHONE']))
                $propertyCollection->getItemByOrderPropertyId(3)->setValue($orderData['PHONE']);

            if(!empty($orderData['USER_DESCRIPTION']))
                $order->setField('USER_DESCRIPTION', $orderData['USER_DESCRIPTION']);

            $order->doFinalAction(true);

            $order->setField('CURRENCY', $currencyCode);

            $result = $order->save();

            // \CSaleOrder::DeductOrder($order->getId(), 'Y'); 

            return $result->isSuccess() ? $order->getId() : $result->getErrors();
        }
    }

    public static function getOrderHtmlForPay($orderId) {

        if(self::includeModules())
        {
            $orderObj = Order::load($orderId);
            $paymentCollection = $orderObj->getPaymentCollection();
            $payment = $paymentCollection[0];
            $service = Manager::getObjectById($payment->getPaymentSystemId());
            $context = Application::getInstance()->getContext();
            $res = $service->initiatePay($payment, $context->getRequest(), \Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);

            return $res->getTemplate();
        }

        return false;
    }

    private static function includeModules()
    {
        if(!Loader::includeModule("catalog") || !Loader::includeModule("sale"))
        {
            throw new \Exception('Ошибка подключения модулей!');
        }

        return true;
    }
}