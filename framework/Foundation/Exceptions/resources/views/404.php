<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Not Found</title>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box; /* Додаємо box-sizing для коректного обрахунку ширини та висоти */
        }
        .text {
            text-align: center;
            color: #bdaaaa;
            display: flex; /* Вирівнюємо текст по горизонталі */
            justify-content: center; /* Вирівнюємо по центру по горизонталі */
            align-items: center; /* Вирівнюємо по центру по вертикалі */
            height: 100vh; /* Займає всю висоту екрану */
            font-size: 1.5em;
            font-weight: 300;
        }
        .text span {
            padding: 0 .5em; /* Задаємо відступи для тексту */
        }

        .span_l{
            border-right: 2px solid #bdaaaa;
        }

        .container {
            background-color: rgba(26,32,44,1);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text">
        <span class="span_l">404</span>
        <span class="span_r"><?php echo e(trans('Not Found')) ?></span>
    </h1>
</div>
</body>
</html>
