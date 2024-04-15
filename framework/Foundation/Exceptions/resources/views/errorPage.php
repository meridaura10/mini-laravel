<!doctype html>
<?php /** @var \Framework\Kernel\Foundation\Exceptions\ErrorPageViewModel $viewModel */ ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style><?= $viewModel->getAssetCssContents('app.css') ?></style>
    <title>
        <?= $viewModel->title() ?>
    </title>
</head>
<body>
<div class="app">
    <div class="container">
        <div class="header_wrap">
            <header class="header">
                <div>
                    <p class="header_title"><?= $viewModel->title() ?></p>
                </div>
            </header>
        </div>
        <div class="content">
            <div class="aside_wrap">
                <aside>
                    <ul class="trace">
                        <?php foreach ($viewModel->trace() as $item): ?>
                            <li>

                                <div>
                                    <?php if (array_key_exists('file', $item)) { ?>
                                        <span>File: </span> <?php echo $item['file'] . ' ' . $item['line']; ?><br>
                                        <span>Function: </span> <?php echo $item['function']; ?>
                                    <?php } else { ?>
                                        <span>File: </span> <?php echo $item['class'] ?><br>
                                        <span>Type: </span> <?php echo $item['type']; ?><br>
                                        <span>Function: </span> <?php echo $item['function']; ?>
                                        <?php } ?>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            </div>
            <div class="preview">

            </div>
        </div>
    </div>
</div>
</body>
</html>