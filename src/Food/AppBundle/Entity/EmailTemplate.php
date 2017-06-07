<?php

namespace Food\AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Food\AppBundle\Validator\Constraints as AppAssert;


/**
 *
 * @ORM\Table(name="email_template")
 * @ORM\Entity(repositoryClass="Food\AppBundle\Entity\EmailTemplateRepository")
 * @Gedmo\TranslationEntity(class="Food\AppBundle\Entity\EmailTemplateLocalized")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class EmailTemplate
{
     /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="Food\AppBundle\Entity\EmailTemplateLocalized", mappedBy="object", cascade={"persist", "remove"})
     **/
    private $translations;

    /**
     * @var string
     * @Gedmo\Translatable
     * @ORM\Column(name="templateId", type="string", length=255)
     */
    private $templateId;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="source", type="string", length=255, nullable=true)
     */
    private $source;

    /**
     * @var boolean
     *
     * @ORM\Column(name="preorder", type="boolean", nullable=true)
     */
    private $preorder;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=16, nullable=true)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;


    /**
     * @var boolean
     *
     * @ORM\Column(name="use_for_all", type="boolean", nullable=true)
     */
    private $useForAll;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="edited_at", type="datetime", nullable=true)
     */
    private $editedAt;


    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     **/
    private $createdBy;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     */
    private $editedBy;

    /**
     * @var \Food\UserBundle\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    private $deletedBy;


    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Add translations
     *
     * @param EmailTemplate $t
     */
    public function addTranslation(EmailTemplateLocalized $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    /**
     * Remove translations
     *
     * @param EmailTemplateLocalized $translations
     */
    public function removeTranslation(EmailTemplateLocalized $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }


    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;

    }

    /**
     * @return string
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * @param string $templateId
     * @return $this
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;

    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isPreorder()
    {
        return $this->preorder;
    }

    /**
     * @param boolean $preorder
     * @return $this
     */
    public function setPreorder($preorder)
    {
        $this->preorder = $preorder;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }


    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     * @return $this
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getEditedAt()
    {
        return $this->editedAt;
    }

    /**
     * @param \DateTime|null $editedAt
     */
    public function setEditedAt($editedAt)
    {
        $this->editedAt = $editedAt;
    }

    /**
     * @return \Food\UserBundle\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param \Food\UserBundle\Entity\User $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return \Food\UserBundle\Entity\User
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * @param \Food\UserBundle\Entity\User $editedBy
     */
    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
    }

    /**
     * @return \Food\UserBundle\Entity\User
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @param \Food\UserBundle\Entity\User $deletedBy
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
    }

    /**
     * @return bool
     */
    public function isUseForAll()
    {
        return $this->useForAll;
    }

    /**
     * @param bool $useForAll
     */
    public function setUseForAll($useForAll)
    {
        $this->useForAll = $useForAll;
    }

}