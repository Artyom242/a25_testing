<?php

namespace App\Application;
require_once __DIR__ . '/../Domain/Users/UserEntity.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Infrastructure/sdbh.php';

use App\Domain\Users\UserEntity;
use sdbh\sdbh;

class AdminService
{

    /** @var UserEntity */
    public $user;

    public function __construct()
    {
        $this->user = new UserEntity();
    }

    public function addNewProduct()
    {
        if (!$this->user->isAdmin) return;
    }

    public function searchProduct()
    {
        $text = $_POST['text'];
        $db = new sdbh();
        $searchData = $db->make_query("SELECT TARIFF, PRICE FROM a25_products where NAME LIKE '%$text%' ");

        foreach ($searchData as &$elem) {
            $elem['TARIFF'] = unserialize($elem['TARIFF']);
        }

        header('Content-Type: application/json');
        echo json_encode($searchData);
    }

    public function getCountProducts() //Количество продктов
    {
        $db = new sdbh();
        $countData = $db->count_rows('a25_products', 'ID');
//        $countData = $db->make_query("SELECT count(id) products FROM a25_products");
        return $countData[0][0];
    }

    public function getNotTariff() //Получает продукты без тарифа
    {
        $db = new sdbh();
        return $db->make_query("SELECT * FROM a25_products where TARIFF IS NULL");
    }

    public function getCheapOrExpensiveService($search) //Самая дорогая и дешёвая дополнительная услуга
    {
        $db = new sdbh();
        $servicesData = $db->make_query("SELECT set_value services FROM `a25_settings`");
        $services = unserialize($servicesData[0]['services']);

        if (!$services) {
            die("Ошибка получения услуг");
        }

        $resService = [
            'name' => key($services),
            'price' => reset($services)
        ];

        foreach ($services as $nameService => $priceService) {
            if ($search === 'min' && $priceService < $resService['price'] || $search === "max" && $priceService > $resService['price']) {
                $resService = [
                    'name' => $nameService,
                    'price' => $priceService,
                ];
            }
        }
        return $resService;
    }

    public function tableFormat(array $products): string //Паттерн таблицы
    {
        ob_start();
        ?>
        <div class="card p-3">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">name</th>
                    <th scope="col">price</th>
                    <th scope="col">tariff</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <th scope="row"><?= $product['ID'] ?></th>
                        <td scope="row"><?= $product['NAME'] ?></td>
                        <td scope="row"><?= $product['PRICE'] ?></td>
                        <td>
                            <?php
                            $tariffProduct = unserialize($product['TARIFF']);
                            if ($tariffProduct) {
                                ksort($tariffProduct);
                                foreach ($tariffProduct as $key => $tariff) {
                                    echo $key . ": " . $tariff . "<br>";
                                }
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function getChangeTariff() //Вывод Продукта, у которого изменение TARIFF начинается раньше всех
    {
        $db = new sdbh();
        $products = $db->make_query("SELECT * FROM a25_products");

        $result = [];
        $firstDayTariffChange = false;

        foreach ($products as $product) {
            $tariffs = unserialize($product['TARIFF']);

            if (!$tariffs || count($tariffs) < 2) {
                continue;
            }

            $priceTariff = reset($tariffs);

            foreach ($tariffs as $day => $tariff) {
                if ($tariff != $priceTariff && ($firstDayTariffChange === false || $day < $firstDayTariffChange)) {
                    $result = $product;
                    $firstDayTariffChange = $day;
                }
            }
        }

        return $result;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin = new AdminService();
    $admin->searchProduct();
}