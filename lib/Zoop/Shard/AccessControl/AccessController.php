<?php
/**
 * @link       http://zoopcommerce.github.io/shard
 * @package    Zoop
 * @license    MIT
 */
namespace Zoop\Shard\AccessControl;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Zoop\Common\User\RoleAwareUserInterface;
use Zoop\Shard\DocumentManagerAwareInterface;
use Zoop\Shard\DocumentManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Defines methods for a manager object to check permssions
 *
 * @since   1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class AccessController implements ServiceLocatorAwareInterface, DocumentManagerAwareInterface {

    use ServiceLocatorAwareTrait;
    use DocumentManagerAwareTrait;

    const owner = 'owner';
    const creator = 'creator';
    const updater = 'updater';

    protected $permissions = [];

    public function enableReadFilter(){
        $filter = $this->documentManager->getFilterCollection()->enable('readAccessControl');
        $filter->setAccessController($this);
    }

    /**
     * Determines if an action can be done by the current User
     *
     * @param array $action
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata
     * @param type $document
     * @return \Zoop\Shard\AccessControl\IsAllowedResult
     */
    public function areAllowed(array $actions, ClassMetadata $metadata = null, $document = null){

        $result = new AllowedResult(false);
        if (!isset($metadata)){
            $metadata = $this->documentManager->getClassMetadata(get_class($document));
        }

        if (!isset($metadata->permissions)){
            return $result;
        }

        if ( !isset($this->permissions[$metadata->name])){
            $this->permissions[$metadata->name] = [];
        }

        $roles = $this->getRoles();
        if (isset($document) && $username = $this->getUsername()){
            if (isset($metadata->owner) &&
                $metadata->reflFields[$metadata->owner]->getValue($document) == $username
            ){
                $roles[] = self::owner;
            }
            if (isset($metadata->stamp) && isset($metadata->stamp['createdBy']) &&
                $metadata->reflFields[$metadata->stamp['createdBy']]->getValue($document) == $username
            ){
                $roles[] = self::creator;
            }
            if (isset($metadata->stamp) && isset($metadata->stamp['updatedBy']) &&
                $metadata->reflFields[$metadata->stamp['updatedBy']]->getValue($document) == $username
            ){
                $roles[] = self::updater;
            }
        }

        foreach($metadata->permissions as $index => $permissionMetadata){

            if ( !isset($this->permissions[$metadata->name][$index])){
                $factory = $permissionMetadata['factory'];
                $this->permissions[$metadata->name][$index] = $factory::get($metadata, $permissionMetadata['options']);
            }

            $permission = $this->permissions[$metadata->name][$index];
            $newResult = $permission->areAllowed($roles, $actions);
            $allowed = $newResult->getAllowed();
            if ( ! isset($allowed)){
                continue;
            }
            $result->setAllowed($allowed);

            $new = $newResult->getNew();
            if (isset($new)){
                $result->setNew(array_merge($new, $newResult->getNew()));
            }

            $old = $newResult->getOld();
            if (isset($old)){
                $result->setOld(array_merge($old, $newResult->getOld()));
            }
        }

        if (isset($document)){
            if (count($result->getNew()) > 0){
                foreach ($result->getNew() as $field => $value){
                    if ($metadata->reflFields[$field]->getValue($document) != $value){
                        $result->setAllowed(false);
                        return $result;
                    }
                }
            }

            if (count($result->getOld()) > 0){
                $changeSet = $this->documentManager->getUnitOfWork()->getDocumentChangeSet($document);
                foreach ($result->getOld() as $field => $value){
                    if ($changeSet[$field][0] != $value){
                        $result->setAllowed(false);
                        return $result;
                    }
                }
            }
        }

        return $result;
    }

    protected function getRoles(){

        if ($this->serviceLocator->has('user') &&
            $user = $this->serviceLocator->get('user')
        ){
            if ($user instanceof RoleAwareUserInterface){
                return $user->getRoles();
            }
        }
        return [];
    }

    protected function getUsername(){
        if ($this->serviceLocator->has('user') &&
            $user = $this->serviceLocator->get('user')
        ){
            return $user->getUsername();
        }
    }
}