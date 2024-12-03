<?php

namespace App;
require_once 'Infrastructure/sdbh.php';

use sdbh\sdbh;

class Calculate
{
    private $dbh;

    public function __construct()
    {
        $this->dbh = new sdbh();
    }

    public function calculatePrice()
    {
        $days = $_POST['days'] ?? 0;
        $product_id = $_POST['product'] ?? 0;
        $selected_services = $_POST['services'] ?? [];
        $product = $this->dbh->make_query("SELECT * FROM a25_products WHERE ID = $product_id");

        if ($product) {
            echo json_encode([
                'days' => $days,
                'tariff' => $this->getTariff($product[0]['TARIFF'], $days),
                'servicePrice' => $this->countingServicePrice($selected_services, $days),
                'totalPrice' => $this->countingTotalPrice($product[0], $days, $selected_services),
            ], 200);
        } else {
            echo json_encode([
                "Ошибка, товар не найден!"
            ], 404);
        }
    }

    private function countingTotalPrice(array $product, int $days, $selected_services)
    {
        $price = $product['PRICE'];
        $tariff = $product['TARIFF'];
        $totalPrice = 0;

        $totalPrice += $this->countingTariffPrice($tariff, $days, $price);
        $totalPrice += $this->countingServicePrice($selected_services, $days);

        return $totalPrice;
    }

    private function getTariff($tariff, int $days)
    {
        $newPrice = 0;

        $tariffs = unserialize($tariff);
        if (is_array($tariffs)) {
            ksort($tariffs);
            foreach ($tariffs as $day_count => $tariff_price) {
                if ($days >= $day_count) {
                    $newPrice = $tariff_price;
                }
            }
        }

        return $newPrice;
    } //Получение тарифа

    private function countingTariffPrice($tariff, int $days, $price): int // Подсчет цены по тарифу
    {
        $tariffPrice = $this->getTariff($tariff, $days);

        if ($tariffPrice > 0) {
            $price = $tariffPrice;
        }

        return $price * $days;
    }

    private function countingServicePrice(array $selected_services, int $days): int // Подсчет дополнительных услуг
    {
        $total_price = 0;

        foreach ($selected_services as $service) {
            $total_price += (float)$service * $days;
        }

        return $total_price;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instance = new Calculate();
    $instance->calculatePrice();
}
