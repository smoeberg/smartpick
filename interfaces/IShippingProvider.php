<?php

namespace SmartPick\Interfaces;

interface IShippingProvider {
    public function createShipment($orderData);
}
