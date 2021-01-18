<?php


namespace TheWebmen\Elastica\Interfaces;


interface IndexItemInterface
{
    /**
     * @return string
     */
    public static function getIndexName();

    /**
     * @return mixed
     */
    public static function getExtendedClasses();
}
