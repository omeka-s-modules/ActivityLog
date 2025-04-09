<?php
namespace ActivityLog\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Site;
use Omeka\Entity\User;

/**
 * @Entity
 */
class ActivityLogEvent extends AbstractEntity
{
    /**
     * @Id
     * @Column(
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(
     *     type="float",
     *     nullable=false
     * )
     */
    protected $created;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $user;

    /**
     * @Column(
     *     type="string",
     *     length=45,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    protected $ip;

    /**
     * @Column(
     *     type="string",
     *     length=255,
     *     nullable=false
     * )
     */
    protected $event;

    /**
     * @Column(
     *     type="string",
     *     length=255,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    protected $resource;

    /**
     * @Column(
     *     type="string",
     *     length=255,
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    protected $resourceId;

    /**
     * @Column(
     *     type="json",
     *     nullable=true,
     *     options={
     *         "default"=null
     *     }
     * )
     */
    protected $data;

    public function getId()
    {
        return $this->id;
    }

    public function setCreated(float $created): void
    {
        $this->created = $created;
    }

    public function getCreated(): float
    {
        return $this->created;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setIp(?string $ip): void
    {
        $this->ip = $ip;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setEvent(string $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setResource(?string $resource): void
    {
        $this->resource = $resource;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResourceId(?string $resourceId): void
    {
        $this->resourceId = $resourceId;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
    }

    public function getData(): ?array
    {
        return $this->data;
    }
}
