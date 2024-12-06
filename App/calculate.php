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
            $totalPrice = $this->countingTotalPrice($product[0], $days, $selected_services);
            $currencies = ['CNY']; //Валюты для конвертации
            echo json_encode([
                'days' => $days,
                'tariff' => $this->getTariff($product[0]['TARIFF'], $days),
                'servicePrice' => $this->countingServicePrice($selected_services, $days),
                'totalPrice' => $totalPrice,
                'convertPrices' => $this->convertCurrency($totalPrice, $currencies),
            ], 200);
        } else {
            echo json_encode([
                "Ошибка, товар не найден!"
            ], 404);
        }
    }

    private function convertCurrency(int $price, array $currencies): array //Конвертация общей стоимости в другие валюты
    {
        $urlCurrency = 'https://www.cbr-xml-daily.ru/daily_json.js';
        $jsonData = file_get_contents($urlCurrency);
        $result = [];

        if ($jsonData === false) {
            die("Ошибка получения курса валют");
        }

        $data = json_decode($jsonData, true);

        foreach ($currencies as $currency) {
            $priceCurrency = $data['Valute'][$currency]['Value'];
            $result[$currency] = round($price / $priceCurrency, 2);
        }

        return $result;
    }

    private function countingTotalPrice(array $product, int $days, $selected_services):int // Подсчет все суммы
    {
        $price = $product['PRICE'];
        $tariff = $product['TARIFF'];
        $totalPrice = 0;

        $totalPrice += $this->countingTariffPrice($tariff, $days, $price);
        $totalPrice += $this->countingServicePrice($selected_services, $days);

        return $totalPrice;
    }

    private function getTariff($tariff, int $days) // Получаем цену по тарифу
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
    }

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
