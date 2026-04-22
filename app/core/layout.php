<?php
// File: /app/Core/Layout.php

use App\Core\Auth;

$user = Auth::user();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Load Reading Center</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<header class="header">
    <div class="logo">UTILITY CO</div>
    <div class="title">
        Load Reading Center – <?= date('d M Y H:i') ?>
    </div>
    <div class="user">
        <?= $user['staff_name'] ?>
        <a href="/logout.php">Logout</a>
    </div>
</header>

<div class="container">
    <aside class="sidebar">
        <a href="/index.php">Dashboard</a>
        <?php if ($user['11kv_level'] === 'YES'): ?>
            <a href="/11kv-entry.php">11kV Entry</a>
        <?php endif; ?>
        <?php if ($user['33kv_level'] === 'YES'): ?>
            <a href="/33kv-entry.php">33kV Entry</a>
        <?php endif; ?>
        <a href="/interruptions.php">Interruptions</a>
        <a href="/reports.php">Reports</a>
    </aside>

    <main class="content">
