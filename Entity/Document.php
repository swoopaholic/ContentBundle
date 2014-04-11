<?php
/**
 * Created by PhpStorm.
 * User: danny
 * Date: 13/03/14
 * Time: 10:10
 */

namespace Swoopaholic\Bundle\ContentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Endroid\Bundle\BehaviorBundle\Model\PublishableInterface;
use Endroid\Bundle\BehaviorBundle\Model\SluggableInterface;
use Swoopaholic\Component\Content\DocumentInterface;
use Swoopaholic\Component\Content\Part\HeaderType;
use Swoopaholic\Component\Content\Part\TextAreaType;

/**
 * Page
 *
 * @ORM\Table("swp_document")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Document implements DocumentInterface, SluggableInterface, PublishableInterface
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(length=255)
     */
    private $slug;

    /**
     * @var string
     * @ORM\Column(length=255)
     */
    private $template = 'default';

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $published = false;

    /**
     * @var array
     * @ORM\Column(name="content", type="array")
     */
    private $content;

    /**
     * @var array
     * @ ORM\Column(name="seo_tags", type="object")
     */
    private $seoTags;

    /**
     * @var array
     * @ ORM\Column(name="social_tags", type="object")
     */
    private $socialTags;

    public function __construct()
    {
        $this->content = new ArrayCollection(
            array(
                'content0' => new HeaderType('Title'),
                'content1' => new TextAreaType('Content')
            )
        );
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the slug.
     *
     * @param $slug
     * @return SluggableInterface
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Returns the slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Returns the sluggable.
     *
     * @return string
     */
    public function getSluggable()
    {
        return $this->name;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Sets the published status.
     *
     * @param $published
     * @return mixed
     */
    public function setPublished($published)
    {
        $this->published = $published;
        return $this;
    }

    /**
     * Returns the published status.
     *
     * @return string
     */
    public function isPublished()
    {
        return $this->published;
    }

    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $content
     * @return $this
     */
    public function addContent($content)
    {
        $this->content[] = $content;
       return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function removeContent($id)
    {
        return $this;
    }

    public function setSeoTags($tags)
    {
        $this->seoTags = $tags;
        return $this;
    }

    public function addSeoTag($seo)
    {
        $this->seo[] = $seo;
        return $this;
    }

    public function removeSeoTag($id)
    {
        return $this;
    }

    public function setSocialTags($tags)
    {
        $this->socialTags = $tags;
        return $this;
    }

    public function getSocialTags()
    {
        return $this->socialTags;
    }

    public function addSocialTag($tag)
    {
        $this->socialTags[] = $tag;
    }

    public function removeSocialTag($id)
    {
        return $this;
    }
}