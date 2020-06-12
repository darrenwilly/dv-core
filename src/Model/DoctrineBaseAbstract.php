<?php
declare(strict_types=1);
namespace DV\Model ;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Exception ;
use Doctrine\Common\Collections\Criteria;
use DV\Doctrine\Doctrine as doctrine_query;
use Symfony\Component\DependencyInjection\ContainerInterface ;
use Shared\Core\Security\Auth\Authentication;

abstract class DoctrineBaseAbstract
{
	use doctrine_query ;

	const REPOSITORY = 'repository' ;
	
	const DEFAULT_ENTITY_NAMESPACE = '%s\\Domain\\Entity\\%s' ;
	
	const DESC = 'desc' ;

	public function __construct(EntityManagerInterface $em , ContainerInterface $container)
    {
        $this->setDoctrineEntityManager($em);
        $this->setContainer($container) ;
    }

    public function getUserInfo($options=[])
    {
        $authenticationService = $this->getContainer()->get(Authentication::class) ;
        return $authenticationService->getUserInfo($options) ;
    }

    /**
	 * Proxy to the repository class findBy
	 *
	 * @param array $options
	 * @return mixed ;
	 */
	public function findBy(array $options)
	{
		### check if the repository string is set
		if(array_key_exists('repository', $options))	{
			### set the repository
			$repository = $options['repository'] ;
			### set the repository
			$this->setRepositoryName($repository) ;

			unset($options['repository']) ;
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

		if(isset($options['method']))	{
			###
			if(! isset($options['method']['name']))	{
				throw new Exception('unable to determine the name of method to call on the entity repository proxy class') ;
			}
			### fetch the name of the method to call on the repository class
			$method = $options['method']['name'] ;

			###
			if(! isset($options['method']['params']))	{
				throw new Exception('unable to determine the name of method to call on the entity repository proxy class') ;
			}
			### fetch the params to pass
			$params = $options['method']['params'] ;

			##
            $repositoryObject = $this->getDoctrineRepository($entity_name) ;
            ##
            if(method_exists($repositoryObject , 'setDoctrineEntityManager'))    {
                $repositoryObject->setDoctrineEntityManager($this->getDoctrineEntityManager());
            }
            if(method_exists($repositoryObject , 'setContainer'))    {
                $repositoryObject->setContainer($this->getContainer());
            }

			### fetch the data using the custom repository that is attached to entity class using Annotation
			$result = $repositoryObject->{$method}($params) ;
		}

		if(isset($options['qb']))	{
			##
            $qbOptions = $options['qb'] ;
            ##
            if(is_array($qbOptions) && isset($qbOptions['query']))    {
                ##
                $queryBuilder = $qbOptions['query'] ;
            }
			elseif($qbOptions instanceof QueryBuilder)    {
			    ##
                $queryBuilder = $qbOptions ;
            }

            if(is_array($qbOptions) &&  isset($qbOptions['query']) && isset($qbOptions['paginate']))    {
                ##
                return $this->paginate($options , $qbOptions['query']) ;
            }
            ##
            $result = $queryBuilder->getQuery()->getResult() ;
		}


		if(isset($options['row']))	{
			### fetch the row information
			$criteria = $options['row'] ;
			### make sure that criteria is an array
			if(! is_array($criteria) && (! $criteria instanceof Criteria))	{
				throw new Exception('an array value is need to fetch a row result') ;
			}

			return $this->getDoctrineRepository($entity_name)->findOneBy($criteria);
		}

		if(isset($options['rowset']))	{
			### fetch the row information
			$criteria = $options['rowset'] ;
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

            if(0 == count($options['rowset']))    {
                $result = $this->getDoctrineRepository($entity_name)->findAll() ;
            }else {
                $result = $this->getDoctrineRepository($entity_name)->findBy($criteria, $orderBy, $limit, $offset);
            }
		}

		if(isset($options['criteria']))	{
			### fetch the criteria value
			$criteria = $options['criteria'] ;

			if(! $criteria instanceof \Doctrine\Common\Collections\Criteria)	{
				throw new Exception('an instance of collection criteria is required to perform the query') ;
			}
			### return the matching row
			$result = $this->getDoctrineRepository($entity_name)->matching($criteria) ;
		}

		if(! isset($result))    {
            throw new Exception('error occured in processing of query, a result set cannot be derived') ;
        }

		### check for paginator here
		if(isset($options['paginate']))	{
			### create the doctrine adapter for paginator
            return $this->paginate($options , $result) ;
		}

		return $result ;
	}

	public function paginate($options , $result)
    {
        $container = $this->getContainer() ;
        ##
        $paginator = $container->get('knp_paginator') ;
        ##
        return $paginator->paginate(
            $result,
            (isset($options['page'])) ? $options['page'] : 1 ,
            (isset($options['onDisplay'])) ? $options['onDisplay'] : 10
        );
    }
	
	protected function prefix_repository_name_with_namespace($entity_name)
	{
		### check for pre entity namespace
		if(false === strpos($entity_name , '\\'))	{
		    ## fetch the namespace of the class calling the Model Repository
            $namespace = $this->fetch_called_class_namespace() ;
            ##
			$entity_name = sprintf(self::DEFAULT_ENTITY_NAMESPACE , $namespace , ucfirst($entity_name)) ;
			### overwrite the repository name with the right syntax (lazyloading)
			$this->setRepositoryName($entity_name) ;
		}
		
		return $this->getRepositoryName() ;
	}

	protected function fetch_called_class_namespace()
    {
        ##
        $called_class = get_called_class() ;
        ##
        if(false === strpos($called_class , '\\'))	{
            ##
            $exploded_name = explode('\\' , $called_class , 2) ;
            ##
            return $exploded_name[0] ;
        }
        ##
        return $called_class ;
    }

}