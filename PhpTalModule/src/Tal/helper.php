<?php
/**
 * helper: custom PHPTAL modifier
 *
 * @param string $src
 * @param string $nothrow
 * @return string
 */


function phptal_tales_helper( $src, $nothrow )
{
   $src = 'this->' . trim($src);
   return PHPTAL_Php_Transformer::transform($src, '$ctx->');
}