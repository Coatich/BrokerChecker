<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="stylesheet.css">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Broker Checker</title>
</head>
<body> 
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow" >
    <div class="container-fluid">
      <a class="navbar-brand text-light" href="index.php">
        Broker Checker Prototype
      </a>
    </div>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <select class="form-select w-auto" name="korisnik">
          <option selected>Izaberite korisnika</option>
          <option value="Petar">Pera Peric</option>
          <option value="Mika">Mika Mikic</option>
        </select>
      </div>
  </nav>

<!-- Kreira search formu i priprema prikaz  -->
<div class="container">
    <?php include 'php/search-form.php'; ?>
    <div id="table-wrapper">
      <?php include 'php/table-template.php'; ?>
    </div>
  </div>

<?php include 'html/comment-modal.html'; ?>
<?php include 'html/broker-modal.html'; ?>



<footer class="text-center py-3 mt-4" style="background-color: #f0f0f0; color: #333; position: fixed; width: 100%; bottom: 0; left: 0;">
  <div>
    v0725.alpha1
  </div>
  <div class="small text-muted">
    Made by Aleksandar Petrovic
  </div>
</footer>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
<script src="js/broker-form.js"></script>
<script src="js/comments.js"></script>
<script src="js/search.js"></script>

</body>

</html>