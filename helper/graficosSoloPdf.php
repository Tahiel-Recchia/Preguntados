<?php
// $datos = lista de jugadores
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        h2 { margin-bottom: 10px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #444;
            padding: 6px;
        }
        th {
            background: #eee;
        }
    </style>
</head>
<body>

<h2>Jugadores por país</h2>

<table>
    <tr>
        <th>País</th>
        <th>Cantidad</th>
    </tr>

    <?php foreach ($datos as $j): ?>
        <tr>
            <td><?= $j['pais'] ?></td>
            <td><?= $j['cantidad'] ?></td>
        </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
