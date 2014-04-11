<?php
/**
 * Created by PhpStorm.
 * User: danny
 * Date: 13/03/14
 * Time: 16:03
 */

namespace Swoopaholic\Bundle\ContentBundle\Form;

use Swoopaholic\Bundle\ContentBundle\Entity\Document;
use Swoopaholic\Component\Form\Type\TabbedFormType;
use Swoopaholic\Component\Form\Type\TabType;
use Symfony\Component\Form\FormBuilderInterface;

class DocumentType extends TabbedFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $documentTab = $builder->create('document', new TabType(), array('inherit_data' => true, 'active' => true));

        $documentTab
            ->add('name')
            ->add('slug')
//            ->add('published', 'checkbox')
            ;

        $contentTab = $builder->create('content', new TabType());

        if (isset($options['data']) && ($options['data'] instanceof Document)) {
            $document = $options['data'];
            foreach ($document->getContent() as $id => $content) {
                $class = $content->getFormClass();
                $options = array('label' => false, 'data' => $content);
                $contentTab->add($id, new $class(), $options);
            }
        }

//        $seoTag = $builder->create('seo', new TabType(), array());

        $builder
            ->add($documentTab)
            ->add($contentTab)
        ;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'document';
    }
}