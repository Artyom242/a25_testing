<?php
require_once 'App/Domain/Users/UserEntity.php';
require_once 'App/Application/AdminService.php';

use App\Domain\Users\UserEntity;
use App\Application\AdminService;

$user = new UserEntity();
if (!$user->isAdmin) die('Доступ закрыт');

$admin = new AdminService();
?>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="assets/css/style.css" rel="stylesheet"/>
</head>
<body>
<header class="p-3 bg-dark text-white">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none">
                <img width="50px" src="/../../assets/img/logo.png" alt="">
            </a>

            <form id="searchFrom" class="ms-auto d-flex gap-2 col-12 col-lg-auto mb-3 mb-lg-0 me-lg-3"
                  method="post" action="App/Application/AdminService.php">
                <input type="search" name="text" class="form-control form-control-dark" placeholder="Search..."
                       aria-label="Search">
                <button type="submit" class="btn btn-outline-light me-2">Поиск</button>
            </form>
        </div>
    </div>
</header>
<div class="container mt-4">
    <h1 class="mb-4">Админка</h1>

    <div class="mb-4 mt-4" id="search_wrapper" style="display: none">
        <h3 class="mb-3">Результаты поиска:</h3>
        <div class="d-flex gap-3 flex-wrap" id="container_search"></div>
    </div>

    <h5 class="mb-2">Количество продуктов - <?= $admin->getCountProducts() ?></h5>
    <div class="mb-3">
        <h5 class="mb-3">Продукты без тарифа:</h5>
        <?php $productsNoTariff = $admin->getNotTariff() ?>
        <?php if ($productsNoTariff): ?>
            <?= $admin->tableFormat($productsNoTariff) ?>
        <?php else: ?>
            <div>
                <p>Таких продуктов нет</p>
            </div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <?php $cheapProducts = $admin->getCheapOrExpensiveService('min') ?>
        <h5>Самая дешевая дополнительная услуга:</h5>
        <?= $cheapProducts['name'] . ": " . $cheapProducts['price'] ?>
    </div>
    <div class="mb-3">
        <?php $cheapProducts = $admin->getCheapOrExpensiveService('max') ?>
        <h5>Самая дорогая дополнительная услуга:</h5>
        <?= $cheapProducts['name'] . ": " . $cheapProducts['price'] ?>
    </div>
    <div class="mb-3">
        <h5>Изменение TARIFF начинается раньше всех:</h5>
        <?php $productChangeTariff = $admin->getChangeTariff() ?>
        <?php if ($productChangeTariff): ?>
            <?= $admin->tableFormat(array($productChangeTariff)) ?>
        <?php else: ?>
            <div>
                <p>Таких продуктов нет</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        $('#searchFrom').submit(function (event) {
            event.preventDefault();

            $.ajax({
                url: 'App/Application/AdminService.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    $('#search_wrapper').show();
                    console.log(response);
                    if (response && response.length > 0) {
                        var htmlBody = '';
                        response.forEach(function (item) {
                            htmlBody += '<div class="card bg-primary text-white card-body p-3 col-sm-3 rounded">';
                            htmlBody += '<h5>Цена - ' + item.PRICE + '</h5>';

                            if (item.TARIFF) {
                                htmlBody += '<h5 class="mb-3">Тарифы:</h5>';
                                htmlBody += '<table class="table table-bordered text-white">';
                                htmlBody += '<thead><tr><th>День</th><th>Цена</th></tr></thead>';
                                htmlBody += '<tbody>';

                                for (let day in item.TARIFF) {
                                    if (item.TARIFF.hasOwnProperty(day)) {
                                        htmlBody += '<tr><td>' + day + '</td>';
                                        htmlBody += '<td>' + item.TARIFF[day] + '</td></tr>';
                                    }
                                }

                                htmlBody += '</tbody>';
                                htmlBody += '</table>';
                            } else {
                                htmlBody += '<p><strong>Тарифы отсутствуют</strong></p>';
                            }

                            htmlBody += '</div>';
                        });

                        $('#container_search').html(htmlBody);
                    } else {
                        $("#container_search").text('Таких продуктов нет');
                    }

                },
                error: function () {
                    console.log("Ошибка")
                    $("#container_search").text('Ошибка при расчете');
                }
            });
        });
    });
</script>
</body>
</html>