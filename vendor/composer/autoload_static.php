<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit48c93436b1418ad01f132c7df74a5dab
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit48c93436b1418ad01f132c7df74a5dab::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit48c93436b1418ad01f132c7df74a5dab::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
