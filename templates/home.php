<?php include 'layout/header.php'; ?>

<div class="container mt-5">
    <div class="jumbotron">
        <h1 class="display-4">Witaj w Serwisie Randkowym!</h1>
        <p class="lead">Znajdź swoją drugą połówkę już dziś.</p>
        <hr class="my-4">
        <div class="row">
            <div class="col-md-6">
                <form action="/search" method="GET" class="mb-4">
                    <div class="form-group">
                        <input type="text" name="q" class="form-control" placeholder="Szukaj...">
                    </div>
                    <button type="submit" class="btn btn-primary">Szukaj</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h2>Polecane Profile</h2>
            <!-- Tu będziemy wyświetlać profile z bazy danych -->
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
