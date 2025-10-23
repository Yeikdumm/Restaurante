<?php
$path = __DIR__.'/../app/Libraries/fpdf.php';
echo 'Ruta: '.$path."<br>";
echo 'Existe: '.(file_exists($path)?'SI':'NO');
