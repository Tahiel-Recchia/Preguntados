<?php

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
class qrModel
{

    public function getQr($usuarioId){
        $dominio = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $perfilUrl = $dominio . "/perfil?id=" . $usuarioId;

        // Crear QR
        $qr = QrCode::create($perfilUrl);
        $writer = new PngWriter();
        $qrResult = $writer->write($qr);

        // Pasar imagen base64 a la vista
        $url = 'data:image/png;base64,' . base64_encode($qrResult->getString());
        return $url;
    }
}