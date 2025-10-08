<?php

class Renderer
{
    public function __construct()
    {
    }

    public function render($template, $data = null)
    {
        include_once("vista/partial/header.php");
        include_once("vista/" . $template . "Vista.php");
        include_once("vista/partial/footer.php");
    }
}
