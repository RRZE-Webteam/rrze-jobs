<?php

$atts = '';
foreach($attributes as $key => $value){
    $atts .= $key . '="' . $value . '" ';
}

echo do_shortcode('[jobs ' . $atts . ']');
