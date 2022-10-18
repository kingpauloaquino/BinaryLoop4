<?php
namespace king052188\BinaryLoops\Facades;

use Illuminate\Support\Facades\Facade;

class BLCore extends Facade
{
  protected static function getFacadeAccessor() {
    return 'king052188-binaryloops';
  }
}
