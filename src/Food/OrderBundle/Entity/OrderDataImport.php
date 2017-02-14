<?php

namespace Food\OrderBundle\Entity;

use Food\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Food\OrderBundle\FoodOrderBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Table(name="order_data_import")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity
 */
class OrderDataImport
{
    const SERVER_PATH_TO_FILE_FOLDER = 'uploads/order_data_import';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="\Food\UserBundle\Entity\User")
     * @ORM\JoinColumn(referencedColumnName="id")
     **/
    private $user;

    /**
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * Unmapped property to handle file uploads
     */
    private $file;

    /**
     * @ORM\Column(name="filename", type="string");
     */
    private $filename;

    /**
     * @ORM\Column(name="infodata", type="text", nullable=true)
     */
    private $infodata;

    /**
     * @ORM\ManyToMany(targetEntity="Food\OrderBundle\Entity\Order", inversedBy="orders")
     */
    private $ordersChanged;

    /**
     * @var string
     * @ORM\Column(name="is_imported", type="boolean", nullable=true)
     */
    private $isImported = null;

    public function __construct()
    {
        $this->ordersChanged = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return OrderDataImport
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set user
     *
     * @param \Food\UserBundle\Entity\User $user
     * @return OrderDataImport
     */
    public function setUser(\Food\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Food\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getUsername()
    {
        $username = '*deleted*';
        $user = $this->getUser();
        if ($user instanceof User) {
            $username = (string)$user;
        }

        return $username;
    }

    /**
     * @return mixed
     */
    public function getInfodata()
    {
        return $this->infodata;
    }

    /**
     * @param mixed $infodata
     */
    public function setInfodata($infodata)
    {
        $this->infodata = $infodata;
    }

    /**
     * @return \Food\OrderBundle\Entity\Order[]
     */
    public function getOrdersChanged()
    {
        return $this->ordersChanged;
    }

    /**
     * @param \Food\OrderBundle\Entity\Order $ordersChanged
     */
    public function setOrdersChanged($ordersChanged) {
        $this->ordersChanged = $ordersChanged;
    }

    /**
     * @param \Food\OrderBundle\Entity\Order $ordersChanged
     */
    public function addOrdersChanged($ordersChanged) {
        $this->ordersChanged[]= $ordersChanged;
    }

    /**
     * @param \Food\OrderBundle\Entity\Order $ordersChanged
     */
    public function removeOrdersChanged($ordersChanged) {
        $this->ordersChanged->removeElement($ordersChanged);
    }

    /**
     * @return string
     */
    public function getIsImported()
    {
        return $this->isImported;
    }

    /**
     * @param string $isImported
     */
    public function setIsImported($isImported)
    {
        $this->isImported = $isImported;
    }

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Manages the copying of the file to the relevant place on the server
     */
    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }

        // we use the original file name here but you should
        // sanitize it at least to avoid any security issues

        // move takes the target directory and target filename as params
        $this->getFile()->move(
            OrderDataImport::SERVER_PATH_TO_FILE_FOLDER,
            $this->getFile()->getClientOriginalName()
        );

        // set the path property to the filename where you've saved the file
        $this->filename = $this->getFile()->getClientOriginalName();

        // clean up the file property as you won't need it anymore
        $this->setFile(null);
    }

    /**
     * @ORM\PrePersist
     */
    public function lifecycleFileUpload() {
        $this->upload();
    }

    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param mixed $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }
}
