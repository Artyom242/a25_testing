<?php
require_once 'App/Infrastructure/sdbh.php';

use sdbh\sdbh;

$dbh = new sdbh();
?>
<html>
<head>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="assets/css/style.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row row-header">
        <div class="col-12" id="count">
            <img src="assets/img/logo.png" alt="logo" style="max-height:50px"/>
            <h1>Прокат Y</h1>
        </div>
    </div>

    <div class="row row-form">
        <div class="col-12">
            <form action="App/calculate.php" method="POST" id="form">

                <?php $products = $dbh->make_query('SELECT * FROM a25_products');
                if (is_array($products)) { ?>
                    <label class="form-label" for="product">Выберите продукт:</label>
                    <select class="form-select" name="product" id="product">
                        <?php foreach ($products as $product) {
                            $name = $product['NAME'];
                            ?>
                            <option value="<?= $product['ID']; ?>"><?= $name; ?></option>
                        <?php } ?>
                    </select>
                <?php } ?>

                <label for="customRange1" class="form-label" id="count">Количество дней:</label>
                <input type="number" name="days" class="form-control" id="customRange1" min="1" max="30">

                <?php $services = unserialize($dbh->mselect_rows('a25_settings', ['set_key' => 'services'], 0, 1, 'id')[0]['set_value']);
                if (is_array($services)) {
                    ?>
                    <label for="customRange1" class="form-label">Дополнительно:</label>
                    <?php
                    $index = 0;
                    foreach ($services as $k => $s) {
                        ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="services[]" value="<?= $s; ?>"
                                   id="flexCheck<?= $index; ?>">
                            <label class="form-check-label" for="flexCheck<?= $index; ?>">
                                <?= $k ?>: <?= $s ?>
                            </label>
                        </div>
                        <?php $index++;
                    } ?>
                <?php } ?>

                <button type="submit" class="btn btn-primary">Рассчитать</button>
            </form>
            <h5>Итоговая стоимость: <span id="total-price" data-bs-html="true" data-bs-toggle="tooltip"
                                          data-bs-placement="top"
                ></span><span style="display: none;" data-bs-html="true" id="currencyRu"
                              data-bs-toggle="tooltip"
                              data-bs-placement="top"
                ><i class="ms-1 fa fa-info-circle" aria-hidden="true"></i></span></h5>
        </div>
    </div>
</div>

<script src="assets/js/script.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script>
    $(document).ready(function () {
        $("#form").submit(function (event) {
            event.preventDefault();
            $.ajax({
                url: 'App/calculate.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    $("#total-price").text(response.totalPrice);
                    console.log(response)
                    let correctDay = formatEnding(response.days);
                    let attrTitle = 'Выбрано: ' + response.days + ' ' + correctDay + "<br>" +
                        'Тариф: ' + response.tariff + ' р/сутки<br>' +
                        "+" + response.servicePrice + " р/сутки за доп.услуги";
                    $("#total-price").attr('data-bs-title', attrTitle);
                    $('#total-price').tooltip('dispose').tooltip();


                    let currencyPrices = getConvertPrices(response.convertPrices);
                    $("#currencyRu").show();
                    $("#currencyRu").attr('data-bs-title', currencyPrices);
                    $('#currencyRu').tooltip('dispose').tooltip();
                },
                error: function () {
                    $("#total-price").text('Ошибка при расчете');
                    $("#currencyRu").hide();
                }
            });
        });

        // В будущем доработать подстановку нужной иконки к своей валюте
        function getConvertPrices(currencies) {
            let res = '';
            for (let currency in currencies) {
                res += currencies[currency] + '¥' + "<br>";
            }

            return res
        }

        function formatEnding(number) {
            let lastNum = number % 10;
            let lastTwoNum = number % 100;

            if (lastNum === 1 && lastTwoNum !== 11) {
                return "день";
            } else if ([2, 3, 4].includes(lastNum) && ![12, 13, 14].includes(lastTwoNum)) {
                return 'дня';
            }
            return 'дней';
        }
    });
</script>
</body>
</html>