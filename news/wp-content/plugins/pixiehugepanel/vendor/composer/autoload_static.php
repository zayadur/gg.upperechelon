<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticIniteea132d5f53b0a82e87ab7791752ec55
{
    public static $prefixesPsr0 = array (
        'A' => 
        array (
            'Alaouy\\Youtube\\' => 
            array (
                0 => __DIR__ . '/..' . '/alaouy/youtube/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticIniteea132d5f53b0a82e87ab7791752ec55::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
