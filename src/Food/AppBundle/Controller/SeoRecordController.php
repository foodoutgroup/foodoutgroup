<?php

namespace Food\AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Food\AppBundle\Entity\SeoRecord;
use Food\AppBundle\Form\SeoRecordType;

/**
 * SeoRecord controller.
 *
 * @Route("/seorecord")
 */
class SeoRecordController extends Controller
{

    /**
     * Lists all SeoRecord entities.
     *
     * @Route("/", name="seorecord")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('FoodAppBundle:SeoRecord')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new SeoRecord entity.
     *
     * @Route("/", name="seorecord_create")
     * @Method("POST")
     * @Template("FoodAppBundle:SeoRecord:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new SeoRecord();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('seorecord_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a SeoRecord entity.
     *
     * @param SeoRecord $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(SeoRecord $entity)
    {
        $form = $this->createForm(new SeoRecordType(), $entity, array(
            'action' => $this->generateUrl('seorecord_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new SeoRecord entity.
     *
     * @Route("/new", name="seorecord_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new SeoRecord();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a SeoRecord entity.
     *
     * @Route("/{id}", name="seorecord_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FoodAppBundle:SeoRecord')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SeoRecord entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing SeoRecord entity.
     *
     * @Route("/{id}/edit", name="seorecord_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FoodAppBundle:SeoRecord')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SeoRecord entity.');
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
    * Creates a form to edit a SeoRecord entity.
    *
    * @param SeoRecord $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(SeoRecord $entity)
    {
        $form = $this->createForm(new SeoRecordType(), $entity, array(
            'action' => $this->generateUrl('seorecord_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing SeoRecord entity.
     *
     * @Route("/{id}", name="seorecord_update")
     * @Method("PUT")
     * @Template("FoodAppBundle:SeoRecord:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('FoodAppBundle:SeoRecord')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find SeoRecord entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('seorecord_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a SeoRecord entity.
     *
     * @Route("/{id}", name="seorecord_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('FoodAppBundle:SeoRecord')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find SeoRecord entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('seorecord'));
    }

    /**
     * Creates a form to delete a SeoRecord entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('seorecord_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
