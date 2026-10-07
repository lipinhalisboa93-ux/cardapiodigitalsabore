<?php session_start(); if(!isset($_SESSION['usuario'])){header('Location:login.php');exit;} require '../config/db.php'; ?>
