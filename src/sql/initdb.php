<?php
require_once(__DIR__.'/../vendor/autoload.php');

$sql = file_get_contents(__DIR__.'/schema.sql');
ORM::raw_execute($sql);

$sql = file_get_contents(__DIR__.'/'.$_ENV['SITE_TYPE'].'.sql');
if(trim($sql)) {
  ORM::raw_execute($sql);
}
