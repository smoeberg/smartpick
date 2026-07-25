<?php
interface IShippingProvider {
    public function createShipment($orderData);
}
