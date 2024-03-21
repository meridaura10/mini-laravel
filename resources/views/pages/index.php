    <main>
        <div class="container">
            <h3 class="mt-3">Новинки</h3>
            <hr>
            <div class="movies">
                <?php foreach ($data as $item) { ?>
                    <p><?php echo $item; ?></p>
                <?php } ?>
            </div>
        </div>
    </main>
