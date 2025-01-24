<?php include 'layout/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Rejestracja</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form action="/register" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nazwa użytkownika*</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email*</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Hasło*</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Potwierdź hasło*</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Imię</label>
                            <input type="text" class="form-control" id="first_name" name="first_name">
                        </div>
                        
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nazwisko</label>
                            <input type="text" class="form-control" id="last_name" name="last_name">
                        </div>
                        
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Data urodzenia*</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gender" class="form-label">Płeć*</label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">Wybierz płeć</option>
                                <option value="M">Mężczyzna</option>
                                <option value="F">Kobieta</option>
                                <option value="OTHER">Inna</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="seeking_gender" class="form-label">Szukam*</label>
                            <select class="form-control" id="seeking_gender" name="seeking_gender" required>
                                <option value="">Wybierz płeć</option>
                                <option value="M">Mężczyzny</option>
                                <option value="F">Kobiety</option>
                                <option value="OTHER">Innej</option>
                                <option value="ANY">Wszystkich</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Lokalizacja</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        
                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Zdjęcie profilowe</label>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">O mnie</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Zarejestruj się</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Masz już konto? <a href="/login">Zaloguj się</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
