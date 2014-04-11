<?php

namespace Swoopaholic\Bundle\ContentBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Swoopaholic\Bridge\Pagerfanta\Pagerfanta;
use Swoopaholic\Bundle\ContentBundle\Form\DocumentType;
use Swoopaholic\Bundle\FrameworkBundle\CrudTable\Type\CrudActionType;
use Swoopaholic\Bundle\ContentBundle\Entity\Document;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Acme\NvsDemoBundle\Form\Type as FormType;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class DocumentController extends Controller
{
    /**
     * @param $document
     * @return array
     *
     * @Template()
     */
    public function viewAction($document)
    {
        return array('page' => $document->getContent());
    }

    /**
     * @param Request $request
     *
     * @Route("", name="swoopaholic_document_admin")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $route = 'swoopaholic_document_admin';
        $router = $this->get('router');

        $currentPage = (int) $request->get('page', 1);
        $maxPerPage = $this->container->getParameter('nvs_framework.tables.max_per_page');

        $adapter = new \Pagerfanta\Adapter\DoctrineORMAdapter($this->getQueryBuilder($request));

        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxPerPage);
        $pagerfanta->setCurrentPage($currentPage);

        $factory = $this->get('nvs_framework.crud_table.factory');
        $factory->setRoute($route)
            ->setItemActionCallback(array($this, 'createActions'))
            ->setData($pagerfanta->getIterator())
            ->addColumn('id', 'Id', 'id')
            ->addColumn('name', 'Name', 'name')
            ->addColumn('slug', 'Slug', 'slug')
        ;

        return array(
            'pager' => $pagerfanta->createView($router, $route, $this->getRequest()->query->all()),
            'table' => $factory->createTable()->createView()
        );
    }

    /**
     * Creates a new Page entity.
     *
     * @Route("/", name="swoopaholic_document_admin_create")
     * @Method("POST")
     * @Template("AcmeNvsDemoBundle:Page:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Document();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('swoopaholic_document_admin_edit', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new Page entity.
     *
     * @Route("/new", name="swoopaholic_document_admin_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Document();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Page entity.
     *
     * @Route("/{id}", name="swoopaholic_document_admin_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SwpContentBundle:Document')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Page entity.
     *
     * @Route("/{id}", name="swoopaholic_document_admin_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('SwpContentBundle:Document')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Document entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('swoopaholic_document_admin'));
    }

    /**
     * Displays a form to edit an existing Page entity.
     *
     * @Route("/{id}/edit", name="swoopaholic_document_admin_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SwpContentBundle:Document')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Document entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing Page entity.
     *
     * @Route("/{id}", name="swoopaholic_document_admin_update")
     * @Method("PUT")
     * @Template("SwpContentBundle:Document:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('SwpContentBundle:Document')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Document entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            // We need to reset the content, or Doctrine won't calculate the changeset
            $entity->setContent(new ArrayCollection($entity->getContent()->toArray()));

            $em->flush();
            return $this->redirect($this->generateUrl('swoopaholic_document_admin_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    public function createActions($factory, $item)
    {
        $router = $this->get('router');

        $id = $item->getId();

        $actions = array();

//        $viewUrl = $router->generate('netvlies_page_admin_view', array('page' => $id));
//        $actions[] = $this->createCrudAction($factory, $id . '_view', $viewUrl, 'search');
//
        $editUrl = $router->generate('swoopaholic_document_admin_edit', array('id' => $id));
        $actions[] = $this->createCrudAction($factory, $id . '_view', $editUrl, 'pencil');

//        $deleteUrl = $router->generate('swoopaholic_document_admin_delete', array('id' => $id));
//        $actions[] = $this->createCrudAction($factory, $id . '_delete', $deleteUrl, 'remove');

        return $actions;
    }

    protected function createCrudAction($factory, $id, $url, $icon)
    {
        return $factory->create(
            $id, new CrudActionType(), array('icon' => $icon, 'url' => $url)
        );
    }

    private function getQueryBuilder(Request $request)
    {
        $object = 'd';

        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('SwpContentBundle:Document')->createQueryBuilder($object);

        $sort = $request->get('sort', 'id');
        $sort = strchr($sort, '.') ? $sort : 'd.' . $sort;

        $qb->orderBy($sort, $request->get('dir', 'ASC'));

        return $qb;
    }

    /**
     * Creates a form to create a Page entity.
     *
     * @param Document $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Document $entity)
    {
        $form = $this->createForm(new DocumentType(), $entity, array(
            'action' => $this->generateUrl('swoopaholic_document_admin_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Creates a form to edit a Page entity.
     *
     * @param Document $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Document $entity)
    {
        $form = $this->createForm(new DocumentType(), $entity, array(
            'action' => $this->generateUrl('swoopaholic_document_admin_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Creates a form to delete a Page entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('swoopaholic_document_admin_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
            ;
    }
}
