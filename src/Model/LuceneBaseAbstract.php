<?php
namespace DV\Model ;

use DV\Lucene\Zend\TraitZS as LuceneBaseEngine ;

abstract class LuceneBaseAbstract extends BaseAbstract
{
    use LuceneBaseEngine ;
	
}