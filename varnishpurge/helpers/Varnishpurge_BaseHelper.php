<?php
namespace Craft;

class Varnishpurge_BaseHelper extends BaseApplicationComponent
{
   public $hashAlgo = 'crc32';

   protected function hash($str) {
      return hash($this->hashAlgo, $str);
   }
}