<?php

namespace Pim\Bundle\VersioningBundle\Manager;

use Symfony\Component\Serializer\SerializerInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Pim\Bundle\VersioningBundle\Entity\VersionableInterface;
use Pim\Bundle\VersioningBundle\Entity\Version;

/**
 * Version builder
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class VersionBuilder
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Build a version from a versionable entity
     *
     * @param VersionableInterface $versionable
     *
     * @return \Pim\Bundle\VersioningBundle\Entity\Version
     */
    public function buildVersion(VersionableInterface $versionable, User $user)
    {
        $resourceName = get_class($versionable);
        $resourceId   = $versionable->getId();
        $numVersion   = $versionable->getVersion();
        // TODO: we don't use direct json serialize due to convert to audit data based on array_diff
        $data         = $this->serializer->normalize($versionable, 'csv');

        return new Version($resourceName, $resourceId, $numVersion, $data, $user);
    }

    /**
     * Create a log entry from current and previous version
     *
     * @param Version $current
     * @param Version $previous
     *
     * @return Audit
     */
    public function buildAudit(Version $current, Version $previous = null)
    {
        $newData = $current->getData();
        if ($previous) {
            $oldData = $previous->getData();
        } else {
            $oldData = array();
        }

        $diff = array_diff($newData, $oldData);
        $diffData = array();
        foreach (array_keys($diff) as $changedField) {
            if (isset($oldData[$changedField])) {
                $diffData[$changedField]= array('old' => $oldData[$changedField]);
            } else {
                $diffData[$changedField]= array('old' => '');
            }
            if (isset($newData[$changedField])) {
                $diffData[$changedField]['new'] = $newData[$changedField];
            } else {
                $diffData[$changedField]['new'] = '';
            }
            if (empty($diffData[$changedField]['new']) and empty($diffData[$changedField]['old'])) {
                unset($diffData[$changedField]);
            } elseif ($diffData[$changedField]['new'] == $diffData[$changedField]['old']) {
                unset($diffData[$changedField]);
            }
        }

        $action = ($current->getVersion() > 1) ? 'update' : 'create';
        $audit = new Audit();
        $audit->setAction($action);
        $audit->setObjectClass($current->getResourceName());
        $audit->setLoggedAt();
        $audit->setObjectName($current->getResourceName());
        $audit->setObjectId($current->getResourceId());
        $audit->setVersion($current->getVersion());
        $audit->setData($diffData);
        $audit->setUser($current->getUser());

        return $audit;
    }
}
