<?php
namespace DV\Model ;

use Doctrine\Common\Collections\ArrayCollection ;
use Exception ;
use Doctrine\Common\Collections\Criteria;
use DV\Doctrine\Doctrine as doctrine_query;

abstract class DoctrineBaseAbstract
{
	use doctrine_query ;

	const REPOSITORY = 'repository' ;
	
	const DEFAULT_ENTITY_NAMESPACE = 'DV\Entity\\' ;
	
	const DESC = 'desc' ;

	/**
	 * Proxy to the repository class findBy
	 *
	 * @param array $_options
	 * @return mixed ;
	 */
	public function findBy(array $_options)
	{
		### check if the repository string is set
		if(array_key_exists('repository', $_options))	{
			### set the repository
			$repository = $_options['repository'] ;
			### set the repository
			$this->setRepositoryName($repository) ;

			unset($_options['repository']) ;
		}

		### get the string representation of instantiate Model Resource Class / Doctrine Entity Class name
		$entity_name = $this->getRepositoryName() ;

		### check for pre entity namespace
		/* if(false === strpos($entity_name , '\\'))	{
			$entity_name = self::DEFAULT_ENTITY_NAMESPACE . $entity_name ;
			### overwrite the repository name with the right syntax (lazyloading)
			$this->setRepositoryName($entity_name) ;
		} */
		$entity_name = $this->prefix_repository_name_with_namespace($entity_name) ;

		if(isset($_options['method']))	{
			###
			if(! isset($_options['method']['name']))	{
				throw new Exception('unable to determine the name of method to call on the entity repository proxy class') ;
			}
			### fetch the name of the method to call on the repository class
			$method = $_options['method']['name'] ;

			###
			if(! isset($_options['method']['params']))	{
				throw new Exception('unable to determine the name of method to call on the entity repository proxy class') ;
			}
			### fetch the params to pass
			$_params = $_options['method']['params'] ;

			### fetch the data using the custom repository that is attached to entity class using Annotation
			$result = $this->getDoctrineRepository($entity_name)->{$method}($_params) ;
		}


		if(isset($_options['row']))	{
			### fetch the row information
			$criteria = $_options['row'] ;
			### make sure that criteria is an array
			if(! is_array($criteria) && (! $criteria instanceof Criteria))	{
				throw new Exception('an array value is need to fetch a row result') ;
			}

			return $this->getDoctrineRepository($entity_name)->findOneBy($criteria);
		}

		if(isset($_options['rowset']))	{
			### fetch the row information
			$criteria = $_options['rowset'] ;
			### make sure that criteria is an array
			if(! is_array($criteria) && (! $criteria instanceof Criteria))	{
				throw new Exception('an array value is need to fetch a rowset result') ;
			}

			if(isset($criteria['order']))	{
				$orderBy = (array) $criteria['order'] ;
				unset($criteria['order'] ) ;
			}else{
				$orderBy = null ;
			}


			if(isset($criteria['limit']))	{
				$limit = $criteria['limit'] ;
				unset($criteria['limit'] ) ;
			}else{
				$limit = null ;
			}


			if(isset($criteria['offset']))	{
				$offset = $criteria['offset'] ;
				unset($criteria['offset'] ) ;
			}else{
				$offset = null ;
			}

            if(0 == count($_options['rowset']))    {
                $result = $this->getDoctrineRepository($entity_name)->findAll() ;
            }else {
                $result = $this->getDoctrineRepository($entity_name)->findBy($criteria, $orderBy, $limit, $offset);
            }
		}

		if(isset($_options['criteria']))	{
			### fetch the criteria value
			$criteria = $_options['criteria'] ;

			if(! $criteria instanceof \Doctrine\Common\Collections\Criteria)	{
				throw new Exception('an instance of collection criteria is required to perform the query') ;
			}
			### return the matching row
			$result = $this->getDoctrineRepository($entity_name)->matching($criteria) ;
		}

		### check for paginator here
		if(isset($_options['paginate']))	{
			### create the doctrine adapter for paginator
            return $this->paginate($_options , $result) ;
		}

		return $result ;
	}
	
	protected function prefix_repository_name_with_namespace($entity_name)
	{
		### check for pre entity namespace
		if(false === strpos($entity_name , '\\'))	{
			$entity_name = self::DEFAULT_ENTITY_NAMESPACE . ucfirst($entity_name) ;
			### overwrite the repository name with the right syntax (lazyloading)
			$this->setRepositoryName($entity_name) ;
		}
		
		return $this->getRepositoryName() ;
	}

}