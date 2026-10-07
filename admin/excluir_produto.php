<?php require 'auth.php'; $s=$pdo->prepare('DELETE FROM produtos WHERE id=?');$s->execute([(int)($_GET['id']??0)]);header('Location:index.php');
